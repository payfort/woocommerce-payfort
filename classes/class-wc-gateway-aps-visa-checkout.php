<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * APS visa checkout gateway class
 *
 * @link       https://paymentservices.amazon.com/
 * @since      2.2.0
 *
 * @package    APS
 * @subpackage APS/includes
 */

/**
 * APS visa checkout gateway class
 *
 * @since      2.2.0
 * @package    APS
 * @subpackage APS/classes
 */
class WC_Gateway_APS_Visa_Checkout extends WC_Gateway_APS_Super {

	public function __construct() {
		parent::__construct();
		$this->plugin_name        = APS_NAME;
		$this->id                 = APS_Constants::APS_PAYMENT_TYPE_VISA_CHECKOUT; // payment gateway plugin ID
		$this->icon               = ''; // URL of the icon that will be displayed on checkout page near your gateway name
		$this->has_fields         = false; // in case you need a custom credit card form
		$this->method_title       = __( 'Amazon Payment Service - Visa Checkout', 'amazon-payment-services' );
		$this->title              = __( 'Visa Checkout', 'amazon-payment-services' );
		$this->description        = __( 'Amazon Payment Service - Visa Checkout', 'amazon-payment-services' );
		$this->method_description = __( 'Accept installments', 'amazon-payment-services' ); // will be displayed on the options page
		$this->enabled            = $this->check_availability();

		// We need custom JavaScript to obtain a token
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_checkout_hander' ), 10, 2 );
	}

	/**
	 * Payment script file to be loaded
	 *
	 * @return void
	 */
	public function payment_scripts() {
		wp_register_script( $this->plugin_name . '-visa-checkout', plugin_dir_url( dirname( __FILE__ ) ) . 'public/js/aps-visa-checkout.js', array( 'jquery' ), APS_VERSION, false );
		$cart_total   = WC()->cart->cart_contents_total;
		$currency     = $this->aps_helper->get_fort_currency();
		$total_amount = $this->aps_helper->convert_fort_amount( $cart_total, 1, $currency );
		$vc_params    = array(
			'api_key'      => $this->aps_config->get_visa_checkout_api_key(),
			'profile_id'   => $this->aps_config->get_visa_checkout_profile_id(),
			'screen_msg'   => get_bloginfo( 'name' ),
			'total_amount' => $total_amount,
			'currency'     => $currency,
			'continue_btn' => __( 'Continue', 'amazon-payment-services' ),
			'country_code' => WC()->countries->get_base_country(),
			'language'     => $this->aps_config->get_language(),
			'aps_vc_integration_type' => $this->get_integration_type()
		);
		wp_localize_script( $this->plugin_name . '-visa-checkout', 'vc_params', $vc_params );
		wp_enqueue_script( $this->plugin_name . '-visa-checkout' );
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
		if ( class_exists( 'APS_Public' ) && APS_Constants::APS_INTEGRATION_TYPE_HOSTED_CHECKOUT === $this->get_integration_type() ) {
			APS_Public::load_visa_checkout_wizard( $this->aps_config->get_visa_checkout_sdk(), $this->aps_config->get_visa_checkout_button_url() );
		}
	}

	/**
	 * Get integration type
	 *
	 * @return string
	 */
	public function get_integration_type() {
		return $this->aps_config->get_visa_checkout_integration_type();
	}

	/**
	 * Validate checkout
	 *
	 * @return void
	 */
	public function validate_checkout_hander( $fields, $errors ) {
		$payment_method = filter_input( INPUT_POST, 'payment_method' );
		if ( APS_Constants::APS_INTEGRATION_TYPE_HOSTED_CHECKOUT === $this->get_integration_type() && $this->id === $payment_method ) {
			$visa_call_id = filter_input( INPUT_POST, 'aps_visa_checkout_callid' );
			$visa_status  = filter_input( INPUT_POST, 'aps_visa_checkout_status' );
			if ( 'cancel' === $visa_status ) {
				$errors->add( 'aps_visa_checkout_error', 'VISA checkout process cancelled by user.' );
			} elseif ( 'error' === $visa_status ) {
				$errors->add( 'aps_visa_checkout_error', 'Something went wrong in visa checkout process. Please try again later.' );
			} elseif ( empty( $visa_call_id ) ) {
				$errors->add( 'aps_visa_checkout_error', 'Please complete VISA checkout process first.' );
			}
		}
	}

	/**
	 * Process the payment and return the result
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );
		update_post_meta( $order_id, 'payment_gateway', APS_Constants::APS_GATEWAY_ID );
		$status = 'failed';
		if ( $status === $order->get_status() ) {
			$order->update_status( 'payment-pending', '' );
		}
		$status = 'failed';
		if ( $status === $order->get_status() ) {
			$order->update_status( 'payment-pending', '' );
		}
		$payment_method        = $this->id;
		$integration_type      = $this->get_integration_type();
		$extras                = array();
		$result                = array();
		$visa_checkout_call_id = filter_input( INPUT_POST, 'aps_visa_checkout_callid' );
		if ( ! empty( $visa_checkout_call_id ) ) {
			$extras['visa_checkout_call_id'] = $visa_checkout_call_id;
		}
		if ( APS_Constants::APS_INTEGRATION_TYPE_REDIRECTION === $integration_type ) {
			$payment_data = $this->aps_payment->get_payment_request_form( $payment_method, $integration_type, $payment_option, $extras );
			$result       = array(
				'result' => 'success',
				'url'    => $payment_data['url'],
				'params' => $payment_data['params'],
			);
			if ( isset( $payment_data['form'] ) ) {
				$result['form'] = $payment_data['form'];
			}
		} else {
			$payment_data = $this->aps_payment->aps_notify( $extras, $order_id, $integration_type, $payment_method, true );
			if ( isset( $payment_data['3ds_url'] ) ) {
				$result = array(
					'result'        => 'success',
					'redirect_link' => $payment_data['3ds_url'],
				);
			} else {
				session_start();
				$_SESSION['aps_error'] = wp_kses_data($payment_data['response_message']);
				session_write_close();
				$result                = array(
					'result'        => 'failure',
					'redirect_link' => wc_get_checkout_url(),
				);
			}
		}
		//save integration type
		update_post_meta( $order_id, 'APS_INTEGRATION_TYPE', $integration_type );
		update_post_meta( $order_id, 'aps_redirected', 1 );
		wp_send_json( $result );
		wp_die();
	}

	/**
	 * Check if available
	 *
	 * @return string
	 */
	public function check_availability() {
		$available = 'yes' === $this->aps_config->get_enable_visa_checkout() ? 'yes' : 'no';
		if ( 'yes' === $this->aps_config->have_subscription() ) {
			$available = 'no';
		}
		return $available;
	}
}
