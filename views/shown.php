<?php
/**
 * This file is responsible for rendering the Shown Connector admin page.
 *
 * @package ShownConnector
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap" id="shown-connector">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<div class="shown-connector-settings">
		<?php if ( ! isset( $is_connected ) || ! $is_connected ) : ?>
			<div class="settings-section">
				<h2>Connect your Shown account</h2>
				<p>Connect your Shown account to your WordPress site to get started. If you don't have a Shown account, you can create one for free. Once you've connected your account, you can add the Shown tracking snippet to your site.</p>
				<a id="connect-button" href="<?php echo isset( $connect_link ) ? esc_url( $connect_link ) : ''; ?>" class="button button-primary">Connect</a>
			</div>
		<?php else : ?>
			<!-- Tracking snippet section.  -->
			<div class="settings-section" id="shown-tab-tracking">
				<?php if ( isset( $error_message ) && $error_message ) : ?>
					<div class="notice notice-error">
						<p><?php echo esc_html( $error_message ); ?></p>
					</div>
				<?php endif; ?>

				<form method="POST" action="<?php echo isset( $form_action ) ? esc_url( $form_action ) : ''; ?>">
					<?php wp_nonce_field( 'shown_connector_tracking', '_tracking_nonce' ); ?>
					<h2>Shown pixel setting:</h2>
					<?php if ( isset( $saved_snippet_url ) && $saved_snippet_url ) : ?>
						<div class="notice-success notice-alt">
							<p>The Shown pixel has been installed on your site. This will enable Shown to track leads and sales, create high-quality audiences, and optimize your ads more efficiently.</p>
						</div>
					<?php endif; ?>
					<?php if ( $saved_business_id && $saved_snippet_url ) : ?>
						<p class="submit">
							<button type="submit" name="remove_tracking" class="button button-secondary"><?php esc_html_e( 'Remove Shown pixel', 'shown-connector' ); ?></button>
						</p>
					<?php else : ?>
						<fieldset>
							<label for="selected_business"><?php esc_html_e( 'Select the business you want to install the tracking code for', 'shown-connector' ); ?></label>
							<select id="selected_business" name="shown_business" class="regular-text">
								<?php foreach ( $businesses as $business ) : ?>
									<option value="<?php echo esc_attr( $business->id ); ?>" <?php selected( $business->id, $saved_business_id ); ?>>
										<?php echo esc_html( $business->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</fieldset>
						<p class="submit">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Install tracking code', 'shown-connector' ); ?></button>
						</p>
					<?php endif; ?>
				</form>
			</div>
			<!-- /Tracking snippet section.  -->

			<!-- Customers Synchronization section.  -->
			<div class="settings-section">
				<form method="POST" class="shown-progress-settings">
					<?php wp_nonce_field( 'shown_connector_setting_customers', '_setting_nonce' ); ?>
					<h3>Customers sync settings:</h3>
					<p>You can synchronize your WooCommerce customers with Shown. This will enable you to target similar audiences or retarget existing customers, leading to better ad performance.</p>
					<fieldset>
						<label for="sync_all_customers">
							<input name="sync_all_customers" type="checkbox" id="sync_all_customers" <?php checked( $sync_all_customers ); ?>>
							Synchronize all existing WooCommerce customers with Shown.
						</label>
						<label for="sync_customers_on_create">
							<input name="sync_customers_on_create" type="checkbox" id="sync_customers_on_create" <?php checked( $sync_customers_on_create ); ?>>
							Automatically synchronize new WooCommerce customers with Shown as they are created.
						</label>
					</fieldset>
					<button type="submit" class="button">Save customers sync settings</button>
				</form>
			</div>
			<!-- /Customers Synchronization section.  -->

			<!-- Products synchronization settings section.  -->
			<div class="settings-section">
				<form method="POST" class="shown-progress-settings">
					<?php wp_nonce_field( 'shown_connector_setting_products', '_setting_nonce' ); ?>
					<h3>Products sync settings:</h3>
					<p>You can synchronize your WooCommerce products with Shown. This will enable you to launch shopping ads across various channels, including Google & Meta.</p>
					<fieldset>
						<label for="sync_all_products">
							<input name="sync_all_products" type="checkbox" id="sync_all_products" <?php checked( $sync_all_products ); ?>>
							Synchronize all existing WooCommerce products with Shown.
						</label>
						<label for="sync_products_on_create">
							<input name="sync_products_on_create" type="checkbox" id="sync_products_on_create" <?php checked( $sync_products_on_create ); ?>>
							Automatically synchronize new WooCommerce products with Shown as they are created.
						</label>
					</fieldset>
					<button type="submit" class="button">Save products sync settings</button>
				</form>
			</div>
			<!-- / Products synchronization settings section.  -->

			<!-- Website ownership verification section.  -->
			<div class="settings-section">
				<form method="POST">
					<?php wp_nonce_field( 'shown_connector_setting_site_verification', '_setting_nonce' ); ?>
					<h3>Website ownership verification:</h3>
					<p>
						To run Google Shopping ads, you need to verify your website with Google. Shown can assist with this process. By enabling this option, Shown will automatically add the required meta-tag to your website.
					</p>
					<fieldset>
						<label for="site_ownership_verification_tags">
							<input name="site_ownership_verification_tags"
									type="checkbox"
									id="site_ownership_verification_tags"
								<?php checked( $enable_website_ownership_verification ); ?>>
							Enable website ownership verification
						</label>
						<br>
					</fieldset>
					<button type="submit" class="button">Save</button>
				</form>
			</div>
			<!-- /Website ownership verification section.  -->

			<!-- Disconnect your Shown account section.  -->
			<div class="settings-section">
				<h3>Disconnect your Shown account</h3>
				<p>Once you've disconnected your account, the Shown pixel will be removed from your site.</p>
				<form method="post">
					<?php wp_nonce_field( 'shown_connector_disconnect', '_disconnect_nonce' ); ?>
					<button type="submit" class="button button-shown-disconnect">Disconnect Shown</button>
				</form>
			</div>
			<!-- /Disconnect your Shown account section.  -->
		<?php endif; ?>
	</div>
</div>
