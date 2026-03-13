<?php

namespace blocks;

use APS_Constants;
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use WC_Gateway_APS;

class WC_Gateway_APS_Blocks_Support extends AbstractPaymentMethodType
{

    /**
     * The gateway instance.
     *
     * @var WC_Gateway_APS
     */
    private $gateway;

    /**
     * Initialize the payment type
     *
     * @param $name
     */
    public function __construct($name) {
        $this->name = $name;
    }

    /**
     * Initializes the payment method type.
     */
    public function initialize()
    {
        $this->settings = get_option('woocommerce_aps_cc_settings', []);
        $gateways = WC()->payment_gateways->payment_gateways();
        $this->gateway = $gateways[$this->name] ?? null;
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active()
    {
		if (null === $this->gateway) {
			return false;
		}

        return $this->gateway->is_available();
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return array
     */
    public function get_payment_method_script_handles()
    {
        $script_path = '../assets/js/frontend/blocks-' . $this->get_name() . '.js';
        $script_asset_path = plugin_dir_path(dirname(__FILE__)) . '../assets/js/frontend/blocks-' . $this->get_name() . '.asset.php';
        $script_asset = file_exists($script_asset_path)
            ? require($script_asset_path)
            : [
                'dependencies'  => [],
                'version'       => APS_VERSION
            ];
        $script_url = plugin_dir_url(dirname(__FILE__)) . $script_path;

        if (! wp_script_is('wc-' . $this->get_name() . '-payments-blocks', 'registered')) {
            wp_register_script(
                'wc-' . $this->get_name() . '-payments-blocks',
                $script_url,
                $script_asset['dependencies'],
                $script_asset['version'],
                true
            );
        }

        return [
            'wc-' . $this->get_name() . '-payments-blocks'
        ];
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data()
    {
        $payment_method_data = [
            'title'             => $this->gateway->title,
            'description'       => $this->gateway->redirection_text,
	        'icons'             => $this->gateway->icons,
            'supports'          => array_filter($this->gateway->supports, [$this->gateway, 'supports']),
            'redirect_message'  => $this->gateway->redirection_button,
            'integration_type'  => $this->gateway->get_integration_type(),
            'is_hosted'         => $this->gateway->get_integration_type() === APS_Constants::APS_INTEGRATION_TYPE_HOSTED_CHECKOUT,
        ];

        if (in_array($this->gateway->id, [APS_Constants::APS_PAYMENT_TYPE_CC, APS_Constants::APS_PAYMENT_TYPE_INSTALLMENT], true)
            && $this->gateway->get_integration_type() === APS_Constants::APS_INTEGRATION_TYPE_HOSTED_CHECKOUT) {
            $extra_form = $this->gateway->get_extra_form();
            $payment_method_data['extra_form'] = $extra_form;
			$payment_method_data['js_script'] = str_replace('classes/blocks', 'public', plugin_dir_url( __FILE__ )) . 'js/aps-checkout.js';
        }

		if ($this->gateway->id === APS_Constants::APS_PAYMENT_TYPE_APPLE_PAY) {
			$payment_method_data['apple_pay_button_html'] = $this->gateway->get_apple_pay_button_html();
		}

        return $payment_method_data;
    }

}