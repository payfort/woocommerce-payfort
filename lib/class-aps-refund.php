<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * APS Payment
 *
 * @link       https://paymentservices.amazon.com/
 * @since      2.2.0
 *
 * @package    APS
 * @subpackage APS/lib
 */

/**
 * APS Payment
 *
 * @since      2.2.0
 * @package    APS
 * @subpackage APS/lib
 */
class APS_Refund extends APS_Super {
	/**
	 * Load Properties
	 */
	private static $instance;
	private $aps_config;
	private $aps_helper;
	private $aps_order;

	/**
	 * Constructor to init
	 */
	public function __construct() {
		$this->aps_config = APS_Config::get_instance();
		$this->aps_helper = APS_Helper::get_instance();
		$this->aps_order  = new APS_Order();
	}

	/**
	 * It will return instance of class
	 *
	 * @return APS_Refund
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new APS_Refund();
		}
		return self::$instance;
	}

	/**
	 * Submit Refund
	 */
	public function submit_refund( $order_id, $amount, $reason ) {
		try {
			$this->aps_order->load_order( $order_id );
			$order = wc_get_order( $order_id );
			$items = $order->get_items();
			foreach ( $items as $item ) {
				$order_item_name = $item->get_name();
			}
			if ( count( $items ) > 1 ) {
				$order_item_name = 'MutipleProducts';
			}
			$payment_details                     = get_post_meta( $order_id, 'aps_payment_response', true );

			$merchant_reference =  $payment_details['merchant_reference'];
            if($this->aps_order->get_payment_method() == APS_Constants::APS_PAYMENT_TYPE_STC_PAY){
                $stc_pay_reference_id = get_post_meta( $order_id, 'stc_pay_reference_id', true );
                if ( !empty($stc_pay_reference_id) && $merchant_reference !== $stc_pay_reference_id ){
                    $merchant_reference = $stc_pay_reference_id;
                    $this->aps_helper->log( 'APS refund stc_pay order_id#' . $order_id . 'stc_pay_reference_id#' . $stc_pay_reference_id );
                }
            }

            if($this->aps_order->get_payment_method() == APS_Constants::APS_PAYMENT_TYPE_TABBY){
                $tabby_reference_id = get_post_meta( $order_id, 'tabby_reference_id', true );
                if ( !empty($tabby_reference_id) && $merchant_reference !== $tabby_reference_id ){
                    $merchant_reference = $tabby_reference_id;
                    $this->aps_helper->log( 'APS refund tabby order_id#' . $order_id . 'tabby_reference_id#' . $tabby_reference_id );
                }
            }

            if ($merchant_reference)
			if ( empty($merchant_reference) ) {
				$payment_method = $this->aps_order->get_payment_method();
				if ( APS_Constants::APS_PAYMENT_TYPE_VALU == $payment_method ) {
					$valu_reference_id = get_post_meta( $order_id, 'valu_reference_id', true );
					if ( !empty($valu_reference_id) ) {
						$this->aps_helper->log( 'APS refund valu order_id#' . $order_id . 'valu_reference_id#' . $valu_reference_id );
						$order_id = $valu_reference_id;
					}
				}
                $merchant_reference = $order_id;
			}

			$gateway_params                      = array(
				'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
				'access_code'         => $this->aps_config->get_access_code(),
				'merchant_reference'  => $merchant_reference,
				'language'            => $this->aps_config->get_language(),
			);

			$currency = $payment_details['currency'];
			if ( empty($currency) ) {
				$currency = $this->aps_order->get_currency();
			}
			$gateway_params['currency']          = strtoupper( $currency);
			$total_amount                        = $this->aps_helper->convert_fort_amount( $amount, $this->aps_order->get_currency_value(), $currency );
			$gateway_params['amount']            = $total_amount;
			$gateway_params['command']           = APS_Constants::APS_COMMAND_REFUND;
			if ( $payment_details['fort_id'] ) {
				$gateway_params['fort_id']           = $payment_details['fort_id'];
			}
			$gateway_params['order_description'] = $this->aps_helper->clean_string( substr( ! empty( $reason ) ? $reason : $order_item_name, 0, 49 ) );
			$signature                           = $this->aps_helper->generate_signature( $gateway_params, 'request' );
			$gateway_params['signature']         = $signature;
			$gateway_url                         = $this->aps_config->get_gateway_url( 'api' );
			$this->aps_helper->log( 'APS refund request \n\n' . wp_json_encode( $gateway_params, true ) );
			$response                            = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
			$this->aps_helper->log( 'APS refund response \n\n' . wp_json_encode( $response, true ) );
			if ( APS_Constants::APS_REFUND_SUCCESS_RESPONSE_CODE === $response['response_code'] ) {
				throw new Exception( __( 'Refund submitted successfully', 'amazon-payment-services' ) );
			} else {
				throw new Exception( $response['response_message'] );
			}
		} catch ( Exception $e ) {
			$error = new WP_Error();
			$error->add( 'aps_refund_error', $e->getMessage() );
			return $error;
		}
	}

	/**
	 * Submit Refund
	 */
	public function submit_apple_pay_refund( $order_id, $amount, $reason ) {
		try {
			$this->aps_order->load_order( $order_id );
			$order = wc_get_order( $order_id );
			$items = $order->get_items();
			foreach ( $items as $item ) {
				$order_item_name = $item->get_name();
			}
			if ( count( $items ) > 1 ) {
				$order_item_name = 'MutipleProducts';
			}
			$payment_details                     = get_post_meta( $order_id, 'aps_payment_response', true );
			$gateway_params                      = array(
				'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
				'access_code'         => $this->aps_config->get_apple_pay_access_code(),
				'merchant_reference'  => $payment_details['merchant_reference'],
				'language'            => $this->aps_config->get_language(),
			);
			$gateway_params['currency']          = strtoupper( $payment_details['currency'] );
			$total_amount                        = $this->aps_helper->convert_fort_amount( $amount, $this->aps_order->get_currency_value(), $currency );
			$gateway_params['amount']            = $total_amount;
			$gateway_params['command']           = APS_Constants::APS_COMMAND_REFUND;
			$gateway_params['fort_id']           = $payment_details['fort_id'];
			$gateway_params['order_description'] = $this->aps_helper->clean_string( substr( ! empty( $reason ) ? $reason : $order_item_name, 0, 49 ) );
			$signature                           = $this->aps_helper->generate_signature( $gateway_params, 'request', 'apple_pay' );
			$gateway_params['signature']         = $signature;
			$gateway_url                         = $this->aps_config->get_gateway_url( 'api' );
			$response                            = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
			$this->aps_helper->log( 'APS apple pay refund response \n\n' . wp_json_encode( $response, true ) );
			if ( APS_Constants::APS_REFUND_SUCCESS_RESPONSE_CODE === $response['response_code'] ) {
				throw new Exception( __( 'Refund submitted successfully', 'amazon-payment-services' ) );
			} else {
				throw new Exception( $response['response_message'] );
			}
		} catch ( Exception $e ) {
			$error = new WP_Error();
			$error->add( 'aps_refund_error', $e->getMessage() );
			return $error;
		}
	}
}
