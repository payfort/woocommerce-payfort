<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * APS valu gateway class
 *
 * @link       https://paymentservices.amazon.com/
 * @since      2.2.0
 *
 * @package    APS
 * @subpackage APS/includes
 */

/**
 * APS valu gateway class
 *
 * @since      2.2.0
 * @package    APS
 * @subpackage APS/classes
 */
class WC_Gateway_APS_Valu extends WC_Gateway_APS_Super {

	public function __construct() {
		parent::__construct();
		$this->id                   = APS_Constants::APS_PAYMENT_TYPE_VALU; // payment gateway plugin ID
		$this->has_fields           = false; // in case you need a custom credit card form
		$this->method_title         = __( 'Amazon Payment Service - Valu', 'amazon-payment-services' );
		$this->title                = __( 'Buy Now, Pay Monthly', 'amazon-payment-services' );
		$this->description          = __( 'Amazon Payment Service - Valu', 'amazon-payment-services' );
		$this->method_description   = __( 'Accept valu payment', 'amazon-payment-services' ); // will be displayed on the options page
		$this->supported_currencies = array( 'EGP' );
		$this->enabled              = $this->check_availability();

		// We need custom JavaScript to obtain a token
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'display_valu_data' ), 10, 1 );
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
		$available = 'yes' === $this->aps_config->get_enable_valu() && in_array( strtoupper( $this->aps_helper->get_front_currency() ), $this->supported_currencies, true ) ? 'yes' : 'no';

		if ( 'yes' === $this->aps_config->have_subscription() ) {
			$available = 'no';
		}
		$cart_min_limit = $this->aps_config->get_valu_minimum_order_limit();
		if ( WC()->cart && floatval( WC()->cart->total ) < $cart_min_limit ) {
				$available = 'no';
			}
		return $available;
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
		$active_tenure = filter_input( INPUT_POST, 'active_tenure' );
		if ( ! empty( $active_tenure ) ) {
			$tenure_amount     = filter_input( INPUT_POST, 'tenure_amount' );
			$tenure_interest   = filter_input( INPUT_POST, 'tenure_interest' );
			$otp   = filter_input( INPUT_POST, 'aps_otp' );
			update_post_meta( $order_id, 'aps_redirected', 1 );
			$purchase_response = $this->aps_payment->valu_execute_purchase( $active_tenure, $otp );
			$redirect_link     = '';
			if ( 'success' === $purchase_response['status'] ) {
				$order = new WC_Order( $order_id );
				update_post_meta( $order_id, 'payment_gateway', APS_Constants::APS_GATEWAY_ID );
				$status = 'failed';
				if ( $status === $order->get_status() ) {
					$order->update_status( 'payment-pending', '' );
				}
				update_post_meta( $order_id, 'valu_active_tenure', $active_tenure );
				update_post_meta( $order_id, 'valu_tenure_amount', $tenure_amount );
				update_post_meta( $order_id, 'valu_tenure_interest', $tenure_interest );
				update_post_meta( $order_id, 'valu_tenure_interest', $tenure_interest );
                update_post_meta( $order_id, 'valu_transaction_id', $purchase_response['valu_transaction_id'] );
                update_post_meta( $order_id, 'loan_number', $purchase_response['loan_number'] );
				WC()->session->set( 'refresh_totals', true );
				$redirect_link = $this->get_return_url( $order );
			} else {
				$redirect_link         = wc_get_checkout_url();
				session_start();
				$_SESSION['aps_error'] = wp_kses_data($purchase_response['message']);
				session_write_close();
			}
			$result = array(
				'result'        => 'success',
				'redirect_link' => $redirect_link,
			);
			wp_send_json( $result );
		} else {
			session_start();
			$reference_id          = wp_kses_data($_SESSION['valu_payment']['reference_id']);
			$mobile_number         = wp_kses_data($_SESSION['valu_payment']['mobile_number']);
			$down_payment          = wp_kses_data($_SESSION['valu_payment']['down_payment']);
            $tou          = wp_kses_data($_SESSION['valu_payment']['tou']);
            $cash_back          = wp_kses_data($_SESSION['valu_payment']['cash_back']);
			session_write_close();
			$generate_otp_response = $this->aps_payment->valu_generate_otp( $reference_id, $mobile_number, $order_id , $down_payment, $tou, $cash_back);
			update_post_meta( $order_id, 'valu_reference_id', $reference_id );
            update_post_meta( $order_id, 'valu_down_payment', $down_payment/100 );
            update_post_meta( $order_id, 'valu_tou', $tou );
            update_post_meta( $order_id, 'valu_cash_back', $cash_back );
			wp_send_json( $generate_otp_response );
		}
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
			APS_Public::load_valu_wizard( $this->aps_config->get_language(), $this->aps_config->get_enable_valu_down_payment(), $this->aps_config->get_valu_down_payment_value() );
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
	 * Display Valu data
	 */
	public function display_valu_data( $order_id ) {
		$aps_response_meta = get_post_meta( $order_id, 'aps_payment_response', true );
		echo '<h2> ' . wp_kses_data( __( 'valU Details', 'amazon-payment-services' ) ) . '</h2>';
		echo '<h4> ' . wp_kses_data( __( 'Installment Plan', 'amazon-payment-services' ) ) . ' : ' . esc_attr(get_post_meta( $order_id, 'valu_tenure_amount', true )) . ' ' . esc_attr($aps_response_meta['currency']) . ' per ' . esc_html__( 'Month', 'amazon-payment-services' ) . ' for ' . esc_attr(get_post_meta( $order_id, 'valu_active_tenure', true )) . ' ' . esc_html__( 'Month', 'amazon-payment-services' ) . '</h4>';
		echo '<h4> ' . wp_kses_data( __( 'Admin Fee', 'amazon-payment-services' ) ) . ' : ' . esc_attr(get_post_meta( $order_id, 'valu_tenure_interest', true )) . ' ' . esc_attr($aps_response_meta['currency']) . '</h4>';
		echo '<h4> ' . wp_kses_data( __( 'Down Payment', 'amazon-payment-services' ) ) . ' : ' . esc_attr(get_post_meta( $order_id, 'valu_down_payment', true )) . ' ' . esc_attr($aps_response_meta['currency']) . '</h4>';
		echo '<h4> ' . wp_kses_data( __( 'Cash Back', 'amazon-payment-services' ) ) . ' : ' . esc_attr(get_post_meta( $order_id, 'valu_cash_back', true )) . ' ' . esc_attr($aps_response_meta['currency']) . '</h4>';
		echo '<h4> ' . wp_kses_data( __( 'ToU', 'amazon-payment-services' ) ) . ' : ' . esc_attr(get_post_meta( $order_id, 'valu_tou', true )) . ' ' . esc_attr($aps_response_meta['currency']) . '</h4>';
        echo '<h4> ' . wp_kses_data( __( 'valU Transaction ID', 'amazon-payment-services' ) ) . ' : ' . esc_attr(get_post_meta( $order_id, 'valu_transaction_id', true )) . '</h4>';
        echo '<h4> ' . wp_kses_data( __( 'Loan Number', 'amazon-payment-services' ) ) . ' : ' . esc_attr(get_post_meta( $order_id, 'loan_number', true )) . '</h4>';
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
		if ( $amount > $total_amount ) {
			$error = new WP_Error();
			$error->add( 'aps_refund_error', __( 'Refund amount should be less than purchase amount', 'amazon-payment-services' ) );
			return $error;
		} else {
			$refund_status = $this->aps_refund->submit_refund( $order_id, $amount, $reason );
			return $refund_status;
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
		$valu_logo       = $image_directory . 'valu-logo.png';
		//Wrap icons
		$icon_html .= '<img src="' . $valu_logo . '" alt="valu" class="payment-icons" />';
		$icon_html .= '</span>';
		return $icon_html;
	}
}
