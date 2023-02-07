<?php
if (!defined('ABSPATH')) {
    exit;
}
$terms_text = __('I agree with the STC Pay', 'amazon-payment-services');
$terms_text .= ' <span class="aps-modal-open" data-modal="terms-modal">' . __('terms and condition', 'amazon-payment-services') . '</span> ';
$terms_text .= __('to proceed with the transaction', 'amazon-payment-services');

$tokens = [];
if ('yes' === $is_enabled_tokenization) {
    $tokens = WC_Payment_Token_APS_STC_Pay::get_customer_tokens();
}

?>
<ul class="token-box stc_pay_token">
    <?php
    $default_checked_token = '';
    foreach ($tokens as $token_row) {
        $checked = intval($token_row->get_is_default()) === 1 ? 'checked' : '';
        if($checked){
            $default_checked_token = $token_row;
        }
        echo '<li>';
            echo '<div class="aps-row ' . wp_kses_data($checked) . '">';
                echo '<div class="aps-col-sm-9">';
                    echo '<input type="radio" class="aps_token_stc_pay stc_pay_aps_token_radio" name="aps_payment_token_stc_pay" data-token-name="' . wp_kses_data($token_row->get_mobile_number()) . '" value="' . wp_kses_data($token_row->get_token()) . '" ' . wp_kses_data($checked) . '/>';
                    echo '<strong>' . esc_attr($token_row->get_display_name()) . '</strong> ';
                echo '</div>';
            echo '</div>';
        echo '</li>';
        $token_row->get_display_name();
    }
    ?>
    <li>
        <div class="aps-row ">
            <div class="aps-col-xs-12">
                <?php
                if ( ! empty( $tokens ) ) {
                    echo '<input type="hidden"  value="'.$default_checked_token->get_mobile_number().'" name="token_mobile_number" class="stc_pay_token_mobile_number"  />';
                    echo '<input type="radio" name="aps_payment_token_stc_pay" data-token-name="" value="" class="aps_token_mobile stc_pay_aps_token_radio" required/> ' . esc_html__( 'Add a new Number', 'amazon-payment-services' );
                }
                ?>
                <div class="aps-hosted-form stc_hosted_form">
                    <section id="stc_pay_request_otp_sec" class="stc_pay_form otp_form active">

                        <div class="aps-row">
                            <div class="aps-col-sm-1 aps-pad-none">
                                <span class="country_code"><?php echo esc_html(APS_Constants::APS_STC_PAY_SAR_COUNTRY_CODE); ?></span>
                            </div>
                            <div class="aps-col-sm-8 aps-pad-none">
                                <input type="text" name="stc_pay_mobile_number" value="" autocomplete="off" maxlength="19"
                                       placeholder="<?php echo esc_html__('Enter your mobile number', 'amazon-payment-services'); ?>"
                                       class="input-text stc_pay_mobile_number onlynum"/>
                            </div>
                            <div class="aps-col-sm-3 aps-pad-none">
                                <button type="button"
                                        class="stc_pay_generate_otp aps-btn"><?php echo esc_html__('Request OTP', 'amazon-payment-services'); ?></button>
                            </div>
                        </div>
                    </section>

                    <section id="stc_pay_verify_otp_sec" class="stc_pay_form otp_form">
                        <div class="otp_generation_msg aps_success"></div>
                        <div class="aps-row">
                            <div class="aps-col-sm-9 aps-pad-none">
                                <input type="password" name="stc_pay_otp" class="form-control no-outline input-text aps_stc_pay_otp"
                                       placeholder="<?php echo esc_html__('Enter OTP', 'amazon-payment-services'); ?>"
                                       onKeyPress="return keyLimit(this,10)" autocomplete="off"/>
                            </div>
                        </div>
                        <?php if ('yes' === $is_enabled_tokenization && 'no' === $have_subscription ) { ?>
                            <p class="form-row clear">
                                <input type="checkbox" class="aps_card_remember_me" name="stc_pay_remember_me" class="input-checkbox"
                                       checked/> <?php echo esc_html__('Save My Number', 'amazon-payment-services'); ?>
                            </p>
                        <?php } ?>
                    </section>
                </div>

            </div>
        </div>
    </li>
</ul>

<label class="stc_pay_process_error aps_error"></label>
<div class="stc_pay_loader">
    <span class="stc_pay_loader_icon"></span>
    <span class="stc_pay_loader_caption"><?php echo esc_html__('Processing...', 'amazon-payment-services'); ?></span>
</div>

<!--<div id="terms-modal" class="aps-modal-window">
	<div class="aps-modal-content">
		<div class="aps-modal-header">
			<a href="javascript:void(0)" class="aps-modal-close">&times;</a>
		</div>
		<div class="aps-modal-body">
		<?php /*echo wp_kses_post($terms_modal_text); */ ?>
		</div>
	</div>
</div>
-->
