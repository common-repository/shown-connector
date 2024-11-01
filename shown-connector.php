<?php
/**
 * @package ShownConnector
 *
 * Plugin Name: Shown Connector
 * Plugin URI: https://shown.io/
 * Description: Sync data between WordPress, WooCommerce and Shown.
 * Version: 1.1.3
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Author: shown.io
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include the Composer autoloader.
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

/**
 * Initializes and runs the plugin.
 */
function shown_connector_plugin_init() {
	$shown_connector = new \Shown\Shown();
	$shown_connector->register();
}
shown_connector_plugin_init();
