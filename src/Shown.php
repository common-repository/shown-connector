<?php
/**
 * Shown Connector
 *
 * @package ShownConnector
 */

declare( strict_types=1 );

namespace Shown;

/**
 * Main plugin class.
 */
class Shown {

	/**
	 * Shown constructor.
	 */
	public function __construct() {
		define( 'SHOWN_CONNECTOR_API_BASE_URL', 'https://shown.io' );
		define( 'SHOWN_CONNECTOR_PLUGIN_NAME', 'Shown connector' );
		define( 'SHOWN_CONNECTOR_PLUGIN_VERSION', '1.1.3' );
		define( 'SHOWN_CONNECTOR_PLUGIN_SLUG', 'shown-connector' );
		define( 'SHOWN_CONNECTOR_API_ENDPOINT', SHOWN_CONNECTOR_API_BASE_URL . '/api' );
		define( 'SHOWN_CONNECTOR_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __DIR__ ) ) );
		define( 'SHOWN_CONNECTOR_PLUGIN_URL', untrailingslashit( plugin_dir_url( __DIR__ ) ) );
		define( 'SHOWN_CONNECTOR_LOGIN_URL', SHOWN_CONNECTOR_API_BASE_URL . '/login/wordpress/external' );
		define( 'SHOWN_CONNECTOR_ADMIN_URL', add_query_arg( array( 'page' => SHOWN_CONNECTOR_PLUGIN_SLUG ), admin_url( 'admin.php' ) ) );
	}

	/**
	 * Register the plugin admin page and hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'create_admin_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_shown_pixel' ) );

		( new Analytics() )->register();

		( new Products() )->register();
		( new Customers() )->register();

		( new Connect() )->register();
		( new TrackingSnippet() )->register();
		( new Settings() )->register();
	}

	/**
	 * Render the admin page.
	 *
	 * @return void
	 */
	public function create_admin_page(): void {
		add_menu_page(
			SHOWN_CONNECTOR_PLUGIN_NAME,
			SHOWN_CONNECTOR_PLUGIN_NAME,
			'manage_options',
			SHOWN_CONNECTOR_PLUGIN_SLUG,
			array( $this, 'display_admin_page' )
		);
	}

	/**
	 * Display the admin page.
	 *
	 * @throws \Exception If an error occurs while fetching businesses.
	 */
	public function display_admin_page(): void {
		$is_connected = get_option( 'shown_access_token', false ) && get_option( 'shown_business_id', false );

		if ( ! $is_connected ) {
			$nonce_url    = wp_nonce_url( SHOWN_CONNECTOR_ADMIN_URL, 'shown_connector_connect', '_connect_nonce' );
			$connect_link = SHOWN_CONNECTOR_LOGIN_URL . '?bearer_flow_url=' . rawurlencode( $nonce_url );
		} else {
			// Customer settings.
			$sync_customers_on_create = get_option( 'shown_sync_customers_on_create', false );
			$sync_all_customers       = get_option( 'shown_sync_all_customers', false );

			// Product settings.
			$sync_products_on_create = get_option( 'shown_sync_products_on_create', false );
			$sync_all_products       = get_option( 'shown_sync_all_products', false );

			// Website ownership verification settings.
			$enable_website_ownership_verification = get_option( 'shown_enable_website_ownership_verification', false );

			// Snippet settings.
			$saved_business_id = get_option( 'shown_business_id' );
			$saved_snippet_url = get_option( 'shown_snippet_url', false );
			$access_token      = get_option( 'shown_access_token', false );
			$error_message     = null;
			$businesses        = array();
			if ( $saved_business_id && $access_token ) {
				$shown_api = new ShownApi();
				$result    = $shown_api->get_businesses();
				if ( ! is_wp_error( $result ) ) {
					$businesses = $result;
				} else {
					$error_code    = $result->get_error_code();
					$error_message = $result->get_error_message();
					$error_message = "Error ($error_code): $error_message";
				}
			}
		}
		require_once SHOWN_CONNECTOR_PLUGIN_PATH . '/views/shown.php';
	}

	/**
	 * Enqueue both admin and front end assets
	 */
	public function enqueue_admin_assets() {
		if ( ! is_admin() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && strpos( $screen->id, 'shown-connector' ) !== false ) {
			wp_enqueue_style(
				SHOWN_CONNECTOR_PLUGIN_SLUG,
				SHOWN_CONNECTOR_PLUGIN_URL . '/assets/admin/style.css',
				array(),
				SHOWN_CONNECTOR_PLUGIN_VERSION
			);

			wp_enqueue_script(
				SHOWN_CONNECTOR_PLUGIN_SLUG,
				SHOWN_CONNECTOR_PLUGIN_URL . '/assets/admin/script.js',
				array( 'jquery' ),
				SHOWN_CONNECTOR_PLUGIN_VERSION,
				true
			);
		}
	}

	/**
	 * Enqueue Shown pixel
	 */
	public function enqueue_shown_pixel() {
		$snippet_url = get_option( 'shown_snippet_url', '' );

		if ( $snippet_url ) {
			wp_enqueue_script(
				SHOWN_CONNECTOR_PLUGIN_SLUG . '-pxl',
				esc_url( $snippet_url ),
				array(),
				SHOWN_CONNECTOR_PLUGIN_VERSION,
				true
			);
		}
	}
}
