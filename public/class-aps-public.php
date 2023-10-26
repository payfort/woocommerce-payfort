<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://paymentservices.amazon.com/
 * @since      2.2.0
 *
 * @package    APS
 * @subpackage APS/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    APS
 * @subpackage APS/public
 */
class APS_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Load Helpers
	 */
	public function load_helpers() {
		$this->aps_helper  = APS_Helper::get_instance();
		$this->aps_config  = APS_Config::get_instance();
		$this->aps_payment = APS_Payment::get_instance();
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		if ( is_rtl() ) {
			wp_enqueue_style( $this->plugin_name . '-responsive-rtl', plugin_dir_url( __FILE__ ) . 'css/aps-responsive-rtl.css', array(), $this->version, 'all' );
		} else {
			wp_enqueue_style( $this->plugin_name . '-responsive', plugin_dir_url( __FILE__ ) . 'css/aps-responsive.css', array(), $this->version, 'all' );
		}

		wp_enqueue_style( $this->plugin_name . '-slickcss', plugin_dir_url( __FILE__ ) . 'css/slick.css', array(), $this->version, 'all' );

		if ( is_rtl() ) {
			wp_enqueue_style( $this->plugin_name . '-main', plugin_dir_url( __FILE__ ) . 'css/aps-public-rtl.css', array(), $this->version, 'all' );
		} else {
			wp_enqueue_style( $this->plugin_name . '-main', plugin_dir_url( __FILE__ ) . 'css/aps-public.css', array(), $this->version, 'all' );
		}

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		//Enqueue slick
		wp_enqueue_script( $this->plugin_name . '-slickjs', plugin_dir_url( __FILE__ ) . 'js/slick.js', array( 'jquery' ), $this->version, true );

		// Enqueue main script
		wp_enqueue_script( $this->plugin_name . '-main', plugin_dir_url( __FILE__ ) . 'js/aps-main.js', array( 'jquery' ), $this->version, true );

		// Register checkout script
		wp_register_script( $this->plugin_name . '-checkout', plugin_dir_url( __FILE__ ) . 'js/aps-checkout.js', array( 'jquery' ), null, true );
		// Localize the script with new data
		$aps_info = array(
			'redirection_type'             => APS_Constants::APS_INTEGRATION_TYPE_REDIRECTION,
			'standard_type'                => APS_Constants::APS_INTEGRATION_TYPE_STANDARD_CHECKOUT,
			'hosted_type'                  => APS_Constants::APS_INTEGRATION_TYPE_HOSTED_CHECKOUT,
			'form_id'                      => APS_Constants::APS_SELECTOR_PAYMENT_REQFORM_ID,
			'payment_method_cc'            => APS_Constants::APS_PAYMENT_TYPE_CC,
			'payment_method_installment'   => APS_Constants::APS_PAYMENT_TYPE_INSTALLMENT,
			'payment_method_apple_pay'     => APS_Constants::APS_PAYMENT_TYPE_APPLE_PAY,
			'payment_method_valu'          => APS_Constants::APS_PAYMENT_TYPE_VALU,
			'payment_method_stc_pay'       => APS_Constants::APS_PAYMENT_TYPE_STC_PAY,
            'payment_method_tabby'         => APS_Constants::APS_PAYMENT_TYPE_TABBY,
			'payment_method_visa_checkout' => APS_Constants::APS_PAYMENT_TYPE_VISA_CHECKOUT,
			'error_msg'                    => array(
				'invalid_mobile_number'    => __( 'Mobile number is invalid', 'amazon-payment-services' ) . '.',
				'invalid_card_length'      => __( 'Invalid card length', 'amazon-payment-services' ) . '.',
				'card_empty'               => __( 'Card number cannot be empty', 'amazon-payment-services' ) . '.',
				'invalid_card'             => __( 'Card number is invalid', 'amazon-payment-services' ) . '.',
				'invalid_card_holder_name' => __( 'Card holder name is invalid', 'amazon-payment-services' ) . '.',
				'invalid_card_cvv'         => __( 'Card CVV is invalid', 'amazon-payment-services' ) . '.',
				'invalid_expiry_month'     => __( 'Expiry month is invalid', 'amazon-payment-services' ) . '.',
				'invalid_expiry_year'      => __( 'Expiry year is invalid', 'amazon-payment-services' ) . '.',
				'invalid_expiry_date'      => __( 'Expiry date is invalid', 'amazon-payment-services' ) . '.',
				'valu_pending_msg'         => __( 'Please complete the evaluation process', 'amazon-payment-services' ) . '.',
                'valu_terms_msg'           => __( 'Please accept the terms and conditions', 'amazon-payment-services' ) . '.',
                'stc_pay_pending_msg'         => __( 'Please complete the evaluation process', 'amazon-payment-services' ) . '.',
                'stc_pay_otp_empty_msg'         => __( 'Please enter a valid otp number', 'amazon-payment-services' ) . '.',
                'tabby_pending_msg'        => __( 'Please complete the evaluation process', 'amazon-payment-services' ) . '.',
				'required_field'           => __( 'This is a required field', 'amazon-payment-services' ) . '.',
			),
			'success_msg'                  => array(
				'otp_generated_message' => __( 'OTP has been sent to you on your mobile number : {mobile_number}', 'amazon-payment-services' ),
			),
			'general_text'                 => array(
				'months_txt'   => __( 'Months', 'amazon-payment-services' ),
				'month_txt'    => __( 'month', 'amazon-payment-services' ),
				'interest_txt' => __( 'Admin Fee:', 'amazon-payment-services' ),
			),
			'ajax_url'                     => admin_url( 'admin-ajax.php' ),
			'checkout_url'                 => site_url( '?wc-ajax=checkout' ),
			'lang'                         => $this->aps_config->get_language(),
			'mada_bins'                    => $this->aps_config->get_mada_bins(),
			'meeza_bins'                   => $this->aps_config->get_meeza_bins(),
			'installment_with_cc'          => $this->aps_config->get_enabled_credit_card_installments(),
			'review_order_checkout_url'    => esc_attr( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ,
		);
		if ( class_exists( 'WC_Subscriptions_Cart' ) && ( WC_Subscriptions_Cart::cart_contains_subscription() || ( function_exists( 'wcs_cart_contains_renewal' ) && wcs_cart_contains_renewal() ) ) ) {
			$aps_info['have_recurring_items'] = true;
		}
		wp_localize_script( $this->plugin_name . '-checkout', 'aps_info', $aps_info );
		// Enqueued script with localized data.
		wp_enqueue_script( $this->plugin_name . '-checkout' );

		// Localize the script with new data
		$currency                      = $this->aps_helper->get_fort_currency();
		$apple_certificates            = get_option( 'aps_apple_pay_certificates' );
		$apple_pay_merchant_identifier = '';
		if (!empty($apple_certificates)) {
			$upload_dir         = wp_upload_dir();
			$certificate_path              = $upload_dir['basedir'] . '/aps-certificates/' . $apple_certificates['apple_certificate_path_file'];
			$apple_pay_merchant_identifier = openssl_x509_parse( file_get_contents( $certificate_path ) )['subject']['UID'];
		}
		$supported_networks            = $this->aps_config->get_apple_pay_supported_networks();
		$apple_vars                    = array(
			'response_url'               => create_wc_api_url( 'aps_applepay_response' ),
			'cancel_url'                 => create_wc_api_url( 'aps_merchant_cancel_apple_pay' ),
			'merchant_identifier'        => $apple_pay_merchant_identifier,
			'country_code'               => WC()->countries->get_base_country(),
			'currency_code'              => strtoupper( $currency ),
			'display_name'               =>  $this->aps_config->get_apple_pay_display_name(),
			'payment_method_installment' => APS_Constants::APS_PAYMENT_TYPE_INSTALLMENT,
			'ajax_url'                   => admin_url( 'admin-ajax.php' ),
			'supported_networks'         => $supported_networks,
		);
		if ( is_checkout() ) {
			// Register apple pay script
			wp_register_script( $this->plugin_name . '-apple-pay-checkout', plugin_dir_url( __FILE__ ) . 'js/aps-apple-pay.js', array( 'jquery' ), $this->version . '-' . hexdec(bin2hex(openssl_random_pseudo_bytes(2))), true );
			wp_localize_script( $this->plugin_name . '-apple-pay-checkout', 'apple_vars', $apple_vars );
			// Enqueued script with localized data.
			wp_enqueue_script( $this->plugin_name . '-apple-pay-checkout' );
		} elseif ( is_cart() ) {
			// Register apple pay script
			wp_register_script( $this->plugin_name . '-apple-pay-cart', plugin_dir_url( __FILE__ ) . 'js/aps-apple-pay-cart.js', array( 'jquery' ), $this->version . '-' . hexdec(bin2hex(openssl_random_pseudo_bytes(2))), true );
			wp_localize_script( $this->plugin_name . '-apple-pay-cart', 'apple_vars', $apple_vars );
			// Enqueued script with localized data.
			wp_enqueue_script( $this->plugin_name . '-apple-pay-cart' );
		} elseif ( is_product() ) {
			// Register apple pay script
			wp_register_script( $this->plugin_name . '-apple-pay-product', plugin_dir_url( __FILE__ ) . 'js/aps-apple-pay-product.js', array( 'jquery' ), $this->version . '-' . hexdec(bin2hex(openssl_random_pseudo_bytes(2))), true );
			wp_localize_script( $this->plugin_name . '-apple-pay-product', 'apple_vars', $apple_vars );
			// Enqueued script with localized data.
			wp_enqueue_script( $this->plugin_name . '-apple-pay-product' );
		}

		if ( ! is_user_logged_in() ) {
			$apple_vars['is_guest_user'] = true;
		} else {
			$apple_vars['is_guest_user'] = false;
		}
	}

	/**
	 * Woocommerce override template
	 *
	 * @return template
	 */
	public function woocommerce_override_template( $template, $template_name, $template_path ) {
		global $woocommerce;
		$_template = $template;
		if ( ! $template_path ) {
			$template_path = $woocommerce->template_url;
		}

		$plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/woo_templates/';

		// Look within passed path within the theme - this is priority
		$template = locate_template(
			array(
				$template_path . $template_name,
				$template_name,
			)
		);

		if ( ! $template && file_exists( $plugin_path . $template_name ) ) {
			$template = $plugin_path . $template_name;
		}

		if ( ! $template ) {
			$template = $_template;
		}

		return $template;
	}

	/**
	 * Load Credit card payment wizard
	 *
	 * @return void
	 */
	public static function load_credit_card_wizard( $integration_type, $card_icons, $is_enabled_tokenization, $have_subscription, $is_authorization, $cc_with_installments ) {
		if ( APS_Constants::APS_INTEGRATION_TYPE_REDIRECTION === $integration_type ) {
			include 'partials/redirection-credit-card-wizard.php';
		} elseif ( APS_Constants::APS_INTEGRATION_TYPE_STANDARD_CHECKOUT === $integration_type ) {
			include 'partials/standard-credit-card-wizard.php';
		} elseif ( APS_Constants::APS_INTEGRATION_TYPE_HOSTED_CHECKOUT === $integration_type ) {
			include 'partials/hosted-credit-card-wizard.php';
		}
	}

	/**
	 * Load installment payment wizard
	 *
	 * @return void
	 */
	public static function load_installment_wizard( $integration_type, $is_enabled_tokenization, $have_subscription, $is_authorization ) {
		if ( APS_Constants::APS_INTEGRATION_TYPE_STANDARD_CHECKOUT === $integration_type ) {
			include 'partials/standard-installment-wizard.php';
		} elseif ( APS_Constants::APS_INTEGRATION_TYPE_HOSTED_CHECKOUT === $integration_type ) {
			include 'partials/hosted-installment-wizard.php';
		}
	}

	/**
	 * Load visa checkout wizard
	 *
	 *  @return void
	 */
	public static function load_visa_checkout_wizard( $visa_checkout_sdk, $visa_checkout_button_url ) {
		include 'partials/hosted-visa-checkout-wizard.php';
	}

	/**
	 * Load valu checkout wizard
	 *
	 *  @return void
	 */
	public static function load_valu_wizard( $language, $is_valu_down_payment_enabled, $valu_down_payment_value ) {
		ob_start();
		include 'terms/terms-' . $language . '.html';
		$terms_modal_text = ob_get_clean();
		include 'partials/hosted-valu-wizard.php';
	}

	/**
	 * Load valu checkout wizard
	 *
	 *  @return void
	 */
	public static function load_stc_pay_wizard( $language , $integration_type , $is_enabled_tokenization , $have_subscription ) {
        ob_start();
        include 'terms/terms-' . $language . '.html';
        $terms_modal_text = ob_get_clean();
        if ( APS_Constants::APS_INTEGRATION_TYPE_REDIRECTION === $integration_type && ('yes' === $is_enabled_tokenization || 'yes' === $have_subscription)  ) {
            include 'partials/redirection-stc-pay-wizard.php';
        } elseif ( APS_Constants::APS_INTEGRATION_TYPE_HOSTED_CHECKOUT === $integration_type ) {
            include 'partials/hosted-stc-pay-wizard.php';
        }
	}
    public static function load_tabby_wizard( $language , $integration_type , $is_enabled_tokenization , $have_subscription ) {
        ob_start();
        include 'terms/terms-' . $language . '.html';
        $terms_modal_text = ob_get_clean();
        include 'partials/redirection-tabby-wizard.php';

    }

	/**
	 * Load apple pay checkout wizard
	 *
	 *  @return void
	 */
	public static function load_apple_pay_wizard( $apple_pay_class ) {
		include 'partials/hosted-apple-pay-wizard.php';
	}
}
