<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
	$icon_html       = '';
	$image_directory = plugin_dir_url( dirname( __FILE__ ) ) . 'images/';
	$mada_logo       = $image_directory . 'mada-logo.png';
	$visa_logo       = $image_directory . 'visa-logo.png';
	$mastercard_logo = $image_directory . 'mastercard-logo.png';
	$amex_logo       = $image_directory . 'amex-logo.png';
	$meeza_logo      = $image_directory . 'meeza-logo.jpg';
	//Wrap icons
	$icon_html .= '<img src="' . $mada_logo . '" alt="mada" class="card-mada card-icon" />';
	$icon_html .= '<img src="' . $meeza_logo . '" alt="meeza" class="card-meeza card-icon" />';
	$icon_html .= '<img src="' . $visa_logo . '" alt="visa" class="card-visa card-icon" />';
	$icon_html .= '<img src="' . $mastercard_logo . '" alt="mastercard" class="card-mastercard card-icon" />';
	$icon_html .= '<img src="' . $amex_logo . '" alt="amex" class="card-amex card-icon" />';
?>
<div id="aps_cc_form" class="aps_hosted_form">
	<div class="payfort-fort-cc" >
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
			<input width="50px" type="text" value="" autocomplete="off" maxlength="2" placeholder="MM" class="input-text aps_expiry_month onlynum" size="2" style="width: 50px" />
			<input width="50px" type="text" autocomplete="off" maxlength="2" placeholder="YY"  class="input-text aps_expiry_year onlynum" size="2" style="width: 50px" />
			<label class="aps_error aps_card_expiry_error"></label>
		</p>
	</div>
</div>

