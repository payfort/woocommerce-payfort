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
 * @package    APS
 * @subpackage APS/includes
 */

/**
 * APS Knet gateway class
 *
 * @since      2.2.0
 * @package    APS
 * @subpackage APS/classes
 */
class WC_Gateway_APS_Naps extends WC_Gateway_APS_Super {

	public function __construct() {
		parent::__construct();
		$this->id                   = APS_Constants::APS_PAYMENT_TYPE_NAPS; // payment gateway plugin ID
		$this->icon                 = ''; // URL of the icon that will be displayed on checkout page near your gateway name
		$this->has_fields           = false; // in case you need a custom credit card form
		$this->method_title         = __( 'Amazon Payment Service - NAPS', 'amazon-payment-services' );
		$this->title                = __( 'NAPS', 'amazon-payment-services' );
		$this->description          = __( 'Amazon Payment Service - Naps', 'amazon-payment-services' );
		$this->method_description   = __( 'Accept NAPS', 'amazon-payment-services' ); // will be displayed on the options page
		$this->api_payment_option   = APS_Constants::APS_PAYMENT_METHOD_NAPS;
		$this->supported_currencies = array( 'QAR' );
		$this->enabled              = $this->check_availability();

		// We need custom JavaScript to obtain a token
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

		// You can also register a webhook here
		// add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
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
		$is_enabled = 'yes' === $this->aps_config->get_enable_naps() && in_array( strtoupper( $this->aps_helper->get_fort_currency() ), $this->supported_currencies, true ) ? 'yes' : 'no';
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
		return false;
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
	 * Process Refund
	 *
	 * @return bool
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		if ( empty( $amount ) || intval( $amount ) <= 0 ) {
			$error = new WP_Error();
			$error->add( 'aps_refund_error', __( 'Invalid refund amount.', 'woocommerce' ) );
			return $error;
		}
		$order        = new WC_Order( $order_id );
		$total_amount = $order->get_total();
		if ( $amount < $total_amount ) {
			$error = new WP_Error();
			$error->add( 'aps_refund_error', __( 'Partial refund is not available in this payment method', 'amazon-payment-services' ) );
			return $error;
		} else {
			$refund_status = $this->aps_refund->submit_refund( $order_id, $amount, $reason );
			return $refund_status;
		}
	}
}
