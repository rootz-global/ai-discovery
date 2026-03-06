<?php
/**
 * AI Data Viewer tab — human-readable view of what AI agents see.
 *
 * Shows every piece of data served to AI agents, formatted for humans,
 * so the site manager can review and be comfortable with what's exposed.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Quick Start Guide (dismissible).
include ROOTZ_AI_DISCOVERY_DIR . 'admin/views/quick-start.php';

// Score Preview (local self-test).
include ROOTZ_AI_DISCOVERY_DIR . 'admin/views/score-preview.php';

// Generate all the data that AI agents would see.
$generator     = new Rootz_Ai_Json();
delete_transient( 'rootz_ai_json_cache' );
$ai_json       = $generator->generate();

$rest          = new Rootz_Rest_Api();
$knowledge_on  = '1' === get_option( 'rootz_enable_knowledge', '1' );
$feed_on       = '1' === get_option( 'rootz_enable_feed', '1' );
$content_on    = '1' === get_option( 'rootz_enable_content', '0' );
?>

<div class="rootz-viewer-intro">
    <h2><?php esc_html_e( 'What AI Agents See', 'rootz-ai-discovery' ); ?></h2>
    <p class="description">
        <?php esc_html_e( 'This is exactly the data served when an AI agent (ChatGPT, Claude, Gemini, Perplexity) visits your site. Review each section to make sure you\'re comfortable with what\'s being shared.', 'rootz-ai-discovery' ); ?>
    </p>
</div>

<?php
// ── MANIFEST STATUS ─────────────────────────────────────────────── //
$needs_signing  = '1' === get_option( 'rootz_manifest_needs_signing', '0' );
$signed_at      = get_option( 'rootz_manifest_signed_at', '' );
$has_signed     = ! empty( get_option( 'rootz_signed_manifest', false ) );
$sign_url       = wp_nonce_url(
    add_query_arg( array( 'page' => 'rootz-ai-discovery', 'tab' => 'viewer', 'rootz_sign_manifest' => '1' ), admin_url( 'options-general.php' ) ),
    'rootz_sign_manifest'
);

if ( $needs_signing ) {
    $status_color  = '#dba617';
    $status_border = '#dba617';
    $status_label  = __( 'Changes Detected — Signing Required', 'rootz-ai-discovery' );
    $status_icon   = '&#9888;';
} elseif ( $has_signed ) {
    $status_color  = '#00a32a';
    $status_border = '#00a32a';
    $status_label  = __( 'Signed & Current', 'rootz-ai-discovery' );
    $status_icon   = '&#10003;';
} else {
    $status_color  = '#d63638';
    $status_border = '#d63638';
    $status_label  = __( 'Not Yet Signed', 'rootz-ai-discovery' );
    $status_icon   = '&#10007;';
}
?>
<div class="rootz-viewer-card" style="background: #f0f6fc; border-left: 4px solid <?php echo esc_attr( $status_border ); ?>;">
    <div class="rootz-viewer-card-header">
        <h3>
            <span class="rootz-viewer-icon">&#9881;</span>
            <?php esc_html_e( 'Manifest Status', 'rootz-ai-discovery' ); ?>
            <span style="color: <?php echo esc_attr( $status_color ); ?>; margin-left: 10px; font-size: 14px;">
                <?php echo $status_icon; // phpcs:ignore ?> <?php echo esc_html( $status_label ); ?>
            </span>
        </h3>
    </div>
    <table class="rootz-viewer-table" style="max-width: 600px;">
        <tbody>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Pages in manifest', 'rootz-ai-discovery' ); ?></td>
                <td><strong><?php echo count( $ai_json['pages'] ?? array() ); ?></strong></td>
            </tr>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Spec version', 'rootz-ai-discovery' ); ?></td>
                <td><?php echo esc_html( $ai_json['specVersion'] ?? '—' ); ?></td>
            </tr>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Signer', 'rootz-ai-discovery' ); ?></td>
                <td><code><?php echo esc_html( $ai_json['_signature']['signer'] ?? 'none' ); ?></code></td>
            </tr>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Last signed', 'rootz-ai-discovery' ); ?></td>
                <td>
                    <?php if ( $signed_at ) : ?>
                        <?php echo esc_html( wp_date( 'M j, Y g:i A', strtotime( $signed_at ) ) ); ?>
                    <?php else : ?>
                        <em><?php esc_html_e( 'Never — manifest has not been signed yet', 'rootz-ai-discovery' ); ?></em>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Signing policy', 'rootz-ai-discovery' ); ?></td>
                <td><?php esc_html_e( 'Admin-approved only. Content changes are detected automatically. You must review and approve before the manifest is re-signed.', 'rootz-ai-discovery' ); ?></td>
            </tr>
        </tbody>
    </table>
    <p style="margin-top: 10px;">
        <?php if ( $needs_signing || ! $has_signed ) : ?>
            <a href="<?php echo esc_url( $sign_url ); ?>" class="button button-primary">
                <?php echo $has_signed ? esc_html__( 'Approve & Sign Manifest', 'rootz-ai-discovery' ) : esc_html__( 'Review & Sign Manifest', 'rootz-ai-discovery' ); ?>
            </a>
        <?php endif; ?>
        <a href="<?php echo esc_url( home_url( '/.well-known/ai' ) ); ?>" target="_blank" class="button" style="margin-left: 8px;">
            <?php esc_html_e( 'View Live Endpoint', 'rootz-ai-discovery' ); ?>
        </a>
        <?php if ( isset( $_GET['signed'] ) && '1' === $_GET['signed'] ) : ?>
            <span style="color: #00a32a; margin-left: 10px; font-weight: bold;">&#10003; <?php esc_html_e( 'Manifest signed successfully!', 'rootz-ai-discovery' ); ?></span>
        <?php endif; ?>
    </p>
</div>

<?php // ── SECTION 1: IDENTITY ─────────────────────────────────────────── ?>
<div class="rootz-viewer-card">
    <div class="rootz-viewer-card-header">
        <h3>
            <span class="rootz-viewer-icon">&#128100;</span>
            <?php esc_html_e( 'Your Identity', 'rootz-ai-discovery' ); ?>
        </h3>
        <span class="rootz-viewer-endpoint"><code>/.well-known/ai</code></span>
    </div>
    <p class="rootz-viewer-explain"><?php esc_html_e( 'When an AI is asked "What does this company do?", this is where it gets the answer.', 'rootz-ai-discovery' ); ?></p>
    <table class="rootz-viewer-table">
        <tbody>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Name', 'rootz-ai-discovery' ); ?></td>
                <td><?php echo esc_html( $ai_json['organization']['name'] ?? '—' ); ?></td>
            </tr>
            <?php if ( ! empty( $ai_json['organization']['mission'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Mission', 'rootz-ai-discovery' ); ?></td>
                <td><?php echo esc_html( $ai_json['organization']['mission'] ); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ( ! empty( $ai_json['organization']['tagline'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Tagline', 'rootz-ai-discovery' ); ?></td>
                <td><?php echo esc_html( $ai_json['organization']['tagline'] ); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Domain', 'rootz-ai-discovery' ); ?></td>
                <td><?php echo esc_html( $ai_json['organization']['domain'] ?? '' ); ?></td>
            </tr>
            <?php if ( ! empty( $ai_json['organization']['sector'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Sector', 'rootz-ai-discovery' ); ?></td>
                <td><?php echo esc_html( is_array( $ai_json['organization']['sector'] ) ? implode( ', ', $ai_json['organization']['sector'] ) : $ai_json['organization']['sector'] ); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ( ! empty( $ai_json['organization']['legalName'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Legal Name', 'rootz-ai-discovery' ); ?></td>
                <td><?php echo esc_html( $ai_json['organization']['legalName'] ); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ( ! empty( $ai_json['organization']['founded'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Founded', 'rootz-ai-discovery' ); ?></td>
                <td><?php echo esc_html( $ai_json['organization']['founded'] ); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ( ! empty( $ai_json['organization']['headquarters'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Headquarters', 'rootz-ai-discovery' ); ?></td>
                <td><?php echo esc_html( $ai_json['organization']['headquarters'] ); ?></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php // ── Contact info (all fields) ──── ?>
    <?php if ( ! empty( $ai_json['contact'] ) ) : ?>
    <h4 style="margin-top: 16px;"><?php esc_html_e( 'Contact Information', 'rootz-ai-discovery' ); ?></h4>
    <table class="rootz-viewer-table">
        <tbody>
            <?php if ( ! empty( $ai_json['contact']['email'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Email', 'rootz-ai-discovery' ); ?></td>
                <td><?php echo esc_html( $ai_json['contact']['email'] ); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ( ! empty( $ai_json['contact']['url'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Contact URL', 'rootz-ai-discovery' ); ?></td>
                <td><?php echo esc_html( $ai_json['contact']['url'] ); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ( ! empty( $ai_json['contact']['operator'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Operator', 'rootz-ai-discovery' ); ?></td>
                <td><?php echo esc_html( $ai_json['contact']['operator'] ); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ( ! empty( $ai_json['contact']['aiSupport'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'AI Support', 'rootz-ai-discovery' ); ?></td>
                <td><?php echo esc_html( $ai_json['contact']['aiSupport'] ); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ( ! empty( $ai_json['contact']['privacy'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Privacy', 'rootz-ai-discovery' ); ?></td>
                <td><?php echo esc_html( $ai_json['contact']['privacy'] ); ?></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php if ( empty( $ai_json['organization']['mission'] ) || empty( $ai_json['organization']['sector'] ) ) : ?>
    <div class="rootz-viewer-hint">
        <?php esc_html_e( 'Tip: Fill in your Mission and Sector on the Identity tab to help AI agents describe your organization accurately.', 'rootz-ai-discovery' ); ?>
    </div>
    <?php endif; ?>
</div>

<?php // ── SECTION 2: CORE CONCEPTS ───────────────────────────────────── ?>
<?php if ( ! empty( $ai_json['coreConcepts'] ) ) : ?>
<div class="rootz-viewer-card">
    <div class="rootz-viewer-card-header">
        <h3>
            <span class="rootz-viewer-icon">&#128218;</span>
            <?php esc_html_e( 'Core Concepts', 'rootz-ai-discovery' ); ?>
            <span class="rootz-viewer-count"><?php echo count( $ai_json['coreConcepts'] ); ?></span>
        </h3>
    </div>
    <p class="rootz-viewer-explain"><?php esc_html_e( 'Key terms AI agents use to understand your domain. If someone asks "What is X?", these definitions are the authoritative source.', 'rootz-ai-discovery' ); ?></p>
    <table class="rootz-viewer-table">
        <tbody>
            <?php foreach ( $ai_json['coreConcepts'] as $concept ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php echo esc_html( $concept['term'] ); ?></td>
                <td><?php echo esc_html( $concept['definition'] ?? '(no definition)' ); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php // ── SECTION 3: CAPABILITIES ───────────────────────────────────── ?>
<?php if ( ! empty( $ai_json['capabilities'] ) ) : ?>
<div class="rootz-viewer-card">
    <div class="rootz-viewer-card-header">
        <h3>
            <span class="rootz-viewer-icon">&#9889;</span>
            <?php esc_html_e( 'Capabilities', 'rootz-ai-discovery' ); ?>
        </h3>
    </div>
    <p class="rootz-viewer-explain"><?php esc_html_e( 'What your site offers AI agents — which endpoints are available and how to access them.', 'rootz-ai-discovery' ); ?></p>
    <table class="rootz-viewer-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Capability', 'rootz-ai-discovery' ); ?></th>
                <th><?php esc_html_e( 'URL', 'rootz-ai-discovery' ); ?></th>
                <th><?php esc_html_e( 'Status', 'rootz-ai-discovery' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $ai_json['capabilities'] as $cap_key => $cap ) :
                if ( ! is_array( $cap ) ) continue;
                $is_available = ! empty( $cap['available'] );
                $cap_url      = $cap['url'] ?? '—';
            ?>
            <tr>
                <td><strong><?php echo esc_html( ucfirst( $cap_key ) ); ?></strong></td>
                <td><code><?php echo esc_html( $cap_url ); ?></code></td>
                <td>
                    <?php if ( $is_available ) : ?>
                        <span style="color: #00a32a;">&#10003; <?php esc_html_e( 'Active', 'rootz-ai-discovery' ); ?></span>
                    <?php else : ?>
                        <span style="color: #996800;">&#9679; <?php esc_html_e( 'Disabled', 'rootz-ai-discovery' ); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php // ── SECTION 4: POLICIES ─────────────────────────────────────────── ?>
<div class="rootz-viewer-card">
    <div class="rootz-viewer-card-header">
        <h3>
            <span class="rootz-viewer-icon">&#128220;</span>
            <?php esc_html_e( 'Content Policies', 'rootz-ai-discovery' ); ?>
        </h3>
        <span class="rootz-viewer-endpoint"><code>/.well-known/ai/policies</code></span>
    </div>
    <p class="rootz-viewer-explain"><?php esc_html_e( 'Rules AI agents should follow when using your content. These are machine-readable instructions, not just suggestions.', 'rootz-ai-discovery' ); ?></p>
    <table class="rootz-viewer-table">
        <tbody>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'License', 'rootz-ai-discovery' ); ?></td>
                <td><?php echo esc_html( $ai_json['policies']['contentLicense']['type'] ?? 'All Rights Reserved' ); ?></td>
            </tr>
            <?php if ( ! empty( $ai_json['policies']['contentLicense']['permissions'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Allowed', 'rootz-ai-discovery' ); ?></td>
                <td>
                    <?php foreach ( $ai_json['policies']['contentLicense']['permissions'] as $perm ) : ?>
                        <span class="rootz-viewer-tag rootz-tag-green"><?php echo esc_html( $perm ); ?></span>
                    <?php endforeach; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if ( ! empty( $ai_json['policies']['contentLicense']['restrictions'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Restricted', 'rootz-ai-discovery' ); ?></td>
                <td>
                    <?php foreach ( $ai_json['policies']['contentLicense']['restrictions'] as $restrict ) : ?>
                        <span class="rootz-viewer-tag rootz-tag-red"><?php echo esc_html( $restrict ); ?></span>
                    <?php endforeach; ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php
    // Show discovered policy pages from the manifest.
    $policy_skip = array( 'contentLicense', 'detailsUrl' );
    $discovered  = array();
    foreach ( ( $ai_json['policies'] ?? array() ) as $pkey => $pval ) {
        if ( in_array( $pkey, $policy_skip, true ) || ! is_array( $pval ) ) {
            continue;
        }
        $discovered[ $pkey ] = $pval;
    }
    if ( ! empty( $discovered ) ) :
    ?>
    <h4 style="margin-top: 16px;"><?php esc_html_e( 'Discovered Policy Pages', 'rootz-ai-discovery' ); ?>
        <span class="rootz-viewer-count"><?php echo count( $discovered ); ?></span>
    </h4>
    <p class="rootz-viewer-explain" style="margin-top: 0;"><?php esc_html_e( 'These pages were auto-detected on your site and included in the policies manifest.', 'rootz-ai-discovery' ); ?></p>
    <table class="rootz-viewer-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Policy', 'rootz-ai-discovery' ); ?></th>
                <th><?php esc_html_e( 'Type', 'rootz-ai-discovery' ); ?></th>
                <th><?php esc_html_e( 'URL', 'rootz-ai-discovery' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $discovered as $dkey => $dval ) : ?>
            <tr>
                <td><strong><?php echo esc_html( ucfirst( str_replace( array( 'Policy', '_' ), array( '', ' ' ), $dkey ) ) ); ?></strong></td>
                <td><span class="rootz-viewer-tag"><?php echo esc_html( $dval['type'] ?? '—' ); ?></span></td>
                <td><code><?php echo esc_html( $dval['url'] ?? '—' ); ?></code></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php // ── SECTION 5: PAGES ────────────────────────────────────────────── ?>
<?php if ( ! empty( $ai_json['pages'] ) ) : ?>
<div class="rootz-viewer-card">
    <div class="rootz-viewer-card-header">
        <h3>
            <span class="rootz-viewer-icon">&#128196;</span>
            <?php esc_html_e( 'Site Pages', 'rootz-ai-discovery' ); ?>
            <span class="rootz-viewer-count"><?php echo count( $ai_json['pages'] ); ?></span>
        </h3>
    </div>
    <p class="rootz-viewer-explain"><?php esc_html_e( 'Your site map with content integrity hashes. AI agents use this to navigate your site and verify content hasn\'t been tampered with.', 'rootz-ai-discovery' ); ?></p>
    <table class="rootz-viewer-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Page', 'rootz-ai-discovery' ); ?></th>
                <th><?php esc_html_e( 'Path', 'rootz-ai-discovery' ); ?></th>
                <th><?php esc_html_e( 'Content Hash', 'rootz-ai-discovery' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $ai_json['pages'] as $page ) :
                $hash_short = ! empty( $page['contentHash'] ) ? substr( $page['contentHash'], 0, 15 ) . '...' : '—';
            ?>
            <tr>
                <td><?php echo esc_html( $page['title'] ); ?></td>
                <td><code><?php echo esc_html( $page['path'] ); ?></code></td>
                <td><code style="font-size: 11px;"><?php echo esc_html( $hash_short ); ?></code></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php // ── SECTION 6: KNOWLEDGE BASE ──────────────────────────────────── ?>
<?php if ( $knowledge_on ) :
    delete_transient( 'rootz_knowledge_cache' );
    $knowledge = $rest->generate_knowledge();
?>
<div class="rootz-viewer-card">
    <div class="rootz-viewer-card-header">
        <h3>
            <span class="rootz-viewer-icon">&#129504;</span>
            <?php esc_html_e( 'Knowledge Base', 'rootz-ai-discovery' ); ?>
        </h3>
        <span class="rootz-viewer-endpoint"><code>/.well-known/ai/knowledge</code></span>
    </div>
    <p class="rootz-viewer-explain"><?php esc_html_e( 'Detailed information AI agents use to answer deep questions about your organization. Auto-generated from your About page, product/service pages, and categories.', 'rootz-ai-discovery' ); ?></p>

    <?php if ( ! empty( $knowledge['about'] ) ) : ?>
    <h4><?php esc_html_e( 'About', 'rootz-ai-discovery' ); ?></h4>
    <div class="rootz-viewer-text-block">
        <strong><?php echo esc_html( $knowledge['about']['title'] ); ?></strong><br />
        <?php echo esc_html( $knowledge['about']['summary'] ); ?>
    </div>
    <?php endif; ?>

    <?php if ( ! empty( $knowledge['products'] ) ) : ?>
    <h4><?php esc_html_e( 'Products & Services', 'rootz-ai-discovery' ); ?> <span class="rootz-viewer-count"><?php echo count( $knowledge['products'] ); ?></span></h4>
    <table class="rootz-viewer-table">
        <tbody>
            <?php foreach ( $knowledge['products'] as $product ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php echo esc_html( $product['name'] ); ?></td>
                <td><?php echo esc_html( $product['description'] ); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php if ( ! empty( $knowledge['glossary'] ) ) : ?>
    <h4><?php esc_html_e( 'Glossary', 'rootz-ai-discovery' ); ?> <span class="rootz-viewer-count"><?php echo count( $knowledge['glossary'] ); ?></span></h4>
    <table class="rootz-viewer-table">
        <tbody>
            <?php foreach ( $knowledge['glossary'] as $entry ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php echo esc_html( $entry['term'] ); ?></td>
                <td><?php echo esc_html( $entry['definition'] ?? '—' ); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php // ── SECTION 7: CONTENT FEED ────────────────────────────────────── ?>
<?php if ( $feed_on ) :
    delete_transient( 'rootz_feed_cache' );
    $feed = $rest->generate_feed();
?>
<div class="rootz-viewer-card">
    <div class="rootz-viewer-card-header">
        <h3>
            <span class="rootz-viewer-icon">&#128240;</span>
            <?php esc_html_e( 'AI Feed', 'rootz-ai-discovery' ); ?>
            <span class="rootz-viewer-count"><?php echo count( $feed['items'] ?? array() ); ?> <?php esc_html_e( 'items', 'rootz-ai-discovery' ); ?></span>
        </h3>
        <span class="rootz-viewer-endpoint"><code>/.well-known/ai/feed</code></span>
    </div>
    <p class="rootz-viewer-explain"><?php esc_html_e( 'Recent content served to AI agents. When someone asks "What\'s new at [your company]?", this is the answer.', 'rootz-ai-discovery' ); ?></p>
    <table class="rootz-viewer-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Title', 'rootz-ai-discovery' ); ?></th>
                <th><?php esc_html_e( 'Date', 'rootz-ai-discovery' ); ?></th>
                <th><?php esc_html_e( 'Summary (shown to AI)', 'rootz-ai-discovery' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $feed_items = $feed['items'] ?? array();
            $shown = array_slice( $feed_items, 0, 10 );
            foreach ( $shown as $item ) :
                $date = ! empty( $item['published'] ) ? wp_date( 'M j, Y', strtotime( $item['published'] ) ) : '—';
            ?>
            <tr>
                <td><strong><?php echo esc_html( $item['title'] ); ?></strong></td>
                <td><?php echo esc_html( $date ); ?></td>
                <td class="rootz-viewer-summary"><?php echo esc_html( wp_trim_words( $item['summary'] ?? '', 25 ) ); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ( count( $feed_items ) > 10 ) : ?>
    <p class="rootz-viewer-more">+ <?php echo esc_html( count( $feed_items ) - 10 ); ?> <?php esc_html_e( 'more items', 'rootz-ai-discovery' ); ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php // ── SECTION 8: STRUCTURED CONTENT ──────────────────────────────── ?>
<?php if ( $content_on ) :
    $content_ep = new Rootz_Content_Endpoint();
    delete_transient( 'rootz_content_cache' );
    $content_data = $content_ep->generate_all();
?>
<div class="rootz-viewer-card">
    <div class="rootz-viewer-card-header">
        <h3>
            <span class="rootz-viewer-icon">&#128451;</span>
            <?php esc_html_e( 'Structured Content', 'rootz-ai-discovery' ); ?>
        </h3>
        <span class="rootz-viewer-endpoint"><code>/.well-known/ai/content</code></span>
    </div>
    <p class="rootz-viewer-explain"><?php esc_html_e( 'Full structured content — replaces scraping. AI agents get clean, verified data instead of parsing your HTML.', 'rootz-ai-discovery' ); ?></p>

    <?php foreach ( $content_data['content'] as $type_key => $items ) :
        $type_label = ucfirst( $type_key );
        $item_count = is_array( $items ) ? count( $items ) : 0;
        if ( $item_count === 0 ) continue;

        if ( $type_key === 'custom' ) :
            foreach ( $items as $custom_group ) :
                $custom_label = $custom_group['label'] ?? ucfirst( $custom_group['type'] ?? 'Custom' );
                $custom_items = $custom_group['items'] ?? array();
            ?>
            <h4><?php echo esc_html( $custom_label ); ?> <span class="rootz-viewer-count"><?php echo count( $custom_items ); ?></span></h4>
            <table class="rootz-viewer-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Title', 'rootz-ai-discovery' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'rootz-ai-discovery' ); ?></th>
                        <th><?php esc_html_e( 'Excerpt', 'rootz-ai-discovery' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( array_slice( $custom_items, 0, 10 ) as $ci ) : ?>
                    <tr>
                        <td><strong><?php echo esc_html( $ci['title'] ); ?></strong></td>
                        <td><span class="rootz-viewer-tag"><?php echo esc_html( $ci['assertionType'] ?? '—' ); ?></span></td>
                        <td class="rootz-viewer-summary"><?php echo esc_html( wp_trim_words( $ci['excerpt'] ?? '', 20 ) ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endforeach;
        else : ?>
        <h4><?php echo esc_html( $type_label ); ?> <span class="rootz-viewer-count"><?php echo intval( $item_count ); ?></span></h4>
        <table class="rootz-viewer-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Title', 'rootz-ai-discovery' ); ?></th>
                    <th><?php esc_html_e( 'Type', 'rootz-ai-discovery' ); ?></th>
                    <th><?php esc_html_e( 'Excerpt (shown to AI)', 'rootz-ai-discovery' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( array_slice( $items, 0, 10 ) as $item ) : ?>
                <tr>
                    <td><strong><?php echo esc_html( $item['title'] ?? '' ); ?></strong></td>
                    <td><span class="rootz-viewer-tag"><?php echo esc_html( $item['assertionType'] ?? '—' ); ?></span></td>
                    <td class="rootz-viewer-summary"><?php echo esc_html( wp_trim_words( $item['excerpt'] ?? '', 20 ) ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ( $item_count > 10 ) : ?>
        <p class="rootz-viewer-more">+ <?php echo esc_html( $item_count - 10 ); ?> <?php esc_html_e( 'more', 'rootz-ai-discovery' ); ?></p>
        <?php endif; ?>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php // ── SECTION 9: DIGITAL IDENTITY ────────────────────────────────── ?>
<?php if ( ! empty( $ai_json['_signature'] ) ) :
    $sig = $ai_json['_signature'];
    $sig_method = $sig['method'] ?? 'unknown';
    $is_ecdsa   = 'ecdsa-secp256k1' === $sig_method;
?>
<div class="rootz-viewer-card">
    <div class="rootz-viewer-card-header">
        <h3>
            <span class="rootz-viewer-icon">&#128274;</span>
            <?php esc_html_e( 'Digital Identity & Signature', 'rootz-ai-discovery' ); ?>
        </h3>
    </div>
    <p class="rootz-viewer-explain"><?php esc_html_e( 'Cryptographic proof that this data comes from you. AI agents can verify this on-chain.', 'rootz-ai-discovery' ); ?></p>
    <table class="rootz-viewer-table">
        <tbody>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Method', 'rootz-ai-discovery' ); ?></td>
                <td>
                    <span class="rootz-viewer-tag"><?php echo esc_html( $sig_method ); ?></span>
                    <?php if ( ! empty( $sig['approvedBy'] ) ) : ?>
                        <span class="rootz-viewer-tag rootz-tag-green"><?php echo esc_html( 'approved by ' . $sig['approvedBy'] ); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ( $is_ecdsa && ! empty( $sig['digitalName'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Digital Name', 'rootz-ai-discovery' ); ?></td>
                <td><code><?php echo esc_html( $sig['digitalName'] ); ?></code></td>
            </tr>
            <?php endif; ?>
            <?php if ( $is_ecdsa && ! empty( $sig['signer'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Signer', 'rootz-ai-discovery' ); ?></td>
                <td><code style="word-break: break-all;"><?php echo esc_html( $sig['signer'] ); ?></code></td>
            </tr>
            <?php endif; ?>
            <?php if ( $is_ecdsa && ! empty( $sig['network'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Network', 'rootz-ai-discovery' ); ?></td>
                <td><?php echo esc_html( $sig['network'] ); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ( ! empty( $sig['contentHash'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Content Hash', 'rootz-ai-discovery' ); ?></td>
                <td><code style="word-break: break-all; font-size: 12px;"><?php echo esc_html( $sig['contentHash'] ); ?></code></td>
            </tr>
            <?php endif; ?>
            <?php if ( ! empty( $sig['signedAt'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Signed At', 'rootz-ai-discovery' ); ?></td>
                <td><?php echo esc_html( wp_date( 'M j, Y g:i A', strtotime( $sig['signedAt'] ) ) ); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ( ! empty( $sig['authorization'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Authorization', 'rootz-ai-discovery' ); ?></td>
                <td><?php echo esc_html( $sig['authorization'] ); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ( ! empty( $sig['note'] ) ) : ?>
            <tr>
                <td class="rootz-viewer-label"><?php esc_html_e( 'Note', 'rootz-ai-discovery' ); ?></td>
                <td><em><?php echo esc_html( $sig['note'] ); ?></em></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php // ── SECTION 10: RAW JSON ────────────────────────────────────────── ?>
<div class="rootz-viewer-card rootz-viewer-raw">
    <div class="rootz-viewer-card-header">
        <h3>
            <span class="rootz-viewer-icon">{ }</span>
            <?php esc_html_e( 'Raw JSON (what the AI actually receives)', 'rootz-ai-discovery' ); ?>
        </h3>
    </div>
    <p class="rootz-viewer-explain"><?php esc_html_e( 'This is the exact JSON response from /.well-known/ai. Click to expand.', 'rootz-ai-discovery' ); ?></p>
    <details>
        <summary><?php esc_html_e( 'Show raw ai.json', 'rootz-ai-discovery' ); ?></summary>
        <pre class="rootz-viewer-json"><?php echo esc_html( wp_json_encode( $ai_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
    </details>
</div>
