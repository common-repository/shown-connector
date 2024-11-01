<?php
/**
 * This file is responsible for creating customers on Shown when a new order is created.
 *
 * @package ShownConnector
 */

declare( strict_types = 1 );

namespace Shown;

/**
 * This service is responsible for creating customers on Shown when a new order is created.
 * Or when the user chooses to "Import all WooCommerce customers" in the settings tab.
 */
class Customers {
	/**
	 *  Register customers hooks.
	 */
	public function register() {
		// Hook into wp_loaded to ensure everything is fully loaded.
		add_action( 'wp_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize the customer's service.
	 *
	 * @return void
	 */
	public function init() {
		if ( get_option( 'shown_sync_customers_on_create' ) ) {
			add_action( 'woocommerce_new_order', array( $this, 'create_customer_on_shown' ) );
		}

		if ( get_option( 'shown_sync_all_customers' ) && ! get_option( 'shown_has_imported_all_woocommerce_customers' ) ) {
			add_action( 'admin_init', array( $this, 'import_all_woocommerce_customers' ) );
		}
	}
	/**
	 * Export single customer to Shown when a new order is created.
	 *
	 * @param int|string $order_id The order ID.
	 * @return void
	 */
	public function create_customer_on_shown( $order_id ) {
		if ( ! Utils::is_woocommerce_active() ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( $order ) {
			$data = array(
				'list_name'  => get_bloginfo( 'name' ) . ' Customers - WooCommerce',
				'first_name' => $order->get_billing_first_name(),
				'last_name'  => $order->get_billing_last_name(),
				'email'      => $order->get_billing_email(),
			);

			$shown_api = new ShownApi();
			$shown_api->create_single_customer_on_shown( $data );
		}
	}

	/**
	 * Export all customers to Shown when the user chooses to "Import all WooCommerce customers" in the settings tab.
	 */
	public function import_all_woocommerce_customers() {
		if ( ! Utils::is_woocommerce_active() ) {
			return;
		}

		$customers_data = $this->read_customers();

		if ( ! empty( $customers_data ) ) {
			$shown_api = new ShownApi();
			$response  = $shown_api->send_customers( $customers_data );
			if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {
				update_option( 'shown_has_imported_all_woocommerce_customers', true );
				add_option( 'shown_customer_list_id', json_decode( $response['body'] )->data->list_id );
			}
		}
	}

	/**
	 * Read all customers from the database.
	 *
	 * @return array<array{first_name:string, last_name:string, email:string }>  $customers  The customers data.
	 */
	public function read_customers(): array {
		// Check if we have a cached version of the customers.
		$cached_customers = get_transient( 'shown_connector_customers_cache' );
		if ( false !== $cached_customers ) {
			return $cached_customers;
		}

		$customers   = array();
		$seen_emails = array();
		$page        = 1;
		$per_page    = 1000;

		do {
			$args = array(
				'limit'    => $per_page,
				'page'     => $page,
				'orderby'  => 'date',
				'order'    => 'DESC',
				'return'   => 'objects',
				'paginate' => true,
			);

			$results   = \wc_get_orders( $args );
			$orders    = $results->orders;
			$max_pages = $results->max_num_pages;

			foreach ( $orders as $order ) {
				if ( ! $order instanceof \WC_Order ) {
					continue;
				}

				$email = $order->get_billing_email();

				if ( empty( $email ) || in_array( $email, $seen_emails, true ) ) {
					continue;
				}
				$seen_emails[] = $email;

				$first_name = $order->get_billing_first_name();
				$last_name  = $order->get_billing_last_name();

				$customers[] = array(
					'first_name' => $first_name,
					'last_name'  => $last_name,
					'email'      => $order->get_billing_email(),
				);
			}

			++$page;

		} while ( $page <= $max_pages );

		if (!empty( $customers ) ) {
			// Cache the result for 1 hour.
			set_transient( 'shown_connector_customers_cache', $customers, 60*60 );
		}

		return $customers;
	}
}
