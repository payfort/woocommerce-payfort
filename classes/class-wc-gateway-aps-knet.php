<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * APS Knet gateway class
 *
 * @link       https://paymentservices.amazon.com/
 * @since      2.2.0
 *
 * @package    APS/includes
 */

/**
 * APS Knet gateway class
 *
 * @since      2.2.0
 * @package    APS
 * @subpackage APS/classes
 */
class WC_Gateway_APS_Knet extends WC_Gateway_APS_Super {

	public function __construct() {
		parent::__construct();
		$this->id                   = APS_Constants::APS_PAYMENT_TYPE_KNET; // payment gateway plugin ID
		$this->icon                 = ''; // URL of the icon that will be displayed on checkout page near your gateway name
		$this->has_fields           = false; // in case you need a custom credit card form
		$this->method_title         = __( 'Amazon Payment Service - KNET', 'amazon-payment-services' );
		$this->title                = __( 'KNET', 'amazon-payment-services' );
		$this->description          = __( 'Amazon Payment Service - KNET', 'amazon-payment-services' );
		$this->method_description   = __( 'Accept Knet', 'amazon-payment-services' ); // will be displayed on the options page
		$this->api_payment_option   = APS_Constants::APS_PAYMENT_METHOD_KNET;
		$this->supported_currencies = array( 'KWD' );
		$this->enabled              = $this->check_availability();
		$this->supports             = array( 'products' );

		// We need custom JavaScript to obtain a token
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

		// You can also register a webhook here
		// add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'display_knet_data' ), 10, 1 );
	}

	/**
	 * Payment script file to be loaded
	 *
	 * @return void
	 */
	public function payment_scripts() {

	}

	/**
	 * Check if available
	 *
	 * @return string
	 */
	public function check_availability() {
		$is_enabled = 'yes' === $this->aps_config->get_enable_knet() && in_array( strtoupper( $this->aps_helper->get_fort_currency() ), $this->supported_currencies, true ) ? 'yes' : 'no';
		if ( 'yes' === $this->aps_config->have_subscription() ) {
			$is_enabled = 'no';
		}
		return $is_enabled;
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
		return APS_Constants::APS_INTEGRATION_TYPE_REDIRECTION;
	}

	/**
	 * Display KNET data
	 */
	public function display_knet_data( $order_id ) {
		$aps_response_meta = get_post_meta( $order_id, 'aps_payment_response', true );
		echo '<h2> ' . wp_kses_data( __( 'KNET Details', 'amazon-payment-services' ) ) . '</h2>';
		echo '<h4> ' . wp_kses_data( __( 'KNET third party transaction number', 'amazon-payment-services' ) ) . ' : ' . esc_attr($aps_response_meta['third_party_transaction_number']) . '</h4>';
		echo '<h4> ' . wp_kses_data( __( 'KNET ref number', 'amazon-payment-services' ) ) . ' : ' . esc_attr($aps_response_meta['knet_ref_number']) . '</h4>';
	}
}
