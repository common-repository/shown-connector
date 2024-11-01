<?php
/**
 * Connect tab.
 *
 * @package ShownConnector
 */

declare( strict_types=1 );

namespace Shown;

/**
 * Class ConnectTab
 */
class Connect {

	/**
	 * Register the tab hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'handle_requests' ) );
	}

	/**
	 * Handle the form submission.
	 *
	 * @return void
	 */
	public function handle_requests() {
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$this->disconnect();

		} else {
			$this->handle_connect();
		}
	}

	/**
	 * Save the access token and business id to the database.
	 *
	 * @return void
	 */
	public function handle_connect() {
		// The redirect happens on the Shown side, so we make sure it's a GET request.
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' === $_SERVER['REQUEST_METHOD'] ) {
			// Get the current request URI.
			$uri                 = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$current_request_uri = html_entity_decode( $uri );

			// Extract and sanitize parameters.
			$query_params = array();
			parse_str( (string) wp_parse_url( $current_request_uri, PHP_URL_QUERY ), $query_params );

			$is_valid_connect_request = isset( $query_params['_connect_nonce'] ) && wp_verify_nonce( $query_params['_connect_nonce'], 'shown_connector_connect' );

			if ( $is_valid_connect_request ) {
				$access_token = isset( $query_params['access_token'] ) ? sanitize_text_field( $query_params['access_token'] ) : null;
				$business_id  = isset( $query_params['business_id'] ) ? sanitize_text_field( $query_params['business_id'] ) : null;

				if ( $access_token ) {
					update_option( 'shown_access_token', $access_token );
				}

				if ( $business_id ) {
					update_option( 'shown_business_id', $business_id );
				}

				// We have an access token and a business id. Let's redirect to the next step.
				if ( $access_token && $business_id ) {
					// Install the tracking code for the selected business.
					$api         = new ShownApi();
					$snippet_url = $api->get_shown_snippet();
					if ( $snippet_url ) {
						update_option( 'shown_snippet_url', esc_url_raw( $snippet_url ) );
					}
				}

				wp_safe_redirect( SHOWN_CONNECTOR_ADMIN_URL );
				exit;
			}
		}
	}

	/**
	 * Disconnect from Shown.
	 *
	 * @return void
	 */
	private function disconnect() {

		if ( isset( $_POST['_disconnect_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_disconnect_nonce'] ) ), 'shown_connector_disconnect' ) ) {

			delete_option( 'shown_access_token' );
			delete_option( 'shown_business_id' );
			delete_option( 'shown_snippet_url' );

			delete_option( 'shown_has_imported_all_woocommerce_customers' );
			delete_option( 'shown_sync_customers_on_create' );
			delete_option( 'shown_sync_all_customers' );
			delete_option( 'shown_customer_list_id' );

			delete_option( 'shown_has_imported_all_woocommerce_products' );
			delete_option( 'shown_sync_products_on_create' );
			delete_option( 'shown_sync_all_products' );
			delete_option( 'shown_product_list_id' );

			delete_option( 'shown_enable_website_ownership_verification' );
			delete_option( 'shown_website_ownership_verification_tags' );

			wp_safe_redirect( SHOWN_CONNECTOR_ADMIN_URL );
			exit;
		}
	}
}
