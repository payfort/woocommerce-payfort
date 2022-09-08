<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Woocommerce STC pay payment token.
 *
 * Representation of a payment token for stc pay
 *
 * Class WC_Payment_Token_STC_Pay
 */
class WC_Payment_Token_APS_STC_Pay extends WC_Payment_Token
{
    /**
     *
     * Token Type String
     *
     * @var string
     */
    protected $type = APS_Constants::APS_PAYMENT_TYPE_STC_PAY;

    /**
     *
     * Stores stcPay payment token data
     *
     * @var string[]
     */
    protected $extra_data = array(
        'mobile_number' => ''
    );

    /**
     * @param string $deprecated
     * @return string
     */
    public function get_display_name($deprecated = '')
    {
       return $this->get_masked_number(4);
//        return $this->get_mobile_number();
    }

    public function get_masked_number( $digitsToShow){
        $mobile_number = $this->get_mobile_number();
        return  '*******'.substr($mobile_number, strlen($mobile_number) - $digitsToShow);
    }

    /**
     * @return bool
     */
    public function validate() {
        if ( false === parent::validate() ) {
            return false;
        }

        if ( ! $this->get_mobile_number( 'edit' ) ) {
            return false;
        }
        return true;
    }

    /**
     * @param string $context
     * @return string
     */
    public function get_mobile_number($context = 'view'){
        return $this->get_prop('mobile_number', $context);
    }

    /**
     * @param $mobile_number
     */
    public function set_mobile_number($mobile_number){
        $this->set_prop('mobile_number', $mobile_number);
    }

    /**
     * @return string
     */
    protected function get_hook_prefix()
    {
        return 'woocommerce_payment_token_stc_pay_get_';
    }

//    /**
//     * @return WC_Payment_Token_APS_STC_Pay[]
//     */
    public static function get_customer_tokens(){
        return WC_Payment_Tokens::get_customer_tokens(get_current_user_id(), APS_Constants::APS_PAYMENT_TYPE_STC_PAY);
    }

}