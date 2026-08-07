<?php
/**
 * AI Readiness Score — the single source of truth for the local self-test.
 *
 * This was previously computed inline inside admin/views/score-preview.php, which
 * meant the number could only ever be seen by someone who had already found the
 * settings page. Extracting it lets the menu badge, the activation notice and the
 * view all report the SAME number — a score that disagrees with itself is worse
 * than no score at all.
 *
 * Note on precision: this is an estimate from local settings. It does not fetch
 * the site over HTTP, so it cannot see the things only an outside observer can
 * (link headers, real endpoint reachability, bot walls). The authoritative score
 * is the one rootz.global/api/scan produces from outside. The UI says so.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Computes the local AI Readiness estimate.
 */
class Rootz_Score {

	/**
	 * Denominator, matching the published 120-point standard.
	 */
	const MAX = 120;

	/**
	 * Cache key for the computed score.
	 */
	const CACHE_KEY = 'rootz_score_estimate';

	/**
	 * Calculate the local score, check list and grade.
	 *
	 * @param bool $use_cache Whether to serve a cached result (default true).
	 * @return array {
	 *     @type int    $score  Points earned.
	 *     @type int    $max    Denominator.
	 *     @type float  $pct    Percentage.
	 *     @type string $grade  A–F.
	 *     @type string $color  Hex colour for the grade.
	 *     @type array  $checks Individual checks, keyed by slug.
	 * }
	 */
	public static function calculate( $use_cache = true ) {
		if ( $use_cache ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$checks = array();
		$score  = 0;

		// 1. Discovery endpoint — always present once the plugin is active.
		$checks['wellKnownAi'] = array(
			'pass'   => true,
			'label'  => __( '/.well-known/ai Endpoint', 'rootz-ai-discovery' ),
			'detail' => 'v' . ROOTZ_AI_DISCOVERY_SPEC . ' — ' . __( 'active', 'rootz-ai-discovery' ),
			'why'    => __( 'The discovery endpoint lets AI read structured data about your site in one request instead of scraping dozens of pages. This saves compute cycles and reduces the energy cost of every AI query about your organization.', 'rootz-ai-discovery' ),
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
		$org_domain = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( ! empty( $org_name ) && ! empty( $org_domain ) && ! empty( $org_mission ) && ! empty( $org_sector ) ) {
			$checks['organization'] = array(
				'pass'   => true,
				'label'  => __( 'Organization Data', 'rootz-ai-discovery' ),
				'detail' => __( 'Name, domain, mission, sector — complete', 'rootz-ai-discovery' ),
				'why'    => __( 'Complete organization data means AI can accurately describe who you are without guessing. When someone asks "What does this company do?", AI answers from your data — not from outdated web scrapes or hallucinations.', 'rootz-ai-discovery' ),
				'learn'  => 'https://rootz.global/standard#organization',
			);
			$score += 15;
		} else {
			$missing = array();
			if ( empty( $org_name ) ) {
				$missing[] = __( 'name', 'rootz-ai-discovery' );
			}
			if ( empty( $org_mission ) ) {
				$missing[] = __( 'mission', 'rootz-ai-discovery' );
			}
			if ( empty( $org_sector ) ) {
				$missing[] = __( 'sector', 'rootz-ai-discovery' );
			}
			$checks['organization'] = array(
				'pass'   => false,
				'label'  => __( 'Organization Data', 'rootz-ai-discovery' ),
				/* translators: %s: comma-separated list of missing field names. */
				'detail' => sprintf( __( 'Missing: %s', 'rootz-ai-discovery' ), implode( ', ', $missing ) ),
				'why'    => __( 'Without complete organization data, AI must scrape your site and guess your identity. This leads to inaccurate responses and wasted processing. Fill in your mission and sector so AI gets it right the first time.', 'rootz-ai-discovery' ),
				'link'   => 'identity',
				'points' => 15,
			);
		}

		// 3. Core concepts — explicit glossary, or category descriptions.
		$concepts       = get_option( 'rootz_core_concepts', '' );
		$cats_with_desc = get_categories(
			array(
				'orderby' => 'count',
				'order'   => 'DESC',
				'number'  => 1,
				'exclude' => array( 1 ),
			)
		);
		$has_concepts = ! empty( $concepts ) || ( ! empty( $cats_with_desc ) && ! empty( $cats_with_desc[0]->description ) );
		if ( $has_concepts ) {
			$checks['coreConcepts'] = array(
				'pass'   => true,
				'label'  => __( 'Core Concepts', 'rootz-ai-discovery' ),
				'detail' => __( 'Glossary terms defined', 'rootz-ai-discovery' ),
				'why'    => __( 'Your glossary teaches AI your domain language. When a user asks about your product or industry, AI uses your definitions — not Wikipedia\'s. This is your chance to control how AI talks about your field.', 'rootz-ai-discovery' ),
			);
			$score += 10;
		} else {
			$checks['coreConcepts'] = array(
				'pass'   => false,
				'label'  => __( 'Core Concepts', 'rootz-ai-discovery' ),
				'detail' => __( 'Add glossary terms on the Identity tab, or add descriptions to your categories', 'rootz-ai-discovery' ),
				'why'    => __( 'Without core concepts, AI has no authoritative definitions for your domain terms. It will use generic descriptions or make assumptions. Define your key terms and AI will use your exact language.', 'rootz-ai-discovery' ),
				'link'   => 'identity',
				'points' => 10,
			);
		}

		// 4. Contact email.
		$contact_email = get_option( 'rootz_contact_email', '' );
		if ( empty( $contact_email ) ) {
			$contact_email = get_option( 'admin_email', '' );
		}
		if ( ! empty( $contact_email ) ) {
			$checks['contact'] = array(
				'pass'   => true,
				'label'  => __( 'Contact Email', 'rootz-ai-discovery' ),
				'detail' => $contact_email,
				'why'    => __( 'A contact email lets AI direct users to reach you. When someone asks "How do I contact this company?", AI provides your preferred channel instead of guessing.', 'rootz-ai-discovery' ),
			);
			$score += 5;
		} else {
			$checks['contact'] = array(
				'pass'   => false,
				'label'  => __( 'Contact Email', 'rootz-ai-discovery' ),
				'detail' => __( 'Add a contact email on the Identity tab', 'rootz-ai-discovery' ),
				'why'    => __( 'Without a contact email, AI cannot help users reach you. This is a missed opportunity every time someone asks an AI assistant how to get in touch.', 'rootz-ai-discovery' ),
				'link'   => 'identity',
				'points' => 5,
			);
		}

		// 5. Content hash.
		$signed_manifest = get_option( 'rootz_signed_manifest', false );
		$has_hash        = ! empty( $signed_manifest ) && is_array( $signed_manifest ) && ! empty( $signed_manifest['_signature']['contentHash'] );
		if ( $has_hash ) {
			$checks['contentHash'] = array(
				'pass'   => true,
				'label'  => __( 'Content Hash', 'rootz-ai-discovery' ),
				'detail' => __( 'SHA-256 integrity hash included', 'rootz-ai-discovery' ),
				'why'    => __( 'Content hashes let AI verify your data hasn\'t been tampered with in transit. This is a fundamental building block of trust — AI can confirm it\'s reading exactly what you published, not a modified copy.', 'rootz-ai-discovery' ),
				'learn'  => 'https://rootz.global/standard#integrity',
			);
			$score += 10;
		}

		// 6. AI Summary.
		$ai_summary = get_option( 'rootz_ai_summary', '' );
		if ( ! empty( $ai_summary ) ) {
			$checks['aiSummary'] = array(
				'pass'   => true,
				'label'  => __( 'AI Summary', 'rootz-ai-discovery' ),
				'detail' => wp_trim_words( $ai_summary, 10 ),
				'why'    => __( 'The AI Summary is the single most important field. It\'s the first thing AI reads and the primary source for answering "What is this site about?" A well-written summary shapes every AI response about your organization.', 'rootz-ai-discovery' ),
			);
			$score += 10;
		} else {
			$checks['aiSummary'] = array(
				'pass'   => false,
				'label'  => __( 'AI Summary', 'rootz-ai-discovery' ),
				'detail' => __( 'Write a summary on the Identity tab — it\'s the first thing AI reads', 'rootz-ai-discovery' ),
				'why'    => __( 'Without an AI Summary, AI must scrape your homepage and infer what you do. This takes more processing power and often produces vague or incorrect descriptions. Write one clear paragraph and AI gets it right every time.', 'rootz-ai-discovery' ),
				'link'   => 'identity',
				'points' => 10,
			);
		}

		// 7. Knowledge endpoint.
		$knowledge_on = '1' === get_option( 'rootz_enable_knowledge', '1' );
		if ( $knowledge_on ) {
			$checks['knowledge'] = array(
				'pass'   => true,
				'label'  => __( 'Knowledge Endpoint', 'rootz-ai-discovery' ),
				'detail' => '/.well-known/ai/knowledge',
				'why'    => __( 'The knowledge endpoint provides deep, structured information about your products, services, and expertise. AI uses this for detailed questions beyond the basic "What do you do?" — like product comparisons, pricing, and technical capabilities.', 'rootz-ai-discovery' ),
				'learn'  => 'https://rootz.global/standard#knowledge',
			);
			$score += 10;
		} else {
			$checks['knowledge'] = array(
				'pass'   => false,
				'hint'   => true,
				'label'  => __( 'Knowledge Endpoint', 'rootz-ai-discovery' ),
				'detail' => __( 'Enable on the Content tab for deeper AI understanding', 'rootz-ai-discovery' ),
				'why'    => __( 'Without a knowledge endpoint, AI only has your summary and basic identity. Enabling it gives AI a structured encyclopedia of your organization — products, services, glossary — so it can answer detailed questions accurately.', 'rootz-ai-discovery' ),
				'link'   => 'content',
				'points' => 10,
			);
		}

		// 8. Feed endpoint.
		$feed_on = '1' === get_option( 'rootz_enable_feed', '1' );
		if ( $feed_on ) {
			$checks['feed'] = array(
				'pass'   => true,
				'label'  => __( 'Feed Endpoint', 'rootz-ai-discovery' ),
				'detail' => '/.well-known/ai/feed',
				'why'    => __( 'The AI feed keeps AI up to date with your latest content. When someone asks "What\'s new at your company?", AI answers with real information instead of stale data from its training set.', 'rootz-ai-discovery' ),
				'learn'  => 'https://rootz.global/standard#feed',
			);
			$score += 5;
		} else {
			$checks['feed'] = array(
				'pass'   => false,
				'hint'   => true,
				'label'  => __( 'Feed Endpoint', 'rootz-ai-discovery' ),
				'detail' => __( 'Enable on the Content tab to share recent posts', 'rootz-ai-discovery' ),
				'why'    => __( 'Without a feed, AI has no way to know what\'s new. It will answer with outdated information from months or years ago. A feed ensures AI always has your latest news, blog posts, and updates.', 'rootz-ai-discovery' ),
				'link'   => 'content',
				'points' => 5,
			);
		}

		// 9. Three-tier bonus.
		if ( $knowledge_on && $feed_on ) {
			$score += 5;
		}

		// 10. Content endpoint.
		if ( '1' === get_option( 'rootz_enable_content', '0' ) ) {
			$checks['content'] = array(
				'pass'   => true,
				'label'  => __( 'Content Endpoint', 'rootz-ai-discovery' ),
				'detail' => '/.well-known/ai/content',
				'why'    => __( 'The content endpoint eliminates HTML scraping entirely. AI gets clean, structured data instead of parsing your theme\'s HTML. This is dramatically more efficient — one API call replaces hundreds of page fetches, reducing both your bandwidth costs and AI processing energy.', 'rootz-ai-discovery' ),
				'learn'  => 'https://rootz.global/standard#content',
			);
			$score += 15;
		} else {
			$checks['content'] = array(
				'pass'   => false,
				'hint'   => true,
				'label'  => __( 'Content Endpoint', 'rootz-ai-discovery' ),
				'detail' => __( 'Enable on the Content tab — eliminates AI scraping', 'rootz-ai-discovery' ),
				'why'    => __( 'Without a content endpoint, AI must scrape every page of your site to read your content. This wastes bandwidth, processing power, and energy. The content endpoint serves everything in one clean response — better for you, better for AI, better for the planet.', 'rootz-ai-discovery' ),
				'link'   => 'content',
				'points' => 15,
			);
		}

		// 11. Plugin wallet / digital name.
		$signing_address = class_exists( 'Rootz_Signer' ) ? Rootz_Signer::stored_address() : '';
		if ( ! empty( $signing_address ) ) {
			$checks['digitalName'] = array(
				'pass'   => true,
				'label'  => __( 'Digital Name', 'rootz-ai-discovery' ),
				'detail' => substr( $signing_address, 0, 12 ) . '...',
				'why'    => __( 'Your Digital Name is a persistent cryptographic identity for your site. Unlike domain names that can be transferred or spoofed, a blockchain address proves continuity — AI can verify it\'s been talking to the same entity over time.', 'rootz-ai-discovery' ),
				'learn'  => 'https://rootz.global/standard#verification',
			);
			$score += 5;
		} else {
			$checks['digitalName'] = array(
				'pass'   => false,
				'hint'   => true,
				'label'  => __( 'Digital Name', 'rootz-ai-discovery' ),
				'detail' => __( 'Generate a plugin wallet on the Account tab', 'rootz-ai-discovery' ),
				'why'    => __( 'Without a Digital Name, your site has no persistent cryptographic identity. AI can\'t distinguish your site from an impersonator. A wallet address proves ownership and builds trust over time.', 'rootz-ai-discovery' ),
				'link'   => 'account',
				'points' => 5,
			);
		}

		// 12. Signature.
		$has_signature = ! empty( $signed_manifest ) && is_array( $signed_manifest )
			&& ! empty( $signed_manifest['_signature']['method'] )
			&& 'hash-only' !== $signed_manifest['_signature']['method'];
		if ( $has_signature ) {
			$checks['signature'] = array(
				'pass'   => true,
				'label'  => __( 'Cryptographic Signature', 'rootz-ai-discovery' ),
				'detail' => 'ECDSA secp256k1',
				'why'    => __( 'Signed content is verifiably authentic. AI can mathematically confirm that this data was published by the holder of your private key and hasn\'t been modified. In an era of deepfakes and misinformation, signed content is how AI knows it can trust what it reads.', 'rootz-ai-discovery' ),
				'learn'  => 'https://rootz.global/standard#signing',
			);
			$score += 3;
		} else {
			$checks['signature'] = array(
				'pass'   => false,
				'hint'   => true,
				'label'  => __( 'Cryptographic Signature', 'rootz-ai-discovery' ),
				'detail' => __( 'Generate a wallet and sign your manifest', 'rootz-ai-discovery' ),
				'why'    => __( 'Unsigned content can be intercepted and modified. With a cryptographic signature, AI can prove your data is authentic. This is especially important as AI-generated misinformation grows — signed content stands out as verified and trustworthy.', 'rootz-ai-discovery' ),
				'link'   => 'account',
				'points' => 3,
			);
		}

		// 13. SEO tags — ours, or any of the major SEO plugins.
		$seo_on = '1' === get_option( 'rootz_enable_seo_tags', '1' );
		$has_seo = $seo_on;
		if ( ! $has_seo ) {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$seo_plugins = array(
				'wordpress-seo/wp-seo.php',
				'seo-by-rank-math/rank-math.php',
				'all-in-one-seo-pack/all_in_one_seo_pack.php',
				'wp-seopress/seopress.php',
			);
			foreach ( $seo_plugins as $plugin ) {
				if ( is_plugin_active( $plugin ) ) {
					$has_seo = true;
					break;
				}
			}
		}
		if ( $has_seo ) {
			$checks['seoMeta'] = array(
				'pass'   => true,
				'label'  => __( 'SEO Meta Tags', 'rootz-ai-discovery' ),
				'detail' => $seo_on
					? __( 'Plugin provides meta description, OG, JSON-LD', 'rootz-ai-discovery' )
					: __( 'SEO plugin detected', 'rootz-ai-discovery' ),
				'why'    => __( 'Meta tags, OpenGraph, and JSON-LD structured data help AI understand your pages before reading the full content. This pre-processing layer means faster, more accurate AI responses with less compute overhead.', 'rootz-ai-discovery' ),
			);
			$score += 2;
		} else {
			$checks['seoMeta'] = array(
				'pass'   => false,
				'hint'   => true,
				'label'  => __( 'SEO Meta Tags', 'rootz-ai-discovery' ),
				'detail' => __( 'Enable SEO tags on the Account tab or install an SEO plugin', 'rootz-ai-discovery' ),
				'why'    => __( 'SEO meta tags aren\'t just for Google — AI uses them too. A meta description gives AI a quick summary of each page, OpenGraph tags provide social context, and JSON-LD schema gives structured data AI can parse instantly.', 'rootz-ai-discovery' ),
				'link'   => 'account',
				'points' => 2,
			);
		}

		// 14. HTTPS — reported, not scored (the standard assumes it).
		if ( 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME ) ) {
			$checks['https'] = array(
				'pass'   => true,
				'label'  => __( 'HTTPS', 'rootz-ai-discovery' ),
				'detail' => __( 'Secure connection', 'rootz-ai-discovery' ),
				'why'    => __( 'HTTPS ensures data integrity between your server and AI. Without it, content could be modified in transit, undermining the trust chain that signed content establishes.', 'rootz-ai-discovery' ),
			);
		}

		$grade  = self::grade_for( $score );
		$result = array(
			'score'  => $score,
			'max'    => self::MAX,
			'pct'    => self::MAX > 0 ? ( $score / self::MAX ) * 100 : 0,
			'grade'  => $grade['grade'],
			'color'  => $grade['color'],
			'checks' => $checks,
		);

		set_transient( self::CACHE_KEY, $result, 15 * MINUTE_IN_SECONDS );

		return $result;
	}

	/**
	 * Map a raw score to a letter grade and colour, using the published thresholds.
	 *
	 * @param int $score Points earned.
	 * @return array{grade:string,color:string}
	 */
	public static function grade_for( $score ) {
		$pct = self::MAX > 0 ? ( $score / self::MAX ) * 100 : 0;

		if ( $pct >= 80 ) {
			return array(
				'grade' => 'A',
				'color' => '#16a34a',
			);
		}
		if ( $pct >= 55 ) {
			return array(
				'grade' => 'B',
				'color' => '#65a30d',
			);
		}
		if ( $pct >= 35 ) {
			return array(
				'grade' => 'C',
				'color' => '#ca8a04',
			);
		}
		if ( $pct >= 15 ) {
			return array(
				'grade' => 'D',
				'color' => '#ea580c',
			);
		}
		return array(
			'grade' => 'F',
			'color' => '#dc2626',
		);
	}

	/**
	 * The unfinished checks, highest-value first — what the operator should do next.
	 *
	 * @param int $limit Maximum number of steps to return.
	 * @return array List of failing checks with their point values.
	 */
	public static function next_steps( $limit = 3 ) {
		$result = self::calculate();
		$todo   = array();

		foreach ( $result['checks'] as $key => $check ) {
			if ( empty( $check['pass'] ) ) {
				$check['key'] = $key;
				$todo[]       = $check;
			}
		}

		usort(
			$todo,
			function ( $a, $b ) {
				return ( isset( $b['points'] ) ? $b['points'] : 0 ) - ( isset( $a['points'] ) ? $a['points'] : 0 );
			}
		);

		return array_slice( $todo, 0, $limit );
	}

	/**
	 * Drop the cached estimate. Call after any settings change.
	 */
	public static function flush() {
		delete_transient( self::CACHE_KEY );
	}
}
