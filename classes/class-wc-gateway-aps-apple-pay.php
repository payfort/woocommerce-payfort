<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * APS apple pay gateway class
 *
 * @link       https://paymentservices.amazon.com/
 * @since      2.2.0
 *
 * @package    APS
 * @subpackage APS/includes
 */

/**
 * APS apple pay gateway class
 *
 * @since      2.2.0
 * @package    APS
 * @subpackage APS/classes
 */
class WC_Gateway_APS_Apple_Pay extends WC_Gateway_APS_Super {

	public function __construct() {
		parent::__construct();
		$this->id                 = APS_Constants::APS_PAYMENT_TYPE_APPLE_PAY; // payment gateway plugin ID
		$this->icon               = ''; // URL of the icon that will be displayed on checkout page near your gateway name
		$this->has_fields         = false; // in case you need a custom credit card form
		$this->method_title       = __( 'Amazon Payment Service - Apple Pay', 'amazon-payment-services' );
		$this->title              = __( 'Apple Pay', 'amazon-payment-services' );
		$this->description        = __( 'Amazon Payment Service - Apple Pay', 'amazon-payment-services' );
		$this->method_description = __( 'Accept Apple Pay payment', 'amazon-payment-services' ); // will be displayed on the options page
		$this->enabled            = $this->check_availability();

		// We need custom JavaScript to obtain a token
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

		// You can also register a webhook here
		add_action( 'woocommerce_api_aps_applepay_response', array( $this, 'aps_applepay_response' ) );

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
	 * Process the payment and return the result
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$status      = 'success';
		$apple_order = array(
			'sub_total'      => 0.00,
			'tax_total'      => 0.00,
			'shipping_total' => 0.00,
			'discount_total' => 0.00,
			'grand_total'    => 0.00,
			'order_items'    => array(),
		);
		try {
			$order = new WC_Order( $order_id );
			update_post_meta( $order_id, 'payment_gateway', APS_Constants::APS_GATEWAY_ID );
			if ( 'failed' === $order->get_status() ) {
				$order->update_status( 'payment-pending', '' );
			}
			$apple_order['sub_total']      = $order->get_subtotal();
			$apple_order['tax_total']      = $order->get_total_tax();
			$apple_order['shipping_total'] = $order->get_shipping_total();
			$apple_order['discount_total'] = $order->get_discount_total();
			$apple_order['grand_total']    = $order->get_total();
			foreach ( $order->get_items() as $item_id => $item ) {
				$apple_order['order_items'][] = array(
					'product_name'     => $item->get_name(),
					'product_subtotal' => $item->get_subtotal(),
				);
			}
		} catch ( \Exception $e ) {
			$status = 'failure';
		}
		$result = array(
			'result'      => $status,
			'reload'      => false,
			'apple_order' => $apple_order,
		);
		wp_send_json( $result );
		wp_die();
	}

	/**
	 * Generate the valu payment form
	 *
	 * @param none
	 * @return string
	 */
	public function payment_fields() {
		$integration_type_cls = 'integration_type_' . $this->id;
		echo '<input type="hidden" class="' . wp_kses_data( $integration_type_cls ) . '" value="' . wp_kses_data( $this->get_integration_type() ) . '" />';
		if ( class_exists( 'APS_Public' ) ) {
			APS_Public::load_apple_pay_wizard( $this->aps_config->get_apple_pay_button_type() );
		}
	}

	/**
	 * Get integration type
	 *
	 * @return string
	 */
	public function get_integration_type() {
		return APS_Constants::APS_INTEGRATION_TYPE_HOSTED_CHECKOUT;
	}

	/**
	 * APS applepay response
	 *
	 * @return void
	 */
	public function aps_applepay_response() {
		$redirect_url   = '';
		$apple_pay_data = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
		session_start();
		if ( isset( $apple_pay_data['data'] ) && ! empty( $apple_pay_data['data'] ) ) {
			$params          = html_entity_decode( $apple_pay_data['data'] );
			$response_params = json_decode(filter_var($params, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
			$this->aps_helper->log( 'apple params: ' . wp_json_encode( $response_params, true ) );

			$order_id = WC()->session->get('order_awaiting_payment');
			update_post_meta( $order_id, 'aps_redirected', 1 );
			$apple_payment   = $this->aps_payment->init_apple_pay_payment( $response_params );
			if ( 'success' === $apple_payment['status'] ) {
				$order = new WC_Order( $apple_payment['order_id'] );
				WC()->session->set( 'refresh_totals', true );
				$redirect_url = $this->get_return_url( $order );
			} else {
				$redirect_url = wc_get_checkout_url();
			}
			// redirect to success page
			$order = new WC_Order( $apple_payment['order_id'] );
			if ( in_array($order->get_status(), ['processing', 'completed']) ) {
				$redirect_url = $this->get_return_url( $order );
				unset( $_SESSION['aps_error'] );
			}
		} else {
			$redirect_url = wc_get_checkout_url();

			// redirect to success page if order processing
			$order = new WC_Order( $this->aps_order->get_session_order_id() );
			if ( in_array($order->get_status(), ['processing', 'completed']) ) {
				$redirect_url = $this->get_return_url( $order );
				unset( $_SESSION['aps_error'] );
			}
		}
		session_write_close();
		//echo '<script>window.top.location.href = "' . esc_url_raw( $redirect_url ) . '"</script>';
		ob_start();
		header('Location: ' . esc_url_raw($redirect_url));
		ob_end_flush();
		exit;
	}

	/**
	 * Check availability
	 *
	 * @return yes/no
	 */
	public function check_availability() {
		$apple_certificates = get_option( 'aps_apple_pay_certificates' );
		$is_enabled         = $this->aps_config->get_enable_apple_pay();
		if ( empty( $apple_certificates ) || ! isset( $apple_certificates['apple_certificate_path_file'] ) || ! isset( $apple_certificates['apple_certificate_key_file'] ) ) {
			$is_enabled = 'no';
		}
		if ( 'yes' === $this->aps_config->have_subscription() ) {
			$is_enabled = 'no';
		}
		return $is_enabled;
	}

	/**
	 * Process Refund
	 *
	 * @return bool
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		if ( empty( $amount ) || intval( $amount ) <= 0 ) {
			$error = new WP_Error();
			$error->add( 'aps_refund_error', __( 'Invalid amount', 'amazon-payment-services' ) );
			return $error;
		}
		$refund_status = $this->aps_refund->submit_apple_pay_refund( $order_id, $amount, $reason );
		return $refund_status;
	}
}
