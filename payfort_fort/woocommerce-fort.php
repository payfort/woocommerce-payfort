<?php

/* Plugin Name: Payfort (FORT)
 * Plugin URI:  https://github.com/payfort/woocommerce-payfort
 * Description: Payfort makes it really easy to start accepting online payments (credit &amp; debit cards) in the Middle East. Sign up is instant, at https://www.payfort.com/
 * Version:     1.2.1
 * Author:      Payfort
 * Author URI:  https://www.payfort.com/
 */
$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
if (in_array('woocommerce/woocommerce.php', $active_plugins)) {

    
    if (!defined('PAYFORT_FORT')) {
        define('PAYFORT_FORT', true);
    }

    if (defined('PAYFORT_FORT_VERSION'))
        return;

    define('PAYFORT_FORT_VERSION', '1.2.1');


    if (!defined('PAYFORT_FORT_DIR')) {
        define('PAYFORT_FORT_DIR', plugin_dir_path(__FILE__));
    }

    if (!defined('PAYFORT_FORT_URL')) {
        define('PAYFORT_FORT_URL', plugin_dir_url(__FILE__));
    }

    add_filter('woocommerce_payment_gateways', 'add_payfort_fort_gateway');

    function add_payfort_fort_gateway($gateways)
    {
        $gateways[] = 'WC_Gateway_Payfort';
        $gateways[] = 'WC_Gateway_Payfort_Fort_Sadad';
        $gateways[] = 'WC_Gateway_Payfort_Fort_Qpay';
        $gateways[] = 'WC_Gateway_Payfort_Fort_Installments';
        return $gateways;
    }

    add_action('plugins_loaded', 'init_payfort_fort_payment_gateway');

    function init_payfort_fort_payment_gateway()
    {
        require 'classes/class-woocommerce-fort.php';
        require 'classes/class-woocommerce-fort-sadad.php';
        require 'classes/class-woocommerce-fort-naps.php';
        require 'classes/class-woocommerce-fort-installments.php';
        
        add_filter( 'woocommerce_get_sections_checkout', function($sections){unset($sections['payfort_fort_installments']);unset($sections['payfort_fort_sadad']);unset($sections['payfort_fort_qpay']);return $sections;}, 500 );
    }

    add_action('plugins_loaded', 'payfort_fort_load_plugin_textdomain');

    function payfort_fort_load_plugin_textdomain()
    {
        load_plugin_textdomain('woocommerce-other-payment-gateway', FALSE, basename(dirname(__FILE__)) . '/languages/');
    }

    function woocommerce_payfort_fort_actions()
    {
        if (isset($_GET['wc-api']) && !empty($_GET['wc-api'])) {
            WC()->payment_gateways();
            switch ($_GET['wc-api'])
            {
                case 'wc_gateway_payfort_process_response':
                    do_action('woocommerce_wc_gateway_payfort_fort_process_response');
                    break;

                case 'wc_gateway_payfort_fort_merchantPageResponse':
                    do_action('woocommerce_wc_gateway_payfort_fort_merchantPageResponse');
                    break;

                case 'wc_gateway_payfort_fort_responseOnline':
                    do_action('woocommerce_wc_gateway_payfort_fort_responseOnline');
                    break;

                case 'wc_gateway_payfort_fort_merchantPageCancel':
                    do_action('woocommerce_wc_gateway_payfort_fort_merchantPageCancel');
                    break;
            }
        }
    }
    
    add_action('init', 'woocommerce_payfort_fort_actions', 500);
}
