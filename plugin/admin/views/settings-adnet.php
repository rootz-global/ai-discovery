<?php
/**
 * Adnet settings tab view.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rootz_adnet_enabled = get_option( 'rootz_adnet_enabled', '0' );
$rootz_adnet_wallet  = get_option( 'rootz_adnet_publisher_wallet', '' );
$rootz_adnet_api_url = get_option( 'rootz_adnet_api_url', 'https://adnet.geistm.com' );
$rootz_adnet_consent = get_option( 'rootz_adnet_consent_notice', '1' );
?>
<div class="rootz-tab-content">

	<?php settings_errors(); ?>

	<h2><?php esc_html_e( 'Adnet — Verified Advertising', 'rootz-ai-discovery' ); ?></h2>
	<p>
		<?php esc_html_e( 'Earn publisher revenue by serving verified advertisements from the Adnet network. Ad budgets are held in smart contracts — payments go directly to your wallet with no intermediary.', 'rootz-ai-discovery' ); ?>
		<a href="https://adnet.geistm.com" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn more at adnet.geistm.com', 'rootz-ai-discovery' ); ?></a>
	</p>

	<div class="notice notice-info inline" style="margin: 0 0 20px; padding: 10px 12px;">
		<p>
			<strong><?php esc_html_e( 'External Service:', 'rootz-ai-discovery' ); ?></strong>
			<?php
			printf(
				/* translators: %s: link to adnet.geistm.com */
				esc_html__( 'When enabled, this feature connects to %s to deliver ads and record verified engagement events. No personally identifiable information is transmitted. See the plugin readme for full disclosure.', 'rootz-ai-discovery' ),
				'<a href="https://adnet.geistm.com" target="_blank" rel="noopener noreferrer">adnet.geistm.com</a>'
			);
			?>
		</p>
	</div>

	<form method="post" action="options.php">
		<?php settings_fields( 'rootz_adnet' ); ?>

		<table class="form-table" role="presentation">

			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Adnet', 'rootz-ai-discovery' ); ?></th>
				<td>
					<label>
						<input type="checkbox"
							name="rootz_adnet_enabled"
							value="1"
							<?php checked( $rootz_adnet_enabled, '1' ); ?>
						/>
						<?php esc_html_e( 'Serve verified advertisements on this site', 'rootz-ai-discovery' ); ?>
					</label>
					<?php
					Rootz_Admin::help_tip(
						__( 'Ads only display on pages where you insert the "Adnet Ad Slot" block via the Gutenberg editor. Enabling this setting alone does not place any ads.', 'rootz-ai-discovery' ),
						'adnet-enabled'
					);
					?>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="rootz_adnet_publisher_wallet">
						<?php esc_html_e( 'Publisher Wallet Address', 'rootz-ai-discovery' ); ?>
					</label>
				</th>
				<td>
					<input type="text"
						id="rootz_adnet_publisher_wallet"
						name="rootz_adnet_publisher_wallet"
						value="<?php echo esc_attr( $rootz_adnet_wallet ); ?>"
						class="regular-text"
						placeholder="0x..."
					/>
					<p class="description">
						<?php esc_html_e( 'Your Polygon wallet address. Ad earnings are paid directly here by campaign smart contracts. Must be a valid Ethereum/Polygon address (0x followed by 40 hex characters).', 'rootz-ai-discovery' ); ?>
					</p>
					<?php
					Rootz_Admin::help_tip(
						__( 'This address is your public publisher identity on the Adnet network. It is submitted to adnet.geistm.com to associate your site with your publisher account. Never enter a private key here.', 'rootz-ai-discovery' ),
						'adnet-wallet'
					);
					?>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Visitor Notice', 'rootz-ai-discovery' ); ?></th>
				<td>
					<label>
						<input type="checkbox"
							name="rootz_adnet_consent_notice"
							value="1"
							<?php checked( $rootz_adnet_consent, '1' ); ?>
						/>
						<?php esc_html_e( 'Inform visitors that this site uses privacy-preserving ad verification', 'rootz-ai-discovery' ); ?>
					</label>
					<?php
					Rootz_Admin::help_tip(
						__( 'Adnet generates a browser+domain key pair stored locally in the visitor\'s browser (IndexedDB). This key is unique to this site — it cannot track visitors across other sites. The notice informs visitors of this.', 'rootz-ai-discovery' ),
						'adnet-consent'
					);
					?>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="rootz_adnet_api_url">
						<?php esc_html_e( 'Adnet API URL', 'rootz-ai-discovery' ); ?>
					</label>
				</th>
				<td>
					<input type="url"
						id="rootz_adnet_api_url"
						name="rootz_adnet_api_url"
						value="<?php echo esc_attr( $rootz_adnet_api_url ); ?>"
						class="regular-text"
					/>
					<p class="description">
						<?php esc_html_e( 'Default: https://adnet.geistm.com — change only for local testing.', 'rootz-ai-discovery' ); ?>
					</p>
				</td>
			</tr>

		</table>

		<?php submit_button(); ?>
	</form>

	<?php if ( '1' === $rootz_adnet_enabled && ! empty( $rootz_adnet_wallet ) ) : ?>
	<hr />
	<h3><?php esc_html_e( 'Getting Started', 'rootz-ai-discovery' ); ?></h3>
	<p><?php esc_html_e( 'Adnet is active. To place an ad unit, edit any page or post, open the block inserter, and search for "Adnet Ad Slot". Enter the campaign ID provided by GeistM.', 'rootz-ai-discovery' ); ?></p>
	<p>
		<a href="<?php echo esc_url( $rootz_adnet_api_url . '/publisher/dashboard' ); ?>"
			target="_blank"
			rel="noopener noreferrer"
			class="button button-secondary">
			<?php esc_html_e( 'Publisher Dashboard →', 'rootz-ai-discovery' ); ?>
		</a>
	</p>
	<?php elseif ( '1' === $rootz_adnet_enabled && empty( $rootz_adnet_wallet ) ) : ?>
	<div class="notice notice-warning inline" style="margin-top: 16px;">
		<p>
			<strong><?php esc_html_e( 'Action needed:', 'rootz-ai-discovery' ); ?></strong>
			<?php esc_html_e( 'Adnet is enabled but no publisher wallet is configured. Enter your wallet address above to start receiving ad revenue.', 'rootz-ai-discovery' ); ?>
		</p>
	</div>
	<?php endif; ?>

</div>
