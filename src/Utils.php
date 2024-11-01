<?php
/**
 * Helper functions.
 *
 * @package ShownConnector
 */

namespace Shown;

/**
 * Class Utils
 */
class Utils {

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_active(): bool {
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
		return in_array( 'woocommerce/woocommerce.php', $active_plugins, true ) || array_key_exists(
			'woocommerce/woocommerce.php',
			$active_plugins
		) || class_exists( 'WooCommerce' );
	}
}
