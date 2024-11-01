<?php
/**
 *  Uninstall script.
 *
 * @package ShownConnector
 */

if ( ! \defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

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
