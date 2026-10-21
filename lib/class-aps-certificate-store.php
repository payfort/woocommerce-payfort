<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * APS Certificate Store (e6f42acf)
 *
 * Apple Pay merchant certificates and private keys are kept in a directory that
 * is verified, by canonical (realpath) comparison, NOT to be web served. There
 * is deliberately no fallback inside wp-content or uploads and no read from a
 * legacy location: if no safe directory exists the store reports a persistent
 * error and Apple Pay stays unavailable (fail closed).
 *
 * Active directory, in order:
 *  1. APS_CERTIFICATES_DIR constant / `aps_apple_pay_certificates_dir` filter.
 *  2. The directory pinned in the `aps_apple_pay_certificates_location` option.
 *  3. First safe automatic candidate (<parent of document root>/ and
 *     <parent of ABSPATH>/aps-private-certificates), then pinned with add_option().
 *
 * A pin is never replaced because a directory is missing on one node (no
 * flip-flop between nodes). Multi-node installs must point every node at the
 * same shared, persistent mount via APS_CERTIFICATES_DIR.
 *
 * Files are raw PEM, mode 0600 in a 0700 directory, written atomically
 * (temp file, verify, rename) with random names. Ownership and modes are
 * checked before any write or use; a failed chmod, or one whose re-read mode
 * is still loose, fails closed like any group/other accessible path.
 * Files left by earlier versions (uploads/aps-certificates,
 * wp-content/aps-private-<random> with a PHP exit guard, previously pinned
 * directories) are migrated in; a source is deleted only after the target was
 * read back and compared. On conflict or failure the source is preserved and
 * a persistent error is recorded; a web-served leftover that cannot be moved
 * keeps Apple Pay disabled until it is removed.
 *
 * @package    APS
 * @subpackage APS/lib
 */
class APS_Certificate_Store {

	const LEGACY_SUBDIR   = 'aps-certificates';
	const PRIVATE_SUBDIR  = 'aps-private-certificates';
	const FALLBACK_OPTION = 'aps_apple_pay_certificates_fallback_dir';
	const DIR_OPTION      = 'aps_apple_pay_certificates_location';
	const ERRORS_OPTION   = 'aps_apple_pay_certificates_errors';
	const SITE_MARKER = '.aps-site';
	const GUARD           = "<?php exit; ?>\n";
	const MAX_BYTES       = 262144;
	const NAME_PATTERN    = '/^[A-Za-z0-9][A-Za-z0-9._-]{0,127}$/';
	const OWNED_PATTERN   = '/\.(crt|key)\.pem$/';

	/** @var array|null Per-request status cache. */
	private static $status = null;

	/** @var bool True only inside an authenticated wp-admin request (the only place that may change anything). */
	private static $admin = false;

	/** @var string Replacement pin, written once the old location is fully migrated. */
	private static $pending_pin = '';

	/** @var array Optional 'chmod' / 'unlink' / 'euid' callables replacing the native calls (tests inject failures). */
	private static $fs = array();

	/* ------------------------------------------------------------------ *
	 * Public API
	 * ------------------------------------------------------------------ */

	/**
	 * New random file name for an uploaded certificate ('crt') or key ('key').
	 *
	 * @param string $kind 'crt' or 'key'.
	 * @return string
	 */
	public static function new_filename( $kind ) {
		$kind = 'key' === $kind ? 'key' : 'crt';
		return bin2hex( random_bytes( 16 ) ) . '.' . $kind . '.pem';
	}

	/**
	 * True when the store is usable: safe directory resolved, migration finished
	 * without problems. Apple Pay must be treated as unavailable otherwise.
	 *
	 * @return bool
	 */
	public static function is_ready() {
		return array() === self::get_problems();
	}

	/**
	 * Problems detected on this node, each array( 'code' => ..., 'message' => ... ).
	 *
	 * @return array
	 */
	public static function get_problems() {
		return self::status()['problems'];
	}

	/**
	 * Absolute path of a stored file inside the verified directory.
	 *
	 * @param string $filename Logical name saved in aps_apple_pay_certificates.
	 * @return string Empty string when unavailable for ANY reason.
	 */
	public static function get_file_path( $filename ) {
		if ( ! self::valid_name( $filename ) ) {
			return '';
		}
		$status = self::status();
		if ( array() !== $status['problems'] || '' === $status['dir'] ) {
			return '';
		}
		$path = $status['dir'] . $filename;
		return ( is_file( $path ) && ! is_link( $path ) && is_readable( $path ) && self::make_private( $path, 0600 ) ) ? $path : '';
	}

	/**
	 * Contents of a stored file, or '' when unavailable.
	 *
	 * @param string $filename
	 * @return string
	 */
	public static function read_pem( $filename ) {
		$path = self::get_file_path( $filename );
		if ( '' === $path ) {
			return '';
		}
		$contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return false === $contents ? '' : $contents;
	}

	/**
	 * Apple merchant identifier (certificate subject UID) or ''.
	 *
	 * @return string
	 */
	public static function merchant_identifier() {
		$certificates = get_option( 'aps_apple_pay_certificates' );
		if ( ! is_array( $certificates ) || empty( $certificates['apple_certificate_path_file'] ) ) {
			return '';
		}
		$pem = self::read_pem( $certificates['apple_certificate_path_file'] );
		if ( '' === $pem ) {
			return '';
		}
		$parsed = openssl_x509_parse( $pem );
		return ( is_array( $parsed ) && isset( $parsed['subject']['UID'] ) && is_string( $parsed['subject']['UID'] ) ) ? $parsed['subject']['UID'] : '';
	}

	/**
	 * Store an uploaded certificate/key under $filename (raw PEM, atomic).
	 *
	 * @param string $tmp_name Uploaded temp file.
	 * @param string $filename Name from new_filename().
	 * @param string $kind     'crt' or 'key'.
	 * @return bool
	 */
	public static function store_uploaded_file( $tmp_name, $filename, $kind = 'crt' ) {
		if ( ! is_uploaded_file( $tmp_name ) || ! self::valid_name( $filename ) || ! self::mutation_allowed() ) {
			return false;
		}
		$contents = file_get_contents( $tmp_name, false, null, 0, self::MAX_BYTES + 1 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		@unlink( $tmp_name ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false === $contents || ! self::looks_like_pem( $contents, $kind ) ) {
			return false;
		}
		$status = self::status();
		if ( '' === $status['dir'] || self::blocking_errors( $status ) ) {
			return false;
		}
		$result = self::write_atomic( $status['dir'] . $filename, $contents ) === 'ok';
		self::reset();
		return $result;
	}

	/**
	 * Read a stored file while the only problems are stale references (file_missing,
	 * bad_reference), so an administrator can repair a broken configuration by uploading.
	 * Any other error (unsafe, mismatching site, unmarked, missing directory) still blocks.
	 *
	 * @param string $filename
	 * @return string PEM or ''.
	 */
	private static function read_for_repair( $filename ) {
		if ( ! self::valid_name( $filename ) ) {
			return '';
		}
		$status = self::status();
		if ( '' === $status['dir'] || self::blocking_errors( $status ) ) {
			return '';
		}
		$path = $status['dir'] . $filename;
		if ( ! is_file( $path ) || is_link( $path ) || ! is_readable( $path ) || ! self::make_private( $path, 0600 ) ) {
			return '';
		}
		$contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return false === $contents ? '' : $contents;
	}

	/**
	 * Errors that make writing unsafe. Stale or loose references and leftovers outside the
	 * store are allowed so they can be repaired; they still keep the store not ready.
	 *
	 * @param array $status
	 * @return bool
	 */
	private static function blocking_errors( $status ) {
		foreach ( $status['problems'] as $p ) {
			if ( ! in_array( $p['code'], array( 'file_missing', 'bad_reference', 'file_permissions', 'exposed_source', 'source_unreadable', 'source_symlink' ), true ) && 0 !== strpos( $p['code'], 'migration_' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Commit an upload transaction: verify that cert and key belong together, save the
	 * option, confirm it took effect, and only then delete the superseded files. On any
	 * failure the newly stored files are removed and the current configuration is kept.
	 *
	 * @param array  $old        Current option value.
	 * @param array  $new        Newly stored files (option key => file name).
	 * @param string $passphrase Key passphrase.
	 * @return string '' on success, otherwise an error message.
	 */
	public static function commit_upload( $old, $new, $passphrase = '' ) {
		$old    = is_array( $old ) ? $old : array();
		$merged = array_merge( $old, $new );
		$fail   = static function ( $message ) use ( $new ) {
			foreach ( $new as $file ) {
				self::delete_file( $file );
			}
			return $message;
		};
		if ( empty( $new ) ) {
			return '';
		}
		$status = self::status();
		if ( '' === $status['dir'] || self::blocking_errors( $status ) ) {
			return 'The certificate store is not available on this server. Nothing was changed.';
		}
		if ( ! empty( $merged['apple_certificate_path_file'] ) && ! empty( $merged['apple_certificate_key_file'] )
			&& ! self::pair_matches( $merged['apple_certificate_path_file'], $merged['apple_certificate_key_file'], $passphrase ) ) {
			return $fail( 'The certificate and the private key do not match (or the key passphrase is wrong). Nothing was changed.' );
		}
		update_option( 'aps_apple_pay_certificates', $merged );
		self::reset();
		$saved = get_option( 'aps_apple_pay_certificates' );
		foreach ( array( 'apple_certificate_path_file', 'apple_certificate_key_file' ) as $key ) {
			if ( ! is_array( $saved ) || ( isset( $saved[ $key ] ) ? $saved[ $key ] : '' ) !== ( isset( $merged[ $key ] ) ? $merged[ $key ] : '' ) ) {
				return $fail( 'The certificate settings could not be saved. Nothing was changed.' );
			}
		}
		foreach ( $old as $key => $file ) {
			if ( isset( $new[ $key ] ) && $new[ $key ] !== $file && is_string( $file ) ) {
				self::delete_file( $file );
			}
		}
		return '';
	}

	/**
	 * Delete a stored file. Call after the option no longer references it.
	 *
	 * @param string $filename
	 * @return void
	 */
	public static function delete_file( $filename ) {
		if ( ! self::valid_name( $filename ) || ! self::mutation_allowed() ) {
			return;
		}
		$status = self::status();
		if ( '' === $status['dir'] ) {
			return;
		}
		$path = $status['dir'] . $filename;
		if ( is_file( $path ) || is_link( $path ) ) {
			self::fs_unlink( $path );
		}
		self::reset();
	}

	/**
	 * admin_init hook: run migration/validation once an administrator is in wp-admin.
	 *
	 * @return void
	 */
	public static function maybe_migrate_on_admin() {
		if ( wp_doing_ajax() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		self::enter_admin_context();
		self::reset();
		self::status();
	}

	/**
	 * Mark the current request as an authenticated administrator request. Only then may
	 * the store create its directory, pin it, migrate, write, delete or persist errors;
	 * every other request (frontend, REST, cron, CLI) is strictly read-only.
	 * Callers must have checked the capability.
	 *
	 * @return void
	 */
	public static function enter_admin_context() {
		self::$admin = true;
		self::reset();
	}

	/**
	 * Leave the admin context (tests / long running processes).
	 *
	 * @return void
	 */
	public static function leave_admin_context() {
		self::$admin = false;
		self::reset();
	}

	/**
	 * Non-blocking findings (leftovers that could not be migrated, exposed duplicates).
	 *
	 * @return array
	 */
	public static function get_warnings() {
		return self::status()['warnings'];
	}

	/**
	 * Whether the certificate and key belong together. Both are read from the store.
	 *
	 * @param string $cert_name       Stored certificate file name.
	 * @param string $key_name        Stored key file name.
	 * @param string $key_passphrase  Passphrase of an encrypted key, '' when none.
	 * @return bool False when either file is unavailable or they do not match.
	 */
	public static function pair_matches( $cert_name, $key_name, $key_passphrase = '' ) {
		$cert_pem = self::read_for_repair( $cert_name );
		$key_pem  = self::read_for_repair( $key_name );
		if ( '' === $cert_pem || '' === $key_pem ) {
			return false;
		}
		$cert = openssl_x509_read( $cert_pem );
		$key  = openssl_pkey_get_private( $key_pem, (string) $key_passphrase );
		return false !== $cert && false !== $key && openssl_x509_check_private_key( $cert, $key );
	}

	/**
	 * Install the client certificate on a cURL handle at the moment of use. Fails closed:
	 * when the files are not available now (they may have vanished since an earlier
	 * check) the handle is pointed at a certificate that cannot exist, so the request
	 * fails inside cURL instead of being sent without the client certificate.
	 *
	 * @param resource|\CurlHandle $curl_handle
	 * @param string               $key_passphrase
	 * @return bool True when the certificate and key were installed.
	 */
	public static function apply_client_cert( $curl_handle, $key_passphrase = '' ) {
		$certificates = get_option( 'aps_apple_pay_certificates' );
		$cert         = '';
		$key          = '';
		if ( is_array( $certificates ) && ! empty( $certificates['apple_certificate_path_file'] ) && ! empty( $certificates['apple_certificate_key_file'] ) && self::is_ready() ) {
			$cert = self::get_file_path( $certificates['apple_certificate_path_file'] );
			$key  = self::get_file_path( $certificates['apple_certificate_key_file'] );
		}
		if ( '' === $cert || '' === $key ) {
			curl_setopt( $curl_handle, CURLOPT_SSLCERT, '/nonexistent/aps-client-certificate-unavailable.pem' );
			curl_setopt( $curl_handle, CURLOPT_SSLKEY, '/nonexistent/aps-client-certificate-unavailable.pem' );
			return false;
		}
		curl_setopt( $curl_handle, CURLOPT_SSLCERT, $cert );
		curl_setopt( $curl_handle, CURLOPT_SSLKEY, $key );
		curl_setopt( $curl_handle, CURLOPT_SSLKEYPASSWD, (string) $key_passphrase );
		return true;
	}

	/**
	 * admin_notices hook / page helper: persistent error notice.
	 *
	 * @return void
	 */
	public static function render_notice() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$problems = self::get_problems();
		$warnings = self::get_warnings();
		if ( array() === $problems && array() === $warnings ) {
			return;
		}
		echo '<div class="notice ' . ( $problems ? 'notice-error' : 'notice-warning' ) . '"><p><strong>';
		echo esc_html( $problems ? __( 'Amazon Payment Services: Apple Pay is disabled because its certificates cannot be stored safely.', 'amazon-payment-services' ) : __( 'Amazon Payment Services: Apple Pay certificate storage needs attention.', 'amazon-payment-services' ) );
		echo '</strong></p><ul>';
		foreach ( array_merge( $problems, $warnings ) as $problem ) {
			echo '<li>' . esc_html( $problem['message'] ) . '</li>';
		}
		echo '</ul><p>' . esc_html__( 'Define APS_CERTIFICATES_DIR in wp-config.php as an absolute path outside the web root that already exists (on a shared, persistent mount when you run more than one web server), then open wp-admin again.', 'amazon-payment-services' ) . '</p></div>';
	}

	/**
	 * Forget cached status (tests, after changes).
	 *
	 * @return void
	 */
	public static function reset() {
		self::$status      = null;
		self::$pending_pin = '';
	}

	/* ------------------------------------------------------------------ *
	 * Path safety
	 * ------------------------------------------------------------------ */

	/**
	 * Canonical form of a possibly non-existing absolute path: realpath() of the
	 * nearest existing ancestor plus the remaining components. No trailing slash.
	 *
	 * @param string $path
	 * @return string|false False for relative paths, '..' segments or NUL bytes.
	 */
	public static function canonical_path( $path ) {
		$path = (string) $path;
		if ( '' === $path || false !== strpos( $path, "\0" ) ) {
			return false;
		}
		$path = str_replace( '\\', '/', $path );
		if ( ! preg_match( '#^(/|[A-Za-z]:/)#', $path ) ) {
			return false;
		}
		$parts = array();
		foreach ( explode( '/', $path ) as $i => $seg ) {
			if ( '..' === $seg ) {
				return false;
			}
			if ( '' === $seg || '.' === $seg ) {
				continue;
			}
			$parts[] = $seg;
		}
		$prefix = ( '/' === $path[0] ) ? '/' : '';
		if ( '' === $prefix ) {
			$prefix = array_shift( $parts ) . '/';
		}
		$rest = array();
		while ( true ) {
			$probe = $prefix . implode( '/', $parts );
			$real  = @realpath( '' === implode( '/', $parts ) ? $prefix : $probe ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( false !== $real ) {
				$real = rtrim( str_replace( '\\', '/', $real ), '/' );
				return $real . ( $rest ? '/' . implode( '/', array_reverse( $rest ) ) : '' );
			}
			if ( empty( $parts ) ) {
				return false;
			}
			$rest[] = array_pop( $parts );
		}
	}

	/**
	 * Every directory a web server may serve files from, canonicalised.
	 * Extra roots (for example an Nginx root PHP cannot see) can be added with
	 * the `aps_certificate_web_roots` filter.
	 *
	 * @return array
	 */
	public static function web_roots() {
		$candidates = array( ABSPATH );
		if ( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
			$candidates[] = (string) $_SERVER['DOCUMENT_ROOT'];
		}
		foreach ( array( 'WP_CONTENT_DIR', 'WP_PLUGIN_DIR', 'WPMU_PLUGIN_DIR' ) as $const ) {
			if ( defined( $const ) ) {
				$candidates[] = (string) constant( $const );
			}
		}
		$candidates[] = dirname( __DIR__ );
		if ( function_exists( 'wp_upload_dir' ) ) {
			$upload = wp_upload_dir( null, false );
			if ( ! empty( $upload['basedir'] ) ) {
				$candidates[] = (string) $upload['basedir'];
			}
		}
		if ( function_exists( 'get_theme_root' ) ) {
			$candidates[] = (string) get_theme_root();
		}
		$candidates = (array) apply_filters( 'aps_certificate_web_roots', $candidates );
		$roots      = array();
		foreach ( $candidates as $candidate ) {
			$canonical = self::canonical_path( $candidate );
			if ( false !== $canonical && '' !== $canonical ) {
				$roots[ $canonical ] = true;
			}
		}
		return array_keys( $roots );
	}

	/**
	 * Why $dir may not hold certificates ('' when acceptable).
	 *
	 * @param string $dir
	 * @return string
	 */
	public static function dir_rejection( $dir ) {
		$canonical = self::canonical_path( $dir );
		if ( false === $canonical || '' === $canonical || '/' === $canonical ) {
			return 'is not an absolute, normalised path';
		}
		foreach ( self::web_roots() as $root ) {
			if ( '/' === $root ) {
				continue;
			}
			if ( $canonical === $root || 0 === strpos( $canonical . '/', $root . '/' ) ) {
				return 'is inside the web-served location ' . $root;
			}
		}
		return '';
	}

	/* ------------------------------------------------------------------ *
	 * Status, resolution, migration
	 * ------------------------------------------------------------------ */

	/**
	 * @return bool
	 */
	private static function mutation_allowed() {
		return self::$admin && ( 'cli' !== PHP_SAPI || ( defined( 'APS_CERTIFICATES_ALLOW_CLI_MIGRATION' ) && APS_CERTIFICATES_ALLOW_CLI_MIGRATION ) );
	}

	/**
	 * @param string $path
	 * @param int    $mode
	 * @return bool chmod's own result; callers also re-read the mode with is_private().
	 */
	private static function fs_chmod( $path, $mode ) {
		if ( isset( self::$fs['chmod'] ) ) {
			return (bool) call_user_func( self::$fs['chmod'], $path, $mode );
		}
		return @chmod( $path, $mode ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_chmod
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	private static function fs_unlink( $path ) {
		if ( isset( self::$fs['unlink'] ) ) {
			return (bool) call_user_func( self::$fs['unlink'], $path );
		}
		return @unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	/**
	 * @return int|null Effective uid of the PHP process, null when it cannot be determined.
	 */
	private static function process_uid() {
		if ( isset( self::$fs['euid'] ) ) {
			$uid = call_user_func( self::$fs['euid'] );
		} else {
			$uid = function_exists( 'posix_geteuid' ) ? posix_geteuid() : null;
		}
		return is_int( $uid ) && $uid >= 0 ? $uid : null;
	}

	/**
	 * @return bool False where owner/mode cannot be verified (Windows ACLs, posix extension unavailable).
	 */
	private static function modes_verifiable() {
		return '\\' !== DIRECTORY_SEPARATOR && null !== self::process_uid();
	}

	/**
	 * Owned by the PHP process user and not accessible to group or others, from a fresh stat.
	 *
	 * @param string $path
	 * @return bool
	 */
	private static function is_private( $path ) {
		if ( ! self::modes_verifiable() ) {
			return false;
		}
		clearstatcache( true, $path );
		$perms = @fileperms( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false === $perms || 0 !== ( $perms & 0077 ) ) {
			return false;
		}
		$owner = @fileowner( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		return false !== $owner && self::process_uid() === $owner;
	}

	/**
	 * Ensure $path is private, tightening it only in the administrator context. Fails when
	 * chmod reports an error or when the re-read mode is still loose (ignored chmod).
	 *
	 * @param string $path
	 * @param int    $mode 0700 for directories, 0600 for files.
	 * @return bool
	 */
	private static function make_private( $path, $mode ) {
		if ( self::is_private( $path ) ) {
			return true;
		}
		if ( ! self::mutation_allowed() || ! self::modes_verifiable() ) {
			return false;
		}
		return self::fs_chmod( $path, $mode ) && self::is_private( $path );
	}

	/**
	 * @param mixed $name
	 * @return bool
	 */
	private static function valid_name( $name ) {
		return is_string( $name ) && 1 === preg_match( self::NAME_PATTERN, $name ) && '.php' !== substr( $name, -4 );
	}

	/**
	 * @param string $contents
	 * @param string $kind
	 * @return bool
	 */
	private static function looks_like_pem( $contents, $kind ) {
		if ( strlen( $contents ) > self::MAX_BYTES || false !== strpos( $contents, "\0" ) ) {
			return false;
		}
		if ( 'key' === $kind ) {
			return 1 === preg_match( '/-----BEGIN [A-Z ]*PRIVATE KEY-----/', $contents );
		}
		return false !== strpos( $contents, '-----BEGIN CERTIFICATE-----' ) && false !== openssl_x509_read( $contents );
	}

	/**
	 * Validate + resolve + migrate once per request.
	 *
	 * @return array dir (trailing slash or ''), problems.
	 */
	private static function status() {
		if ( null !== self::$status ) {
			return self::$status;
		}
		$all = array();
		$dir = self::resolve_dir( $all );
		if ( '' !== $dir ) {
			if ( self::mutation_allowed() ) {
				self::migrate_sources( $dir, $all );
			} else {
				self::report_exposed_sources( $dir, $all );
			}
			$errors_so_far = array_filter(
				$all,
				static function ( $p ) {
					return 'error' === $p['level'];
				}
			);
			if ( '' !== self::$pending_pin && array() === $errors_so_far && self::mutation_allowed() ) {
				update_option( self::DIR_OPTION, self::make_pin( self::$pending_pin ), false );
			}
			self::check_references( $dir, $all );
		}
		$errors   = array();
		$warnings = array();
		foreach ( $all as $p ) {
			if ( 'error' === $p['level'] ) {
				$errors[] = $p;
			} else {
				$warnings[] = $p;
			}
		}
		self::$status = array(
			'dir'      => $dir,
			'problems' => $errors,
			'warnings' => $warnings,
		);
		self::persist_problems( $all );
		return self::$status;
	}

	/**
	 * Add a problem.
	 *
	 * @param array  $problems
	 * @param string $code
	 * @param string $message
	 * @return void
	 */
	private static function problem( &$problems, $code, $message, $level = 'error' ) {
		$problems[] = array(
			'code'    => $code,
			'message' => $message,
			'level'   => $level,
		);
	}

	/**
	 * @return string
	 */
	private static function node_id() {
		return function_exists( 'gethostname' ) ? (string) gethostname() : 'node';
	}

	/**
	 * Keep a per-node record of the problems so the error is visible in the DB and
	 * survives requests. Written only when it changes.
	 *
	 * @param array $problems
	 * @return void
	 */
	private static function persist_problems( $problems ) {
		if ( ! self::mutation_allowed() ) {
			return;
		}
		$all  = get_option( self::ERRORS_OPTION, array() );
		$all  = is_array( $all ) ? $all : array();
		$node = self::node_id();
		$new  = array_map(
			static function ( $p ) {
				return $p['code'] . ': ' . $p['message'];
			},
			$problems
		);
		$old  = isset( $all[ $node ]['problems'] ) ? $all[ $node ]['problems'] : array();
		if ( $new === $old ) {
			return;
		}
		if ( $new ) {
			$all[ $node ] = array(
				'problems' => $new,
				'time'     => time(),
			);
		} else {
			unset( $all[ $node ] );
		}
		update_option( self::ERRORS_OPTION, $all, false );
	}

	/**
	 * Pick and validate the active directory. Never repins because a directory is
	 * missing on this node.
	 *
	 * @param array $problems
	 * @return string Directory with trailing slash, '' on failure.
	 */
	private static function resolve_dir( &$problems ) {
		if ( ! self::modes_verifiable() ) {
			self::problem( $problems, 'permissions_unverifiable', 'File ownership and permissions cannot be verified on this server (Windows, or the PHP posix extension is unavailable or disabled), so private keys are not used. Apple Pay certificate storage requires a POSIX server with the posix extension enabled.' );
			return '';
		}
		$configured = defined( 'APS_CERTIFICATES_DIR' ) ? (string) APS_CERTIFICATES_DIR : '';
		$configured = (string) apply_filters( 'aps_apple_pay_certificates_dir', $configured );
		$pin        = get_option( self::DIR_OPTION, '' );
		$pin_dir    = '';
		$source     = '';
		if ( '' !== $configured ) {
			// Explicit operator choice: used as is, never created, never replaced by a pin.
			$base   = $configured;
			$source = 'configured';
		} else {
			if ( is_array( $pin ) && ! empty( $pin['dir'] ) ) {
				if ( empty( $pin['site'] ) || ! hash_equals( self::site_id(), (string) $pin['site'] ) ) {
					// A pin copied from another environment (cloned database): never operate on it.
					self::problem( $problems, 'pin_site_mismatch', 'The stored certificate directory belongs to a different site or environment. Define APS_CERTIFICATES_DIR as a new, separate directory for this site and upload the certificate and key again.' );
					return '';
				}
				$pin_dir = (string) $pin['dir'];
			}
			// A plain-string pin is from an earlier plugin version: it is only a migration source.
			if ( '' !== $pin_dir ) {
				$base   = $pin_dir;
				$source = 'pinned';
			} else {
				if ( ! self::mutation_allowed() ) {
					self::problem( $problems, 'not_initialised', 'The certificate directory has not been set up yet; an administrator must open wp-admin once, or define APS_CERTIFICATES_DIR.' );
					return '';
				}
				$base = self::choose_automatic();
				if ( '' === $base ) {
					self::problem( $problems, 'no_safe_dir', 'No safe writable directory outside the web root could be determined. Define APS_CERTIFICATES_DIR with an existing absolute path outside the web root.' );
					return '';
				}
				$source = 'auto';
			}
		}

		$rejection = self::dir_rejection( $base );
		if ( '' !== $rejection && 'pinned' === $source && self::mutation_allowed() ) {
			// A web-served pin of this site is replaced by a safe automatic directory after migration.
			$base      = self::choose_automatic();
			$source    = 'repin';
			$rejection = '' === $base ? 'none' : self::dir_rejection( $base );
		}
		if ( '' !== $rejection ) {
			self::problem( $problems, 'unsafe_dir', 'The certificate directory is not acceptable: it is relative, or inside a web-served location. Use an absolute path outside the web root.' );
			return '';
		}
		$dir = self::with_site_namespace( self::canonical_path( $base ) ) . '/';

		if ( ! is_dir( $dir ) ) {
			// Only the automatic first-time choice may create a directory. A configured or pinned
			// directory that is missing is a mount/clone problem: creating an empty one could
			// hide the real files and let a migration put them in the wrong place.
			if ( ! in_array( $source, array( 'auto', 'repin' ), true ) || ! self::mutation_allowed() ) {
				self::problem( $problems, 'dir_missing', 'The certificate directory does not exist on this server. Create it (on multi-server setups mount the same shared directory on every node). Nothing is created or re-pinned automatically.' );
				return '';
			}
			if ( ! @mkdir( $dir, 0700, true ) && ! is_dir( $dir ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
				self::problem( $problems, 'dir_create_failed', 'Could not create the certificate directory.' );
				return '';
			}
		}
		// Re-validate with the existing directory resolved (symlinks).
		$real = @realpath( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false === $real || '' !== self::dir_rejection( $real ) ) {
			self::problem( $problems, 'unsafe_dir', 'The certificate directory resolves into a web-served location.' );
			return '';
		}
		$dir    = rtrim( str_replace( '\\', '/', $real ), '/' ) . '/';
		$marker = $dir . self::SITE_MARKER;
		// Another site's directory, or one not yet claimed outside wp-admin, is refused before
		// anything (including chmod) touches it.
		if ( ( file_exists( $marker ) || is_link( $marker ) || ! self::mutation_allowed() ) && ! self::check_marker( $dir, $problems ) ) {
			return '';
		}
		// Only directories the plugin created itself are tightened; an operator's directory may
		// be shared with other data, so its mode is verified but never changed.
		$plugin_dir = in_array( $source, array( 'auto', 'repin' ), true );
		if ( ! ( $plugin_dir ? self::make_private( $dir, 0700 ) : self::is_private( $dir ) ) ) {
			self::problem( $problems, 'dir_permissions', 'The certificate directory must be owned by the web server user and accessible to nobody else (mode 0700). Its permissions are not safe or could not be corrected, so nothing was read or written. Fix the owner/mode (chmod 700) and open wp-admin again.' );
			return '';
		}
		if ( ! is_writable( $dir ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
			self::problem( $problems, 'dir_not_writable', 'The certificate directory is not writable by the web server user.' );
			return '';
		}

		if ( ! self::check_marker( $dir, $problems ) ) {
			return '';
		}
		self::$pending_pin = ( 'repin' === $source ) ? self::strip_site_namespace( $dir ) : '';
		if ( 'auto' === $source && self::mutation_allowed() ) {
			// First pin wins; an existing pin of any kind is never replaced here (no flip-flop).
			if ( false === get_option( self::DIR_OPTION, false ) || '' === $pin || is_string( $pin ) ) {
				if ( is_string( $pin ) && '' !== $pin ) {
					self::$pending_pin = self::strip_site_namespace( $dir ); // Upgrade an old string pin after migration.
				} else {
					add_option( self::DIR_OPTION, self::make_pin( self::strip_site_namespace( $dir ) ), '', 'no' );
				}
			}
		}
		return $dir;
	}

	/**
	 * The directory carries a marker with the owning site id. A staging copy of the
	 * configuration that points at the production directory sees a foreign marker and
	 * refuses to read or change anything. An unmarked directory is adopted only by an
	 * administrator request; until then it is not used.
	 *
	 * @param string $dir
	 * @param array  $problems
	 * @return bool
	 */
	private static function check_marker( $dir, &$problems ) {
		$path = $dir . self::SITE_MARKER;
		if ( is_file( $path ) && ! is_link( $path ) ) {
			$found = trim( (string) @file_get_contents( $path, false, null, 0, 128 ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( hash_equals( self::site_id(), $found ) ) {
				if ( self::make_private( $path, 0600 ) ) {
					return true;
				}
				self::problem( $problems, 'dir_marker_permissions', 'The certificate directory ownership marker is accessible to others and could not be corrected to mode 0600; an administrator must open wp-admin (or chmod 600 the marker).' );
				return false;
			}
			self::problem( $problems, 'dir_site_mismatch', 'The certificate directory is owned by a different site or environment (for example a staging copy pointing at production). Nothing was read or changed. Use a separate directory for this site; ownership is never taken over automatically.' );
			return false;
		}
		if ( file_exists( $path ) || is_link( $path ) ) {
			self::problem( $problems, 'dir_marker_invalid', 'The certificate directory ownership marker is not a regular file.' );
			return false;
		}
		if ( ! self::mutation_allowed() ) {
			self::problem( $problems, 'dir_unmarked', 'The certificate directory has not been claimed by this site yet; an administrator must open wp-admin once.' );
			return false;
		}
		$handle = @fopen( $path, 'x' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( false === $handle ) {
			self::problem( $problems, 'dir_marker_failed', 'Could not claim the certificate directory for this site.' );
			return false;
		}
		$line    = self::site_id() . "\n";
		$written = fwrite( $handle, $line ) === strlen( $line ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		if ( ! $written || ! self::make_private( $path, 0600 ) ) {
			self::fs_unlink( $path );
			self::problem( $problems, 'dir_marker_failed', 'Could not claim the certificate directory for this site.' );
			return false;
		}
		return true;
	}

	/**
	 * @param string $dir Candidate migration source.
	 * @return bool True when it carries a marker of another site.
	 */
	private static function foreign_marker( $dir ) {
		$path = rtrim( $dir, '/' ) . '/' . self::SITE_MARKER;
		if ( ! is_file( $path ) ) {
			return false;
		}
		return ! hash_equals( self::site_id(), trim( (string) @file_get_contents( $path, false, null, 0, 128 ) ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	/**
	 * Identifier of this site, stored with the pin so a database copied to another
	 * environment (staging) refuses to use or modify the original's certificates.
	 *
	 * @return string
	 */
	private static function site_id() {
		return hash( 'sha256', function_exists( 'home_url' ) ? (string) home_url() : '' );
	}

	/**
	 * @param string $dir
	 * @return array
	 */
	private static function make_pin( $dir ) {
		return array(
			'dir'  => $dir,
			'site' => self::site_id(),
		);
	}

	/**
	 * Per-site subdirectory so multisite sites never share files.
	 *
	 * @param string $base canonical base, no trailing slash.
	 * @return string
	 */
	private static function with_site_namespace( $base ) {
		return ( function_exists( 'is_multisite' ) && is_multisite() ) ? $base . '/site-' . (int) get_current_blog_id() : $base;
	}

	/**
	 * @param string $dir
	 * @return string
	 */
	private static function strip_site_namespace( $dir ) {
		$dir = rtrim( $dir, '/' );
		return ( function_exists( 'is_multisite' ) && is_multisite() ) ? preg_replace( '#/site-\d+$#', '', $dir ) : $dir;
	}

	/**
	 * First safe automatic candidate.
	 *
	 * @return string
	 */
	private static function choose_automatic() {
		// The real web root must be known and consistent with ABSPATH. If PHP cannot see
		// it (reverse proxy, differing Nginx root) we cannot prove a directory is outside
		// it, so no automatic choice is made: the operator defines APS_CERTIFICATES_DIR.
		if ( empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
			return '';
		}
		$doc = self::canonical_path( (string) $_SERVER['DOCUMENT_ROOT'] );
		$abs = self::canonical_path( ABSPATH );
		if ( false === $doc || false === $abs || '/' === $doc || '/' === $abs ) {
			return '';
		}
		$related = ( $abs === $doc || 0 === strpos( $abs . '/', $doc . '/' ) || 0 === strpos( $doc . '/', $abs . '/' ) );
		if ( ! $related ) {
			return '';
		}
		$highest = ( strlen( $doc ) < strlen( $abs ) ) ? $doc : $abs;
		$base    = dirname( $highest );
		if ( '/' === $base || '.' === $base ) {
			return '';
		}
		$candidate = rtrim( $base, '/' ) . '/' . self::PRIVATE_SUBDIR;
		if ( '' !== self::dir_rejection( $candidate ) ) {
			return '';
		}
		$check = is_dir( $candidate ) ? $candidate : $base;
		return ( is_dir( $check ) && is_writable( $check ) ) ? $candidate : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
	}

	/**
	 * Directories from earlier versions that may hold certificate files.
	 *
	 * @param string $active Active directory (trailing slash).
	 * @return array of array( 'dir' => ..., 'exposed' => bool, 'all' => bool, 'guarded' => bool )
	 *               exposed: always web-served; all: migrate every file (else only referenced ones).
	 */
	private static function source_dirs( $active ) {
		$sources = array();
		$upload  = function_exists( 'wp_upload_dir' ) ? wp_upload_dir( null, false ) : array();
		if ( ! empty( $upload['basedir'] ) ) {
			$sources[] = array(
				'dir'     => rtrim( (string) $upload['basedir'], '/' ) . '/' . self::LEGACY_SUBDIR . '/',
				'exposed' => true,
				'all'     => true,
				'rmdir'   => true,
			);
		}
		if ( defined( 'WP_CONTENT_DIR' ) ) {
			$content = rtrim( (string) WP_CONTENT_DIR, '/' ) . '/';
			$names   = array();
			$option  = (string) get_option( self::FALLBACK_OPTION, '' );
			if ( 1 === preg_match( '/^aps-private-[a-z0-9]{32}$/', $option ) ) {
				$names[] = $option;
			}
			if ( ! ( function_exists( 'is_multisite' ) && is_multisite() ) && is_dir( $content ) ) {
				foreach ( (array) @scandir( $content ) as $entry ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					if ( is_string( $entry ) && 1 === preg_match( '/^aps-private-[a-z0-9]{32}$/', $entry ) ) {
						$names[] = $entry;
					}
				}
			}
			foreach ( array_unique( $names ) as $name ) {
				$sources[] = array(
					'dir'     => $content . $name . '/',
					'exposed' => true,
					'all'     => true,
					'rmdir'   => true,
				);
			}
		}
		$previous = array();
		$pin_raw  = get_option( self::DIR_OPTION, '' );
		$pinned   = is_array( $pin_raw ) ? ( isset( $pin_raw['dir'] ) ? (string) $pin_raw['dir'] : '' ) : (string) $pin_raw;
		if ( '' !== $pinned ) {
			$previous[] = $pinned;
		}
		$abs = self::canonical_path( ABSPATH );
		if ( false !== $abs && '/' !== $abs ) {
			$previous[] = dirname( $abs ) . '/' . self::PRIVATE_SUBDIR;
		}
		if ( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
			$doc = self::canonical_path( (string) $_SERVER['DOCUMENT_ROOT'] );
			if ( false !== $doc && '/' !== $doc ) {
				$previous[] = dirname( $doc ) . '/' . self::PRIVATE_SUBDIR;
			}
		}
		foreach ( $previous as $prev ) {
			$canonical = self::canonical_path( $prev );
			if ( false === $canonical ) {
				continue;
			}
			$variants = array( $canonical . '/' );
			if ( function_exists( 'is_multisite' ) && is_multisite() ) {
				$variants = array( $canonical . '/' ); // Flat, shared by sites: referenced files only.
			}
			foreach ( $variants as $variant ) {
				if ( $variant === $active || ! is_dir( $variant ) || self::foreign_marker( $variant ) ) {
					continue;
				}
				$real = @realpath( $variant ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				if ( false !== $real && rtrim( str_replace( '\\', '/', $real ), '/' ) . '/' === $active ) {
					continue;
				}
				$exposed   = '' !== self::dir_rejection( $variant );
				$sources[] = array(
					'dir'     => $variant,
					'exposed' => $exposed,
					'all'     => false,
					'rmdir'   => false,
				);
			}
		}
		return $sources;
	}

	/**
	 * Names referenced by the plugin option.
	 *
	 * @return array
	 */
	private static function referenced_names() {
		$names        = array();
		$certificates = get_option( 'aps_apple_pay_certificates' );
		if ( is_array( $certificates ) ) {
			foreach ( array( 'apple_certificate_path_file', 'apple_certificate_key_file' ) as $key ) {
				if ( ! empty( $certificates[ $key ] ) && is_string( $certificates[ $key ] ) ) {
					$names[] = $certificates[ $key ];
				}
			}
		}
		return $names;
	}

	/**
	 * Housekeeping files that older versions created.
	 *
	 * @return array
	 */
	private static function housekeeping() {
		return array( '.htaccess', 'index.php', 'index.html', 'web.config', self::SITE_MARKER, '.', '..' );
	}

	/**
	 * Why an entry of a source directory matters: '' (ignore), 'referenced', 'owned' (a
	 * plugin file nobody references) or 'shared' (plugin-named, unreferenced, in a directory
	 * other sites may use, so it must not be moved by this site).
	 *
	 * @param array  $source     From source_dirs().
	 * @param mixed  $entry      Directory entry.
	 * @param array  $referenced Names referenced by this site.
	 * @return string
	 */
	private static function source_candidate( $source, $entry, $referenced ) {
		if ( ! is_string( $entry ) || in_array( $entry, self::housekeeping(), true ) ) {
			return '';
		}
		$logical = self::logical_name( $entry );
		if ( in_array( $logical, $referenced, true ) || in_array( $entry, $referenced, true ) ) {
			return 'referenced';
		}
		if ( $source['all'] ) {
			return 'owned';
		}
		// A previously pinned directory may hold unrelated files: only plugin-named ones are ours.
		if ( ! $source['exposed'] || 1 !== preg_match( self::OWNED_PATTERN, $logical ) ) {
			return '';
		}
		return ( function_exists( 'is_multisite' ) && is_multisite() ) ? 'shared' : 'owned';
	}

	/**
	 * @param string $entry
	 * @return string Name without the '.php' guard suffix of older versions.
	 */
	private static function logical_name( $entry ) {
		return ( '.php' === substr( $entry, -4 ) ) ? substr( $entry, 0, -4 ) : $entry;
	}

	/**
	 * A readable regular file without any PEM block: not key material, may stay.
	 *
	 * @param string $path
	 * @return bool
	 */
	private static function harmless_leftover( $path ) {
		if ( is_link( $path ) || ! is_file( $path ) || ! is_readable( $path ) ) {
			return false;
		}
		$raw = @file_get_contents( $path, false, null, 0, self::MAX_BYTES + strlen( self::GUARD ) + 1 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return false !== $raw && strlen( $raw ) <= self::MAX_BYTES + strlen( self::GUARD ) && false === strpos( $raw, '-----BEGIN ' );
	}

	/**
	 * Report (never change anything) legacy files that remain in web-served locations.
	 * Anything that may be key material blocks readiness until an administrator run has
	 * removed it; plain non-PEM files are ignored.
	 *
	 * @param string $active
	 * @param array  $problems
	 * @return void
	 */
	private static function report_exposed_sources( $active, &$problems ) {
		$referenced = self::referenced_names();
		foreach ( self::source_dirs( $active ) as $source ) {
			if ( ! $source['exposed'] || ! is_dir( $source['dir'] ) ) {
				continue;
			}
			if ( is_link( rtrim( $source['dir'], '/' ) ) ) {
				self::problem( $problems, 'source_symlink', 'An earlier web-served certificate directory is a symbolic link. Apple Pay stays disabled until the link and any certificate files it exposes are removed manually.' );
				continue;
			}
			$entries = @scandir( $source['dir'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( false === $entries ) {
				self::problem( $problems, 'source_unreadable', 'A web-served certificate directory from an earlier version cannot be read, so it may still expose a private key. Apple Pay stays disabled until it is made readable by the web server user (then open wp-admin) or removed manually.' );
				continue;
			}
			foreach ( $entries as $entry ) {
				$candidate = self::source_candidate( $source, $entry, $referenced );
				if ( '' === $candidate || ( 'owned' === $candidate && self::harmless_leftover( $source['dir'] . $entry ) ) ) {
					continue;
				}
				self::problem( $problems, 'exposed_source', 'Certificate files from an earlier version remain in a web-served directory. Apple Pay stays disabled until an administrator opens wp-admin (they are moved automatically) or they are removed manually.' );
				break;
			}
		}
	}

	/**
	 * Copy certificate files from the old locations into $active. Sources are deleted
	 * only when they are web-served and the copy was verified; private (non-web)
	 * sources are copied and never deleted. Unreferenced plugin files whose name is
	 * invalid or taken are kept under a new random name. A web-served file that may be
	 * key material and could not be moved is an error until it is gone; non-PEM files
	 * are left alone (warning). Only called from the administrator context.
	 *
	 * @param string $active
	 * @param array  $problems
	 * @return void
	 */
	private static function migrate_sources( $active, &$problems ) {
		if ( ! self::mutation_allowed() ) {
			return;
		}
		$referenced = self::referenced_names();
		foreach ( self::source_dirs( $active ) as $source ) {
			if ( ! is_dir( $source['dir'] ) ) {
				continue;
			}
			$level = $source['exposed'] ? 'error' : 'warning';
			if ( is_link( rtrim( $source['dir'], '/' ) ) ) {
				// The link target may be another site's or unrelated storage: never read, copy or delete through it.
				self::problem( $problems, 'source_symlink', 'An earlier certificate directory is a symbolic link; it was not read or changed. If it is web-served, Apple Pay stays disabled until the link and any certificate files it exposes are removed manually.', $level );
				continue;
			}
			$entries = @scandir( $source['dir'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( false === $entries ) {
				self::problem( $problems, 'source_unreadable', $source['exposed'] ? 'A web-served certificate directory from an earlier version cannot be read, so it may still expose a private key. Apple Pay stays disabled until it is made readable by the web server user (then open wp-admin again) or removed manually.' : 'An earlier certificate directory could not be read; its files were not migrated.', $level );
				continue;
			}
			$left = 0;
			foreach ( $entries as $entry ) {
				$candidate = self::source_candidate( $source, $entry, $referenced );
				if ( '' === $candidate ) {
					continue;
				}
				++$left;
				if ( 'shared' === $candidate ) {
					self::problem( $problems, 'exposed_source', 'A certificate file that this site does not reference remains in a web-served directory shared by several sites. It was not moved because it may belong to another site. Apple Pay stays disabled until it is removed manually.' );
					continue;
				}
				$result = self::migrate_one( $source['dir'] . $entry, self::logical_name( $entry ), $active, (bool) $source['exposed'], 'referenced' === $candidate );
				if ( 'ok' === $result ) {
					--$left;
					continue;
				}
				if ( 'not_pem' === $result ) {
					self::problem( $problems, 'migration_not_pem', 'A file that is not a certificate was found in an earlier certificate directory; it was left in place.', 'warning' );
				} elseif ( $source['exposed'] ) {
					self::problem( $problems, 'migration_' . $result, 'A certificate file from an earlier version could not be moved out of a web-served directory (' . $result . '). Apple Pay stays disabled until it is gone: remove it manually from the server. A file the plugin cannot delete cannot be hidden from the web server by the plugin.' );
				} else {
					self::problem( $problems, 'migration_' . $result, 'A certificate file from an earlier location was not migrated (' . $result . '); it was left in place.', 'warning' );
				}
			}
			if ( $source['rmdir'] && $source['exposed'] && 0 === $left ) {
				foreach ( self::housekeeping() as $hk ) {
					if ( '.' !== $hk && '..' !== $hk && is_file( $source['dir'] . $hk ) ) {
						self::fs_unlink( $source['dir'] . $hk );
					}
				}
				@rmdir( $source['dir'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}
		}
	}

	/**
	 * Migrate a single file; the source is deleted only after the target was read
	 * back and equals the PEM extracted from the source.
	 *
	 * @param string $source
	 * @param string $logical   Logical (target) file name.
	 * @param string $active
	 * @param bool   $delete    Delete the source after a verified copy.
	 * @param bool   $keep_name The name is referenced and must be kept (conflicts fail).
	 * @return string 'ok' or a short failure reason.
	 */
	private static function migrate_one( $source, $logical, $active, $delete = false, $keep_name = true ) {
		if ( is_link( $source ) || ! is_file( $source ) ) {
			return 'not_regular_file';
		}
		$size = @filesize( $source ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false === $size || $size > self::MAX_BYTES + strlen( self::GUARD ) ) {
			return 'bad_size';
		}
		$raw = @file_get_contents( $source ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $raw ) {
			return 'unreadable';
		}
		$pem = ( 0 === strpos( $raw, self::GUARD ) ) ? substr( $raw, strlen( self::GUARD ) ) : $raw;
		if ( false === strpos( $pem, '-----BEGIN ' ) ) {
			return 'not_pem';
		}
		if ( false !== strpos( $pem, '<?php' ) ) {
			return 'unsafe_content';
		}
		if ( $keep_name && self::valid_name( $logical ) ) {
			$result = self::write_atomic( $active . $logical, $pem );
		} else {
			$result = self::write_renamed( $logical, $active, $pem );
		}
		if ( 'ok' !== $result ) {
			return $result;
		}
		if ( ! $delete ) {
			return 'ok';
		}
		self::fs_unlink( $source );
		clearstatcache( true, $source );
		return ( file_exists( $source ) || is_link( $source ) ) ? 'source_not_deleted' : 'ok';
	}

	/**
	 * Store an unreferenced file under its own name when that is valid and free, otherwise
	 * under an existing identical copy or a new random name (never overwrites anything).
	 *
	 * @param string $logical
	 * @param string $active
	 * @param string $pem
	 * @return string write_atomic() result.
	 */
	private static function write_renamed( $logical, $active, $pem ) {
		if ( self::valid_name( $logical ) ) {
			$result = self::write_atomic( $active . $logical, $pem );
			if ( 'conflict' !== $result ) {
				return $result;
			}
		}
		$existing = self::find_identical( $active, $pem );
		if ( '' !== $existing ) {
			return self::write_atomic( $active . $existing, $pem );
		}
		$kind = 1 === preg_match( '/-----BEGIN [A-Z ]*PRIVATE KEY-----/', $pem ) ? 'key' : 'crt';
		return self::write_atomic( $active . self::new_filename( $kind ), $pem );
	}

	/**
	 * Name of a stored file with exactly $contents, so repeated runs do not pile up copies.
	 *
	 * @param string $active
	 * @param string $contents
	 * @return string
	 */
	private static function find_identical( $active, $contents ) {
		$hash = hash( 'sha256', $contents );
		foreach ( (array) @scandir( $active ) as $entry ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$path = $active . $entry;
			if ( ! self::valid_name( $entry ) || '.pem' !== substr( $entry, -4 ) || is_link( $path ) || ! is_file( $path ) || filesize( $path ) !== strlen( $contents ) ) {
				continue;
			}
			if ( hash_equals( $hash, (string) hash_file( 'sha256', $path ) ) ) {
				return $entry;
			}
		}
		return '';
	}

	/**
	 * Atomic write with read-back verification. An existing target with the same
	 * content counts as success once it is private; a different one is a conflict and
	 * is never overwritten.
	 *
	 * @param string $target
	 * @param string $contents
	 * @return string 'ok', 'conflict', 'insecure_permissions', 'write_failed' or 'verify_failed'.
	 */
	private static function write_atomic( $target, $contents ) {
		if ( file_exists( $target ) || is_link( $target ) ) {
			return self::existing_target( $target, $contents );
		}
		$dir = dirname( $target );
		if ( ! self::is_private( $dir ) ) {
			return 'insecure_permissions';
		}
		$tmp = @tempnam( $dir, '.aps-tmp-' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false === $tmp ) {
			return 'write_failed';
		}
		$ok = false;
		$fh = @fopen( $tmp, 'wb' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen
		if ( $fh ) {
			$len = strlen( $contents );
			$ok  = ( fwrite( $fh, $contents ) === $len ) && fflush( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
			if ( function_exists( 'fsync' ) ) {
				$ok = $ok && fsync( $fh );
			}
			fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
		}
		$ok = $ok && self::make_private( $tmp, 0600 );
		$ok = $ok && hash_equals( hash( 'sha256', $contents ), (string) hash_file( 'sha256', $tmp ) );
		if ( ! $ok ) {
			self::fs_unlink( $tmp );
			return 'write_failed';
		}
		// link() fails when the target exists, so a concurrent writer (another node on a
		// shared mount) is never overwritten. The temp name is removed either way.
		$linked = @link( $tmp, $target ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		self::fs_unlink( $tmp );
		if ( ! $linked ) {
			if ( file_exists( $target ) ) {
				return self::existing_target( $target, $contents );
			}
			return 'write_failed'; // No hard-link support: fail closed.
		}
		clearstatcache( true, $target );
		if ( ! hash_equals( hash( 'sha256', $contents ), (string) hash_file( 'sha256', $target ) ) ) {
			return 'verify_failed';
		}
		return self::is_private( $target ) ? 'ok' : 'insecure_permissions';
	}

	/**
	 * @param string $target   Existing path.
	 * @param string $contents Expected contents.
	 * @return string 'ok', 'conflict' or 'insecure_permissions'.
	 */
	private static function existing_target( $target, $contents ) {
		$existing = ( is_link( $target ) || ! is_file( $target ) ) ? false : @file_get_contents( $target ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $existing || ! hash_equals( hash( 'sha256', $contents ), hash( 'sha256', $existing ) ) ) {
			return 'conflict';
		}
		return self::make_private( $target, 0600 ) ? 'ok' : 'insecure_permissions';
	}

	/**
	 * Every file named in the option must exist in the active directory and be private.
	 *
	 * @param string $dir
	 * @param array  $problems
	 * @return void
	 */
	private static function check_references( $dir, &$problems ) {
		foreach ( self::referenced_names() as $name ) {
			if ( ! self::valid_name( $name ) ) {
				self::problem( $problems, 'bad_reference', 'The stored certificate file name is invalid; upload the Apple Pay certificate and key again.' );
			} elseif ( ! is_file( $dir . $name ) || is_link( $dir . $name ) ) {
				self::problem( $problems, 'file_missing', 'A stored certificate file was not found in the certificate directory on this server; upload the Apple Pay certificate and key again.' );
			} elseif ( ! self::make_private( $dir . $name, 0600 ) ) {
				self::problem( $problems, 'file_permissions', 'A stored certificate file is not owned by the web server user or is accessible to others, and could not be corrected to mode 0600. Fix it (chmod 600) or upload the Apple Pay certificate and key again.' );
			}
		}
	}
}
