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
class WC_Gateway_APS_Super extends WC_Payment_Gateway {

	protected $aps_config;
	protected $aps_helper;
	protected $aps_payment;
	protected $aps_order;
	protected $aps_refund;

	/* Validation Messages property */
	private $validation_messages = array();

	public function __construct() {
		$this->aps_config       = APS_Config::get_instance();
		$this->aps_helper       = APS_Helper::get_instance();
		$this->aps_payment      = APS_Payment::get_instance();
		$this->aps_refund       = APS_Refund::get_instance();
		$this->aps_order        = new APS_Order();
		$this->supports         = array( 'products', 'refunds' );
		$this->redirection_text = __( 'You will be redirected to the Amazon Payment Services website when you place an order', 'amazon-payment-services' );

		$this->validation_messages = array(
			'required'   => __( '{field_name} : cannot be empty', 'amazon-payment-services' ),
			'no_space'   => __( '{field_name} : should not contain blank spaces', 'amazon-payment-services' ),
			'min_length' => __( '{field_name} : must be greater than or equals {min_length}', 'amazon-payment-services' ),
			'max_length' => __( '{field_name} : must be lower than or equals to {max_length}', 'amazon-payment-services' ),
		);
		// Method with all the options fields
		$this->has_fields = true;
		$this->init_form_fields();

		// Register webhook
		add_action( 'woocommerce_api_aps_online_response', array( $this, 'aps_online_response' ) );
		add_action( 'woocommerce_api_aps_offline_response', array( $this, 'aps_offline_response' ) );
		add_action( 'woocommerce_api_aps_merchant_response', array( $this, 'aps_merchant_response' ) );
		add_action( 'woocommerce_api_aps_merchant_cancel', array( $this, 'aps_merchant_cancel' ) );
		add_action( 'woocommerce_api_aps_merchant_cancel_apple_pay', array( $this, 'aps_merchant_cancel_apple_pay' ) );
		add_action( 'woocommerce_api_aps_token_response', array( $this, 'aps_token_response' ) );
	}

	/**
	 * Save admin options
	 *
	 * @return void
	 */
	public function save_admin_options() {
		$this->init_settings();
		$post_data = $this->get_post_data();
		foreach ( $this->get_form_fields() as $key => $field ) {
			if ( 'title' !== $this->get_field_type( $field ) ) {
				$this->check_field_validity( $key, $field, $post_data );
			}
		}
		if ( ! empty( $this->errors ) ) {
			$this->display_errors();
		} else {
			parent::process_admin_options();
		}
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'api keys' and availability on a country-by-country basis
	 *
	 * @return void
	 */
	public function admin_options() {
		$template_array = array(
			'file'       => 'configuration-fields.php',
			'method_obj' => $this,
		);
		APS_Admin::load_config_fields( $template_array );
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @return void
	 */
	public function init_form_fields() {
		// Create Field Loader Object
		$aps_field_loader = new APS_Fields_Loader();

		// Load all fields
		$this->form_fields = $aps_field_loader->get_config_fields();
	}

	/**
	 * Process the payment and return the result
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		global $woocommerce;
		$order                         = new WC_Order( $order_id );
		$payment_method                = $this->id;
		$integration_type              = $this->get_integration_type();
		$payment_option                = isset( $this->api_payment_option ) ? $this->api_payment_option : null;
		$extras                        = array();
		$installment_plan_code         = filter_input( INPUT_POST, 'aps_installment_plan_code' );
		$installment_issuer_code       = filter_input( INPUT_POST, 'aps_installment_issuer_code' );
		$installment_confirmation_en   = filter_input( INPUT_POST, 'aps_installment_confirmation_en' );
		$installment_confirmation_ar   = filter_input( INPUT_POST, 'aps_installment_confirmation_ar' );
		$aps_installment_interest      = filter_input( INPUT_POST, 'aps_installment_interest' );
		$aps_installment_amount        = filter_input( INPUT_POST, 'aps_installment_amount' );
		$aps_payment_token_cc          = filter_input( INPUT_POST, 'aps_payment_token_cc' );
		$aps_card_bin                  = filter_input( INPUT_POST, 'aps_card_bin' );
		$aps_payment_token_installment = filter_input( INPUT_POST, 'aps_payment_token_installment' );
		$aps_payment_cvv               = filter_input( INPUT_POST, 'aps_payment_cvv' );

		$aps_cc_plan_code       = filter_input( INPUT_POST, 'aps_cc_plan_code' );
		$aps_cc_issuer_code     = filter_input( INPUT_POST, 'aps_cc_issuer_code' );
		$aps_cc_confirmation_en = filter_input( INPUT_POST, 'aps_cc_confirmation_en' );
		$aps_cc_confirmation_ar = filter_input( INPUT_POST, 'aps_cc_confirmation_ar' );
		$aps_cc_interest        = filter_input( INPUT_POST, 'aps_cc_interest' );
		$aps_cc_amount          = filter_input( INPUT_POST, 'aps_cc_amount' );

		if ( ! empty( $installment_plan_code ) ) {
			update_post_meta( $order_id, 'hosted_installment_plan_code', $installment_plan_code );
		}
		if ( ! empty( $installment_issuer_code ) ) {
			update_post_meta( $order_id, 'hosted_installment_issuer_code', $installment_issuer_code );
		}
		if ( ! empty( $installment_confirmation_en ) ) {
			update_post_meta( $order_id, 'aps_installment_confirmation_en', $installment_confirmation_en );
		}
		if ( ! empty( $installment_confirmation_ar ) ) {
			update_post_meta( $order_id, 'aps_installment_confirmation_ar', $installment_confirmation_ar );
		}
		if ( ! empty( $aps_installment_interest ) ) {
			update_post_meta( $order_id, 'aps_installment_interest', $aps_installment_interest );
		}
		if ( ! empty( $aps_installment_amount ) ) {
			update_post_meta( $order_id, 'aps_installment_amount', $aps_installment_amount );
		}

		//Credit cards
		if ( ! empty( $aps_cc_plan_code ) ) {
			update_post_meta( $order_id, 'hosted_cc_plan_code', $aps_cc_plan_code );
		} else {
			$plan_code = get_post_meta( $order_id, 'hosted_cc_plan_code', true);
			if ( isset( $plan_code ) && ! empty( $plan_code ) ) {
				delete_post_meta( $order_id, 'hosted_cc_plan_code');
			}
		}
		if ( ! empty( $aps_cc_issuer_code ) ) {
			update_post_meta( $order_id, 'hosted_cc_issuer_code', $aps_cc_issuer_code );
		}
		if ( ! empty( $aps_cc_confirmation_en ) ) {
			update_post_meta( $order_id, 'aps_cc_confirmation_en', $aps_cc_confirmation_en );
		}
		if ( ! empty( $aps_cc_confirmation_ar ) ) {
			update_post_meta( $order_id, 'aps_cc_confirmation_ar', $aps_cc_confirmation_ar );
		}
		if ( ! empty( $aps_cc_interest ) ) {
			update_post_meta( $order_id, 'aps_cc_interest', $aps_cc_interest );
		}
		if ( ! empty( $aps_cc_amount ) ) {
			update_post_meta( $order_id, 'aps_cc_amount', $aps_cc_amount );
		}

		if ( APS_Constants::APS_PAYMENT_TYPE_CC === $payment_method ) {
			if ( isset( $aps_payment_token_cc ) && ! empty( $aps_payment_token_cc ) ) {
				$extras['aps_payment_token'] = trim( $aps_payment_token_cc, ' ' );
			}
		} elseif ( APS_Constants::APS_PAYMENT_TYPE_INSTALLMENT === $payment_method ) {
			if ( isset( $aps_payment_token_installment ) && ! empty( $aps_payment_token_installment ) ) {
				$extras['aps_payment_token'] = trim( $aps_payment_token_installment, ' ' );
			}
		}
		if ( isset( $aps_payment_cvv ) && ! empty( $aps_payment_cvv ) ) {
			$extras['aps_payment_cvv'] = trim( $aps_payment_cvv, ' ' );
		}
		if ( isset( $order_id ) && ! empty( $order_id ) ) {
			$extras['order_id'] = $order_id;
		}
		if ( isset( $aps_card_bin ) && ! empty( $aps_card_bin ) ) {
			$extras['aps_card_bin'] = trim( $aps_card_bin, ' ' );
		}
		update_post_meta( $order_id, 'payment_gateway', APS_Constants::APS_GATEWAY_ID );
		$status = 'failed';
		if ( $status === $order->get_status() ) {
			$order->update_status( 'payment-pending', '' );
		}
		$payment_data = $this->aps_payment->get_payment_request_form( $payment_method, $integration_type, $payment_option, $extras );
		$result       = array(
			'result'                 => 'success',
			'url'                    => $payment_data['url'],
			'params'                 => $payment_data['params'],
			'is_hosted_tokenization' => $payment_data['is_hosted_tokenization'],
			'redirect_url'           => $payment_data['redirect_url'],
		);
		// save integration type
		update_post_meta( $order_id, 'APS_INTEGEATION_TYPE', $integration_type );
		update_post_meta( $order_id, 'aps_redirected', 1 );
		if ( isset( $payment_data['form'] ) ) {
			$result['form'] = $payment_data['form'];
		}
		wp_send_json( $result );
		wp_die();
	}

	/**
	 * Validate the fields based on validation rules
	 *
	 * @param string key
	 * @param array field_data
	 * @param array post_data
	 *
	 * @return void
	 */
	private function check_field_validity( $key, $field_data, $post_data ) {
		$type       = $this->get_field_type( $field_data );
		$field_key  = $this->get_field_key( $key );
		$value      = isset( $post_data[ $field_key ] ) ? $post_data[ $field_key ] : null;
		$field_name = isset( $field_data['title'] ) ? $field_data['title'] : $field_key;
		if ( isset( $field_data['validation_rules'] ) && ! empty( $field_data['validation_rules'] ) ) {
			foreach ( $field_data['validation_rules'] as $validation_type ) {
				if ( 'required' === $validation_type && ( empty( $value ) || is_null( $value ) ) ) {
					$this->add_error( str_replace( '{field_name}', $field_name, $this->validation_messages['required'] ) );
				} elseif ( 'no_space' === $validation_type && preg_match( '/\s/', $value ) ) {
					$this->add_error( str_replace( '{field_name}', $field_name, $this->validation_messages['no_space'] ) );
				}
			}
		} if ( isset( $field_data['min_length'] ) || isset( $field_data['max_length'] ) ) {
			if ( isset( $field_data['min_length'] ) && strlen( $value ) < $field_data['min_length'] ) {
				$this->add_error( str_replace( array( '{field_name}', '{min_length}' ), array( $field_name, $field_data['min_length'] ), $this->validation_messages['min_length'] ) );
			} elseif ( isset( $field_data['max_length'] ) && strlen( $value ) > $field_data['max_length'] ) {
				$this->add_error( str_replace( array( '{field_name}', '{max_length}' ), array( $field_name, $field_data['max_length'] ), $this->validation_messages['max_length'] ) );
			}
		}
	}

	/**
	 * APS online Response
	 *
	 * @return void
	 */
	public function aps_online_response() {
		$this->aps_handle_response();
	}

	/**
	 * APS offline Response
	 *
	 * @return void
	 */
	public function aps_offline_response() {
		$this->aps_handle_response( 'offline' );
	}

	/**
	 * Online merchant response
	 *
	 * @return void
	 */
	public function aps_merchant_response() {
		$post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
		if (null == $post) {
			$post = [];
		}
		$get  =  wp_kses_post_deep($_GET);
		$response_params = array_merge( $get, $post );
		$order_id        = $response_params['merchant_reference'];
		if ( ! empty( $order_id ) ) {
			$this->aps_order->load_order( $order_id );
			$integration_type = ! empty( $this->aps_order->get_payment_integration_type() ) ? $this->aps_order->get_payment_integration_type() : $this->get_integration_type();
			$this->aps_handle_response( 'online', $integration_type );
		}
	}

	/**
	 * APS handle Response
	 *
	 * @return void
	 */
	public function aps_handle_response( $response_mode = 'online', $integration_type = APS_Constants::APS_INTEGRATION_TYPE_REDIRECTION, $is_merchant_call = false ) {
		$post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
		if (null == $post) {
			$post = [];
		}
		$get  =  wp_kses_post_deep($_GET);
		$response_params = array_merge( $get, $post );
		if ( empty( $response_params ) ) {
			$params = file_get_contents( 'php://input' );
			if (!empty($params)) {
				$response_params = json_decode(filter_var($params, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES), true);
				$this->aps_helper->log( 'webhook params: ' . wp_json_encode( $response_params, true ) );
			} else {
				$this->aps_helper->log( 'webhook params is empty' );
			}

		}
		if ( isset( $response_params['merchant_reference'] ) ) {
			if (isset($response_params['payment_option']) && 'VALU' === $response_params['payment_option'] && 'offline' === $response_mode ) {
				$response_params['merchant_reference'] = $this->aps_helper->find_valu_order_by_reference( $response_params['merchant_reference'] );
			}
			$success = $this->aps_payment->handle_fort_response( $response_params, $response_mode, $integration_type );
			if ( $success ) {
				//handle valu refund webhook
				if ( isset( $response_params['command'] ) && 'REFUND' == $response_params['command'] ) {
					$order_id = $response_params['merchant_reference'];
					$this->aps_order->load_order( $order_id );

					// check if webhook call for valu refund
					$order = $this->aps_order->get_loaded_order();
					if ( ! ( $order && $order->get_id() ) ) {
						$response_params['merchant_reference'] = $this->aps_helper->find_valu_order_by_reference( $response_params['merchant_reference'] );

						$this->aps_helper->log( 'Valu REFUND order_id' . $response_params['merchant_reference']);
					}
				}
				$order = new WC_Order( $response_params['merchant_reference'] );
				WC()->session->set( 'refresh_totals', true );
				$redirect_url = $this->get_return_url( $order );
			} else {
				$redirect_url = wc_get_checkout_url();
			}
			if ( 'offline' === $response_mode ) {
				$this->aps_helper->log( 'Webhook processed' );
				header( 'HTTP/1.1 200 OK' );
				exit;
			} else {
				$order = new WC_Order( $response_params['merchant_reference'] );
				if ( in_array($order->get_status(), ['processing', 'completed']) ) {
					$redirect_url = $this->get_return_url( $order );
					unset( $_SESSION['aps_error'] );
				}

				if ( APS_Constants::APS_INTEGRATION_TYPE_STANDARD_CHECKOUT == $integration_type ) {
					$this->aps_helper->log( '!success redirect \n\n' . $redirect_url );
					echo '<script>window.top.location.href = "' . esc_url_raw( $redirect_url ) . '"</script>';
					exit;
				} else {
					$this->aps_helper->log( 'success redirect \n\n' . $redirect_url );
					ob_start();
					header('Location: ' . esc_url_raw($redirect_url));
					ob_end_flush();
					exit;
				}
			}
		}
	}

	/**
	 * APS Merchant Cancel
	 *
	 * @return void
	 */
	public function aps_merchant_cancel() {
		$this->aps_payment->merchant_page_cancel();
		//echo '<script>window.top.location.href = "' . esc_url_raw( wc_get_checkout_url() ) . '"</script>';
		$this->aps_helper->log( 'Cancel redirect \n\n');
		ob_start();
		header('Location: ' . esc_url_raw(wc_get_checkout_url() ));
		ob_end_flush();
		exit;
	}

	/**
	 * APS Merchant Cancel apple pay
	 *
	 * @return void
	 */
	public function aps_merchant_cancel_apple_pay() {
		$apple_pay_cancel = 1;
		$this->aps_payment->merchant_page_cancel($apple_pay_cancel);
		//echo '<script>window.top.location.href = "' . esc_url_raw( wc_get_checkout_url() ) . '"</script>';
		$this->aps_helper->log( 'Cancel redirect \n\n');
		ob_start();
		header('Location: ' . esc_url_raw(wc_get_checkout_url() ));
		ob_end_flush();
		exit;
	}

	/**
	 * APS Token Response
	 */
	public function aps_token_response() {
		$user_id = '';
		if ( isset($_GET['auth']) ) {
			$user_id       = sanitize_text_field($_GET['auth']);
		}
		$response_data = filter_input_array( INPUT_POST );
		$this->aps_create_token( $response_data, $user_id );
	}

	/**
	 * Create Tokens
	 */
	public function aps_create_token( $response_params, $user_id ) {
		session_start();
		try {
			if ( APS_Constants::APS_TOKEN_SUCCESS_RESPONSE_CODE === $response_params['response_code'] || APS_Constants::APS_TOKEN_SUCCESS_STATUS_CODE === $response_params['status'] ) {
				$existing_tokens = WC_Payment_Tokens::get_customer_tokens( $user_id, APS_Constants::APS_PAYMENT_TYPE_CC );
				$token           = $response_params['token_name'];
				$card_number     = $response_params['card_number'];
				$match_with_old  = array_filter(
					$existing_tokens,
					function( $token_row ) use ( $token ) {
						if ( $token_row->get_token() === $token ) {
							return true;
						} else {
							return false;
						}
					}
				);
				if ( ! empty( $match_with_old ) ) {
					foreach ( $match_with_old as $old_token_row ) {
						if ( isset( $response_params['payment_option'] ) ) {
							update_metadata( 'payment_token', $old_token_row->get_id(), 'card_type', strtolower( $response_params['payment_option'] ) );
						}
						if ( isset( $response_params['card_holder_name'] ) ) {
							update_metadata( 'payment_token', $old_token_row->get_id(), 'card_holder_name', $response_params['card_holder_name'] );
						}
						if ( isset( $response_params['card_number'] ) ) {
							$last4 = substr( $response_params['card_number'], -4 );
							update_metadata( 'payment_token', $old_token_row->get_id(), 'last4', $last4 );
							update_metadata( 'payment_token', $old_token_row->get_id(), 'masking_card', $response_params['card_number'] );
						}
						if ( isset( $response_params['expiry_date'] ) ) {
							$short_year  = substr( $response_params['expiry_date'], 0, 2 );
							$short_month = substr( $response_params['expiry_date'], 2, 2 );
							$date_format = \DateTime::createFromFormat( 'y', $short_year );
							$full_year   = $date_format->format( 'Y' );
							update_metadata( 'payment_token', $old_token_row->get_id(), 'expiry_month', $short_month );
							update_metadata( 'payment_token', $old_token_row->get_id(), 'expiry_year', $full_year );
						}
						$this->aps_helper->log( 'APS token updated \n\n' . wp_json_encode( $response_params, true ) );
					}
				} else {
					$card_type   = isset( $response_params['card_number'] ) ? $this->aps_helper->find_card_type( substr( $response_params['card_number'], 0, 6 ) ) : null;
					$gateway_id  = APS_Constants::APS_PAYMENT_TYPE_CC;
					$token       = new WC_Payment_Token_CC();
					$short_year  = substr( $response_params['expiry_date'], 0, 2 );
					$date_format = \DateTime::createFromFormat( 'y', $short_year );
					$full_year   = $date_format->format( 'Y' );
					$token->set_token( $response_params['token_name'] );
					$token->set_gateway_id( $gateway_id );
					$token->set_card_type( strtolower( $card_type ) );
					$token->set_last4( substr( $response_params['card_number'], -4 ) );
					$token->set_expiry_month( substr( $response_params['expiry_date'], 2, 2 ) );
					$token->set_expiry_year( $full_year );
					$token->set_user_id( $user_id );
					$token->save();
					update_metadata( 'payment_token', $token->get_id(), 'masking_card', $response_params['card_number'] );
					if ( isset( $response_params['card_holder_name'] ) ) {
						update_metadata( 'payment_token', $token->get_id(), 'card_holder_name', $response_params['card_holder_name'] );
					}
					$this->aps_helper->log( 'APS token created \n\n' . wp_json_encode( $response_params, true ) );
					$_SESSION['aps_token_success'] = __( 'Payment method successfully added.', 'woocommerce' );
				}
			} else {
				$_SESSION['aps_token_error'] = wp_kses_data($response_params['response_message']);
			}
		} catch ( Exception $e ) {
			$_SESSION['aps_token_error'] = wp_kses_data($e->getMessage());
		}
		$redirect_to = wc_get_account_endpoint_url( 'payment-methods' );
		wp_safe_redirect( $redirect_to );
		exit;
	}

	/**
	 * Show checkout error
	 *
	 * @return void
	 */
	public function show_checkout_payment_errors() {
		if ( isset( $_SESSION['aps_error'] ) ) {
			$aps_error = wp_kses_data($_SESSION['aps_error']);
			/* translators: %s: aps_error */
			$aps_error_msg = sprintf( __( 'An error occurred while making the transaction. Please try again. (Error message: %s)', 'amazon-payment-services' ), wp_kses_data($aps_error) );
			$this->aps_helper->set_flash_msg( $aps_error_msg, APS_Constants::APS_FLASH_MESSAGE_ERROR );
			unset( $_SESSION['aps_error'] );
		}
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
	}

	/**
	 * Can the order be refunded via APS?
	 *
	 * @param  WC_Order $order Order object.
	 * @return bool
	 */
	public function can_refund_order( $order ) {
		$order_status = $order->get_status();
		$payment_data = get_post_meta( $order->get_id(), 'aps_payment_response', true );
		if ( 'cancelled' === $order_status ) {
			return false;
		} elseif ( APS_Constants::APS_COMMAND_AUTHORIZATION === $payment_data['command'] ) {
			$authorization_command = get_post_meta( $order->get_id(), 'aps_authorization_command', true );
			if ( APS_Constants::APS_COMMAND_VOID === $authorization_command ) {
				return false;
			} elseif ( APS_Constants::APS_COMMAND_CAPTURE === $authorization_command ) {
				return $this->aps_helper->captured_amount_total( $order->get_id() ) > 0 ? true : false;
			} else {
				return false;
			}
		}
		return true;
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
		$payment_data = get_post_meta( $order_id, 'aps_payment_response', true );
		if ( isset( $payment_data['command'] ) && APS_Constants::APS_COMMAND_AUTHORIZATION === $payment_data['command'] ) {
			$authorization_command = get_post_meta( $order_id, 'aps_authorization_command', true );
			if ( APS_Constants::APS_COMMAND_CAPTURE === $authorization_command ) {
				$order           = wc_get_order( $order_id );
				$captured_amount = $this->aps_helper->captured_amount_total( $order_id );
				$refunded_amount = $this->aps_helper->getOrderRefundedAmoutTotal($order_id);
				$balance_amount  = $captured_amount - $refunded_amount;

				$this->aps_helper->log( 'refunded_amount= ' . $refunded_amount . '  captured_amount= ' . $captured_amount . ' balance_amount =' . $balance_amount . ' amount' . $amount);
				if ( $amount > $balance_amount ) {
					$error = new WP_Error();
					$error->add( 'aps_refund_error', __( 'Refund amount must be lower than captured amount', 'woocommerce' ) );
					return $error;
				}
			}
		}
		$refund_status = $this->aps_refund->submit_refund( $order_id, $amount, $reason );
		return $refund_status;
	}

	public function redirection_info() {
		$integration_type = $this->get_integration_type();
		if ( APS_Constants::APS_INTEGRATION_TYPE_HOSTED_CHECKOUT !== $integration_type ) {
			echo '<p class="redirection_info">' . wp_kses_data( $this->redirection_text ) . '</p>';
		}
	}

	/**
	 * Generate Text info HTML.
	 *
	 * @param string $key Field key.
	 * @param array  $data Field data.
	 * @since  1.0.0
	 * @return string
	 */
	public function generate_text_info_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo wp_kses_post($this->get_tooltip_html( $data )); // WPCS: XSS ok. ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<?php echo wp_kses_post($this->get_description_html( $data )); // WPCS: XSS ok. ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}
}
