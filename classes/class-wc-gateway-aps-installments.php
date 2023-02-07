<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * APS installment gateway class
 *
 * @link       https://paymentservices.amazon.com/
 * @since      2.2.0
 *
 * @package    APS
 * @subpackage APS/includes
 */

/**
 * APS installment gateway class
 *
 * @since      2.2.0
 * @package    APS
 * @subpackage APS/classes
 */
class WC_Gateway_APS_Installments extends WC_Gateway_APS_Super {

	public function __construct() {
		parent::__construct();
		$this->id                     = APS_Constants::APS_PAYMENT_TYPE_INSTALLMENT; // payment gateway plugin ID
		$this->icon                   = ''; // URL of the icon that will be displayed on checkout page near your gateway name
		$this->has_fields             = false; // in case you need a custom credit card form
		$this->method_title           = __( 'Amazon Payment Service - Installments', 'amazon-payment-services' );
		$this->title                  = __( 'Installments', 'amazon-payment-services' );
		$this->description            = __( 'Amazon Payment Service - Installments', 'amazon-payment-services' );
		$this->method_description     = __( 'Accept installments', 'amazon-payment-services' ); // will be displayed on the options page
		$this->price_limit_currencies = array( 'AED', 'SAR', 'EGP' );
		$this->enabled                = $this->check_availability();
		$this->supports               = array( 'products', 'refunds' );
		if ( 'yes' === $this->aps_config->get_enabled_tokenization() ) {
			$this->supports[] = 'tokenization';
		}
		// We need custom JavaScript to obtain a token
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

		// You can also register a webhook here
		// add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
		add_action( 'woocommerce_after_checkout_validaion', array( $this, 'validate_checkout_hander' ), 10, 2 );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'display_installment_data' ), 10, 1 );
	}

	/**
	 * Payment script file to be loaded
	 *
	 * @return void
	 */
	public function payment_scripts() {

	}

	/**
	 * Check if this gateway is enabled and available in the user's currency
	 *
	 * @return bool
	 */
	public function is_valid_for_use() {
		// Skip currency check
		return false;
	}

	/**
	 * Generate the payment fields
	 *
	 * @param none
	 * @return string
	 */
	public function payment_fields() {
		$this->redirection_info();
		$integration_type_cls = 'integration_type_' . $this->id;
		echo '<input type="hidden" class="' . wp_kses_data( $integration_type_cls ) . '" value="' . wp_kses_data( $this->get_integration_type() ) . '" />';
		if ( class_exists( 'APS_Public' ) ) {
			APS_Public::load_installment_wizard( $this->get_integration_type(), $this->aps_config->get_enabled_tokenization(), $this->aps_config->have_subscription(), $this->aps_config->is_authorization() );
		}
	}

	/**
	 * Get integration type
	 *
	 * @return string
	 */
	public function get_integration_type() {
		return $this->aps_config->get_installment_integration_type();
	}

	/**
	 * Validate checkout
	 *
	 * @return void
	 */
	public function validate_checkout_hander( $fields, $errors ) {
		$payment_method = filter_input( INPUT_POST, 'payment_method' );
		if ( APS_Constants::APS_INTEGRATION_TYPE_HOSTED_CHECKOUT === $this->get_integration_type() && $this->id === $payment_method ) {
			$installment_plan_code   = filter_input( INPUT_POST, 'aps_installment_plan_code' );
			$installment_issuer_code = filter_input( INPUT_POST, 'aps_installment_issuer_code' );
			if ( empty( $installment_plan_code ) || empty( $installment_issuer_code ) ) {
				$errors->add( 'aps_installment_plan_unselected', 'Please select installment plan.' );
			}
		}
	}

	/**
	 * Custom Icon
	 *
	 * @return icon_html string
	 */
	public function get_icon() {
		$icon_html       = '<span class="aps-cards-container">';
		$image_directory = plugin_dir_url( dirname( __FILE__ ) ) . 'public/images/';
		$visa_logo       = $image_directory . 'visa-logo.png';
		$mastercard_logo = $image_directory . 'mastercard-logo.png';
		//Wrap icons
		$icon_html .= '<img src="' . $visa_logo . '" alt="visa" class="payment-icons" />';
		$icon_html .= '<img src="' . $mastercard_logo . '" alt="mastercard" class="payment-icons"/>';
		$icon_html .= '</span>';
		return $icon_html;
	}

	/**
	 * Check availability of payment option
	 *
	 * @return yes/no
	 */
	public function check_availability() {
		$is_enabled = $this->aps_config->get_enable_installment();
		if ( $this->aps_config->get_installment_integration_type() == APS_Constants::APS_INTEGRATION_TYPE_EMBEDDED_HOSTED_CHECKOUT ) {
			$is_enabled = 'no';
		}
		/** if ( 'yes' === $is_enabled && in_array( strtoupper( $this->aps_helper->get_front_currency() ), $this->price_limit_currencies, true ) ) {
			$cart_min_limit = 0;
			if ( 'SAR' === $this->aps_helper->get_front_currency() ) {
				$cart_min_limit = $this->aps_config->get_installment_sar_minimum_order_limit();
			} elseif ( 'AED' === $this->aps_helper->get_front_currency() ) {
				$cart_min_limit = $this->aps_config->get_installment_aed_minimum_order_limit();
			} elseif ( 'EGP' === $this->aps_helper->get_front_currency() ) {
				$cart_min_limit = $this->aps_config->get_installment_egp_minimum_order_limit();
			}

			if ( WC()->cart && floatval( WC()->cart->total ) < $cart_min_limit ) {
				$is_enabled = 'no';
			}
		} */
		if ( 'yes' === $this->aps_config->have_subscription() ) {
			$is_enabled = 'no';
		}
		return $is_enabled;
	}

	/**
	 * Display installment data
	 */
	public function display_installment_data( $order_id ) {
		$aps_response_meta      = get_post_meta( $order_id, 'aps_payment_response', true );
		$aps_installment_amount = get_post_meta( $order_id, 'aps_installment_amount', true );
		$aps_interest_amount    = get_post_meta( $order_id, 'aps_installment_interest', true );
		$number_of_installments = $aps_response_meta['number_of_installments'];
		echo '<h2> ' . wp_kses_data( __( 'Installment Details', 'amazon-payment-services' ) ) . '</h2>';
		if ( ! empty( $aps_installment_amount ) ) {
			echo '<h4> ' . wp_kses_data( __( 'EMI', 'amazon-payment-services' ) ) . ' : ' . esc_attr($aps_installment_amount) . ' ' . esc_attr($aps_response_meta['currency']) . '/ ' . esc_html__( 'Month', 'amazon-payment-services' ) . '</h4>';
		}
		if ( ! empty( $aps_interest_amount ) ) {
			echo '<h4> ' . wp_kses_data( __( 'Interest', 'amazon-payment-services' ) ) . ' : ' . esc_attr($aps_interest_amount) . '</h4>';
		}
		if ( ! empty( $number_of_installments ) ) {
			echo '<h4> ' . wp_kses_data( __( 'Installments', 'amazon-payment-services' ) ) . ' : ' . esc_attr($number_of_installments) . '</h4>';
		}
		$confirm_msg = get_post_meta( $order_id, 'aps_installment_confirmation_' . $this->aps_config->get_language(), true );
		if ( ! empty( $confirm_msg ) ) {
			echo wp_kses_data( '<p> ' . __( 'Confirmation', 'amazon-payment-services' ) . ' : <strong> ' . wp_kses_data($confirm_msg) . ' </strong></p>' );
		}
	}
}
