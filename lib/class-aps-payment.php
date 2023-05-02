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
		$payment_request_params = $this->build_payment_gateway_params( $payment_method, $integration_type, $payment_option, $extras );
		$aps_data               = array(
			'url'                    => $payment_request_params['url'],
			'params'                 => $payment_request_params['params'],
			'is_hosted_tokenization' => $payment_request_params['is_hosted_tokenization'],
			'redirect_url'           => $payment_request_params['redirect_url'],
		);
		if ( APS_Constants::APS_INTEGRATION_TYPE_REDIRECTION === $integration_type ) {
			$aps_request_form = '<form name="aps_payment_form" id="' . APS_Constants::APS_SELECTOR_PAYMENT_REQFORM_ID . '" method="POST" action="' . $payment_request_params['url'] . '">';
			foreach ( $payment_request_params['params'] as $k => $v ) {
				$aps_request_form .= '<input type="hidden" name="' . $k . '" value="' . $v . '">';
			}
			$aps_request_form .= '<input type="submit">';
			$aps_data['form']  = $aps_request_form;
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

		$gateway_params = array(
			'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
			'access_code'         => $this->aps_config->get_access_code(),
			'merchant_reference'  => $order_id,
			'language'            => $this->aps_config->get_language(),
		);
		if ( APS_Constants::APS_INTEGRATION_TYPE_REDIRECTION === $integration_type ) {
			$currency                            = $this->aps_helper->get_fort_currency();
			$gateway_params['currency']          = strtoupper( $currency );
			$gateway_params['amount']            = $this->aps_helper->convert_fort_amount( $this->aps_order->get_total(), $this->aps_order->get_currency_value(), $currency );
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
                $gateway_params['customer_ip'] = $this->aps_helper->get_customer_ip();
                $gateway_params['customer_name'] =  $this->aps_order->get_customer_name();
                $gateway_params['merchant_extra'] =  $order_id;
            }
			$plugin_params  = $this->aps_config->plugin_params();
			$gateway_params = array_merge( $gateway_params, $plugin_params );
			if ( APS_Constants::APS_PAYMENT_TYPE_CC !== $payment_method && APS_Constants::APS_PAYMENT_TYPE_VISA_CHECKOUT !== $payment_method && APS_Constants::APS_PAYMENT_TYPE_STC_PAY !== $payment_method ) {
				unset( WC()->session->order_awaiting_payment );
			}
		} else {
			$gateway_params['service_command'] = APS_Constants::APS_COMMAND_TOKENIZATION;
			$gateway_params['return_url']      = create_wc_api_url( 'aps_merchant_response' );
			if ( APS_Constants::APS_PAYMENT_TYPE_INSTALLMENT === $payment_method && APS_Constants::APS_INTEGRATION_TYPE_STANDARD_CHECKOUT === $integration_type ) {
				$currency                       = $this->aps_helper->get_fort_currency();
				$gateway_params['currency']     = strtoupper( $currency );
				$gateway_params['installments'] = APS_Constants::APS_COMMAND_STANDALONE;
				$gateway_params['amount']       = $this->aps_helper->convert_fort_amount( $this->aps_order->get_total(), $this->aps_order->get_currency_value(), $currency );
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
					$this->aps_order->success_order( $aps_notify_params, 'online' );
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
				}
			}
		}
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
		$this->aps_helper->log( 'APS build_payment_gateway_params payment method ($payment_method) \n\n' . wp_json_encode( $builder, true ) );
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
            if (isset($response_params['payment_option']) && $response_params['payment_option'] ===  APS_Constants::APS_PAYMENT_METHOD_STC_PAY){
                $order_id = $response_params['merchant_extra'];
                $this->aps_order->load_order( $order_id );
            }else{
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
			$signature_type     = isset( $response_params['digital_wallet'] ) && APS_Constants::APS_PAYMENT_METHOD_APPLE_PAY === $response_params['digital_wallet'] ? 'apple_pay' : 'regular';

			//check webhook call for apple pay
			if ( isset( $response_params['command'] ) && in_array($response_params['command'], array('REFUND', 'CAPTURE', 'VOID_AUTHORIZATION')) ) {
				if ( isset($response_params['access_code']) && $response_params['access_code'] == $this->aps_config->get_apple_pay_access_code() ) {
					$signature_type = 'apple_pay';
				}
			}

			$response_signature = $this->aps_helper->generate_signature( $response_gateway_params, 'response', $signature_type );

			//update order id if webhook call for valu refund
			if ( '' != $order_id_by_reference && ( ! ( $order && $order->get_id() ) ) ) {
				$order_id = $order_id_by_reference;
				$this->aps_order->load_order( $order_id );
				$response_params['merchant_reference'] = $order_id;
				$this->aps_helper->log( 'Valu REFUND order_id from reference_id' . $order_id);
			}


			$payment_method = $this->aps_order->get_payment_method();

            $stc_ignore_signature = true;
            if ($payment_method == APS_Constants::APS_PAYMENT_TYPE_STC_PAY || APS_Constants::APS_PAYMENT_METHOD_STC_PAY === $response_params['payment_option'] ){
                $stc_ignore_signature = false;
            }
			// check the signature
			if ( strtolower( $response_signature ) !== strtolower( $signature ) && 'VALU' !== $response_params['payment_option'] && $stc_ignore_signature ) {
				$response_message = __( 'Invalid Singature', 'amazon-payment-services' );
				// There is a problem in the response we got
				$this->aps_order->on_hold_order( 'Invalid Signature.' );
				$aps_invalid_signature_log = "APS Response invalid signature ERROR\n\n Original array : " . wp_json_encode( $response_params, true ) . "\n\n\n Final array : " . wp_json_encode( $response_gateway_params, true );
				$this->aps_helper->log( $aps_invalid_signature_log );
				return true;
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
				$this->aps_order->success_order( $response_params, $response_mode );
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
						$this->aps_order->success_order( $aps_notify_params, $response_mode );
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
						$this->aps_order->success_order( $aps_notify_params, $response_mode );
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
		$currency       = $this->aps_order->get_currency();
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
			'customer_ip'         => $this->aps_helper->get_customer_ip(),
			'amount'              => $this->aps_helper->convert_fort_amount( $this->aps_order->get_total(), $this->aps_order->get_currency_value(), $currency ),
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

		if ( isset( $plan_code ) && ! empty( $plan_code ) ) {
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
		$signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
		$gateway_params['signature'] = $signature;
		$gateway_url                 = $this->aps_config->get_gateway_url( 'api' );
		$this->aps_helper->log( 'APS aps_notify request \n\n' . wp_json_encode( $gateway_params, true ) );
		$response = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
		$this->aps_helper->log( 'APS aps_notify response \n\n' . wp_json_encode( $response, true ) );
		return $response;
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
		$gateway_params         = array(
			'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
			'access_code'         => $this->aps_config->get_access_code(),
			'merchant_reference'  => $renewal_order_id,
			'language'            => $language,
			'command'             => APS_Constants::APS_COMMAND_PURCHASE,
			'customer_ip'         => $this->resolve_customer_ip(),
			'amount'              => $this->aps_helper->convert_fort_amount( $recurring_amount, 1, $currency ),
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
		$signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
		$gateway_params['signature'] = $signature;

		$gateway_url = $this->aps_config->get_gateway_url( 'api' );
		$this->aps_helper->log( 'APS recurring request \n\n' . wp_json_encode( $gateway_params, true ) );
		$response = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
		if ( APS_Constants::APS_PAYMENT_SUCCESS_RESPONSE_CODE === $response['response_code'] ) {
			$this->aps_order->success_order( $response, 'online' );
			$payment_status = true;
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
        $gateway_params         = array(
            'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
            'access_code'         => $this->aps_config->get_access_code(),
            'digital_wallet'      => APS_Constants::APS_PAYMENT_METHOD_STC_PAY,
            'merchant_reference'  => $this->aps_helper->generate_random_key(),
            'language'            => $language,
            'command'             => APS_Constants::APS_COMMAND_PURCHASE,
            'customer_ip'         => $this->resolve_customer_ip(),
            'amount'              => $this->aps_helper->convert_fort_amount( $recurring_amount, 1, $currency ),
            'currency'            => strtoupper( $currency ),
            'customer_email'      => $this->aps_order->get_email(),
            'token_name'          => $aps_response['token_name'],
            'return_url'          => create_wc_api_url( 'aps_online_response' ),
        );
        $customer_name          = $this->aps_order->get_customer_name();
        if ( ! empty( $customer_name ) ) {
            $gateway_params['customer_name'] = $customer_name;
        }
        $signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
        $gateway_params['signature'] = $signature;

        $gateway_url = $this->aps_config->get_gateway_url( 'api' );
        $this->aps_helper->log( 'APS recurring request \n\n' . wp_json_encode( $gateway_params, true ) );
        $response = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
        if ( APS_Constants::APS_PAYMENT_SUCCESS_RESPONSE_CODE === $response['response_code'] ) {
            $this->aps_order->success_order( $response, 'online' );
            $payment_status = true;
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
			$currency       = $this->aps_helper->get_fort_currency();
			$gateway_params = array(
				'digital_wallet'      => 'APPLE_PAY',
				'command'             => $this->aps_config->get_command( APS_Constants::APS_PAYMENT_TYPE_APPLE_PAY ),
				'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
				'access_code'         => $this->aps_config->get_apple_pay_access_code(),
				'merchant_reference'  => $order_id,
				'language'            => $this->aps_config->get_language(),
				'amount'              => $this->aps_helper->convert_fort_amount( $this->aps_order->get_total(), $this->aps_order->get_currency_value(), $currency ),
				'currency'            => strtoupper( $currency ),
				'customer_email'      => $this->aps_order->get_email(),
				'apple_data'          => $response_params->data->paymentData->data,
				'apple_signature'     => $response_params->data->paymentData->signature,
				'customer_ip'         => $this->aps_helper->get_customer_ip(),
			);
			foreach ( $response_params->data->paymentData->header as $key => $value ) {
				$gateway_params['apple_header'][ 'apple_' . $key ] = $value;
			}
			foreach ( $response_params->data->paymentMethod as $key => $value ) {
				$gateway_params['apple_paymentMethod'][ 'apple_' . $key ] = $value;
			}
			$signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request', 'apple_pay' );
			$gateway_params['signature'] = $signature;
			$gateway_url                 = $this->aps_config->get_gateway_url( 'api' );
			$this->aps_helper->log( 'Apple payment request ' . json_encode( $gateway_params ) );
			$response = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
			$this->aps_helper->log( 'Apple payment response ' . json_encode( $response ) );
			if ( APS_Constants::APS_PAYMENT_SUCCESS_RESPONSE_CODE === $response['response_code'] || APS_Constants::APS_PAYMENT_AUTHORIZATION_SUCCESS_RESPONSE_CODE === $response['response_code'] ) {
				$this->aps_order->success_order( $response, 'online' );
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
		$currency      = $this->aps_helper->get_fort_currency();
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
				'product_price'    => $this->aps_helper->convert_fort_amount( $order->get_total(), $this->aps_order->get_currency_value(), $currency ),
				'product_category' => $category_name,
			);
		} else {
			$products[] = array(
				'product_name'     => $product_name,
				'product_price'    => $this->aps_helper->convert_fort_amount( $order->get_total(), $this->aps_order->get_currency_value(), $currency ),
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
				session_start();
				$_SESSION['valu_payment']['reference_id']  = wp_kses_data($reference_id);
				$_SESSION['valu_payment']['mobile_number'] = wp_kses_data($mobile_number);
				$_SESSION['valu_payment']['down_payment'] = wp_kses_data($down_payment);
                $_SESSION['valu_payment']['tou'] = wp_kses_data($tou);
                $_SESSION['valu_payment']['cash_back'] = wp_kses_data($cash_back);
				session_write_close();
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
	 * Valu generate OTP
	 *
	 * @return array
	 */
	public function valu_generate_otp( $reference_id, $mobile_number, $order_id, $down_payment, $tou, $cash_back) {
		$status               = 'success';
		$message              = 'OTP Generated';
		$mobile_number_string = null;
		$tenure_html = '';
        if($this->aps_config->get_enable_valu_down_payment() =="yes") {
            if (empty($down_payment) || $down_payment == "") {
                $down_payment = $this->aps_config->get_valu_down_payment_value();
            }
        }else{$down_payment=0;}

		try {
			$this->aps_order->load_order( $order_id );
			$products                    = $this->get_valu_products_data();
			$currency                    = $this->aps_helper->get_front_currency();
			$gateway_params              = array(
				'service_command'     => 'OTP_GENERATE',
				'access_code'         => $this->aps_config->get_access_code(),
				'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
				'merchant_reference'  => $reference_id,
				'language'            => $this->aps_config->get_language(),
				'payment_option'      => 'VALU',
				'merchant_order_id'   => $order_id,
				'phone_number'        => $mobile_number,
				'amount'              => $this->aps_helper->convert_fort_amount( $this->aps_order->get_total(), $this->aps_order->get_currency_value(), $currency ),
				'currency'            => $currency,
				'products'            => $products,
				'total_downpayment'	  => $down_payment,
                'wallet_amount'       => intval($tou)*100,
                'cashback_wallet_amount' => intval($cash_back)*100,
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
				$tenure_html                     = "<div class='tenure_carousel'>";
				if ( isset( $result['installment_detail']['plan_details'] ) ) {
					foreach ( $result['installment_detail']['plan_details'] as $key => $ten ) {
						$tenure_html .= '<div class="slide">
								<div class="tenureBox" data-tenure="' . $ten['number_of_installments'] . '" data-tenure-amount="' . ( number_format($ten['amount_per_month']/100,2,'.','') ) . '" data-tenure-interest="' . ( number_format($ten['fees_amount']/100,2,'.','') ) . '" >
									<p class="tenure">' . $ten['number_of_installments'] . ' {months_txt}</p>
									<p class="emi"><strong>' . ( number_format($ten['amount_per_month']/100,2,'.','') ) . '</strong> EGP/{month_txt}</p>
									<p class="int_rate"> {interest_txt}' . ( number_format($ten['fees_amount']/100,2,'.','') )  . '</p>
									
								</div>
							</div>';
					}
				}
				$tenure_html .= '</div>';
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
			$reference_id = wp_kses_data($_SESSION['valu_payment']['reference_id']);
			$order_id     = ! empty( $this->aps_order->get_session_order_id() ) ? $this->aps_order->get_session_order_id() : wp_kses_data($_SESSION['valu_payment']['order_id']);
			$this->aps_order->load_order( $order_id );
			$mobile_number = wp_kses_data($_SESSION['valu_payment']['mobile_number']);
			session_write_close();
			$currency      = $this->aps_helper->get_front_currency();

			$gateway_params              = array(
				'service_command'     => 'OTP_VERIFY',
				'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
				'access_code'         => $this->aps_config->get_access_code(),
				'merchant_reference'  => $reference_id,
				'language'            => $this->aps_config->get_language(),
				'payment_option'      => 'VALU',
				'phone_number'        => $mobile_number,
				'amount'              => $this->aps_helper->convert_fort_amount( $this->aps_order->get_total(), $this->aps_order->get_currency_value(), $currency ),
				'merchant_order_id'   => $order_id,
				'currency'            => $currency,
				'otp'                 => $otp,
				'total_downpayment'   => 0,
			);
			$signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
			$gateway_params['signature'] = $signature;
			//execute post
			$gateway_url          = $this->aps_config->get_gateway_url( 'api' );
			
			$valuapi_stop_message = __( 'VALU API failed. Please try again later', 'amazon-payment-services' );
		} catch ( \Exception $e ) {
			$status  = 'error';
			$message = $e->getMessage();
		}
		return array(
			'status'      => $status,
			'message'     => $message,
		);
	}

	/**
	 * Valu generate OTP
	 *
	 * @return array
	 */
	public function valu_execute_purchase( $active_tenure, $otp ) {
		$status  = 'success';
		$message = '';
		try {
			$order_id = $this->aps_order->get_session_order_id();
			$this->aps_order->load_order( $order_id );
			session_start();
			$reference_id                = wp_kses_data($_SESSION['valu_payment']['reference_id']);
			$mobile_number               = wp_kses_data($_SESSION['valu_payment']['mobile_number']);
			$total_down_payment          = wp_kses_data($_SESSION['valu_payment']['down_payment']);
            $tou                         = wp_kses_data($_SESSION['valu_payment']['tou']);
            $cash_back                   = wp_kses_data($_SESSION['valu_payment']['cash_back']);
			$otp                         = wp_kses_data($otp);
			$customer_email              = $this->aps_order->get_email();
			$customer_code               = $mobile_number;
			$currency                    = $this->aps_helper->get_front_currency();
			$transaction_id              = wp_kses_data($_SESSION['valu_payment']['transaction_id']);
			session_write_close();
            if($this->aps_config->get_enable_valu_down_payment() =="yes") {
                if (empty($total_down_payment) || $total_down_payment == "") {
                    $total_down_payment = $this->aps_config->get_valu_down_payment_value();
                }
            }else{$total_down_payment=0;}
			$gateway_params              = array(
				'command'              => 'PURCHASE',
				'merchant_identifier'  => $this->aps_config->get_merchant_identifier(),
				'access_code'          => $this->aps_config->get_access_code(),
				'merchant_reference'   => $reference_id,
				'language'             => $this->aps_config->get_language(),
				'payment_option'       => 'VALU',
				'phone_number'         => $mobile_number,
				'amount'               => $this->aps_helper->convert_fort_amount( $this->aps_order->get_total(), $this->aps_order->get_currency_value(), $currency ),
				'merchant_order_id'    => $order_id,
				'currency'             => strtoupper( $currency ),
				'otp'                  => $otp,
				'tenure'               => $active_tenure,
				'total_down_payment'   => $total_down_payment,
                'wallet_amount'       => intval($tou)*100,
                'cashback_wallet_amount' => intval($cash_back)*100,
				'customer_code'        => $customer_code,
				'customer_email'       => $customer_email,
				'purchase_description' => 'Order' . $order_id,
				'transaction_id'       => $transaction_id,
			);
			
			if($otp === "" || empty($otp)){
				
				$message = __( 'Please provide the OTP', 'amazon-payment-services' );
				throw new \Exception( $message );
			}
            if($total_down_payment + $tou + $cash_back > $this->aps_helper->convert_fort_amount( $this->aps_order->get_total(), $this->aps_order->get_currency_value(), $currency )){
            	
                $message = __( 'Cashback, Downpayment, and ToU amounts sum cannot be greater than Cart Total', 'amazon-payment-services' );
                throw new \Exception( $message );
            }
			
			//$plugin_params               = $this->aps_config->plugin_params();
			//$gateway_params              = array_merge( $gateway_params, $plugin_params );
			$signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
			$gateway_params['signature'] = $signature;
			//execute post
			$gateway_url = $this->aps_config->get_gateway_url( 'api' );
			$result      = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
			$this->aps_helper->log( 'Valu execute purchase ' . json_encode( $result ) );
			if ( isset( $result['response_code'] ) && APS_Constants::APS_PAYMENT_SUCCESS_RESPONSE_CODE === $result['response_code'] ) {
				$status  = 'success';
				$message = __( 'Transaction Verified successfully', 'amazon-payment-services' );
				$this->aps_order->success_order( $result, 'online' );
			} elseif ( in_array( $response['response_code'], APS_Constants::APS_ONHOLD_RESPONSE_CODES, true ) ){
				$this->aps_order->on_hold_order( $response['response_message'] );
				$aps_error_log = "APS valU on hold stage : \n\n" . wp_json_encode( $response, true );
				$this->aps_helper->log( $aps_error_log );
				$status = 'success';
				$message = __( 'The transaction has been processed, but failed to receive confirmation. Please contact to verify', 'amazon-payment-services' );
			} else {
				$status  = 'error';
				$message = isset( $result['response_message'] ) && ! empty( $result['response_message'] ) ? $result['response_message'] : $valuapi_stop_message;
				$this->aps_order->decline_order( $response, $message );
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
            'valu_transaction_id' =>$result['valu_transaction_id'],
            'loan_number' =>$result['loan_number'],
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
            $reference_id = $this->aps_helper->generate_random_key();
            $this->aps_order->load_order( $order_id );
            $currency                    = $this->aps_helper->get_front_currency();
            $gateway_params              = array(
                'service_command'     => 'GENERATE_OTP',
                'access_code'         => $this->aps_config->get_access_code(),
                'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
                'merchant_reference'  => $reference_id,
                'language'            => $this->aps_config->get_language(),
                'digital_wallet'      => 'STCPAY',
                'phone_number'        => $mobile_number,
                'amount'              => $this->aps_helper->convert_fort_amount( $this->aps_order->get_total(), $this->aps_order->get_currency_value(), $currency ),
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
            $order_id = $this->aps_order->get_session_order_id();
            $this->aps_order->load_order( $order_id );
            session_start();
            $reference_id                = wp_kses_data($_SESSION['stc_pay_payment']['reference_id']);
            $mobile_number               = wp_kses_data($_SESSION['stc_pay_payment']['mobile_number']);
            session_write_close();
            $customer_email              = $this->aps_order->get_email();
            $customer_name               = $this->aps_order->get_customer_name();
            $currency                    = $this->aps_helper->get_front_currency();

            $gateway_params              = array(
                'command'              => 'PURCHASE',
                'merchant_identifier'  => $this->aps_config->get_merchant_identifier(),
                'access_code'          => $this->aps_config->get_access_code(),
                'language'             => $this->aps_config->get_language(),
                'digital_wallet'       => 'STCPAY',
                'phone_number'         => $mobile_number,
                'amount'               => $this->aps_helper->convert_fort_amount( $this->aps_order->get_total(), $this->aps_order->get_currency_value(), $currency ),
                'merchant_order_id'    => $order_id,
                'currency'             => strtoupper( $currency ),
                'customer_name'        => $customer_name,
                'customer_ip'          => $this->aps_helper->get_customer_ip(),
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
                $gateway_params['remember_me'] = $remember_me ? 'YES' : 'NO';
                $gateway_params['otp'] = $otp;
                $gateway_params['merchant_reference'] = $reference_id;
            }
            $signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
            $gateway_params['signature'] = $signature;
            //execute post
            $gateway_url = $this->aps_config->get_gateway_url( 'api' );
            $result      = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
            $this->aps_helper->log( 'STC Pay execute purchase ' . json_encode( $result ) );
            $stc_pay_api_error_message = __( 'STC PAY API failed. Please try again later', 'amazon-payment-services' );
            if ( isset( $result['response_code'] ) && APS_Constants::APS_PAYMENT_SUCCESS_RESPONSE_CODE === $result['response_code'] ) {
                $status  = 'success';
                $message = __( 'Transaction Verified successfully', 'amazon-payment-services' );
                $this->aps_order->success_order( $result, 'online' );
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


    /**
     * Try to resolve the customer IP address,
     * take the PHP SERVER value first, and it that is empty
     * try to take it from the original order
     *
     * @return string
     */
    private function resolve_customer_ip(): string
    {
        $customerIp = $this->aps_helper->get_customer_ip();
        if (empty($customerIp)) {
            $customerIp = $this->aps_order->get_customer_ip();
        }

        return $customerIp;
    }
}

