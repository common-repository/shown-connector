<?php
/**
 * Analytics class.
 *
 * @package ShownConnector
 */

declare( strict_types=1 );

namespace Shown;

/**
 * Class Analytics: Handle conversions tracking
 */
class Analytics {

	/**
	 * Constructor.
	 */
	public function register() {
		add_action( 'woocommerce_add_to_cart', array( $this, 'trigger_custom_add_to_cart_js_event' ), 10, 6 );
		add_action( 'woocommerce_thankyou', array( $this, 'trigger_purchase_completed_js_event' ) );
	}

	/**
	 * Triggers a javascript custom event to track adding a product to the cart.
	 *
	 * @param string $cart_item_key The cart item key.
	 * @param int    $product_id The product ID.
	 * @param int    $quantity The quantity.
	 * @param int    $variation_id The variation ID.
	 * @param array  $variation The variation.
	 * @param array  $cart_item_data The cart item data.
	 *
	 * @return void
	 */
	public function trigger_custom_add_to_cart_js_event(
		string $cart_item_key,
		int $product_id,
		int $quantity,
		int $variation_id,
		array $variation,
		array $cart_item_data
	) {
		$cart_data = array(
			'cart_item_key'  => $cart_item_key,
			'product_id'     => $product_id,
			'quantity'       => $quantity,
			'variation_id'   => $variation_id,
			'variation'      => $variation,
			'cart_item_data' => $cart_item_data,
		);

		add_action(
			'wp_footer',
			function () use ( $cart_data ) {
				?>
			<script type="text/javascript">
				jQuery(document).ready(function ($) {
					$(document.body).trigger('shown_add_to_cart', <?php echo wp_json_encode( $cart_data ); ?>);
				});
			</script>
				<?php
			}
		);
	}

	/**
	 * Triggers a javascript custom event to track purchase completed.
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return void
	 */
	public function trigger_purchase_completed_js_event( $order_id ) {
		add_action(
			'wp_footer',
			function () use ( $order_id ) {
				?>
			<script type="text/javascript">
				jQuery(document).ready(function ($) {
					$(document.body).trigger('shown_purchase_completed', <?php echo absint( $order_id ); ?>);
				});
			</script>
				<?php
			}
		);
	}
}
