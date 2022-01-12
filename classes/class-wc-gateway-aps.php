<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * APS credit card gateway class
 *
 * @link       https://paymentservices.amazon.com/
 * @since      2.2.0
 *
 * @package    APS
 * @subpackage APS/includes
 */
/**
 * APS credit card gateway class
 *
 * @since      2.2.0
 * @package    APS
 * @subpackage APS/classes
 */
class WC_Gateway_APS extends WC_Gateway_APS_Super {

	public function __construct() {
		parent::__construct();
		$this->id                 = APS_Constants::APS_PAYMENT_TYPE_CC; // payment gateway plugin ID
		$this->mada_title         = __( 'mada debit card / Credit Cards', 'amazon-payment-services' );
		$this->regular_title      = __( 'Credit / Debit card', 'amazon-payment-services' );
		$this->description        = __( 'Accept credit / Debit card payment', 'amazon-payment-services' );
		$this->method_title       = __( 'Amazon Payment Service', 'amazon-payment-services' );
		$this->method_description = __( 'Amazon Payment Service - All payment methods', 'amazon-payment-services' );
		$this->api_payment_option = null;
		if ( is_admin() ) {
			$this->enabled            = $this->aps_config->adminDisplayEnabledPaymentMethod();
		} else {
			$this->enabled            = $this->aps_config->get_enable_credit_card();
		}
		$this->title              = $this->get_checkout_payment_title();
		$this->supports           = array(
			'products',
			'refunds',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'multiple_subscriptions',
			'add_payment_method',
		);
		if ( 'yes' === $this->aps_config->get_enabled_tokenization() ) {
			$this->supports[] = 'tokenization';
		}

		// We need custom JavaScript to obtain a token
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

		// This action hook saves the settings
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_admin_options' ) );

		// You can also register a webhook here

		//Display Error
		add_action( 'woocommerce_before_checkout_form', array( $this, 'show_checkout_payment_errors' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'display_cc_data' ), 10, 1 );
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
		return true;
	}

	/**
	 * Get integration type
	 *
	 * @return string
	 */
	public function get_integration_type() {
		return $this->aps_config->get_credit_card_integration_type();
	}

	/**
	 * Generate the payment fields
	 *
	 * @param none
	 * @return string
	 */
	public function payment_fields() {
		if ( is_checkout() ) {
			$this->redirection_info();
			$integration_type_cls = 'integration_type_' . $this->id;
			echo '<input type="hidden" class="' . wp_kses_data( $integration_type_cls ) . '" value="' . wp_kses_data( $this->get_integration_type() ) . '" />';
			if ( class_exists( 'APS_Public' ) ) {
				APS_Public::load_credit_card_wizard( $this->get_integration_type(), $this->get_icons_array(), $this->aps_config->get_enabled_tokenization(), $this->aps_config->have_subscription(), $this->aps_config->is_authorization(), $this->aps_config->get_enabled_credit_card_installments() );
			}
		} else {
			$integration_type_cls = 'integration_type_' . $this->id;
			echo '<input type="hidden" class="' . wp_kses_data( $integration_type_cls ) . '" value="' . wp_kses_data( $this->get_integration_type() ) . '" />';
			$this->tokenization_form();
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
		$mada_logo       = $image_directory . 'mada-logo.png';
		$visa_logo       = $image_directory . 'visa-logo.png';
		$mastercard_logo = $image_directory . 'mastercard-logo.png';
		$amex_logo       = $image_directory . 'amex-logo.png';
		$meeza_logo      = $image_directory . 'meeza-logo.jpg';
		//Wrap icons
		if ( 'yes' === $this->aps_config->get_show_mada_branding() ) {
			$icon_html .= '<img src="' . $mada_logo . '" alt="mada" class="payment-icons" />';
		}
		$icon_html .= '<img src="' . $visa_logo . '" alt="visa" class="payment-icons" />';
		$icon_html .= '<img src="' . $mastercard_logo . '" alt="mastercard" class="payment-icons"/>';
		$icon_html .= '<img src="' . $amex_logo . '" alt="amex" class="payment-icons"/>';
		if ( 'yes' === $this->aps_config->get_show_meeza_branding() ) {
			$icon_html .= '<img src="' . $meeza_logo . '" alt="meeza" class="payment-icons"/>';
		}
		$icon_html .= '</span>';
		return $icon_html;
	}

	/**
	 * Get Checkout Payment Title
	 */
	public function get_checkout_payment_title() {
		return $this->regular_title;
	}

	/**
	 * Get filter title
	 */
	public function get_filter_title() {
		if ( 'yes' === $this->aps_config->get_show_mada_branding() ) {
			return $this->mada_title;
		}
		return $this->regular_title;
	}

	/**
	 * Get card icons
	 *
	 * @return array
	 */
	public function get_icons_array() {
		$image_directory = plugin_dir_url( dirname( __FILE__ ) ) . 'public/images/';
		$mada_logo       = $image_directory . 'mada-logo.png';
		$visa_logo       = $image_directory . 'visa-logo.png';
		$mastercard_logo = $image_directory . 'mastercard-logo.png';
		$amex_logo       = $image_directory . 'amex-logo.png';
		$meeza_logo      = $image_directory . 'meeza-logo.jpg';
		$card_icons      = array(
			'mada'       => $mada_logo,
			'visa'       => $visa_logo,
			'mastercard' => $mastercard_logo,
			'amex'       => $amex_logo,
			'meeza'      => $meeza_logo,
		);
		return $card_icons;
	}

	/**
	 * Tokenization Form
	 */
	public function tokenization_form() {
		include plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/tokenization-credit-card-form.php';
	}

	/**
	 * Display installment data
	 */
	public function display_cc_data( $order_id ) {
		$aps_response_meta      = get_post_meta( $order_id, 'aps_payment_response', true );
		$aps_installment_amount = get_post_meta( $order_id, 'aps_cc_amount', true );
		$aps_interest_amount    = get_post_meta( $order_id, 'aps_cc_interest', true );
		$number_of_installments = isset($aps_response_meta['number_of_installments'])?$aps_response_meta['number_of_installments'] : '';
		if ( ! empty( $number_of_installments ) ) {
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
}
