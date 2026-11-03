<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Guest checkout capabilities and database-atomic valU replay/abuse protection. */
class APS_Valu_Guard {
	const TTL = 900;
	const PREFIX = '_aps_valu_guard_';

	private static function context() {
		if ( ! function_exists( 'WC' ) || ! WC()->session || ! WC()->cart || WC()->cart->is_empty() ) {
			return false;
		}
		return array( (string) WC()->session->get_customer_id(), (int) get_current_user_id(), (string) WC()->cart->get_cart_hash() );
	}

	/** Render only in the uncached checkout form, never a public nonce/localized script. */
	public static function issue() {
		$context = self::context();
		if ( false === $context ) {
			return '';
		}
		$state = WC()->session->get( 'aps_valu_authorization' );
		if ( ! is_array( $state ) || $state['context'] !== $context || $state['expires'] <= time()
			|| ( ! wp_doing_ajax() && 'GET' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && self::exists( 'customer', $state['token'] ) ) ) {
			$state = array( 'token' => bin2hex( random_bytes( 32 ) ), 'context' => $context, 'expires' => time() + self::TTL );
			WC()->session->set( 'aps_valu_authorization', $state );
		}
		self::cleanup();
		return $state['token'];
	}

	public static function token() {
		$token = isset( $_POST['aps_valu_authorization'] ) ? $_POST['aps_valu_authorization'] : '';
		$context = self::context();
		$state = false !== $context ? WC()->session->get( 'aps_valu_authorization' ) : null;
		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' )
			|| ! is_string( $token ) || ! preg_match( '/\A[a-f0-9]{64}\z/', $token )
			|| ! is_array( $state ) || $state['expires'] <= time() || $state['context'] !== $context
			|| ! hash_equals( $state['token'], $token ) ) {
			throw new \Exception( __( 'Your valU checkout has expired. Reload checkout and verify your mobile number again.', 'amazon-payment-services' ) );
		}
		return $token;
	}

	private static function key( $kind, $identity ) {
		return self::PREFIX . hash_hmac( 'sha256', $kind . ':' . $identity, wp_salt( 'auth' ) );
	}

	private static function exists( $kind, $identity ) {
		global $wpdb;
		return null !== $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", self::key( $kind, $identity ) ) );
	}

	/** Unique option_name is the lock. No transient/cache read-modify-write race. */
	private static function claim( $kind, $identity, $expiry ) {
		global $wpdb;
		return 1 === $wpdb->query( $wpdb->prepare(
			"INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')",
			self::key( $kind, $identity ), (string) $expiry
		) );
	}

	private static function limit( $kind, $identity, $maximum ) {
		$window = (int) floor( time() / self::TTL );
		for ( $slot = 0; $slot < $maximum; ++$slot ) {
			if ( self::claim( 'limit-' . $kind, $identity . ':' . $window . ':' . $slot, ( $window + 2 ) * self::TTL ) ) {
				return;
			}
		}
		throw new \Exception( __( 'Too many valU attempts. Please wait 15 minutes before trying again.', 'amazon-payment-services' ) );
	}

	public static function begin_customer( $mobile ) {
		$token = self::token();
		if ( ! is_string( $mobile ) || ! preg_match( '/\A[0-9]{11,19}\z/', $mobile ) ) {
			throw new \Exception( __( 'Invalid mobile number.', 'amazon-payment-services' ) );
		}
		// Never trust forwarding headers here. A trusted-proxy real-IP module should
		// set REMOTE_ADDR; deployments can supply a verified client IP through this filter.
		$remote = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
		$ip = apply_filters( 'aps_valu_client_ip', $remote );
		if ( ! is_string( $ip ) || false === filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			throw new \Exception( 'Unable to verify the checkout client address.' );
		}
		self::limit( 'ip', bin2hex( inet_pton( $ip ) ), 20 );
		self::limit( 'customer', (string) WC()->session->get_customer_id(), 5 );
		// The valU form uses Egyptian mobile numbers. Group local/20/0020 forms
		// by their common national suffix, even when APS accepts multiple spellings.
		self::limit( 'phone', substr( $mobile, -10 ), 5 );
		if ( ! self::claim( 'customer', $token, time() + 2 * self::TTL ) ) {
			throw new \Exception( __( 'This valU verification has already been submitted. Reload checkout to start again.', 'amazon-payment-services' ) );
		}
		return $token;
	}

	public static function begin( $stage, $valu, $order, $otp = null ) {
		$token = self::token();
		if ( ! is_array( $valu ) || empty( $valu['authorization'] ) || ! hash_equals( $valu['authorization'], $token )
			|| ! $order || ! $order->needs_payment() || ! in_array( $stage, array( 'generate', 'verify', 'purchase' ), true ) ) {
			throw new \Exception( __( 'Your valU details could not be verified. Please start again.', 'amazon-payment-services' ) );
		}
		if ( 'generate' !== $stage && ( ! is_string( $otp ) || ! preg_match( '/\A[0-9]{4,10}\z/', $otp ) ) ) {
			throw new \Exception( __( 'Invalid OTP.', 'amazon-payment-services' ) );
		}
		if ( 'generate' !== $stage && ! self::exists( 'generate', $token ) ) {
			throw new \Exception( 'valU OTP has not been generated.' );
		}
		$identity = $token;
		if ( 'verify' === $stage ) {
			self::limit( 'otp', $token, 5 );
			$identity .= ':' . $otp;
		}
		if ( ! self::claim( $stage, $identity, time() + 2 * self::TTL ) ) {
			throw new \Exception( __( 'This valU step has already been submitted. Please start again.', 'amazon-payment-services' ) );
		}
		if ( 'verify' === $stage && ! self::claim( 'verify-transition', $token, time() + 2 * self::TTL ) ) {
			throw new \Exception( 'valU OTP verification is already submitted.' );
		}
		if ( 'purchase' === $stage && ! self::claim( 'order', (string) $order->get_id(), PHP_INT_MAX ) ) {
			// Deliberately not released on timeout/decline: reconcile the prior payment first.
			throw new \Exception( __( 'A valU payment was already submitted for this order. Please contact the store before retrying.', 'amazon-payment-services' ) );
		}
	}

	/** Only a definitive rejected OTP permits another code; success/timeout stays consumed. */
	public static function retry_rejected_otp( $valu ) {
		global $wpdb;
		$wpdb->delete( $wpdb->options, array( 'option_name' => self::key( 'verify-transition', $valu['authorization'] ) ) );
	}

	private static function cleanup() {
		global $wpdb;
		// Bounded cleanup of this class's expired rows only; order submission locks remain.
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND CAST(option_value AS UNSIGNED) < %d LIMIT 100",
			$wpdb->esc_like( self::PREFIX ) . '%', time()
		) );
	}
}
