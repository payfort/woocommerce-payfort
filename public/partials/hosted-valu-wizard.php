<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$terms_text  = __( 'I agree with the valU', 'amazon-payment-services' );
$terms_text .= ' <span class="aps-modal-open" data-modal="terms-modal">' . __( 'terms and condition', 'amazon-payment-services' ) . '</span> ';
$terms_text .= __( 'to proceed with the transaction', 'amazon-payment-services' );
?>
<section id="request_otp_sec" class="valu_form active">
	<div class="aps-row">
		<div class="aps-col-sm-1 aps-pad-none">
			<span class="country_code"><?php echo esc_html(APS_Constants::APS_VALU_EG_COUNTRY_CODE); ?></span>
		</div>
		<div class="aps-col-sm-8 aps-pad-none">
			<input type="text" value="" autocomplete="off" maxlength="19" placeholder="<?php echo esc_html__( 'Enter your mobile number', 'amazon-payment-services' ); ?>" class="input-text aps_valu_mob_number onlynum" />
		</div>
		<div class="aps-col-sm-3 aps-pad-none">
			<button type="button" class="valu_customer_verify aps-btn"><?php echo esc_html__( 'Request OTP', 'amazon-payment-services' ); ?></button>
		</div>
	</div>
</section>

<section id="verfiy_otp_sec" class="valu_form">
	<div class="otp_generation_msg aps_success"></div>
	<div class="aps-row">
		<div class="aps-col-sm-9 aps-pad-none">
			<input type="password" class="form-control no-outline input-text aps_valu_otp" placeholder="<?php echo esc_html__( 'Enter OTP', 'amazon-payment-services' ); ?>" onKeyPress="return keyLimit(this,10)" autocomplete="off"/>
		</div>
		<div class="aps-col-sm-3 aps-pad-none">
			<button type="button" class="valu_otp_verify aps-btn"><?php echo esc_html__( 'Verify OTP', 'amazon-payment-services' ); ?></button>
		</div>
	</div>
</section>

<section id="tenure_sec" class="valu_form">
	<input type="hidden" id="aps_active_tenure" name="active_tenure" />
	<input type="hidden" id="aps_tenure_amount" name="tenure_amount" />
	<input type="hidden" id="aps_tenure_interest" name="tenure_interest" />
	<p id="aps_valu_otp_field" class="form-row">
		<div class="install-line"><?php echo esc_html__( 'OTP verified successfully, Please select your Installment plan!', 'amazon-payment-services' ); ?></div>
		<div class="tenure">
		</div>
		<div class="termRow mt-1">
			<input type="checkbox" name="valu_terms" id="valu_terms" /> <?php echo wp_kses_post($terms_text); ?>
		</div>
		<label class="tenure_term_error aps_error"></label>
	</p>
</section>

<label class="valu_process_error aps_error"></label>
<div class="valu_loader">
		<span class="valu_loader_icon"></span>
		<span class="valu_loader_caption"><?php echo esc_html__( 'Processing...', 'amazon-payment-services' ); ?></span>
</div>

<div id="terms-modal" class="aps-modal-window">
	<div class="aps-modal-content">
		<div class="aps-modal-header">
			<a href="javascript:void(0)" class="aps-modal-close">&times;</a>
		</div>
		<div class="aps-modal-body">
		<?php echo wp_kses_post($terms_modal_text); ?>
		</div>
	</div>
</div>
