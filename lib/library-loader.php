<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The class responsible to load all libraries
 */
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'lib/class-aps-super.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'lib/class-aps-config.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'lib/class-aps-payment.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'lib/class-aps-order.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'lib/class-aps-helper.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'lib/class-aps-refund.php';
