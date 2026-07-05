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
$rootz_checks = array();
$rootz_score  = 0;
$rootz_max    = 120;

// 1. Discovery endpoint.
$rootz_checks['wellKnownAi'] = array(
	'pass'   => true,
	'label'  => '/.well-known/ai Endpoint',
	'detail' => 'v' . ROOTZ_AI_DISCOVERY_SPEC . ' — active',
	'why'    => 'The discovery endpoint lets AI read structured data about your site in one request instead of scraping dozens of pages. This saves compute cycles and reduces the energy cost of every AI query about your organization.',
	'learn'  => 'https://rootz.global/standard',
);
$rootz_score                += 10;

// 2. Organization completeness.
$rootz_org_name    = get_option( 'rootz_organization_name', get_bloginfo( 'name' ) );
$rootz_org_mission = get_option( 'rootz_organization_tagline', '' );
$rootz_org_sector  = get_option( 'rootz_sector', '' );
if ( empty( $rootz_org_sector ) ) {
	$rootz_org_sector = get_option( 'rootz_organization_sector', '' );
}
$rootz_org_domain   = wp_parse_url( home_url(), PHP_URL_HOST );
$rootz_org_complete = ! empty( $rootz_org_name ) && ! empty( $rootz_org_domain ) && ! empty( $rootz_org_mission ) && ! empty( $rootz_org_sector );
if ( $rootz_org_complete ) {
	$rootz_checks['organization'] = array(
		'pass'   => true,
		'label'  => 'Organization Data',
		'detail' => 'Name, domain, mission, sector — complete',
		'why'    => 'Complete organization data means AI can accurately describe who you are without guessing. When someone asks "What does this company do?", AI answers from your data — not from outdated web scrapes or hallucinations.',
		'learn'  => 'https://rootz.global/standard#organization',
	);
	$rootz_score                 += 15;
} else {
	$rootz_missing = array();
	if ( empty( $rootz_org_name ) ) {
		$rootz_missing[] = 'name';
	}
	if ( empty( $rootz_org_mission ) ) {
		$rootz_missing[] = 'mission';
	}
	if ( empty( $rootz_org_sector ) ) {
		$rootz_missing[] = 'sector';
	}
	$rootz_checks['organization'] = array(
		'pass'   => false,
		'label'  => 'Organization Data',
		'detail' => 'Missing: ' . implode( ', ', $rootz_missing ),
		'why'    => 'Without complete organization data, AI must scrape your site and guess your identity. This leads to inaccurate responses and wasted processing. Fill in your mission and sector so AI gets it right the first time.',
		'link'   => 'identity',
	);
}

// 3. Core concepts.
$rootz_concepts       = get_option( 'rootz_core_concepts', '' );
$rootz_cats_with_desc = get_categories(
	array(
		'orderby' => 'count',
		'order'   => 'DESC',
		'number'  => 1,
		'exclude' => array( 1 ),
	)
);
$rootz_has_concepts   = ! empty( $rootz_concepts ) || ( ! empty( $rootz_cats_with_desc ) && ! empty( $rootz_cats_with_desc[0]->description ) );
if ( $rootz_has_concepts ) {
	$rootz_checks['coreConcepts'] = array(
		'pass'   => true,
		'label'  => 'Core Concepts',
		'detail' => 'Glossary terms defined',
		'why'    => 'Your glossary teaches AI your domain language. When a user asks about your product or industry, AI uses your definitions — not Wikipedia\'s. This is your chance to control how AI talks about your field.',
	);
	$rootz_score                 += 10;
} else {
	$rootz_checks['coreConcepts'] = array(
		'pass'   => false,
		'label'  => 'Core Concepts',
		'detail' => 'Add glossary terms on the Identity tab, or add descriptions to your categories',
		'why'    => 'Without core concepts, AI has no authoritative definitions for your domain terms. It will use generic descriptions or make assumptions. Define your key terms and AI will use your exact language.',
		'link'   => 'identity',
	);
}

// 4. Contact email.
$rootz_contact_email = get_option( 'rootz_contact_email', '' );
if ( empty( $rootz_contact_email ) ) {
	$rootz_contact_email = get_option( 'admin_email', '' );
}
if ( ! empty( $rootz_contact_email ) ) {
	$rootz_checks['contact'] = array(
		'pass'   => true,
		'label'  => 'Contact Email',
		'detail' => $rootz_contact_email,
		'why'    => 'A contact email lets AI direct users to reach you. When someone asks "How do I contact this company?", AI provides your preferred channel instead of guessing.',
	);
	$rootz_score            += 5;
} else {
	$rootz_checks['contact'] = array(
		'pass'   => false,
		'label'  => 'Contact Email',
		'detail' => 'Add a contact email on the Identity tab',
		'why'    => 'Without a contact email, AI cannot help users reach you. This is a missed opportunity every time someone asks an AI assistant how to get in touch.',
		'link'   => 'identity',
	);
}

// 5. Content hash.
$rootz_signed_manifest = get_option( 'rootz_signed_manifest', false );
$rootz_has_hash        = ! empty( $rootz_signed_manifest ) && is_array( $rootz_signed_manifest ) && ! empty( $rootz_signed_manifest['_signature']['contentHash'] );
if ( $rootz_has_hash ) {
	$rootz_checks['contentHash'] = array(
		'pass'   => true,
		'label'  => 'Content Hash',
		'detail' => 'SHA-256 integrity hash included',
		'why'    => 'Content hashes let AI verify your data hasn\'t been tampered with in transit. This is a fundamental building block of trust — AI can confirm it\'s reading exactly what you published, not a modified copy.',
		'learn'  => 'https://rootz.global/standard#integrity',
	);
	$rootz_score                += 10;
}

// 6. AI Summary.
$rootz_ai_summary = get_option( 'rootz_ai_summary', '' );
if ( ! empty( $rootz_ai_summary ) ) {
	$rootz_checks['aiSummary'] = array(
		'pass'   => true,
		'label'  => 'AI Summary',
		'detail' => wp_trim_words( $rootz_ai_summary, 10 ),
		'why'    => 'The AI Summary is the single most important field. It\'s the first thing AI reads and the primary source for answering "What is this site about?" A well-written summary shapes every AI response about your organization.',
	);
	$rootz_score              += 10;
} else {
	$rootz_checks['aiSummary'] = array(
		'pass'   => false,
		'label'  => 'AI Summary',
		'detail' => 'Write a summary on the Identity tab — it\'s the first thing AI reads',
		'why'    => 'Without an AI Summary, AI must scrape your homepage and infer what you do. This takes more processing power and often produces vague or incorrect descriptions. Write one clear paragraph and AI gets it right every time.',
		'link'   => 'identity',
	);
}

// 7. Knowledge endpoint.
$rootz_knowledge_on = '1' === get_option( 'rootz_enable_knowledge', '1' );
if ( $rootz_knowledge_on ) {
	$rootz_checks['knowledge'] = array(
		'pass'   => true,
		'label'  => 'Knowledge Endpoint',
		'detail' => '/.well-known/ai/knowledge',
		'why'    => 'The knowledge endpoint provides deep, structured information about your products, services, and expertise. AI uses this for detailed questions beyond the basic "What do you do?" — like product comparisons, pricing, and technical capabilities.',
		'learn'  => 'https://rootz.global/standard#knowledge',
	);
	$rootz_score              += 10;
} else {
	$rootz_checks['knowledge'] = array(
		'pass'   => false,
		'hint'   => true,
		'label'  => 'Knowledge Endpoint',
		'detail' => 'Enable on the Content tab for deeper AI understanding',
		'why'    => 'Without a knowledge endpoint, AI only has your summary and basic identity. Enabling it gives AI a structured encyclopedia of your organization — products, services, glossary — so it can answer detailed questions accurately.',
		'link'   => 'content',
	);
}

// 8. Feed endpoint.
$rootz_feed_on = '1' === get_option( 'rootz_enable_feed', '1' );
if ( $rootz_feed_on ) {
	$rootz_checks['feed'] = array(
		'pass'   => true,
		'label'  => 'Feed Endpoint',
		'detail' => '/.well-known/ai/feed',
		'why'    => 'The AI feed keeps AI up to date with your latest content. When someone asks "What\'s new at your company?", AI answers with real information instead of stale data from its training set.',
		'learn'  => 'https://rootz.global/standard#feed',
	);
	$rootz_score         += 5;
} else {
	$rootz_checks['feed'] = array(
		'pass'   => false,
		'hint'   => true,
		'label'  => 'Feed Endpoint',
		'detail' => 'Enable on the Content tab to share recent posts',
		'why'    => 'Without a feed, AI has no way to know what\'s new. It will answer with outdated information from months or years ago. A feed ensures AI always has your latest news, blog posts, and updates.',
		'link'   => 'content',
	);
}

// 9. Three-tier bonus.
if ( $rootz_knowledge_on && $rootz_feed_on ) {
	$rootz_score += 5;
}

// 10. Content endpoint.
$rootz_content_on = '1' === get_option( 'rootz_enable_content', '0' );
if ( $rootz_content_on ) {
	$rootz_checks['content'] = array(
		'pass'   => true,
		'label'  => 'Content Endpoint',
		'detail' => '/.well-known/ai/content',
		'why'    => 'The content endpoint eliminates HTML scraping entirely. AI gets clean, structured data instead of parsing your theme\'s HTML. This is dramatically more efficient — one API call replaces hundreds of page fetches, reducing both your bandwidth costs and AI processing energy.',
		'learn'  => 'https://rootz.global/standard#content',
	);
	$rootz_score            += 15;
} else {
	$rootz_checks['content'] = array(
		'pass'   => false,
		'hint'   => true,
		'label'  => 'Content Endpoint',
		'detail' => 'Enable on the Content tab — eliminates AI scraping',
		'why'    => 'Without a content endpoint, AI must scrape every page of your site to read your content. This wastes bandwidth, processing power, and energy. The content endpoint serves everything in one clean response — better for you, better for AI, better for the planet.',
		'link'   => 'content',
	);
}

// 11. Plugin wallet / digital name.
$rootz_signing_address = Rootz_Signer::stored_address();
if ( ! empty( $rootz_signing_address ) ) {
	$rootz_checks['digitalName'] = array(
		'pass'   => true,
		'label'  => 'Digital Name',
		'detail' => substr( $rootz_signing_address, 0, 12 ) . '...',
		'why'    => 'Your Digital Name is a persistent cryptographic identity for your site. Unlike domain names that can be transferred or spoofed, a blockchain address proves continuity — AI can verify it\'s been talking to the same entity over time.',
		'learn'  => 'https://rootz.global/standard#verification',
	);
	$rootz_score                += 5;
} else {
	$rootz_checks['digitalName'] = array(
		'pass'   => false,
		'hint'   => true,
		'label'  => 'Digital Name',
		'detail' => 'Generate a plugin wallet on the Account tab',
		'why'    => 'Without a Digital Name, your site has no persistent cryptographic identity. AI can\'t distinguish your site from an impersonator. A wallet address proves ownership and builds trust over time.',
		'link'   => 'account',
	);
}

// 12. Signature.
$rootz_has_signature = ! empty( $rootz_signed_manifest ) && is_array( $rootz_signed_manifest )
	&& ! empty( $rootz_signed_manifest['_signature']['method'] )
	&& 'hash-only' !== $rootz_signed_manifest['_signature']['method'];
if ( $rootz_has_signature ) {
	$rootz_checks['signature'] = array(
		'pass'   => true,
		'label'  => 'Cryptographic Signature',
		'detail' => 'ECDSA secp256k1',
		'why'    => 'Signed content is verifiably authentic. AI can mathematically confirm that this data was published by the holder of your private key and hasn\'t been modified. In an era of deepfakes and misinformation, signed content is how AI knows it can trust what it reads.',
		'learn'  => 'https://rootz.global/standard#signing',
	);
	$rootz_score              += 3;
} else {
	$rootz_checks['signature'] = array(
		'pass'   => false,
		'hint'   => true,
		'label'  => 'Cryptographic Signature',
		'detail' => 'Generate a wallet and sign your manifest',
		'why'    => 'Unsigned content can be intercepted and modified. With a cryptographic signature, AI can prove your data is authentic. This is especially important as AI-generated misinformation grows — signed content stands out as verified and trustworthy.',
		'link'   => 'account',
	);
}

// 13. SEO tags.
$rootz_seo_on      = '1' === get_option( 'rootz_enable_seo_tags', '1' );
$rootz_seo_plugins = array( 'wordpress-seo/wp-seo.php', 'seo-by-rank-math/rank-math.php', 'all-in-one-seo-pack/all_in_one_seo_pack.php', 'wp-seopress/seopress.php' );
$rootz_has_seo     = $rootz_seo_on;
foreach ( $rootz_seo_plugins as $rootz_p ) {
	if ( is_plugin_active( $rootz_p ) ) {
		$rootz_has_seo = true;
		break;
	}
}
if ( $rootz_has_seo ) {
	$rootz_checks['seoMeta'] = array(
		'pass'   => true,
		'label'  => 'SEO Meta Tags',
		'detail' => $rootz_seo_on ? 'Plugin provides meta description, OG, JSON-LD' : 'SEO plugin detected',
		'why'    => 'Meta tags, OpenGraph, and JSON-LD structured data help AI understand your pages before reading the full content. This pre-processing layer means faster, more accurate AI responses with less compute overhead.',
	);
	$rootz_score            += 2;
} else {
	$rootz_checks['seoMeta'] = array(
		'pass'   => false,
		'hint'   => true,
		'label'  => 'SEO Meta Tags',
		'detail' => 'Enable SEO tags on the Account tab or install an SEO plugin',
		'why'    => 'SEO meta tags aren\'t just for Google — AI uses them too. A meta description gives AI a quick summary of each page, OpenGraph tags provide social context, and JSON-LD schema gives structured data AI can parse instantly.',
		'link'   => 'account',
	);
}

// 14. HTTPS.
$rootz_is_https = wp_parse_url( home_url(), PHP_URL_SCHEME ) === 'https';
if ( $rootz_is_https ) {
	$rootz_checks['https'] = array(
		'pass'   => true,
		'label'  => 'HTTPS',
		'detail' => 'Secure connection',
		'why'    => 'HTTPS ensures data integrity between your server and AI. Without it, content could be modified in transit, undermining the trust chain that signed content establishes.',
	);
}

// ── Calculate grade ──────────────────────────────────────────────────────────
$rootz_pct = ( $rootz_max > 0 ) ? ( $rootz_score / $rootz_max ) * 100 : 0;
if ( $rootz_pct >= 80 ) {
	$rootz_grade       = 'A';
	$rootz_grade_color = '#16a34a';
} elseif ( $rootz_pct >= 55 ) {
	$rootz_grade       = 'B';
	$rootz_grade_color = '#65a30d';
} elseif ( $rootz_pct >= 35 ) {
	$rootz_grade       = 'C';
	$rootz_grade_color = '#ca8a04';
} elseif ( $rootz_pct >= 15 ) {
	$rootz_grade       = 'D';
	$rootz_grade_color = '#ea580c';
} else {
	$rootz_grade       = 'F';
	$rootz_grade_color = '#dc2626';
}
?>

<div class="rootz-viewer-card" style="border-left: 4px solid <?php echo esc_attr( $rootz_grade_color ); ?>; background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);">
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
		<div style="display: flex; align-items: center; justify-content: center; width: 80px; height: 80px; border-radius: 12px; background: <?php echo esc_attr( $rootz_grade_color ); ?>; color: #fff; font-size: 2.5rem; font-weight: 800;">
			<?php echo esc_html( $rootz_grade ); ?>
		</div>
		<div>
			<div style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">
				<?php echo esc_html( $rootz_score ); ?> / <?php echo esc_html( $rootz_max ); ?>
			</div>
			<div style="font-size: 0.9rem; color: #64748b;">
				<?php echo esc_html( round( $rootz_pct ) ); ?>% &mdash; <?php esc_html_e( 'estimated from local settings', 'rootz-ai-discovery' ); ?>
			</div>
		</div>
	</div>

	<ul style="list-style: none; padding: 0; margin: 16px 0 0;">
		<?php
		foreach ( $rootz_checks as $rootz_check ) :
			$rootz_is_pass = ! empty( $rootz_check['pass'] );
			$rootz_is_hint = ! $rootz_is_pass && ! empty( $rootz_check['hint'] );
			if ( $rootz_is_pass ) {
				$rootz_icon = '&#10003;';
				$rootz_bg   = '#16a34a';
			} elseif ( $rootz_is_hint ) {
				$rootz_icon = '&#8505;';
				$rootz_bg   = '#2563eb';
			} else {
				$rootz_icon = '&#10007;';
				$rootz_bg   = '#dc2626';
			}
			?>
		<li style="display: flex; align-items: flex-start; gap: 10px; padding: 10px 0; border-bottom: 1px solid #e2e8f0;">
			<span style="flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: <?php echo esc_attr( $rootz_bg ); ?>; color: #fff; font-size: 12px; font-weight: bold; margin-top: 1px;">
				<?php echo wp_kses( $rootz_icon, array() ); ?>
			</span>
			<div style="flex: 1;">
				<strong><?php echo esc_html( $rootz_check['label'] ); ?></strong>
				<?php if ( ! empty( $rootz_check['detail'] ) ) : ?>
					<span style="color: #64748b; font-size: 0.9rem;"> &mdash; <?php echo esc_html( $rootz_check['detail'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $rootz_check['why'] ) ) : ?>
					<div style="margin-top: 4px; color: #475569; font-size: 0.85rem; line-height: 1.5;">
						<?php echo esc_html( $rootz_check['why'] ); ?>
						<?php if ( ! empty( $rootz_check['learn'] ) ) : ?>
							<a href="<?php echo esc_url( $rootz_check['learn'] ); ?>" target="_blank" rel="noopener" style="white-space: nowrap;"><?php esc_html_e( 'Learn more', 'rootz-ai-discovery' ); ?> &rarr;</a>
						<?php endif; ?>
						<?php if ( ! empty( $rootz_check['link'] ) ) : ?>
							<a href="
							<?php
							echo esc_url(
								add_query_arg(
									array(
										'page' => 'rootz-ai-discovery',
										'tab'  => $rootz_check['link'],
									),
									admin_url( 'options-general.php' )
								)
							);
							?>
										" style="white-space: nowrap;"><?php esc_html_e( 'Fix this', 'rootz-ai-discovery' ); ?> &rarr;</a>
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
