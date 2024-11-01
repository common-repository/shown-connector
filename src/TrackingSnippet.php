<?php
/**
 * Tracking snippet tab.
 *
 * @package ShownConnector
 */

declare( strict_types=1 );

namespace Shown;

/**
 * Tracking snippet tab.
 */
class TrackingSnippet {

	/**
	 * Register tracking snippet tab hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
	}

	/**
	 * Make an API call to install the tracking code.
	 *
	 * @return void
	 */
	public function handle_form_submission() {
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		if ( ! isset( $_POST['_tracking_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_tracking_nonce'] ) ), 'shown_connector_tracking' ) ) {
			return;
		}

		// If the user wants to remove the tracking code, delete the tracking code from the WordPress database.
		if ( isset( $_POST['remove_tracking'] ) ) {
			delete_option( 'shown_snippet_url' );
			return;
		}

		// If the user wants to install the tracking code, make an API call to shown.io to get the tracking code.
		if ( isset( $_POST['shown_business'] ) ) {
			$business_id  = sanitize_text_field( wp_unslash( $_POST['shown_business'] ) );
			$access_token = get_option( 'shown_access_token', '' );

			$api         = new ShownApi( $business_id, $access_token );
			$snippet_url = $api->get_shown_snippet();

			if ( $snippet_url ) {
				// Store the snippet URL in the WordPress database.
				update_option( 'shown_business_id', $business_id );
				update_option( 'shown_snippet_url', sanitize_text_field( $snippet_url ) );
			}
		}
	}
}
