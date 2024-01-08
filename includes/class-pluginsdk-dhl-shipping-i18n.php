<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://plugins.dk/
 * @since      1.0.0
 *
 * @package    Pluginsdk_Dhl_Shipping
 * @subpackage Pluginsdk_Dhl_Shipping/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Pluginsdk_Dhl_Shipping
 * @subpackage Pluginsdk_Dhl_Shipping/includes
 * @author     Plugins.dk <support@plugins.dk>
 */
class Pluginsdk_Dhl_Shipping_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'pluginsdk-dhl-shipping',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
