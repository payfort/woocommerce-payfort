<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://paymentservices.amazon.com/
 * @since      2.2.0
 *
 * @package    APS
 * @subpackage APS/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      2.2.0
 * @package    APS
 * @subpackage APS/includes
 */
class APS_I18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    2.2.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'amazon-payment-services',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
