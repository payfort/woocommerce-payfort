<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * All functions of APS payment gateways
 *
 * @link       https://paymentservices.amazon.com/
 * @since      2.2.0
 *
 * @package    APS
 * @subpackage APS/includes
 */

/**
 * All functions of APS payment gateways
 *
 * @since      2.2.0
 * @package    APS
 * @subpackage APS/includes
 */
class Gateway_Loader {

	/**
	 * Init APS Gateways
	 *
	 * @return array
	 */
	public function init_gateways( $gateways ) {
		//Below condition check to show supported payment options in frontend only
		if ( is_admin() && isset($_GET['tab']) && 'checkout' == $_GET['tab'] && isset($_GET['page']) && 'wc-settings' == $_GET['page'] ) {
			//Load list of supported payment options for checkout view
			$gateways[] = 'WC_Gateway_APS';
		} else {
			$gateways[] = 'WC_Gateway_APS_Apple_Pay';
			$gateways[] = 'WC_Gateway_APS';
			$gateways[] = 'WC_Gateway_APS_Knet';
            $gateways[] = 'WC_Gateway_APS_Omannet';
			$gateways[] = 'WC_Gateway_APS_Naps';
			$gateways[] = 'WC_Gateway_APS_Benefit';
			$gateways[] = 'WC_Gateway_APS_Valu';
			$gateways[] = 'WC_Gateway_APS_Installments';
			$gateways[] = 'WC_Gateway_APS_Visa_Checkout';
			$gateways[] = 'WC_Gateway_APS_STC_Pay';
            $gateways[] = 'WC_Gateway_APS_TABBY';
		}
		return $gateways;
	}

	/**
	 * Load gateway class files
	 *
	 * @return void
	 */
	public function load_gateway_classes() {
		/* The class responsible to load all libraries */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'lib/library-loader.php';

		/* The class responsible to load super gateway class */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-wc-gateway-aps-super.php';

		/* Require aps credit card gateway class */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-wc-gateway-aps.php';

		/* Require valu gateway class */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-wc-gateway-aps-valu.php';

		/* Require installment gateway class */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-wc-gateway-aps-installments.php';

		/* Require Naps gateway class */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-wc-gateway-aps-naps.php';

        /* Require Naps gateway class */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-wc-gateway-aps-benefit.php';

        /* Require knet gateway class */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-wc-gateway-aps-knet.php';

        /* Require Omannet gateway class */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-wc-gateway-aps-omannet.php';

		/* Require visa checkout gateway class */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-wc-gateway-aps-visa-checkout.php';

		/* Require apple pay gateway class */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-wc-gateway-aps-apple-pay.php';

		/* Require stc pay gateway class */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-wc-gateway-aps-stc-pay.php';

        /* Require tabby gateway class */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-wc-gateway-aps-tabby.php';
	}

	/**
	 * Available Gateways
	 */
	public function aps_available_payment_gateways( $available_gateways ) {
        if ( ! is_checkout() ) {
			unset( $available_gateways[ APS_Constants::APS_PAYMENT_TYPE_VALU ] );
			unset( $available_gateways[ APS_Constants::APS_PAYMENT_TYPE_INSTALLMENT ] );
			unset( $available_gateways[ APS_Constants::APS_PAYMENT_TYPE_NAPS ] );
			unset( $available_gateways[ APS_Constants::APS_PAYMENT_TYPE_BENEFIT ] );
			unset( $available_gateways[ APS_Constants::APS_PAYMENT_TYPE_KNET ] );
            unset( $available_gateways[ APS_Constants::APS_PAYMENT_TYPE_OMANNET ] );
			unset( $available_gateways[ APS_Constants::APS_PAYMENT_TYPE_VISA_CHECKOUT ] );
			unset( $available_gateways[ APS_Constants::APS_PAYMENT_TYPE_APPLE_PAY ] );
			unset( $available_gateways[ APS_Constants::APS_PAYMENT_TYPE_STC_PAY ] );
            unset( $available_gateways[ APS_Constants::APS_PAYMENT_TYPE_TABBY ] );
		}
		return $available_gateways;
	}
}
