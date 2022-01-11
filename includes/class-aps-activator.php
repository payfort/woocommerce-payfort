<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Fired during plugin activation
 *
 * @link       https://paymentservices.amazon.com/
 * @since      2.2.0
 *
 * @package    APS
 * @subpackage APS/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      2.2.0
 * @package    APS
 * @subpackage APS/includes
 */
class APS_Activator {

	/**
	 * Method execute during plugin activation
	 *
	 * @since    2.2.0
	 */
	public static function activate() {
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			wp_die( 'Sorry, but this plugin requires the Woocommerce to be installed and active. <br><a href="' . esc_url(admin_url( 'plugins.php' )) . '">&laquo; Return to Plugins</a>' );
		}
		//Schedule an action if it's not already scheduled
		if ( ! wp_next_scheduled( 'aps_pending_payment_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'aps_pending_payment_cron' );
		}
	}

}
