<?php
/**
 * Generates llms.txt and llms-full.txt from WordPress site structure.
 *
 * Spec-compliant with llmstxt.org format. Pulls from AI Discovery settings,
 * WordPress content, and policy pages. Optionally signs the output with
 * the plugin wallet — the one thing no other llms.txt implementation does.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates llms.txt and llms-full.txt files from WordPress site data.
 */
class Rootz_Llms_Txt {

	/**
	 * Generate standard llms.txt (concise, links only).
	 * Served at /llms.txt
	 *
	 * @return string The llms.txt content.
	 */
	public function generate() {
		$cached = get_transient( 'rootz_llms_txt_cache' );
		if ( false !== $cached ) {
			return $cached;
		}

		$lines = array();
		$lines = array_merge( $lines, $this->get_header() );
		$lines = array_merge( $lines, $this->get_about_section() );
		$lines = array_merge( $lines, $this->get_pages_section() );
		$lines = array_merge( $lines, $this->get_posts_section() );
		$lines = array_merge( $lines, $this->get_policies_section() );
		$lines = array_merge( $lines, $this->get_agents_section() );
		$lines = array_merge( $lines, $this->get_optional_section() );

		$content = implode( "\n", $lines ) . "\n";
		$content = html_entity_decode( $content, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$content = $this->append_signature( $content );

		set_transient( 'rootz_llms_txt_cache', $content, HOUR_IN_SECONDS );

		return $content;
	}

	/**
	 * Generate llms-full.txt (full content inline).
	 * Served at /llms-full.txt
	 *
	 * @return string The llms-full.txt content.
	 */
	public function generate_full() {
		$cached = get_transient( 'rootz_llms_full_cache' );
		if ( false !== $cached ) {
			return $cached;
		}

		$lines = array();
		$lines = array_merge( $lines, $this->get_header() );

		// Core concepts expanded.
		$concepts = get_option( 'rootz_core_concepts', '' );
		if ( ! empty( $concepts ) ) {
			$lines[] = '## Core Concepts';
			$lines[] = '';
			foreach ( explode( "\n", $concepts ) as $concept ) {
				$concept = trim( $concept );
				if ( ! empty( $concept ) ) {
					$lines[] = $concept;
				}
			}
			$lines[] = '';
		}

		// Full page content.
		$lines = array_merge( $lines, $this->get_full_pages() );

		// Full post content.
		$lines = array_merge( $lines, $this->get_full_posts() );

		// Policies.
		$lines = array_merge( $lines, $this->get_policies_section() );

		// For AI Agents.
		$lines = array_merge( $lines, $this->get_agents_section() );

		$content = implode( "\n", $lines ) . "\n";
		$content = html_entity_decode( $content, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$content = $this->append_signature( $content );

		set_transient( 'rootz_llms_full_cache', $content, HOUR_IN_SECONDS );

		return $content;
	}

	/**
	 * Build the H1 heading, blockquote, and prose intro section.
	 *
	 * @return array Lines of text for the header section.
	 */
	private function get_header() {
		$lines = array();

		$site_name = get_option( 'rootz_organization_name', get_bloginfo( 'name' ) );
		$tagline   = get_option( 'rootz_organization_tagline', get_bloginfo( 'description' ) );
		$summary   = get_option( 'rootz_ai_summary', '' );
		$sector    = get_option( 'rootz_sector', get_option( 'rootz_organization_sector', '' ) );

		$lines[] = '# ' . $site_name;
		$lines[] = '';

		// Blockquote: prefer AI summary, fall back to tagline.
		$blockquote = ! empty( $summary ) ? $summary : $tagline;
		if ( ! empty( $blockquote ) ) {
			$lines[] = '> ' . $blockquote;
			$lines[] = '';
		}

		// Prose intro.
		$intro_parts = array();
		if ( ! empty( $sector ) ) {
			$intro_parts[] = $site_name . ' is a ' . $sector . ' organization.';
		}
		if ( ! empty( $tagline ) && ! empty( $summary ) ) {
			// If we used summary for blockquote, add tagline as prose.
			$intro_parts[] = $tagline;
		}
		if ( ! empty( $intro_parts ) ) {
			$lines[] = implode( ' ', $intro_parts );
			$lines[] = '';
		}

		return $lines;
	}

	/**
	 * Build the about section with key links.
	 *
	 * @return array Lines of text for the about section.
	 */
	private function get_about_section() {
		$lines   = array();
		$lines[] = '## About';
		$lines[] = '';

		// About page.
		foreach ( array( 'about', 'about-us', 'who-we-are', 'company' ) as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page && 'publish' === $page->post_status ) {
				$lines[] = '- [' . $page->post_title . '](' . get_permalink( $page ) . '): Company overview and mission';
				break;
			}
		}

		// AI Discovery endpoint.
		$lines[] = '- [AI Discovery](' . home_url( '/.well-known/ai' ) . '): Machine-readable identity (AI Discovery Standard v1.2)';

		// Contact.
		$contact_url = get_option( 'rootz_contact_url', '' );
		if ( ! empty( $contact_url ) ) {
			$lines[] = '- [Contact](' . $contact_url . '): Contact information';
		}

		$lines[] = '';
		return $lines;
	}

	/**
	 * Build the key pages section with optional excerpts.
	 *
	 * @return array Lines of text for the pages section.
	 */
	private function get_pages_section() {
		$limit            = absint( get_option( 'rootz_llms_pages_limit', 30 ) );
		$include_excerpts = '1' === get_option( 'rootz_llms_include_excerpts', '1' );

		$pages = get_pages(
			array(
				'sort_column' => 'menu_order,post_title',
				'post_status' => 'publish',
				'number'      => $limit,
			)
		);

		if ( empty( $pages ) ) {
			return array();
		}

		$lines   = array();
		$lines[] = '## Key Pages';
		$lines[] = '';

		// Split: first batch goes in main section, overflow goes in Optional.
		$main_limit = min( count( $pages ), 15 );
		for ( $i = 0; $i < $main_limit; $i++ ) {
			$page = $pages[ $i ];
			$url  = get_permalink( $page );
			$line = '- [' . $page->post_title . '](' . $url . ')';
			if ( $include_excerpts ) {
				$excerpt = $this->get_excerpt( $page );
				if ( ! empty( $excerpt ) ) {
					$line .= ': ' . $excerpt;
				}
			}
			$lines[] = $line;
		}

		$lines[] = '';
		return $lines;
	}

	/**
	 * Build the recent posts section with optional excerpts.
	 *
	 * @return array Lines of text for the posts section.
	 */
	private function get_posts_section() {
		$limit            = absint( get_option( 'rootz_llms_posts_limit', 10 ) );
		$include_excerpts = '1' === get_option( 'rootz_llms_include_excerpts', '1' );

		$posts = get_posts(
			array(
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if ( empty( $posts ) ) {
			return array();
		}

		$lines   = array();
		$lines[] = '## Recent Posts';
		$lines[] = '';
		foreach ( $posts as $post ) {
			$url  = get_permalink( $post );
			$line = '- [' . $post->post_title . '](' . $url . ')';
			if ( $include_excerpts ) {
				$excerpt = $this->get_excerpt( $post );
				if ( ! empty( $excerpt ) ) {
					$line .= ': ' . $excerpt;
				}
			}
			$lines[] = $line;
		}

		$lines[] = '';
		return $lines;
	}

	/**
	 * Build the policies section with license, permissions, and discovered policy pages.
	 *
	 * @return array Lines of text for the policies section.
	 */
	private function get_policies_section() {
		$lines   = array();
		$lines[] = '## Policies';
		$lines[] = '';

		// Discovered policy pages.
		$discovered = Rootz_Rest_Api::discover_policy_pages();
		foreach ( $discovered as $key => $info ) {
			if ( ! empty( $info['url'] ) && ! empty( $info['title'] ) ) {
				$desc = '';
				if ( ! empty( $info['type'] ) ) {
					$type_labels = array(
						'privacy'         => 'Data handling and privacy practices',
						'terms'           => 'Usage terms and conditions',
						'data-protection' => 'Data protection and PII handling',
						'cookies'         => 'Cookie usage policy',
						'ai-usage'        => 'AI and bot usage policy',
						'accessibility'   => 'Accessibility statement',
						'copyright'       => 'Copyright and DMCA policy',
					);
					$desc        = isset( $type_labels[ $info['type'] ] ) ? ': ' . $type_labels[ $info['type'] ] : '';
				}
				$lines[] = '- [' . $info['title'] . '](' . $info['url'] . ')' . $desc;
			}
		}

		// License and permissions.
		$license     = get_option( 'rootz_content_license', 'all-rights-reserved' );
		$license_map = array(
			'cc-by-4.0'           => 'CC-BY-4.0',
			'cc-by-sa-4.0'        => 'CC-BY-SA-4.0',
			'cc-by-nc-4.0'        => 'CC-BY-NC-4.0',
			'cc-by-nc-sa-4.0'     => 'CC-BY-NC-SA-4.0',
			'cc0'                 => 'CC0-1.0 (Public Domain)',
			'all-rights-reserved' => 'All Rights Reserved',
		);
		$lines[]     = 'Content license: ' . ( isset( $license_map[ $license ] ) ? $license_map[ $license ] : $license );

		if ( '1' === get_option( 'rootz_allow_quoting', '1' ) ) {
			$lines[] = 'AI agents may quote and summarize content with attribution.';
		}
		if ( '1' === get_option( 'rootz_allow_training', '0' ) ) {
			$lines[] = 'AI training on this content is permitted.';
		} else {
			$lines[] = 'AI training on this content is not permitted.';
		}

		$lines[] = '';
		return $lines;
	}

	/**
	 * Build the AI Agents section with tools and endpoints.
	 *
	 * @return array Lines of text for the agents section.
	 */
	private function get_agents_section() {
		$lines   = array();
		$lines[] = '## For AI Agents';
		$lines[] = '';

		// Always available.
		$lines[] = '- [API Tools](' . home_url( '/.well-known/ai/tools' ) . '): Available tool endpoints';

		if ( '1' === get_option( 'rootz_enable_knowledge', '1' ) ) {
			$lines[] = '- [Knowledge Base](' . home_url( '/.well-known/ai/knowledge' ) . '): Structured organizational knowledge';
		}
		if ( '1' === get_option( 'rootz_enable_feed', '1' ) ) {
			$lines[] = '- [Content Feed](' . home_url( '/.well-known/ai/feed' ) . '): AI-optimized content feed';
		}
		if ( '1' === get_option( 'rootz_enable_content', '0' ) ) {
			$lines[] = '- [Content](' . home_url( '/.well-known/ai/content' ) . '): Full structured content endpoint';
		}

		$lines[] = '- [Search](' . rest_url( 'rootz/v1/search?q=QUERY' ) . '): Full-text content search';
		$lines[] = '- [Verify](' . rest_url( 'rootz/v1/verify?page=/PATH' ) . '): Verify page content integrity';
		$lines[] = '- [Status](' . rest_url( 'rootz/v1/status' ) . '): Site AI-readiness score';

		$lines[] = '';
		return $lines;
	}

	/**
	 * Build the optional section for overflow pages and older posts.
	 *
	 * @return array Lines of text for the optional section.
	 */
	private function get_optional_section() {
		$limit = absint( get_option( 'rootz_llms_pages_limit', 30 ) );

		$pages = get_pages(
			array(
				'sort_column' => 'menu_order,post_title',
				'post_status' => 'publish',
				'number'      => $limit,
			)
		);

		// Only include overflow pages beyond the first 15.
		$overflow = array_slice( $pages, 15 );
		if ( empty( $overflow ) ) {
			return array();
		}

		$lines   = array();
		$lines[] = '## Optional';
		$lines[] = '';
		foreach ( $overflow as $page ) {
			$lines[] = '- [' . $page->post_title . '](' . get_permalink( $page ) . ')';
		}

		$lines[] = '';
		return $lines;
	}

	/**
	 * Build full page content as markdown for llms-full.txt.
	 *
	 * @return array Lines of markdown for all pages.
	 */
	private function get_full_pages() {
		$pages = get_pages(
			array(
				'sort_column' => 'menu_order,post_title',
				'post_status' => 'publish',
				'number'      => absint( get_option( 'rootz_llms_pages_limit', 30 ) ),
			)
		);

		if ( empty( $pages ) ) {
			return array();
		}

		$lines   = array();
		$lines[] = '## Pages';
		$lines[] = '';

		foreach ( $pages as $page ) {
			$lines[] = '### ' . $page->post_title;
			$lines[] = 'URL: ' . get_permalink( $page );
			$lines[] = '';
			$lines[] = $this->strip_to_markdown( $page->post_content );
			$lines[] = '';
		}

		return $lines;
	}

	/**
	 * Build full post content as markdown for llms-full.txt.
	 *
	 * @return array Lines of markdown for all posts.
	 */
	private function get_full_posts() {
		$limit = absint( get_option( 'rootz_llms_full_posts_limit', 50 ) );

		$posts = get_posts(
			array(
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if ( empty( $posts ) ) {
			return array();
		}

		$lines   = array();
		$lines[] = '## Recent Posts';
		$lines[] = '';

		foreach ( $posts as $post ) {
			$lines[] = '### ' . $post->post_title;
			$lines[] = 'Published: ' . get_the_date( 'Y-m-d', $post );
			$lines[] = 'URL: ' . get_permalink( $post );
			$lines[] = '';
			$lines[] = $this->strip_to_markdown( $post->post_content );
			$lines[] = '';
		}

		return $lines;
	}

	/**
	 * Public entry point for HTML-to-markdown conversion.
	 *
	 * Used by getPage tool and other components that need clean markdown
	 * from WordPress HTML content.
	 *
	 * @param string $html Raw post content HTML.
	 * @return string Clean markdown text.
	 */
	public function html_to_markdown( $html ) {
		return $this->strip_to_markdown( $html );
	}

	/**
	 * Convert HTML content to clean markdown.
	 *
	 * Simple conversion — not perfect, but readable. No external dependencies.
	 *
	 * @param string $html Raw post content HTML.
	 * @return string Clean markdown text.
	 */
	private function strip_to_markdown( $html ) {
		// Apply WordPress content filters (shortcodes, embeds, etc.).
		$html = apply_filters( 'the_content', $html );

		// Headings.
		$html = preg_replace( '/<h1[^>]*>(.*?)<\/h1>/is', '# $1' . "\n", $html );
		$html = preg_replace( '/<h2[^>]*>(.*?)<\/h2>/is', '## $1' . "\n", $html );
		$html = preg_replace( '/<h3[^>]*>(.*?)<\/h3>/is', '### $1' . "\n", $html );
		$html = preg_replace( '/<h4[^>]*>(.*?)<\/h4>/is', '#### $1' . "\n", $html );
		$html = preg_replace( '/<h5[^>]*>(.*?)<\/h5>/is', '##### $1' . "\n", $html );
		$html = preg_replace( '/<h6[^>]*>(.*?)<\/h6>/is', '###### $1' . "\n", $html );

		// Links.
		$html = preg_replace( '/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', '[$2]($1)', $html );

		// Bold and italic.
		$html = preg_replace( '/<(strong|b)>(.*?)<\/\1>/is', '**$2**', $html );
		$html = preg_replace( '/<(em|i)>(.*?)<\/\1>/is', '*$2*', $html );

		// Images.
		$html = preg_replace( '/<img[^>]+alt=["\']([^"\']*)["\'][^>]+src=["\']([^"\']+)["\'][^>]*\/?>/is', '![$1]($2)', $html );
		$html = preg_replace( '/<img[^>]+src=["\']([^"\']+)["\'][^>]+alt=["\']([^"\']*)["\'][^>]*\/?>/is', '![$2]($1)', $html );
		$html = preg_replace( '/<img[^>]+src=["\']([^"\']+)["\'][^>]*\/?>/is', '![]($1)', $html );

		// Blockquotes.
		$html = preg_replace( '/<blockquote[^>]*>(.*?)<\/blockquote>/is', '> $1', $html );

		// List items.
		$html = preg_replace( '/<li[^>]*>(.*?)<\/li>/is', '- $1' . "\n", $html );

		// Paragraphs and line breaks.
		$html = preg_replace( '/<\/p>/is', "\n\n", $html );
		$html = preg_replace( '/<br\s*\/?>/is', "\n", $html );

		// Strip all remaining HTML tags.
		$html = wp_strip_all_tags( $html );

		// Decode HTML entities (&mdash; → —, &amp; → &, etc.).
		$html = html_entity_decode( $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		// Clean up whitespace.
		$html = preg_replace( '/\n{3,}/', "\n\n", $html );
		$html = preg_replace( '/[ \t]+/', ' ', $html );

		return trim( $html );
	}

	/**
	 * Get a first-sentence excerpt from a post.
	 *
	 * @param WP_Post $post The post object.
	 * @return string First sentence or trimmed excerpt.
	 */
	private function get_excerpt( $post ) {
		if ( ! empty( $post->post_excerpt ) ) {
			return html_entity_decode( wp_trim_words( $post->post_excerpt, 15 ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}
		$plain = html_entity_decode( wp_strip_all_tags( $post->post_content ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		if ( empty( $plain ) ) {
			return '';
		}
		// First sentence.
		$first = preg_split( '/(?<=[.!?])\s+/', $plain, 2 );
		if ( ! empty( $first[0] ) && strlen( $first[0] ) < 200 ) {
			return $first[0];
		}
		return wp_trim_words( $plain, 15 );
	}

	/**
	 * Append a signed attestation footer to llms.txt content.
	 *
	 * This is the unique differentiator. No other llms.txt plugin signs the output.
	 *
	 * @param string $content The llms.txt content above the signature.
	 * @return string Content with signature block appended.
	 */
	private function append_signature( $content ) {
		$lines   = array();
		$lines[] = '---';
		$lines[] = '_Generated by Rootz AI Discovery v' . ROOTZ_AI_DISCOVERY_VERSION . '_';

		$address = Rootz_Signer::stored_address();
		if ( ! empty( $address ) ) {
			$lines[] = '_Signed by: ' . $address . '_';
		}

		// Hash the content above the separator.
		$content_hash = hash( 'sha256', $content );
		$lines[]      = '_Content hash: sha256:' . $content_hash . '_';

		// Sign if wallet + GMP available.
		if ( ! empty( $address ) && Rootz_Signer::signing_available() ) {
			$signer = new Rootz_Signer();
			if ( $signer->has_key() ) {
				$sig = $signer->sign( $content_hash );
				if ( $sig ) {
					$lines[] = '_Signature: ' . $sig['signature'] . '_';
				}
			}
		}

		$lines[] = '_Timestamp: ' . gmdate( 'c' ) . '_';
		$lines[] = '_Verify: ' . home_url( '/.well-known/ai' ) . '_';

		return $content . implode( "\n", $lines ) . "\n";
	}
}
