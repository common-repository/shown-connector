<?php
/**
 * Settings tab.
 *
 * @package ShownConnector
 */

declare( strict_types=1 );

namespace Shown;

/**
 * Class SettingsTab
 */
class Settings {

	/**
	 * Register settings tab hooks.
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
		add_action( 'wp_head', array( $this, 'inject_meta_tags' ) );
	}

	/**
	 * Handle the form submission.
	 *
	 * @return void
	 */
	public function handle_form_submission() {

		if ( empty( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['_setting_nonce'] ) ) {
			return;
		}

		$customers_nonce = sanitize_textarea_field( wp_unslash( $_POST['_setting_nonce'] ) );
		if ( ! $customers_nonce ) {
			return;
		}

		// Save customer's settings.
		if ( wp_verify_nonce( $customers_nonce, 'shown_connector_setting_customers' ) ) {
			$sync_customers_on_create = isset( $_POST['sync_customers_on_create'] ) && filter_var( wp_unslash( $_POST['sync_customers_on_create'] ), FILTER_VALIDATE_BOOLEAN );
			$sync_all_customers       = isset( $_POST['sync_all_customers'] ) && filter_var( wp_unslash( $_POST['sync_all_customers'] ), FILTER_VALIDATE_BOOLEAN );

			update_option( 'shown_sync_customers_on_create', $sync_customers_on_create );
			update_option( 'shown_sync_all_customers', $sync_all_customers );

		}

		// Save products' settings.
		if ( wp_verify_nonce( $customers_nonce, 'shown_connector_setting_products' ) ) {
			$sync_products_on_create = isset( $_POST['sync_products_on_create'] ) && filter_var( wp_unslash( $_POST['sync_products_on_create'] ), FILTER_VALIDATE_BOOLEAN );
			$sync_all_products       = isset( $_POST['sync_all_products'] ) && filter_var( wp_unslash( $_POST['sync_all_products'] ), FILTER_VALIDATE_BOOLEAN );

			update_option( 'shown_sync_products_on_create', $sync_products_on_create );
			update_option( 'shown_sync_all_products', $sync_all_products );
		}

		// Save website ownership verification settings.
		if ( wp_verify_nonce( $customers_nonce, 'shown_connector_setting_site_verification' ) ) {
			$enable_website_ownership_verification = isset( $_POST['site_ownership_verification_tags'] ) && filter_var( wp_unslash( $_POST['site_ownership_verification_tags'] ), FILTER_VALIDATE_BOOLEAN );

			update_option( 'shown_enable_website_ownership_verification', $enable_website_ownership_verification );

			$business_id  = get_option( 'shown_business_id', null );
			$access_token = get_option( 'shown_access_token', null );
			if ( $business_id && $access_token && $enable_website_ownership_verification ) {
				$api  = new ShownApi( $business_id, $access_token );
				$tags = $api->get_website_ownership_verification_tags();
				if ( ! empty( $tags ) ) {
					update_option( 'shown_website_ownership_verification_tags', $tags );
				}
			}
		}
	}

	/**
	 * Inject meta tags in front-end.
	 *
	 * @return void
	 */
	public function inject_meta_tags(): void {
		if ( ! get_option( 'shown_enable_website_ownership_verification', false ) ) {
			return;
		}
		$tags = get_option( 'shown_website_ownership_verification_tags' );
		if ( ! $tags ) {
			return;
		}
		foreach ( $tags as $tag ) {
			if ( isset( $tag['name'] ) && isset( $tag['content'] ) ) {
				echo '<meta name=' . esc_attr( $tag['name'] ) . ' content=' . esc_attr( $tag['content'] ) . '>' . PHP_EOL;
			}
		}
	}
}
