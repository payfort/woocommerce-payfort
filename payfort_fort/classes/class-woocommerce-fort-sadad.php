<?php

class WC_Gateway_Payfort_Fort_Sadad extends WC_Gateway_Payfort
{

    public $id = PAYFORT_FORT_PAYMENT_METHOD_SADAD;

    public function __construct()
    {
        parent::__construct();
        $this->title        = __('SADAD', 'payfort_fort');
        $this->method_title = 'Payfort Sadad';
        $this->description  = __('Pay for your items with using SADAD payment method', 'payfort_fort');
        $this->icon       = apply_filters('woocommerce_FORT_icon', PAYFORT_FORT_URL . 'assets/images/SADAD-logo.png');
        $this->has_fields = false;
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
    function is_valid_for_use()
    {
        $baseCurrency = $this->pfHelper->getBaseCurrency();
        $frontCurrency = $this->pfHelper->getFrontCurrency();
        if($this->pfHelper->getFortCurrency($baseCurrency, $frontCurrency) != 'SAR') {
            return false;
        }
        return true;
    }
    
    /**
     * Generate the credit card payment form
     *
     * @access public
     * @param none
     * @return string
     */
    function payment_fields()
    {
        if ($this->description) {
            echo "<p>" . $this->description . "</p>";
        }
    }

}
