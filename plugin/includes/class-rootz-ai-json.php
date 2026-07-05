<?php
/**
 * Generates the ai.json response for /.well-known/ai (v1.2).
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates the ai.json manifest for the AI Discovery endpoint.
 */
class Rootz_Ai_Json {

	/**
	 * Generate the ai.json manifest.
	 *
	 * Serves the admin-approved signed manifest if available.
	 * Falls back to generating a fresh (unsigned or self-signed) manifest
	 * if no admin-approved version exists yet.
	 */
	public function generate() {
		$cached = get_transient( 'rootz_ai_json_cache' );
		if ( false !== $cached ) {
			return $cached;
		}

		// Prefer the admin-approved signed manifest.
		$signed = get_option( 'rootz_signed_manifest', false );
		if ( ! empty( $signed ) && is_array( $signed ) ) {
			set_transient( 'rootz_ai_json_cache', $signed, HOUR_IN_SECONDS );
			return $signed;
		}

		// No admin-approved manifest yet — generate fresh with hash-only signature.
		// This happens on first install or before any admin visits the settings.
		$data = $this->generate_unsigned();

		$content_hash       = hash( 'sha256', wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		$data['_signature'] = array(
			'contentHash'   => 'sha256:' . $content_hash,
			'signedAt'      => gmdate( 'c' ),
			'method'        => 'hash-only',
			'authorization' => 'pending-admin-approval',
			'note'          => 'This manifest has not yet been approved by an admin. Visit the plugin settings to sign it.',
		);

		set_transient( 'rootz_ai_json_cache', $data, HOUR_IN_SECONDS );
		return $data;
	}

	/**
	 * Generate the ai.json data WITHOUT a signature block.
	 * Used by the admin signing flow to produce clean data for signing.
	 */
	public function generate_unsigned() {
		$data = array(
			'specVersion'   => ROOTZ_AI_DISCOVERY_SPEC,
			'schemaVersion' => '2026-02',
			'standard'      => 'ai-discovery',
			'generated'     => gmdate( 'c' ),
			'organization'  => $this->get_organization(),
			'coreConcepts'  => $this->get_core_concepts(),
			'capabilities'  => $this->get_capabilities(),
			'policies'      => $this->get_policies_summary(),
		);

		$ai_summary = get_option( 'rootz_ai_summary', '' );
		if ( ! empty( $ai_summary ) ) {
			$data['aiSummary'] = $ai_summary;
		}

		$data['contact'] = $this->get_contacts();

		$data['generator'] = array(
			'name'    => 'rootz-ai-discovery',
			'version' => ROOTZ_AI_DISCOVERY_VERSION,
			'url'     => 'https://discover.rootz.global/plugin/',
		);

		$pages = $this->get_pages();
		if ( ! empty( $pages ) ) {
			$data['pages'] = $pages;
		}

		return $data;
	}

	/**
	 * Build the organization data block.
	 *
	 * @return array Organization data.
	 */
	private function get_organization() {
		$org = array(
			'name'   => get_option( 'rootz_organization_name', get_bloginfo( 'name' ) ),
			'domain' => wp_parse_url( home_url(), PHP_URL_HOST ),
			'url'    => home_url(),
		);

		$fields = array(
			'rootz_organization_tagline' => 'mission',
			'rootz_tagline'              => 'tagline',
			'rootz_legal_name'           => 'legalName',
			'rootz_founded'              => 'founded',
			'rootz_headquarters'         => 'headquarters',
			'rootz_digital_name'         => 'digitalName',
		);

		foreach ( $fields as $option_key => $json_key ) {
			$value = get_option( $option_key, '' );
			if ( ! empty( $value ) ) {
				$org[ $json_key ] = $value;
			}
		}

		// Sector as array.
		$sector = get_option( 'rootz_sector', '' );
		if ( empty( $sector ) ) {
			$sector = get_option( 'rootz_organization_sector', '' );
		}
		if ( ! empty( $sector ) ) {
			$org['sector'] = array_map( 'trim', explode( ',', $sector ) );
		}

		// Site icon as logo.
		$site_icon_id = get_option( 'site_icon' );
		if ( $site_icon_id ) {
			$icon_url = wp_get_attachment_image_url( $site_icon_id, 'full' );
			if ( $icon_url ) {
				$org['logo'] = $icon_url;
			}
		}

		return $org;
	}

	/**
	 * Build the contact information block.
	 *
	 * @return array Contact data.
	 */
	private function get_contacts() {
		$contacts = array();

		// Primary contact email (general inquiries).
		$contact_email = get_option( 'rootz_contact_email', '' );
		if ( ! empty( $contact_email ) ) {
			$contacts['email'] = $contact_email;
		}

		// Contact URL (contact page).
		$contact_url = get_option( 'rootz_contact_url', '' );
		if ( ! empty( $contact_url ) ) {
			$contacts['url'] = $contact_url;
		}

		// Operator / responsible person.
		$operator = get_option( 'rootz_contact_operator', '' );
		if ( ! empty( $operator ) ) {
			$contacts['operator'] = $operator;
		}

		// AI-specific contact (for agent developers).
		$ai_email = get_option( 'rootz_contact_ai_email', '' );
		if ( ! empty( $ai_email ) ) {
			$contacts['aiSupport'] = $ai_email;
		}

		// Privacy contact.
		$privacy_email = get_option( 'rootz_contact_privacy_email', '' );
		if ( ! empty( $privacy_email ) ) {
			$contacts['privacy'] = $privacy_email;
		}

		// Fall back to WordPress admin email if nothing configured.
		if ( empty( $contacts ) ) {
			$admin_email = get_option( 'admin_email', '' );
			if ( ! empty( $admin_email ) ) {
				$contacts['email'] = $admin_email;
			}
		}

		return $contacts;
	}

	/**
	 * Build the capabilities data block.
	 *
	 * @return array Capabilities data.
	 */
	private function get_capabilities() {
		$caps = array(
			'knowledge' => array(
				'available' => '1' === get_option( 'rootz_enable_knowledge', '1' ),
				'url'       => '/.well-known/ai/knowledge',
				'auth'      => 'none',
				'rateLimit' => '100/hour',
			),
			'feed'      => array(
				'available' => '1' === get_option( 'rootz_enable_feed', '1' ),
				'url'       => '/.well-known/ai/feed',
				'auth'      => 'none',
				'rateLimit' => '100/hour',
			),
		);

		if ( '1' === get_option( 'rootz_enable_content', '0' ) ) {
			$segments = array();
			if ( '1' === get_option( 'rootz_content_include_pages', '1' ) ) {
				$segments[] = '/.well-known/ai/content/pages';
			}
			if ( '1' === get_option( 'rootz_content_include_posts', '1' ) ) {
				$segments[] = '/.well-known/ai/content/posts';
			}
			if ( '1' === get_option( 'rootz_content_include_media', '0' ) ) {
				$segments[] = '/.well-known/ai/content/media';
			}

			$caps['content'] = array(
				'available' => true,
				'url'       => '/.well-known/ai/content',
				'auth'      => 'none',
				'rateLimit' => '100/hour',
				'segments'  => $segments,
				'includes'  => array(
					'pages'       => '1' === get_option( 'rootz_content_include_pages', '1' ),
					'posts'       => '1' === get_option( 'rootz_content_include_posts', '1' ),
					'customTypes' => '1' === get_option( 'rootz_content_include_custom_types', '0' ),
					'media'       => '1' === get_option( 'rootz_content_include_media', '0' ),
					'fullText'    => '1' === get_option( 'rootz_content_include_full_text', '0' ),
				),
			);
		}

		$tool_count = class_exists( 'Rootz_Rest_Api' ) ? Rootz_Rest_Api::tool_count() : 8;
		$protocols  = array( 'rest' );
		if ( '1' === get_option( 'rootz_webmcp_enabled', '1' ) ) {
			array_unshift( $protocols, 'webmcp' );
		}
		$caps['tools'] = array(
			'available' => true,
			'protocols' => $protocols,
			'toolCount' => $tool_count,
		);

		return $caps;
	}

	/**
	 * Build the policies summary block for ai.json.
	 *
	 * @return array Policies summary data.
	 */
	private function get_policies_summary() {
		$license = get_option( 'rootz_content_license', 'all-rights-reserved' );
		$map     = array(
			'cc-by-4.0'           => 'CC-BY-4.0',
			'cc-by-sa-4.0'        => 'CC-BY-SA-4.0',
			'cc-by-nc-4.0'        => 'CC-BY-NC-4.0',
			'cc-by-nc-sa-4.0'     => 'CC-BY-NC-SA-4.0',
			'cc0'                 => 'CC0-1.0',
			'all-rights-reserved' => 'All Rights Reserved',
		);

		$policies = array(
			'contentLicense' => array(
				'type' => isset( $map[ $license ] ) ? $map[ $license ] : $license,
			),
		);

		if ( '1' === get_option( 'rootz_allow_quoting', '1' ) ) {
			$policies['contentLicense']['permissions'] = array( 'quote', 'summarize' );
		}
		if ( '0' === get_option( 'rootz_allow_training', '0' ) ) {
			$policies['contentLicense']['restrictions'] = array( 'no_training' );
		}

		// Auto-discover policy pages and include URL + type in ai.json summary.
		// Uses the same discovery as the full policies endpoint, but trimmed
		// to just URL and type for the compact ai.json format.
		$discovered = Rootz_Rest_Api::discover_policy_pages();
		foreach ( $discovered as $key => $info ) {
			$policies[ $key ] = array(
				'url'  => $info['url'],
				'type' => $info['type'],
			);
		}

		// Add pointer to full policies endpoint.
		$policies['detailsUrl'] = '/.well-known/ai/policies';

		return $policies;
	}

	/**
	 * Build the core concepts glossary from settings or categories.
	 *
	 * @return array Core concepts data.
	 */
	private function get_core_concepts() {
		$concepts = array();
		$custom   = get_option( 'rootz_core_concepts', '' );

		if ( ! empty( $custom ) ) {
			foreach ( explode( "\n", $custom ) as $line ) {
				$parts = explode( ':', $line, 2 );
				if ( count( $parts ) === 2 ) {
					$concepts[] = array(
						'term'       => trim( $parts[0] ),
						'definition' => trim( $parts[1] ),
					);
				}
			}
		}

		// Fall back to categories with descriptions.
		if ( empty( $concepts ) ) {
			$categories = get_categories(
				array(
					'orderby' => 'count',
					'order'   => 'DESC',
					'number'  => 10,
					'exclude' => array( 1 ),
				)
			);
			foreach ( $categories as $cat ) {
				if ( ! empty( $cat->description ) ) {
					$concepts[] = array(
						'term'       => $cat->name,
						'definition' => $cat->description,
					);
				}
			}
		}

		// Still empty? Auto-generate from site tagline + about page.
		if ( empty( $concepts ) ) {
			$tagline = get_bloginfo( 'description' );
			if ( ! empty( $tagline ) ) {
				$concepts[] = array(
					'term'       => get_bloginfo( 'name' ),
					'definition' => $tagline,
				);
			}

			// Try to extract key info from About page.
			$about_slugs = array( 'about', 'about-us', 'who-we-are' );
			foreach ( $about_slugs as $slug ) {
				$about = get_page_by_path( $slug );
				if ( $about && 'publish' === $about->post_status ) {
					$about_text = wp_strip_all_tags( $about->post_content );
					if ( strlen( $about_text ) > 50 ) {
						$concepts[] = array(
							'term'       => 'About',
							'definition' => wp_trim_words( $about_text, 60 ),
						);
					}
					break;
				}
			}
		}

		return $concepts;
	}

	/**
	 * Build the site pages list with content hashes.
	 *
	 * @return array Pages data.
	 */
	private function get_pages() {
		$wp_pages = get_pages(
			array(
				'sort_column' => 'menu_order,post_title',
				'post_status' => 'publish',
				'number'      => 20,
			)
		);
		if ( empty( $wp_pages ) ) {
			return array();
		}

		$pages = array();
		foreach ( $wp_pages as $page ) {
			// Plain-text content for hashing (strip HTML, normalize whitespace).
			$plain = wp_strip_all_tags( $page->post_content );
			$plain = preg_replace( '/\s+/', ' ', trim( $plain ) );

			$entry = array(
				'path'        => wp_parse_url( get_permalink( $page ), PHP_URL_PATH ) ? wp_parse_url( get_permalink( $page ), PHP_URL_PATH ) : '/',
				'title'       => $page->post_title,
				'contentHash' => 'sha256:' . hash( 'sha256', $plain ),
				'modified'    => get_post_modified_time( 'c', true, $page ),
			);

			$pages[] = $entry;
		}
		return $pages;
	}
}
