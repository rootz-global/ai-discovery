<?php
/**
 * Quick Start Guide — dismissible welcome panel.
 *
 * Included at the top of settings-viewer.php.
 * Leads with Auto-Populate for existing sites.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rootz_dismissed = '1' === get_option( 'rootz_quickstart_dismissed', '0' );
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag, no state change.
if ( $rootz_dismissed && ! isset( $_GET['show-guide'] ) ) {
	return;
}

$rootz_has_summary  = '' !== get_option( 'rootz_ai_summary', '' );
$rootz_has_org_name = '' !== get_option( 'rootz_organization_name', '' );
$rootz_has_wallet   = Rootz_Signer::has_stored_key();
$rootz_has_signed   = ! empty( get_option( 'rootz_signed_manifest', false ) ) && '1' !== get_option( 'rootz_manifest_needs_signing', '0' );
$rootz_populate_url = add_query_arg(
	array(
		'page' => 'rootz-ai-discovery',
		'tab'  => 'account',
	),
	admin_url( 'options-general.php' )
);
?>
<div class="rootz-quickstart">
	<div class="rootz-quickstart-header">
		<h3>
			<span class="rootz-viewer-icon">&#127919;</span>
			<?php esc_html_e( 'Quick Start Guide', 'rootz-ai-discovery' ); ?>
		</h3>
		<?php if ( ! $rootz_dismissed ) : ?>
		<a href="
			<?php
			echo esc_url(
				wp_nonce_url(
					add_query_arg(
						array(
							'page'                     => 'rootz-ai-discovery',
							'tab'                      => 'viewer',
							'rootz_dismiss_quickstart' => '1',
						),
						admin_url( 'options-general.php' )
					),
					'rootz_dismiss_quickstart'
				)
			);
			?>
		" class="button button-small">
			<?php esc_html_e( 'Dismiss', 'rootz-ai-discovery' ); ?>
		</a>
		<?php endif; ?>
	</div>

	<p style="font-size: 14px; line-height: 1.6; max-width: 700px; margin: 0 0 16px;">
		<?php esc_html_e( 'AI Discovery makes your WordPress site readable by AI agents (ChatGPT, Claude, Gemini, Perplexity). Instead of scraping your HTML, AI agents get structured, verified data about your organization, content, and policies — like robots.txt, but for the AI era.', 'rootz-ai-discovery' ); ?>
	</p>

	<?php if ( ! $rootz_has_summary ) : ?>
	<div style="background: #fff; border: 2px solid #00a32a; border-radius: 6px; padding: 14px 18px; margin: 0 0 16px; max-width: 600px;">
		<h4 style="margin: 0 0 6px; font-size: 14px; color: #1d2327;">
			&#9889; <?php esc_html_e( 'Step 1: Auto-Populate Your Site Data', 'rootz-ai-discovery' ); ?>
		</h4>
		<p style="margin: 0 0 10px; font-size: 13px; color: #50575e;">
			<?php esc_html_e( 'Since you have an existing WordPress site, we can fill in most fields automatically from your pages, categories, and site settings. If the AI proxy is available, it will write your AI Summary and Core Concepts too.', 'rootz-ai-discovery' ); ?>
		</p>
		<a href="<?php echo esc_url( $rootz_populate_url ); ?>" class="button button-primary">
			<?php esc_html_e( 'Go to Auto-Populate', 'rootz-ai-discovery' ); ?>
		</a>
	</div>
	<?php endif; ?>

	<h4 style="margin: 0 0 8px; font-size: 14px;"><?php esc_html_e( 'Setup Checklist', 'rootz-ai-discovery' ); ?></h4>
	<table class="rootz-viewer-table" style="max-width: 600px;">
		<tbody>
			<?php
			$rootz_checklist = array(
				array(
					'label' => __( 'Auto-populate site data (or fill in manually)', 'rootz-ai-discovery' ),
					'done'  => $rootz_has_summary && $rootz_has_org_name,
					'tab'   => 'account',
				),
				array(
					'label' => __( 'Plugin wallet is generated (for signing)', 'rootz-ai-discovery' ),
					'done'  => $rootz_has_wallet,
					'tab'   => 'account',
					'note'  => Rootz_Signer::has_gmp() ? '' : __( '(requires PHP GMP extension)', 'rootz-ai-discovery' ),
				),
				array(
					'label' => __( 'Approve & sign your manifest', 'rootz-ai-discovery' ),
					'done'  => $rootz_has_signed,
					'tab'   => 'viewer',
				),
				array(
					'label' => __( 'Scan your site at rootz.global/ai-discovery', 'rootz-ai-discovery' ),
					'done'  => false,
					'url'   => 'https://rootz.global/ai-discovery',
					'tab'   => '',
				),
			);
			foreach ( $rootz_checklist as $rootz_item ) :
				$rootz_icon = $rootz_item['done'] ? '<span style="color: #00a32a;">&#10003;</span>' : '<span style="color: #dba617;">&#9675;</span>';
				if ( ! empty( $rootz_item['url'] ) ) {
					$rootz_link_url = $rootz_item['url'];
				} else {
					$rootz_link_url = add_query_arg(
						array(
							'page' => 'rootz-ai-discovery',
							'tab'  => $rootz_item['tab'],
						),
						admin_url( 'options-general.php' )
					);
				}
				?>
			<tr>
				<td style="width: 24px; text-align: center;"><?php echo wp_kses( $rootz_icon, array( 'span' => array( 'style' => array() ) ) ); ?></td>
				<td>
					<?php if ( ! $rootz_item['done'] ) : ?>
						<a href="<?php echo esc_url( $rootz_link_url ); ?>" <?php echo ! empty( $rootz_item['url'] ) ? 'target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $rootz_item['label'] ); ?></a>
					<?php else : ?>
						<span style="color: #50575e;"><?php echo esc_html( $rootz_item['label'] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $rootz_item['note'] ) ) : ?>
						<span class="description"> <?php echo esc_html( $rootz_item['note'] ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<h4 style="margin: 16px 0 8px; font-size: 13px; font-weight: 600;"><?php esc_html_e( 'What the plugin does automatically', 'rootz-ai-discovery' ); ?></h4>
	<ul style="list-style: disc; padding-left: 20px; font-size: 13px; color: #50575e; margin: 0;">
		<li><?php esc_html_e( 'Serves /.well-known/ai with your identity, policies, and capabilities', 'rootz-ai-discovery' ); ?></li>
		<li><?php esc_html_e( 'Generates a knowledge base from your About page and categories', 'rootz-ai-discovery' ); ?></li>
		<li><?php esc_html_e( 'Publishes recent posts as an AI-optimized feed', 'rootz-ai-discovery' ); ?></li>
		<li><?php esc_html_e( 'Exposes pages, posts, and media as structured content', 'rootz-ai-discovery' ); ?></li>
		<li><?php esc_html_e( 'Computes content hashes for integrity verification', 'rootz-ai-discovery' ); ?></li>
		<li><?php esc_html_e( 'Provides 8 REST API tools (search, verify, status, and more)', 'rootz-ai-discovery' ); ?></li>
		<li><?php esc_html_e( 'Adds an AI Discovery link tag to your site header', 'rootz-ai-discovery' ); ?></li>
	</ul>

	<h4 style="margin: 16px 0 8px; font-size: 13px; font-weight: 600;"><?php esc_html_e( 'How to verify', 'rootz-ai-discovery' ); ?></h4>
	<ol style="font-size: 13px; color: #50575e; padding-left: 20px; margin: 0;">
		<li>
		<?php
			printf(
				/* translators: %s: link to the /.well-known/ai endpoint */
				esc_html__( 'Visit %s — you should see a JSON response with your organization data', 'rootz-ai-discovery' ),
				'<a href="' . esc_url( home_url( '/.well-known/ai' ) ) . '" target="_blank"><code>/.well-known/ai</code></a>'
			);
			?>
			</li>
		<li>
		<?php
			printf(
				/* translators: %s: link to the AI Discovery scanner */
				esc_html__( 'Scan your site at %s to get your score (aim for an A!)', 'rootz-ai-discovery' ),
				'<a href="https://rootz.global/ai-discovery" target="_blank" rel="noopener">rootz.global/ai-discovery</a>'
			);
			?>
			</li>
		<li><?php esc_html_e( 'Check the Analytics tab after a few days to see which AI agents visit', 'rootz-ai-discovery' ); ?></li>
	</ol>
</div>
