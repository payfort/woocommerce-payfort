<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
	$icon_html       = '';
	$image_directory = plugin_dir_url( dirname( __FILE__ ) ) . 'images/';
	$visa_logo       = $image_directory . 'visa-logo.png';
	$mastercard_logo = $image_directory . 'mastercard-logo.png';
	//Wrap icons
	$icon_html .= '<img src="' . esc_attr($visa_logo) . '" alt="visa" class="card-visa card-icon" />';
	$icon_html .= '<img src="' . esc_attr($mastercard_logo) . '" alt="mastercard" class="card-mastercard card-icon" />';
	$tokens     = array();
if ( 'yes' === $is_enabled_tokenization ) {
	$tokens = WC_Payment_Tokens::get_customer_tokens( get_current_user_id(), APS_Constants::APS_PAYMENT_TYPE_CC );
	$tokens = array_filter(
		$tokens,
		function( $token_row ) {
			if ( in_array( $token_row->get_card_type(), array( 'visa', 'mastercard' ), true ) ) {
				return true;
			} else {
				return false;
			}
		}
	);
}
$card_icons = array(
	'visa'       => $visa_logo,
	'mastercard' => $mastercard_logo,
);
?>
<ul class="token-box installment_token">
	<?php
	foreach ( $tokens as $token_row ) {
		$checked      = intval( $token_row->get_is_default() ) === 1 ? 'checked' : '';
		$masking_card = get_metadata( 'payment_token', $token_row->get_id(), 'masking_card', true );
		$maxlength    = 'amex' === $token_row->get_card_type() ? 4 : 3;
		$card_type = $token_row->get_card_type();
		if ( 'mada' != $card_type ) {
			$card_type = strtoupper($card_type);
		}
		echo '<li class="token_list">';
			echo '<div class="aps-row ' . wp_kses_data( $checked ) . '">';
				echo '<div class="aps-col-sm-9">';
					echo '<input type="radio" class="aps_installment_token aps_token_radio" name="aps_payment_token_installment" data-masking-card="' . wp_kses_data( substr( $masking_card, 0, 6 ) ) . '" value=" ' . wp_kses_data( $token_row->get_token() ) . ' " ' . wp_kses_data( $checked ) . '/>';
					echo '<img class="card-icon" src="' . wp_kses_data( $card_icons[ $token_row->get_card_type() ] ) . '"/>';
					echo '<strong>' . esc_attr($card_type) . ' ' . esc_attr($token_row->get_last4()) . '</strong> ';
					echo wp_kses_data( __( 'exp', 'amazon-payment-services' ) . ' ' . esc_attr($token_row->get_expiry_month()) . '/' . esc_attr($token_row->get_expiry_year()) );
				echo '</div>';
				echo '<div class="aps-col-sm-3">';
					echo '<input type="text" value="" autocomplete="off" maxlength="' . esc_attr($maxlength) . '" class="input-text aps_saved_card_cvv onlynum" placeholder="' . esc_html__( 'CVV', 'amazon-payment-services' ) . '">';
				echo '</div>';
			echo '</div>';
			echo '<div class="aps-row"> <div class="aps-col-sm-12"><label class="aps_error aps_install_token_error"></label></div></div>';
		echo '</li>';
	}
	?>
	<li>
		<div class="aps-row">
			<div class="aps-col-xs-12">
				<?php
				if ( ! empty( $tokens ) ) {
					echo '<input type="radio" name="aps_payment_token_installment" value="" class="aps_token_card aps_token_radio" required/> ' . esc_html__( 'Add a new card', 'amazon-payment-services' );
				}
				?>
				<div id="aps_instalment_form" class="aps_hosted_form">
					<div class="payfort-fort-instalment">
						<p id="aps_card_number_field" class="form-row">
							<label class="" for="aps_card_number"><?php echo esc_html__( 'Card Number', 'amazon-payment-services' ); ?> <span class="required">*</span></label>
							<div class="card-row">
								<input type="text" value="" autocomplete="off" maxlength="19" placeholder="" class="input-text aps_card_number onlynum"/>
								<?php echo wp_kses_post($icon_html); ?>
							</div>
							<label class="aps_error aps_card_error"></label>
						</p>
						<p id="aps_card_holder_name_field" class="form-row clear">
							<label class="" for="aps_card_holder_name"><?php echo esc_html__( 'Card Holder name', 'amazon-payment-services' ); ?></label>
							<input type="text" value="" autocomplete="off" maxlength="50" placeholder="" class="input-text aps_card_holder_name">
							<label class="aps_error aps_card_name_error"></label>
						</p>
						<p id="aps_expiry_month_field" class="form-row clear form-row-wide">
							<label class="" for="aps_expiry_month"><?php echo esc_html__( 'Expiry Date', 'amazon-payment-services' ); ?>  <span class="required">*</span></label>
							<input width="50px" type="text" value="" autocomplete="off" maxlength="2" placeholder="MM" class="input-text aps_expiry_month onlynum" size="2" style="width: 50px">
							<input width="50px" type="text" value="" autocomplete="off" maxlength="2" placeholder="YY" class="input-text aps_expiry_year onlynum" size="2" style="width: 50px">
							<label class="aps_error aps_card_expiry_error"></label>
						</p>
						<p id="aps_card_security_code_field" class="form-row clear">
							<label class="" for="aps_card_security_code"><?php echo esc_html__( 'CVV', 'amazon-payment-services' ); ?>  <span class="required">*</span></label>
							<input type="text" value="" autocomplete="off" maxlength="4" placeholder="" class="input-text aps_card_security_code onlynum" style="width: 60px">
							<label class="aps_error aps_card_cvv_error"></label>
						</p>
						<?php if ( 'yes' === $is_enabled_tokenization && 'no' === $have_subscription ) { ?>
							<p class="form-row clear">
								<input type="checkbox" class="aps_card_remember_me" class="input-checkbox" checked/> <?php echo esc_html__( 'Save My Card', 'amazon-payment-services' ); ?>
							</p>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
	</li>
</ul>
<input type="hidden" id="aps_installment_plan_code" name="aps_installment_plan_code" />
<input type="hidden" id="aps_installment_issuer_code" name="aps_installment_issuer_code" />

<input type="hidden" id="aps_installment_confirmation_en" name="aps_installment_confirmation_en" />
<input type="hidden" id="aps_installment_confirmation_ar" name="aps_installment_confirmation_ar" />

<input type="hidden" id="aps_installment_interest" name="aps_installment_interest" />
<input type="hidden" id="aps_installment_amount" name="aps_installment_amount" />

<div id="installment_plans" class="plan_box">
	<div class="issuer_info"></div>
	<div class="plans"></div>
	<label class="aps_error aps_plan_error"></label>
	<div class="plan_info"></div>
</div>
