<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://paymentservices.amazon.com/
 * @since      2.2.0
 *
 * @package    APS
 * @subpackage APS/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    APS
 * @subpackage APS/admin
 */
class APS_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    2.2.0
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    2.2.0
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    2.2.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version     = $version;
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
        add_action( 'admin_post_upload_apple_certificates', array( $this, 'upload_apple_certificates' ) );

        add_action( 'admin_notices', array( $this, 'aps_success_notice' ) );
        add_action( 'admin_notices', array( $this, 'aps_error_notice' ) );

        add_action( 'add_meta_boxes', array( $this, 'aps_meta_boxes' ) );
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    2.2.0
     */
    public function enqueue_styles() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in APS_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The APS_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/aps-admin.css', array(), $this->version, 'all' );

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    2.2.0
     */
    public function enqueue_scripts() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in APS_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The APS_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/aps-admin.js', array( 'jquery' ), $this->version, false );

    }

    /**
     * Load configuration Fields
     *
     * @return void
     */
    public static function load_config_fields( $args ) {
        $obj  = isset( $args['method_obj'] ) ? $args['method_obj'] : null;
        $file = isset( $args['file'] ) ? $args['file'] : null;
        if ( ! empty( $obj ) && ! empty( $file ) ) {
            include 'partials/' . $file;
        }
    }

    /**
     * Register admin menus
     */
    public function register_admin_menu() {
        add_options_page(
            __( 'Apple Pay Certificates', 'amazon-payment-services' ),
            __( 'Apple Pay Certificates', 'amazon-payment-services' ),
            'manage_options',
            'apple-pay-certificates',
            array( $this, 'render_apple_pay_certificates' )
        );
    }

    /**
     * Render apple pay certificates
     */
    public function render_apple_pay_certificates() {
        include 'pages/apple-pay-certificates.php';
    }

    /**
     * Upload apple pay certificates
     */
    public function upload_apple_certificates() {
        $aps_error_messages = array();
        $aps_success        = 'success';
        $certificates       = array();
        session_start();
        $nonceToken = sanitize_key($_POST['_upload_apple_certificates_nonce'] ?? '');
        if ( !empty($nonceToken) && wp_verify_nonce( $nonceToken, 'upload_apple_certificates' ) ) {
            $upload_dir    = wp_upload_dir();
            $uploding_path = $upload_dir['basedir'] . '/aps-certificates/';
            if (!file_exists($uploding_path)) {
                wp_mkdir_p($uploding_path);
                if (!file_exists(trailingslashit($uploding_path) . '.htaccess')) {
                    $file_handle = @fopen( trailingslashit($uploding_path) . '.htaccess', 'wb' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen
                    if ( $file_handle ) {
                        fwrite( $file_handle, 'deny from all' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
                        fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
                    }
                }
            }
            if (!file_exists(trailingslashit($uploding_path) . 'index.html')) {
                $file_handle = @fopen( trailingslashit($uploding_path) . 'index.html', 'wb' );
                if ( $file_handle ) {
                    fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
                }
            }

            $existingAppleCertificates = get_option( 'aps_apple_pay_certificates' );
            if ( isset( $_FILES['certificate_path_file'] ) && ! empty( $_FILES['certificate_path_file']['tmp_name'] ) ) {

                $certificate_path_file      = isset($_FILES['certificate_path_file']['name']) ? sanitize_file_name($_FILES['certificate_path_file']['name']) : '';
                $certificate_path_file_info = pathinfo( $certificate_path_file );
                if ( 'pem' == $certificate_path_file_info['extension'] ) {
                    $certificateName = hash('md5', $nonceToken . '.apple.crt.' . time());
                    $certificate_path_filename  = $certificateName.'.crt.' . $certificate_path_file_info['extension'];
                    if ( file_exists( $uploding_path . $certificate_path_filename ) ) {
                        unlink( $uploding_path . $certificate_path_filename );
                    }
                    $temp_name = sanitize_text_field($_FILES['certificate_path_file']['tmp_name']);

                    if ( move_uploaded_file( $temp_name, $uploding_path . $certificate_path_filename ) ) {
                        chmod( $uploding_path . $certificate_path_filename, 0755 );
                        $certificates['apple_certificate_path_file'] = $certificate_path_filename;

                        // if there is an older certificate, delete it
                        if ( isset( $existingAppleCertificates['apple_certificate_path_file'] ) && file_exists( $uploding_path . $existingAppleCertificates['apple_certificate_path_file'] ) ) {
                            unlink( $uploding_path . $existingAppleCertificates['apple_certificate_path_file'] );
                        }
                    } else {
                        $aps_error_messages[] = 'Unable to upload certificate path file.';
                    }
                } else {
                    $aps_error_messages[] = 'certificate crt file type not valid.';
                }
            }
            if ( isset( $_FILES['certificate_key_file'] ) && ! empty( $_FILES['certificate_key_file']['tmp_name'] ) ) {
                $certificate_key_file      = isset($_FILES['certificate_key_file']['name']) ? sanitize_file_name($_FILES['certificate_key_file']['name']) : '';
                $certificate_key_file_info = pathinfo( $certificate_key_file );
                if ( 'pem' == $certificate_key_file_info['extension']) {
                    $keyName = hash('md5', $nonceToken . '.apple.key.' . time());
                    $certificate_key_filename  = $keyName.'.key.' . $certificate_key_file_info['extension'];
                    if ( file_exists( $uploding_path . $certificate_key_filename ) ) {
                        unlink( $uploding_path . $certificate_key_filename );
                    }
                    $temp_name = sanitize_text_field($_FILES['certificate_key_file']['tmp_name']);
                    if ( move_uploaded_file( $temp_name, $uploding_path . $certificate_key_filename ) ) {
                        chmod( $uploding_path . $certificate_key_filename, 0755 );
                        $certificates['apple_certificate_key_file'] = $certificate_key_filename;

                        // if there is an older key, delete it
                        if ( isset( $existingAppleCertificates['apple_certificate_key_file'] ) && file_exists( $uploding_path . $existingAppleCertificates['apple_certificate_key_file'] ) ) {
                            unlink( $uploding_path . $existingAppleCertificates['apple_certificate_key_file'] );
                        }
                    } else {
                        $aps_error_messages[] = 'Unable to upload certificate key file.';
                    }
                } else {
                    $aps_error_messages[] = 'certificate key file type not valid.';
                }
            }

            if ( empty( $aps_error_messages ) && ! empty( $certificates ) ) {
                if ( ! empty( get_option( 'aps_apple_pay_certificates' ) ) ) {
                    $old_certificates = get_option( 'aps_apple_pay_certificates' );
                    $certificates     = array_merge( $old_certificates, $certificates );
                }
                update_option( 'aps_apple_pay_certificates', $certificates );
                $aps_success = 'Certificates uploaded successfully';
            }
        } else {
            $aps_error_messages[] = 'Request Expired. Please try again later.';
        }

        if ( ! empty( $aps_error_messages ) ) {
            $_SESSION['aps_error_messages'] = wp_kses_post_deep($aps_error_messages);
        } elseif ( ! empty( $aps_success ) ) {
            $_SESSION['aps_success_message'] = wp_kses_data($aps_success);
        }
        session_write_close();
        wp_safe_redirect( admin_url( 'options-general.php?page=apple-pay-certificates' ) );
    }

    /**
     * Display success notice
     */
    public function aps_success_notice() {
        session_start();
        if ( isset( $_SESSION['aps_success_message'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p> ' . wp_kses_data( $_SESSION['aps_success_message'] ) . '</p></div>';
        }
        unset( $_SESSION['aps_success_message'] );
        session_write_close();
    }

    /**
     * Display error notice
     */
    public function aps_error_notice() {
        session_start();
        if ( isset( $_SESSION['aps_error_messages'] ) ) {
            foreach ( $_SESSION['aps_error_messages'] as $msg ) {
                echo '<div class="notice notice-error is-dismissible"><p> ' . wp_kses_data( $msg ) . '</p></div>';
            }
        }
        unset( $_SESSION['aps_error_messages'] );
        session_write_close();
    }

    /**
     * Setup Meta boxes
     */
    public function aps_meta_boxes() {
        add_meta_box( 'aps_payment_information', __( 'APS Payment Information', 'woocommerce' ), array( $this, 'render_aps_payment_information' ), 'shop_order', 'side', 'core' );

        global $post;
        $order_id = $post->ID;
        $aps_data = get_post_meta( $order_id, 'aps_payment_response', true );
        if ( ! empty( $aps_data ) && isset( $aps_data['command'] ) && 'AUTHORIZATION' == $aps_data['command'] ) {
            add_meta_box( 'aps_payment_authorization_info', __( 'Capture/Void Authorization', 'woocommerce' ), array( $this, 'render_capture_aps_payment' ), 'shop_order', 'normal', 'high' );
        }
    }

    public function render_capture_aps_payment() {
        global $post;
        $order_id                 = $post->ID;
        $order                    = wc_get_order( $order_id );
        $aps_data                 = get_post_meta( $order_id, 'aps_payment_response', true );
        $aps_authorization_action = get_post_meta( $order_id, 'aps_authorization_command', true );
        if ( ! empty( $aps_data ) && isset( $aps_data['command'] ) && 'AUTHORIZATION' == $aps_data['command'] ) {
            $payment_method = $order->get_payment_method();
            $amount         = $order->get_total();
            $order_total    = $order->get_total();

            $total_captured      = 0;
            $total_void          = 0;
            $aps_capture_history = array();

            if ( ! empty( $aps_authorization_action ) ) {
                $aps_capture_history = $this->get_captured_amount_history( $order_id, $aps_authorization_action );
                $amt                 = array_sum( array_column( $aps_capture_history, 'amount' ) );
                if ( 'CAPTURE' === $aps_authorization_action ) {
                    $total_captured = $amt;
                } else {
                    $total_void = $amt;
                }
            }
            $remain_capture = $order_total - $total_captured;
            include 'partials/aps-payment-capture.php';
        }
    }

    public function get_captured_amount_history( $order_id, $authorization_command ) {
        global $wpdb;
        $history = $wpdb->get_results($wpdb->prepare("
			SELECT DATE_FORMAT(posts.post_date, '%%M %%d, %%Y') as date, postmeta.meta_value as amount
			FROM $wpdb->postmeta AS postmeta
			INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'aps_capture_trans' AND posts.post_status = %s AND post_parent = %d )
			WHERE postmeta.meta_key = 'aps_authorization_captured_amount'
			AND postmeta.post_id = posts.ID ORDER BY posts.ID DESC LIMIT 0, 99
			", strtolower($authorization_command), $order_id), ARRAY_A
        );
        return $history;
    }

    public function render_aps_payment_information() {
        global $post;
        $order_id       = $post->ID;
        $order          = new WC_Order( $order_id );
        $aps_data       = get_post_meta( $order_id, 'aps_payment_response', true );
        $payment_method = $order->get_payment_method();
        if ( ! empty( $aps_data ) ) {
            include 'partials/aps-payment-information.php';
        }
    }
}
