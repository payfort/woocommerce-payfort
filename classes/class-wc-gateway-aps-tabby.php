<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * APS tabby gateway class
 *
 * @link       https://paymentservices.amazon.com/
 * @since      2.2.0
 *
 * @package    APS
 * @subpackage APS/includes
 */

/**
 * APS tabby gateway class
 *
 * @since      2.2.0
 * @package    APS
 * @subpackage APS/classes
 */
class WC_Gateway_APS_TABBY extends WC_Gateway_APS_Super
{

    public function __construct()
    {
        parent::__construct();
        $this->id = APS_Constants::APS_PAYMENT_TYPE_TABBY; // payment gateway plugin ID
        $this->has_fields = false; // in case you need a custom credit card form
        $this->method_title = __('Amazon Payment Service - Tabby', 'amazon-payment-services');
        $this->title = __('Pay in 4. No interest, no fees.', 'amazon-payment-services');
        $this->description = __('Amazon Payment Service - Tabby', 'amazon-payment-services');
        $this->method_description = __('Accept Tabby', 'amazon-payment-services'); // will be displayed on the options page
        $this->supported_currencies = array('SAR','AED');
        $this->enabled = $this->check_availability();
        $this->load_dependencies();
        $this->supports = array(
            'products',
            'refunds',
            'subscriptions',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_amount_changes',
            'subscription_date_changes',
            'subscription_payment_method_change',
            'subscription_payment_method_change_customer',
            'subscription_payment_method_change_admin',
            'multiple_subscriptions',
            'gateway_scheduled_payments',
        );

        // We need custom JavaScript to obtain a token
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

    }

    /**
     * Check if available
     *
     * @return string
     */
    public function check_availability()
    {
        $available = 'yes' === $this->aps_config->get_enable_tabby() && in_array(strtoupper($this->aps_helper->get_front_currency()), $this->supported_currencies, true) ? 'yes' : 'no';

        return $available;
    }

    public function load_dependencies()
    {

    }

    /**
     * Payment script file to be loaded
     *
     * @return void
     */
    public function payment_scripts()
    {

    }

    /**
     * Check if this gateway is enabled and available in the user's currency
     *
     * @return bool
     */
    public function is_valid_for_use()
    {
        // Skip currency check
        return false;
    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $tabby_token = filter_input(INPUT_POST, 'aps_payment_token_tabby');
        $order = new WC_Order($order_id);
        $tabby_mobile = filter_input(INPUT_POST, 'token_mobile_number');
        session_start();
        if (!empty($tabby_mobile)) {
            $_SESSION['tabby_payment']['mobile_number'] = wp_kses_data($tabby_mobile);
        }

        $status = 'failed';
        if ($status === $order->get_status()) {
            $order->update_status('payment-pending', '');
        }
        $payment_method = $this->id;
        $integration_type = $this->get_integration_type();
        // check if integration type is redirection
        $payment_option = APS_Constants::APS_PAYMENT_METHOD_TABBY;
        $extras['order_id'] = $order_id;
        $extras['aps_payment_token'] = '' ;
        $payment_data = $this->aps_payment->get_payment_request_form($payment_method, $integration_type, $payment_option, $extras);
        $result = array(
            'result' => 'success',
            'url' => $payment_data['url'],
            'params' => $payment_data['params'],
        );
        if (isset($payment_data['form'])) {
            $result['form'] = $payment_data['form'];
        }
        update_post_meta($order_id, 'APS_INTEGRATION_TYPE', $integration_type);
        update_post_meta($order_id, 'aps_redirected', 1);
        wp_send_json($result);

        wp_die();
    }

    /**
     * Get integration type
     *
     * @return string
     */
    public function get_integration_type()
    {
		return APS_Constants::APS_INTEGRATION_TYPE_REDIRECTION;
    }

    /**
     * Return TABBY enable tokenization
     *
     * @return string
     */
    public function get_enabled_tokenization()
    {
        return 'no';
    }

    /**
     * Generate the TABBY payment form
     *
     * @param none
     * @return string
     */
    public function payment_fields()
    {
        $this->redirection_info();
        $integration_type_cls = 'integration_type_' . $this->id;
        echo '<input type="hidden" class="' . wp_kses_data($integration_type_cls) . '" value="' . wp_kses_data($this->get_integration_type()) . '" />';
        if (class_exists('APS_Public')) {
            APS_Public::load_tabby_wizard($this->aps_config->get_language(), $this->get_integration_type(), $this->get_enabled_tokenization(), $this->aps_config->have_subscription());
        }
    }

    /**
     * Process Refund
     *
     * @return bool
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        if (empty($amount) || intval($amount) <= 0) {
            $error = new WP_Error();
            $error->add('aps_refund_error', __('Invalid refund amount.', 'woocommerce'));
            return $error;
        }
        $order = new WC_Order($order_id);
        $total_amount = $order->get_total();
        if ($amount > $total_amount) {
            $error = new WP_Error();
            $error->add('aps_refund_error', __('Refund amount must not be higher than captured amount', 'amazon-payment-services'));
            return $error;
        } else {
            $refund_status = $this->aps_refund->submit_refund($order_id, $amount, $reason);
            return $refund_status;
        }
    }

    /**
     * Custom Icon
     *
     * @return icon_html string
     */

     public function get_icon()
     {
        $icon_html = '<span class="aps-cards-container">';
        $image_directory = plugin_dir_url(dirname(__FILE__)) . 'public/images/';
        $tabby_logo = $image_directory . 'tabby-logo.png';
        //Wrap icons
        $icon_html .= '<img src="' . $tabby_logo . '" alt="tabby" class="payment-icons" />';
        $icon_html .= '</span>';
        return $icon_html;
    }

}

