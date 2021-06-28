<?php

/**
 * All functions of APS config fields
 *
 * @link       https://paymentservices.amazon.com/
 * @since      2.2.0
 *
 * @package    APS
 * @subpackage APS/includes
 */
/**
 * All functions of APS config fields
 *
 * @since      2.2.0
 * @package    APS
 * @subpackage APS/includes
 * @author     Amazon Payment Services
 */
class APS_Fields_Loader {

	/**
	 * Define loaded property for fields
	 */
	private $redirection;
	private $standard_checkout;
	private $hosted_checkout;
	private $embedded_hosted_checkout;
	private $button_type_buy;
	private $button_type_donate;
	private $button_type_plain;
	private $button_type_setup;
	private $button_type_book;
	private $button_type_checkout;
	private $button_type_subscribe;
	private $button_type_addmoney;
	private $button_type_contribute;
	private $button_type_order;
	private $button_type_reload;
	private $button_type_rent;
	private $button_type_support;
	private $button_type_tip;
	private $button_type_topup;

	public function __construct() {
		$this->redirection            = __( 'Redirection', 'amazon_payment_services' );
		$this->standard_checkout      = __( 'Standard Checkout', 'amazon_payment_services' );
		$this->hosted_checkout        = __( 'Hosted Checkout', 'amazon_payment_services' );
		$this->embedded_hosted_checkout= __( 'Embedded Hosted Checkout', 'amazon_payment_services' );
		$this->button_type_buy        = __( 'BUY', 'amazon_payment_services' );
		$this->button_type_donate     = __( 'DONATE', 'amazon_payment_services' );
		$this->button_type_plain      = __( 'PLAIN', 'amazon_payment_services' );
		$this->button_type_setup      = __( 'SETUP', 'amazon_payment_services' );
		$this->button_type_book       = __( 'BOOK', 'amazon_payment_services' );
		$this->button_type_checkout   = __( 'CHECKOUT', 'amazon_payment_services' );
		$this->button_type_subscribe  = __( 'SUBSCRIBE', 'amazon_payment_services' );
		$this->button_type_addmoney   = __( 'ADDMONEY', 'amazon_payment_services' );
		$this->button_type_contribute = __( 'CONTRIBUTE', 'amazon_payment_services' );
		$this->button_type_order      = __( 'ORDER', 'amazon_payment_services' );
		$this->button_type_reload     = __( 'RELOAD', 'amazon_payment_services' );
		$this->button_type_rent       = __( 'RENT', 'amazon_payment_services' );
		$this->button_type_support    = __( 'SUPPORT', 'amazon_payment_services' );
		$this->button_type_tip        = __( 'TIP', 'amazon_payment_services' );
		$this->button_type_topup      = __( 'TOPUP', 'amazon_payment_services' );
	}

	/**
	 * Return aps config fields
	 *
	 * return array
	 */
	public function get_config_fields() {
		$mada_meeza_msg = __( 'Please do not change any of the below BINs configuration unless it is instructed by APS Integration team. For further inquiries:  {aps_support_email}', 'amazon_payment_services' );
		$mada_meeza_msg = str_replace( '{aps_support_email}', 'integration-ps@amazon.com', $mada_meeza_msg );
		return array(
			'merchant_group'                      => array(
				'title' => __( 'Amazon Payment Services Merchant Configuration', 'amazon_payment_services' ),
				'type'  => 'title',
			),
			'merchant_identifier'                 => array(
				'title'            => __( 'Merchant Identifier', 'amazon_payment_services' ),
				'type'             => 'text',
				'default'          => '',
				'desc_tip'         => true,
				'placeholder'      => '',
				'validation_rules' => array( 'required', 'no_space' ),
				'min_length'       => 1,
				'max_length'       => 20,
			),
			'access_code'                         => array(
				'title'            => __( 'Access Code', 'amazon_payment_services' ),
				'type'             => 'text',
				'default'          => '',
				'desc_tip'         => true,
				'placeholder'      => '',
				'validation_rules' => array( 'required', 'no_space' ),
				'min_length'       => 1,
				'max_length'       => 20,
			),
			'request_sha_phrase'                  => array(
				'title'            => __( 'Request SHA Phrase', 'amazon_payment_services' ),
				'type'             => 'text',
				'default'          => '',
				'desc_tip'         => true,
				'placeholder'      => '',
				'validation_rules' => array( 'required', 'no_space' ),
				'min_length'       => 1,
				'max_length'       => 50,
			),
			'response_sha_phrase'                 => array(
				'title'            => __( 'Response SHA Phrase', 'amazon_payment_services' ),
				'type'             => 'text',
				'default'          => '',
				'desc_tip'         => true,
				'placeholder'      => '',
				'validation_rules' => array( 'required', 'no_space' ),
				'min_length'       => 1,
				'max_length'       => 50,
			),
			'status_cron_duration'                => array(
				'title'       => __( 'Status cron duriation', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					15  => __( '15 Minutes' ),
					30  => __( '30 Minutes' ),
					45  => __( '45 Minutes' ),
					60  => __( '1 Hour' ),
					120 => __( '2 Hour' ),
				),
				'default'     => APS_Constants::APS_STATUS_CRON_DEFAULT_DURATION,
				'desc_tip'    => true,
				'placeholder' => __( 'Show issuer logo', 'amazon_payment_services' ),
				'class'       => 'wc-enhanced-select',
			),
			'globalconfig_group'                  => array(
				'title' => __( 'Amazon Payment Services Global Configuration', 'amazon_payment_services' ),
				'type'  => 'title',
			),
			'sandbox_mode'                        => array(
				'title'       => __( 'Sandbox Mode', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					'yes' => __( 'Yes', 'amazon_payment_services' ),
					'no'  => __( 'No', 'amazon_payment_services' ),
				),
				'default'     => 'no',
				'desc_tip'    => true,
				'placeholder' => '',
				'class'       => 'wc-enhanced-select',
			),
			'command'                             => array(
				'title'       => __( 'Command', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					'AUTHORIZATION' => __( 'AUTHORIZATION', 'amazon_payment_services' ),
					'PURCHASE'      => __( 'PURCHASE', 'amazon_payment_services' ),
				),
				'default'     => '',
				'desc_tip'    => true,
				'placeholder' => '',
				'class'       => 'wc-enhanced-select',
			),
			'hash_algorithm'                      => array(
				'title'       => __( 'SHA Type', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					'sha256'  => __( 'SHA-256', 'amazon_payment_services' ),
					'sha512'  => __( 'SHA-512', 'amazon_payment_services' ),
					'hmac256' => __( 'HMAC-256', 'amazon_payment_services' ),
					'hmac512' => __( 'HMAC-512', 'amazon_payment_services' ),
				),
				'default'     => '',
				'desc_tip'    => true,
				'placeholder' => '',
				'class'       => 'wc-enhanced-select',
			),
			'gateway_currency'                    => array(
				'title'       => __( 'Gateway Currency', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					'base'  => __( 'Base', 'amazon_payment_services' ),
					'front' => __( 'Front', 'amazon_payment_services' ),
				),
				'default'     => 'base',
				'desc_tip'    => true,
				'placeholder' => '',
				'class'       => 'wc-enhanced-select',
			),
			'debug_mode'                          => array(
				'title'       => __( 'Debug Mode', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					'yes' => __( 'Yes', 'amazon_payment_services' ),
					'no'  => __( 'No', 'amazon_payment_services' ),
				),
				'default'     => 'no',
				'desc_tip'    => true,
				'placeholder' => '',
				'class'       => 'wc-enhanced-select',
			),
			'logs_url'                            => array(
				'title'       => __( 'Log URL', 'amazon_payment_services' ),
				'type'        => 'text_info',
				'description' => '<a href=" ' . admin_url( 'admin.php?page=wc-status&tab=logs' ) . ' " target="_blank">' . __( 'Click here to view', 'amazon_payment_services' ) . '</a>',
			),
			'host_to_host_url'                    => array(
				'title'       => __( 'Host to Host URL', 'amazon_payment_services' ),
				'type'        => 'text_info',
				'description' => create_wc_api_url( 'aps_offline_response' ),
			),
			'enable_tokenization'                 => array(
				'title'       => __( 'Enable Tokenization', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					'yes' => __( 'Yes', 'amazon_payment_services' ),
					'no'  => __( 'No', 'amazon_payment_services' ),
				),
				'default'     => 'yes',
				'desc_tip'    => true,
				'placeholder' => '',
				'class'       => 'wc-enhanced-select',
			),
			'hide_delete_token_button'            => array(
				'title'       => __( 'Hide delete Token button', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					'yes' => __( 'Yes', 'amazon_payment_services' ),
					'no'  => __( 'No', 'amazon_payment_services' ),
				),
				'default'     => 'no',
				'desc_tip'    => true,
				'placeholder' => '',
				'class'       => 'wc-enhanced-select',
			),
			'creditcard_group'                    => array(
				'title' => __( 'Credit / Debit Card', 'amazon_payment_services' ),
				'type'  => 'title',
			),
			'enable_credit_card'                  => array(
				'title'       => __( 'Enabled', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					'yes' => __( 'Yes', 'amazon_payment_services' ),
					'no'  => __( 'No', 'amazon_payment_services' ),
				),
				'default'     => 'no',
				'desc_tip'    => true,
				'placeholder' => '',
				'class'       => 'wc-enhanced-select',
			),
			'credit_card_integration_type'        => array(
				'title'       => __( 'Integration Type', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					APS_Constants::APS_INTEGRATION_TYPE_REDIRECTION   => $this->redirection,
					APS_Constants::APS_INTEGRATION_TYPE_STANDARD_CHECKOUT  => $this->standard_checkout,
					APS_Constants::APS_INTEGRATION_TYPE_HOSTED_CHECKOUT => $this->hosted_checkout,
				),
				'default'     => APS_Constants::APS_DEFAULT_INTEGRATION_TYPE,
				'desc_tip'    => true,
				'placeholder' => __( 'Integration Type', 'amazon_payment_services' ),
				'class'       => 'wc-enhanced-select',
			),
			'show_mada_branding'                  => array(
				'title'   => __( 'Show mada Branding', 'amazon_payment_services' ),
				'type'    => 'checkbox',
				'label'   => __( 'Show mada Branding during checkout', 'amazon_payment_services' ),
				'default' => 'no',
			),
			'show_meeza_branding'                 => array(
				'title'   => __( 'Show Meeza Branding', 'amazon_payment_services' ),
				'type'    => 'checkbox',
				'label'   => __( 'Show Meeza Branding during checkout', 'amazon_payment_services' ),
				'default' => 'no',
			),
			'mada_bins'                           => array(
				'title'            => __( 'mada Bins', 'amazon_payment_services' ),
				'type'             => 'textarea',
				'label'            => __( 'mada Bins', 'amazon_payment_services' ),
				'default'          => APS_Constants::MADA_BINS,
				'validation_rules' => array( 'no_space' ),
				'description'      => $mada_meeza_msg,
			),
			'meeza_bins'                          => array(
				'title'            => __( 'Meeza Bins', 'amazon_payment_services' ),
				'type'             => 'textarea',
				'label'            => __( 'Meeza Bins', 'amazon_payment_services' ),
				'default'          => APS_Constants::MEEZA_BINS,
				'validation_rules' => array( 'no_space' ),
				'description'      => $mada_meeza_msg,
			),
			'applepay_group'                      => array(
				'title' => __( 'Apple Pay', 'amazon_payment_services' ),
				'type'  => 'title',
			),
			'enable_apple_pay'                    => array(
				'title'       => __( 'Enabled', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					'yes' => __( 'Yes', 'amazon_payment_services' ),
					'no'  => __( 'No', 'amazon_payment_services' ),
				),
				'default'     => 'no',
				'desc_tip'    => true,
				'placeholder' => '',
				'class'       => 'wc-enhanced-select',
			),
			'enable_apple_pay_product_page'       => array(
				'title'       => __( 'Enabled Apple Pay in product page', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					'yes' => __( 'Yes', 'amazon_payment_services' ),
					'no'  => __( 'No', 'amazon_payment_services' ),
				),
				'default'     => 'no',
				'desc_tip'    => true,
				'placeholder' => '',
				'class'       => 'wc-enhanced-select',
			),
			'enable_apple_pay_cart_page'          => array(
				'title'       => __( 'Enabled Apple Pay in cart page', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					'yes' => __( 'Yes', 'amazon_payment_services' ),
					'no'  => __( 'No', 'amazon_payment_services' ),
				),
				'default'     => 'no',
				'desc_tip'    => true,
				'placeholder' => '',
				'class'       => 'wc-enhanced-select',
			),
			'apple_pay_hash_algorithm'            => array(
				'title'   => __( 'SHA Type', 'amazon_payment_services' ),
				'type'    => 'select',
				'options' => array(
					'sha256'  => __( 'SHA-256', 'amazon_payment_services' ),
					'sha512'  => __( 'SHA-512', 'amazon_payment_services' ),
					'hmac256' => __( 'HMAC-256', 'amazon_payment_services' ),
					'hmac512' => __( 'HMAC-512', 'amazon_payment_services' ),
				),
				'default' => '',
			),
			'apple_pay_button_type'               => array(
				'title'       => __( 'Apple Pay Button Types', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					APS_Constants::APS_APPLE_TYPE_BUY      => $this->button_type_buy,
					APS_Constants::APS_APPLE_TYPE_DONATE   => $this->button_type_donate,
					APS_Constants::APS_APPLE_TYPE_PLAIN    => $this->button_type_plain,
					APS_Constants::APS_APPLE_TYPE_SETUP    => $this->button_type_setup,
					APS_Constants::APS_APPLE_TYPE_BOOK     => $this->button_type_book,
					APS_Constants::APS_APPLE_TYPE_CHECKOUT => $this->button_type_checkout,
					APS_Constants::APS_APPLE_TYPE_SUBSCRIBE => $this->button_type_subscribe,
					APS_Constants::APS_APPLE_TYPE_ADDMONEY => $this->button_type_addmoney,
					APS_Constants::APS_APPLE_TYPE_CONTRIBUTE => $this->button_type_contribute,
					APS_Constants::APS_APPLE_TYPE_ORDER    => $this->button_type_order,
					APS_Constants::APS_APPLE_TYPE_RELOAD   => $this->button_type_reload,
					APS_Constants::APS_APPLE_TYPE_RENT     => $this->button_type_rent,
					APS_Constants::APS_APPLE_TYPE_SUPPORT  => $this->button_type_support,
					APS_Constants::APS_APPLE_TYPE_TIP      => $this->button_type_tip,
					APS_Constants::APS_APPLE_TYPE_TOPUP    => $this->button_type_topup,
				),
				'default'     => APS_Constants::APS_APPLE_TYPE_PLAIN,
				'desc_tip'    => true,
				'placeholder' => '',
				'class'       => 'wc-enhanced-select',
			),
			'apple_pay_access_code'               => array(
				'title'            => __( 'Access Code', 'amazon_payment_services' ),
				'type'             => 'text',
				'default'          => '',
				'desc_tip'         => true,
				'placeholder'      => '',
				'validation_rules' => array( 'no_space' ),
				'max_length'       => 20,
			),
			'apple_pay_request_sha_phrase'        => array(
				'title'            => __( 'Request SHA Phrase', 'amazon_payment_services' ),
				'type'             => 'text',
				'default'          => '',
				'desc_tip'         => true,
				'placeholder'      => '',
				'validation_rules' => array( 'no_space' ),
				'max_length'       => 50,
			),
			'apple_pay_response_sha_phrase'       => array(
				'title'            => __( 'Response SHA Phrase', 'amazon_payment_services' ),
				'type'             => 'text',
				'default'          => '',
				'desc_tip'         => true,
				'placeholder'      => '',
				'validation_rules' => array( 'no_space' ),
				'max_length'       => 50,
			),
			'apple_pay_domain_name'               => array(
				'title'            => __( 'Domain Name', 'amazon_payment_services' ),
				'type'             => 'text',
				'default'          => '',
				'desc_tip'         => true,
				'placeholder'      => '',
				'validation_rules' => array( 'no_space' ),
				'max_length'       => 50,
			),
			'apple_pay_display_name'               => array(
				'title'            => __( 'Display Name', 'amazon_payment_services' ),
				'type'             => 'text',
				'default'          => '',
				'placeholder'      => '',
				'max_length'       => 64,
				'description'      => 'A string of 64 or fewer UTF-8 characters containing the canonical name for your store, suitable for display. Do not localize the name.',
			),
			'apple_pay_supported_networks'        => array(
				'title'       => __( 'Supported Networks', 'amazon_payment_services' ),
				'type'        => 'multiselect',
				'options'     => array(
					'amex'       => __( 'American Express', 'amazon_payment_services' ),
					'masterCard' => __( 'MasterCard', 'amazon_payment_services' ),
					'visa'       => __( 'Visa', 'amazon_payment_services' ),
					'mada'       => __( 'mada', 'amazon_payment_services' ),
				),
				'desc_tip'    => true,
				'placeholder' => '',
				'class'       => 'wc-enhanced-select',
			),
			'apple_pay_production_key'            => array(
				'title'            => __( 'Production Key', 'amazon_payment_services' ),
				'type'             => 'text',
				'default'          => '',
				'desc_tip'         => true,
				'placeholder'      => '',
				'validation_rules' => array( 'no_space' ),
				'max_length'       => 50,
			),
			'applepay_certificates'               => array(
				'title'       => __( 'Apple Pay Certificates', 'amazon_payment_services' ),
				'type'        => 'text_info',
				'description' => '<a href="' . admin_url( 'options-general.php?page=apple-pay-certificates' ) . '"> Click here to view certificates</a>',
			),
			'knet_group'                          => array(
				'title' => __( 'KNET', 'amazon_payment_services' ),
				'type'  => 'title',
			),
			'enable_knet'                         => array(
				'title'       => __( 'Enabled', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					'yes' => __( 'Yes', 'amazon_payment_services' ),
					'no'  => __( 'No', 'amazon_payment_services' ),
				),
				'default'     => 'no',
				'desc_tip'    => true,
				'placeholder' => '',
				'class'       => 'wc-enhanced-select',
			),
			'naps_group'                          => array(
				'title' => __( 'NAPS', 'amazon_payment_services' ),
				'type'  => 'title',
			),
			'enable_naps'                         => array(
				'title'       => __( 'Enabled', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					'yes' => __( 'Yes', 'amazon_payment_services' ),
					'no'  => __( 'No', 'amazon_payment_services' ),
				),
				'default'     => 'no',
				'desc_tip'    => true,
				'placeholder' => '',
				'class'       => 'wc-enhanced-select',
			),
			'visa_checkout_group'                 => array(
				'title' => __( 'Visa Checkout', 'amazon_payment_services' ),
				'type'  => 'title',
			),
			'enable_visa_checkout'                => array(
				'title'       => __( 'Enabled', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					'yes' => __( 'Yes', 'amazon_payment_services' ),
					'no'  => __( 'No', 'amazon_payment_services' ),
				),
				'default'     => 'no',
				'desc_tip'    => true,
				'placeholder' => '',
				'class'       => 'wc-enhanced-select',
			),
			'visa_checkout_integration_type'      => array(
				'title'       => __( 'Integration Type', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					APS_Constants::APS_INTEGRATION_TYPE_REDIRECTION   => $this->redirection,
					APS_Constants::APS_INTEGRATION_TYPE_HOSTED_CHECKOUT => $this->hosted_checkout,
				),
				'default'     => APS_Constants::APS_DEFAULT_INTEGRATION_TYPE,
				'desc_tip'    => true,
				'placeholder' => __( 'Integration Type', 'amazon_payment_services' ),
				'class'       => 'wc-enhanced-select',
			),
			'visa_checkout_api_key'               => array(
				'title'       => __( 'API Key', 'amazon_payment_services' ),
				'type'        => 'text',
				'default'     => '',
				'desc_tip'    => true,
				'placeholder' => '',
				'max_length'  => 50,
			),
			'visa_checkout_profile_id'            => array(
				'title'       => __( 'Profile ID', 'amazon_payment_services' ),
				'type'        => 'text',
				'default'     => '',
				'desc_tip'    => true,
				'placeholder' => '',
				'max_length'  => 50,
			),
			'installments_group'                  => array(
				'title' => __( 'Installments', 'amazon_payment_services' ),
				'type'  => 'title',
			),
			'enable_installment'                  => array(
				'title'       => __( 'Enabled', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					'yes' => __( 'Yes', 'amazon_payment_services' ),
					'no'  => __( 'No', 'amazon_payment_services' ),
				),
				'default'     => 'no',
				'desc_tip'    => true,
				'placeholder' => '',
				'class'       => 'wc-enhanced-select',
			),
			'installment_integration_type'        => array(
				'title'       => __( 'Integration Type', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					APS_Constants::APS_INTEGRATION_TYPE_REDIRECTION   => $this->redirection,
					APS_Constants::APS_INTEGRATION_TYPE_STANDARD_CHECKOUT  => $this->standard_checkout,
					APS_Constants::APS_INTEGRATION_TYPE_HOSTED_CHECKOUT => $this->hosted_checkout,
					APS_Constants::APS_INTEGRATION_TYPE_EMBEDDED_HOSTED_CHECKOUT => $this->embedded_hosted_checkout,
				),
				'default'     => APS_Constants::APS_DEFAULT_INTEGRATION_TYPE,
				'desc_tip'    => true,
				'placeholder' => __( 'Integration Type', 'amazon_payment_services' ),
				'class'       => 'wc-enhanced-select',
			),
			'installment_sar_minimum_order_limit' => array(
				'title'      => __( 'Intallment Order Purchase minimum limit (SAR)', 'amazon_payment_services' ),
				'type'       => 'number',
				'default'    => 1000,
				'max_length' => 5,
			),
			'installment_aed_minimum_order_limit' => array(
				'title'      => __( 'Intallment Order Purchase minimum limit (AED)', 'amazon_payment_services' ),
				'type'       => 'number',
				'default'    => 1000,
				'max_length' => 5,
			),
			'installment_egp_minimum_order_limit' => array(
				'title'      => __( 'Intallment Order Purchase minimum limit (EGP)', 'amazon_payment_services' ),
				'type'       => 'number',
				'default'    => 1000,
				'max_length' => 5,
			),
			'show_issuer_name'                    => array(
				'title'       => __( 'Show issuer name', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					'yes' => 'Yes',
					'no'  => 'No',
				),
				'default'     => 'no',
				'desc_tip'    => true,
				'placeholder' => __( 'Show issuer name', 'amazon_payment_services' ),
				'class'       => 'wc-enhanced-select',
			),
			'show_issuer_logo'                    => array(
				'title'       => __( 'Show issuer logo', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					'yes' => 'Yes',
					'no'  => 'No',
				),
				'default'     => 'no',
				'desc_tip'    => true,
				'placeholder' => __( 'Show issuer logo', 'amazon_payment_services' ),
				'class'       => 'wc-enhanced-select',
			),
			'valu_group'                          => array(
				'title' => __( 'Valu', 'amazon_payment_services' ),
				'type'  => 'title',
			),
			'enable_valu'                         => array(
				'title'       => __( 'Enabled', 'amazon_payment_services' ),
				'type'        => 'select',
				'options'     => array(
					'yes' => __( 'Yes', 'amazon_payment_services' ),
					'no'  => __( 'No', 'amazon_payment_services' ),
				),
				'default'     => 'no',
				'desc_tip'    => true,
				'placeholder' => '',
				'class'       => 'wc-enhanced-select',
			),
			'valu_minimum_order_limit'            => array(
				'title'      => __( 'VALU Order Purchase minimum limit in EGP', 'amazon_payment_services' ),
				'type'       => 'number',
				'default'    => 500,
				'max_length' => 5,
			),
		);
	}
}
