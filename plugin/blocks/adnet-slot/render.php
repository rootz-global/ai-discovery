<?php
/**
 * Adnet Ad Slot block — server-side render.
 *
 * @package Rootz_AI_Discovery
 *
 * @var array  $attributes Block attributes.
 * @var string $content    Inner block content (unused — no inner blocks).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rootz_campaign_id    = isset( $attributes['campaignId'] ) ? sanitize_text_field( $attributes['campaignId'] ) : '';
$rootz_placeholder    = isset( $attributes['placeholderText'] ) ? sanitize_text_field( $attributes['placeholderText'] ) : __( 'Advertisement', 'rootz-ai-discovery' );
$rootz_adnet_enabled  = get_option( 'rootz_adnet_enabled', '0' );
$rootz_adnet_wallet   = get_option( 'rootz_adnet_publisher_wallet', '' );

// Show an editor-only notice if campaign ID is missing and the user can edit.
if ( empty( $rootz_campaign_id ) ) {
	if ( current_user_can( 'edit_posts' ) ) {
		echo '<p class="rootz-adnet-no-campaign" style="background:#fff3cd;border:1px solid #ffc107;padding:8px 12px;border-radius:4px;">'
			. esc_html__( 'Adnet Ad Slot: no Campaign ID set. Edit this block and enter a campaign ID from your publisher dashboard.', 'rootz-ai-discovery' )
			. '</p>';
	}
	return;
}

// Silently render nothing if Adnet is disabled or wallet not configured.
if ( '1' !== $rootz_adnet_enabled || empty( $rootz_adnet_wallet ) ) {
	return;
}
?>
<div class="rootz-adnet-slot wp-block-rootz-ai-discovery-adnet-slot"
	data-campaign="<?php echo esc_attr( $rootz_campaign_id ); ?>"
	aria-label="<?php echo esc_attr( $rootz_placeholder ); ?>"
	role="region">
	<span class="rootz-adnet-label screen-reader-text"><?php echo esc_html( $rootz_placeholder ); ?></span>
</div>
