<?php
/**
 * Shown API class file.
 *
 * @package ShownConnector
 */

declare( strict_types=1 );

namespace Shown;

use WP_Error;

/**
 * Class ShownApi
 */
class ShownApi {

	/**
	 * Get the shown business snippet.
	 *
	 * @return string|null
	 */
	public function get_shown_snippet(): ?string {
		$access_token = get_option( 'shown_access_token' );
		$business_id  = get_option( 'shown_business_id' );

		if ( ! ( $business_id && $access_token ) ) {
			return null;
		}

		$response      = wp_safe_remote_get(
			SHOWN_CONNECTOR_API_ENDPOINT . '/business/snippet',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Business-Id'   => $business_id,
				),
				'body'    => array(
					'business_id' => $business_id,
				),
			)
		);
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 === $response_code && ! is_wp_error( $response ) ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body );

			return $data->url;
		}

		return null;
	}

	/**
	 * Get the website ownership verification tags.
	 *
	 * @return array|null
	 */
	public function get_website_ownership_verification_tags(): ?array {
		$access_token = get_option( 'shown_access_token' );
		$business_id  = get_option( 'shown_business_id' );

		if ( ! ( $business_id && $access_token ) ) {
			return null;
		}
		$response      = wp_safe_remote_get(
			SHOWN_CONNECTOR_API_ENDPOINT . '/business/website-verification-tags?website=' . get_site_url(),
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Business-Id'   => $business_id,
				),
			)
		);
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 === $response_code && ! is_wp_error( $response ) ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			return $data['tags_definitions'] ? $data['tags_definitions'] : null;
		}

		return null;
	}

	/**
	 * Make an API call to shown.io to get the businesses.
	 *
	 * @return mixed
	 * @throws \Exception If the API call fails.
	 */
	public function get_businesses() {
		$access_token = get_option( 'shown_access_token' );
		$business_id  = get_option( 'shown_business_id' );

		if ( ! ( $business_id && $access_token ) ) {
			return null;
		}

		$response = wp_remote_get(
			SHOWN_CONNECTOR_API_ENDPOINT . '/business/list',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Business-Id'   => $business_id,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', 'API request failed: Please retry by refreshing this page' );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			// Handle non-200 HTTP response.
			return new WP_Error( 'api_error', 'Error fetching businesses. HTTP code: ' . $response_code );
		}

		$body = wp_remote_retrieve_body( $response );
		try {
			$decoded_body = json_decode( $body, false, 512, JSON_THROW_ON_ERROR );
		} catch ( \JsonException $e ) {
			// Handle JSON decoding error.
			return new WP_Error( 'json_error', 'Error decoding JSON: ' . $e->getMessage() );
		}

		return $decoded_body;
	}

	/**
	 * Send products to shown.io
	 *
	 * @param array $products_data The product data.
	 *
	 * @return array|WP_Error
	 */
	public function send_products_to_shown( array $products_data ) {
		$access_token = get_option( 'shown_access_token' );
		$business_id  = get_option( 'shown_business_id' );

		if ( ! ( $business_id && $access_token ) ) {
			return null;
		}

		return wp_safe_remote_post(
			SHOWN_CONNECTOR_API_ENDPOINT . '/products/create-multiple',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Business-Id'   => $business_id,
				),
				'body'    => array(
					'list_name' => get_bloginfo( 'name' ) . ' Products - WooCommerce',
					'source'    => 'wordpress',
					'products'  => $products_data,
				),
			)
		);
	}

	/**
	 * Send customers to shown.io
	 *
	 * @param array $product_data The customer data.
	 *
	 * @return void
	 */
	public function send_single_product( array $product_data ): void {
		$access_token    = get_option( 'shown_access_token' );
		$business_id     = get_option( 'shown_business_id' );
		$product_list_id = get_option( 'shown_product_list_id' );

		if ( ! $business_id || ! $access_token || ! $product_list_id ) {
			return;
		}

		wp_safe_remote_post(
			SHOWN_CONNECTOR_API_ENDPOINT . '/products/' . $product_list_id,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Business-Id'   => $business_id,
				),
				'body'    => $product_data,
			)
		);
	}

	/**
	 * Send single customer data to shown on creation
	 *
	 * @param array $customer_data The customer data.
	 *
	 * @return void
	 */
	public function create_single_customer_on_shown( array $customer_data ): void {
		$access_token     = get_option( 'shown_access_token' );
		$business_id      = get_option( 'shown_business_id' );
		$customer_list_id = get_option( 'shown_customer_list_id', false );

		if ( ! $customer_list_id || ! $business_id || ! $access_token ) {
			return;
		}
		wp_safe_remote_post(
			SHOWN_CONNECTOR_API_ENDPOINT . '/business/customers/add-or-update/' . get_option( 'shown_customer_list_id' ),
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Business-Id'   => $business_id,
				),
				'body'    => $customer_data,
			)
		);
	}

	/**
	 * Send customers to shown.io
	 *
	 * @param array $customers_data The customer data.
	 *
	 * @return array|WP_Error|null
	 */
	public function send_customers( array $customers_data ) {
		$access_token = get_option( 'shown_access_token' );
		$business_id  = get_option( 'shown_business_id' );
		if ( ! $business_id || ! $access_token ) {
			return null;
		}

		return wp_safe_remote_post(
			SHOWN_CONNECTOR_API_ENDPOINT . '/business/customers',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Business-Id'   => $business_id,
				),
				'body'    => array(
					'list_name' => esc_html( get_bloginfo( 'name' ) ) . ' Customers - WooCommerce',
					'list_id'   => get_option( 'shown_customer_list_id', null ),
					'customers' => $customers_data,
					'source'    => 'wordpress',
				),
			)
		);
	}
}
