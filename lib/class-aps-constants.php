<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * APS constants
 *
 * @link       https://paymentservices.amazon.com/
 * @since      2.2.0
 *
 * @package    APS
 * @subpackage APS/lib
 */

/**
 * APS constants
 *
 * @since      2.2.0
 * @package    APS
 * @subpackage APS/lib
 */
class APS_Constants {
	/**
	 * Defining constants
	 */
	// Payment Types
	const APS_GATEWAY_ID                 = 'amazon_payment_services';
	const APS_PAYMENT_TYPE_CC            = 'aps_cc';
	const APS_PAYMENT_TYPE_VALU          = 'aps_valu';
	const APS_PAYMENT_TYPE_INSTALLMENT   = 'aps_installment';
	const APS_PAYMENT_TYPE_NAPS          = 'aps_naps';
	const APS_PAYMENT_TYPE_BENEFIT          = 'aps_benefit';
	const APS_PAYMENT_TYPE_KNET          = 'aps_knet';
    const APS_PAYMENT_TYPE_OMANNET       = 'aps_omannet';
	const APS_PAYMENT_TYPE_VISA_CHECKOUT = 'aps_visa_checkout';
	const APS_PAYMENT_TYPE_APPLE_PAY     = 'aps_apple_pay';
	const APS_PAYMENT_TYPE_STC_PAY       = 'aps_stc_pay';
    const APS_PAYMENT_TYPE_TABBY       = 'aps_tabby';
	const APS_RETRY_PAYMENT_METHODS      = array(
		'aps_cc',
		'aps_installment',
		'aps_visa_checkout',
	);

	// Integration Types Values
	const APS_INTEGRATION_TYPE_REDIRECTION       = 'redirection';
	const APS_INTEGRATION_TYPE_STANDARD_CHECKOUT = 'standard_checkout';
	const APS_INTEGRATION_TYPE_HOSTED_CHECKOUT   = 'hosted_checkout';
	const APS_INTEGRATION_TYPE_EMBEDDED_HOSTED_CHECKOUT = 'embedded_hosted_checkout';
	const APS_DEFAULT_INTEGRATION_TYPE           = 'redirection';

	// Payment Method Name
	const APS_PAYMENT_METHOD_NAPS      = 'NAPS';
	const APS_PAYMENT_METHOD_KNET      = 'KNET';
    const APS_PAYMENT_METHOD_OMANNET      = 'OMANNET';
	const APS_PAYMENT_METHOD_VALU      = 'VALU';
	const APS_PAYMENT_METHOD_BENEFIT     = 'BENEFIT';
	const APS_PAYMENT_METHOD_APPLE_PAY = 'APPLE_PAY';
	const APS_PAYMENT_METHOD_STC_PAY   = 'STCPAY';
    const APS_PAYMENT_METHOD_TABBY     = 'TABBY';
	const APS_RETRY_PAYMENT_OPTIONS    = array(
		'VISA',
		'MASTERCARD',
		'AMEX',
		'MADA',
		'MEEZA',
	);
	const APS_RETRY_DIGITAL_WALLETS    = array(
		'VISA_CHECKOUT',
	);

	//Payment response coder
	const APS_PAYMENT_SUCCESS_RESPONSE_CODE               = '14000';
	const APS_TOKENIZATION_SUCCESS_RESPONSE_CODE          = '18000';
	const APS_SAFE_TOKENIZATION_SUCCESS_RESPONSE_CODE     = '18062';
	const APS_UPDATE_TOKENIZATION_SUCCESS_RESPONSE_CODE   = '18063';
	const APS_PAYMENT_CANCEL_RESPONSE_CODE                = '00072';
	const APS_MERCHANT_SUCCESS_RESPONSE_CODE              = '20064';
	const APS_GET_INSTALLMENT_SUCCESS_RESPONSE_CODE       = '62000';
	const APS_VALU_CUSTOMER_VERIFY_SUCCESS_RESPONSE_CODE  = '90000';
	const APS_VALU_CUSTOMER_VERIFY_FAILED_RESPONSE_CODE   = '00160';
	const APS_VALU_OTP_GENERATE_SUCCESS_RESPONSE_CODE     = '88000';
	const APS_VALU_OTP_VERIFY_SUCCESS_RESPONSE_CODE       = '92182';
	const APS_STC_PAY_OTP_GENERATE_SUCCESS_RESPONSE_CODE  = '88000';
	const APS_STC_PAY_OTP_VERIFY_SUCCESS_RESPONSE_CODE    = '92182';
    const APS_TABBY_OTP_GENERATE_SUCCESS_RESPONSE_CODE     = '88000';
    const APS_TABBY_OTP_VERIFY_SUCCESS_RESPONSE_CODE       = '92182';
	const APS_REFUND_SUCCESS_RESPONSE_CODE                = '06000';
	const APS_PAYMENT_AUTHORIZATION_SUCCESS_RESPONSE_CODE = '02000';
	const APS_TOKEN_SUCCESS_RESPONSE_CODE                 = '52062';
	const APS_TOKEN_SUCCESS_STATUS_CODE                   = '52';
	const APS_CAPTURE_SUCCESS_RESPONSE_CODE               = '04000';
	const APS_AUTHORIZATION_VOIDED_SUCCESS_RESPONSE_CODE  = '08000';
	const APS_CHECK_STATUS_SUCCESS_RESPONSE_CODE          = '12000';
	const APS_ONHOLD_RESPONSE_CODES                       = array(
		'15777',
		'15778',
		'15779',
		'15780',
		'15781',
		'00006',
		'01006',
		'02006',
		'03006',
		'04006',
		'05006',
		'06006',
		'07006',
		'08006',
		'09006',
		'11006',
//		'13006',
		'17006',
	);
	const APS_FAILED_RESPONSE_CODES                       = array(
		'13666',
		'00072',
	);

	// Flash Message status
	const APS_FLASH_MESSAGE_ERROR   = 'error';
	const APS_FLASH_MESSAGE_SUCCESS = 'success';

	// Selector constants
	const APS_SELECTOR_PAYMENT_REQFORM_ID = 'aps_payment_form';

	// API Command
	const APS_COMMAND_GET_INSTALLMENT_PLANS = 'GET_INSTALLMENTS_PLANS';
	const APS_COMMAND_TOKENIZATION          = 'TOKENIZATION';
	const APS_COMMAND_STANDALONE            = 'STANDALONE';
	const APS_COMMAND_PURCHASE              = 'PURCHASE';
	const APS_COMMAND_AUTHORIZATION         = 'AUTHORIZATION';
	const APS_COMMAND_VISA_CHECKOUT_WALLET  = 'VISA_CHECKOUT';
	const APS_COMMAND_REFUND                = 'REFUND';
	const APS_COMMAND_RECURRING             = 'RECURRING';
	const APS_COMMAND_CAPTURE               = 'CAPTURE';
	const APS_COMMAND_VOID                  = 'VOID_AUTHORIZATION';
	const APS_COMMAND_ECOMMERCE             = 'ECOMMERCE';
	const APS_COMMAND_CHECK_STATUS          = 'CHECK_STATUS';

	// Generic Constants
	const APS_VALU_EG_COUNTRY_CODE         = '+20';

	const APS_STC_PAY_SAR_COUNTRY_CODE      = '+966';

    const APS_TABBY_SAR_COUNTRY_CODE      = '+966';

	const APS_STATUS_CRON_DEFAULT_DURATION = '60';

	// Apple Button Types
	const APS_APPLE_TYPE_BUY        = 'apple-pay-buy';
	const APS_APPLE_TYPE_DONATE     = 'apple-pay-donate';
	const APS_APPLE_TYPE_PLAIN      = 'apple-pay-plain';
	const APS_APPLE_TYPE_SETUP      = 'apple-pay-set-up';
	const APS_APPLE_TYPE_BOOK       = 'apple-pay-book';
	const APS_APPLE_TYPE_CHECKOUT   = 'apple-pay-check-out';
	const APS_APPLE_TYPE_SUBSCRIBE  = 'apple-pay-subscribe';
	const APS_APPLE_TYPE_ADDMONEY   = 'apple-pay-add-money';
	const APS_APPLE_TYPE_CONTRIBUTE = 'apple-pay-contribute';
	const APS_APPLE_TYPE_ORDER      = 'apple-pay-order';
	const APS_APPLE_TYPE_RELOAD     = 'apple-pay-reload';
	const APS_APPLE_TYPE_RENT       = 'apple-pay-rent';
	const APS_APPLE_TYPE_SUPPORT    = 'apple-pay-support';
	const APS_APPLE_TYPE_TIP        = 'apple-pay-tip';
	const APS_APPLE_TYPE_TOPUP      = 'apple-pay-top-up';

	// Post types
	const APS_CAPTURE_POST_TYPE = 'aps_capture_trans';
	const APS_REFUND_POST_TYPE  = 'shop_order_refund';

	//Bins
	const MADA_BINS  = '440647|440795|446404|457865|968208|457997|474491|636120|417633|468540|468541|468542|468543|968201|446393|409201|458456|484783|462220|455708|410621|455036|486094|486095|486096|504300|440533|489318|489319|445564|968211|410685|406996|432328|428671|428672|428673|968206|446672|543357|434107|407197|407395|412565|431361|604906|521076|529415|535825|543085|524130|554180|549760|968209|524514|529741|537767|535989|536023|513213|520058|558563|588848|588850|407520|968202|410834|968204|422817|422818|422819|428331|483010|483011|483012|589206|968207|419593|439954|530060|531196|420132|421141|588845|403024|968205|406136|42689700|605141|968203|242030|442463|22402030|22337902|22337986';
	const MEEZA_BINS = '507803[0-6][0-9]|507808[3-9][0-9]|507809[0-9][0-9]|507810[0-2][0-9]';
}

