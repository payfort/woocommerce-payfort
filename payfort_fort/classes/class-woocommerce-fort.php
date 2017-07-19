<?php
require_once PAYFORT_FORT_DIR . 'lib/payfortFort/init.php';

class WC_Gateway_Payfort extends Payfort_Fort_Super
{
    public $pfConfig;
    public $pfHelper;
    public $pfPayment;

    public function __construct()
    {
        global $woocommerce;

        $this->has_fields = false;
        $this->load_plugin_textdomain();
        $this->icon       = apply_filters('woocommerce_FORT_icon', PAYFORT_FORT_URL . 'assets/images/cards.png');
        if(is_admin()) {
            $this->has_fields = true;
            $this->init_form_fields();
        }
        
        // Define user set variables
        $this->title               = Payfort_Fort_Language::__('Credit / Debit Card');
        $this->description         = $this->get_option('description');
        $this->pfConfig            = Payfort_Fort_Config::getInstance();
        $this->pfHelper            = Payfort_Fort_Helper::getInstance();
        $this->pfPayment           = Payfort_Fort_Payment::getInstance();
        $this->enable_sadad        = $this->get_option('enable_sadad') == 'yes' ? true : false;
        $baseCurrency = $this->pfHelper->getBaseCurrency();
        $frontCurrency = $this->pfHelper->getFrontCurrency();
        if($this->pfHelper->getFortCurrency($baseCurrency, $frontCurrency) != 'SAR') {
            $this->enable_sadad  = false;
        }
        $this->enable_naps         = $this->get_option('enable_naps') == 'yes' ? true : false;
        if($this->pfHelper->getFortCurrency($baseCurrency, $frontCurrency) != 'QAR') {
            $this->enable_naps  = false;
        }
        $this->enable_credit_card  = $this->get_option('enable_credit_card') == 'yes' ? true : false;
        
        // Actions
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
        
        // Save options
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        if (!$this->is_valid_for_use()) {
            $this->enabled = false;
        }

        //add_action('woocommerce_api_'.strtolower(get_class($this).'_process_response'), array(&$this, 'process_response'));
        //add_action('woocommerce_api_'.strtolower(get_class($this).'_merchantPageResponse'), array(&$this, 'merchantPageResponse'));
        //add_action('woocommerce_api_'.strtolower(get_class($this).'_merchantPageCancel'), array(&$this, 'merchantPageCancel'));

        add_action('woocommerce_wc_gateway_payfort_fort_process_response', array(&$this, 'process_response'));
        add_action('woocommerce_wc_gateway_payfort_fort_responseOnline', array(&$this, 'responseOnline'));
        add_action('woocommerce_wc_gateway_payfort_fort_merchantPageResponse', array(&$this, 'merchantPageResponse'));
        add_action('woocommerce_wc_gateway_payfort_fort_merchantPageCancel', array(&$this, 'merchantPageCancel'));
    }

    function process_admin_options() {
        $result = parent::process_admin_options();
        $post_data = $this->get_post_data();
        $settings = $this->settings;
        
        $sadadSettings = array();
        $qpaySettings = array();
        
        $sadadSettings['enabled'] = isset($settings['enable_sadad']) ? $settings['enable_sadad'] : "no";
        $qpaySettings['enabled']  = isset($settings['enable_naps']) ? $settings['enable_naps'] : "no";
        
        update_option( 'woocommerce_payfort_fort_sadad_settings', apply_filters( 'woocommerce_settings_api_sanitized_fields_sadad', $sadadSettings ) );
        update_option( 'woocommerce_payfort_fort_qpay_settings', apply_filters( 'woocommerce_settings_api_sanitized_fields_qpay', $qpaySettings ) );
        return $result;
    }
    
    function payment_scripts()
    {
        global $woocommerce;
        if (!is_checkout()) {
            return;
        }
        
        wp_enqueue_script('fortjs-creditCardValidator', PAYFORT_FORT_URL . 'assets/js/jquery.creditCardValidator.js', array(), WC_VERSION, true);
        wp_enqueue_script('fortjs-payfort_fort', PAYFORT_FORT_URL . 'assets/js/payfort_fort.js', array(), WC_VERSION, true);
        wp_enqueue_script('fortjs-checkout', PAYFORT_FORT_URL . 'assets/js/checkout.js', array(), WC_VERSION, true);
        if ($this->pfConfig->getCcIntegrationType() == 'merchantPage') {
            wp_enqueue_style('font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css');
            wp_enqueue_style('fortcss-checkout', PAYFORT_FORT_URL . 'assets/css/checkout.css');
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
        // Skip currency check
        return true;
    }

    /**
     * Admin Panel Options
     * - Options for bits like 'api keys' and availability on a country-by-country basis
     *
     * @since 1.0.0
     */
    public function admin_options()
    {
        ?>
        <h3><?php _e('Payfort FORT', 'payfort_fort'); ?></h3>
        <p><?php _e('Please fill in the below section to start accepting payments on your site! You can find all the required information in your <a href="https://fort.payfort.com/" target="_blank">Payfort FORT Dashboard</a>.', 'payfort_fort'); ?></p>

        <?php if ($this->is_valid_for_use()) : ?>

            <table class="form-table">
            <?php
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
            ?>
                <script>
                    jQuery(document).ready(function () {
                        jQuery('[name=save]').click(function () {
                            if (!jQuery('#woocommerce_payfort_enable_credit_card').is(':checked') && !jQuery('#woocommerce_payfort_enable_sadad').is(':checked') && !jQuery('#woocommerce_payfort_enable_naps').is(':checked')) {
                                alert('Please enable at least 1 payment method!');
                                return false;
                            }
                        })
                    });
                </script>
                <tr valign="top">
                    <th class="titledesc" scope="row">
                        <label for="woocommerce_fort_host_to_host_url">Host to Host URL</label>
                        <!--<img width="16" height="16" src="http://localhost/wordpress/wp-content/plugins/woocommerce/assets/images/help.png" class="help_tip">-->
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <legend class="screen-reader-text"><span>Host to Host URL</span></legend>
                            <input type="text" readonly="readonly" placeholder="" value="<?php echo get_site_url() . '/?wc-api=wc_gateway_payfort_process_response'; ?>" style="" id="woocommerce_fort_host_to_host_url" name="woocommerce_fort_host_to_host_url" class="input-text regular-input ">
                        </fieldset>
                    </td>
                </tr>
            </table><!--/.form-table-->
        <?php else : ?>
            <div class="inline error"><p><strong><?php _e('Gateway Disabled', 'payfort_fort'); ?></strong>: <?php _e('Payfort FORT does not support your store currency at this time.', 'payfort_fort'); ?></p></div>
        <?php
        endif;
    }

    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields()
    {
        $this->form_fields = array(
            'enabled'             => array(
                'title'   => __('Enable/Disable', 'payfort_fort'),
                'type'    => 'checkbox',
                'label'   => __('Enable the FORT gateway', 'payfort_fort'),
                'default' => 'yes'
            ),
            'description'         => array(
                'title'       => __('Description', 'payfort_fort'),
                'type'        => 'text',
                'description' => __('This is the description the user sees during checkout.', 'payfort_fort'),
                'default'     => __('Pay for your items with any Credit or Debit Card', 'payfort_fort')
            ),
            'language'            => array(
                'title'       => __('Language', 'payfort_fort'),
                'type'        => 'select',
                'options'     => array(
                    'store' => __('Stor Language', 'payfort_fort'),
                    'en'    => __('English (en)', 'payfort_fort'),
                    'ar'    => __('Arabic (ar)', 'payfort_fort')
                ),
                'description' => __('The language of the payment page.', 'payfort_fort'),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => '',
                'class'       => 'wc-enhanced-select',
            ),
            'merchant_identifier' => array(
                'title'       => __('Merchant Identifier', 'payfort_fort'),
                'type'        => 'text',
                'description' => __('Your MID, you can find in your FORT account security settings.', 'payfort_fort'),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => ''
            ),
            'access_code'         => array(
                'title'       => __('Access Code', 'payfort_fort'),
                'type'        => 'text',
                'description' => __('Your access code, you can find in your FORT account security settings.', 'payfort_fort'),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => ''
            ),
            'command'             => array(
                'title'       => __('Command', 'payfort_fort'),
                'type'        => 'select',
                'options'     => array(
                    'AUTHORIZATION' => __('AUTHORIZATION', 'payfort_fort'),
                    'PURCHASE'      => __('PURCHASE', 'payfort_fort')
                ),
                'description' => __('Order operation to be used in the payment page.', 'payfort_fort'),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => '',
                'class'       => 'wc-enhanced-select',
            ),
            'hash_algorithm'      => array(
                'title'       => __('SHA Algorithm', 'payfort_fort'),
                'type'        => 'select',
                'options'     => array(
                    'SHA1'   => __('SHA1', 'payfort_fort'),
                    'SHA256' => __('SHA-256', 'payfort_fort'),
                    'SHA512' => __('SHA-512', 'payfort_fort')
                ),
                'description' => __('The hash algorithm used for the signature', 'payfort_fort'),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => '',
                'class'       => 'wc-enhanced-select',
            ),
            'request_sha'         => array(
                'title'       => __('Request SHA phrase', 'payfort_fort'),
                'type'        => 'text',
                'description' => __('Your request SHA phrase, you can find in your FORT account security settings.', 'payfort_fort'),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => ''
            ),
            'response_sha'        => array(
                'title'       => __('Response SHA phrase', 'payfort_fort'),
                'type'        => 'text',
                'description' => __('Your response SHA phrase, you can find in your FORT account security settings.', 'payfort_fort'),
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => ''
            ),
            /*'gateway_currency'    => array(
                'title'       => __('Gateway Currency', 'payfort_fort'),
                'type'        => 'select',
                'options'     => array(
                    'base'  => __('Base', 'payfort_fort'),
                    'front' => __('Front', 'payfort_fort'),
                ),
                'default'     => 'base',
                'desc_tip'    => true,
                'description' => __('The Currency should be sent to payment gateway.', 'payfort_fort'),
                'placeholder' => '',
                'class'       => 'wc-enhanced-select',
            ),*/
            'debug_mode'          => array(
                'title'       => __('Debug Mode', 'payfort_fort'),
                'type'        => 'select',
                'options'     => array(
                    '1' => __('enabled', 'payfort_fort'),
                    '0' => __('disabled', 'payfort_fort'),
                ),
                'default'     => '0',
                'desc_tip'    => true,
                'description' => sprintf(__('Logs additional information. <br>Log file path: %s', 'payfort_fort'), 'Your admin panel -> WooCommerce -> System Status -> Logs'),
                'placeholder' => '',
                'class'       => 'wc-enhanced-select',
            ),
            'order_placement' => array(
                'title'       => __('Order Placement', 'payfort_fort'),
                'type'        => 'select',
                'options'     => array(
                    'all' => __('All', 'payfort_fort'),
                    'success' => __('Success', 'payfort_fort'),
                ),
                'default'     => 'all',
                'placeholder' => '',
                'class'       => 'wc-enhanced-select',
            ),
            'enable_credit_card'  => array(
                'title'   => __('Credit \ Debit Card', 'payfort_fort'),
                'type'    => 'checkbox',
                'label'   => __('Enable Credit \ Debit Card Payment Method', 'payfort_fort'),
                'default' => 'yes'
            ),
            'integration_type'    => array(
                'title'       => __('Integration Type', 'payfort_fort'),
                'type'        => 'select',
                'options'     => array(
                    'redirection'  => __('Redirection', 'payfort_fort'),
                    'merchantPage' => __('Merchant Page', 'payfort_fort'),
                    'merchantPage2' => __('Merchant Page 2.0', 'payfort_fort'),
                ),
                'description' => __('Credit \ Debit Card Integration Type', 'payfort_fort'),
                'default'     => 'redirection',
                'desc_tip'    => true,
                'placeholder' => __('Integration Type', 'payfort_fort'),
                'class'       => 'wc-enhanced-select',
            ),
            'enable_sadad'        => array(
                'title'   => __('SADAD', 'payfort_fort'),
                'type'    => 'checkbox',
                'label'   => __('Enable SADAD Payment Method', 'payfort_fort'),
                'default' => 'no'
            ),
            'enable_naps'         => array(
                'title'   => __('NAPS', 'payfort_fort'),
                'type'    => 'checkbox',
                'label'   => __('Enable NAPS Payment Method', 'payfort_fort'),
                'default' => 'no'
            ),
            'sandbox_mode'        => array(
                'title'   => __('Sandbox mode', 'payfort_fort'),
                'type'    => 'checkbox',
                'label'   => __('Enable Sandbox mode', 'payfort_fort'),
                'default' => 'no'
            )
        );
    }

    /**
     * Process the payment and return the result
     *
     * @access public
     * @param int $order_id
     * @return array
     */
    function process_payment($order_id)
    {
        global $woocommerce;
        $order   = new WC_Order($order_id);
        if (!isset($_GET['response_code'])) {
            $paymentMethod   = PAYFORT_FORT_PAYMENT_METHOD_CC;
            $integrationType = PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION;
            $payment_method  = $_POST['payment_method'];
            if($payment_method == PAYFORT_FORT_PAYMENT_METHOD_SADAD) {
                $paymentMethod = PAYFORT_FORT_PAYMENT_METHOD_SADAD;
            }
            elseif($payment_method == PAYFORT_FORT_PAYMENT_METHOD_NAPS) {
                $paymentMethod = PAYFORT_FORT_PAYMENT_METHOD_NAPS;
            }
            else{
                $paymentMethod = PAYFORT_FORT_PAYMENT_METHOD_CC;
                $integrationType = $this->pfConfig->getCcIntegrationType();
            }
            $postData   = array();
            $gatewayUrl = '#';

            if ($integrationType == PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION) {
                if ($paymentMethod == PAYFORT_FORT_PAYMENT_METHOD_SADAD) {
                    update_post_meta($order->id, '_payment_method_title', 'SADAD');
                    update_post_meta($order->id, '_payment_method', PAYFORT_FORT_PAYMENT_METHOD_SADAD);
                }
                else if ($paymentMethod == PAYFORT_FORT_PAYMENT_METHOD_NAPS) {
                    update_post_meta($order->id, '_payment_method_title', 'NAPS');
                    update_post_meta($order->id, '_payment_method', PAYFORT_FORT_PAYMENT_METHOD_NAPS);
                }
            }
            elseif ($paymentMethod == PAYFORT_FORT_PAYMENT_METHOD_CC && $integrationType == PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE2) {
                $this->has_fields = true;
            }

            $form   = $this->pfPayment->getPaymentRequestForm($paymentMethod, $integrationType);
            $result = array('result' => 'success', 'form' => $form);
            if (isset($_POST['woocommerce_pay']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'woocommerce-pay')) {
                wp_send_json($result);
                exit;
            }
            else {
                return $result;
            }
        }
    }

    public function process_response()
    {
        $this->_handleResponse('offline');
    }

    public function responseOnline()
    {
        $this->_handleResponse('online');
    }

    public function merchantPageResponse()
    {
        $this->_handleResponse('online', $this->pfConfig->getCcIntegrationType());
    }

    private function _handleResponse($response_mode = 'online', $integration_type = PAYFORT_FORT_INTEGRATION_TYPE_REDIRECTION)
    {
        global $woocommerce;
        $response_params = array_merge($_GET, $_POST); //never use $_REQUEST, it might include PUT .. etc
        if (isset($response_params['merchant_reference'])) {
            $success = $this->pfPayment->handleFortResponse($response_params, $response_mode, $integration_type);
            if ($success) {
                $order = new WC_Order($response_params['merchant_reference']);
                WC()->session->set('refresh_totals', true);
                $redirectUrl = $this->get_return_url($order);
            }
            else {
                $redirectUrl = esc_url($woocommerce->cart->get_checkout_url());
            }
            echo '<script>window.top.location.href = "' . $redirectUrl . '"</script>';
            exit;
        }
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
        // Access the global object
        echo "<input type='hidden' id='payfort_fort_cc_integration_type' value='" . $this->pfConfig->getCcIntegrationType() . "'>";
        echo "<input type='hidden' id='payfort_cancel_url' value='" . get_site_url() . '?wc-api=wc_gateway_payfort_fort_merchantPageCancel' . "'>";
        
        if ($this->enable_credit_card && $this->pfConfig->getCcIntegrationType() == 'merchantPage') {
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
        if (!$this->enable_credit_card) {
            echo preg_replace('/^\s+|\n|\r|\s+$/m', '', '<script>
                    jQuery(".payment_method_payfort").eq(0).remove();
                </script>');
        }
        if ($this->description) {
            echo "<p>" . $this->description . "</p>";
        }
        if($this->pfConfig->getCcIntegrationType() == PAYFORT_FORT_INTEGRATION_TYPE_MERCAHNT_PAGE2) :
            $arr_js_messages = 
                        array(
                            'error_invalid_card_number' => __('error_invalid_card_number', 'payfort_fort'),
                            'error_invalid_card_holder_name' => __('error_invalid_card_holder_name', 'payfort_fort'),
                            'error_invalid_expiry_date' => __('error_invalid_expiry_date', 'payfort_fort'),
                            'error_invalid_cvc_code' => __('error_invalid_cvc_code', 'payfort_fort'),
                            'error_invalid_cc_details' => __('error_invalid_cc_details', 'payfort_fort'),
                        );

            $arr_js_messages = $this->pfHelper->loadJsMessages($arr_js_messages);
        ?>
        <fieldset>
            <div id="payfort_fort_cc_form">
                <div class="payfort-fort-cc" >
                    <p id="payfort_fort_card_number_field" class="form-row">
                        <label class="" for="payfort_fort_card_number"><?php echo __('text_card_number', 'payfort_fort');?> <span class="required">*</span></label>
                        <input type="text" value="" autocomplete="off" maxlength="16" placeholder="" id="payfort_fort_card_number" class="input-text">
                    </p>
                    <p id="payfort_fort_card_holder_name_field" class="form-row clear">
                        <label class="" for="payfort_fort_card_holder_name"><?php echo __('text_card_holder_name', 'payfort_fort');?></label>
                        <input type="text" value="" autocomplete="off" maxlength="50" placeholder="" id="payfort_fort_card_holder_name" class="input-text">
                    </p>
                    <p id="payfort_fort_expiry_month_field" class="form-row clear form-row-wide">
                        <label class="" for="payfort_fort_expiry_month"><?php echo __('text_expiry_date', 'payfort_fort');?>  <span class="required">*</span></label>
                        <input width="50px" type="text" value="" autocomplete="off" maxlength="2" placeholder="" id="payfort_fort_expiry_month" class="input-text" size="2" style="width: 50px">
                        <input width="50px" type="text" value="" autocomplete="off" maxlength="2" placeholder="" id="payfort_fort_expiry_year" class="input-text" size="2" style="width: 50px">
                        <input type="hidden" id="payfort_fort_expiry"/>
                    </p>
                    <p id="payfort_fort_card_security_code_field" class="form-row clear">
                        <label class="" for="payfort_fort_card_security_code"><?php echo __('text_cvc_code', 'payfort_fort');?>  <span class="required">*</span></label>
                        <input type="text" value="" autocomplete="off" maxlength="4" placeholder="" id="payfort_fort_card_security_code" class="input-text" style="width: 60px">
                        <span class="description" style="clear: left;float: left;"><?php echo __('help_cvc_code', 'payfort_fort') ?></span>
                    </p>
                    <script>
                        var arr_messages = [];
                        <?php echo "$arr_js_messages"; ?>
                    </script>
                </div>
            </div>
        </fieldset>
        <?php
        endif;
    }

    function merchantPageCancel()
    {
        global $woocommerce;
        $this->pfPayment->merchantPageCancel();
        echo '<script>window.top.location.href = "' . esc_url($woocommerce->cart->get_checkout_url()) . '"</script>';
        exit;
    }

    /**
     * Load Localisation files.
     *
     * Note: the first-loaded translation file overrides any following ones if the same translation is present.
     *
     * Locales found in:
     *      - WP_LANG_DIR/payfort_fort/payfort_fort-LOCALE.mo
     *      - WP_LANG_DIR/plugins/payfort_fort-LOCALE.mo
     */
    public function load_plugin_textdomain()
    {
        $locale = apply_filters('plugin_locale', get_locale(), 'payfort_fort');

        load_textdomain('payfort_fort', PAYFORT_FORT_DIR . 'languages/payfort_fort-' . $locale . '.mo');
        load_plugin_textdomain('payfort_fort', false, plugin_basename(dirname(__FILE__)) . '/languages');
    }

}
