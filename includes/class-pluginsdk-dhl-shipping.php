<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://plugins.dk/
 * @since      1.0.0
 *
 * @package    Pluginsdk_Dhl_Shipping
 * @subpackage Pluginsdk_Dhl_Shipping/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Pluginsdk_Dhl_Shipping
 * @subpackage Pluginsdk_Dhl_Shipping/includes
 * @author     Plugins.dk <support@plugins.dk>
 */
class Pluginsdk_Dhl_Shipping {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Pluginsdk_Dhl_Shipping_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
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
	 * @since    1.0.0
	 */
	public function __construct() {
		if(defined('PLUGINSDK_DHL_SHIPPING_VERSION')) {
			$this->version = PLUGINSDK_DHL_SHIPPING_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'pluginsdk-dhl-shipping';
		$this->plugin_slug = 'pluginsdk-dhl-shipping';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		add_action('init', array($this, 'pluginsdk_dhl_shipping_load_shipping_method_class'), 2, 1);
		add_action('init', array($this, 'pluginsdk_dhl_shipping_load_checkout_fields'));
		add_action('init', array($this, 'pluginsdk_dhl_shipping_load_edit_order_fields'));

		if(defined('WP_DEBUG')) {
			if(false === WP_DEBUG) {
				error_reporting(0);
			}
		}

	}

	public function pluginsdk_dhl_shipping_load_shipping_method_class() {
		require_once plugin_dir_path( __FILE__ ) . '/shipping/class-pluginsdk-dhl-shipping-private.php';
		require_once plugin_dir_path( __FILE__ ) . '/shipping/class-pluginsdk-dhl-shipping-pickup.php';
	}

	public function pluginsdk_dhl_shipping_load_checkout_fields() {
		require_once plugin_dir_path( dirname(__FILE__ ) ) . 'public/partials/pluginsdk-dhl-shipping-public-display.php';
		require_once plugin_dir_path( dirname(__FILE__ ) ) . 'public/class-pluginsdk-dhl-shipping-public.php';
	}

	public function pluginsdk_dhl_shipping_load_edit_order_fields() {
		require_once plugin_dir_path( dirname(__FILE__ ) ) . 'admin/partials/pluginsdk-dhl-shipping-admin-display.php';
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Pluginsdk_Dhl_Shipping_Loader. Orchestrates the hooks of the plugin.
	 * - Pluginsdk_Dhl_Shipping_i18n. Defines internationalization functionality.
	 * - Pluginsdk_Dhl_Shipping_Admin. Defines all hooks for the admin area.
	 * - Pluginsdk_Dhl_Shipping_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)).'includes/class-pluginsdk-dhl-shipping-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)).'includes/class-pluginsdk-dhl-shipping-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)).'admin/class-pluginsdk-dhl-shipping-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)).'public/class-pluginsdk-dhl-shipping-public.php';

		$this->loader = new Pluginsdk_Dhl_Shipping_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Pluginsdk_Dhl_Shipping_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Pluginsdk_Dhl_Shipping_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Pluginsdk_Dhl_Shipping_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
		
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'pluginsdk_dhl_shipping_dhl_order_label');
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'pluginsdk_dhl_shipping_dhl_order_track_and_trace');
 


		$this->loader->add_action( 'wp_ajax_pluginsdk_dhl_shipping_ajax_generate', $plugin_admin, 'pluginsdk_dhl_shipping_ajax_generate' );
		$this->loader->add_action( 'wp_ajax_pluginsdk_dhl_shipping_ajax_download', $plugin_admin, 'pluginsdk_dhl_shipping_ajax_download' );
		$this->loader->add_action( 'wp_ajax_pluginsdk_dhl_shipping_ajax_download_return', $plugin_admin, 'pluginsdk_dhl_shipping_ajax_download_return' );
		
		$this->loader->add_action('admin_menu', $plugin_admin, 'dhl_settings_menu');
		$this->loader->add_action('admin_init', $plugin_admin, 'dhl_register_settings');

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Pluginsdk_Dhl_Shipping_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

	    // Registering action hook to update order meta during WooCommerce checkout
		$this->loader->add_action('woocommerce_checkout_update_order_meta', $plugin_public, 'pluginsdk_dhl_shipping_update_order_meta_method');
		//$this->loader->add_action('woocommerce_checkout_create_order', $plugin_public, 'pluginsdk_dhl_shipping_update_order_meta_method');

		$this->loader->add_action('wp_ajax_retrieve_parcelshops', $plugin_public, 'retrieve_parcelshops');
		$this->loader->add_action('wp_ajax_nopriv_retrieve_parcelshops', $plugin_public, 'retrieve_parcelshops');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Pluginsdk_Dhl_Shipping_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
