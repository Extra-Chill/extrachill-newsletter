<?php
/**
 * Newsletter Settings Page
 *
 * Admin UI for Sendy API configuration and integration management.
 * Dynamically discovers registered integrations via filter.
 *
 * @package ExtraChillNewsletter
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'ec_newsletter_add_settings_menu' );
function ec_newsletter_add_settings_menu() {
	add_submenu_page(
		'edit.php?post_type=newsletter',
		__( 'Newsletter Settings', 'extrachill-newsletter' ),
		__( 'Settings', 'extrachill-newsletter' ),
		'manage_options',
		'newsletter-settings',
		'ec_newsletter_render_settings_page'
	);
}

add_action( 'admin_post_ec_newsletter_settings', 'ec_newsletter_handle_settings_save' );
function ec_newsletter_handle_settings_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'extrachill-newsletter' ) );
	}

	check_admin_referer( 'ec_newsletter_settings', 'ec_newsletter_nonce' );

	$settings = array();

	// Sendy API connection: saved to Data Machine Business (the single owner of
	// Sendy credentials). A blank key keeps the stored one.
	$api_key        = isset( $_POST['sendy_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['sendy_api_key'] ) ) : '';
	$sendy_url      = isset( $_POST['sendy_url'] ) ? esc_url_raw( wp_unslash( $_POST['sendy_url'] ) ) : '';
	$connection_err = '';
	$saved          = extrachill_newsletter_save_sendy_connection( $api_key, $sendy_url );
	if ( is_wp_error( $saved ) ) {
		$connection_err = $saved->get_error_message();
	}

	// Email Configuration
	$settings['from_name']  = isset( $_POST['from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['from_name'] ) ) : 'Extra Chill';
	$settings['from_email'] = isset( $_POST['from_email'] ) ? sanitize_email( wp_unslash( $_POST['from_email'] ) ) : 'newsletter@extrachill.com';
	$settings['reply_to']   = isset( $_POST['reply_to'] ) ? sanitize_email( wp_unslash( $_POST['reply_to'] ) ) : 'chubes@extrachill.com';
	$settings['brand_id']   = isset( $_POST['brand_id'] ) ? sanitize_text_field( wp_unslash( $_POST['brand_id'] ) ) : '1';

	// Integration Sendy list IDs
	$integrations = get_newsletter_integrations();
	foreach ( $integrations as $context => $integration ) {
		$settings[ $integration['list_id_key'] ] = isset( $_POST[ $integration['list_id_key'] ] ) ? sanitize_text_field( wp_unslash( $_POST[ $integration['list_id_key'] ] ) ) : '';
	}

	update_site_option( 'extrachill_newsletter_settings', $settings );

	// Redirect back with success message
	$query = array(
		'post_type' => 'newsletter',
		'page'      => 'newsletter-settings',
		'updated'   => 'true',
	);
	if ( '' !== $connection_err ) {
		$query['sendy_error'] = rawurlencode( $connection_err );
	}
	$redirect_url = add_query_arg( $query, admin_url( 'edit.php' ) );

	wp_safe_redirect( $redirect_url );
	exit;
}

function ec_newsletter_render_settings_page() {
	$settings     = get_newsletter_settings();
	$integrations = get_newsletter_integrations();
	$updated      = filter_input( INPUT_GET, 'updated', FILTER_VALIDATE_BOOLEAN );
	$sendy_error  = isset( $_GET['sendy_error'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['sendy_error'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
	$connection   = extrachill_newsletter_get_sendy_connection();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Newsletter Settings', 'extrachill-newsletter' ); ?></h1>

		<?php if ( $updated ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Newsletter settings updated successfully.', 'extrachill-newsletter' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $sendy_error ) : ?>
			<div class="notice notice-error">
				<p><?php echo esc_html( sprintf( /* translators: %s: error message. */ __( 'Sendy connection was not saved: %s', 'extrachill-newsletter' ), $sendy_error ) ); ?></p>
			</div>
		<?php elseif ( ! $connection['api_key_set'] || '' === $connection['sendy_url'] ) : ?>
			<div class="notice notice-warning">
				<p><?php esc_html_e( 'The Sendy API connection is not configured. Campaign push and subscriptions will fail until a key and URL are saved.', 'extrachill-newsletter' ); ?></p>
			</div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ec_newsletter_settings" />
			<?php wp_nonce_field( 'ec_newsletter_settings', 'ec_newsletter_nonce' ); ?>

			<!-- API Configuration -->
			<h2><?php esc_html_e( 'API Configuration', 'extrachill-newsletter' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Stored in the Data Machine Business Sendy config, shared by every Sendy feature on the network.', 'extrachill-newsletter' ); ?></p>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="sendy_api_key"><?php esc_html_e( 'Sendy API Key', 'extrachill-newsletter' ); ?></label>
						</th>
						<td>
							<input type="password" id="sendy_api_key" name="sendy_api_key" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( $connection['api_key_set'] ? __( 'Saved. Leave blank to keep.', 'extrachill-newsletter' ) : '' ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Your Sendy API key from Sendy settings. Leave blank to keep the saved key.', 'extrachill-newsletter' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="sendy_url"><?php esc_html_e( 'Sendy URL', 'extrachill-newsletter' ); ?></label>
						</th>
						<td>
							<input type="url" id="sendy_url" name="sendy_url" value="<?php echo esc_attr( $connection['sendy_url'] ); ?>" placeholder="<?php echo esc_attr( EXTRACHILL_NEWSLETTER_SENDY_URL_DEFAULT ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Your Sendy installation URL (without trailing slash).', 'extrachill-newsletter' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<!-- Email Configuration -->
			<h2><?php esc_html_e( 'Email Configuration', 'extrachill-newsletter' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Configure email sender information and branding.', 'extrachill-newsletter' ); ?></p>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="from_name"><?php esc_html_e( 'From Name', 'extrachill-newsletter' ); ?></label>
						</th>
						<td>
							<input type="text" id="from_name" name="from_name" value="<?php echo esc_attr( $settings['from_name'] ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Name that appears in the "From" field of newsletters.', 'extrachill-newsletter' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="from_email"><?php esc_html_e( 'From Email', 'extrachill-newsletter' ); ?></label>
						</th>
						<td>
							<input type="email" id="from_email" name="from_email" value="<?php echo esc_attr( $settings['from_email'] ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Email address that appears in the "From" field of newsletters.', 'extrachill-newsletter' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="reply_to"><?php esc_html_e( 'Reply To', 'extrachill-newsletter' ); ?></label>
						</th>
						<td>
							<input type="email" id="reply_to" name="reply_to" value="<?php echo esc_attr( $settings['reply_to'] ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Email address for replies to newsletters.', 'extrachill-newsletter' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="brand_id"><?php esc_html_e( 'Brand ID', 'extrachill-newsletter' ); ?></label>
						</th>
						<td>
							<input type="text" id="brand_id" name="brand_id" value="<?php echo esc_attr( $settings['brand_id'] ); ?>" class="small-text" />
							<p class="description"><?php esc_html_e( 'Sendy brand ID for newsletter campaigns.', 'extrachill-newsletter' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<!-- Integration Configuration -->
			<h2><?php esc_html_e( 'Form Integrations', 'extrachill-newsletter' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Configure Sendy list IDs for each subscription form context.', 'extrachill-newsletter' ); ?></p>

			<table class="form-table">
				<tbody>
					<?php foreach ( $integrations as $context => $integration ) : ?>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $integration['list_id_key'] ); ?>"><?php echo esc_html( $integration['label'] ); ?></label>
						</th>
						<td>
							<input
								type="text"
								id="<?php echo esc_attr( $integration['list_id_key'] ); ?>"
								name="<?php echo esc_attr( $integration['list_id_key'] ); ?>"
								value="<?php echo esc_attr( $settings[ $integration['list_id_key'] ] ?? '' ); ?>"
								class="regular-text"
							/>
							<p class="description"><?php echo esc_html( $integration['description'] ); ?></p>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php submit_button( __( 'Save Newsletter Settings', 'extrachill-newsletter' ) ); ?>
		</form>
	</div>
	<?php
}

function get_newsletter_settings() {
	$ability = wp_get_ability( 'extrachill/get-newsletter-settings' );
	if ( $ability ) {
		$result = $ability->execute( array() );
		if ( ! is_wp_error( $result ) ) {
			// Flatten ability output back to legacy flat format for admin UI.
			$settings = $result['settings'];
			foreach ( $result['integrations'] as $context => $integration ) {
				$integrations = get_newsletter_integrations();
				if ( isset( $integrations[ $context ]['list_id_key'] ) ) {
					$settings[ $integrations[ $context ]['list_id_key'] ] = $integration['list_id'];
				}
			}
			return $settings;
		}
	}

	// Legacy fallback below.
	$defaults = extrachill_newsletter_default_settings();

	// Add defaults for registered integrations (list IDs only)
	$integrations = get_newsletter_integrations();
	foreach ( $integrations as $context => $integration ) {
		$defaults[ $integration['list_id_key'] ] = '';
	}

	$settings = get_site_option( 'extrachill_newsletter_settings', array() );
	return wp_parse_args( $settings, $defaults );
}
