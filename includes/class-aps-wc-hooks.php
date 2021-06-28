<?php

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
 * @author     Amazon Payment Services
 */
class APS_WC_Hooks {

	/**
	 * APS WC Hooks
	 */
	public function load_helpers() {
		$this->aps_helper  = APS_Helper::get_instance();
		$this->aps_config  = APS_Config::get_instance();
		$this->aps_payment = APS_Payment::get_instance();
	}

	/**
	 * APS Subscription Payment
	 */
	public function aps_subscription_payment( $amount_to_charge, $order ) {
		if ( class_exists( 'WC_Subscriptions_Manager' ) ) {
			$result = $this->aps_payment->process_subscription_payment( $order, $amount_to_charge );
			if ( $result ) {
				WC_Subscriptions_Manager::put_subscription_on_hold_for_order( $order );
			} else {
				WC_Subscriptions_Manager::activate_subscriptions_for_order( $order );
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
		if ( isset( $_SESSION['aps_token_error'] ) ) {
			$aps_error_msg = $_SESSION['aps_token_error'];
			$this->aps_helper->set_flash_msg( $aps_error_msg, APS_Constants::APS_FLASH_MESSAGE_ERROR );
			unset( $_SESSION['aps_token_error'] );
		} elseif ( isset( $_SESSION['aps_token_success'] ) ) {
			$aps_success_msg = $_SESSION['aps_token_success'];
			$this->aps_helper->set_flash_msg( $aps_success_msg, APS_Constants::APS_FLASH_MESSAGE_SUCCESS );
			unset( $_SESSION['aps_token_success'] );
		}
	}

	/**
	 * Validate checkout
	 *
	 * @return void
	 */
	public function aps_checkout_validation( $fields, $errors ) {
		$payment_method = filter_input( INPUT_POST, 'payment_method' );
		if ( empty( $payment_method ) ) {
			$errors->add( 'payment_method_not_selected', 'Please select payment method' );
		}
	}

	/**
	 * APS Pending Payment Cron Handler
	 */
	public function aps_pending_payment_cron_handler() {
		$duration_mins = $this->aps_config->get_status_cron_duration();
		$args          = array(
			'post_type'   => 'shop_order',
			'meta_query'  => array(
				'relation' => 'AND',
				array(
					'key'   => 'payment_gateway',
					'value' => APS_Constants::APS_GATEWAY_ID,
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

	public function aps_wocommerce_credit_card_type_labels($labels_type){
		if ( !empty( $labels_type ) ) {
			if($labels_type == 'Mada'){
				$labels_type = 'mada';
			}
		}
		return $labels_type;
	}
}
