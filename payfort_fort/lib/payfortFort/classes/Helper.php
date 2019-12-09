<?php

class Payfort_Fort_Helper extends Payfort_Fort_Super
{

    private static $instance;
    private $pfConfig;
    private $log;
    
    public function __construct()
    {
        parent::__construct();
        $this->pfConfig = Payfort_Fort_Config::getInstance();
    }

    /**
     * @return Payfort_Fort_Config
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Payfort_Fort_Helper();
        }
        return self::$instance;
    }
    
    public function getBaseCurrency()
    {
        return get_woocommerce_currency();
    }

    public function getFrontCurrency()
    {
        return get_woocommerce_currency();
    }
    
    public function getFortCurrency($baseCurrencyCode, $currentCurrencyCode)
    {
        $gateway_currency = $this->pfConfig->getGatewayCurrency();
        $currencyCode     = $baseCurrencyCode;
        if ($gateway_currency == 'front') {
            $currencyCode = $currentCurrencyCode;
        }
        return $currencyCode;
    }

    public function getReturnUrl($path)
    {
        return get_site_url().'?wc-api=wc_gateway_payfort_fort_'.$path;
    }

    public function getUrl($path)
    {
        $url = get_site_url().$path;
        return $url;
    }

    /**
     * Convert Amount with decimal points
     * @param decimal $amount
     * @param decimal $currency_value
     * @param string  $currency_code
     * @return decimal
     */
    public function convertFortAmount($amount, $currency_value, $currency_code)
    {
        $gateway_currency = $this->pfConfig->getGatewayCurrency();
        $new_amount       = 0;
        //$decimal_points = $this->currency->getDecimalPlace();
        $decimal_points   = $this->getCurrencyDecimalPoints($currency_code);
        if ($gateway_currency == 'front') {
            $new_amount = round($amount * $currency_value, $decimal_points);
        }
        else {
            $new_amount = round($amount, $decimal_points);
        }
        if($decimal_points != 0) {
            $new_amount = $new_amount * (pow(10, $decimal_points));
        }
        return "$new_amount";
    }

    /**
     * 
     * @param string $currency
     * @param integer 
     */
    public function getCurrencyDecimalPoints($currency)
    {
        $decimalPoint  = 2;
        $arrCurrencies = array(
            'JOD' => 3,
            'KWD' => 3,
            'OMR' => 3,
            'TND' => 3,
            'BHD' => 3,
            'LYD' => 3,
            'IQD' => 3,
            'CLF' => 4,
            'BIF' => 0,
            'DJF' => 0,
            'GNF' => 0, 
            'ISK' => 0,
            'JPY' => 0,
            'KMF' => 0,
            'KRW' => 0,
            'CLP' => 0,
            'PYG' => 0,
            'RWF' => 0,
            'UGX' => 0,
            'VND' => 0,
            'VUV' => 0,
            'XAF' => 0,
        );
        if (isset($arrCurrencies[$currency])) {
            $decimalPoint = $arrCurrencies[$currency];
        }
        return $decimalPoint;
    }

    /**
     * calculate fort signature
     * @param array $arrData
     * @param sting $signType request or response
     * @return string fort signature
     */
    public function calculateSignature($arrData, $signType = 'request')
    {
        $shaString = '';

        ksort($arrData);
        foreach ($arrData as $k => $v) {
            $shaString .= "$k=$v";
        }

        if ($signType == 'request') {
            $shaString = $this->pfConfig->getRequestShaPhrase() . $shaString . $this->pfConfig->getRequestShaPhrase();
        }
        else {
            $shaString = $this->pfConfig->getResponseShaPhrase() . $shaString . $this->pfConfig->getResponseShaPhrase();
        }
        $signature = hash($this->pfConfig->getHashAlgorithm(), $shaString);

        return $signature;
    }

    /**
     * Log the error on the disk
     */
    public function log($messages, $forceDebug = false)
    {
        $debugMode = $this->pfConfig->isDebugMode();
        if (!$debugMode && !$forceDebug) {
            return;
        }
        if ( ! class_exists( 'WC_Logger' ) ) {
                include_once( 'class-wc-logger.php' );
        }
        if ( empty( $this->log ) ) {
                $this->log = new WC_Logger();
        }
        $this->log->add( 'payfort_fort', $messages );
    }

    public function getCustomerIp()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    public function getGatewayHost()
    {
        if ($this->pfConfig->isSandboxMode()) {
            return $this->getGatewaySandboxHost();
        }
        return $this->getGatewayProdHost();
    }

    public function getGatewayUrl($type = 'redirection')
    {
        $testMode = $this->pfConfig->isSandboxMode();
        if ($type == 'notificationApi') {
            $gatewayUrl = $testMode ?  'https://sbpaymentservices.payfort.com/FortAPI/paymentApi' :  'https://paymentservices.payfort.com/FortAPI/paymentApi';
        }
        else {
            $gatewayUrl = $testMode ? $this->pfConfig->getGatewaySandboxHost() . 'FortAPI/paymentPage' : $this->pfConfig->getGatewayProdHost() . 'FortAPI/paymentPage';
        }

        return $gatewayUrl;
    }

    public function setFlashMsg($message, $status = PAYFORT_FORT_FLASH_MSG_ERROR, $title = '')
    {
        global $woocommerce;
        if(function_exists("wc_add_notice")) {
            // Use the new version of the add_error method
            wc_add_notice($message, $status);
        } else {
            // Use the old version
            if($status == PAYFORT_FORT_FLASH_MSG_ERROR){
                $woocommerce->add_error($message);
            }
            else{
                $woocommerce->add_message($message);
            }
            
        }
    }
    
    public static function loadJsMessages($messages, $isReturn = true, $category = 'payfort_fort') {
        $result = '';
        foreach($messages as $label => $translation) {
            $result .= "arr_messages['{$category}.{$label}']='" . $translation ."';\n";
        }
        if($isReturn) {
            return $result;
        }
        else{
            echo $result; 
        }
    }

}

?>