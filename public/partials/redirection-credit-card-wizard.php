<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$tokens = array();
if ( 'yes' === $is_enabled_tokenization ) {
	$tokens = WC_Payment_Tokens::get_customer_tokens( get_current_user_id(), APS_Constants::APS_PAYMENT_TYPE_CC );
	if ( 'yes' === $have_subscription ) {
		$tokens = array_filter(
			$tokens,
			function( $token_row ) {
				if ( in_array( $token_row->get_card_type(), array( 'visa', 'mastercard', 'amex' ), true ) ) {
					return true;
				} else {
					return false;
				}
			}
		);
	}
}
echo '<ul class="token-box">';
foreach ( $tokens as $token_row ) {
	$checked = intval( $token_row->get_is_default() ) === 1 ? 'checked="checked"' : '';
	$card_type = $token_row->get_card_type();
	if ( 'mada' != $card_type ) {
		$card_type = strtoupper($card_type);
	}
	echo '<li>';
	echo '<span class="aps-pull-left">';
	echo '<input type="radio" class="aps-radio" name="aps_payment_token_cc" value=" ' . wp_kses_data( $token_row->get_token() ) . ' " ' . wp_kses_data( $checked ) . ' />';
	echo '<img class="card-icon" src="' . wp_kses_data($card_icons[ $token_row->get_card_type() ]) . '"/>';
	echo '<strong>' . esc_attr($card_type) . ' ' . esc_attr($token_row->get_last4()) . '</strong>';
	echo '</span>';

	echo '<span class="aps-pull-right">';
	echo wp_kses_data(__( 'exp', 'amazon-payment-services' ) . ' ' . esc_attr($token_row->get_expiry_month()) . '/' . esc_attr($token_row->get_expiry_year()));
	echo '</span>';
	echo '</li>';
}
echo '<li>';
echo '<input type="radio" name="aps_payment_token_cc" value="" class="aps_token_card" required/> ' . esc_html__( 'Add a new card', 'amazon-payment-services' );
echo '</li>';
echo '</ul>';
