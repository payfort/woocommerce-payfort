<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://paymentservices.amazon.com/
 * @since      2.2.0
 *
 * @package    APS
 * @subpackage APS/includes
 */
/**
 * The core plugin class.
 *load_plugin_textdomain
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      2.2.0
 * @package    APS
 * @subpackage APS/includes
 */
class APS {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    2.2.0
	 * @var      APS_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    2.2.0
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    2.2.0
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    2.2.0
	 */
	public function __construct() {
		if ( defined( 'APS_VERSION' ) ) {
			$this->version = APS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = APS_NAME;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->load_aps_gateway();
		$this->load_ajax_routes();
		$this->load_wc_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - APS_Loader. Orchestrates the hooks of the plugin.
	 * - APS_I18n. Defines internationalization functionality.
	 * - APS_Admin. Defines all hooks for the admin area.
	 * - APS_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    2.2.0
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for contants
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'lib/class-aps-constants.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		/* The class responsible to load all libraries */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-aps-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-aps-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-aps-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-aps-public.php';

		/**
		 * The class responsible for loading config fields
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-aps-fields-loader.php';

		/**
		 * The class responsible for defining all actions that occur in the aps gateway
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-gateway-loader.php';

		/**
		 * The class responsible for defining all ajax actions that occur in the aps gateway
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-aps-ajax.php';

		/**
		 * This class is responsible to all common woocommerce hooks
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-aps-wc-hooks.php';

		$this->loader = new APS_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the APS_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    2.2.0
	 */
	private function set_locale() {

		$plugin_i18n = new APS_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    2.2.0
	 */
	private function define_admin_hooks() {

		$plugin_admin = new APS_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    2.2.0
	 */
	private function define_public_hooks() {

		$plugin_public = new APS_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'plugins_loaded', $plugin_public, 'load_helpers' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_filter( 'woocommerce_locate_template', $plugin_public, 'woocommerce_override_template', 1, 3 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    2.2.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     2.2.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     2.2.0
	 * @return    APS_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     2.2.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Load all payment gateways
	 *
	 * @since     2.2.0
	 * @return void
	 */
	public function load_aps_gateway() {

		$gate_loader = new Gateway_Loader();
		$this->loader->add_filter( 'woocommerce_payment_gateways', $gate_loader, 'init_gateways' );
		$this->loader->add_action( 'plugins_loaded', $gate_loader, 'load_gateway_classes' );
		$this->loader->add_filter( 'woocommerce_available_payment_gateways', $gate_loader, 'aps_available_payment_gateways' );
	}

	public function load_ajax_routes() {
		//Ajax hooks
		$aps_ajax = new APS_Ajax();
		//Load Ajax handler
		$this->loader->add_action( 'plugins_loaded', $aps_ajax, 'load_helpers' );

		$this->loader->add_action( 'wp_ajax_get_installment_plans', $aps_ajax, 'get_installment_handler' );
		$this->loader->add_action( 'wp_ajax_nopriv_get_installment_plans', $aps_ajax, 'get_installment_handler' );

		$this->loader->add_action( 'wp_ajax_valu_verify_customer', $aps_ajax, 'valu_verify_customer' );
		$this->loader->add_action( 'wp_ajax_nopriv_valu_verify_customer', $aps_ajax, 'valu_verify_customer' );

		$this->loader->add_action( 'wp_ajax_valu_otp_verify', $aps_ajax, 'valu_otp_verify' );
		$this->loader->add_action( 'wp_ajax_nopriv_valu_otp_verify', $aps_ajax, 'valu_otp_verify' );

		$this->loader->add_action( 'wp_ajax_valu_proceed_tenure', $aps_ajax, 'valu_otp_verify' );
		$this->loader->add_action( 'wp_ajax_nopriv_valu_otp_verify', $aps_ajax, 'valu_otp_verify' );

		$this->loader->add_action( 'wp_ajax_valu_set_tenure', $aps_ajax, 'valu_set_tenure' );
		$this->loader->add_action( 'wp_ajax_nopriv_valu_set_tenure', $aps_ajax, 'valu_set_tenure' );

		$this->loader->add_action( 'wp_ajax_validate_apple_url', $aps_ajax, 'validate_apple_url' );
		$this->loader->add_action( 'wp_ajax_nopriv_validate_apple_url', $aps_ajax, 'validate_apple_url' );

		$this->loader->add_action( 'wp_ajax_validate_apple_pay_shipping_address', $aps_ajax, 'validate_apple_pay_shipping_address' );
		$this->loader->add_action( 'wp_ajax_nopriv_validate_apple_pay_shipping_address', $aps_ajax, 'validate_apple_pay_shipping_address' );

		$this->loader->add_action( 'wp_ajax_get_apple_pay_cart_data', $aps_ajax, 'get_apple_pay_cart_data' );
		$this->loader->add_action( 'wp_ajax_nopriv_get_apple_pay_cart_data', $aps_ajax, 'get_apple_pay_cart_data' );

		$this->loader->add_action( 'wp_ajax_create_cart_order', $aps_ajax, 'create_cart_order' );
		$this->loader->add_action( 'wp_ajax_nopriv_create_cart_order', $aps_ajax, 'create_cart_order' );

		$this->loader->add_action( 'wp_ajax_create_aps_token_builder', $aps_ajax, 'create_aps_token_builder' );
		$this->loader->add_action( 'wp_ajax_aps_payment_authorization', $aps_ajax, 'aps_payment_authorization' );
		$this->loader->add_action( 'wp_ajax_nopriv_aps_payment_authorization', $aps_ajax, 'aps_payment_authorization' );
	}

	public function load_wc_hooks() {
		//APS hooks
		$aps_wc_hooks = new APS_WC_Hooks();
		//Load APS handler
		$this->loader->add_action( 'woocommerce_after_checkout_validation', $aps_wc_hooks, 'aps_checkout_validation', 10, 2 );
		$this->loader->add_action( 'plugins_loaded', $aps_wc_hooks, 'load_helpers' );
		$this->loader->add_action( 'woocommerce_scheduled_subscription_payment_' . APS_Constants::APS_PAYMENT_TYPE_CC, $aps_wc_hooks, 'aps_subscription_payment', 10, 2 );
        $this->loader->add_action( 'woocommerce_scheduled_subscription_payment_' . APS_Constants::APS_PAYMENT_TYPE_STC_PAY, $aps_wc_hooks, 'aps_stc_subscription_payment', 10, 2 );
        $this->loader->add_action( 'woocommerce_scheduled_subscription_payment_' . APS_Constants::APS_PAYMENT_TYPE_TABBY, $aps_wc_hooks, 'aps_tabby_subscription_payment', 10, 2 );
        $this->loader->add_action( 'woocommerce_payment_token_deleted', $aps_wc_hooks, 'aps_token_deleted', 10, 2 );
		$this->loader->add_filter( 'woocommerce_payment_methods_list_item', $aps_wc_hooks, 'aps_render_payment_methods', 10, 2 );

		//Add custom messages
		$this->loader->add_action( 'woocommerce_account_content', $aps_wc_hooks, 'show_token_response_messages', 1, 2 );

		//Run cron
		$this->loader->add_action( 'aps_pending_payment_cron', $aps_wc_hooks, 'aps_pending_payment_cron_handler' );

		//Apple pay hooks
		$this->loader->add_action( 'woocommerce_proceed_to_checkout', $aps_wc_hooks, 'aps_apple_pay_button_in_cart', 99 );
		$this->loader->add_action( 'woocommerce_after_add_to_cart_form', $aps_wc_hooks, 'aps_apple_pay_button_in_product', 99 );

		//mada title update
		$this->loader->add_action('woocommerce_get_credit_card_type_label', $aps_wc_hooks, 'aps_wocommerce_credit_card_type_labels', 10, 1);

	}
}
