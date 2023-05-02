<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * APS Config
 *
 * @link  https://paymentservices.amazon.com/
 * @since 2.2.0
 *
 * @package    APS
 * @subpackage APS/lib
 */

/**
 * APS Config
 *
 * @since      2.2.0
 * @package    APS
 * @subpackage APS/lib
 */
class APS_Config extends APS_Super {


	/**
	 * Load Properties
	 */
	private static $instance;
	private $merchant_identifier;
	private $access_code;
	private $apple_pay_access_code;
	private $request_sha_phrase;
	private $apple_pay_request_sha_phrase;
	private $response_sha_phrase;
	private $apple_pay_response_sha_phrase;
	private $sandbox_mode;
	private $command;
	private $hash_algorithm;
	private $apple_pay_hash_algorithm;
	private $gateway_currency;
	private $debug_mode;
	private $enable_tokenization;
	private $hide_delete_token_button;
	private $threeds_redirection_method;
	private $enable_credit_card;
	private $credit_card_integration_type;
	private $show_mada_branding;
	private $show_meeza_branding;
	private $enable_apple_pay;
	private $enable_apple_pay_product_page;
	private $enable_apple_pay_cart_page;
	private $enable_knet;
	private $enable_naps;
	private $enable_visa_checkout;
	private $visa_checkout_integration_type;
	private $visa_checkout_api_key;
	private $visa_checkout_profile_id;
	private $visa_checkout_sdk_url;
	private $visa_checkout_button_url;
	private $enable_installment;
	private $installment_integration_type;
	private $installment_sar_minimum_order_limit;
	private $installment_aed_minimum_order_limit;
	private $installment_egp_minimum_order_limit;
	private $show_issuer_name;
	private $show_issuer_logo;
	private $enable_valu;
    private $enable_valu_down_payment;
    private $valu_down_payment_value;
	private $enable_stc_pay;
    private $stc_pay_integration_type;
    private $stc_pay_enabled_tokenization;
	private $valu_minimum_order_limit;
	private $apple_pay_production_key;
	private $apple_pay_domain_name;
	private $apple_pay_display_name;
	private $apple_pay_supported_networks;
	private $apple_pay_button_type;
	private $mada_bins;
	private $meeza_bins;
	private $status_cron_duration;

	/**
	 * Constructor to init
	 */
	public function __construct() {
		parent::__construct();

		$this->merchant_identifier                 = $this->get_aps_config( 'merchant_identifier' );
		$this->access_code                         = $this->get_aps_config( 'access_code' );
		$this->apple_pay_access_code               = $this->get_aps_config( 'apple_pay_access_code' );
		$this->request_sha_phrase                  = $this->get_aps_config( 'request_sha_phrase' );
		$this->apple_pay_request_sha_phrase        = $this->get_aps_config( 'apple_pay_request_sha_phrase' );
		$this->response_sha_phrase                 = $this->get_aps_config( 'response_sha_phrase' );
		$this->apple_pay_response_sha_phrase       = $this->get_aps_config( 'apple_pay_response_sha_phrase' );
		$this->sandbox_mode                        = $this->get_aps_config( 'sandbox_mode' );
		$this->command                             = $this->get_aps_config( 'command' );
		$this->hash_algorithm                      = $this->get_aps_config( 'hash_algorithm' );
		$this->apple_pay_hash_algorithm            = $this->get_aps_config( 'apple_pay_hash_algorithm' );
		$this->gateway_currency                    = $this->get_aps_config( 'gateway_currency' );
		$this->debug_mode                          = $this->get_aps_config( 'debug_mode' );
		$this->enable_tokenization                 = $this->get_aps_config( 'enable_tokenization' );
		$this->hide_delete_token_button            = $this->get_aps_config( 'hide_delete_token_button' );
		$this->threeds_redirection_method          = $this->get_aps_config( 'threeds_redirection_method' );
		$this->enable_credit_card                  = $this->get_aps_config( 'enable_credit_card' );
		$this->credit_card_integration_type        = $this->get_aps_config( 'credit_card_integration_type' );
		$this->show_mada_branding                  = $this->get_aps_config( 'show_mada_branding' );
		$this->show_meeza_branding                 = $this->get_aps_config( 'show_meeza_branding' );
		$this->enable_apple_pay                    = $this->get_aps_config( 'enable_apple_pay' );
		$this->enable_apple_pay_product_page       = $this->get_aps_config( 'enable_apple_pay_product_page' );
		$this->enable_apple_pay_cart_page          = $this->get_aps_config( 'enable_apple_pay_cart_page' );
		$this->enable_knet                         = $this->get_aps_config( 'enable_knet' );
		$this->enable_naps                         = $this->get_aps_config( 'enable_naps' );
		$this->enable_visa_checkout                = $this->get_aps_config( 'enable_visa_checkout' );
		$this->visa_checkout_integration_type      = $this->get_aps_config( 'visa_checkout_integration_type' );
		$this->visa_checkout_api_key               = $this->get_aps_config( 'visa_checkout_api_key' );
		$this->visa_checkout_profile_id            = $this->get_aps_config( 'visa_checkout_profile_id' );
		$this->enable_installment                  = $this->get_aps_config( 'enable_installment' );
		$this->installment_integration_type        = $this->get_aps_config( 'installment_integration_type' );
		$this->installment_sar_minimum_order_limit = $this->get_aps_config( 'installment_sar_minimum_order_limit' );
		$this->installment_aed_minimum_order_limit = $this->get_aps_config( 'installment_aed_minimum_order_limit' );
		$this->installment_egp_minimum_order_limit = $this->get_aps_config( 'installment_egp_minimum_order_limit' );
		$this->show_issuer_name                    = $this->get_aps_config( 'show_issuer_name' );
		$this->show_issuer_logo                    = $this->get_aps_config( 'show_issuer_logo' );
		$this->enable_valu                         = $this->get_aps_config( 'enable_valu' );
		$this->valu_minimum_order_limit            = $this->get_aps_config( 'valu_minimum_order_limit' );
        $this->enable_valu_down_payment            = $this->get_aps_config('enable_valu_down_payment');
        $this->valu_down_payment_value             = $this->get_aps_config('valu_down_payment_value');
		$this->gateway_api_url                     = 'yes' === $this->get_aps_config( 'sandbox_mode' ) ? 'https://sbpaymentservices.payfort.com/FortAPI/paymentApi' : 'https://paymentservices.payfort.com/FortAPI/paymentApi';
		$this->gateway_host_url                    = 'yes' === $this->get_aps_config( 'sandbox_mode' ) ? 'https://sbcheckout.payfort.com/FortAPI/paymentPage' : 'https://checkout.payfort.com/FortAPI/paymentPage';
		$this->visa_checkout_sdk_url               = 'yes' === $this->get_aps_config( 'sandbox_mode' ) ? 'https://sandbox-assets.secure.checkout.visa.com/checkout-widget/resources/js/integration/v1/sdk.js' : 'https://assets.secure.checkout.visa.com/checkout-widget/resources/js/integration/v1/sdk.js';
		$this->visa_checkout_button_url            = 'yes' === $this->get_aps_config( 'sandbox_mode' ) ? 'https://sandbox.secure.checkout.visa.com/wallet-services-web/xo/button.png' : 'https://assets.secure.checkout.visa.com/wallet-services-web/xo/button.png';
		$this->apple_pay_production_key            = $this->get_aps_config( 'apple_pay_production_key' );
		$this->apple_pay_domain_name               = $this->get_aps_config( 'apple_pay_domain_name' );
		$this->apple_pay_display_name              = $this->get_aps_config( 'apple_pay_display_name' );
		$this->apple_pay_button_type               = $this->get_aps_config( 'apple_pay_button_type' );
		$this->apple_pay_supported_networks        = $this->get_aps_config( 'apple_pay_supported_networks' );
		$this->mada_bins                           = $this->get_aps_config( 'mada_bins' );
		$this->meeza_bins                          = $this->get_aps_config( 'meeza_bins' );
		$this->status_cron_duration                = $this->get_aps_config( 'status_cron_duration' );
        $this->enable_stc_pay                      = $this->get_aps_config( 'enable_stc_pay' );
        $this->stc_pay_integration_type            = $this->get_aps_config('stc_pay_integration_type');
        $this->stc_pay_enabled_tokenization        = $this->get_aps_config('stc_pay_enabled_tokenization');
	}

	/**
	 * It will return instance of class
	 *
	 * @return APS_Config
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new APS_Config();
		}
		return self::$instance;
	}

	/**
	 * It will return APS configuration
	 *
	 * @return string
	 */
	public function get_aps_config( $key ) {
		return $this->get_option( $key );
	}

	/**
	 * Return Merchant Identifier
	 *
	 * @return string
	 */
	public function get_merchant_identifier() {
		return $this->merchant_identifier;
	}

	/**
	 * Return Access code
	 *
	 * @return string
	 */
	public function get_access_code() {
		return $this->decodeValue($this->access_code);
	}

	/**
	 * Return Apple pay Access code
	 *
	 * @return string
	 */
	public function get_apple_pay_access_code() {
		return $this->decodeValue($this->apple_pay_access_code);
	}

	/**
	 * Return Request SHA
	 *
	 * @return string
	 */
	public function get_request_sha_phrase() {
		return $this->decodeValue($this->request_sha_phrase);
	}

	/**
	 * Return Apple pay Request SHA
	 *
	 * @return string
	 */
	public function get_apple_pay_request_sha_phrase() {
		return $this->decodeValue($this->apple_pay_request_sha_phrase);
	}

	/**
	 * Return Response SHA
	 *
	 * @return string
	 */
	public function get_response_sha_phrase() {
		return $this->decodeValue($this->response_sha_phrase);
	}

	/**
	 * Return Apple pay Reposne SHA
	 *
	 * @return string
	 */
	public function get_apple_pay_response_sha_phrase() {
		return $this->decodeValue($this->apple_pay_response_sha_phrase);
	}

	/**
	 * Return Sandbox Mode
	 *
	 * @return string
	 */
	public function get_sandbox_mode() {
		return $this->sandbox_mode;
	}

	/**
	 * Return Command
	 *
	 * @return string
	 */
	public function get_command( $payment_method, $card_number = null, $card_type = null ) {
		$mada_regex  = '/^' . $this->get_mada_bins() . '/';
		$meeza_regex = '/^' . $this->get_meeza_bins() . '/';

		$command            = $this->command;
		$authorized_methods = array(
			APS_Constants::APS_PAYMENT_TYPE_CC,
			APS_Constants::APS_PAYMENT_TYPE_VISA_CHECKOUT,
			APS_Constants::APS_PAYMENT_TYPE_APPLE_PAY,
        );
		if ( 'AUTHORIZATION' === $command && ! in_array( $payment_method, $authorized_methods, true ) ) {
			$command = 'PURCHASE';
		}
		if ( 'AUTHORIZATION' === $command && APS_Constants::APS_PAYMENT_TYPE_CC === $payment_method ) {
			if ( ! empty( $card_number ) ) {
				if ( preg_match( $mada_regex, $card_number ) || preg_match( $meeza_regex, $card_number ) ) {
					$command = 'PURCHASE';
				}
			} elseif ( ! empty( $card_type ) ) {
				if ( 'MADA' === $card_type || 'MEEZA' === $card_type ) {
					$command = 'PURCHASE';
				}
			}
		}
		return $command;
	}

	/**
	 * Is authorization
	 */
	public function is_authorization() {
		if ( 'AUTHORIZATION' === $this->command ) {
			return 'yes';
		}
		return 'no';
	}

	/**
	 * Return Hash Algorithm
	 *
	 * @return string
	 */
	public function get_hash_algorithm() {
		return $this->hash_algorithm;
	}

	/**
	 * Return Apple pay Hash Algorithm
	 *
	 * @return string
	 */
	public function get_apple_pay_hash_algorithm() {
		return $this->apple_pay_hash_algorithm;
	}

	/**
	 * Return Gateway Currency
	 *
	 * @return string
	 */
	public function get_gateway_currency() {
		return $this->gateway_currency;
	}

	/**
	 * Return Debug mode
	 *
	 * @return string
	 */
	public function get_debug_mode() {
		return $this->debug_mode;
	}

	/**
	 * Return enable tokenization
	 *
	 * @return string
	 */
	public function get_enabled_tokenization() {
		return ! empty( $this->enable_tokenization ) ? $this->enable_tokenization : 'yes';
	}

	/**
	 * Have subscription?
	 */
	public function have_subscription() {
		if ( class_exists( 'WC_Subscriptions_Cart' ) && ( WC_Subscriptions_Cart::cart_contains_subscription() || ( function_exists( 'wcs_cart_contains_renewal' ) && wcs_cart_contains_renewal() ) ) ) {
			return 'yes';
		} elseif ( is_product() ) {
			$product_id =  get_the_ID();
			$product = wc_get_product($product_id);
			if ( isset($product) && false != $product && 'subscription' === $product->get_type() ) {
				return 'yes';
			}
		}
		return 'no';
	}

	/**
	 * Return enable tokenization
	 *
	 * @return string
	 */
	public function get_hide_delete_token_button() {
		return ! empty( $this->hide_delete_token_button ) ? $this->hide_delete_token_button : 'no';
	}

	/**
	 * Return 3ds Redirection Method
	 *
	 * @return string
	 */
	public function get_threeds_redirection_method() {
		return ! empty( $this->threeds_redirection_method ) ? $this->threeds_redirection_method : 'server_side';
	}

	/**
	 * Return Enable credit card
	 *
	 * @return string
	 */
	public function get_enable_credit_card() {
		return $this->enable_credit_card;
	}

	/**
	 * Return Credit card integration type
	 *
	 * @return string
	 */
	public function get_credit_card_integration_type() {
		return $this->credit_card_integration_type;
	}

	/**
	 * Return enabled credit card installments
	 *
	 * @return string
	 */
	public function get_enabled_credit_card_installments() {
		$enabled_cc_with_installment = 'no';
		if ( APS_Constants::APS_INTEGRATION_TYPE_EMBEDDED_HOSTED_CHECKOUT == $this->installment_integration_type ) {
			$enabled_cc_with_installment = 'yes';
			if ( 'yes' === $this->have_subscription() ) {
				$enabled_cc_with_installment = 'no';
			}
		}
		return $enabled_cc_with_installment;
	}

	/**
	 * Return Show mada branding
	 *
	 * @return string
	 */
	public function get_show_mada_branding() {
		return $this->show_mada_branding;
	}

	/**
	 * Return Show meeza branding
	 *
	 * @return string
	 */
	public function get_show_meeza_branding() {
		return $this->show_meeza_branding;
	}

	/**
	 * Return enable apple pay
	 *
	 * @return string
	 */
	public function get_enable_apple_pay() {
		return $this->enable_apple_pay;
	}

	/**
	 * Return enable apple pay in product page
	 *
	 * @return string
	 */
	public function get_enable_apple_pay_product_page() {
		return ! empty( $this->enable_apple_pay_product_page ) ? $this->enable_apple_pay_product_page : 'no';
	}

	/**
	 * Check apple pay availability in product page
	 *
	 * @return yes/no
	 */
	public function can_show_apple_pay_product_page() {
		$apple_certificates   = get_option( 'aps_apple_pay_certificates' );
		$is_enabled           = $this->get_enable_apple_pay();
		$product_page_enabled = $this->get_enable_apple_pay_product_page();
		if ( empty( $apple_certificates ) || ! isset( $apple_certificates['apple_certificate_path_file'] ) || ! isset( $apple_certificates['apple_certificate_key_file'] ) ) {
			$is_enabled = 'no';
		}
		if ( 'no' === $product_page_enabled ) {
			$is_enabled = 'no';
		}
		if ( 'yes' === $this->have_subscription() ) {
			$is_enabled = 'no';
		}
		return $is_enabled;
	}

	/**
	 * Return enable apple pay in cart page
	 *
	 * @return string
	 */
	public function get_enable_apple_pay_cart_page() {
		return ! empty( $this->enable_apple_pay_cart_page ) ? $this->enable_apple_pay_cart_page : 'no';
	}

	/**
	 * Check apple pay availability in product page
	 *
	 * @return yes/no
	 */
	public function can_show_apple_pay_cart_page() {
		$apple_certificates = get_option( 'aps_apple_pay_certificates' );
		$is_enabled         = $this->get_enable_apple_pay();
		$cart_page_enabled  = $this->get_enable_apple_pay_cart_page();
		if ( empty( $apple_certificates ) || ! isset( $apple_certificates['apple_certificate_path_file'] ) || ! isset( $apple_certificates['apple_certificate_key_file'] ) ) {
			$is_enabled = 'no';
		}
		if ( 'no' === $cart_page_enabled ) {
			$is_enabled = 'no';
		}
		if ( 'yes' === $this->have_subscription() ) {
			$is_enabled = 'no';
		}
		return $is_enabled;
	}

	/**
	 * Return enable knet
	 *
	 * @return string
	 */
	public function get_enable_knet() {
		return $this->enable_knet;
	}

	/**
	 * Return enable naps
	 *
	 * @return string
	 */
	public function get_enable_naps() {
		return $this->enable_naps;
	}

	/**
	 * Return enable visa checkout
	 *
	 * @return string
	 */
	public function get_enable_visa_checkout() {
		return $this->enable_visa_checkout;
	}

	/**
	 * Return visa checkout integration type
	 *
	 * @return string
	 */
	public function get_visa_checkout_integration_type() {
		return $this->visa_checkout_integration_type;
	}

	/**
	 * Return visa checkout api key
	 *
	 * @return string
	 */
	public function get_visa_checkout_api_key() {
		return $this->decodeValue($this->visa_checkout_api_key);
	}

	/**
	 * Return visa checkout profile name
	 *
	 * @return string
	 */
	public function get_visa_checkout_profile_id() {
		return $this->visa_checkout_profile_id;
	}

	/**
	 * Return enable installment
	 *
	 * @return string
	 */
	public function get_enable_installment() {
		return $this->enable_installment;
	}

	/**
	 * Return installment integration type
	 *
	 * @return string
	 */
	public function get_installment_integration_type() {
		return $this->installment_integration_type;
	}

	/**
	 * Return installment SAR minimum order limit
	 *
	 * @return string
	 */
	public function get_installment_sar_minimum_order_limit() {
		return ! empty( $this->installment_sar_minimum_order_limit ) ? $this->installment_sar_minimum_order_limit : 1000;
	}

	/**
	 * Return installment EGP minimum order limit
	 *
	 * @return string
	 */
	public function get_installment_egp_minimum_order_limit() {
		return ! empty( $this->installment_egp_minimum_order_limit ) ? $this->installment_egp_minimum_order_limit : 1000;
	}

	/**
	 * Return installment AED minimum order limit
	 *
	 * @return string
	 */
	public function get_installment_aed_minimum_order_limit() {
		return ! empty( $this->installment_aed_minimum_order_limit ) ? $this->installment_aed_minimum_order_limit : 1000;
	}

	/**
	 * Return get issuer name
	 *
	 * @return string
	 */
	public function show_issuer_name() {
		return ! empty( $this->show_issuer_name ) ? $this->show_issuer_name : 'no';
	}

	/**
	 * Return get issuer logo
	 *
	 * @return string
	 */
	public function show_issuer_logo() {
		return ! empty( $this->show_issuer_logo ) ? $this->show_issuer_logo : 'no';
	}

	/**
	 * Return enable valu
	 *
	 * @return string
	 */
	public function get_enable_valu() {
		return $this->enable_valu;
	}

	/**
	 * Return valu minimum order limit
	 *
	 * @return string
	 */
	public function get_valu_minimum_order_limit() {
		return $this->valu_minimum_order_limit;
	}

    public function get_enable_valu_down_payment() {
		return $this->enable_valu_down_payment;
	}

    public function get_valu_down_payment_value() {
		return $this->valu_down_payment_value;
	}

    /**
     * Return enable stc pay
     *
     * @return string
     */
    public function get_enable_stc_pay() {
        return $this->enable_stc_pay;
    }

    /**
     * Return Stc Pay integration type
     *
     * @return string
     */
    public function get_stc_pay_integration_type() {
        return $this->stc_pay_integration_type;
    }

    /**
     * Return STC-PAY enable tokenization
     *
     * @return string
     */
    public function get_stc_pay_enabled_tokenization()
    {
        return $this->stc_pay_enabled_tokenization;
    }

	/**
	 * Return language
	 *
	 * @return string
	 */
	public function get_language() {
		$language = '';
		$language = apply_filters( 'wpml_current_language', null );
		if ( empty($language) ) {
			$language = get_locale();
		}
		return 'ar' === $language ? 'ar' : 'en';
	}

	/**
	 * Return gateway url
	 *
	 * @return string
	 */
	public function get_gateway_url( $type = 'host' ) {
		if ( 'host' === $type ) {
			return $this->gateway_host_url;
		} elseif ( 'api' === $type ) {
			return $this->gateway_api_url;
		}
	}

	/**
	 * Return visa checkout sdk url
	 *
	 * @return string
	 */
	public function get_visa_checkout_sdk() {
		return $this->visa_checkout_sdk_url;
	}

	/**
	 * Return visa checkout button url
	 *
	 * @return string
	 */
	public function get_visa_checkout_button_url() {
		return $this->visa_checkout_button_url;
	}

	/**
	 * Return apple pay production key
	 *
	 * @return string
	 */
	public function get_apple_pay_production_key() {
		return $this->apple_pay_production_key;
	}

	/**
	 * Return apple pay domain name
	 *
	 * @return string
	 */
	public function get_apple_pay_domain_name() {
		return $this->apple_pay_domain_name;
	}

	/**
	 * Return apple pay display name
	 *
	 * @return string
	 */
	public function get_apple_pay_display_name() {
		return $this->apple_pay_display_name;
	}

	/**
	 * Return apple pay supported networks
	 *
	 * @return string
	 */
	public function get_apple_pay_supported_networks() {
		return $this->apple_pay_supported_networks;
	}

	/** 
	 *Return Apple pay Button Types
	 *
	 * @return string
	 */
	public function get_apple_pay_button_type() {
		if ( ! empty( $this->apple_pay_button_type ) ) {
			return $this->apple_pay_button_type;
		} else {
			return 'apple-pay-buy';
		}
	}

	/**
	 * Return mada bins
	 *
	 * @return mada_bins
	 */
	public function get_mada_bins() {
		if ( ! empty( $this->mada_bins ) ) {
			return $this->mada_bins;
		} else {
			return APS_Constants::MADA_BINS;
		}
	}

	/**
	 * Return meeza bins
	 *
	 * @return meeza_bins
	 */
	public function get_meeza_bins() {
		if ( ! empty( $this->meeza_bins ) ) {
			return $this->meeza_bins;
		} else {
			return APS_Constants::MEEZA_BINS;
		}
	}

	/**
	 * Get Plugin params
	 *
	 * @return plugin_params array
	 */
	public function plugin_params() {
		return array(
			'app_programming'    => 'PHP',
			'app_framework'      => 'Wordpress',
			'app_ver'            => 'v' . get_option( 'woocommerce_version' ),
			'app_plugin'         => 'Woocommerce',
			'app_plugin_version' => 'v' . APS_VERSION,
		);
	}

	/**
	 * Get cron duration
	 */
	public function get_status_cron_duration() {
		if ( ! empty( $this->status_cron_duration ) ) {
			return $this->status_cron_duration;
		} else {
			return APS_Constants::APS_STATUS_CRON_DEFAULT_DURATION;
		}
	}

	public function decodeValue( $value ) {
		return html_entity_decode( $value, ENT_QUOTES, get_bloginfo( 'charset' ) );
	}

	/**
	 * Display payment method enabled in admin listing if atleast one enabled
	*/
	public function adminDisplayEnabledPaymentMethod() {
		$payment_method_status = array(
			$this->enable_credit_card,
			$this->enable_installment,
			$this->enable_visa_checkout,
			$this->enable_valu,
			$this->enable_knet,
			$this->enable_naps,
			$this->enable_apple_pay,
            $this->enable_stc_pay
		);
		if ( in_array('yes', $payment_method_status) ) {
			return 'yes';
		}
		return 'no';
	}
}
