<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class APS_Order extends APS_Super {

    /**
     * @var WC_Order
     */
    private $order = array();
	private $order_id;
	private $aps_config;
	private $log;

	public function __construct() {
		parent::__construct();
		$this->aps_config = APS_Config::get_instance();
	}

	/**
	 * Loaded order by id
	 *
	 * @param order_id int
	 * @return object
	 */
	public function load_order( $order_id ) {
		$this->order_id = $order_id;
		$this->order    = $this->get_order_by_id( $order_id );
	}

	/**
	 * Set order by id
	 *
	 * @param order_id int
	 */
	public function set_order_id( $order_id ) {
		$this->order_id = $order_id;
	}

	/**
	 * Set order by object
	 *
	 * @param order object
	 */
	public function set_order( $order ) {
		$this->order = $order;
	}

	/**
	 * Get session order id
	 *
	 * @return order object
	 */
	public function get_session_order_id() {
		return WC()->session->get( 'order_awaiting_payment' );
	}

	/**
	 * Get this order id
	 *
	 * @return order_id int
	 */
	public function get_order_id() {
		return $this->order->get_id();
	}

	/**
	 * Get order by id
	 *
	 * @return order object
	 */
	public function get_order_by_id( $order_id ) {
		$order = wc_get_order( $order_id );
		return $order;
	}

	/**
	 * Get loaded order
	 *
	 * @return order object
	 */
	public function get_loaded_order() {
		return $this->order;
	}

	/**
	 * Get currency
	 *
	 * @return currency string
	 */
	public function get_currency_code() {
		return $this->order->get_currency();
	}

	/**
	 * Get currency rate
	 *
	 * @return currency_rate number
	 */
	public function get_currency_value() {
		return 1;
	}

	/**
	 * Get order total
	 *
	 * @return order_total number
	 */
	public function get_total() {
		return $this->order->get_total();
	}

	/**
	 * Get payment method
	 *
	 * @return payment_method string
	 */
	public function get_payment_method() {
		return $this->order->get_payment_method();
	}

	/**
	 * Get currency
	 *
	 * @return currency string
	 */
	public function get_currency() {
		return $this->order->get_currency();
	}

	/**
	 * Get payment integrate type
	 *
	 * @return integration_type string
	 */
	public function get_payment_integration_type() {
		return get_post_meta( $this->order_id, 'APS_INTEGRATION_TYPE', true );
	}

	/**
	 * Get customer name
	 *
	 * @return name string
	 */
	public function get_customer_name() {
		$first_name = $this->order->get_billing_first_name();
		$last_name  = $this->order->get_billing_last_name();
		return trim( $first_name . ' ' . $last_name );
	}

	/**
	 * Get customer email
	 *
	 * @return email string
	 */
	public function get_email() {
		return $this->order->get_billing_email();
	}

    /**
     * Get customer phone number
     *
     * @return phone number string
     */
    public function get_phone_number() {
        return $this->order->get_billing_phone();
    }

	/**
	 * Get order status
	 *
	 * @return order_status string
	 */
	public function get_status() {
		return $this->order->get_status();
	}

	/**
	 * Decline order
	 *
	 * @return bool
	 */
	public function decline_order( $response_params, $reason = '' ) {
		$this->order_log( 'APS decline order response\n\n' . json_encode( $response_params, true ) );
		if ( $this->get_order_id() ) {
			$tokenization_status = get_post_meta( $this->get_order_id(), 'tokenization_status', true );
			if ( isset( $response_params['service_command'] ) && APS_Constants::APS_COMMAND_TOKENIZATION === $response_params['service_command'] ) {
				$this->failed_order( $reason );
			} elseif ( isset( $response_params['payment_option'] ) && in_array( $response_params['payment_option'], APS_Constants::APS_RETRY_PAYMENT_OPTIONS, true ) ) {
				if ( 'yes' === $tokenization_status ) {
					$this->cancelled_order( $reason );
				} else {
					$this->failed_order( $reason );
				}
			} elseif ( isset( $response_params['payment_option'] ) && in_array( $response_params['digital_wallet'], APS_Constants::APS_RETRY_DIGITAL_WALLETS, true ) ) {
				if ( 'yes' === $tokenization_status ) {
					$this->cancelled_order( $reason );
				} else {
					$this->failed_order( $reason );
				}
			} elseif ( isset( $response_params['response_code'] ) && in_array( $response_params['response_code'], APS_Constants::APS_FAILED_RESPONSE_CODES, true ) ) {
				if ( isset( $response_params['payment_option'] ) && in_array( $response_params['payment_option'], APS_Constants::APS_RETRY_PAYMENT_OPTIONS, true ) ) {
					if ( 'yes' === $tokenization_status ) {
						$this->cancelled_order( $reason );
					} else {
						$this->failed_order( $reason );
					}
				} else {
					$payment_method = $this->get_payment_method();
					if ( ! empty( $payment_method ) && in_array( $payment_method, APS_Constants::APS_RETRY_PAYMENT_METHODS, true ) ) {
						if ( 'yes' === $tokenization_status ) {
							$this->cancelled_order( $reason );
						} else {
							$this->failed_order( $reason );
						}
					} else {
						$this->cancelled_order( $reason );
					}
				}
			} else {
				if ( isset( $response_params['digital_wallet'] ) && APS_Constants::APS_PAYMENT_METHOD_APPLE_PAY === $response_params['digital_wallet'] ) {
					$this->cancelled_order( $reason );
				} elseif ( 'yes' === $tokenization_status ) {
					$this->cancelled_order( $reason );
				} else {
					$this->failed_order( $reason );
				}
			}
		}
		return true;
	}

	/**
	 * Failed order
	 *
	 * @return bool
	 */
	public function failed_order( $reason = 'Payment Failed' ) {
		$status = 'failed';
		if ( $status === $this->get_status() ) {
			return true;
		}
		// Don't failed order if already payment success
		if ( in_array($this->get_status(), ['processing', 'completed', 'refunded']) ) {
			return true;
		}
		$note = $reason;
		$this->order->update_status( $status, $note );
		return true;
	}

	/**
	 * Cancel order
	 *
	 * @return bool
	 */
	public function cancelled_order( $reason = 'Payment Cancelled' ) {
		$status = 'cancelled';
		if ( $status === $this->get_status() ) {
			return true;
		}
		// Don't failed order if already payment success
		if ( in_array($this->get_status(), ['processing', 'completed', 'refunded']) ) {
			return true;
		}
		$this->order->update_status( 'cancelled', $reason );
		$this->order_log( 'APS order cancelled ');
		unset( WC()->session->order_awaiting_payment );
		return true;
	}

	/**
	 * ON hold order
	 */
	public function on_hold_order( $note ) {
		if ( $this->get_order_id() ) {
			$this->order->update_status( 'on-hold', $note );
		}
	}

	/**
	 * Success order
	 *
	 * @return bool
	 */
	public function success_order( $response_params, $response_mode ) {
		try {
			$this->order_log( 'APS success order response (' . $response_mode . ')\n\n' . json_encode( $response_params, true ) );
			if ( $this->get_order_id() ) {
				$fort_id_saved = get_post_meta( $this->get_order_id(), 'fort_id_saved', true );
				if ( 'offline' === $response_mode ) {
					$status = 'processing';
					if ( $status !== $this->get_status() ) {
						$this->order->payment_complete();
					}
					if ( isset( $response_params['token_name'] ) && ! empty( $response_params['token_name'] ) ) {
						$this->save_aps_tokens( $response_params, $response_mode );
					}
					if ( isset( $response_params['fort_id'] ) && empty( $fort_id_saved ) ) {
						$this->order->add_order_note( 'APS payment successful<br/>Fort id: ' . $response_params['fort_id'] );
						update_post_meta( $this->get_order_id(), 'fort_id_saved', 'yes' );
					}
				} elseif ( 'online' === $response_mode ) {
					$status = 'processing';
					if ( $status !== $this->get_status() ) {
						$this->order->payment_complete();
					}
					if ( isset( $response_params['token_name'] ) && ! empty( $response_params['token_name'] ) ) {
						$this->save_aps_tokens( $response_params, $response_mode );
					}
				}
			}
			if ( ! empty( $response_params ) ) {
				update_post_meta( $this->get_order_id(), 'aps_payment_response', $response_params );
			}
			return true;
		} catch ( Exception $e ) {
			$this->order_log( 'APS success_order function failure : ' . $e->getMessage() );
			throw new Exception( $e->getMessage() );
		}
	}

	/**
	 * Capture order
	 */
	public function capture_order( $response_params, $response_mode ) {
		$this->order_log( 'APS capture response (' . $response_mode . ')\n\n' . json_encode( $response_params, true ) );
		if ( $this->get_order_id() ) {
			if ( class_exists( 'APS_Helper' ) && isset( $response_params['fort_id'] ) ) {
				$aps_helper                     = new APS_Helper();
				$amount                         = $aps_helper->convert_dec_amount( $response_params['amount'], $response_params['currency'] );
				$order_id                       = $response_params['merchant_reference'];
				$order_post                     = get_post( $order_id );
				$insert_aps_capture_transaction = array(
					'post_type'   => APS_Constants::APS_CAPTURE_POST_TYPE,
					'post_status' => 'capture',
					'post_title'  => wp_strip_all_tags( 'Order capture Amount' . $amount ),
					'post_parent' => $order_id,
					'post_author' => $order_post->post_author,
				);
				$capture_id                     = wp_insert_post( $insert_aps_capture_transaction );
				update_post_meta( $capture_id, 'aps_authorization_captured_amount', $amount );
				update_post_meta( $capture_id, 'aps_fort_id', $response_params['fort_id'] );
				update_post_meta( $order_id, 'aps_authorization_command', APS_Constants::APS_COMMAND_CAPTURE );
				$this->order->add_order_note( 'APS payment captured<br/>Captured amount: ' . wc_price( $amount ) );
			}
		}
	}

	/**
	 * Captured amount history
	 */
	private function captured_amount_history( $order_id ) {
		global $wpdb;
		$history = $wpdb->get_results($wpdb->prepare("
			SELECT DATE_FORMAT(posts.post_date, '%%M %%d, %%Y') as date, postmeta.meta_value as amount
			FROM $wpdb->postmeta AS postmeta
			INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'aps_capture_trans' AND posts.post_status = 'capture' AND post_parent = %d )
			WHERE postmeta.meta_key = 'aps_authorization_captured_amount'
			AND postmeta.post_id = posts.ID ORDER BY posts.ID DESC LIMIT 0, 99
			", $order_id), ARRAY_A
		);
		return $history;
	}

	/**
	 * Void order
	 */
	public function void_order( $response_params, $response_mode ) {
		$this->order_log( 'APS void response (' . $response_mode . ')\n\n' . json_encode( $response_params, true ) );
		if ( $this->get_order_id() ) {
			if ( class_exists( 'APS_Helper' ) && isset( $response_params['fort_id'] ) ) {
				$aps_helper                     = new APS_Helper();
				$amount                         = $this->get_total();
				$order_id                       = $response_params['merchant_reference'];
				$order_post                     = get_post( $order_id );
				$insert_aps_capture_transaction = array(
					'post_type'   => APS_Constants::APS_CAPTURE_POST_TYPE,
					'post_status' => 'void_authorization',
					'post_title'  => wp_strip_all_tags( 'Order void authorization Amount' . $amount ),
					'post_parent' => $order_id,
					'post_author' => $order_post->post_author,
				);
				$capture_id                     = wp_insert_post( $insert_aps_capture_transaction );
				update_post_meta( $capture_id, 'aps_authorization_captured_amount', $amount );
				update_post_meta( $capture_id, 'aps_fort_id', $response_params['fort_id'] );
				update_post_meta( $order_id, 'aps_authorization_command', APS_Constants::APS_COMMAND_VOID );
				$this->order->add_order_note( 'APS payment void<br/>Void amount: ' . wc_price( $amount ) );
				$this->cancelled_order( 'Payment failed by void' );
			}
		}
	}

	/**
	 * Refund Order
	 */
	public function refund_order( $response_params, $response_mode ) {
		$this->order_log( 'APS refund order response (' . $response_mode . ')\n\n' . json_encode( $response_params, true ) );
		if ( $this->get_order_id() ) {
			if ( class_exists( 'APS_Helper' ) && isset( $response_params['fort_id'] ) ) {
				$aps_helper = new APS_Helper();
				$amount     = $aps_helper->convert_dec_amount( $response_params['amount'], $response_params['currency'] );
				$order_id   = $response_params['merchant_reference'];
				$order_post = get_post( $order_id );
				$reason     = __( 'Refund by APS Backoffice', 'amazon-payment-services' );
				$refund     = wc_create_refund(
					array(
						'amount'   => $amount,
						'reason'   => $reason,
						'order_id' => $order_id,
					)
				);
				$this->order->add_order_note( 'APS payment refunded<br/>Refund amount: ' . wc_price( $amount ) . '<br/>Refund Reason: ' . $reason );
				return true;
			}
		}
	}

	/**
	 * Save Token
	 */
	public function save_aps_tokens( $response_params, $response_mode ) {
		try {
			//check response with get Method and card detail contain * only
			if ( isset( $response_params['expiry_date'] ) ) {
				if ( !preg_match('#[^*]#', $response_params['expiry_date']) ) {
					// return if all character are *
					return;
				}
			}

			$aps_helper      = new APS_Helper();
			$is_stc_pay = isset($response_params['digital_wallet']) && $response_params['digital_wallet'] === APS_Constants::APS_PAYMENT_METHOD_STC_PAY;
			$existing_tokens = $aps_helper->find_token_row( $response_params['token_name'], $this->order->get_customer_id(), ($is_stc_pay ? APS_Constants::APS_PAYMENT_TYPE_STC_PAY:'' ));
			if ( ! empty( $existing_tokens ) ) {
				$old_token_row = WC_Payment_Tokens::get( $existing_tokens['token_id'] );
				if ( isset( $response_params['payment_option'] ) ) {
					update_metadata( 'payment_token', $existing_tokens['token_id'], 'card_type', strtolower( $response_params['payment_option'] ) );
				}
				if ( isset( $response_params['card_holder_name'] ) ) {
					update_metadata( 'payment_token', $existing_tokens['token_id'], 'card_holder_name', $response_params['card_holder_name'] );
				}
				if ( isset( $response_params['card_number'] ) ) {
					$last4 = substr( $response_params['card_number'], -4 );
					update_metadata( 'payment_token', $existing_tokens['token_id'], 'last4', $last4 );
					update_metadata( 'payment_token', $existing_tokens['token_id'], 'masking_card', $response_params['card_number'] );
				}
				if ( isset( $response_params['expiry_date'] ) ) {
					$short_year  = substr( $response_params['expiry_date'], 0, 2 );
					$short_month = substr( $response_params['expiry_date'], 2, 2 );
					$date_format = \DateTime::createFromFormat( 'y', $short_year );
					$full_year   = $date_format->format( 'Y' );
					update_metadata( 'payment_token', $existing_tokens['token_id'], 'expiry_month', $short_month );
					update_metadata( 'payment_token', $existing_tokens['token_id'], 'expiry_year', $full_year );
				}
				$this->order->add_payment_token( $old_token_row );
				$this->order_log( 'APS token updated ( ' . $response_mode . ')\n\n' . wp_json_encode( $response_params, true ) );
			} else {
			    // Add STC pay payment token
			    if($is_stc_pay){
			        $gateway_id = APS_Constants::APS_PAYMENT_TYPE_STC_PAY;
			        $token = new WC_Payment_Token_APS_STC_Pay();
			        $phone_number = $response_params['phone_number'];
			        $token->set_mobile_number($phone_number);
			        $token->set_gateway_id($gateway_id);
			        $token->set_default(true);
			        $token->set_token($response_params['token_name']);
			        if($this->order->get_customer_id()){
			            $token->set_user_id($this->order->get_customer_id());
                    }
			        $token->save();
                    $this->order_log( 'APS token created ( ' . $response_mode . ')\n\n' . wp_json_encode( $response_params, true ) );
			        $this->order->add_payment_token($token);
                }
			    else{
                    $gateway_id  = APS_Constants::APS_PAYMENT_TYPE_CC;
                    $token       = new WC_Payment_Token_CC();
                    $short_year  = substr( $response_params['expiry_date'], 0, 2 );
                    $date_format = \DateTime::createFromFormat( 'y', $short_year );
                    $full_year   = $date_format->format( 'Y' );
                    $token->set_token( $response_params['token_name'] );
                    $token->set_gateway_id( $gateway_id );
                    $token->set_card_type( strtolower( $response_params['payment_option'] ) );
                    $token->set_last4( substr( $response_params['card_number'], -4 ) );
                    $token->set_expiry_month( substr( $response_params['expiry_date'], 2, 2 ) );
                    $token->set_expiry_year( $full_year );
                    if ( $this->order->get_customer_id() ) {
                        $token->set_user_id( $this->order->get_customer_id() );
                    }
                    $token->save();
                    $this->order_log( 'APS token created ( ' . $response_mode . ')\n\n' . wp_json_encode( $response_params, true ) );
                    update_metadata( 'payment_token', $token->get_id(), 'masking_card', $response_params['card_number'] );
                    if ( isset( $response_params['card_holder_name'] ) ) {
                        update_metadata( 'payment_token', $token->get_id(), 'card_holder_name', $response_params['card_holder_name'] );
                    }
                    $this->order->add_payment_token( $token );
                }

			}
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Order log
	 */
	public function order_log( $messages, $force_debug = false ) {
		$debug_mode = $this->aps_config->get_debug_mode() === 'yes' ? true : false;
		if ( ! $debug_mode && ! $force_debug ) {
			return;
		}
		if ( ! class_exists( 'WC_Logger' ) ) {
			include_once 'class-wc-logger.php';
		}
		if ( empty( $this->log ) ) {
			$this->log = new WC_Logger();
		}
		$this->log->add( APS_NAME, $messages );
	}

	/**
	 * Get checkout received url
	 */
	public function get_checkout_success_url() {
		if ( $this->get_order_id() ) {
			return $this->order->get_checkout_order_received_url();
		}
	}
}
