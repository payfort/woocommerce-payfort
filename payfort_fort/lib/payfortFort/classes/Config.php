<?php

define('PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION', 'redirection');
define('PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE', 'merchantPage');
define('PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE2', 'merchantPage2');
define('PAYFORT_FORT_PAYMENT_METHOD_CC', 'payfort');
define('PAYFORT_FORT_PAYMENT_METHOD_NAPS', 'payfort_fort_qpay');
define('PAYFORT_FORT_PAYMENT_METHOD_SADAD', 'payfort_fort_sadad');
define('PAYFORT_FORT_PAYMENT_METHOD_INSTALLMENTS', 'payfort_fort_installments');
define('PAYFORT_FORT_FLASH_MSG_ERROR', 'error');
define('PAYFORT_FORT_FLASH_MSG_SUCCESS', 'success');
define('PAYFORT_FORT_FLASH_MSG_INFO', 'info');
define('PAYFORT_FORT_FLASH_MSG_WARNING', 'warning');

class Payfort_Fort_Config extends Payfort_Fort_Super
{

    private static $instance;
    private $language;
    private $merchantIdentifier;
    private $accessCode;
    private $command;
    private $hashAlgorithm;
    private $requestShaPhrase;
    private $responseShaPhrase;
    private $sandboxMode;
    private $gatewayCurrency;
    private $debugMode;
    private $hostUrl;
    private $successOrderStatusId;
    private $orderPlacement;
    private $status;
    private $ccStatus;
    private $ccIntegrationType;
    private $sadadStatus;
    private $napsStatus;
    private $gatewayProdHost;
    private $gatewaySandboxHost;
    private $logFileDir;
    
    // installments
    private $installmentsStatus;
    private $installmentsIntegrationType;


    public function __construct()
    {
        parent::__construct();

        $this->gatewayProdHost    = 'https://checkout.payfort.com/';
        $this->gatewaySandboxHost = 'https://sbcheckout.payfort.com/';
        $this->logFileDir         = WC_LOG_DIR. 'payfort_fort.log';
        
        $this->init_settings();
        $this->language                              = $this->_getShoppingCartConfig('language');
        $this->merchantIdentifier                    = $this->_getShoppingCartConfig('merchant_identifier');
        $this->accessCode                            = $this->_getShoppingCartConfig('access_code');
        $this->command                               = $this->_getShoppingCartConfig('command');
        $this->hashAlgorithm                         = $this->_getShoppingCartConfig('hash_algorithm');
        $this->requestShaPhrase                      = $this->_getShoppingCartConfig('request_sha');
        $this->responseShaPhrase                     = $this->_getShoppingCartConfig('response_sha');
        $this->sandboxMode                           = $this->_getShoppingCartConfig('sandbox_mode');
        $this->gatewayCurrency                       = 'base';
        $this->debugMode                             = $this->_getShoppingCartConfig('debug_mode');
        //$this->hostUrl = $this->_getShoppingCartConfig('hostUrl');
        $this->successOrderStatusId = '';
        $this->orderPlacement                        = $this->_getShoppingCartConfig('order_placement');
        $this->status                                = $this->enabled;
        $this->ccStatus                              = $this->_getShoppingCartConfig('enable_credit_card');
        $this->ccIntegrationType                     = $this->_getShoppingCartConfig('integration_type');
        $this->sadadStatus                           = $this->_getShoppingCartConfig('enable_sadad');
        $this->napsStatus                            = $this->_getShoppingCartConfig('enable_naps');
        // installments
        $this->installmentsStatus                    = $this->_getShoppingCartConfig('enable_installments');
        $this->installmentsIntegrationType           = $this->_getShoppingCartConfig('installments_integration_type');
        
    }

    /**
     * @return Payfort_Fort_Config
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Payfort_Fort_Config();
        }
        return self::$instance;
    }

    private function _getShoppingCartConfig($key)
    {
        return $this->get_option($key);
    }

    public function getLanguage()
    {
        $langCode = $this->language;
        if ($this->language == 'store') {
            $langCode = Payfort_Fort_Language::getCurrentLanguageCode();
        }
        if ($langCode != 'ar') {
            $langCode = 'en';
        }
        return $langCode;
    }

    public function getMerchantIdentifier()
    {
        return $this->merchantIdentifier;
    }

    public function getAccessCode()
    {
        return $this->accessCode;
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function getHashAlgorithm()
    {
        return $this->hashAlgorithm;
    }

    public function getRequestShaPhrase()
    {
        return $this->requestShaPhrase;
    }

    public function getResponseShaPhrase()
    {
        return $this->responseShaPhrase;
    }

    public function getSandboxMode()
    {
        return $this->sandboxMode;
    }

    public function isSandboxMode()
    {
        if ($this->sandboxMode == 'yes') {
            return true;
        }
        return false;
    }

    public function getGatewayCurrency()
    {
        return $this->gatewayCurrency;
    }

    public function getDebugMode()
    {
        return $this->debugMode;
    }

    public function isDebugMode()
    {
        if ($this->debugMode) {
            return true;
        }
        return false;
    }

    public function getHostUrl()
    {
        return $this->hostUrl;
    }

    public function getSuccessOrderStatusId()
    {
        return $this->successOrderStatusId;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function isActive()
    {
        if ($this->active) {
            return true;
        }
        return false;
    }

    public function getOrderPlacement()
    {
        return $this->orderPlacement;
    }

    public function orderPlacementIsAll()
    {
        if (empty($this->orderPlacement) || $this->orderPlacement == 'all') {
            return true;
        }
        return false;
    }

    public function orderPlacementIsOnSuccess()
    {
        if ($this->orderPlacement == 'success') {
            return true;
        }
        return false;
    }

    public function getCcStatus()
    {
        return $this->ccStatus;
    }

    public function isCcActive()
    {
        if ($this->ccStatus) {
            return true;
        }
        return false;
    }

    public function getCcIntegrationType()
    {
        return $this->ccIntegrationType;
    }
    
    public function getInstallmentsIntegrationType(){
        return $this->installmentsIntegrationType;
    }

    public function isCcMerchantPage()
    {
        if ($this->ccIntegrationType == PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE) {
            return true;
        }
        return false;
    }

    public function isCcMerchantPage2()
    {
        if ($this->ccIntegrationType == PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE2) {
            return true;
        }
        return false;
    }

    public function getSadadStatus()
    {
        return $this->sadadStatus;
    }

    public function isSadadActive()
    {
        if ($this->sadadStatus) {
            return true;
        }
        return false;
    }
    
    public function getInstallmentsStatus(){
        $this->installmentsStatus;
    }
    
    public function isInstallmentsActive(){
         if ($this->installmentsStatus) {
            return true;
        }
        return false;
    }

    public function getNapsStatus()
    {
        return $this->napsStatus;
    }

    public function isNapsActive()
    {
        if ($this->napsStatus) {
            return true;
        }
        return false;
    }

    public function getGatewayProdHost()
    {
        return $this->gatewayProdHost;
    }

    public function getGatewaySandboxHost()
    {
        return $this->gatewaySandboxHost;
    }

    public function getLogFileDir()
    {
        return $this->logFileDir;
    }

}

?>