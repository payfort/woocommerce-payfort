<?php

class WC_Gateway_Payfort_Fort_Installments extends WC_Gateway_Payfort {
    
    public $pfConfig;
    public $id = PAYFORT_FORT_PAYMENT_METHOD_INSTALLMENTS;

    public function __construct() {
        parent::__construct();
        $this->title                         = __('Installments', 'payfort_fort');
        $this->method_title                  = 'Payfort Installments';
        $this->description                   = __('Pay for your items with Installments', 'payfort_fort');
        $this->has_fields                    = false;
        $this->pfConfig                      = Payfort_Fort_Config::getInstance();
        $this->enable_installments           = $this->get_option('enable_installments') == 'yes' ? true : false;
        $this->icon                          = apply_filters('woocommerce_FORT_icon', PAYFORT_FORT_URL . 'assets/images/cards.png');
        if (!$this->is_valid_for_use()) {
            $this->enabled = true;
        }
    }

    /**
     * Check if this gateway is enabled and available in the user's currency
     *
     * @access public
     * @return bool
     */
    function is_valid_for_use() {
        return true;
    }
    
  function payment_scripts() {
        global $woocommerce;
        if (!is_checkout()) {
            return;
        }
        
        wp_enqueue_script('fortjs-creditCardValidator', PAYFORT_FORT_URL . 'assets/js/jquery.creditCardValidator.js', array(), WC_VERSION, true);
        wp_enqueue_script('fortjs-payfort_fort', PAYFORT_FORT_URL . 'assets/js/payfort_fort.js', array(), WC_VERSION, true);
        wp_enqueue_script('fortjs-checkout', PAYFORT_FORT_URL . 'assets/js/checkout.js', array(), WC_VERSION, true);
        if ($this->pfConfig->getInstallmentsIntegrationType() == 'merchantPage') {
            wp_enqueue_style('font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css');
            wp_enqueue_style('fortcss-checkout', PAYFORT_FORT_URL . 'assets/css/checkout.css');
        }
    }    

    /**
     * Generate the credit card payment form
     *
     * @access public
     * @param none
     * @return string
     */
    function payment_fields() {
        // Access the global object
        echo "<input type='hidden' id='payfort_fort_installments_integration_type' value='" . $this->pfConfig->getInstallmentsIntegrationType() . "'>";
        echo "<input type='hidden' id='payfort_installments_cancel_url' value='" . get_site_url() . '?wc-api=wc_gateway_payfort_fort_merchantPageCancel' . "'>";
        
        if ($this->pfConfig->getInstallmentsIntegrationType() == 'merchantPage') {
            echo preg_replace('/^\s+|\n|\r|\s+$/m', '', '<script>
                    jQuery(".payment_method_payfort").eq(0).after(\'\
                    <div class="pf-iframe-background" id="div-pf-iframe" style="display:none">
                        <div class="pf-iframe-container">
                            <span class="pf-close-container">
                                <i class="fa fa-times-circle pf-iframe-close" onclick="payfortFortMerchantPage.closePopup()"></i>
                            </span>
                            <i class="fa fa-spinner fa-spin pf-iframe-spin"></i>
                            <div class="pf-iframe" id="pf_iframe_content"></div>
                        </div>
                    </div>\');
                </script>');
        }
        
        if ($this->description) {
            echo "<p>" . $this->description . "</p>";
        }
    }

}
