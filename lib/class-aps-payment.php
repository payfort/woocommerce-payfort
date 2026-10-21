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
class APS_Payment extends APS_Super {

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
	 * @return APS_Payment
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new APS_Payment();
		}
		return self::$instance;
	}

	/**
	 * Return Request form with params
	 *
	 * @return array
	 */
	public function get_payment_request_form( $payment_method, $integration_type, $payment_option, $extras ) {
		try {
			$payment_request_params = $this->build_payment_gateway_params( $payment_method, $integration_type, $payment_option, $extras );
		} catch ( \Exception $e ) {
			// Fail closed: never send a request whose amount could not be priced correctly.
			return array(
				'url'                    => '',
				'params'                 => array(),
				'is_hosted_tokenization' => false,
				'redirect_url'           => null,
				'error_message'          => $e->getMessage(),
			);
		}
		$aps_data               = array(
			'url'                    => $payment_request_params['url'],
			'params'                 => $payment_request_params['params'],
			'is_hosted_tokenization' => $payment_request_params['is_hosted_tokenization'],
			'redirect_url'           => $payment_request_params['redirect_url'],
		);
		if (
            APS_Constants::APS_INTEGRATION_TYPE_REDIRECTION === $integration_type
            || APS_Constants::APS_INTEGRATION_TYPE_HOSTED_CHECKOUT === $integration_type
        ) {
			$aps_request_form = '<form name="aps_payment_form" id="' . APS_Constants::APS_SELECTOR_PAYMENT_REQFORM_ID . '" method="POST" action="' . $payment_request_params['url'] . '">';
			foreach ( $payment_request_params['params'] as $k => $v ) {
				$aps_request_form .= '<input type="hidden" name="' . $k . '" value="' . $v . '">';
			}
			$aps_request_form .= '<input type="submit">';
			$aps_data['form']  = $aps_request_form;
		}

		if (isset($payment_request_params['error_message'])) {
			$aps_data['error_message'] = $payment_request_params['error_message'];
		}

		return $aps_data;
	}

	/**
	 * Build payment request params
	 *
	 * @param payment_method string
	 * @param integration_type string
	 * @param payment_option string
	 *
	 * @return array
	 */
	public function build_payment_gateway_params( $payment_method, $integration_type, $payment_option, $extras ) {
		$order_id = isset( $extras['order_id'] ) ? $extras['order_id'] : $this->aps_order->get_session_order_id();
		$this->aps_order->load_order( $order_id );
		$is_hosted_tokenization = false;
		$redirect_url           = null;
		$error_message          = null;

		$gateway_params = array(
			'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
			'access_code'         => $this->aps_config->get_access_code(),
			'merchant_reference'  => $order_id,
			'language'            => $this->aps_config->get_language(),
		);
		if ( APS_Constants::APS_INTEGRATION_TYPE_REDIRECTION === $integration_type ) {
			// Currency and amount come from the stored order, never the storefront cookie.
			$currency                            = $this->aps_helper->get_order_fort_currency( $this->aps_order->get_loaded_order() );
			$gateway_params['currency']          = strtoupper( $currency );
			$gateway_params['amount']            = $this->aps_helper->get_order_fort_amount( $this->aps_order->get_loaded_order(), $currency );
			$gateway_params['customer_email']    = $this->aps_order->get_email();
			$gateway_params['command']           = $this->aps_config->get_command( $payment_method );
			$gateway_params['order_description'] = 'Order#' . $order_id;
			if ( isset( $extras['aps_payment_token'] ) && ! empty( $extras['aps_payment_token'] ) ) {
				$gateway_params['token_name'] = $extras['aps_payment_token'];
			}
			$gateway_params['return_url'] = create_wc_api_url( 'aps_online_response' );
			if ( ! empty( $payment_option ) ) {
				$gateway_params['payment_option'] = $payment_option;
			} elseif ( APS_Constants::APS_PAYMENT_TYPE_INSTALLMENT === $payment_method ) {
				$gateway_params['installments'] = APS_Constants::APS_COMMAND_STANDALONE;
				$gateway_params['command']      = APS_Constants::APS_COMMAND_PURCHASE;
			} elseif ( APS_Constants::APS_PAYMENT_TYPE_VISA_CHECKOUT === $payment_method ) {
				$gateway_params['digital_wallet'] = APS_Constants::APS_COMMAND_VISA_CHECKOUT_WALLET;
			}
            if ( APS_Constants::APS_PAYMENT_TYPE_STC_PAY === $payment_method ) {
                $gateway_params['digital_wallet'] =  APS_Constants::APS_PAYMENT_METHOD_STC_PAY;
                $gateway_params['merchant_reference'] = $this->aps_helper->generate_random_key();
                update_post_meta( $order_id , 'stc_pay_reference_id' , $gateway_params['merchant_reference'] );
                $gateway_params['customer_ip'] = $this->aps_order->get_customer_ip();
                $gateway_params['customer_name'] =  $this->aps_order->get_customer_name();
                $gateway_params['merchant_extra'] =  $order_id;
            }
            if ( APS_Constants::APS_PAYMENT_TYPE_TABBY === $payment_method ) {
                $gateway_params['payment_option'] =  APS_Constants::APS_PAYMENT_METHOD_TABBY;
                $gateway_params['merchant_reference'] = $order_id;
                update_post_meta( $order_id , 'tabby_reference_id' , $gateway_params['merchant_reference'] );
                $gateway_params['customer_ip'] = $this->aps_order->get_customer_ip();
                $gateway_params['customer_name'] =  $this->aps_order->get_customer_name();
                $gateway_params['phone_number'] =  $this->aps_order->get_phone_number();
            }
			$plugin_params  = $this->aps_config->plugin_params();
			$gateway_params = array_merge( $gateway_params, $plugin_params );
			if ( APS_Constants::APS_PAYMENT_TYPE_CC !== $payment_method && APS_Constants::APS_PAYMENT_TYPE_VISA_CHECKOUT !== $payment_method && APS_Constants::APS_PAYMENT_TYPE_STC_PAY !== $payment_method && APS_Constants::APS_PAYMENT_TYPE_TABBY !== $payment_method) {
				unset( WC()->session->order_awaiting_payment );
			}
		} else {
			$gateway_params['service_command'] = APS_Constants::APS_COMMAND_TOKENIZATION;
			$gateway_params['return_url']      = create_wc_api_url( 'aps_merchant_response' );
			if ( APS_Constants::APS_PAYMENT_TYPE_INSTALLMENT === $payment_method && APS_Constants::APS_INTEGRATION_TYPE_STANDARD_CHECKOUT === $integration_type ) {
				$currency                       = $this->aps_helper->get_order_fort_currency( $this->aps_order->get_loaded_order() );
				$gateway_params['currency']     = strtoupper( $currency );
				$gateway_params['installments'] = APS_Constants::APS_COMMAND_STANDALONE;
				$gateway_params['amount']       = $this->aps_helper->get_order_fort_amount( $this->aps_order->get_loaded_order(), $currency );
			}
			if ( isset( $extras['aps_payment_token'] ) && ! empty( $extras['aps_payment_token'] ) ) {
				$gateway_params['token_name'] = $extras['aps_payment_token'];
				if ( isset( $extras['aps_payment_cvv'] ) && ! empty( $extras['aps_payment_cvv'] ) ) {
					$gateway_params['card_security_code'] = $extras['aps_payment_cvv'];
				}
				if ( isset( $extras['aps_card_bin'] ) && ! empty( $extras['aps_card_bin'] ) ) {
					$gateway_params['card_bin'] = $extras['aps_card_bin'];
				}
				$aps_notify_params       = $this->aps_notify( $gateway_params, $order_id, $integration_type );
				$notify_response_message = $aps_notify_params['response_message'];
				$notify_code             = $aps_notify_params['response_code'];
				if ( APS_Constants::APS_INTEGRATION_TYPE_HOSTED_CHECKOUT === $integration_type ) {
					$is_hosted_tokenization = true;
				}
				if ( APS_Constants::APS_PAYMENT_SUCCESS_RESPONSE_CODE === $notify_code ) {
					if ( ! $this->aps_order->success_order( $aps_notify_params, 'online' ) ) {
						$this->aps_helper->log( 'APS response failed verification for order ' . $order_id . '; order held for review instead of being marked paid.' );
					}
					$redirect_url = $this->aps_order->get_checkout_success_url();
				} elseif ( APS_Constants::APS_MERCHANT_SUCCESS_RESPONSE_CODE === $notify_code && isset( $aps_notify_params['3ds_url'] ) ) {
					$this->aps_helper->log( 'build 3ds_url=' . $aps_notify_params['3ds_url'] );
					$redirect_url = $aps_notify_params['3ds_url'];
				} elseif ( in_array( $notify_code, APS_Constants::APS_ONHOLD_RESPONSE_CODES, true ) ) {
					$this->aps_order->on_hold_order( $notify_response_message );
					$aps_error_log = "APS handler ERROR -\n\n" . wp_json_encode( $aps_notify_params, true );
					$this->aps_helper->log( $aps_error_log );
					$redirect_url = $this->aps_order->get_checkout_success_url();
				} else {
					$aps_error_log = "APS handler ERROR::\n\n" . wp_json_encode( $aps_notify_params, true );
					$this->aps_helper->log( $aps_error_log );
					$result                = $this->aps_order->decline_order( $aps_notify_params, $notify_response_message );
					session_start();
					$_SESSION['aps_error'] = wp_kses_data($notify_response_message);
					session_write_close();
					$redirect_url          = wc_get_checkout_url();
					$error_message = $notify_response_message;
				}
			}
		}
		// Remember exactly what was asked for, so the response can be checked against it.
		$this->aps_order->record_expected_payment(
			isset( $gateway_params['amount'] ) ? $gateway_params['amount'] : '',
			isset( $gateway_params['currency'] ) ? $gateway_params['currency'] : ''
		);
		$signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
		$gateway_params['signature'] = $signature;
		//In case of subscription on we explictly set remember_me to yes
		if ( APS_Constants::APS_INTEGRATION_TYPE_HOSTED_CHECKOUT === $integration_type && 'yes' === $this->aps_config->have_subscription() ) {
			$gateway_params['remember_me'] = 'YES';
		}

		$gateway_url = $this->aps_config->get_gateway_url();
		$builder     = array(
			'url'                    => $gateway_url,
			'params'                 => $gateway_params,
			'is_hosted_tokenization' => $is_hosted_tokenization,
			'redirect_url'           => $redirect_url,
		);
		if (!empty($error_message)) {
			$builder['error_message'] = $error_message;
		}
		$log_builder           = $builder;
		$log_builder['params'] = $this->aps_helper->redact_sensitive_params( $gateway_params );
		$this->aps_helper->log( 'APS build_payment_gateway_params payment method ($payment_method) \n\n' . wp_json_encode( $log_builder, true ) );
		return $builder;
	}

	/**
	 * Handle fort response
	 *
	 * @param response_params array
	 * @param response_mode string
	 * @param integration_type string
	 *
	 * @return bool
	 */
	public function handle_fort_response( $response_params, $response_mode, $integration_type ) {
		try {
			$success          = false;
			$response_message = __( 'Invalid Fort Parameters', 'amazon-payment-services' );
			$aps_error_log    = "APS handler ERROR=\n\n" . wp_json_encode( $response_params, true );
			if ( empty( $response_params ) ) {
				$this->aps_helper->log( $aps_error_log );
				throw new Exception( $response_message );
			}

			if ( ! isset( $response_params['merchant_reference'] ) || empty( $response_params['merchant_reference'] ) ) {
				$this->aps_helper->log( $aps_error_log );
				throw new Exception( $response_message );
			}

			// FIX: Validate merchant reference is a positive integer before loading order
			$merchant_ref_value = ( isset( $response_params['payment_option'] ) && $response_params['payment_option'] === APS_Constants::APS_PAYMENT_METHOD_STC_PAY )
				? ( isset( $response_params['merchant_extra'] ) ? $response_params['merchant_extra'] : '' )
				: $response_params['merchant_reference'];

			if ( empty( $merchant_ref_value ) || ! is_numeric( $merchant_ref_value ) || intval( $merchant_ref_value ) <= 0 ) {
				$this->aps_helper->log( 'APS handler ERROR: Invalid merchant reference value: ' . wp_json_encode( $merchant_ref_value ) );
				return false;
			}

            if (isset($response_params['payment_option']) && $response_params['payment_option'] ===  APS_Constants::APS_PAYMENT_METHOD_STC_PAY){
                $order_id = $response_params['merchant_extra'];
                $this->aps_order->load_order( $order_id );
            } else{
                $order_id = $response_params['merchant_reference'];
                $this->aps_order->load_order( $order_id );
            }

			// check if webhook call for valu refund
			$order = $this->aps_order->get_loaded_order();
			$order_id_by_reference = '';
			if ( ! ( $order && $order->get_id() ) ) {
				if ( isset( $response_params['command'] ) && 'REFUND' == $response_params['command'] ) {
					$this->aps_helper->log( 'Valu REFUND merchant_reference' . $response_params['merchant_reference']);
					$order_id_by_reference = $this->aps_helper->find_order_by_reference( $response_params['merchant_reference'] , APS_Constants::APS_PAYMENT_METHOD_VALU);
					$this->aps_helper->log( 'Valu REFUND order_id' . $response_params['merchant_reference']);
				}
			}

			// FIX: Verify the order was placed through an APS payment gateway.
			// This prevents the callback from acting on orders placed via non-APS methods
			// (e.g., bank transfer, cheque) which should never be modified by APS webhooks.
			$order = $this->aps_order->get_loaded_order();
			if ( $order && $order->get_id() ) {
				$loaded_payment_method = $this->aps_order->get_payment_method();
				$aps_payment_methods   = array(
					APS_Constants::APS_PAYMENT_TYPE_CC,
					APS_Constants::APS_PAYMENT_TYPE_VALU,
					APS_Constants::APS_PAYMENT_TYPE_INSTALLMENT,
					APS_Constants::APS_PAYMENT_TYPE_NAPS,
					APS_Constants::APS_PAYMENT_TYPE_BENEFIT,
					APS_Constants::APS_PAYMENT_TYPE_KNET,
					APS_Constants::APS_PAYMENT_TYPE_OMANNET,
					APS_Constants::APS_PAYMENT_TYPE_VISA_CHECKOUT,
					APS_Constants::APS_PAYMENT_TYPE_APPLE_PAY,
					APS_Constants::APS_PAYMENT_TYPE_STC_PAY,
					APS_Constants::APS_PAYMENT_TYPE_TABBY,
				);
				if ( ! in_array( $loaded_payment_method, $aps_payment_methods, true ) ) {
					$this->aps_helper->log( 'APS handler ERROR: Order #' . $order->get_id() . ' payment method (' . $loaded_payment_method . ') is not an APS method. Ignoring callback.' );
					return false;
				}
			}
			$excluded_params = array( 'signature', 'wc-ajax', 'wc-api', 'APS_fort', 'integration_type', 'WordApp_launch', 'WordApp_mobile_site', 'WordApp_demo', 'WordApp_demo', 'lang' );

			$response_type           = $response_params['response_message'];
			$signature               = $response_params['signature'];
			$response_order_id       = $response_params['merchant_reference'];
			$response_status         = isset( $response_params['status'] ) ? $response_params['status'] : '';
			$response_code           = isset( $response_params['response_code'] ) ? $response_params['response_code'] : '';
			$response_status_message = $response_type;

			$response_gateway_params = $response_params;
			foreach ( $response_gateway_params as $k => $v ) {
				if ( in_array( $k, $excluded_params, true ) ) {
					unset( $response_gateway_params[ $k ] );
				}
			}
			//update order id if webhook call for valu refund
			if ( '' != $order_id_by_reference && ( ! ( $order && $order->get_id() ) ) ) {
				$order_id = $order_id_by_reference;
				$this->aps_order->load_order( $order_id );
				$response_params['merchant_reference'] = $order_id;
				$this->aps_helper->log( 'Valu REFUND order_id from reference_id' . $order_id);
			}

			$payment_method = $this->aps_order->get_payment_method();

			$aps_payment_methods = array(
				APS_Constants::APS_PAYMENT_TYPE_CC,
				APS_Constants::APS_PAYMENT_TYPE_VALU,
				APS_Constants::APS_PAYMENT_TYPE_INSTALLMENT,
				APS_Constants::APS_PAYMENT_TYPE_NAPS,
				APS_Constants::APS_PAYMENT_TYPE_BENEFIT,
				APS_Constants::APS_PAYMENT_TYPE_KNET,
				APS_Constants::APS_PAYMENT_TYPE_OMANNET,
				APS_Constants::APS_PAYMENT_TYPE_VISA_CHECKOUT,
				APS_Constants::APS_PAYMENT_TYPE_APPLE_PAY,
				APS_Constants::APS_PAYMENT_TYPE_STC_PAY,
				APS_Constants::APS_PAYMENT_TYPE_TABBY,
			);

			if ( ! in_array( $payment_method, $aps_payment_methods, true ) ) {
				$this->aps_helper->log( 'SECURITY: Callback rejected — order #' . $order_id . ' uses non-APS payment method: ' . $payment_method );
				return false;
			}

			// FIX: Determine signature type from the order's stored payment method
			// (trusted server-side data) instead of attacker-controlled digital_wallet parameter.
			// This prevents key confusion attacks where an attacker forces Apple Pay key selection
			// by setting digital_wallet=APPLE_PAY in the webhook request.
			$signature_type = ( APS_Constants::APS_PAYMENT_TYPE_APPLE_PAY === $payment_method ) ? 'apple_pay' : 'regular';

			$response_signature = $this->aps_helper->generate_signature( $response_gateway_params, 'response', $signature_type );

            $isTabbyPay = false;
            if ($payment_method == APS_Constants::APS_PAYMENT_TYPE_TABBY || APS_Constants::APS_PAYMENT_METHOD_TABBY === $response_params['payment_option'] ){
                $isTabbyPay = true;
            }

			// check the signature
			if ( strtolower( $response_signature ) !== strtolower( $signature ) ) {
				// FIX: A request whose signature does not verify is untrusted.
				// Do NOT mutate order state — only log the mismatch and return false.
				$aps_invalid_signature_log = "APS Response invalid signature ERROR\n\n Original array : " . wp_json_encode( $response_params, true ) . "\n\n\n Final array : " . wp_json_encode( $response_gateway_params, true );
				$this->aps_helper->log( $aps_invalid_signature_log );
				return false;
			}
			if ( APS_Constants::APS_PAYMENT_CANCEL_RESPONSE_CODE === $response_code ) {
				$response_message = __( 'Transaction Cancelled', 'amazon-payment-services' );
				$result           = $this->aps_order->decline_order( $response_params, $response_message );
				if ( $result ) {
					$this->aps_helper->log( $aps_error_log );
					throw new Exception( $response_message );
				}
			}
			if ( APS_Constants::APS_PAYMENT_SUCCESS_RESPONSE_CODE === $response_code || APS_Constants::APS_PAYMENT_AUTHORIZATION_SUCCESS_RESPONSE_CODE === $response_code ) {
				if ( ! $this->aps_order->success_order( $response_params, $response_mode ) ) {
					$this->aps_helper->log( 'APS response failed verification for order ' . $order_id . '; order held for review instead of being marked paid.' );
				}
			} elseif ( in_array( $response_code, APS_Constants::APS_ONHOLD_RESPONSE_CODES, true ) ) {
				$this->aps_order->on_hold_order( $response_status_message );
				$this->aps_helper->log( $aps_error_log );
			} elseif ( APS_Constants::APS_CAPTURE_SUCCESS_RESPONSE_CODE === $response_code || APS_Constants::APS_PAYMENT_AUTHORIZATION_SUCCESS_RESPONSE_CODE === $response_code ) {
				$this->aps_order->capture_order( $response_params, $response_mode );
			} elseif ( APS_Constants::APS_REFUND_SUCCESS_RESPONSE_CODE === $response_code ) {
				$this->aps_order->refund_order( $response_params, $response_mode );
			} elseif ( APS_Constants::APS_AUTHORIZATION_VOIDED_SUCCESS_RESPONSE_CODE === $response_code || APS_Constants::APS_PAYMENT_AUTHORIZATION_SUCCESS_RESPONSE_CODE === $response_code ) {
				$this->aps_order->void_order( $response_params, $response_mode );
			} elseif ( APS_Constants::APS_TOKENIZATION_SUCCESS_RESPONSE_CODE === $response_code || APS_Constants::APS_UPDATE_TOKENIZATION_SUCCESS_RESPONSE_CODE === $response_code ) {
				update_post_meta( $order_id, 'tokenization_status', 'yes' );
				$aps_notify_params       = $this->aps_notify( $response_params, $order_id, $integration_type );
				$notify_response_message = $aps_notify_params['response_message'];
				$notify_code             = $aps_notify_params['response_code'];
				if ( APS_Constants::APS_MERCHANT_SUCCESS_RESPONSE_CODE === $notify_code || APS_Constants::APS_PAYMENT_SUCCESS_RESPONSE_CODE === $notify_code ) {
					if ( isset( $aps_notify_params['3ds_url'] ) ) {
						if ( APS_Constants::APS_INTEGRATION_TYPE_STANDARD_CHECKOUT == $integration_type) {
							$this->aps_helper->log( 'JS 3ds_url redirect=' . $aps_notify_params['3ds_url'] );
							echo '<script>window.top.location.href = "' . esc_url_raw( $aps_notify_params['3ds_url'] ) . '"</script>';
							exit;
						} else {
							$this->aps_helper->log( '3ds_url redirect=' . $aps_notify_params['3ds_url'] );
							$ds_method = $this->aps_config->get_threeds_redirection_method();
							if ('server_side' == $ds_method ) {
								ob_start();
								header('Location: ' . esc_url_raw($aps_notify_params['3ds_url']));
								ob_end_flush();
							} else {
								echo '<script>window.top.location.href = "' . esc_url_raw( $aps_notify_params['3ds_url'] ) . '"</script>';
							}
							exit;
						}
					} else {
						if ( ! $this->aps_order->success_order( $aps_notify_params, $response_mode ) ) {
							$this->aps_helper->log( 'APS response failed verification for order ' . $order_id . '; order held for review instead of being marked paid.' );
						}
					}
				} elseif ( in_array( $notify_code, APS_Constants::APS_ONHOLD_RESPONSE_CODES, true ) ) {
					$this->aps_order->on_hold_order( $notify_response_message );
					$aps_error_log = "APS handler ERROR:\n\n" . wp_json_encode( $aps_notify_params, true );
					$this->aps_helper->log( $aps_error_log );
				} else {
					$result        = $this->aps_order->decline_order( $aps_notify_params, $notify_response_message );
					$aps_error_log = "APS handler ERROR-\n\n" . wp_json_encode( $aps_notify_params, true );
					$this->aps_helper->log( $aps_error_log );
					throw new Exception( $notify_response_message );
				}
			} elseif ( APS_Constants::APS_SAFE_TOKENIZATION_SUCCESS_RESPONSE_CODE === $response_code ) {
				update_post_meta( $order_id, 'tokenization_status', 'yes' );
				$aps_notify_params       = $this->aps_notify( $response_params, $order_id, $integration_type );
				$notify_response_message = $aps_notify_params['response_message'];
				$notify_code             = $aps_notify_params['response_code'];
				if ( APS_Constants::APS_MERCHANT_SUCCESS_RESPONSE_CODE === $notify_code || APS_Constants::APS_PAYMENT_SUCCESS_RESPONSE_CODE === $notify_code ) {
					if ( isset( $aps_notify_params['3ds_url'] ) ) {
						$this->aps_helper->log( '3ds_url redirect :=' . $aps_notify_params['3ds_url'] );
						$ds_method = $this->aps_config->get_threeds_redirection_method();
						if ( 'server_side' == $ds_method ) {
							ob_start();
							header('Location: ' . esc_url_raw($aps_notify_params['3ds_url']));
							ob_end_flush();
						} else {
							echo '<script>window.top.location.href = "' . esc_url_raw( $aps_notify_params['3ds_url'] ) . '"</script>';
						}
						exit;
					} else {
						if ( ! $this->aps_order->success_order( $aps_notify_params, $response_mode ) ) {
							$this->aps_helper->log( 'APS response failed verification for order ' . $order_id . '; order held for review instead of being marked paid.' );
						}
					}
				} elseif ( in_array( $notify_code, APS_Constants::APS_ONHOLD_RESPONSE_CODES, true ) ) {
					$this->aps_order->on_hold_order( $notify_response_message );
					$aps_error_log = "APS handler ERROR :\n\n" . wp_json_encode( $aps_notify_params, true );
					$this->aps_helper->log( $aps_error_log );
				} else {
					$result        = $this->aps_order->decline_order( $aps_notify_params, $notify_response_message );
					$aps_error_log = "APS handler ERROR =\n\n" . wp_json_encode( $aps_notify_params, true );
					$this->aps_helper->log( $aps_error_log );
					throw new Exception( $notify_response_message );
				}
			} else {
				$result = $this->aps_order->decline_order( $response_params, $response_status_message );
				$this->aps_helper->log( $aps_error_log );
				throw new Exception( $response_status_message );
			}
		} catch ( Exception $e ) {
			//need to store data in session here
			session_start();
			$_SESSION['aps_error'] = wp_kses_data($e->getMessage());
			session_write_close();
			return false;
		}
		return true;
	}

	/**
	 * APS notify
	 *
	 * @param response_params array
	 * @param order_id int
	 *
	 * @return bool
	 */
	public function aps_notify( $response_params, $order_id, $integration_type, $loaded_order = false ) {
		//send host to host
		if ( $loaded_order ) {
			$this->aps_order->load_order( $order_id );
		}
		// Charge in the merchant's configured gateway currency, converted from the order total.
		$currency       = $this->aps_helper->get_order_fort_currency( $this->aps_order->get_loaded_order() );
		$payment_method = $this->aps_order->get_payment_method();
		$command        = APS_Constants::APS_COMMAND_PURCHASE;
		$plan_code      = get_post_meta( $order_id, 'hosted_cc_plan_code', true );
		if ( class_exists( 'WC_Subscriptions_Cart' ) && WC_Subscriptions_Cart::cart_contains_subscription() ) {
			$command = APS_Constants::APS_COMMAND_PURCHASE;
		} elseif ( isset( $response_params['card_bin'] ) && ! empty( $response_params['card_bin'] ) ) {
			$command = $this->aps_config->get_command( $payment_method, $response_params['card_bin'] );
		} elseif ( isset( $response_params['card_number'] ) && ! empty( $response_params['card_number'] ) ) {
			$command = $this->aps_config->get_command( $payment_method, substr( $response_params['card_number'], 0, 6 ) );
		} else {
			$command = $this->aps_config->get_command( $payment_method );
		}

		if ( isset( $response_params['token_name'] ) && ! empty( $response_params['token_name'] ) ) {
			$token_row = $this->aps_helper->find_token_row( $response_params['token_name'] );
			if ( isset( $token_row['token_id'] ) ) {
				$card_type = get_metadata( 'payment_token', $token_row['token_id'], 'card_type', true );
				if ( ! empty( $card_type ) ) {
					$command = $this->aps_config->get_command( $payment_method, null, strtoupper( $card_type ) );
				}
			}
		}
		$gateway_params = array(
			'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
			'access_code'         => $this->aps_config->get_access_code(),
			'merchant_reference'  => $order_id,
			'language'            => $this->aps_config->get_language(),
			'command'             => $command,
			'customer_ip'         => $this->aps_order->get_customer_ip(),
			'amount'              => $this->aps_helper->get_order_fort_amount( $this->aps_order->get_loaded_order(), $currency ),
			'currency'            => strtoupper( $currency ),
			'customer_email'      => $this->aps_order->get_email(),
			'return_url'          => create_wc_api_url( 'aps_online_response' ),
		);
		if ( APS_Constants::APS_PAYMENT_TYPE_VISA_CHECKOUT === $payment_method ) {
			$gateway_params['call_id']        = $response_params['visa_checkout_call_id'];
			$gateway_params['digital_wallet'] = APS_Constants::APS_COMMAND_VISA_CHECKOUT_WALLET;
		} else {
			$gateway_params['token_name'] = $response_params['token_name'];
			if ( isset( $response_params['card_security_code'] ) ) {
				$gateway_params['card_security_code'] = $response_params['card_security_code'];
			}
		}
		if ( APS_Constants::APS_PAYMENT_TYPE_INSTALLMENT === $payment_method && APS_Constants::APS_INTEGRATION_TYPE_STANDARD_CHECKOUT === $integration_type ) {
			$gateway_params['installments'] = 'YES';
			$gateway_params['plan_code']    = $response_params['plan_code'];
			$gateway_params['issuer_code']  = $response_params['issuer_code'];
			$gateway_params['command']      = 'PURCHASE';
		} elseif ( APS_Constants::APS_PAYMENT_TYPE_INSTALLMENT === $payment_method && APS_Constants::APS_INTEGRATION_TYPE_HOSTED_CHECKOUT === $integration_type ) {
			$gateway_params['installments'] = 'HOSTED';
			$gateway_params['plan_code']    = get_post_meta( $order_id, 'hosted_installment_plan_code', true );
			$gateway_params['issuer_code']  = get_post_meta( $order_id, 'hosted_installment_issuer_code', true );
			$gateway_params['command']      = 'PURCHASE';
		}

		// Card installment meta only applies to card payments (f22b6b9c).
		if ( APS_Constants::APS_PAYMENT_TYPE_CC === $payment_method && ! empty( $plan_code ) ) {
			$gateway_params['installments'] = 'HOSTED';
			$gateway_params['plan_code']    = get_post_meta( $order_id, 'hosted_cc_plan_code', true );
			$gateway_params['issuer_code']  = get_post_meta( $order_id, 'hosted_cc_issuer_code', true );
			$gateway_params['command']      = 'PURCHASE';
		}
		$customer_name = $this->aps_order->get_customer_name();
		if ( ! empty( $customer_name ) ) {
			$gateway_params['customer_name'] = $customer_name;
		}
		$gateway_params['eci'] = APS_Constants::APS_COMMAND_ECOMMERCE;
		if ( isset( $response_params['remember_me'] ) && ! isset( $response_params['card_security_code'] ) && APS_Constants::APS_PAYMENT_TYPE_VISA_CHECKOUT !== $payment_method ) {
			$gateway_params['remember_me'] = isset( $response_params['remember_me'] ) ? $response_params['remember_me'] : 'NO';
		}
		$plugin_params  = $this->aps_config->plugin_params();
		$gateway_params = array_merge( $gateway_params, $plugin_params );
		//generate request signature
		// Remember exactly what was asked for, so the response can be checked against it.
		$this->aps_order->record_expected_payment(
			isset( $gateway_params['amount'] ) ? $gateway_params['amount'] : '',
			isset( $gateway_params['currency'] ) ? $gateway_params['currency'] : ''
		);
		$signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
		$gateway_params['signature'] = $signature;
		$gateway_url                 = $this->aps_config->get_gateway_url( 'api' );
		$this->aps_helper->log( 'APS aps_notify request \n\n' . wp_json_encode( $this->aps_helper->redact_sensitive_params( $gateway_params ), true ) );
		$response = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
		$this->aps_helper->log( 'APS aps_notify response \n\n' . wp_json_encode( $response, true ) );
		return $response;
	}

	/**
	 * Renewal amount, converted from the renewal order currency into the
	 * currency the parent payment (and its token) was charged in.
	 *
	 * Returns null when no trustworthy rate exists, so the renewal fails
	 * closed instead of charging an unconverted figure (7d9080c3).
	 *
	 * @param WC_Order $renewal_order
	 * @param string   $currency
	 * @param float    $recurring_amount
	 * @return float|null
	 */
	private function get_renewal_fort_amount( $renewal_order, $currency, $recurring_amount ) {
		try {
			return $this->aps_helper->get_order_fort_amount( $renewal_order, $currency, $recurring_amount );
		} catch ( \Exception $e ) {
			$renewal_order->add_order_note( __( 'APS renewal not charged: no conversion rate from the order currency to the payment currency.', 'amazon-payment-services' ) );
			return null;
		}
	}

	/**
	 * Process subscription payment
	 */
	public function process_subscription_payment( $renewal_order, $recurring_amount ) {
		$payment_status = false;
		$this->aps_order->load_order( $renewal_order->get_id() );
		$renewal_order_id       = $renewal_order->get_id();
		$subscription_order_id  = get_post_meta( $renewal_order_id, '_subscription_renewal', true );
		$subscription_order_obj = get_post( $subscription_order_id );
		$parent_order_id        = $subscription_order_obj->post_parent;
		$aps_response           = get_post_meta( $parent_order_id, 'aps_payment_response', true );
		$currency               = $aps_response['currency'];
		$language               = $aps_response['language'];
		$fort_amount            = $this->get_renewal_fort_amount( $renewal_order, $currency, $recurring_amount );
		if ( null === $fort_amount ) {
			return false;
		}
		$gateway_params         = array(
			'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
			'access_code'         => $this->aps_config->get_access_code(),
			'merchant_reference'  => $renewal_order_id,
			'language'            => $language,
			'command'             => APS_Constants::APS_COMMAND_PURCHASE,
			'customer_ip'         => $this->aps_helper->get_customer_ip(),
			'amount'              => $fort_amount,
			'currency'            => strtoupper( $currency ),
			'customer_email'      => $this->aps_order->get_email(),
			'eci'                 => APS_Constants::APS_COMMAND_RECURRING,
			'token_name'          => $aps_response['token_name'],
			'return_url'          => create_wc_api_url( 'aps_online_response' ),
		);
		$customer_name          = $this->aps_order->get_customer_name();
		if ( ! empty( $customer_name ) ) {
			$gateway_params['customer_name'] = $customer_name;
		}
		// Remember exactly what was asked for, so the response can be checked against it.
		$this->aps_order->record_expected_payment(
			isset( $gateway_params['amount'] ) ? $gateway_params['amount'] : '',
			isset( $gateway_params['currency'] ) ? $gateway_params['currency'] : ''
		);
		$signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
		$gateway_params['signature'] = $signature;

		$gateway_url = $this->aps_config->get_gateway_url( 'api' );
		$this->aps_helper->log( 'APS recurring request \n\n' . wp_json_encode( $gateway_params, true ) );
		$response = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
		if ( APS_Constants::APS_PAYMENT_SUCCESS_RESPONSE_CODE === $response['response_code'] ) {
			// Only treat the renewal as paid when the response passed verification.
			$payment_status = (bool) $this->aps_order->success_order( $response, 'online' );
			if ( ! $payment_status ) {
				$this->aps_helper->log( 'APS recurring payment rejected during verification for order ' . $this->aps_order->get_order_id() );
			}
		} else {
			$result         = $this->aps_order->decline_order( $response, $response['response_message'] );
			$payment_status = false;
		}
		$this->aps_helper->log( 'APS recurring response \n\n' . wp_json_encode( $response, true ) );
		return $payment_status;
	}


    public function stc_process_subscription_payment( $renewal_order, $recurring_amount ) {
        $payment_status = false;
        $this->aps_order->load_order( $renewal_order->get_id() );
        $renewal_order_id       = $renewal_order->get_id();
        $subscription_order_id  = get_post_meta( $renewal_order_id, '_subscription_renewal', true );
        $subscription_order_obj = get_post( $subscription_order_id );
        $parent_order_id        = $subscription_order_obj->post_parent;
        $aps_response           = get_post_meta( $parent_order_id, 'aps_payment_response', true );
        $currency               = $aps_response['currency'];
        $language               = $aps_response['language'];
        $fort_amount            = $this->get_renewal_fort_amount( $renewal_order, $currency, $recurring_amount );
        if ( null === $fort_amount ) {
            return false;
        }
        $gateway_params         = array(
            'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
            'access_code'         => $this->aps_config->get_access_code(),
            'digital_wallet'      => APS_Constants::APS_PAYMENT_METHOD_STC_PAY,
            'merchant_reference'  => $this->aps_helper->generate_random_key(),
            'language'            => $language,
            'command'             => APS_Constants::APS_COMMAND_PURCHASE,
            'customer_ip'         => $this->aps_helper->get_customer_ip(),
            'amount'              => $fort_amount,
            'currency'            => strtoupper( $currency ),
            'customer_email'      => $this->aps_order->get_email(),
            'token_name'          => $aps_response['token_name'],
            'return_url'          => create_wc_api_url( 'aps_online_response' ),
        );
        $customer_name          = $this->aps_order->get_customer_name();
        if ( ! empty( $customer_name ) ) {
            $gateway_params['customer_name'] = $customer_name;
        }
        // Remember exactly what was asked for, so the response can be checked against it.
        $this->aps_order->record_expected_payment(
            isset( $gateway_params['amount'] ) ? $gateway_params['amount'] : '',
            isset( $gateway_params['currency'] ) ? $gateway_params['currency'] : ''
        );
        $signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
        $gateway_params['signature'] = $signature;

        $gateway_url = $this->aps_config->get_gateway_url( 'api' );
        $this->aps_helper->log( 'APS recurring request \n\n' . wp_json_encode( $gateway_params, true ) );
        $response = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
        if ( APS_Constants::APS_PAYMENT_SUCCESS_RESPONSE_CODE === $response['response_code'] ) {
            // Only treat the renewal as paid when the response passed verification.
            $payment_status = (bool) $this->aps_order->success_order( $response, 'online' );
            if ( ! $payment_status ) {
                $this->aps_helper->log( 'APS recurring payment rejected during verification for order ' . $this->aps_order->get_order_id() );
            }
        } else {
            $result         = $this->aps_order->decline_order( $response, $response['response_message'] );
            $payment_status = false;
        }
        $this->aps_helper->log( 'APS recurring response \n\n' . wp_json_encode( $response, true ) );
        return $payment_status;
    }
    public function tabby_process_subscription_payment( $renewal_order, $recurring_amount ) {
        $payment_status = false;
        $this->aps_order->load_order( $renewal_order->get_id() );
        $renewal_order_id       = $renewal_order->get_id();
        $subscription_order_id  = get_post_meta( $renewal_order_id, '_subscription_renewal', true );
        $subscription_order_obj = get_post( $subscription_order_id );
        $parent_order_id        = $subscription_order_obj->post_parent;
        $aps_response           = get_post_meta( $parent_order_id, 'aps_payment_response', true );
        $currency               = $aps_response['currency'];
        $language               = $aps_response['language'];
        $fort_amount            = $this->get_renewal_fort_amount( $renewal_order, $currency, $recurring_amount );
        if ( null === $fort_amount ) {
            return false;
        }
        $gateway_params         = array(
            'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
            'access_code'         => $this->aps_config->get_access_code(),
            'payment_option'      => APS_Constants::APS_PAYMENT_METHOD_TABBY,
            'merchant_reference'  => $this->aps_helper->generate_random_key(),
            'language'            => $language,
            'command'             => APS_Constants::APS_COMMAND_PURCHASE,
            'customer_ip'         => $this->aps_helper->get_customer_ip(),
            'amount'              => $fort_amount,
            'currency'            => strtoupper( $currency ),
            'customer_email'      => $this->aps_order->get_email(),
            'token_name'          => $aps_response['token_name'],
            'return_url'          => create_wc_api_url( 'aps_online_response' ),
        );
        $customer_name          = $this->aps_order->get_customer_name();
        if ( ! empty( $customer_name ) ) {
            $gateway_params['customer_name'] = $customer_name;
        }
        // Remember exactly what was asked for, so the response can be checked against it.
        $this->aps_order->record_expected_payment(
            isset( $gateway_params['amount'] ) ? $gateway_params['amount'] : '',
            isset( $gateway_params['currency'] ) ? $gateway_params['currency'] : ''
        );
        $signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
        $gateway_params['signature'] = $signature;

        $gateway_url = $this->aps_config->get_gateway_url( 'api' );
        $this->aps_helper->log( 'APS recurring request \n\n' . wp_json_encode( $gateway_params, true ) );
        $response = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
        if ( APS_Constants::APS_PAYMENT_SUCCESS_RESPONSE_CODE === $response['response_code'] ) {
            // Only treat the renewal as paid when the response passed verification.
            $payment_status = (bool) $this->aps_order->success_order( $response, 'online' );
            if ( ! $payment_status ) {
                $this->aps_helper->log( 'APS recurring payment rejected during verification for order ' . $this->aps_order->get_order_id() );
            }
        } else {
            $result         = $this->aps_order->decline_order( $response, $response['response_message'] );
            $payment_status = false;
        }
        $this->aps_helper->log( 'APS recurring response \n\n' . wp_json_encode( $response, true ) );
        return $payment_status;
    }


    /**
	 * APS Delete Token
	 */
	public function delete_aps_token( $token_id, $token ) {
		$gateway_params              = array(
			'service_command'     => 'UPDATE_TOKEN',
			'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
			'access_code'         => $this->aps_config->get_access_code(),
			'merchant_reference'  => $this->aps_helper->generate_random_key(),
			'language'            => $this->aps_config->get_language(),
			'token_name'          => $token->get_token(),
			'token_status'        => 'INACTIVE',
		);
		$signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
		$gateway_params['signature'] = $signature;
		$gateway_url                 = $this->aps_config->get_gateway_url( 'api' );
		$response                    = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
		$this->aps_helper->log( 'APS delete token \n\n' . wp_json_encode( $response, true ) );
	}

	/**
	 * Merchant page cancel
	 *
	 * @return void
	 */
	public function merchant_page_cancel( $apple_pay_cancel = false ) {
		$order_id = $this->aps_order->get_session_order_id();
		$this->aps_order->load_order( $order_id );

		if ( $order_id ) {
			$this->aps_order->cancelled_order();
			//display msg when cancel and not display on back button
			if ( ! $apple_pay_cancel ) {
				$this->aps_helper->set_flash_msg( __( 'Transaction Cancelled', 'amazon-payment-services' ), APS_Constants::APS_FLASH_MESSAGE_ERROR );
			}
		}
		//display msg when cancel from all page apple pay
		if ( $apple_pay_cancel ) {
			$this->aps_helper->set_flash_msg( __( 'Transaction Cancelled', 'amazon-payment-services' ), APS_Constants::APS_FLASH_MESSAGE_ERROR );
		}
	}

	/**
	 * Init apple pay payment
	 */
	public function init_apple_pay_payment( $response_params ) {
		$status   = 'success';
		$order_id = 0;
		try {
			$order_id = $this->aps_order->get_session_order_id();
			$this->aps_order->load_order( $order_id );
            $fort_currency              = $this->aps_helper->get_order_fort_currency( $this->aps_order->get_loaded_order() );
            $order_currency             = strtoupper( (string) $this->aps_order->get_currency() );
            $currency_conversion_rate   = $this->aps_helper->get_order_conversion_rate( $this->aps_order->get_loaded_order(), $fort_currency );
            if ( null === $currency_conversion_rate ) {
                throw new \Exception( __( 'This order cannot be paid in the configured payment currency. Please contact the store.', 'amazon-payment-services' ) );
            }

			$gateway_params = array(
				'digital_wallet'      => 'APPLE_PAY',
				'command'             => $this->aps_config->get_command( APS_Constants::APS_PAYMENT_TYPE_APPLE_PAY ),
				'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
				'access_code'         => $this->aps_config->get_apple_pay_access_code(),
				'merchant_reference'  => $order_id,
				'language'            => $this->aps_config->get_language(),
				'amount'              => $this->aps_helper->convert_to_base_currency( $this->aps_order->get_total(), $currency_conversion_rate ),
				'currency'            => strtoupper( $fort_currency ),
				'customer_email'      => $this->aps_order->get_email(),
				'apple_data'          => $response_params->data->paymentData->data,
				'apple_signature'     => $response_params->data->paymentData->signature,
				'customer_ip'         => $this->aps_order->get_customer_ip(),
			);
			foreach ( $response_params->data->paymentData->header as $key => $value ) {
				$gateway_params['apple_header'][ 'apple_' . $key ] = $value;
			}
			foreach ( $response_params->data->paymentMethod as $key => $value ) {
				$gateway_params['apple_paymentMethod'][ 'apple_' . $key ] = $value;
			}
			// Remember exactly what was asked for, so the response can be checked against it.
			$this->aps_order->record_expected_payment(
				isset( $gateway_params['amount'] ) ? $gateway_params['amount'] : '',
				isset( $gateway_params['currency'] ) ? $gateway_params['currency'] : ''
			);
			$signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request', 'apple_pay' );
			$gateway_params['signature'] = $signature;
			$gateway_url                 = $this->aps_config->get_gateway_url( 'api' );
			$this->aps_helper->log( 'Apple payment request ' . json_encode( $gateway_params ) );
			$response = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
			$this->aps_helper->log( 'Apple payment response ' . json_encode( $response ) );
			if ( APS_Constants::APS_PAYMENT_SUCCESS_RESPONSE_CODE === $response['response_code'] || APS_Constants::APS_PAYMENT_AUTHORIZATION_SUCCESS_RESPONSE_CODE === $response['response_code'] ) {
				if ( ! $this->aps_order->success_order( $response, 'online' ) ) {
					$this->aps_helper->log( 'APS apple pay response failed verification for order ' . $order_id . '; order held for review.' );
					throw new Exception( __( 'We could not verify this payment. Please contact the store.', 'amazon-payment-services' ) );
				}
				$status = 'success';
			} elseif ( in_array( $response['response_code'], APS_Constants::APS_ONHOLD_RESPONSE_CODES, true ) ) {
				$this->aps_order->on_hold_order( $response['response_message'] );
				$aps_error_log = "APS apple pay on hold stage : \n\n" . wp_json_encode( $response, true );
				$this->aps_helper->log( $aps_error_log );
				$status = 'success';
			} else {
				$result = $this->aps_order->decline_order( $response, $response['response_message'] );
				$status = 'error';
				if ( $result ) {
					throw new Exception( $response['response_message'] );
				}
			}
		} catch ( \Exception $e ) {
			session_start();
			$status                = 'error';
			$_SESSION['aps_error'] = wp_kses_data($e->getMessage());
			session_write_close();
		}
		return array(
			'status'   => $status,
			'order_id' => $order_id,
		);
	}

	/***************************************** Valu Payment Gateway Functions *************************/

	/**
	 * Get Valu Products array
	 *
	 * @return array
	 */
	private function get_valu_products_data() {
		$products      = array();
		$product_name  = '';
		$category_name = '';
		$order_id      = $this->aps_order->get_session_order_id();
		$order         = $this->aps_order->get_order_by_id( $order_id );
		$items         = $order->get_items();
		$currency      = strtoupper( (string) $order->get_currency() );
		foreach ( $items as $item ) {
			$product_name       = $this->aps_helper->clean_string( $item->get_name() );
			$product_id         = $item->get_product_id();
			$product_categories = get_the_terms( $product_id, 'product_cat' );
			foreach ( $product_categories as $product_category ) {
				$category_name = $this->aps_helper->clean_string( $product_category->name );
				break;
			}
			break;
		}
		if ( count( $items ) > 1 ) {
			$products[] = array(
				'product_name'     => 'MutipleProducts',
				'product_price'    => $this->aps_helper->get_order_fort_amount( $order, $currency ),
				'product_category' => $category_name,
			);
		} else {
			$products[] = array(
				'product_name'     => $product_name,
				'product_price'    => $this->aps_helper->get_order_fort_amount( $order, $currency ),
				'product_category' => $category_name,
			);
		}
		return $products;
	}

	/**
	 * Valu verify customer
	 *
	 * @return array
	 */
	public function valu_verify_customer( $mobile_number, $down_payment, $tou, $cash_back ) {
		$status  = 'success';
		$message = 'Customer verified';
		try {
			// f22b6b9c: only integer minor units (piastres) that are zero or more may enter the session.
			foreach ( array( $down_payment, $tou, $cash_back ) as $amount ) {
				if ( ! is_int( $amount ) || $amount < 0 ) {
					throw new \Exception( __( 'Incorrect valU amount', 'amazon-payment-services' ) );
				}
			}
			unset( WC()->session->order_awaiting_payment );
			$reference_id                = $this->aps_helper->generate_random_key();
			$gateway_params              = array(
				'service_command'     => 'CUSTOMER_VERIFY',
				'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
				'access_code'         => $this->aps_config->get_access_code(),
				'merchant_reference'  => $reference_id,
				'language'            => $this->aps_config->get_language(),
				'payment_option'      => 'VALU',
				'phone_number'        => $mobile_number,
			);
			$signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
			$gateway_params['signature'] = $signature;
			//execute post
			$gateway_url = $this->aps_config->get_gateway_url( 'api' );
			$result      = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
			$this->aps_helper->log( 'Valu verfiy customer ' . json_encode( $result ) );
			$valuapi_stop_message = __( 'VALU API failed. Please try again later', 'amazon-payment-services' );
			if ( isset( $result['status'] ) && APS_Constants::APS_VALU_CUSTOMER_VERIFY_SUCCESS_RESPONSE_CODE === $result['response_code'] ) {
				// Start a fresh valU session tied to this shopper's WooCommerce session, so
				// values left over from an earlier attempt or another browser cannot be reused.
				$binding = bin2hex( random_bytes( 16 ) );
				session_start();
				$_SESSION['valu_payment'] = array(
					'reference_id'  => wp_kses_data( $reference_id ),
					'mobile_number' => wp_kses_data( $mobile_number ),
					'down_payment'  => $down_payment,
					'tou'           => $tou,
					'cash_back'     => $cash_back,
					'binding'       => $binding,
					'user_id'       => (int) get_current_user_id(),
					'cart_hash'     => $this->current_cart_hash(),
				);
				session_write_close();
				if ( function_exists( 'WC' ) && WC()->session ) {
					WC()->session->set( 'aps_valu_binding', $binding );
				}
			} elseif ( isset( $result['response_code'] ) && APS_Constants::APS_VALU_CUSTOMER_VERIFY_FAILED_RESPONSE_CODE === $result['response_code'] ) {
				$status  = 'error';
				$message = isset( $result['response_message'] ) && ! empty( $result['response_message'] ) ? 'Customer does not exist.' : $valuapi_stop_message;
				session_start();
				unset( $_SESSION['valu_payment'] );
				session_write_close();
			} else {
				$status  = 'error';
				$message = isset( $result['response_message'] ) && ! empty( $result['response_message'] ) ? $result['response_message'] : $valuapi_stop_message;
				session_start();
				unset( $_SESSION['valu_payment'] );
				session_write_close();
			}
		} catch ( \Exception $e ) {
			$status  = 'error';
			$message = $e->getMessage();
		}
		$response_arr = array(
			'status'  => $status,
			'message' => $message,
		);
		return $response_arr;
	}

	/**
	 * Check the valU amounts held in the PHP session before they are used or stored.
	 *
	 * The down payment, ToU and cashback reach the session from a shopper request, so
	 * they are checked again here (f22b6b9c): they must be whole, non-negative minor
	 * units, belong to this shopper's WooCommerce session, user and unchanged cart, and,
	 * once an order exists, belong to that EGP order and its customer, match the order
	 * total recorded at OTP generation and not exceed it.
	 *
	 * @param mixed         $valu          Contents of $_SESSION['valu_payment'].
	 * @param WC_Order|null $order         Order being paid, when known.
	 * @param bool          $require_order True when the session must already be tied to $order.
	 * @return array|false Amounts in minor units keyed down_payment, tou, cash_back; false when invalid.
	 */
	public function validate_valu_session( $valu, $order = null, $require_order = false ) {
		if ( ! is_array( $valu ) ) {
			return false;
		}
		$binding = ( function_exists( 'WC' ) && WC()->session ) ? (string) WC()->session->get( 'aps_valu_binding' ) : '';
		if ( '' === $binding || empty( $valu['binding'] ) || ! hash_equals( $binding, (string) $valu['binding'] ) ) {
			return false;
		}
		// A guest who logs in, or a different account on the same browser, starts again.
		if ( ! isset( $valu['user_id'] ) || (int) $valu['user_id'] !== (int) get_current_user_id() ) {
			return false;
		}
		$cart_hash = $this->current_cart_hash();
		if ( empty( $valu['cart_hash'] ) || '' === $cart_hash || ! hash_equals( (string) $valu['cart_hash'], $cart_hash ) ) {
			return false;
		}
		$amounts = array();
		foreach ( array( 'down_payment', 'tou', 'cash_back' ) as $key ) {
			$value = isset( $valu[ $key ] ) ? $valu[ $key ] : 0;
			if ( ! ( is_int( $value ) && $value >= 0 ) && ! ( is_string( $value ) && ctype_digit( $value ) ) ) {
				return false;
			}
			$amounts[ $key ] = (int) $value;
		}
		if ( ! $order ) {
			return $require_order ? false : $amounts;
		}
		if ( isset( $valu['order_id'] ) ? (int) $valu['order_id'] !== (int) $order->get_id() : $require_order ) {
			return false;
		}
		if ( (int) $order->get_customer_id() !== (int) get_current_user_id() ) {
			return false;
		}
		$order_cart_hash = (string) $order->get_cart_hash();
		if ( '' !== $order_cart_hash && ! hash_equals( (string) $valu['cart_hash'], $order_cart_hash ) ) {
			return false;
		}
		$currency = strtoupper( (string) $order->get_currency() );
		if ( 'EGP' !== $currency ) {
			return false;
		}
		try {
			$total = (int) round( $this->aps_helper->get_order_fort_amount( $order, $currency ) );
		} catch ( \Exception $e ) {
			return false;
		}
		if ( isset( $valu['order_total'] ) && (int) $valu['order_total'] !== $total ) {
			return false;
		}
		if ( $require_order && ! isset( $valu['order_total'] ) ) {
			return false;
		}
		if ( array_sum( $amounts ) > $total ) {
			return false;
		}
		return $amounts;
	}

	/**
	 * Down payment to send to valU, in piastres.
	 *
	 * When the shopper left the field empty the configured default applies. That default
	 * is entered by the merchant in EGP (it pre-fills the shopper's EGP field), so it is
	 * parsed and capped like a shopper value instead of being sent as raw piastres.
	 *
	 * @param int      $down_payment Shopper down payment in piastres.
	 * @param int|null $max_minor    Inclusive upper bound in piastres.
	 * @return int|false
	 */
	public function resolve_valu_down_payment( $down_payment, $max_minor = null ) {
		if ( 'yes' !== $this->aps_config->get_enable_valu_down_payment() ) {
			return 0;
		}
		if ( ! is_int( $down_payment ) || $down_payment < 0 ) {
			return false;
		}
		if ( $down_payment > 0 ) {
			return ( null !== $max_minor && $down_payment > $max_minor ) ? false : $down_payment;
		}
		$default = trim( (string) $this->aps_config->get_valu_down_payment_value() );
		if ( '' === $default ) {
			return 0;
		}
		return $this->aps_helper->parse_minor_amount( $default, 'EGP', $max_minor );
	}

	/**
	 * @return string Hash of the current cart, or '' when there is none.
	 */
	private function current_cart_hash() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
			return '';
		}
		return (string) WC()->cart->get_cart_hash();
	}

	/**
	 * Tenures APS offered for this valU transaction, keyed by number of installments.
	 *
	 * @param array $result OTP_GENERATE / OTP_VERIFY response.
	 * @return array
	 */
	private function extract_valu_tenures( $result ) {
		$tenures = array();
		if ( isset( $result['installment_detail']['plan_details'] ) && is_array( $result['installment_detail']['plan_details'] ) ) {
			foreach ( array_slice( $result['installment_detail']['plan_details'], 0, 48 ) as $ten ) {
				// f22b6b9c: APS sends piastres; only plain non-negative integers are accepted.
				$count    = is_array( $ten ) && isset( $ten['number_of_installments'] ) ? $this->aps_helper->parse_installment_count( $ten['number_of_installments'] ) : false;
				$amount   = is_array( $ten ) && isset( $ten['amount_per_month'] ) ? $this->parse_piastres( $ten['amount_per_month'] ) : false;
				$interest = is_array( $ten ) && isset( $ten['fees_amount'] ) ? $this->parse_piastres( $ten['fees_amount'] ) : false;
				if ( false === $count || false === $amount || $amount <= 0 || false === $interest || isset( $tenures[ (string) $count ] ) ) {
					continue;
				}
				$tenures[ (string) $count ] = array(
					'amount'          => $this->aps_helper->format_minor_amount( $amount, 'EGP' ),
					'interest'        => $this->aps_helper->format_minor_amount( $interest, 'EGP' ),
					'amount_minor'    => $amount,
					'interest_minor'  => $interest,
				);
			}
		}
		return $tenures;
	}

	/**
	 * @param mixed $raw
	 * @return int|false
	 */
	private function parse_piastres( $raw ) {
		if ( ! ( is_int( $raw ) || is_string( $raw ) ) || ! preg_match( '/^\d{1,12}$/', (string) $raw ) ) {
			return false;
		}
		return (int) $raw;
	}

	/**
	 * Tenure choices for the valU wizard, from the validated tenure list only.
	 *
	 * @param array $tenures extract_valu_tenures() result.
	 * @return string
	 */
	private function render_valu_tenure_html( $tenures ) {
		$html = "<div class='tenure_carousel'>";
		foreach ( $tenures as $count => $ten ) {
			$html .= '<div class="slide">
								<div class="tenureBox" data-tenure="' . esc_attr( $count ) . '" data-tenure-amount="' . esc_attr( $ten['amount'] ) . '" data-tenure-interest="' . esc_attr( $ten['interest'] ) . '" >
									<p class="tenure">' . esc_html( $count ) . ' {months_txt}</p>
									<p class="emi"><strong>' . esc_html( $ten['amount'] ) . '</strong> EGP/{month_txt}</p>
									<p class="int_rate"> {interest_txt}' . esc_html( $ten['interest'] ) . '</p>
								</div>
							</div>';
		}
		return $html . '</div>';
	}

	/**
	 * Look up a submitted valU tenure in the tenures APS offered this shopper.
	 *
	 * @param mixed  $valu   Contents of $_SESSION['valu_payment'].
	 * @param string $tenure Submitted number of installments.
	 * @return array|false tenure, amount and interest from the server-side list; false when not offered.
	 */
	public function get_valu_tenure( $valu, $tenure ) {
		$tenure = (string) $tenure;
		if ( ! preg_match( '/^[1-9]\d?$/', $tenure ) || ! is_array( $valu ) || empty( $valu['tenures'][ $tenure ] )
			|| ! isset( $valu['tenures'][ $tenure ]['amount_minor'], $valu['tenures'][ $tenure ]['interest_minor'] ) ) {
			return false;
		}
		return array( 'tenure' => $tenure ) + $valu['tenures'][ $tenure ];
	}

	/**
	 * Valu generate OTP
	 *
	 * @return array
	 */
	public function valu_generate_otp( $reference_id, $mobile_number, $order_id, $down_payment, $tou, $cash_back) {
		$status               = 'success';
		$message              = 'OTP Generated';
		$mobile_number_string = null;
		$tenure_html = '';

		try {
			$this->aps_order->load_order( $order_id );
			$products                    = $this->get_valu_products_data();
			$currency                    = strtoupper( (string) $this->aps_order->get_currency() );
			// f22b6b9c: the order total is remembered so purchase can tell if the order changed.
			$order_total                 = (int) round( $this->aps_helper->get_order_fort_amount( $this->aps_order->get_loaded_order(), $currency ) );
			$down_payment                = $this->resolve_valu_down_payment( $down_payment, $order_total );
			if ( false === $down_payment || 'EGP' !== $currency || $down_payment + $tou + $cash_back > $order_total ) {
				throw new \Exception( __( 'Your valU details could not be verified. Please verify your mobile number again.', 'amazon-payment-services' ) );
			}
			session_start();
			$valu_session = isset( $_SESSION['valu_payment'] ) ? $_SESSION['valu_payment'] : null;
			session_write_close();
			if ( false === $this->validate_valu_session( $valu_session, $this->aps_order->get_loaded_order() ) ) {
				throw new \Exception( 'Invalid valU checkout.' );
			}
			$gateway_params              = array(
				'service_command'     => 'OTP_GENERATE',
				'access_code'         => $this->aps_config->get_access_code(),
				'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
				'merchant_reference'  => $reference_id,
				'language'            => $this->aps_config->get_language(),
				'payment_option'      => 'VALU',
				'merchant_order_id'   => $order_id,
				'phone_number'        => $mobile_number,
				'amount'              => $order_total,
				'currency'            => $currency,
				'products'            => $products,
				'total_downpayment'	  => $down_payment,
                'wallet_amount'       => intval($tou),
                'cashback_wallet_amount' => intval($cash_back),
				'include_installments' => 'YES'
			);
			$signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
			$gateway_params['signature'] = $signature;
			//execute post
			$gateway_url = $this->aps_config->get_gateway_url( 'api' );
			$result      = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
			$this->aps_helper->log( 'Valu generate otp ' . json_encode( $result ) );
			$valuapi_stop_message = __( 'VALU API failed. Please try again later', 'amazon-payment-services' );
			if ( isset( $result['response_code'] ) && APS_Constants::APS_VALU_OTP_GENERATE_SUCCESS_RESPONSE_CODE === $result['response_code'] ) {
				$status                                     = 'success';
				$mobile_number_string                       = APS_Constants::APS_VALU_EG_COUNTRY_CODE . $mobile_number;
				session_start();
				$_SESSION['valu_payment']['order_id']       = wp_kses_data($order_id);
				$_SESSION['valu_payment']['transaction_id'] = wp_kses_data($result['merchant_order_id']);
				$_SESSION['valu_payment']['tenures']        = $this->extract_valu_tenures( $result );
				$_SESSION['valu_payment']['order_total']    = $order_total;
				session_write_close();
			} else {
				$status  = 'genotp_error';
				$message = isset( $result['response_message'] ) && ! empty( $result['response_message'] ) ? $result['response_message'] : $valuapi_stop_message;
				session_start();
				unset( $_SESSION['valu_payment'] );
				session_write_close();
			}
			if ( isset( $result['response_code'] ) && APS_Constants::APS_VALU_OTP_GENERATE_SUCCESS_RESPONSE_CODE === $result['response_code'] ) {
				$status                          = 'success';
				$message                         = __( 'OTP Verified successfully', 'amazon-payment-services' );
				$tenure_html                     = $this->render_valu_tenure_html( $this->extract_valu_tenures( $result ) );
			} else {
				$status  = 'error';
				$message = isset( $result['response_message'] ) && ! empty( $result['response_message'] ) ? $result['response_message'] : $valuapi_stop_message;
			}
		} catch ( \Exception $e ) {
			$status  = 'error';
			$message = $e->getMessage();
		}
		$response_arr = array(
			'status'               => $status,
			'message'              => $message,
			'mobile_number_string' => $mobile_number_string,
			'tenure_html' => $tenure_html,
		);
		return $response_arr;
	}

	/**
	 * Valu verify OTP
	 *
	 * @return array
	 */
	public function valu_verfiy_otp( $otp ) {
		$status      = '';
		$message     = '';
		$tenure_html = '';
		try {
			session_start();
			$valu_session = isset( $_SESSION['valu_payment'] ) ? $_SESSION['valu_payment'] : null;
			session_write_close();
			// f22b6b9c: the order comes from this shopper's WooCommerce session only, and the
			// valU session must belong to that shopper, cart and order.
			$order_id = $this->aps_order->get_session_order_id();
			$order    = empty( $order_id ) ? false : wc_get_order( $order_id );
			if ( ! $order || false === $this->validate_valu_session( $valu_session, $order, true ) ) {
				throw new \Exception( __( 'Your valU details could not be verified. Please verify your mobile number again.', 'amazon-payment-services' ) );
			}
			$this->aps_order->load_order( $order_id );
			$reference_id  = wp_kses_data( isset( $valu_session['reference_id'] ) ? $valu_session['reference_id'] : '' );
			$mobile_number = wp_kses_data( isset( $valu_session['mobile_number'] ) ? $valu_session['mobile_number'] : '' );
			$currency      = strtoupper( (string) $this->aps_order->get_currency() );

			$gateway_params              = array(
				'service_command'     => 'OTP_VERIFY',
				'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
				'access_code'         => $this->aps_config->get_access_code(),
				'merchant_reference'  => $reference_id,
				'language'            => $this->aps_config->get_language(),
				'payment_option'      => 'VALU',
				'phone_number'        => $mobile_number,
				'amount'              => $this->aps_helper->get_order_fort_amount( $this->aps_order->get_loaded_order(), $currency ),
				'merchant_order_id'   => $order_id,
				'currency'            => $currency,
				'otp'                 => $otp,
				'total_downpayment'   => 0,
			);
			$signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
			$gateway_params['signature'] = $signature;
			//execute post
			$gateway_url          = $this->aps_config->get_gateway_url( 'api' );
			$result               = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
			$this->aps_helper->log( 'Valu verify otp ' . json_encode( $result ) );

			$valuapi_stop_message = __( 'VALU API failed. Please try again later', 'amazon-payment-services' );
			if ( isset( $result['response_code'] ) && APS_Constants::APS_VALU_OTP_VERIFY_SUCCESS_RESPONSE_CODE === $result['response_code'] ) {
				$status  = 'success';
				$message = __( 'OTP Verified successfully', 'amazon-payment-services' );
				if ( isset( $result['installment_detail']['plan_details'] ) ) {
					session_start();
					if ( isset( $_SESSION['valu_payment'] ) && is_array( $_SESSION['valu_payment'] ) ) {
						$_SESSION['valu_payment']['tenures'] = $this->extract_valu_tenures( $result );
					}
					session_write_close();
					$tenure_html = $this->render_valu_tenure_html( $this->extract_valu_tenures( $result ) );
				}
			} else {
				$status  = 'error';
				$message = isset( $result['response_message'] ) && ! empty( $result['response_message'] ) ? $result['response_message'] : $valuapi_stop_message;
				throw new \Exception( $message );
			}
		} catch ( \Exception $e ) {
			$status  = 'error';
			$message = $e->getMessage();
		}
		return array(
			'status'      => $status,
			'message'     => $message,
			'tenure_html' => $tenure_html,
		);
	}

	/**
	 * Valu generate OTP
	 *
	 * @return array
	 */
	public function valu_execute_purchase( $order_id, $active_tenure, $otp ) {
		$status  = 'success';
		$message = '';
		$result  = array();
		$valuapi_stop_message = __( 'VALU API failed. Please contact the store before retrying.', 'amazon-payment-services' );
		try {
			// f22b6b9c: charge only the order the gateway was asked to pay, and only when it is
			// the order awaiting payment that the valU session was opened for.
			if ( (int) $order_id <= 0 || (int) $order_id !== (int) $this->aps_order->get_session_order_id() ) {
				throw new \Exception( __( 'Your valU details could not be verified. Please verify your mobile number again.', 'amazon-payment-services' ) );
			}
			$this->aps_order->load_order( $order_id );
			session_start();
			$valu_session                = isset( $_SESSION['valu_payment'] ) ? $_SESSION['valu_payment'] : null;
			session_write_close();
			// f22b6b9c: check the session amounts again, and that they belong to this order and shopper.
			$amounts = $this->validate_valu_session( $valu_session, $this->aps_order->get_loaded_order(), true );
			if ( false === $amounts ) {
				throw new \Exception( __( 'Your valU details could not be verified. Please verify your mobile number again.', 'amazon-payment-services' ) );
			}
			$reference_id                = wp_kses_data( isset( $valu_session['reference_id'] ) ? $valu_session['reference_id'] : '' );
			$mobile_number               = wp_kses_data( isset( $valu_session['mobile_number'] ) ? $valu_session['mobile_number'] : '' );
			$total_down_payment          = $amounts['down_payment'];
			$tou                         = $amounts['tou'];
			$cash_back                   = $amounts['cash_back'];
			$otp                         = wp_kses_data($otp);
			$customer_email              = $this->aps_order->get_email();
			$customer_code               = $mobile_number;
			$currency                    = strtoupper( (string) $this->aps_order->get_currency() );
			$transaction_id              = wp_kses_data( isset( $valu_session['transaction_id'] ) ? $valu_session['transaction_id'] : '' );
			$total_down_payment          = $this->resolve_valu_down_payment( $total_down_payment, (int) $valu_session['order_total'] );
			if ( false === $total_down_payment ) {
				throw new \Exception( __( 'Your valU details could not be verified. Please verify your mobile number again.', 'amazon-payment-services' ) );
			}
			$gateway_params              = array(
				'command'              => 'PURCHASE',
				'merchant_identifier'  => $this->aps_config->get_merchant_identifier(),
				'access_code'          => $this->aps_config->get_access_code(),
				'merchant_reference'   => $reference_id,
				'language'             => $this->aps_config->get_language(),
				'payment_option'       => 'VALU',
				'phone_number'         => $mobile_number,
				'amount'               => $this->aps_helper->get_order_fort_amount( $this->aps_order->get_loaded_order(), $currency ),
				'merchant_order_id'    => $order_id,
				'currency'             => strtoupper( $currency ),
				'otp'                  => $otp,
				'tenure'               => $active_tenure,
				'total_down_payment'   => $total_down_payment,
                'wallet_amount'       => intval($tou),
                'cashback_wallet_amount' => intval($cash_back),
				'customer_code'        => $customer_code,
				'customer_email'       => $customer_email,
				'purchase_description' => 'Order' . $order_id,
				'transaction_id'       => $transaction_id,
			);
			
			if($otp === "" || empty($otp)){
				
				$message = __( 'Please provide the OTP', 'amazon-payment-services' );
				throw new \Exception( $message );
			}
            if($total_down_payment + $tou + $cash_back > $this->aps_helper->get_order_fort_amount( $this->aps_order->get_loaded_order(), $currency )){
            	
                $message = __( 'Cashback, Downpayment, and ToU amounts sum cannot be greater than Cart Total', 'amazon-payment-services' );
                throw new \Exception( $message );
            }
			
			//$plugin_params               = $this->aps_config->plugin_params();
			//$gateway_params              = array_merge( $gateway_params, $plugin_params );
			// Remember exactly what was asked for, so the response can be checked against it.
			$this->aps_order->record_expected_payment(
				isset( $gateway_params['amount'] ) ? $gateway_params['amount'] : '',
				isset( $gateway_params['currency'] ) ? $gateway_params['currency'] : ''
			);
			$signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
			$gateway_params['signature'] = $signature;
			//execute post
			$gateway_url = $this->aps_config->get_gateway_url( 'api' );
			$result      = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
			$this->aps_helper->log( 'Valu execute purchase ' . json_encode( $result ) );
			if ( isset( $result['response_code'] ) && APS_Constants::APS_PAYMENT_SUCCESS_RESPONSE_CODE === $result['response_code'] ) {
				if ( ! $this->aps_order->success_order( $result, 'online' ) ) {
					$this->aps_helper->log( 'APS valU response failed verification for order ' . $this->aps_order->get_order_id() . '; order held for review.' );
					throw new \Exception( __( 'We could not verify this payment. Please contact the store.', 'amazon-payment-services' ) );
				}
				$status  = 'success';
				$message = __( 'Transaction Verified successfully', 'amazon-payment-services' );
			} elseif ( isset( $result['response_code'] ) && in_array( $result['response_code'], APS_Constants::APS_ONHOLD_RESPONSE_CODES, true ) ){
				$this->aps_order->on_hold_order( $result['response_message'] );
				$aps_error_log = "APS valU on hold stage : \n\n" . wp_json_encode( $result, true );
				$this->aps_helper->log( $aps_error_log );
				$status = 'success';
				$message = __( 'The transaction has been processed, but failed to receive confirmation. Please contact to verify', 'amazon-payment-services' );
			} else {
				$status  = 'error';
				$message = isset( $result['response_message'] ) && ! empty( $result['response_message'] ) ? $result['response_message'] : $valuapi_stop_message;
				$this->aps_order->decline_order( $result, $message );
				throw new \Exception( $message );
			}
			session_start();
			unset( $_SESSION['valu_payment'] );
			session_write_close();
		} catch ( \Exception $e ) {
			$status  = 'error';
			$message = $e->getMessage();
		}
		return array(
			'status'  => $status,
			'message' => $message,
            'valu_transaction_id' => isset( $result['valu_transaction_id'] ) ? $result['valu_transaction_id'] : '',
            'loan_number' => isset( $result['loan_number'] ) ? $result['loan_number'] : '',
		);
	}

    /***************************************** STC Pay Payment Gateway Functions *************************/

    /**
     * STC Pay generate OTP
     *
     * @return array
     */
    public function stc_pay_generate_otp(  $mobile_number, $order_id ) {
        $status               = 'success';
        $message              = 'OTP Generated';
        $mobile_number_string = null;
        try {
            // Enforce the Saudi mobile format STC Pay expects before requesting an OTP.
            if ( ! preg_match( '/^0?5[0-9]{8}$/', (string) $mobile_number ) ) {
                throw new \Exception( __( 'Please enter a valid Saudi mobile number.', 'amazon-payment-services' ) );
            }

            $reference_id = $this->aps_helper->generate_random_key();
            $this->aps_order->load_order( $order_id );
            $currency                    = strtoupper( (string) $this->aps_order->get_currency() );
            $gateway_params              = array(
                'service_command'     => 'GENERATE_OTP',
                'access_code'         => $this->aps_config->get_access_code(),
                'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
                'merchant_reference'  => $reference_id,
                'language'            => $this->aps_config->get_language(),
                'digital_wallet'      => 'STCPAY',
                'phone_number'        => $mobile_number,
                'amount'              => $this->aps_helper->get_order_fort_amount( $this->aps_order->get_loaded_order(), $currency ),
                'currency'            => $currency,
            );
            $signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
            $gateway_params['signature'] = $signature;
            //execute post
            $gateway_url = $this->aps_config->get_gateway_url( 'api' );
            $result      = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
            $this->aps_helper->log( 'STC Pay generate otp ' . json_encode( $result ) );
            update_post_meta( $order_id, 'stc_pay_reference_id', $reference_id );
            $stc_pay_api_error_message = __( 'STC PAY API failed. Please try again later', 'amazon-payment-services' );
            if ( isset( $result['response_code'] ) && APS_Constants::APS_STC_PAY_OTP_GENERATE_SUCCESS_RESPONSE_CODE === $result['response_code'] ) {
                $status                                     = 'success';
                $mobile_number_string                       = APS_Constants::APS_STC_PAY_SAR_COUNTRY_CODE . $mobile_number;
                session_start();
                $_SESSION['stc_pay_payment']['reference_id']       = wp_kses_data($reference_id);
                $_SESSION['stc_pay_payment']['mobile_number']       = wp_kses_data($mobile_number);
                $_SESSION['stc_pay_payment']['order_id']       = wp_kses_data($order_id);
                session_write_close();
            } else {
                $status  = 'genotp_error';
                $message = isset( $result['response_message'] ) && ! empty( $result['response_message'] ) ? $result['response_message'] : $stc_pay_api_error_message;
                session_start();
                unset( $_SESSION['stc_pay_payment'] );
                session_write_close();
            }
        } catch ( \Exception $e ) {
            $status  = 'error';
            $message = $e->getMessage();
        }
        $response_arr = array(
            'status'               => $status,
            'message'              => $message,
            'mobile_number_string' => $mobile_number_string,
        );
        return $response_arr;
    }

    /**
     * STC Pay generate OTP
     *
     * @return array
     */
    public function stc_pay_execute_purchase( $otp, $remember_me, $token_name ) {
        $status  = 'success';
        $message = '';
        try {
            // FIX: Defense-in-depth validation that the STC Pay token belongs to the current user.
            // This is a secondary check in case the gateway class validation is bypassed.
            if ( ! empty( $token_name ) ) {
                $current_user_id = get_current_user_id();
                if ( 0 === $current_user_id ) {
                    throw new \Exception( __( 'You must be logged in to use a saved payment token.', 'amazon-payment-services' ) );
                }
                $user_tokens = WC_Payment_Tokens::get_customer_tokens( $current_user_id, APS_Constants::APS_PAYMENT_TYPE_STC_PAY );
                $token_is_valid = false;
                foreach ( $user_tokens as $user_token ) {
                    if ( $user_token->get_token() === $token_name ) {
                        $token_is_valid = true;
                        break;
                    }
                }
                if ( ! $token_is_valid ) {
                    $this->aps_helper->log( 'SECURITY: STC Pay token ownership validation failed for user ' . $current_user_id . ' with token: ' . $token_name );
                    throw new \Exception( __( 'Invalid payment token. The token does not belong to your account.', 'amazon-payment-services' ) );
                }
            }

            $order_id = $this->aps_order->get_session_order_id();
            $this->aps_order->load_order( $order_id );
            session_start();
            $reference_id                = wp_kses_data($_SESSION['stc_pay_payment']['reference_id']);
            $mobile_number               = wp_kses_data($_SESSION['stc_pay_payment']['mobile_number']);
            session_write_close();
            $customer_email              = $this->aps_order->get_email();
            $customer_name               = $this->aps_order->get_customer_name();
            $currency                    = strtoupper( (string) $this->aps_order->get_currency() );

            $gateway_params              = array(
                'command'              => 'PURCHASE',
                'merchant_identifier'  => $this->aps_config->get_merchant_identifier(),
                'access_code'          => $this->aps_config->get_access_code(),
                'language'             => $this->aps_config->get_language(),
                'digital_wallet'       => 'STCPAY',
                'phone_number'         => $mobile_number,
                'amount'               => $this->aps_helper->get_order_fort_amount( $this->aps_order->get_loaded_order(), $currency ),
                'merchant_order_id'    => $order_id,
                'currency'             => strtoupper( $currency ),
                'customer_name'        => $customer_name,
                'customer_ip'          => $this->aps_order->get_customer_ip(),
                'customer_email'       => $customer_email,
                'order_description'    =>  'Order' . $order_id,
                'settlement_reference' => $order_id
            );
            $plugin_params               = $this->aps_config->plugin_params();
            $gateway_params              = array_merge( $gateway_params, $plugin_params );
            if(!empty($token_name)){
                $gateway_params['token_name'] = $token_name;
                $gateway_params['merchant_reference'] = $this->aps_helper->generate_random_key();
                update_post_meta( $order_id, 'stc_pay_reference_id', $gateway_params['merchant_reference'] );
            }
            else{
                if($this->aps_config->get_enabled_tokenization() == 'yes') {
                    $gateway_params['remember_me'] = $remember_me ? 'YES' : 'NO';
                }
                $gateway_params['otp'] = $otp;
                $gateway_params['merchant_reference'] = $reference_id;
            }
            // Remember exactly what was asked for, so the response can be checked against it.
            $this->aps_order->record_expected_payment(
                isset( $gateway_params['amount'] ) ? $gateway_params['amount'] : '',
                isset( $gateway_params['currency'] ) ? $gateway_params['currency'] : ''
            );
            $signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
            $gateway_params['signature'] = $signature;
            //execute post
            $gateway_url = $this->aps_config->get_gateway_url( 'api' );
            $result      = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
            $this->aps_helper->log( 'STC Pay execute purchase ' . json_encode( $result ) );
            $stc_pay_api_error_message = __( 'STC PAY API failed. Please try again later', 'amazon-payment-services' );
            if ( isset( $result['response_code'] ) && APS_Constants::APS_PAYMENT_SUCCESS_RESPONSE_CODE === $result['response_code'] ) {
                if ( ! $this->aps_order->success_order( $result, 'online' ) ) {
                    $this->aps_helper->log( 'APS STC Pay response failed verification for order ' . $this->aps_order->get_order_id() . '; order held for review.' );
                    throw new \Exception( __( 'We could not verify this payment. Please contact the store.', 'amazon-payment-services' ) );
                }
                $status  = 'success';
                $message = __( 'Transaction Verified successfully', 'amazon-payment-services' );
            } else {
                $status  = 'error';
                $message = isset( $result['response_message'] ) && ! empty( $result['response_message'] ) ? $result['response_message'] : $stc_pay_api_error_message;
                $this->aps_order->decline_order( $result, $message );
                throw new \Exception( $message );
            }
            session_start();
            unset( $_SESSION['stc_pay_payment'] );
            session_write_close();
        } catch ( \Exception $e ) {
            $status  = 'error';
            $message = $e->getMessage();
        }
        return array(
            'status'  => $status,
            'message' => $message,
        );
    }

}

