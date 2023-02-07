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
                    echo '<input type="radio" name="aps_payment_token_stc_pay" data-token-name="" value="" class="aps_token_mobile stc_pay_aps_token_radio stc_add_new_card" required/> ' . esc_html__( 'Add a new Number', 'amazon-payment-services' );
                    } ?>
<!--                    <p class="form-row clear stc_pay_remember_me" id="stc_remember_me_box">-->
<!--                        <input type="checkbox" class="aps_card_remember_me" name="stc_pay_remember_me" class="input-checkbox" checked/> --><?php //echo esc_html__( 'Save My Card', 'amazon-payment-services' ); ?><!--</p>-->
            </div>
        </div>
    </li>
</ul>