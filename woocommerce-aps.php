<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * APS plugin activation
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://paymentservices.amazon.com/
 * @since             2.2.0
 * @package           APS
 *
 * @wordpress-plugin
 * Plugin Name:       Amazon payment services
 * Plugin URI:        https://paymentservices.amazon.com/
 * Description:       Amazon payment services makes it really easy to start accepting online payments (credit &amp; debit cards) in the Middle East. Sign up is instant, at https://paymentservices.amazon.com/
 * Version:           2.3.7
 * Author:            Amazon Payment Services
 * Author URI:        https://paymentservices.amazon.com/
 * Text Domain:       amazon-payment-services
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'APS_VERSION', '2.3.7' );
define( 'APS_NAME', 'amazon-payment-services' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-aps-activator.php
 */
function activate_aps() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aps-activator.php';
	APS_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-aps-deactivator.php
 */
function deactivate_aps() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aps-deactivator.php';
	APS_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_aps' );
register_deactivation_hook( __FILE__, 'deactivate_aps' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-aps.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    2.2.0
 */
function run_aps() {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( 'Sorry, but this plugin requires the Woocommerce to be installed and active. <br><a href="' . esc_url(admin_url( 'plugins.php' )) . '">&laquo; Return to Plugins</a>' );
	}
	$plugin = new APS();
	$plugin->run();

}
run_aps();

//Register a session
function register_aps_session() {
	
	/*if ( ! session_id() ) {
		session_start();
	}*/
}
add_action( 'init', 'register_aps_session' );

function aps_settings_link( $links ) {
	$settings_link = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . APS_Constants::APS_PAYMENT_TYPE_CC ) . '">Settings</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'aps_settings_link' );

function check_woocommerce_dependency() {
	if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}
}
add_action( 'admin_init', 'check_woocommerce_dependency' );

function create_wc_api_url( $request, $vars = array() ) {
	$api_url = WC()->api_request_url( $request );
	if ( ! empty( $vars ) ) {
		$api_url = add_query_arg(
			$vars,
			$api_url
		);
	}
	return $api_url;
}
