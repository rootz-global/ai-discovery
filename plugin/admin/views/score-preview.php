<?php
/**
 * Score Preview — local self-test matching the rootz.global scanner.
 *
 * Each check explains WHY it matters for AI, efficiency, trust, and the web.
 * This is a marketing and educational document as much as a diagnostic tool.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Run local checks ─────────────────────────────────────────────────────────
$checks = array();
$score  = 0;
$max    = 120;

// 1. Discovery endpoint.
$checks['wellKnownAi'] = array(
    'pass'  => true,
    'label' => '/.well-known/ai Endpoint',
    'detail' => 'v' . ROOTZ_AI_DISCOVERY_SPEC . ' — active',
    'why'    => 'The discovery endpoint lets AI read structured data about your site in one request instead of scraping dozens of pages. This saves compute cycles and reduces the energy cost of every AI query about your organization.',
    'learn'  => 'https://rootz.global/standard',
);
$score += 10;

// 2. Organization completeness.
$org_name    = get_option( 'rootz_organization_name', get_bloginfo( 'name' ) );
$org_mission = get_option( 'rootz_organization_tagline', '' );
$org_sector  = get_option( 'rootz_sector', '' );
if ( empty( $org_sector ) ) {
    $org_sector = get_option( 'rootz_organization_sector', '' );
}
$org_domain  = wp_parse_url( home_url(), PHP_URL_HOST );
$org_complete = ! empty( $org_name ) && ! empty( $org_domain ) && ! empty( $org_mission ) && ! empty( $org_sector );
if ( $org_complete ) {
    $checks['organization'] = array(
        'pass'  => true,
        'label' => 'Organization Data',
        'detail' => 'Name, domain, mission, sector — complete',
        'why'    => 'Complete organization data means AI can accurately describe who you are without guessing. When someone asks "What does this company do?", AI answers from your data — not from outdated web scrapes or hallucinations.',
        'learn'  => 'https://rootz.global/standard#organization',
    );
    $score += 15;
} else {
    $missing = array();
    if ( empty( $org_name ) )    $missing[] = 'name';
    if ( empty( $org_mission ) ) $missing[] = 'mission';
    if ( empty( $org_sector ) )  $missing[] = 'sector';
    $checks['organization'] = array(
        'pass'  => false,
        'label' => 'Organization Data',
        'detail' => 'Missing: ' . implode( ', ', $missing ),
        'why'    => 'Without complete organization data, AI must scrape your site and guess your identity. This leads to inaccurate responses and wasted processing. Fill in your mission and sector so AI gets it right the first time.',
        'link'   => 'identity',
    );
}

// 3. Core concepts.
$concepts = get_option( 'rootz_core_concepts', '' );
$cats_with_desc = get_categories( array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 1, 'exclude' => array( 1 ) ) );
$has_concepts = ! empty( $concepts ) || ( ! empty( $cats_with_desc ) && ! empty( $cats_with_desc[0]->description ) );
if ( $has_concepts ) {
    $checks['coreConcepts'] = array(
        'pass'  => true,
        'label' => 'Core Concepts',
        'detail' => 'Glossary terms defined',
        'why'    => 'Your glossary teaches AI your domain language. When a user asks about your product or industry, AI uses your definitions — not Wikipedia\'s. This is your chance to control how AI talks about your field.',
    );
    $score += 10;
} else {
    $checks['coreConcepts'] = array(
        'pass'  => false,
        'label' => 'Core Concepts',
        'detail' => 'Add glossary terms on the Identity tab, or add descriptions to your categories',
        'why'    => 'Without core concepts, AI has no authoritative definitions for your domain terms. It will use generic descriptions or make assumptions. Define your key terms and AI will use your exact language.',
        'link'   => 'identity',
    );
}

// 4. Contact email.
$contact_email = get_option( 'rootz_contact_email', '' );
if ( empty( $contact_email ) ) {
    $contact_email = get_option( 'admin_email', '' );
}
if ( ! empty( $contact_email ) ) {
    $checks['contact'] = array(
        'pass'  => true,
        'label' => 'Contact Email',
        'detail' => $contact_email,
        'why'    => 'A contact email lets AI direct users to reach you. When someone asks "How do I contact this company?", AI provides your preferred channel instead of guessing.',
    );
    $score += 5;
} else {
    $checks['contact'] = array(
        'pass'  => false,
        'label' => 'Contact Email',
        'detail' => 'Add a contact email on the Identity tab',
        'why'    => 'Without a contact email, AI cannot help users reach you. This is a missed opportunity every time someone asks an AI assistant how to get in touch.',
        'link'   => 'identity',
    );
}

// 5. Content hash.
$signed_manifest = get_option( 'rootz_signed_manifest', false );
$has_hash = ! empty( $signed_manifest ) && is_array( $signed_manifest ) && ! empty( $signed_manifest['_signature']['contentHash'] );
if ( $has_hash ) {
    $checks['contentHash'] = array(
        'pass'  => true,
        'label' => 'Content Hash',
        'detail' => 'SHA-256 integrity hash included',
        'why'    => 'Content hashes let AI verify your data hasn\'t been tampered with in transit. This is a fundamental building block of trust — AI can confirm it\'s reading exactly what you published, not a modified copy.',
        'learn'  => 'https://rootz.global/standard#integrity',
    );
    $score += 10;
}

// 6. AI Summary.
$ai_summary = get_option( 'rootz_ai_summary', '' );
if ( ! empty( $ai_summary ) ) {
    $checks['aiSummary'] = array(
        'pass'  => true,
        'label' => 'AI Summary',
        'detail' => wp_trim_words( $ai_summary, 10 ),
        'why'    => 'The AI Summary is the single most important field. It\'s the first thing AI reads and the primary source for answering "What is this site about?" A well-written summary shapes every AI response about your organization.',
    );
    $score += 10;
} else {
    $checks['aiSummary'] = array(
        'pass'  => false,
        'label' => 'AI Summary',
        'detail' => 'Write a summary on the Identity tab — it\'s the first thing AI reads',
        'why'    => 'Without an AI Summary, AI must scrape your homepage and infer what you do. This takes more processing power and often produces vague or incorrect descriptions. Write one clear paragraph and AI gets it right every time.',
        'link'   => 'identity',
    );
}

// 7. Knowledge endpoint.
$knowledge_on = '1' === get_option( 'rootz_enable_knowledge', '1' );
if ( $knowledge_on ) {
    $checks['knowledge'] = array(
        'pass'  => true,
        'label' => 'Knowledge Endpoint',
        'detail' => '/.well-known/ai/knowledge',
        'why'    => 'The knowledge endpoint provides deep, structured information about your products, services, and expertise. AI uses this for detailed questions beyond the basic "What do you do?" — like product comparisons, pricing, and technical capabilities.',
        'learn'  => 'https://rootz.global/standard#knowledge',
    );
    $score += 10;
} else {
    $checks['knowledge'] = array(
        'pass'  => false,
        'hint'  => true,
        'label' => 'Knowledge Endpoint',
        'detail' => 'Enable on the Content tab for deeper AI understanding',
        'why'    => 'Without a knowledge endpoint, AI only has your summary and basic identity. Enabling it gives AI a structured encyclopedia of your organization — products, services, glossary — so it can answer detailed questions accurately.',
        'link'   => 'content',
    );
}

// 8. Feed endpoint.
$feed_on = '1' === get_option( 'rootz_enable_feed', '1' );
if ( $feed_on ) {
    $checks['feed'] = array(
        'pass'  => true,
        'label' => 'Feed Endpoint',
        'detail' => '/.well-known/ai/feed',
        'why'    => 'The AI feed keeps AI up to date with your latest content. When someone asks "What\'s new at your company?", AI answers with real information instead of stale data from its training set.',
        'learn'  => 'https://rootz.global/standard#feed',
    );
    $score += 5;
} else {
    $checks['feed'] = array(
        'pass'  => false,
        'hint'  => true,
        'label' => 'Feed Endpoint',
        'detail' => 'Enable on the Content tab to share recent posts',
        'why'    => 'Without a feed, AI has no way to know what\'s new. It will answer with outdated information from months or years ago. A feed ensures AI always has your latest news, blog posts, and updates.',
        'link'   => 'content',
    );
}

// 9. Three-tier bonus.
if ( $knowledge_on && $feed_on ) {
    $score += 5;
}

// 10. Content endpoint.
$content_on = '1' === get_option( 'rootz_enable_content', '0' );
if ( $content_on ) {
    $checks['content'] = array(
        'pass'  => true,
        'label' => 'Content Endpoint',
        'detail' => '/.well-known/ai/content',
        'why'    => 'The content endpoint eliminates HTML scraping entirely. AI gets clean, structured data instead of parsing your theme\'s HTML. This is dramatically more efficient — one API call replaces hundreds of page fetches, reducing both your bandwidth costs and AI processing energy.',
        'learn'  => 'https://rootz.global/standard#content',
    );
    $score += 15;
} else {
    $checks['content'] = array(
        'pass'  => false,
        'hint'  => true,
        'label' => 'Content Endpoint',
        'detail' => 'Enable on the Content tab — eliminates AI scraping',
        'why'    => 'Without a content endpoint, AI must scrape every page of your site to read your content. This wastes bandwidth, processing power, and energy. The content endpoint serves everything in one clean response — better for you, better for AI, better for the planet.',
        'link'   => 'content',
    );
}

// 11. Plugin wallet / digital name.
$signing_address = Rootz_Signer::stored_address();
if ( ! empty( $signing_address ) ) {
    $checks['digitalName'] = array(
        'pass'  => true,
        'label' => 'Digital Name',
        'detail' => substr( $signing_address, 0, 12 ) . '...',
        'why'    => 'Your Digital Name is a persistent cryptographic identity for your site. Unlike domain names that can be transferred or spoofed, a blockchain address proves continuity — AI can verify it\'s been talking to the same entity over time.',
        'learn'  => 'https://rootz.global/standard#verification',
    );
    $score += 5;
} else {
    $checks['digitalName'] = array(
        'pass'  => false,
        'hint'  => true,
        'label' => 'Digital Name',
        'detail' => 'Generate a plugin wallet on the Account tab',
        'why'    => 'Without a Digital Name, your site has no persistent cryptographic identity. AI can\'t distinguish your site from an impersonator. A wallet address proves ownership and builds trust over time.',
        'link'   => 'account',
    );
}

// 12. Signature.
$has_signature = ! empty( $signed_manifest ) && is_array( $signed_manifest )
    && ! empty( $signed_manifest['_signature']['method'] )
    && 'hash-only' !== $signed_manifest['_signature']['method'];
if ( $has_signature ) {
    $checks['signature'] = array(
        'pass'  => true,
        'label' => 'Cryptographic Signature',
        'detail' => 'ECDSA secp256k1',
        'why'    => 'Signed content is verifiably authentic. AI can mathematically confirm that this data was published by the holder of your private key and hasn\'t been modified. In an era of deepfakes and misinformation, signed content is how AI knows it can trust what it reads.',
        'learn'  => 'https://rootz.global/standard#signing',
    );
    $score += 3;
} else {
    $checks['signature'] = array(
        'pass'  => false,
        'hint'  => true,
        'label' => 'Cryptographic Signature',
        'detail' => 'Generate a wallet and sign your manifest',
        'why'    => 'Unsigned content can be intercepted and modified. With a cryptographic signature, AI can prove your data is authentic. This is especially important as AI-generated misinformation grows — signed content stands out as verified and trustworthy.',
        'link'   => 'account',
    );
}

// 13. SEO tags.
$seo_on = '1' === get_option( 'rootz_enable_seo_tags', '1' );
$seo_plugins = array( 'wordpress-seo/wp-seo.php', 'seo-by-rank-math/rank-math.php', 'all-in-one-seo-pack/all_in_one_seo_pack.php', 'wp-seopress/seopress.php' );
$has_seo = $seo_on;
foreach ( $seo_plugins as $p ) {
    if ( is_plugin_active( $p ) ) {
        $has_seo = true;
        break;
    }
}
if ( $has_seo ) {
    $checks['seoMeta'] = array(
        'pass'  => true,
        'label' => 'SEO Meta Tags',
        'detail' => $seo_on ? 'Plugin provides meta description, OG, JSON-LD' : 'SEO plugin detected',
        'why'    => 'Meta tags, OpenGraph, and JSON-LD structured data help AI understand your pages before reading the full content. This pre-processing layer means faster, more accurate AI responses with less compute overhead.',
    );
    $score += 2;
} else {
    $checks['seoMeta'] = array(
        'pass'  => false,
        'hint'  => true,
        'label' => 'SEO Meta Tags',
        'detail' => 'Enable SEO tags on the Account tab or install an SEO plugin',
        'why'    => 'SEO meta tags aren\'t just for Google — AI uses them too. A meta description gives AI a quick summary of each page, OpenGraph tags provide social context, and JSON-LD schema gives structured data AI can parse instantly.',
        'link'   => 'account',
    );
}

// 14. HTTPS.
$is_https = wp_parse_url( home_url(), PHP_URL_SCHEME ) === 'https';
if ( $is_https ) {
    $checks['https'] = array(
        'pass'  => true,
        'label' => 'HTTPS',
        'detail' => 'Secure connection',
        'why'    => 'HTTPS ensures data integrity between your server and AI. Without it, content could be modified in transit, undermining the trust chain that signed content establishes.',
    );
}

// ── Calculate grade ──────────────────────────────────────────────────────────
$pct = ( $max > 0 ) ? ( $score / $max ) * 100 : 0;
if ( $pct >= 80 ) {
    $grade = 'A';
    $grade_color = '#16a34a';
} elseif ( $pct >= 55 ) {
    $grade = 'B';
    $grade_color = '#65a30d';
} elseif ( $pct >= 35 ) {
    $grade = 'C';
    $grade_color = '#ca8a04';
} elseif ( $pct >= 15 ) {
    $grade = 'D';
    $grade_color = '#ea580c';
} else {
    $grade = 'F';
    $grade_color = '#dc2626';
}
?>

<div class="rootz-viewer-card" style="border-left: 4px solid <?php echo esc_attr( $grade_color ); ?>; background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);">
    <div class="rootz-viewer-card-header">
        <h3>
            <span class="rootz-viewer-icon">&#127942;</span>
            <?php esc_html_e( 'AI Readiness Score', 'rootz-ai-discovery' ); ?>
        </h3>
    </div>
    <p class="rootz-viewer-explain">
        <?php esc_html_e( 'How well-prepared is your site for the AI era? Each measure below reduces AI processing costs, improves accuracy, and builds trust. A higher score means AI can serve your visitors better — with less energy and more confidence.', 'rootz-ai-discovery' ); ?>
    </p>

    <div style="display: flex; align-items: center; gap: 24px; margin: 16px 0;">
        <div style="display: flex; align-items: center; justify-content: center; width: 80px; height: 80px; border-radius: 12px; background: <?php echo esc_attr( $grade_color ); ?>; color: #fff; font-size: 2.5rem; font-weight: 800;">
            <?php echo esc_html( $grade ); ?>
        </div>
        <div>
            <div style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">
                <?php echo esc_html( $score ); ?> / <?php echo esc_html( $max ); ?>
            </div>
            <div style="font-size: 0.9rem; color: #64748b;">
                <?php echo esc_html( round( $pct ) ); ?>% &mdash; <?php esc_html_e( 'estimated from local settings', 'rootz-ai-discovery' ); ?>
            </div>
        </div>
    </div>

    <ul style="list-style: none; padding: 0; margin: 16px 0 0;">
        <?php foreach ( $checks as $check ) :
            $is_pass = ! empty( $check['pass'] );
            $is_hint = ! $is_pass && ! empty( $check['hint'] );
            if ( $is_pass ) {
                $icon  = '&#10003;';
                $bg    = '#16a34a';
            } elseif ( $is_hint ) {
                $icon  = '&#8505;';
                $bg    = '#2563eb';
            } else {
                $icon  = '&#10007;';
                $bg    = '#dc2626';
            }
        ?>
        <li style="display: flex; align-items: flex-start; gap: 10px; padding: 10px 0; border-bottom: 1px solid #e2e8f0;">
            <span style="flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: <?php echo esc_attr( $bg ); ?>; color: #fff; font-size: 12px; font-weight: bold; margin-top: 1px;">
                <?php echo wp_kses( $icon, array() ); ?>
            </span>
            <div style="flex: 1;">
                <strong><?php echo esc_html( $check['label'] ); ?></strong>
                <?php if ( ! empty( $check['detail'] ) ) : ?>
                    <span style="color: #64748b; font-size: 0.9rem;"> &mdash; <?php echo esc_html( $check['detail'] ); ?></span>
                <?php endif; ?>
                <?php if ( ! empty( $check['why'] ) ) : ?>
                    <div style="margin-top: 4px; color: #475569; font-size: 0.85rem; line-height: 1.5;">
                        <?php echo esc_html( $check['why'] ); ?>
                        <?php if ( ! empty( $check['learn'] ) ) : ?>
                            <a href="<?php echo esc_url( $check['learn'] ); ?>" target="_blank" rel="noopener" style="white-space: nowrap;"><?php esc_html_e( 'Learn more', 'rootz-ai-discovery' ); ?> &rarr;</a>
                        <?php endif; ?>
                        <?php if ( ! empty( $check['link'] ) ) : ?>
                            <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'rootz-ai-discovery', 'tab' => $check['link'] ), admin_url( 'options-general.php' ) ) ); ?>" style="white-space: nowrap;"><?php esc_html_e( 'Fix this', 'rootz-ai-discovery' ); ?> &rarr;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>

    <div style="margin-top: 20px; padding: 16px; background: #f0f6fc; border-radius: 8px; border: 1px solid #bfdbfe;">
        <strong style="color: #1e40af;"><?php esc_html_e( 'Why does this matter?', 'rootz-ai-discovery' ); ?></strong>
        <p style="margin: 8px 0 0; color: #1e3a5f; font-size: 0.9rem; line-height: 1.6;">
            <?php esc_html_e( 'Every time an AI answers a question about your organization, it either reads structured data you published (fast, accurate, low energy) or scrapes your website and guesses (slow, error-prone, high energy). A high AI Readiness score means your site serves AI efficiently — reducing compute costs across the entire AI ecosystem while ensuring accuracy. Signed, structured content is the foundation of a trustworthy AI-readable web.', 'rootz-ai-discovery' ); ?>
        </p>
    </div>

    <p style="margin-top: 16px;">
        <a href="https://rootz.global/ai-discovery" target="_blank" rel="noopener" class="button button-primary">
            <?php esc_html_e( 'Get Official Score at rootz.global', 'rootz-ai-discovery' ); ?>
        </a>
        <a href="https://rootz.global/standard" target="_blank" rel="noopener" class="button" style="margin-left: 8px;">
            <?php esc_html_e( 'Read the Standard', 'rootz-ai-discovery' ); ?>
        </a>
    </p>
</div>
