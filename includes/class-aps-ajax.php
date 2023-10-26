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
 * All functions of APS ajax
 *
 * @since      2.2.0
 * @package    APS
 * @subpackage APS/includes
 */
class APS_Ajax {

	public function load_helpers() {
		$this->aps_helper  = APS_Helper::get_instance();
		$this->aps_config  = APS_Config::get_instance();
		$this->aps_payment = APS_Payment::get_instance();
		$this->aps_order   = new APS_Order();
	}

	/**
	 * Find bin in plans
	 *
	 * @return issuer_key int
	 */
	private function find_bin_in_plans( $card_bin, $issuer_data ) {
		$issuer_key = null;
		if ( ! empty( $issuer_data ) ) {
			foreach ( $issuer_data as $key => $row ) {
				$card_regex  = '';
				$issuer_bins = array_column( $row['bins'], 'bin' );
				if ( ! empty( $issuer_bins ) ) {
					$card_regex = '/^' . implode( '|', $issuer_bins ) . '/';
					if ( preg_match( $card_regex, $card_bin ) ) {
						$issuer_key = $key;
						break;
					}
				}
			}
		}
		return $issuer_key;
	}

	/**
	 * Get installment plan
	 *
	 * @return array
	 */
	private function get_installment_plan( $card_bin ) {
		$retarr = array(
			'status'           => 'success',
			'installment_data' => array(),
			'code'             => 200,
			'message'          => 'List of plans',
		);
		try {
			if ( 0 === WC()->cart->get_cart_contents_count() ) {
				throw new \Exception( 'Cart is empty', 400 );
			}
			$cart_total     = WC()->cart->cart_contents_total;
			$currency       = $this->aps_helper->get_fort_currency();
			$gateway_params = array(
				'query_command'       => APS_Constants::APS_COMMAND_GET_INSTALLMENT_PLANS,
				'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
				'access_code'         => $this->aps_config->get_access_code(),
				'language'            => $this->aps_config->get_language(),
				'amount'              => $this->aps_helper->convert_fort_amount( $cart_total, 1, $currency ),
				'currency'            => strtoupper( $currency ),
			);
			//generate request signature
			$signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
			$gateway_params['signature'] = $signature;

			$gateway_url = $this->aps_config->get_gateway_url( 'api' );
			$response    = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
			if ( APS_Constants::APS_GET_INSTALLMENT_SUCCESS_RESPONSE_CODE === $response['response_code'] ) {
				$response['installment_detail']['issuer_detail'] = array_filter(
					$response['installment_detail']['issuer_detail'],
					function ( $row ) {
						return ! empty( $row['plan_details'] ) ? true : false;
					}
				);
				if ( empty( $response['installment_detail']['issuer_detail'] ) ) {
					throw new \Exception( __( 'No plans found', 'amazon-payment-services' ), 404 );
				}
				$issuer_key = $this->find_bin_in_plans( $card_bin, $response['installment_detail']['issuer_detail'] );
				if ( empty( $issuer_key ) && ! isset( $response['installment_detail']['issuer_detail'][ $issuer_key ] ) ) {
					throw new \Exception( __( 'There is no installment plan available', 'amazon-payment-services' ), 404 );
				}
				$retarr['installment_data'] = $response['installment_detail']['issuer_detail'][ $issuer_key ];
			} else {
				throw new \Exception( $response['response_message'], 422 );
			}
		} catch ( \Exception $ex ) {
			$retarr['status']  = 'error';
			$retarr['code']    = $ex->getCode();
			$retarr['message'] = $ex->getMessage();

		}
		$this->aps_helper->log( 'APS installment_plans response \n\n' . wp_json_encode( $response, true ) );
		return $retarr;
	}

	/**
	 * Get Installment plans ajax handler
	 */
	public function get_installment_handler() {
		$retarr      = array(
			'status'          => 'success',
			'plans_html'      => '',
			'plan_info'       => '',
			'issuer_info'     => '',
			'message'         => '',
			'confirmation_en' => '',
			'confirmation_ar' => '',
		);
		$card_bin    = filter_input( INPUT_POST, 'card_bin' );
		$pay_with_cc = intval( filter_input( INPUT_POST, 'pay_with_cc' ) );
		$pay_full_payment = '';
		if ( 1 === $pay_with_cc ) {
			$cart_min_limit = 0;
			$currency_check = 0;
			if ( 'SAR' === $this->aps_helper->get_front_currency() ) {
				$currency_check = 1;
				$cart_min_limit = $this->aps_config->get_installment_sar_minimum_order_limit();
			} elseif ( 'AED' === $this->aps_helper->get_front_currency() ) {
				$currency_check = 1;
				$cart_min_limit = $this->aps_config->get_installment_aed_minimum_order_limit();
			} elseif ( 'EGP' === $this->aps_helper->get_front_currency() ) {
				$currency_check = 1;
				$cart_min_limit = $this->aps_config->get_installment_egp_minimum_order_limit();
			}

			if ( 1 === $currency_check && floatval( WC()->cart->total ) < $cart_min_limit ) {
				$retarr['status']  = 'error';
				$retarr['message'] = 'Currency limit should be great than ' . $cart_min_limit;
				echo wp_json_encode( $retarr );
				wp_die();
			}

			$pay_full_payment = '<div class="slide">
						<div class="emi_box" data-interest ="" data-amount="" data-plan-code="" data-issuer-code="" data-full-payment="1">
							<p class="with_full_payment">' . __( 'Proceed with full amount', 'amazon-payment-services' ) . '</p>
						</div>
					</div>';
		}

		$card_bin = str_replace( array( ' ', '*' ), array( '', '' ), $card_bin );
		$response = $this->get_installment_plan( $card_bin );
		if ( 'success' === $response['status'] && ! empty( $response['installment_data'] ) ) {
			$all_plans      = $response['installment_data']['plan_details'];
			$banking_system = $response['installment_data']['banking_system'];
			$interest_text  = 'Non Islamic' === $banking_system ? __( 'Interest', 'amazon-payment-services' ) : __( 'Profit Rate', 'amazon-payment-services' );
			$months_text    = __( 'Months', 'amazon-payment-services' );
			$month_text     = __( 'month', 'amazon-payment-services' );
			$plans_html     = "<div class='emi_carousel'>";
			if ( ! empty( $all_plans ) ) {
				$plans_html .= $pay_full_payment;
				foreach ( $all_plans as $key => $plan ) {
					$interest      = $this->aps_helper->convert_dec_amount( $plan['fee_display_value'], $this->aps_helper->get_fort_currency() );
					$interest_info = $interest . ( 'Percentage' === $plan['fees_type'] ? '%' : '' ) . ' ' . $interest_text;
					$plans_html   .= "<div class='slide'>
						<div class='emi_box' data-interest ='" . $interest_info . "' data-amount='" . $plan['amountPerMonth'] . "' data-plan-code='" . $plan['plan_code'] . "' data-issuer-code='" . $response['installment_data']['issuer_code'] . "' >
							<p class='installment'>" . $plan['number_of_installment'] . ' ' . $months_text . "</p>
							<p class='emi'><strong>" . ( $plan['amountPerMonth'] ) . '</strong> ' . $plan['currency_code'] . '/' . $month_text . "</p>
							<p class='int_rate'>" . $interest . ( 'Percentage' === $plan['fees_type'] ? '%' : '' ) . ' ' . $interest_text . '</p>
						</div>
					</div>';
				}
			}
			$plans_html .= '</div>';
			//Plan info
			$terms_url          = $response['installment_data'][ 'terms_and_condition_' . $this->aps_config->get_language() ];
			$processing_content = $response['installment_data'][ 'processing_fees_message_' . $this->aps_config->get_language() ];
			$issuer_text        = $response['installment_data'][ 'issuer_name_' . $this->aps_config->get_language() ];
			$issuer_logo        = $response['installment_data'][ 'issuer_logo_' . $this->aps_config->get_language() ];
			$terms_text         = '';
			if ( 'yes' === $this->aps_config->show_issuer_logo() ) {
				$terms_text .= "<img src='" . $issuer_logo . "' class='issuer-logo' />";
			}
			$terms_text .= __( 'I agree with the installment {terms_link} to proceed with the transaction', 'amazon-payment-services' );
			$terms_text  = str_replace( '{terms_link}', '<a target="_blank" href="' . $terms_url . '">' . __( 'terms and condition', 'amazon-payment-services' ) . '</a>', $terms_text );
			$plan_info   = '<input type="checkbox" name="installment_term" id="installment_term" required/>' . $terms_text;
			$plan_info  .= '<label class="aps_installment_terms_error aps_error"></label>';
			$plan_info  .= '<p> ' . $processing_content . '</p>';

			$issuer_info = '';
			if ( 'yes' === $this->aps_config->show_issuer_name() ) {
				$issuer_info .= "<div class='issuer_info'> <p> " . __( 'Issuer name', 'amazon-payment-services' ) . ' : ' . $issuer_text . '</p> </div>';
			}

			$retarr['plans_html']      = $plans_html;
			$retarr['plan_info']       = $plan_info;
			$retarr['issuer_info']     = $issuer_info;
			$retarr['confirmation_en'] = $response['installment_data']['confirmation_message_en'];
			$retarr['confirmation_ar'] = $response['installment_data']['confirmation_message_ar'];
		} else {
			$retarr['status']  = 'error';
			$retarr['message'] = $response['message'];
		}
		echo wp_json_encode( $retarr );
		wp_die();
	}

	/**
	 * Validate apply url
	 */
	public function validate_apple_url() {
		try {
			$apple_url = filter_input( INPUT_POST, 'apple_url' );
			if ( empty( $apple_url ) ) {
				throw new \Exception( 'Apple pay url is missing' );
			}
			if ( ! filter_var( $apple_url, FILTER_VALIDATE_URL ) ) {
				throw new \Exception( 'Apple pay url is invalid' );
			}
			$parse_apple = wp_parse_url( $apple_url );
			$matched_apple = preg_match('/^(?:[^.]+\.)*apple\.com[^.]+$/', $apple_url);
			if ( ! isset( $parse_apple['scheme'] ) || ! in_array( $parse_apple['scheme'], array( 'https' ), true ) || ! $matched_apple ) {
				throw new \Exception( 'Apple pay url is invalid' );
			}
			echo wp_kses_data($this->aps_helper->init_apple_pay_api( $apple_url ) );
		} catch ( \Exception $e ) {
			echo wp_json_encode( array( 'error' => $e->getMessage() ) );
		}
		wp_die();
	}

	/**
	 * Validate apple pay address
	 */
	public function validate_apple_pay_shipping_address() {
		$status         = 'success';
		$error_msg      = '';
		$shipping_total = 0.00;
		$address_obj = filter_input( INPUT_POST, 'address_obj', FILTER_DEFAULT, FILTER_FORCE_ARRAY );
		if ( isset( $address_obj ) ) {
			$address_data = array_map('sanitize_text_field', wp_unslash($address_obj ));
			$this->aps_helper->log( 'APS address data \n\n' . wp_json_encode( $address_data, true ) );
			global $woocommerce;
			if ( isset( $address_data['countryCode'] ) && ! empty( $address_data['countryCode'] ) ) {
				WC()->customer->set_shipping_country( strtoupper( $address_data['countryCode'] ) );
			}
			if ( isset( $address_data['administrativeArea'] ) && ! empty( $address_data['administrativeArea'] ) ) {
				WC()->customer->set_shipping_state( $address_data['administrativeArea'] );
			}
			if ( isset( $address_data['postalCode'] ) && ! empty( $address_data['postalCode'] ) ) {
				WC()->customer->set_shipping_postcode( $address_data['postalCode'] );
			}
			if ( isset( $address_data['locality'] ) && ! empty( $address_data['locality'] ) ) {
				WC()->customer->set_shipping_city( $address_data['locality'] );
			}
		}
		if ( WC()->cart->needs_shipping() ) {
			$shipping_country = WC()->customer->get_shipping_country();
			if ( empty( $shipping_country ) ) {
				$error_msg = __( 'Shipping Address is invalid', 'amazon-payment-services' );
				$status    = 'error';
			} elseif ( ! in_array( WC()->customer->get_shipping_country(), array_keys( WC()->countries->get_shipping_countries() ), true ) ) {
				/* translators: %s: shipping location */
				$error_msg = sprintf( __( 'Unfortunately <strong>we do not ship %s</strong>. Please enter an alternative shipping address.', 'amazon-payment-services' ), WC()->countries->shipping_to_prefix() . ' ' . WC()->customer->get_shipping_country() );
				$status    = 'error';
			} else {
				$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
				foreach ( WC()->shipping()->get_packages() as $i => $package ) {
					if ( ! isset( $chosen_shipping_methods[ $i ], $package['rates'][ $chosen_shipping_methods[ $i ] ] ) ) {
						$error_msg = __( 'No shipping method has been selected. Please double check your address, or contact us if you need any help.', 'woocommerce' );
						$status    = 'error';
					}
				}
			}
		}
		WC()->cart->calculate_shipping();
		WC()->cart->calculate_totals();
		$result                   = array(
			'status'    => $status,
			'error_msg' => $error_msg,
		);
		$result['sub_total']      = WC()->cart->get_subtotal();
		$result['tax_total']      = WC()->cart->get_total_tax();
		$result['shipping_total'] = WC()->cart->get_shipping_total();
		$result['discount_total'] = WC()->cart->get_discount_total();
		$result['grand_total']    = WC()->cart->total;
		$this->aps_helper->log( 'APS validate apple pay address data \n\n' . wp_json_encode( $result, true ) );
		wp_send_json( $result );
		wp_die();
	}

	/**
	 * Get apple pay cart data
	 */
	public function get_apple_pay_cart_data() {
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
			$exec_from = filter_input( INPUT_POST, 'exec_from' );
			$product_cart_datas = filter_input( INPUT_POST, 'product_cart_data', FILTER_DEFAULT, FILTER_FORCE_ARRAY);

			if ( isset($exec_from) && 'product_page' === $exec_from && isset( $product_cart_datas ) && ! empty( $product_cart_datas ) ) {
				$product_cart_data = array_map( 'sanitize_text_field', $product_cart_datas );
				$product_id        = isset( $product_cart_data['product_id'] ) ? $product_cart_data['product_id'] : 0;
				$quantity          = isset( $product_cart_data['quantity'] ) ? $product_cart_data['quantity'] : 1;
				$variation_id      = isset( $product_cart_data['variation_id'] ) ? $product_cart_data['variation_id'] : 0;
				$variation         = isset( $product_cart_data['variation'] ) ? $product_cart_data['variation'] : array();
				$cart_item_data    = isset( $product_cart_data['cart_item_data'] ) ? $product_cart_data['cart_item_data'] : array();
				WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation, $cart_item_data );
			}
			$cart                          = WC()->cart;
			$apple_order['sub_total']      = $cart->get_subtotal();
			$apple_order['tax_total']      = $cart->get_total_tax();
			$apple_order['shipping_total'] = $cart->get_shipping_total();
			$apple_order['discount_total'] = $cart->get_discount_total();
			$apple_order['grand_total']    = $cart->total;
			foreach ( $cart->get_cart() as $cart_item ) {
				$apple_order['order_items'][] = array(
					'product_name'     => $cart_item['data']->get_title(),
					'product_subtotal' => $cart_item['line_total'],
				);
			}
		} catch ( \Exception $e ) {
			$status = 'failure';
		}
		$result = array(
			'result'      => $status,
			'apple_order' => $apple_order,
		);
		$this->aps_helper->log( 'APS apple pay cart data \n\n' . wp_json_encode( $result, true ) );
		wp_send_json( $result );
		wp_die();
	}

	/**
	 * Create cart order
	 */
	public function create_cart_order() {
		$address = array();
		$user_id = get_current_user_id();
		WC()->cart->calculate_shipping();
		WC()->cart->calculate_totals();
		$cart             = WC()->cart;
		$checkout         = WC()->checkout();
		$order_id         = $checkout->create_order(
			array(
				'payment_method' => APS_Constants::APS_PAYMENT_TYPE_APPLE_PAY,
			)
		);
		$order            = wc_get_order( $order_id );
		$shipping_address = WC()->session->get( 'customer' );
		$this->aps_helper->log( 'APS 372 apple shipping_address \n\n' . wp_json_encode( $shipping_address, true ) );
		$shipping_address = array_filter(
			$shipping_address,
			function( $v, $k ) {
				return substr( $k, 0, 9 ) === 'shipping_';
			},
			ARRAY_FILTER_USE_BOTH
		);
		foreach ( $shipping_address as $key => $val ) {
			$address[ str_replace( 'shipping_', '', $key ) ] = $val;
		}
		$address_obj = filter_input( INPUT_POST, 'address_obj', FILTER_DEFAULT, FILTER_FORCE_ARRAY );
		if ( isset( $address_obj ) && ! empty( $address_obj ) ) {
			$address_data = array_map('sanitize_text_field', wp_unslash($address_obj ));
			$addressLines = [];
			if ( isset($address_obj['addressLines']) ) {
				$addressLines = array_map('sanitize_text_field', wp_unslash( $address_obj['addressLines'] ));
			}
			global $woocommerce;
			if ( isset( $address_data['givenName'] ) && ! empty( $address_data['givenName'] ) ) {
				$address['first_name'] = $address_data['givenName'];
			}
			if ( isset( $address_data['familyName'] ) && ! empty( $address_data['familyName'] ) ) {
				$address['last_name'] = $address_data['familyName'];
			}
			if ( isset( $addressLines ) && ! empty( $addressLines ) ) {
				if ( isset( $addressLines[0] ) && ! empty( $addressLines[0] ) ) {
					$address['address_1'] = $addressLines[0];
				}
				if ( isset( $addressLines[1] ) && ! empty( $addressLines[1] ) ) {
					$address['address_2'] = $addressLines[1];
				}
			}
			if ( isset( $address_data['emailAddress'] ) && ! empty( $address_data['emailAddress'] ) ) {
				$address['email'] = $address_data['emailAddress'];
			}
		}
		$this->aps_helper->log( 'APS 383 apple shipping_address \n\n' . wp_json_encode( $address, true ) );
		$order->set_address( $address, 'shipping' );
		$order->set_address( $address, 'billing' );
		update_post_meta( $order_id, '_customer_user', $user_id );
		$order->calculate_totals();
		WC()->session->set( 'order_awaiting_payment', $order_id );
		$this->aps_helper->log( 'APS apple pay order created \n\n' . $order_id . ' \n\n\n and login user id : ' . $user_id );
	}
	/**
	 * Valu Verfiy customer
	 */
	public function valu_verify_customer() {
		$response_arr = array(
			'status'  => 'success',
			'message' => '',
		);
		try {
			$mobile_number = filter_input( INPUT_POST, 'mobile_number' );
			$down_payment = filter_input( INPUT_POST, 'down_payment' );
            $tou = filter_input( INPUT_POST, 'tou' );
            $cash_back = filter_input( INPUT_POST, 'cash_back' );
			if (intval($down_payment) >= 0){
				//EGP currency ISO code requires 2 decimal points
				$down_payment = $down_payment * 100;
			} else {
				throw new \Exception( 'Incorrect down payment amount' );
			}
			if ( empty( $mobile_number ) ) {
				throw new \Exception( 'Mobile number is missing' );
			}
			$verfiy_response         = $this->aps_payment->valu_verify_customer( $mobile_number, $down_payment, $tou, $cash_back  );
			$response_arr['status']  = $verfiy_response['status'];
			$response_arr['message'] = $verfiy_response['message'];
		} catch ( \Exception $e ) {
			$response_arr['status']  = 'error';
			$response_arr['message'] = $e->getMessage();
		}
		echo wp_json_encode( $response_arr );
		wp_die();
	}

	/**
	 * Valu OTP Verfiy
	 */
	public function valu_otp_verify() {
		$response_arr = array(
			'status'  => 'success',
			'message' => '',
		);
		try {
			$otp = filter_input( INPUT_POST, 'otp' );
			if ( empty( $otp ) ) {
				throw new \Exception( 'OTP is missing' );
			}
			$verify_response             = $this->aps_payment->valu_verfiy_otp( $otp );
			$response_arr['status']      = $verify_response['status'];
			$response_arr['message']     = $verify_response['message'];
			$response_arr['tenure_html'] = $verify_response['tenure_html'];
		} catch ( \Exception $e ) {
			$response_arr['status']  = 'error';
			$response_arr['message'] = $e->getMessage();
		}
		echo wp_json_encode( $response_arr );
		wp_die();
	}

	/**
	 * Valu OTP Verfiy
	 */
	public function valu_set_tenure() {
		$response_arr = array(
			'status'  => 'success',
			'message' => '',
		);
		try {
			$tenure = filter_input( INPUT_GET, 'tenure' );
			if ( empty( $otp ) ) {
				throw new \Exception( 'Tenure is missing' );
			}
			session_start();
			$_SESSION['valu_payment'] = wp_kses_data($tenure);
			session_write_close();
			$response_arr['status']   = 'success';
		} catch ( \Exception $e ) {
			$response_arr['status']  = 'error';
			$response_arr['message'] = $e->getMessage();
		}
		echo wp_json_encode( $response_arr );
		wp_die();
	}


	/**
	 * Create APS Token builder
	 */
	public function create_aps_token_builder() {
		$gateway_params              = array(
			'service_command'     => 'CREATE_TOKEN',
			'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
			'access_code'         => $this->aps_config->get_access_code(),
			'merchant_reference'  => $this->aps_helper->generate_random_key(),
			'language'            => $this->aps_config->get_language(),
			'return_url'          => create_wc_api_url( 'aps_token_response', array( 'auth' => get_current_user_id() ) ),
		);
		$signature                   = $this->aps_helper->generate_signature( $gateway_params, 'request' );
		$gateway_params['signature'] = $signature;
		$gateway_url                 = $this->aps_config->get_gateway_url();
		$builder                     = array(
			'params'      => $gateway_params,
			'gateway_url' => $gateway_url,
		);
		wp_send_json( $builder );
		wp_die();
	}

	/**
	 * Aps payment authorization capture & void
	 */
	public function aps_payment_authorization() {
		$response_arr = array(
			'status'  => 'success',
			'message' => '',
		);
		try {
			$order_id              = filter_input( INPUT_POST, 'order_id' );
			$authorization_command = filter_input( INPUT_POST, 'authorization_command' );
			$amount_authorization  = filter_input( INPUT_POST, 'amount_authorization' );
			if ( empty( $order_id ) ) {
				throw new \Exception( __( 'Order Id is missing', 'amazon-payment-services' ) );
			}
			if ( empty( $authorization_command ) ) {
				throw new \Exception( __( 'Authorization command Type is missing', 'amazon-payment-services' ) );
			}
			if ( ! empty( $authorization_command ) && APS_Constants::APS_COMMAND_CAPTURE == $authorization_command ) {
				if ( empty( $amount_authorization ) ) {
					throw new \Exception( __( 'Authorization amount is missing', 'amazon-payment-services' ) );
				}
			}
			$response                = $this->submit_authorization( $order_id, $authorization_command, $amount_authorization );
			$response_arr['message'] = $response['message'];
			if ('error' == $response['status']) {
				throw new \Exception( $response['message'] );
			}
		} catch ( \Exception $e ) {
			$response_arr['status']  = 'error';
			$response_arr['message'] = $e->getMessage();
		}
		echo wp_json_encode( $response_arr );
		wp_die();
	}

	/**
	 * Submit capture /void authorization
	 *
	 * @return array
	 */
	public function submit_authorization( $order_id, $authorization_command, $amount ) {
		$response_arr = array(
			'status'  => 'success',
			'message' => '',
		);
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

			$signature_type = 'regular';
			$access_code = $this->aps_config->get_access_code();
			if ($order->get_payment_method() == APS_Constants::APS_PAYMENT_TYPE_APPLE_PAY) {
				$access_code = $this->aps_config->get_apple_pay_access_code();
				$signature_type = 'apple_pay';
			}

			$payment_details = get_post_meta( $order_id, 'aps_payment_response', true );
			$gateway_params  = array(
				'merchant_identifier' => $this->aps_config->get_merchant_identifier(),
				'access_code'         => $access_code,
				'merchant_reference'  => $payment_details['merchant_reference'],
				'language'            => $this->aps_config->get_language(),
			);

			if ( APS_Constants::APS_COMMAND_CAPTURE === $authorization_command ) {
				$gateway_params['currency'] = strtoupper( $payment_details['currency'] );
				$total_amount               = $this->aps_helper->convert_fort_amount( $amount, $this->aps_order->get_currency_value(), $currency );
				$gateway_params['amount']   = $total_amount;
				$response_arr['message']    = __( 'Payment Capture successfully ', 'amazon-payment-services' );
			} else {
				$response_arr['message'] = __( 'Authorization Voided Successfully', 'amazon-payment-services' );
			}
			$gateway_params['command']           = $authorization_command;
			$gateway_params['fort_id']           = $payment_details['fort_id'];
			$gateway_params['order_description'] = $this->aps_helper->clean_string( substr( $order_item_name, 0, 49 ) );
			$signature                           = $this->aps_helper->generate_signature( $gateway_params, 'request', $signature_type );
			$gateway_params['signature']         = $signature;
			$gateway_url                         = $this->aps_config->get_gateway_url( 'api' );
			$this->aps_helper->log( 'APS Capture request \n\n' . $gateway_url . wp_json_encode( $gateway_params, true ) );
			$response = $this->aps_helper->call_rest_api( $gateway_params, $gateway_url );
			$this->aps_helper->log( 'APS Capture response \n\n' . wp_json_encode( $response, true ) );
			if ( APS_Constants::APS_CAPTURE_SUCCESS_RESPONSE_CODE === $response['response_code'] || APS_Constants::APS_AUTHORIZATION_VOIDED_SUCCESS_RESPONSE_CODE === $response['response_code'] ) {
				update_post_meta( $order_id, 'aps_authorization_command', $authorization_command );
			} else {
				throw new \Exception( $response['response_message'] );
			}
		} catch ( Exception $e ) {
			$this->aps_helper->log( 'Submit Capture Error \n\n' . $e->getMessage() );
			$response_arr['status']  = 'error';
			$response_arr['message'] = $e->getMessage();
		}
		return $response_arr;
	}

	/**
	 * Handle capture response
	 *
	 * @return void
	 */
	public function handle_capture_response( $order_id, $authorization_command, $amount, $response ) {
		$insert_aps_capture_transaction = array(
			'post_type'   => 'aps_capture_trans',
			'post_status' => $authorization_command,
			'post_title'  => wp_strip_all_tags( 'Order ' . $authorization_command . ' Amount' . $amount ),
			'post_parent' => $order_id,
		);
		$post_id                        = wp_insert_post( $insert_aps_capture_transaction );
		$this->aps_helper->log( 'Submit Capture post id \n\n' . $post_id );
		update_post_meta( $post_id, 'aps_authorization_captured_amount', $amount );
		if ( isset( $response['fort_id'] ) ) {
			update_post_meta( $post_id, 'aps_fort_id', $response['fort_id'] );
		}
	}
}
