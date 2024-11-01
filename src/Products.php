<?php
/**
 * Handle products' export to shown.io
 *
 * @package ShownConnector
 */

declare( strict_types=1 );

namespace Shown;

use WC_Product;
use WP_Query;

/**
 *  Handle products' export to shown.io
 */
class Products {

	/**
	 * Register products hooks.
	 */
	public function register() {
		add_action( 'transition_post_status', array( $this, 'create_product_on_shown' ), 10, 3 );
		if ( get_option( 'shown_sync_products_on_create' ) && ! get_option( 'shown_has_imported_all_woocommerce_products' ) ) {
			add_action( 'admin_init', array( $this, 'synchronize_all_woocommerce_products' ) );
		}
	}

	/**
	 *  Export single product to Shown when a new product is created.
	 *
	 * @param string $new_status The new product status.
	 * @param string $old_status The old product status.
	 * @param mixed  $post The product object.
	 *
	 * @return void
	 */
	public function create_product_on_shown( string $new_status, string $old_status, $post ) {
		if ( ! $this->should_process_product( $new_status, $old_status, $post ) ) {
			return;
		}

		$product      = wc_get_product( $post->ID );
		$product_data = $this->get_product_data( $product );
		$api          = new ShownApi();
		$api->send_single_product( $product_data );
	}

	/**
	 * Check if the product should be processed.
	 *
	 * @param string $new_status The new product status.
	 * @param string $old_status The old product status.
	 * @param mixed  $post The product object.
	 *
	 * @return bool
	 */
	private function should_process_product( string $new_status, string $old_status, $post ): bool {
		return Utils::is_woocommerce_active()
				&& 'product' === $post->post_type
				&& 'publish' === $new_status
				&& 'publish' !== $old_status;
	}

	/**
	 * Get product data.
	 *
	 * @param WC_Product $product The product object.
	 *
	 * @return array The product data.
	 */
	private function get_product_data( WC_Product $product ): array {
		$price  = $this->get_product_price( $product );
		$image  = $this->get_product_image( $product );
		$brands = $this->get_product_brands( $product );

		return array(
			'list_name'    => get_bloginfo( 'name' ) . ' Products - WooCommerce',
			'source'       => 'wordpress',
			'title'        => $product->get_name(),
			'description'  => $product->get_description(),
			'price'        => $price,
			'currency'     => get_woocommerce_currency(),
			'in_stock'     => $product->is_in_stock(),
			'image'        => $image,
			'product_page' => $product->get_permalink(),
			'brand'        => $brands,
			'retailer_id'  => $product->get_sku(),
			'category'     => $this->get_product_category( $product ),
		);
	}

	/**
	 * Extract Sale price or Regular price from the product.
	 *
	 * @param WC_Product $product The product object.
	 *
	 * @return float
	 */
	private function get_product_price( WC_Product $product ): float {
		$sale_price    = $product->get_sale_price();
		$regular_price = $product->get_regular_price();

		// If either sale price or regular price is empty, return 0.0 as a default value.
		if ( empty( $sale_price ) && empty( $regular_price ) ) {
			return 0.0;
		}

		// If the sale price is available, return it as a float.
		if ( ! empty( $sale_price ) ) {
			return floatval( $sale_price );
		}

		// Otherwise, return the regular price as a float.
		return floatval( $regular_price );
	}


	/**
	 * Get full product image URL.
	 *
	 * @param WC_Product $product The product object.
	 *
	 * @return string|null
	 */
	private function get_product_image( WC_Product $product ) {
		$image_id = $product->get_image_id();

		return $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : null;
	}

	/**
	 * Get product brands.
	 *
	 * @param WC_Product $product The product object.
	 *
	 * @return string
	 */
	private function get_product_brands( WC_Product $product ): string {
		$brands = wp_get_post_terms(
			$product->get_id(),
			'brand',
			array(
				'orderby' => 'name',
				'fields'  => 'names',
			)
		) ?? '';

		return is_array( $brands ) ? wp_json_encode( $brands ) : '';
	}

	/**
	 * Get product category.
	 *
	 * @param WC_Product $product The product object.
	 *
	 * @return string
	 */
	private function get_product_category( WC_Product $product ): string {
		return wp_strip_all_tags( wc_get_product_category_list( $product->get_id() ) );
	}

	/**
	 * Export all products to Shown when the user chooses to "Import all WooCommerce products" in the settings tab.
	 *
	 * @return void
	 */
	public function synchronize_all_woocommerce_products() {
		if ( ! Utils::is_woocommerce_active() ) {
			return;
		}

		$products_data = $this->get_products_data();

		if ( ! empty( $products_data ) ) {
			$api      = new ShownApi();
			$response = $api->send_products_to_shown( $products_data );

			if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {
				$this->handle_successful_import( $response );
			}
		}
	}

	/**
	 * Get the product data.
	 *
	 * @return array<mixed> The product data.
	 */
	private function get_products_data(): array {
		$args = array(
			'status'         => 'publish',
			'post_type'      => 'product',
			'posts_per_page' => - 1,
		);

		$query = new WP_Query( $args );

		$products_data = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$product = wc_get_product( get_the_ID() );

				if ( $product && $product->get_sku() ) {
					$products_data[] = $this->get_product_data( $product );
				}
			}

			wp_reset_postdata();
		}

		return $products_data;
	}

	/**
	 * Handle successful import of all products.
	 *
	 * @param array $response The response from Shown.
	 *
	 * @return void
	 */
	private function handle_successful_import( $response ) {
		$decoded_response = json_decode( $response['body'] );
		if ( null !== $decoded_response && isset( $decoded_response->data->list_id ) ) {
			$list_id = sanitize_text_field( $decoded_response->data->list_id );

			// Mark all products as sent to Shown.
			update_option( 'shown_has_imported_all_woocommerce_products', true );

			if ( false === get_option( 'shown_product_list_id' ) ) {
				add_option( 'shown_product_list_id', $list_id );
			} else {
				// Option already exists, so we update it.
				update_option( 'shown_product_list_id', $list_id );
			}
		}
	}
}
