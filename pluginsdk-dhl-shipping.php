<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://plugins.dk/
 * @since             1.0.0
 * @package           Pluginsdk_Dhl_Shipping
 *
 * @wordpress-plugin
 * Plugin Name:       Plugins.dk DHL shipping for Woocommerc
 * Plugin URI:        https://plugins.dk/
 * Description:       DHL forsendelse til WooCommerce
 * Version:           1.0.0
 * Author:            Plugins.dk
 * Author URI:        https://plugins.dk//
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       pluginsdk-dhl-shipping
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PLUGINSDK_DHL_SHIPPING_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-pluginsdk-dhl-shipping-activator.php
 */
function activate_pluginsdk_dhl_shipping() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-pluginsdk-dhl-shipping-activator.php';
	Pluginsdk_Dhl_Shipping_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-pluginsdk-dhl-shipping-deactivator.php
 */
function deactivate_pluginsdk_dhl_shipping() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-pluginsdk-dhl-shipping-deactivator.php';
	Pluginsdk_Dhl_Shipping_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_pluginsdk_dhl_shipping' );
register_deactivation_hook( __FILE__, 'deactivate_pluginsdk_dhl_shipping' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-pluginsdk-dhl-shipping.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_pluginsdk_dhl_shipping() {

	$plugin = new Pluginsdk_Dhl_Shipping();
	$plugin->run();

}
run_pluginsdk_dhl_shipping();
