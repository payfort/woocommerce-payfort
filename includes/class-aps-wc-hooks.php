<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * All functions of APS ajax
 *
 * @link       https://paymentservices.amazon.com/
 * @since      2.2.0
 *
 * @package    APS
 * @subpackage APS/includes
 */

/**
 * All functions of APS WC Hooks
 *
 * @since      2.2.0
 * @package    APS
 * @subpackage APS/includes
 */
class APS_WC_Hooks {

	const APS_PHP_SESSION_KEYS = array(
		'valu_payment',
		'stc_pay_payment',
		'tabby_payment',
		'aps_error',
		'aps_token_error',
		'aps_token_success',
		'aps_success_message',
		'aps_error_messages',
	);

	const APS_WC_SESSION_KEYS = array(
		'aps_checkout_snapshot',
	);

	const APS_LOGOUT_PURGE_PREFIX = 'aps_logout_purge_sid_';

	const APS_LOGOUT_PURGE_PENDING_OPTION = 'aps_logout_purge_pending';

	/**
	 * APS WC Hooks
	 */
	public function load_helpers() {
		$this->aps_helper  = APS_Helper::get_instance();
		$this->aps_config  = APS_Config::get_instance();
		$this->aps_payment = APS_Payment::get_instance();
	}

	/**
	 * APS Subscription Payment (credit card).
	 *
	 * BUSINESS-LOGIC FIX: the previous implementation had the
	 * two success/failure branches inverted, so a successful renewal put
	 * the subscription on hold while a failed renewal activated it. The
	 * STC Pay and Tabby handlers in this same file already had the
	 * correct mapping; the credit card handler now matches them:
	 *   process_subscription_payment() returns true on success  -> activate
	 *   process_subscription_payment() returns false on failure -> on hold
	 */
	public function aps_subscription_payment( $amount_to_charge, $order ) {
		if ( class_exists( 'WC_Subscriptions_Manager' ) ) {
			$result = $this->aps_payment->process_subscription_payment( $order, $amount_to_charge );
			if ( $result ) {
				WC_Subscriptions_Manager::activate_subscriptions_for_order( $order );
			} else {
				WC_Subscriptions_Manager::put_subscription_on_hold_for_order( $order );
			}
		}
	}

    /**
     * APS_STC Subscription Payment
     */
    public function aps_stc_subscription_payment( $amount_to_charge, $order ) {
        if ( class_exists( 'WC_Subscriptions_Manager' ) ) {
            $result = $this->aps_payment->stc_process_subscription_payment( $order, $amount_to_charge );
            if ( $result ) {
                WC_Subscriptions_Manager::activate_subscriptions_for_order( $order );
            } else {
                WC_Subscriptions_Manager::put_subscription_on_hold_for_order( $order );
            }
        }
    }

    /**
     * APS_TABBY Subscription Payment
     */
    public function aps_tabby_subscription_payment( $amount_to_charge, $order ) {
        if ( class_exists( 'WC_Subscriptions_Manager' ) ) {
            $result = $this->aps_payment->tabby_process_subscription_payment( $order, $amount_to_charge );
            if ( $result ) {
                WC_Subscriptions_Manager::activate_subscriptions_for_order( $order );
            } else {
                WC_Subscriptions_Manager::put_subscription_on_hold_for_order( $order );
            }
        }
    }

	/**
	 * APS Delete token
	 */
	public function aps_token_deleted( $token_id, $token ) {
		$this->aps_payment->delete_aps_token( $token_id, $token );
	}

	/**
	 * Add apple pay button
	 */
	public function add_apple_pay_on_product_page() {
		global $product;
		$apple_pay_class = $this->aps_config->get_apple_pay_button_type();
		if ( is_user_logged_in() ) {
			echo '<div class="apple_pay_option aps-d-none">';
			include plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/hosted-apple-pay-wizard.php';
			echo '</div>';
		} elseif ( ! is_user_logged_in() && 'yes' === get_option( 'woocommerce_enable_guest_checkout' ) ) {
			echo '<div class="apple_pay_option aps-d-none">';
			include plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/hosted-apple-pay-wizard.php';
			echo '</div>';
		}
	}

	/**
	 * APS Render Payment methods
	 *
	 * @return array
	 */
	public function aps_render_payment_methods( $item, $payment_token ) {
		if ( 'aps_cc' === $payment_token->get_gateway_id() && 'yes' === $this->aps_config->get_hide_delete_token_button() ) {
			unset( $item['actions']['delete'] );
		}
		return $item;
	}

	/**
	 * Messages
	 */
	public function show_token_response_messages() {
		session_start();
		if ( isset( $_SESSION['aps_token_error'] ) ) {
			$aps_error_msg = wp_kses_data($_SESSION['aps_token_error']);
			$this->aps_helper->set_flash_msg( $aps_error_msg, APS_Constants::APS_FLASH_MESSAGE_ERROR );
			unset( $_SESSION['aps_token_error'] );
		} elseif ( isset( $_SESSION['aps_token_success'] ) ) {
			$aps_success_msg = wp_kses_data($_SESSION['aps_token_success']);
			$this->aps_helper->set_flash_msg( $aps_success_msg, APS_Constants::APS_FLASH_MESSAGE_SUCCESS );
			unset( $_SESSION['aps_token_success'] );
		}
		session_write_close();
	}

	/**
	 * Validate checkout
	 *
	 * @return void
	 */
	public function aps_checkout_validation( $fields, $errors ) {
		if(WC()->cart->needs_payment()){
		$payment_method = filter_input( INPUT_POST, 'payment_method' );
		if ( empty( $payment_method ) ) {
			$errors->add( 'payment_method_not_selected', 'Please select payment method' );
		}
		if (isset($fields['payment_method'])) {
			$payment_method = $fields['payment_method'];
			$payment_methods = array(
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
            APS_Constants::APS_PAYMENT_TYPE_TABBY
            );
			if (in_array($payment_method, $payment_methods)) {
				if (isset($errors->errors) && empty($errors->errors)) {
					$last_order_id = WC()->session->get( 'order_awaiting_payment');
					if (empty($last_order_id) || null != $last_order_id) {
						$aps_redirected = get_post_meta( $last_order_id, 'aps_redirected', true );
						if (1 == $aps_redirected) {
							$order    = wc_get_order( $last_order_id );
							if ($order->get_status() == 'pending') {
								WC()->session->set( 'order_awaiting_payment', false);
							}
						}
					}
				}
			}
		}
	}}

	/**
	 * APS Pending Payment Cron Handler
	 */
	public function aps_pending_payment_cron_handler() {
		$duration_mins = $this->aps_config->get_status_cron_duration();
		$payment_methods = array(
			APS_Constants::APS_PAYMENT_TYPE_CC,
			APS_Constants::APS_PAYMENT_TYPE_VALU,
			APS_Constants::APS_PAYMENT_TYPE_INSTALLMENT,
			APS_Constants::APS_PAYMENT_TYPE_BENEFIT,
			APS_Constants::APS_PAYMENT_TYPE_KNET,
            APS_Constants::APS_PAYMENT_TYPE_OMANNET,
			APS_Constants::APS_PAYMENT_TYPE_VISA_CHECKOUT,
			APS_Constants::APS_PAYMENT_TYPE_APPLE_PAY,
            APS_Constants::APS_PAYMENT_TYPE_STC_PAY,
            APS_Constants::APS_PAYMENT_TYPE_TABBY
        );

		$args          = array(
			'post_type'   => 'shop_order',
			'meta_query'  => array(
				'relation' => 'AND',
				array(
					'key'   => '_payment_method',
					'value' => $payment_methods,
				),
			),
			'date_query'  => array(
				array(
					'before'    => gmdate( 'Y-m-d H:i:s', strtotime( "-{$duration_mins} minutes" ) ),
					'inclusive' => true,
				),
			),
			'post_status' => array( 'wc-pending', 'wc-on-hold' ),
			'limit'       => -1,
		);
		$get_data      = new WP_Query( $args );
		if ( $get_data->have_posts() ) {
			while ( $get_data->have_posts() ) {
				$get_data->the_post();
				$order_id    = get_the_ID();
				$this->aps_helper->log( 'check status called : ' . $order_id );
				$status_data = $this->aps_helper->aps_status_checker( get_the_ID() );
				if ( ! empty( $status_data ) && isset( $status_data['response_code'] ) ) {
					$response_code    = $status_data['response_code'];
					$transaction_code = $status_data['transaction_code'];
					$order            = wc_get_order( $order_id );
					update_post_meta( $order_id, 'aps_check_status_response', $status_data );
					if ( APS_Constants::APS_CHECK_STATUS_SUCCESS_RESPONSE_CODE === $response_code && ( APS_Constants::APS_PAYMENT_SUCCESS_RESPONSE_CODE === $transaction_code || APS_Constants::APS_PAYMENT_AUTHORIZATION_SUCCESS_RESPONSE_CODE === $transaction_code ) ) {
						$status = 'processing';
						if ( $status !== $order->get_status() ) {
							$order_note = 'Payment complete by APS Check status';
							$order->update_status( $status, $order_note );
						}
					} else {
						$status = 'cancelled';
						if ( $status !== $order->get_status() ) {
							$order_note = 'Payment cancelled by APS Check status';
							$order->update_status( $status, $order_note );
						}
					}
				}
			}
		}
		wp_reset_postdata();
	}

	public function aps_apple_pay_button_in_cart() {
		if ( class_exists( 'APS_Public' ) && 'yes' === $this->aps_config->can_show_apple_pay_cart_page() ) {
			echo '<div class="apple_pay_option aps-d-none">';
			APS_Public::load_apple_pay_wizard( $this->aps_config->get_apple_pay_button_type() );
			echo '</div>';
		}
	}

	public function aps_apple_pay_button_in_product() {
		if ( class_exists( 'APS_Public' ) && 'yes' === $this->aps_config->can_show_apple_pay_product_page() ) {
			echo '<div class="apple_pay_option aps-d-none">';
			APS_Public::load_apple_pay_wizard( $this->aps_config->get_apple_pay_button_type() );
			echo '</div>';
		}
	}

	public function aps_wocommerce_credit_card_type_labels( $labels_type ) {
		if ( !empty( $labels_type ) ) {
			if ('Mada' == $labels_type) {
				$labels_type = 'mada';
			}
		}
		return $labels_type;
	}

	/**
	 * Clear APS data from the PHP and WooCommerce sessions on logout.
	 *
	 * Hooked to clear_auth_cookie (before WordPress and WooCommerce tear the
	 * login down) and wp_logout. When the PHP session cannot be reopened, for
	 * example because output already started, the session is flagged and
	 * cleaned at the start of its next request (65bd630e).
	 *
	 * @param int $user_id Logged-out user, when the hook provides one.
	 */
	public function aps_clear_session_data( $user_id = 0 ) {
		$user_id = (int) $user_id;
		if ( 0 === $user_id && function_exists( 'get_current_user_id' ) ) {
			$user_id = (int) get_current_user_id();
		}
		$this->aps_clear_wc_session_data( $user_id );
		if ( ! $this->aps_clear_php_session_data() ) {
			$this->aps_defer_php_session_purge();
		}
	}

	/**
	 * Finish a cleanup that could not reach the PHP session during logout.
	 *
	 * Runs at init, before any APS code reads the session. If the session still
	 * cannot be opened here, later code cannot open it either, so the marker is
	 * kept until a cleanup actually succeeds.
	 */
	public function aps_purge_logged_out_session() {
		if ( ! function_exists( 'get_option' ) || ! get_option( self::APS_LOGOUT_PURGE_PENDING_OPTION ) ) {
			return;
		}
		$marker = $this->aps_logout_purge_marker();
		if ( '' === $marker || false === get_option( $marker ) ) {
			return;
		}
		if ( $this->aps_clear_php_session_data() ) {
			delete_option( $marker );
			$this->aps_refresh_logout_purge_pending();
		}
	}

	/**
	 * Returns true only once the session store no longer holds APS keys, or
	 * there is no session to clean.
	 *
	 * @return bool
	 */
	private function aps_clear_php_session_data() {
		$status = session_status();
		if ( PHP_SESSION_DISABLED === $status ) {
			return true;
		}
		$was_active = PHP_SESSION_ACTIVE === $status;
		if ( ! $was_active ) {
			// Starting a session for a browser that has none would only create one.
			if ( '' === $this->aps_php_session_id() ) {
				return true;
			}
			if ( headers_sent() || ! session_start() ) {
				// Not persisted, but keeps the rest of this request from reusing them.
				$this->aps_unset_php_session_keys();
				return false;
			}
		}
		$this->aps_unset_php_session_keys();
		// Write now: a later session_abort() or fatal error must not bring the keys back.
		session_write_close();
		// session_write_close() reports success even when the save handler fails,
		// so read the store back. After output starts that is impossible; report
		// unverified so the next request re-checks.
		if ( headers_sent() || ! session_start( $was_active ? array() : array( 'read_and_close' => true ) ) ) {
			return false;
		}
		$cleared = ! array_intersect_key( $_SESSION, array_flip( self::APS_PHP_SESSION_KEYS ) );
		$this->aps_unset_php_session_keys();
		return $cleared;
	}

	private function aps_unset_php_session_keys() {
		foreach ( self::APS_PHP_SESSION_KEYS as $session_key ) {
			unset( $_SESSION[ $session_key ] );
		}
	}

	private function aps_defer_php_session_purge() {
		$marker = $this->aps_logout_purge_marker();
		if ( '' === $marker || ! function_exists( 'add_option' ) ) {
			return;
		}
		// A plain option, not a transient: session data has no guaranteed expiry, so
		// the marker must not be evicted or expire before the data is gone.
		add_option( $marker, time(), '', 'no' );
		// Written after the marker; see aps_refresh_logout_purge_pending().
		update_option( self::APS_LOGOUT_PURGE_PENDING_OPTION, 1, true );
		if ( false === get_option( $marker ) || ! get_option( self::APS_LOGOUT_PURGE_PENDING_OPTION ) ) {
			$this->aps_log_logout_failure( 'could not record the pending PHP session cleanup; APS data may remain in the stored PHP session' );
		}
	}

	/**
	 * @param string $message Must not contain session identifiers.
	 */
	private function aps_log_logout_failure( $message ) {
		if ( isset( $this->aps_helper ) && is_object( $this->aps_helper ) ) {
			$this->aps_helper->log( 'APS logout cleanup: ' . $message, true );
		}
	}

	/**
	 * Drops the autoloaded pending flag once no marker remains, so requests skip
	 * the marker lookup. Deleting before re-checking means a marker added
	 * concurrently is either seen here or re-sets the flag itself.
	 */
	private function aps_refresh_logout_purge_pending() {
		global $wpdb;
		if ( ! is_object( $wpdb ) ) {
			return;
		}
		delete_option( self::APS_LOGOUT_PURGE_PENDING_OPTION );
		$remaining = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT 1",
				$wpdb->esc_like( self::APS_LOGOUT_PURGE_PREFIX ) . '%'
			)
		);
		if ( null !== $remaining ) {
			update_option( self::APS_LOGOUT_PURGE_PENDING_OPTION, 1, true );
		}
	}

	/**
	 * @return string Marker option name for the current session, or '' without one.
	 */
	private function aps_logout_purge_marker() {
		$session_id = $this->aps_php_session_id();
		return '' === $session_id ? '' : self::APS_LOGOUT_PURGE_PREFIX . hash( 'sha256', $session_id );
	}

	/**
	 * @return string Session ID in use or sent by the browser, '' if none.
	 */
	private function aps_php_session_id() {
		$session_id = session_id();
		if ( '' === $session_id ) {
			$name       = session_name();
			$session_id = isset( $_COOKIE[ $name ] ) && is_string( $_COOKIE[ $name ] ) ? $_COOKIE[ $name ] : '';
		}
		return 1 === preg_match( '/^[A-Za-z0-9,-]{1,256}$/', $session_id ) ? $session_id : '';
	}

	/**
	 * @param int $user_id Logged-out user.
	 */
	private function aps_clear_wc_session_data( $user_id ) {
		$session = function_exists( 'WC' ) && is_object( WC() ) && ! empty( WC()->session ) ? WC()->session : null;
		if ( $session ) {
			$changed = false;
			foreach ( self::APS_WC_SESSION_KEYS as $session_key ) {
				if ( null !== $session->get( $session_key ) ) {
					$session->__unset( $session_key );
					$changed = true;
				}
			}
			if ( $changed && is_callable( array( $session, 'save_data' ) ) ) {
				$session->save_data();
			}
		}
		// WooCommerce restores a user's stored session at next login, and is not
		// always loaded on the request that logs out.
		$customer_ids = array();
		if ( $user_id > 0 ) {
			$customer_ids[] = (string) $user_id;
		}
		// Re-check the stored row too: save_data() does not report failures.
		if ( $session && is_callable( array( $session, 'get_customer_id' ) ) ) {
			$customer_ids[] = (string) $session->get_customer_id();
		}
		foreach ( array_unique( array_filter( $customer_ids, 'strlen' ) ) as $customer_id ) {
			if ( ! $this->aps_clear_persisted_wc_session( $customer_id ) ) {
				$this->aps_log_logout_failure( 'could not clear APS data from the stored WooCommerce session' );
			}
		}
	}

	/**
	 * @param string $customer_id WooCommerce session key.
	 * @return bool False when stored APS data could not be removed.
	 */
	private function aps_clear_persisted_wc_session( $customer_id ) {
		global $wpdb;
		if ( ! is_object( $wpdb ) || ! class_exists( 'WC_Cache_Helper' ) || ! defined( 'WC_SESSION_CACHE_GROUP' ) ) {
			return true;
		}
		$table     = $wpdb->prefix . 'woocommerce_sessions';
		$cache_key = WC_Cache_Helper::get_cache_prefix( WC_SESSION_CACHE_GROUP ) . $customer_id;
		// Only overwrite the exact row we read, so a concurrent save is retried rather than lost.
		for ( $attempt = 0; $attempt < 3; $attempt++ ) {
			$value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM {$table} WHERE session_key = %s", $customer_id ) );
			if ( ! is_string( $value ) ) {
				return true;
			}
			$data = @unserialize( $value, array( 'allowed_classes' => false ) );
			if ( ! is_array( $data ) ) {
				return true;
			}
			$aps_data = array_intersect_key( $data, array_flip( self::APS_WC_SESSION_KEYS ) );
			if ( empty( $aps_data ) ) {
				return true;
			}
			$updated = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET session_value = %s WHERE session_key = %s AND CAST(session_value AS BINARY) = %s",
					serialize( array_diff_key( $data, $aps_data ) ),
					$customer_id,
					$value
				)
			);
			wp_cache_delete( $cache_key, WC_SESSION_CACHE_GROUP );
			if ( $updated ) {
				return true;
			}
		}
		return false;
	}
}
