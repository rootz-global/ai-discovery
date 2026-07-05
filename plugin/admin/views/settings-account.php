<?php
/**
 * Account & Signing settings tab — signing key, licensing, wallet identity.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rootz_has_gmp         = Rootz_Signer::has_gmp();
$rootz_signing_address = Rootz_Signer::stored_address();
$rootz_has_stored_key  = Rootz_Signer::has_stored_key();
$rootz_can_sign        = $rootz_has_gmp && $rootz_has_stored_key;
$rootz_wallet_address  = get_option( 'rootz_plugin_wallet', '' );
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only flag from nonce-verified POST handler redirect.
$rootz_auto_populate = isset( $_GET['auto-populate'] ) && '1' === $_GET['auto-populate'];
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$rootz_key_generated     = isset( $_GET['key-generated'] ) && '1' === $_GET['key-generated'];
$rootz_ai_api_key        = get_option( 'rootz_ai_api_key', '' );
$rootz_has_license_class = class_exists( 'Rootz_License' );
$rootz_owner_identity    = $rootz_has_license_class ? Rootz_License::get_owner_identity() : get_option( 'rootz_owner_identity', '' );
$rootz_license_status    = $rootz_has_license_class ? Rootz_License::get_cached_status() : null;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$rootz_license_refreshed = isset( $_GET['license-refreshed'] ) && '1' === $_GET['license-refreshed'];
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$rootz_site_registered = isset( $_GET['site-registered'] ) && '1' === $_GET['site-registered'];
?>

<div class="rootz-viewer-intro">
	<h2><?php esc_html_e( 'Account & Signing', 'rootz-ai-discovery' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Your site\'s cryptographic identity, subscription, and AI-powered content tools.', 'rootz-ai-discovery' ); ?>
	</p>
</div>

<?php // ── SUBSCRIPTION & LICENSE ──────────────────────────────────────── ?>
<?php if ( $rootz_has_license_class ) : ?>
	<?php
	$rootz_current_tier = $rootz_license_status ? ( $rootz_license_status['tier'] ?? 'free' ) : 'free';
	$rootz_is_valid     = $rootz_license_status && ! empty( $rootz_license_status['valid'] );
	$rootz_active_sites = $rootz_license_status ? intval( $rootz_license_status['activeSites'] ?? 0 ) : 0;
	$rootz_max_sites    = $rootz_license_status ? intval( $rootz_license_status['maxSites'] ?? 1 ) : 1;
	$rootz_expires      = $rootz_license_status['expires'] ?? '';
	$rootz_has_license  = $rootz_is_valid && 'free' !== $rootz_current_tier;
	?>
<div class="rootz-viewer-card" style="border-left: 4px solid <?php echo esc_attr( $rootz_has_license ? '#00a32a' : '#dba617' ); ?>; background: linear-gradient(135deg, <?php echo esc_attr( $rootz_has_license ? '#f0faf0' : '#fff8e5' ); ?> 0%, #fff 100%);">
	<div class="rootz-viewer-card-header">
		<h3>
			<span class="rootz-viewer-icon">&#11088;</span>
			<?php esc_html_e( 'Subscription & License', 'rootz-ai-discovery' ); ?>
			<?php Rootz_Admin::help_tip( __( 'Your subscription is tied to an Owner Identity — an Ethereum address that represents your account across multiple WordPress sites. Subscribe to unlock premium features like unlimited AI generation, monitoring, and wallet-delegated signing.', 'rootz-ai-discovery' ), 'license' ); ?>
		</h3>
		<?php if ( $rootz_has_license ) : ?>
			<span class="rootz-status-connected">&#9679;
				<?php
				printf(
					/* translators: %s: subscription tier name (e.g., Standard, Pro) */
					esc_html__( '%s Plan Active', 'rootz-ai-discovery' ),
					esc_html( ucfirst( $rootz_current_tier ) )
				);
				?>
			</span>
		<?php else : ?>
			<span class="rootz-status-disconnected">&#9679; <?php esc_html_e( 'Free Tier', 'rootz-ai-discovery' ); ?></span>
		<?php endif; ?>
	</div>

	<?php if ( $rootz_license_refreshed ) : ?>
	<div class="rootz-viewer-hint" style="border-left-color: #2271b1; background: #f0f6fc;">
		<?php esc_html_e( 'License status refreshed.', 'rootz-ai-discovery' ); ?>
	</div>
	<?php endif; ?>

	<?php if ( $rootz_site_registered ) : ?>
	<div class="rootz-viewer-hint" style="border-left-color: #00a32a; background: #f0faf0;">
		<?php esc_html_e( 'This site has been registered under your license.', 'rootz-ai-discovery' ); ?>
	</div>
	<?php endif; ?>

	<?php if ( $rootz_has_license ) : ?>
		<?php // ── Active license status ─── ?>
	<div style="margin: 16px 0; padding: 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px;">
		<table class="rootz-viewer-table" style="margin: 0;">
			<tbody>
				<tr>
					<td class="rootz-viewer-label"><?php esc_html_e( 'Plan', 'rootz-ai-discovery' ); ?></td>
					<td>
						<span class="rootz-viewer-tag" style="background: #00a32a; color: #fff;">
							<?php echo esc_html( ucfirst( $rootz_current_tier ) ); ?>
						</span>
						<?php if ( 'standard' === $rootz_current_tier ) : ?>
							<span style="margin-left: 8px; font-size: 12px;">$5/mo</span>
						<?php elseif ( 'pro' === $rootz_current_tier ) : ?>
							<span style="margin-left: 8px; font-size: 12px;">$10/mo</span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td class="rootz-viewer-label"><?php esc_html_e( 'Sites', 'rootz-ai-discovery' ); ?></td>
					<td>
						<?php
						printf(
							/* translators: %1$d: number of registered sites, %2$d: maximum sites allowed */
							esc_html__( '%1$d of %2$d registered', 'rootz-ai-discovery' ),
							intval( $rootz_active_sites ),
							intval( $rootz_max_sites )
						);
						?>
						<?php if ( $rootz_active_sites >= $rootz_max_sites ) : ?>
							<span style="color: #d63638; margin-left: 8px;"><?php esc_html_e( '(limit reached)', 'rootz-ai-discovery' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<?php if ( ! empty( $rootz_expires ) ) : ?>
				<tr>
					<td class="rootz-viewer-label"><?php esc_html_e( 'Renews', 'rootz-ai-discovery' ); ?></td>
					<td><?php echo esc_html( wp_date( 'F j, Y', strtotime( $rootz_expires ) ) ); ?></td>
				</tr>
				<?php endif; ?>
				<tr>
					<td class="rootz-viewer-label"><?php esc_html_e( 'Owner Identity', 'rootz-ai-discovery' ); ?></td>
					<td>
						<div style="display: flex; align-items: center; gap: 8px;">
							<code id="rootz-owner-id-display" style="font-size: 12px; word-break: break-all; flex: 1;"><?php echo esc_html( $rootz_owner_identity ); ?></code>
							<button type="button" class="button button-small" data-rootz-copy="<?php echo esc_attr( $rootz_owner_identity ); ?>" title="<?php esc_attr_e( 'Copy to clipboard', 'rootz-ai-discovery' ); ?>">
								<?php esc_html_e( 'Copy', 'rootz-ai-discovery' ); ?>
							</button>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="rootz-viewer-hint" style="border-left-color: #2271b1; background: #f0f6fc; margin-top: 12px;">
		<strong><?php esc_html_e( 'Multi-Site Licensing:', 'rootz-ai-discovery' ); ?></strong>
		<?php esc_html_e( 'Your Owner Identity works across all your WordPress sites. Copy it above and paste it into the Account tab of any other site you own to share the same subscription. Each site keeps its own plugin wallet — the Owner Identity just links them under one license.', 'rootz-ai-discovery' ); ?>
	</div>

	<div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px;">
		<?php if ( 'standard' === $rootz_current_tier ) : ?>
		<a href="<?php echo esc_url( Rootz_License::checkout_url( 'pro' ) ); ?>" class="button button-primary">
			<?php esc_html_e( 'Upgrade to Pro — $10/mo', 'rootz-ai-discovery' ); ?>
		</a>
		<?php endif; ?>
		<form method="post" action="" style="display: inline;">
			<?php wp_nonce_field( 'rootz_refresh_license', 'rootz_refresh_license_nonce' ); ?>
			<button type="submit" name="rootz_do_refresh_license" value="1" class="button">
				<?php esc_html_e( 'Refresh License Status', 'rootz-ai-discovery' ); ?>
			</button>
		</form>
	</div>

	<?php else : ?>
		<?php // ── No license — show subscribe buttons ─── ?>
	<p class="rootz-viewer-explain">
		<?php esc_html_e( 'You are on the free tier. Subscribe to unlock unlimited AI generation, weekly monitoring, AI Readiness badges, and more.', 'rootz-ai-discovery' ); ?>
	</p>

	<div style="display: flex; gap: 12px; margin: 16px 0; flex-wrap: wrap;">
		<div style="flex: 1; min-width: 200px; padding: 20px; background: #f0f6fc; border: 2px solid #2271b1; border-radius: 8px; text-align: center;">
			<div style="font-size: 18px; font-weight: 700; color: #1d2327;"><?php esc_html_e( 'Standard', 'rootz-ai-discovery' ); ?></div>
			<div style="font-size: 28px; font-weight: 700; color: #2271b1; margin: 8px 0;">$5<span style="font-size: 14px; color: #646970;">/mo</span></div>
			<ul style="text-align: left; font-size: 13px; margin: 12px 0; padding-left: 18px;">
				<li><?php esc_html_e( 'Up to 5 sites', 'rootz-ai-discovery' ); ?></li>
				<li><?php esc_html_e( 'Unlimited AI auto-configure', 'rootz-ai-discovery' ); ?></li>
				<li><?php esc_html_e( 'Weekly readiness scans', 'rootz-ai-discovery' ); ?></li>
				<li><?php esc_html_e( 'AI Readiness Badge', 'rootz-ai-discovery' ); ?></li>
			</ul>
			<a href="<?php echo esc_url( Rootz_License::checkout_url( 'standard' ) ); ?>" class="button button-primary" style="width: 100%;">
				<?php esc_html_e( 'Subscribe to Standard', 'rootz-ai-discovery' ); ?>
			</a>
		</div>

		<div style="flex: 1; min-width: 200px; padding: 20px; background: linear-gradient(135deg, #f0f6fc 0%, #e8f0fe 100%); border: 2px solid #135e96; border-radius: 8px; text-align: center;">
			<div style="font-size: 18px; font-weight: 700; color: #1d2327;"><?php esc_html_e( 'Pro', 'rootz-ai-discovery' ); ?></div>
			<div style="font-size: 28px; font-weight: 700; color: #135e96; margin: 8px 0;">$10<span style="font-size: 14px; color: #646970;">/mo</span></div>
			<ul style="text-align: left; font-size: 13px; margin: 12px 0; padding-left: 18px;">
				<li><?php esc_html_e( 'Up to 20 sites', 'rootz-ai-discovery' ); ?></li>
				<li><?php esc_html_e( 'Everything in Standard', 'rootz-ai-discovery' ); ?></li>
				<li><?php esc_html_e( 'Wallet-delegated signing', 'rootz-ai-discovery' ); ?></li>
				<li><?php esc_html_e( 'Dynamic content & web services', 'rootz-ai-discovery' ); ?></li>
				<li><?php esc_html_e( 'Custodial wallet identity', 'rootz-ai-discovery' ); ?></li>
			</ul>
			<a href="<?php echo esc_url( Rootz_License::checkout_url( 'pro' ) ); ?>" class="button button-primary" style="width: 100%; background: #135e96; border-color: #135e96;">
				<?php esc_html_e( 'Subscribe to Pro', 'rootz-ai-discovery' ); ?>
			</a>
		</div>
	</div>
	<?php endif; ?>

	<?php // ── Owner Identity field ─── ?>
	<details style="margin-top: 16px;"<?php echo empty( $rootz_owner_identity ) && ! $rootz_has_license ? '' : ' open'; ?>>
		<summary style="cursor: pointer; font-size: 13px; color: #2271b1;">
			<?php if ( ! empty( $rootz_owner_identity ) ) : ?>
				<?php esc_html_e( 'License Key Settings', 'rootz-ai-discovery' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'Already have a License Key? Paste it here', 'rootz-ai-discovery' ); ?>
			<?php endif; ?>
		</summary>
		<div style="margin-top: 12px; padding: 16px; background: #f9f9f9; border-radius: 4px;">
			<p class="description" style="margin-bottom: 12px;">
				<?php esc_html_e( 'Your License Key (Owner Identity) represents your subscription across multiple WordPress sites. If you subscribed on another site, paste your license key here to share the subscription.', 'rootz-ai-discovery' ); ?>
			</p>
			<form method="post" action="options.php">
				<?php settings_fields( 'rootz_account' ); ?>
				<table class="form-table" role="presentation" style="margin: 0;">
					<tr style="padding: 0;">
						<th scope="row" style="padding: 8px 10px 8px 0; width: 140px;">
							<label for="rootz_owner_identity"><?php esc_html_e( 'License Key', 'rootz-ai-discovery' ); ?></label>
						</th>
						<td style="padding: 4px 0;">
							<input type="text" id="rootz_owner_identity" name="rootz_owner_identity"
									value="<?php echo esc_attr( $rootz_owner_identity ); ?>"
									class="regular-text" placeholder="0x..." style="font-family: monospace;" />
							<p class="description">
								<?php esc_html_e( 'Paste the identity address from your subscription confirmation email or from another site using the same plan.', 'rootz-ai-discovery' ); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Identity', 'rootz-ai-discovery' ), 'secondary', 'submit', false ); ?>
			</form>

			<?php if ( ! empty( $rootz_owner_identity ) && ! empty( $rootz_signing_address ) ) : ?>
			<div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #ddd;">
				<form method="post" action="">
					<?php wp_nonce_field( 'rootz_register_site', 'rootz_register_site_nonce' ); ?>
					<button type="submit" name="rootz_do_register_site" value="1" class="button">
						<?php esc_html_e( 'Register This Site Under License', 'rootz-ai-discovery' ); ?>
					</button>
					<span class="description" style="margin-left: 8px;">
						<?php
						printf(
							/* translators: %s: shortened plugin wallet address */
							esc_html__( 'Registers plugin wallet %s with your identity.', 'rootz-ai-discovery' ),
							'<code>' . esc_html( substr( $rootz_signing_address, 0, 10 ) ) . '...</code>'
						);
						?>
					</span>
				</form>
			</div>
			<?php endif; ?>
		</div>
	</details>
</div>
<?php endif; // $rootz_has_license_class ?>

<?php // ── NETWORK STATUS (Deployer Wallet Balance) ──────────────────── ?>
<div class="rootz-viewer-card" style="border-left: 4px solid #8c6dfd; background: linear-gradient(135deg, #f9f5ff 0%, #fff 100%);">
	<div class="rootz-viewer-card-header">
		<h3>
			<span class="rootz-viewer-icon">&#9889;</span>
			<?php esc_html_e( 'Network Status', 'rootz-ai-discovery' ); ?>
			<?php Rootz_Admin::help_tip( __( 'Shows the status of the Rootz identity infrastructure on Polygon. The Deployer wallet funds gas for identity contract creation. If the balance drops below 10 POL, new identity contracts cannot be created until the wallet is topped up.', 'rootz-ai-discovery' ), 'network-status' ); ?>
		</h3>
		<span id="rootz-network-status-badge" class="rootz-status-disconnected">&#9679; <?php esc_html_e( 'Loading...', 'rootz-ai-discovery' ); ?></span>
	</div>

	<div id="rootz-network-status" style="margin: 16px 0; padding: 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px;">
		<table class="rootz-viewer-table" style="margin: 0;">
			<tbody>
				<tr>
					<td class="rootz-viewer-label"><?php esc_html_e( 'Deployer Wallet', 'rootz-ai-discovery' ); ?></td>
					<td>
						<code style="font-size: 12px;">0x86670C5C580BBCCf21EbA5eaaEbCc3087bb37A19</code>
					</td>
				</tr>
				<tr>
					<td class="rootz-viewer-label"><?php esc_html_e( 'Balance', 'rootz-ai-discovery' ); ?></td>
					<td>
						<span id="rootz-deployer-balance" style="font-weight: 600;">—</span>
						<span style="color: #646970;"> POL (Polygon)</span>
						<span id="rootz-balance-warning" style="display: none; color: #d63638; margin-left: 8px; font-weight: 600;">&#9888; <?php esc_html_e( 'Low balance — top up needed', 'rootz-ai-discovery' ); ?></span>
					</td>
				</tr>
				<tr>
					<td class="rootz-viewer-label"><?php esc_html_e( 'Registry Sites', 'rootz-ai-discovery' ); ?></td>
					<td><span id="rootz-registry-count">—</span></td>
				</tr>
				<tr>
					<td class="rootz-viewer-label"><?php esc_html_e( 'Network', 'rootz-ai-discovery' ); ?></td>
					<td><?php esc_html_e( 'Polygon Mainnet (Chain ID 137)', 'rootz-ai-discovery' ); ?></td>
				</tr>
				<tr>
					<td class="rootz-viewer-label"><?php esc_html_e( 'Identity Factory', 'rootz-ai-discovery' ); ?></td>
					<td><code style="font-size: 11px;">0xc6361e4780eb16ee8643538376600D97F9E4C9c0</code></td>
				</tr>
			</tbody>
		</table>
	</div>

	<div style="display: flex; gap: 8px; align-items: center;">
		<button type="button" id="rootz-refresh-network" class="button">
			<?php esc_html_e( 'Refresh', 'rootz-ai-discovery' ); ?>
		</button>
		<span id="rootz-network-updated" style="font-size: 12px; color: #646970;"></span>
	</div>
</div>


<?php // ── PLUGIN WALLET IDENTITY ──────────────────────────────────────── ?>
<?php if ( ! empty( $rootz_signing_address ) ) : ?>
<div class="rootz-viewer-card" style="border-left: 4px solid #2271b1; background: linear-gradient(135deg, #f0f6fc 0%, #fff 100%);">
	<div class="rootz-viewer-card-header">
		<h3>
			<span class="rootz-viewer-icon">&#128737;</span>
			<?php esc_html_e( 'Plugin Wallet', 'rootz-ai-discovery' ); ?>
			<?php Rootz_Admin::help_tip( __( 'Your site generates a cryptographic keypair (secp256k1) stored locally on your server. Every AI Discovery response is signed with this key, proving the data genuinely comes from your site and has not been tampered with. The private key is encrypted in your WordPress database using your wp-config.php salts.', 'rootz-ai-discovery' ), 'wallet' ); ?>
		</h3>
		<?php if ( $rootz_can_sign ) : ?>
			<span class="rootz-status-connected">&#9679; <?php esc_html_e( 'Signing Active', 'rootz-ai-discovery' ); ?></span>
		<?php else : ?>
			<span class="rootz-status-disconnected">&#9679; <?php esc_html_e( 'Read-Only (GMP needed to sign)', 'rootz-ai-discovery' ); ?></span>
		<?php endif; ?>
	</div>

	<?php if ( $rootz_key_generated ) : ?>
	<div class="rootz-viewer-hint" style="border-left-color: #00a32a; background: #f0faf0;">
		<?php esc_html_e( 'Signing key generated! Your AI Discovery data is now cryptographically signed.', 'rootz-ai-discovery' ); ?>
	</div>
	<?php endif; ?>

	<div style="margin: 16px 0; padding: 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px;">
		<div style="font-size: 11px; text-transform: uppercase; color: #646970; letter-spacing: 0.5px; margin-bottom: 6px;">
			<?php esc_html_e( 'Site Identity Address', 'rootz-ai-discovery' ); ?>
		</div>
		<code style="font-size: 15px; font-weight: 600; letter-spacing: 0.5px; word-break: break-all; display: block; padding: 8px 0;">
			<?php echo esc_html( $rootz_signing_address ); ?>
		</code>
		<div style="margin-top: 10px; display: flex; gap: 16px; font-size: 12px; color: #646970;">
			<span><?php esc_html_e( 'Algorithm: ECDSA secp256k1', 'rootz-ai-discovery' ); ?></span>
			<span>|</span>
			<span><?php esc_html_e( 'Network: Ethereum-compatible', 'rootz-ai-discovery' ); ?></span>
			<span>|</span>
			<span>
				<?php if ( $rootz_can_sign ) : ?>
					<?php esc_html_e( 'Status: Self-signed', 'rootz-ai-discovery' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Status: Hash-only (enable GMP to sign)', 'rootz-ai-discovery' ); ?>
				<?php endif; ?>
			</span>
		</div>
	</div>

	<p class="rootz-viewer-explain">
		<?php esc_html_e( 'This is your site\'s persistent cryptographic identity. It\'s an Ethereum-compatible address generated locally on your server. Every AI Discovery response includes this address, allowing AI agents to verify data authenticity and track your site\'s identity over time.', 'rootz-ai-discovery' ); ?>
	</p>

	<table class="rootz-viewer-table">
		<tbody>
			<tr>
				<td class="rootz-viewer-label"><?php esc_html_e( 'Private Key Storage', 'rootz-ai-discovery' ); ?></td>
				<td><?php esc_html_e( 'AES-256-CBC encrypted in database (key derived from wp-config.php salts)', 'rootz-ai-discovery' ); ?></td>
			</tr>
			<tr>
				<td class="rootz-viewer-label"><?php esc_html_e( 'Signing Capability', 'rootz-ai-discovery' ); ?></td>
				<td>
					<?php if ( $rootz_can_sign ) : ?>
						<span style="color: #00a32a;">&#10003;</span>
						<?php esc_html_e( 'Active — every ai.json response is cryptographically signed', 'rootz-ai-discovery' ); ?>
					<?php else : ?>
						<span style="color: #996800;">&#9888;</span>
						<?php esc_html_e( 'Inactive — PHP GMP extension not available. Content hashes are still included.', 'rootz-ai-discovery' ); ?>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<td class="rootz-viewer-label"><?php esc_html_e( 'Authorization', 'rootz-ai-discovery' ); ?></td>
				<td>
					<span class="rootz-viewer-tag"><?php esc_html_e( 'self-signed', 'rootz-ai-discovery' ); ?></span>
					<span class="description" style="margin-left: 8px;">
						<?php esc_html_e( 'Proves consistency. Subscribe to Pro for wallet-delegated authorization.', 'rootz-ai-discovery' ); ?>
					</span>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<?php else : ?>
	<?php // ── NO KEY YET ──────────────────────────────────────────────────── ?>
<div class="rootz-viewer-card">
	<div class="rootz-viewer-card-header">
		<h3>
			<span class="rootz-viewer-icon">&#128737;</span>
			<?php esc_html_e( 'Plugin Wallet', 'rootz-ai-discovery' ); ?>
		</h3>
		<?php if ( ! $rootz_has_gmp ) : ?>
			<span class="rootz-status-disconnected">&#9679; <?php esc_html_e( 'GMP Required', 'rootz-ai-discovery' ); ?></span>
		<?php else : ?>
			<span class="rootz-status-disconnected">&#9679; <?php esc_html_e( 'Not Generated', 'rootz-ai-discovery' ); ?></span>
		<?php endif; ?>
	</div>

	<?php if ( ! $rootz_has_gmp ) : ?>
	<div class="rootz-viewer-hint" style="border-left-color: #d63638; background: #fcf0f1;">
		<?php esc_html_e( 'The PHP GMP extension is required to generate a signing key. Most production WordPress hosts have GMP enabled. Contact your hosting provider if needed. The plugin still works without it — content hashes are included but not cryptographically signed.', 'rootz-ai-discovery' ); ?>
	</div>
	<?php endif; ?>

	<p class="rootz-viewer-explain">
		<?php esc_html_e( 'Generate a persistent wallet address for your site. This Ethereum-compatible keypair is created locally and signs every AI Discovery response, proving the data is authentic and hasn\'t been tampered with.', 'rootz-ai-discovery' ); ?>
	</p>

	<?php if ( $rootz_has_gmp ) : ?>
	<form method="post" action="">
		<?php wp_nonce_field( 'rootz_generate_key', 'rootz_generate_key_nonce' ); ?>
		<p>
			<button type="submit" name="rootz_do_generate_key" value="1" class="button button-primary button-hero">
				<?php esc_html_e( 'Generate Plugin Wallet', 'rootz-ai-discovery' ); ?>
			</button>
		</p>
		<p class="description">
			<?php esc_html_e( 'Creates a new secp256k1 keypair. This only needs to happen once — the address persists until you delete it.', 'rootz-ai-discovery' ); ?>
		</p>
	</form>
	<?php endif; ?>
</div>
<?php endif; ?>

<?php // ── AUTHORIZER WALLET (OPTIONAL) ──────────────────────────────── ?>
<div class="rootz-viewer-card" style="border-left: 4px solid #c3c4c7;">
	<div class="rootz-viewer-card-header">
		<h3>
			<span class="rootz-viewer-icon">&#128176;</span>
			<?php esc_html_e( 'Authorizer Wallet', 'rootz-ai-discovery' ); ?>
			<span class="rootz-viewer-tag" style="background: #dcdcde; color: #646970; margin-left: 8px; font-size: 11px; font-weight: 400;"><?php esc_html_e( 'Optional', 'rootz-ai-discovery' ); ?></span>
			<?php Rootz_Admin::help_tip( __( 'This is for enterprise users who want to create a chain of authority. An authorizer wallet (e.g. MetaMask) can sign an on-chain transaction that delegates trust to this site\'s plugin wallet. This binds your site\'s identity to a parent wallet, domain identity, or smart contract. Most users do not need this — your plugin wallet works on its own.', 'rootz-ai-discovery' ), 'authorizer' ); ?>
		</h3>
	</div>

	<p class="rootz-viewer-explain">
		<?php esc_html_e( 'For enterprises that want to establish a chain of authority. An authorizer wallet can sign an on-chain transaction that delegates trust to this site\'s plugin wallet, binding it to a parent identity, domain, or smart contract. This upgrades the signature from "self-signed" to "delegated."', 'rootz-ai-discovery' ); ?>
	</p>
	<p class="rootz-viewer-explain" style="margin-top: 4px; font-style: italic; color: #646970;">
		<?php esc_html_e( 'You do not need this to use the plugin. Skip this section unless your organization requires delegated authorization.', 'rootz-ai-discovery' ); ?>
	</p>

	<details style="margin-top: 8px;">
		<summary style="cursor: pointer; font-size: 13px; color: #2271b1;">
			<?php if ( ! empty( $rootz_wallet_address ) ) : ?>
				<?php
				printf(
					/* translators: %s: shortened authorizer wallet address */
					esc_html__( 'Authorizer: %s', 'rootz-ai-discovery' ),
					'<code>' . esc_html( substr( $rootz_wallet_address, 0, 10 ) ) . '...</code>'
				);
				?>
			<?php else : ?>
				<?php esc_html_e( 'Set up authorizer wallet (enterprise)', 'rootz-ai-discovery' ); ?>
			<?php endif; ?>
		</summary>
		<div style="margin-top: 12px; padding: 16px; background: #f9f9f9; border-radius: 4px;">
			<form method="post" action="options.php">
				<?php settings_fields( 'rootz_account' ); ?>

				<table class="form-table" role="presentation" style="margin: 0;">
					<tr style="padding: 0;">
						<th scope="row" style="padding: 8px 10px 8px 0; width: 140px;">
							<label for="rootz_plugin_wallet"><?php esc_html_e( 'Wallet Address', 'rootz-ai-discovery' ); ?></label>
						</th>
						<td style="padding: 4px 0;">
							<input type="text" id="rootz_plugin_wallet" name="rootz_plugin_wallet"
									value="<?php echo esc_attr( $rootz_wallet_address ); ?>"
									class="regular-text" placeholder="0x..." style="font-family: monospace;" />
							<p class="description">
								<?php esc_html_e( 'The MetaMask or custodial wallet that will authorize this site. Creates a verifiable chain of trust from your organization to this site\'s AI Discovery data.', 'rootz-ai-discovery' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save Authorizer', 'rootz-ai-discovery' ), 'secondary', 'submit', false ); ?>
			</form>

			<?php if ( $rootz_has_stored_key && ! empty( $rootz_wallet_address ) ) : ?>
			<div class="rootz-viewer-hint" style="margin-top: 12px;">
				<strong><?php esc_html_e( 'Next step:', 'rootz-ai-discovery' ); ?></strong>
				<?php esc_html_e( 'Send an authorization transaction from your authorizer wallet to bind it to this site\'s plugin wallet. This feature is coming in a future update.', 'rootz-ai-discovery' ); ?>
			</div>
			<?php endif; ?>
		</div>
	</details>
</div>

<?php // ── AI CONTENT GENERATION ───────────────────────────────────────── ?>
<?php
$rootz_ai_gen       = new Rootz_Ai_Generator();
$rootz_has_proxy    = $rootz_ai_gen->has_proxy();
$rootz_has_direct   = $rootz_ai_gen->has_direct();
$rootz_ai_available = $rootz_ai_gen->is_available();
$rootz_proxy_usage  = get_transient( 'rootz_proxy_usage' );
$rootz_proxy_error  = get_transient( 'rootz_proxy_error' );
?>
<div class="rootz-viewer-card">
	<div class="rootz-viewer-card-header">
		<h3>
			<span class="rootz-viewer-icon">&#129302;</span>
			<?php esc_html_e( 'AI Content Generation', 'rootz-ai-discovery' ); ?>
		</h3>
		<?php if ( $rootz_has_proxy ) : ?>
			<span class="rootz-status-connected">&#9679; <?php esc_html_e( 'Rootz AI Active', 'rootz-ai-discovery' ); ?></span>
		<?php elseif ( $rootz_has_direct ) : ?>
			<span class="rootz-status-connected">&#9679; <?php esc_html_e( 'Direct API', 'rootz-ai-discovery' ); ?></span>
		<?php else : ?>
			<span class="rootz-status-disconnected">&#9679; <?php esc_html_e( 'Not Available', 'rootz-ai-discovery' ); ?></span>
		<?php endif; ?>
	</div>

	<?php if ( $rootz_has_proxy ) : ?>
	<p class="rootz-viewer-explain">
		<?php esc_html_e( 'AI generation is powered by your plugin wallet. Requests are signed with your site key and processed through the Rootz AI service. No API key needed.', 'rootz-ai-discovery' ); ?>
	</p>

		<?php if ( $rootz_proxy_usage ) : ?>
	<div style="margin: 12px 0; padding: 12px 16px; background: #f0f6fc; border-radius: 4px; border-left: 3px solid #2271b1;">
		<div style="display: flex; justify-content: space-between; align-items: center;">
			<span>
				<strong><?php esc_html_e( 'Usage:', 'rootz-ai-discovery' ); ?></strong>
				<?php
				$rootz_used  = intval( $rootz_proxy_usage['used'] ?? 0 );
				$rootz_limit = intval( $rootz_proxy_usage['limit'] ?? 3 );
				$rootz_tier  = esc_html( $rootz_proxy_usage['tier'] ?? 'free' );
				printf(
					/* translators: %1$d: number of AI generations used, %2$d: monthly limit */
					esc_html__( '%1$d of %2$d generations this month', 'rootz-ai-discovery' ),
					intval( $rootz_used ),
					intval( $rootz_limit )
				);
				?>
			</span>
			<span class="rootz-viewer-tag"><?php echo esc_html( $rootz_tier ); ?></span>
		</div>
			<?php if ( $rootz_limit > 0 ) : ?>
		<div style="margin-top: 8px; background: #ddd; border-radius: 3px; height: 6px; overflow: hidden;">
			<div style="background: <?php echo esc_attr( $rootz_used >= $rootz_limit ? '#d63638' : '#2271b1' ); ?>; height: 100%; width: <?php echo esc_attr( min( 100, ( $rootz_used / $rootz_limit ) * 100 ) ); ?>%; border-radius: 3px;"></div>
		</div>
		<?php endif; ?>
			<?php if ( $rootz_used >= $rootz_limit && 'free' === $rootz_tier && $rootz_has_license_class ) : ?>
		<p style="margin: 8px 0 0; font-size: 12px;">
			<a href="<?php echo esc_url( Rootz_License::checkout_url( 'standard' ) ); ?>"><?php esc_html_e( 'Upgrade for unlimited generations', 'rootz-ai-discovery' ); ?></a>
		</p>
		<?php endif; ?>
	</div>
	<?php endif; ?>

		<?php if ( $rootz_proxy_error ) : ?>
	<div class="rootz-viewer-hint" style="border-left-color: #d63638; background: #fcf0f1;">
			<?php echo esc_html( $rootz_proxy_error ); ?>
	</div>
			<?php delete_transient( 'rootz_proxy_error' ); ?>
	<?php endif; ?>

	<?php elseif ( ! $rootz_has_proxy && ! $rootz_has_direct ) : ?>
	<p class="rootz-viewer-explain">
		<?php if ( ! empty( $rootz_signing_address ) && ! $rootz_has_gmp ) : ?>
			<?php esc_html_e( 'AI generation requires the GMP extension for wallet signing. Enable GMP on your host, or add your own Anthropic API key below as a fallback.', 'rootz-ai-discovery' ); ?>
		<?php elseif ( empty( $rootz_signing_address ) ) : ?>
			<?php esc_html_e( 'Generate a plugin wallet above to enable free AI-powered content generation, or add your own Anthropic API key below.', 'rootz-ai-discovery' ); ?>
		<?php endif; ?>
	</p>
	<?php endif; ?>

	<?php // Advanced: Direct API key section. ?>
	<details style="margin-top: 12px;"<?php echo ! $rootz_has_proxy && $rootz_has_direct ? ' open' : ''; ?>>
		<summary style="cursor: pointer; font-size: 13px; color: #2271b1;">
			<?php esc_html_e( 'Advanced: Use your own Anthropic API key', 'rootz-ai-discovery' ); ?>
			<?php if ( $rootz_has_direct ) : ?>
				<span class="rootz-viewer-tag" style="margin-left: 6px;"><?php esc_html_e( 'configured', 'rootz-ai-discovery' ); ?></span>
			<?php endif; ?>
		</summary>
		<div style="margin-top: 12px; padding: 12px; background: #f9f9f9; border-radius: 4px;">
			<p class="description" style="margin-bottom: 8px;">
				<?php esc_html_e( 'Optional fallback. If the Rootz AI service is unavailable, the plugin will use this key to call Anthropic directly.', 'rootz-ai-discovery' ); ?>
			</p>
			<form method="post" action="options.php">
				<?php settings_fields( 'rootz_account' ); ?>
				<table class="form-table" role="presentation" style="margin: 0;">
					<tr style="padding: 0;">
						<td style="padding: 4px 0;">
							<input type="password" id="rootz_ai_api_key" name="rootz_ai_api_key"
									value="<?php echo esc_attr( $rootz_ai_api_key ); ?>"
									class="regular-text" placeholder="sk-ant-..." autocomplete="off" />
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save API Key', 'rootz-ai-discovery' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
	</details>
</div>

<?php // ── AUTO-POPULATE ───────────────────────────────────────────────── ?>
<div class="rootz-viewer-card">
	<div class="rootz-viewer-card-header">
		<h3>
			<span class="rootz-viewer-icon">&#9889;</span>
			<?php esc_html_e( 'Auto-Populate from Site Data', 'rootz-ai-discovery' ); ?>
		</h3>
	</div>
	<p class="rootz-viewer-explain">
		<?php if ( $rootz_ai_available ) : ?>
			<?php esc_html_e( 'AI-powered auto-populate is active. Click below to have AI analyze your site content and generate polished AI Discovery fields.', 'rootz-ai-discovery' ); ?>
		<?php else : ?>
			<?php esc_html_e( 'Reads your existing WordPress content and pre-fills AI Discovery fields. Generate a plugin wallet or add an API key above for AI-enhanced generation.', 'rootz-ai-discovery' ); ?>
		<?php endif; ?>
	</p>

	<?php if ( $rootz_auto_populate ) : ?>
	<div class="rootz-viewer-hint" style="border-left-color: #00a32a; background: #f0faf0;">
		<?php esc_html_e( 'Auto-populate complete! Check the Identity tab to review the generated content.', 'rootz-ai-discovery' ); ?>
	</div>
	<?php endif; ?>

	<div id="rootz-auto-populate-preview" style="margin: 16px 0;">
		<?php
		$rootz_site_name  = get_bloginfo( 'name' );
		$rootz_site_desc  = get_bloginfo( 'description' );
		$rootz_about_page = null;
		foreach ( array( 'about', 'about-us', 'who-we-are' ) as $rootz_slug ) {
			$rootz_p = get_page_by_path( $rootz_slug );
			if ( $rootz_p && 'publish' === $rootz_p->post_status ) {
				$rootz_about_page = $rootz_p;
				break;
			}
		}
		$rootz_categories = get_categories(
			array(
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => 10,
				'hide_empty' => true,
				'exclude'    => array( 1 ),
			)
		);
		$rootz_page_count = wp_count_posts( 'page' )->publish;
		$rootz_post_count = wp_count_posts( 'post' )->publish;
		$rootz_has_woo    = class_exists( 'WooCommerce' );
		?>

		<table class="rootz-viewer-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Data Source', 'rootz-ai-discovery' ); ?></th>
					<th><?php esc_html_e( 'Found', 'rootz-ai-discovery' ); ?></th>
					<th><?php esc_html_e( 'Will Populate', 'rootz-ai-discovery' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php esc_html_e( 'Site Title', 'rootz-ai-discovery' ); ?></td>
					<td><strong><?php echo esc_html( $rootz_site_name ); ?></strong></td>
					<td><?php esc_html_e( 'Organization Name', 'rootz-ai-discovery' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Site Tagline', 'rootz-ai-discovery' ); ?></td>
					<td><?php echo esc_html( $rootz_site_desc ? $rootz_site_desc : '(empty)' ); ?></td>
					<td><?php esc_html_e( 'Mission / Tagline', 'rootz-ai-discovery' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'About Page', 'rootz-ai-discovery' ); ?></td>
					<td>
						<?php if ( $rootz_about_page ) : ?>
							<span style="color: #00a32a;">&#10003;</span> <?php echo esc_html( $rootz_about_page->post_title ); ?>
						<?php else : ?>
							<span style="color: #996800;">&#10007;</span> <?php esc_html_e( 'Not found', 'rootz-ai-discovery' ); ?>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $rootz_ai_available ) : ?>
							<?php esc_html_e( 'AI Summary (AI-drafted from page content)', 'rootz-ai-discovery' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'AI Summary (first 80 words of about page)', 'rootz-ai-discovery' ); ?>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Categories', 'rootz-ai-discovery' ); ?></td>
					<td><?php echo esc_html( count( $rootz_categories ) ); ?> <?php esc_html_e( 'categories', 'rootz-ai-discovery' ); ?></td>
					<td><?php esc_html_e( 'Core Concepts / Glossary', 'rootz-ai-discovery' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Pages', 'rootz-ai-discovery' ); ?></td>
					<td><?php echo esc_html( $rootz_page_count ); ?> <?php esc_html_e( 'published', 'rootz-ai-discovery' ); ?></td>
					<td><?php esc_html_e( 'Site map + Knowledge base', 'rootz-ai-discovery' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Posts', 'rootz-ai-discovery' ); ?></td>
					<td><?php echo esc_html( $rootz_post_count ); ?> <?php esc_html_e( 'published', 'rootz-ai-discovery' ); ?></td>
					<td><?php esc_html_e( 'AI Feed', 'rootz-ai-discovery' ); ?></td>
				</tr>
				<?php if ( $rootz_has_woo ) : ?>
				<tr>
					<td><?php esc_html_e( 'WooCommerce', 'rootz-ai-discovery' ); ?></td>
					<td><span style="color: #00a32a;">&#10003;</span> <?php esc_html_e( 'Detected', 'rootz-ai-discovery' ); ?></td>
					<td><?php esc_html_e( 'Products, pricing, inventory (future)', 'rootz-ai-discovery' ); ?></td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<form method="post" action="">
		<?php wp_nonce_field( 'rootz_auto_populate', 'rootz_auto_populate_nonce' ); ?>
		<p>
			<button type="submit" name="rootz_do_auto_populate" value="1" class="button button-primary">
				<?php if ( $rootz_ai_available ) : ?>
					<?php esc_html_e( 'Auto-Populate with AI', 'rootz-ai-discovery' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Auto-Populate from Site Data', 'rootz-ai-discovery' ); ?>
				<?php endif; ?>
			</button>
			<span class="description" style="margin-left: 12px;">
				<?php esc_html_e( 'Won\'t overwrite fields you\'ve already filled in.', 'rootz-ai-discovery' ); ?>
			</span>
		</p>
	</form>
</div>

<?php // ── SEO TAGS ────────────────────────────────────────────────────── ?>
<div class="rootz-viewer-card">
	<div class="rootz-viewer-card-header">
		<h3>
			<span class="rootz-viewer-icon">&#128270;</span>
			<?php esc_html_e( 'SEO Meta Tags', 'rootz-ai-discovery' ); ?>
		</h3>
	</div>
	<p class="rootz-viewer-explain">
		<?php esc_html_e( 'When enabled, the plugin adds a meta description, OpenGraph tags, and JSON-LD Organization schema to your site\'s HTML head. These are automatically skipped if a dedicated SEO plugin (Yoast, RankMath, AIOSEO, SEOPress) is detected.', 'rootz-ai-discovery' ); ?>
	</p>
	<form method="post" action="options.php">
		<?php settings_fields( 'rootz_account' ); ?>
		<label style="display: flex; align-items: center; gap: 8px; padding: 8px 0;">
			<input type="hidden" name="rootz_enable_seo_tags" value="0" />
			<input type="checkbox" name="rootz_enable_seo_tags" value="1"
					<?php checked( '1', get_option( 'rootz_enable_seo_tags', '1' ) ); ?> />
			<?php esc_html_e( 'Add meta description, OpenGraph, and JSON-LD when no SEO plugin is detected', 'rootz-ai-discovery' ); ?>
		</label>
		<?php submit_button( __( 'Save', 'rootz-ai-discovery' ), 'secondary', 'submit', false ); ?>
	</form>
</div>

<?php // ── AI DISCOVERY REGISTRY ──────────────────────────────────────── ?>
<?php
$rootz_registry_consent = get_option( 'rootz_registry_consent', '0' );
$rootz_site_domain      = wp_parse_url( home_url(), PHP_URL_HOST );
?>
<div class="rootz-viewer-card" style="border-left: 4px solid #8c6dfd;">
	<div class="rootz-viewer-card-header">
		<h3>
			<span class="rootz-viewer-icon">&#127760;</span>
			<?php esc_html_e( 'AI Discovery Adoption Registry', 'rootz-ai-discovery' ); ?>
		</h3>
		<?php if ( '1' === $rootz_registry_consent ) : ?>
			<span class="rootz-status-connected">&#9679; <?php esc_html_e( 'Listed', 'rootz-ai-discovery' ); ?></span>
		<?php else : ?>
			<span class="rootz-status-disconnected">&#9679; <?php esc_html_e( 'Not Listed', 'rootz-ai-discovery' ); ?></span>
		<?php endif; ?>
	</div>

	<p class="rootz-viewer-explain">
		<?php esc_html_e( 'Help grow the AI Discovery Standard by sharing your site\'s domain with the public adoption registry. Listed sites appear on the AI Discovery Standard website as real-world implementations, demonstrating the growing ecosystem.', 'rootz-ai-discovery' ); ?>
	</p>

	<div style="margin: 12px 0; padding: 12px 16px; background: #f9f5ff; border-radius: 4px; border-left: 3px solid #8c6dfd; font-size: 13px;">
		<strong><?php esc_html_e( 'What gets shared:', 'rootz-ai-discovery' ); ?></strong>
		<?php
		printf(
			/* translators: %s: the site domain */
			esc_html__( ' Your domain (%s) and plugin version. No personal data, no IP address, no content.', 'rootz-ai-discovery' ),
			'<code>' . esc_html( $rootz_site_domain ) . '</code>'
		);
		?>
	</div>

	<form method="post" action="options.php">
		<?php settings_fields( 'rootz_account' ); ?>
		<label style="display: flex; align-items: center; gap: 8px; padding: 8px 0;">
			<input type="hidden" name="rootz_registry_consent" value="0" />
			<input type="checkbox" name="rootz_registry_consent" value="1"
					<?php checked( '1', $rootz_registry_consent ); ?> />
			<?php esc_html_e( 'List my site in the AI Discovery adoption registry', 'rootz-ai-discovery' ); ?>
		</label>
		<p class="description" style="margin-left: 28px; margin-top: -4px;">
			<?php esc_html_e( 'You can uncheck this at any time to remove your site from the registry.', 'rootz-ai-discovery' ); ?>
		</p>
		<?php submit_button( __( 'Save', 'rootz-ai-discovery' ), 'secondary', 'submit', false ); ?>
	</form>
</div>
