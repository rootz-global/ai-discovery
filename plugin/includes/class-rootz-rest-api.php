<?php
/**
 * REST API endpoints for Rootz AI Discovery.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API endpoints and tools for Rootz AI Discovery.
 */
class Rootz_Rest_Api {

	/**
	 * Register all REST routes.
	 */
	public function register_routes() {
		$namespace = 'rootz/v1';

		// GET /rootz/v1/ai.json — Organization info.
		register_rest_route(
			$namespace,
			'/ai.json',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_ai_json' ),
				'permission_callback' => '__return_true',
			)
		);

		// GET /rootz/v1/policies — Machine-readable policies.
		// Supports ?full_text=1 to include complete policy page content.
		register_rest_route(
			$namespace,
			'/policies',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_policies' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'full_text' => array(
						'type'              => 'string',
						'default'           => '0',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// GET /rootz/v1/knowledge — Knowledge base.
		register_rest_route(
			$namespace,
			'/knowledge',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_knowledge' ),
				'permission_callback' => '__return_true',
			)
		);

		// GET /rootz/v1/feed — AI-optimized feed.
		register_rest_route(
			$namespace,
			'/feed',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_feed' ),
				'permission_callback' => '__return_true',
			)
		);

		// GET /rootz/v1/tools — Available tool manifest.
		register_rest_route(
			$namespace,
			'/tools',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_tools' ),
				'permission_callback' => '__return_true',
			)
		);

		// GET /rootz/v1/search — Search site content.
		register_rest_route(
			$namespace,
			'/search',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'search_content' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q'      => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'limit'  => array(
						'type'              => 'integer',
						'default'           => 10,
						'sanitize_callback' => 'absint',
					),
					'offset' => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
						'description'       => 'Skip this many results (for pagination). Use with limit.',
					),
					'type'   => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
						'description'       => 'Filter by post type: post, page, or empty for all.',
					),
				),
			)
		);

		// GET /rootz/v1/verify — Verify page content hash against signed manifest.
		register_rest_route(
			$namespace,
			'/verify',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'verify_page_hash' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'page' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// GET /rootz/v1/status — Self-scoring site status for management console.
		register_rest_route(
			$namespace,
			'/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_status' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// GET /rootz/v1/context — AI assistant context file for plugin setup.
		register_rest_route(
			$namespace,
			'/context',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_context' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// GET /rootz/v1/page — Read any published page/post as structured markdown.
		register_rest_route(
			$namespace,
			'/page',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_page_content' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'path' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'description'       => 'Page URL path (e.g., /about/, /blog/my-post/)',
					),
				),
			)
		);
	}

	/**
	 * GET /rootz/v1/ai.json
	 *
	 * @return WP_REST_Response
	 */
	public function get_ai_json() {
		$generator = new Rootz_Ai_Json();
		$data      = $generator->generate();
		$response  = rest_ensure_response( $data );
		$response->header( 'Access-Control-Allow-Origin', '*' );
		return $response;
	}

	/**
	 * GET /rootz/v1/policies
	 *
	 * @param WP_REST_Request|null $request Optional REST request object.
	 * @return WP_REST_Response
	 */
	public function get_policies( $request = null ) {
		$license_key = get_option( 'rootz_content_license', 'all-rights-reserved' );
		$full_text   = $request && '1' === $request->get_param( 'full_text' );

		$policies = array(
			'contentLicense' => array(
				'type'          => $this->format_license( $license_key ),
				'permissions'   => array(),
				'restrictions'  => array(),
				'commercialUse' => ! in_array( $license_key, array( 'cc-by-nc-4.0', 'cc-by-nc-sa-4.0' ), true ),
			),
			'termsOfService' => array(
				'summary'   => esc_html__( 'Standard usage terms apply. AI agents must respect rate limits and attribution requirements.', 'rootz-ai-discovery' ),
				'rateLimit' => '60/hour',
			),
		);

		if ( '1' === get_option( 'rootz_allow_quoting', '1' ) ) {
			$policies['contentLicense']['permissions'][] = 'quote';
			$policies['contentLicense']['permissions'][] = 'summarize';
			$policies['contentLicense']['permissions'][] = 'cache_24h';
		}
		if ( '0' === get_option( 'rootz_allow_training', '0' ) ) {
			$policies['contentLicense']['restrictions'][] = 'no_training';
		}
		$policies['contentLicense']['restrictions'][] = 'no_full_reproduction';

		// Auto-discover policy pages from WordPress content.
		$discovered = self::discover_policy_pages();
		foreach ( $discovered as $key => $info ) {
			$entry = $info;

			// If ?full_text=1, include the complete plain-text page content.
			if ( $full_text && ! empty( $info['url'] ) ) {
				$page_id = url_to_postid( $info['url'] );
				if ( $page_id ) {
					$page = get_post( $page_id );
					if ( $page ) {
						$entry['fullText'] = wp_strip_all_tags( $page->post_content );
					}
				}
			}

			$policies[ $key ] = $entry;
		}

		$policies = $this->sign_response( $policies );

		$response = rest_ensure_response( $policies );
		$response->header( 'Access-Control-Allow-Origin', '*' );
		return $response;
	}

	/**
	 * Return the total number of tools in the manifest.
	 * Keep in sync with get_tools() categories.
	 */
	public static function tool_count() {
		// discovery: getOrganizationInfo, getPolicies, getKnowledge, getFeed.
		// actions:   searchContent, verifyPageHash, getPage.
		// meta:      getStatus, getContext.
		return 9;
	}

	/**
	 * Auto-discover policy pages from WordPress content by slug patterns.
	 *
	 * @return array Discovered policy pages keyed by policy type.
	 */
	public static function discover_policy_pages() {
		$cached = get_transient( 'rootz_discovered_policies' );
		if ( false !== $cached ) {
			return $cached;
		}

		$discovered = array();

		// Policy page patterns: key => [ slugs to try, type label ].
		$patterns = array(
			'privacy'       => array(
				'slugs' => array( 'privacy-policy', 'privacy', 'datenschutz', 'confidentialite' ),
				'type'  => 'privacy',
			),
			'terms'         => array(
				'slugs' => array( 'terms-of-use', 'terms-of-service', 'terms', 'tos', 'terms-and-conditions' ),
				'type'  => 'terms',
			),
			'dataPolicy'    => array(
				'slugs' => array( 'data-pii-policy', 'data-policy', 'pii-policy', 'data-protection', 'data-handling', 'gdpr' ),
				'type'  => 'data-protection',
			),
			'cookiePolicy'  => array(
				'slugs' => array( 'cookie-policy', 'cookies', 'cookie-notice' ),
				'type'  => 'cookies',
			),
			'aiPolicy'      => array(
				'slugs' => array( 'ai-usage-policy', 'ai-policy', 'ai-terms', 'ai-usage', 'bot-policy', 'automation-policy' ),
				'type'  => 'ai-usage',
			),
			'accessibility' => array(
				'slugs' => array( 'accessibility', 'accessibility-statement', 'a11y' ),
				'type'  => 'accessibility',
			),
			'dmca'          => array(
				'slugs' => array( 'dmca', 'copyright', 'copyright-policy', 'dmca-policy' ),
				'type'  => 'copyright',
			),
		);

		// 1. WordPress built-in privacy page first.
		$wp_privacy_id = (int) get_option( 'wp_page_for_privacy_policy' );
		if ( $wp_privacy_id && 'publish' === get_post_status( $wp_privacy_id ) ) {
			$page                  = get_post( $wp_privacy_id );
			$discovered['privacy'] = array(
				'url'     => get_permalink( $wp_privacy_id ),
				'title'   => $page->post_title,
				'type'    => 'privacy',
				'summary' => wp_trim_words( wp_strip_all_tags( $page->post_content ), 100 ),
				'source'  => 'wordpress-setting',
			);
		}

		// 2. Scan for pages matching slug patterns.
		foreach ( $patterns as $key => $pattern ) {
			if ( isset( $discovered[ $key ] ) ) {
				continue;
			}
			foreach ( $pattern['slugs'] as $slug ) {
				$page = get_page_by_path( $slug );
				if ( $page && 'publish' === $page->post_status ) {
					$discovered[ $key ] = array(
						'url'     => get_permalink( $page ),
						'title'   => $page->post_title,
						'type'    => $pattern['type'],
						'summary' => wp_trim_words( wp_strip_all_tags( $page->post_content ), 100 ),
						'source'  => 'auto-discovered',
					);
					break;
				}
			}
		}

		// Cache for 1 hour.
		set_transient( 'rootz_discovered_policies', $discovered, HOUR_IN_SECONDS );
		return $discovered;
	}

	/**
	 * Generate knowledge data (usable outside REST context).
	 *
	 * @return array Knowledge data.
	 */
	public function generate_knowledge() {
		$cached = get_transient( 'rootz_knowledge_cache' );
		if ( false !== $cached ) {
			return $cached;
		}

		$knowledge = $this->build_knowledge();
		set_transient( 'rootz_knowledge_cache', $knowledge, HOUR_IN_SECONDS );
		return $knowledge;
	}

	/**
	 * Generate feed data (usable outside REST context).
	 *
	 * @return array Feed data.
	 */
	public function generate_feed() {
		$cached = get_transient( 'rootz_feed_cache' );
		if ( false !== $cached ) {
			return $cached;
		}

		$feed = $this->build_feed();
		set_transient( 'rootz_feed_cache', $feed, HOUR_IN_SECONDS );
		return $feed;
	}

	/**
	 * GET /rootz/v1/knowledge
	 *
	 * @return WP_REST_Response
	 */
	public function get_knowledge() {
		$knowledge = $this->generate_knowledge();
		$knowledge = $this->sign_response( $knowledge );
		$response  = rest_ensure_response( $knowledge );
		$response->header( 'Access-Control-Allow-Origin', '*' );
		return $response;
	}

	/**
	 * Build knowledge data array from site content.
	 *
	 * @return array Knowledge data structure.
	 */
	private function build_knowledge() {
		$knowledge = array(
			'specVersion'  => ROOTZ_AI_DISCOVERY_SPEC,
			'generated'    => gmdate( 'c' ),
			'organization' => array(
				'name'    => get_option( 'rootz_organization_name', get_bloginfo( 'name' ) ),
				'tagline' => get_option( 'rootz_organization_tagline', get_bloginfo( 'description' ) ),
				'url'     => home_url(),
			),
		);

		// Auto-detect "About" page.
		$about_page = $this->find_page_by_slug( array( 'about', 'about-us', 'who-we-are' ) );
		if ( $about_page ) {
			$knowledge['about'] = array(
				'title'   => $about_page->post_title,
				'url'     => get_permalink( $about_page ),
				'summary' => wp_trim_words( wp_strip_all_tags( $about_page->post_content ), 100 ),
			);
		}

		// Products/services from pages.
		$product_pages = $this->find_pages_by_slug( array( 'products', 'services', 'solutions', 'features' ) );
		if ( ! empty( $product_pages ) ) {
			$knowledge['products'] = array();
			foreach ( $product_pages as $page ) {
				$knowledge['products'][] = array(
					'name'        => $page->post_title,
					'url'         => get_permalink( $page ),
					'description' => wp_trim_words( wp_strip_all_tags( $page->post_content ), 50 ),
				);
			}
		}

		// Categories as glossary.
		$categories = get_categories(
			array(
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => 20,
				'hide_empty' => true,
				'exclude'    => array( 1 ),
			)
		);
		if ( ! empty( $categories ) ) {
			$knowledge['glossary'] = array();
			foreach ( $categories as $cat ) {
				$entry = array(
					'term' => $cat->name,
				);
				if ( ! empty( $cat->description ) ) {
					$entry['definition'] = $cat->description;
				}
				$knowledge['glossary'][] = $entry;
			}
		}

		return $knowledge;
	}

	/**
	 * GET /rootz/v1/feed
	 *
	 * @return WP_REST_Response
	 */
	public function get_feed() {
		$feed     = $this->generate_feed();
		$feed     = $this->sign_response( $feed );
		$response = rest_ensure_response( $feed );
		$response->header( 'Access-Control-Allow-Origin', '*' );
		return $response;
	}

	/**
	 * Build feed data array from recent posts.
	 *
	 * @return array Feed data structure.
	 */
	private function build_feed() {
		$license_key = get_option( 'rootz_content_license', 'all-rights-reserved' );

		$feed = array(
			'specVersion' => ROOTZ_AI_DISCOVERY_SPEC,
			'generated'   => gmdate( 'c' ),
			'source'      => array(
				'name' => get_option( 'rootz_organization_name', get_bloginfo( 'name' ) ),
				'url'  => home_url(),
			),
			'feedUrl'     => rest_url( 'rootz/v1/feed' ),
			'htmlUrl'     => home_url( '/blog' ),
			'items'       => array(),
		);

		$posts = get_posts(
			array(
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		foreach ( $posts as $post ) {
			$item = array(
				'id'        => $post->post_name . '-' . $post->ID,
				'title'     => $post->post_title,
				'url'       => get_permalink( $post ),
				'published' => gmdate( 'c', strtotime( $post->post_date_gmt ) ),
				'summary'   => wp_trim_words( wp_strip_all_tags( $post->post_content ), 60 ),
				'license'   => $this->format_license( $license_key ),
			);

			// Categories.
			$cats = wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
			if ( ! empty( $cats ) ) {
				$item['categories'] = $cats;
			}

			// Tags.
			$tags = wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );
			if ( ! empty( $tags ) ) {
				$item['tags'] = $tags;
			}

			$feed['items'][] = $item;
		}

		return $feed;
	}

	/**
	 * GET /rootz/v1/tools
	 *
	 * @return WP_REST_Response
	 */
	public function get_tools() {
		$tools = array(
			'specVersion' => ROOTZ_AI_DISCOVERY_SPEC,
			'site'        => array(
				'name' => get_option( 'rootz_organization_name', get_bloginfo( 'name' ) ),
				'url'  => home_url(),
			),
			'protocols'   => array( 'rest', 'webmcp' ),
			'categories'  => array(
				'discovery' => array(
					'description' => 'Read-only information about the site.',
					'tools'       => array(
						array(
							'name'        => 'getOrganizationInfo',
							'description' => 'Get structured organization identity and capabilities.',
							'method'      => 'GET',
							'endpoint'    => rest_url( 'rootz/v1/ai.json' ),
							'auth'        => 'none',
						),
						array(
							'name'        => 'getPolicies',
							'description' => 'Get machine-readable content policies, licensing, and terms.',
							'method'      => 'GET',
							'endpoint'    => rest_url( 'rootz/v1/policies' ),
							'auth'        => 'none',
						),
						array(
							'name'        => 'getKnowledge',
							'description' => 'Get structured knowledge base about the organization.',
							'method'      => 'GET',
							'endpoint'    => rest_url( 'rootz/v1/knowledge' ),
							'auth'        => 'none',
						),
						array(
							'name'        => 'getFeed',
							'description' => 'Get AI-optimized feed of recent content.',
							'method'      => 'GET',
							'endpoint'    => rest_url( 'rootz/v1/feed' ),
							'auth'        => 'none',
						),
					),
				),
				'actions'   => array(
					'description' => 'Interactive tools for searching, reading, and verifying site content in real time from the owner\'s database.',
					'tools'       => array(
						array(
							'name'        => 'searchContent',
							'description' => 'Search site content by keyword. Returns matching pages and posts with excerpts. Supports pagination — use offset to get more results.',
							'method'      => 'GET',
							'endpoint'    => rest_url( 'rootz/v1/search' ),
							'auth'        => 'none',
							'parameters'  => array(
								'q'      => array(
									'type'        => 'string',
									'required'    => true,
									'description' => 'Search query',
								),
								'limit'  => array(
									'type'        => 'integer',
									'default'     => 10,
									'description' => 'Max results (up to 50)',
								),
								'offset' => array(
									'type'        => 'integer',
									'default'     => 0,
									'description' => 'Skip results for pagination. Use nextOffset from response.',
								),
								'type'   => array(
									'type'        => 'string',
									'default'     => '',
									'description' => 'Filter: post, page, or empty for all',
								),
							),
						),
						array(
							'name'        => 'getPage',
							'description' => 'Read any published page or post as structured markdown with origin provenance, content hash, freshness metadata, and policy permissions. Use searchContent to find pages, then getPage to read them.',
							'method'      => 'GET',
							'endpoint'    => rest_url( 'rootz/v1/page' ),
							'auth'        => 'none',
							'parameters'  => array(
								'path' => array(
									'type'        => 'string',
									'required'    => true,
									'description' => 'Page URL path (e.g., /about/, /blog/my-post/)',
								),
							),
						),
						array(
							'name'        => 'verifyPageHash',
							'description' => 'Verify a page content hash against the signed manifest. Proves content integrity without trusting the transport layer.',
							'method'      => 'GET',
							'endpoint'    => rest_url( 'rootz/v1/verify' ),
							'auth'        => 'none',
							'parameters'  => array(
								'page' => array(
									'type'        => 'string',
									'required'    => true,
									'description' => 'Page path (e.g., /about/)',
								),
							),
						),
					),
				),
				'meta'      => array(
					'description' => 'Site health and configuration status.',
					'tools'       => array(
						array(
							'name'        => 'getStatus',
							'description' => 'Get site AI Discovery score, endpoint status, and metrics summary. Requires admin authentication.',
							'method'      => 'GET',
							'endpoint'    => rest_url( 'rootz/v1/status' ),
							'auth'        => 'manage_options',
						),
						array(
							'name'        => 'getContext',
							'description' => 'Get AI assistant context file with plugin setup instructions, current site status, and score breakdown. Read this first when helping a site owner configure AI Discovery. Requires admin authentication.',
							'method'      => 'GET',
							'endpoint'    => rest_url( 'rootz/v1/context' ),
							'auth'        => 'manage_options',
							'contentType' => 'text/markdown',
						),
					),
				),
			),
		);

		$tools = $this->sign_response( $tools );

		$response = rest_ensure_response( $tools );
		$response->header( 'Access-Control-Allow-Origin', '*' );
		return $response;
	}

	/**
	 * GET /rootz/v1/search — Search site content.
	 *
	 * @param WP_REST_Request $request The REST request with search parameters.
	 * @return WP_REST_Response
	 */
	public function search_content( $request ) {
		$query  = $request->get_param( 'q' );
		$limit  = min( $request->get_param( 'limit' ), 50 );
		$offset = $request->get_param( 'offset' );
		$type   = $request->get_param( 'type' );

		$post_types = array( 'post', 'page' );
		if ( ! empty( $type ) && in_array( $type, array( 'post', 'page' ), true ) ) {
			$post_types = array( $type );
		}

		$wp_query = new WP_Query(
			array(
				's'              => $query,
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'offset'         => $offset,
				'orderby'        => 'relevance',
			)
		);

		$results = array();
		foreach ( $wp_query->posts as $post ) {
			$plain     = wp_strip_all_tags( $post->post_content );
			$results[] = array(
				'title'     => $post->post_title,
				'url'       => get_permalink( $post ),
				'path'      => wp_parse_url( get_permalink( $post ), PHP_URL_PATH ) ? wp_parse_url( get_permalink( $post ), PHP_URL_PATH ) : '/',
				'type'      => $post->post_type,
				'excerpt'   => wp_trim_words( $plain, 40 ),
				'published' => get_the_date( 'c', $post ),
				'modified'  => get_post_modified_time( 'c', true, $post ),
			);
		}

		$found_total = $wp_query->found_posts;
		$has_more    = ( $offset + $limit ) < $found_total;

		$data = array(
			'query'       => $query,
			'results'     => $results,
			'returned'    => count( $results ),
			'totalFound'  => $found_total,
			'offset'      => $offset,
			'hasMore'     => $has_more,
			'_provenance' => $this->build_provenance(),
		);

		if ( $has_more ) {
			$data['nextOffset'] = $offset + $limit;
		}

		$data     = $this->sign_response( $data );
		$response = rest_ensure_response( $data );
		$response->header( 'Access-Control-Allow-Origin', '*' );
		return $response;
	}

	/**
	 * GET /rootz/v1/verify — Verify page content hash against signed manifest.
	 *
	 * @param WP_REST_Request $request The REST request with page parameter.
	 * @return WP_REST_Response
	 */
	public function verify_page_hash( $request ) {
		$page_path = $request->get_param( 'page' );

		// Normalize: ensure leading slash, trim trailing slash for matching.
		$page_path = '/' . ltrim( $page_path, '/' );

		// Use the cached manifest (don't bust cache on every verify call).
		$generator = new Rootz_Ai_Json();
		$manifest  = $generator->generate();

		// Find the page in the manifest.
		$manifest_entry = null;
		if ( ! empty( $manifest['pages'] ) ) {
			foreach ( $manifest['pages'] as $entry ) {
				// Match with and without trailing slash.
				$entry_path = rtrim( $entry['path'], '/' );
				$check_path = rtrim( $page_path, '/' );
				if ( $entry_path === $check_path || $entry['path'] === $page_path ) {
					$manifest_entry = $entry;
					break;
				}
			}
		}

		if ( ! $manifest_entry ) {
			$data     = array(
				'page'     => $page_path,
				'verified' => false,
				'error'    => 'Page not found in manifest. Available pages: ' . implode( ', ', array_column( $manifest['pages'] ?? array(), 'path' ) ),
			);
			$data     = $this->sign_response( $data );
			$response = rest_ensure_response( $data );
			$response->header( 'Access-Control-Allow-Origin', '*' );
			return $response;
		}

		// Resolve the page from WordPress to compute current hash.
		$page_id = $this->resolve_page_id( $page_path );

		if ( ! $page_id ) {
			$data     = array(
				'page'         => $page_path,
				'manifestHash' => $manifest_entry['contentHash'],
				'verified'     => false,
				'error'        => 'Could not resolve page path to a WordPress post.',
			);
			$data     = $this->sign_response( $data );
			$response = rest_ensure_response( $data );
			$response->header( 'Access-Control-Allow-Origin', '*' );
			return $response;
		}

		// Compute current content hash from the live database content.
		$post         = get_post( $page_id );
		$plain        = wp_strip_all_tags( $post->post_content );
		$plain        = preg_replace( '/\s+/', ' ', trim( $plain ) );
		$current_hash = 'sha256:' . hash( 'sha256', $plain );

		$match = ( $current_hash === $manifest_entry['contentHash'] );

		$signer_address = '';
		if ( ! empty( $manifest['_signature']['signer'] ) ) {
			$signer_address = $manifest['_signature']['signer'];
		}

		$data = array(
			'page'              => $page_path,
			'title'             => $manifest_entry['title'],
			'manifestHash'      => $manifest_entry['contentHash'],
			'currentHash'       => $current_hash,
			'match'             => $match,
			'verified'          => $match,
			'signer'            => $signer_address,
			'manifestGenerated' => $manifest['generated'] ?? '',
			'pageModified'      => $manifest_entry['modified'],
		);

		$data     = $this->sign_response( $data );
		$response = rest_ensure_response( $data );
		$response->header( 'Access-Control-Allow-Origin', '*' );
		return $response;
	}

	/**
	 * Resolve a URL path to a WordPress post ID.
	 *
	 * Tries multiple strategies: url_to_postid with and without trailing slash,
	 * then falls back to get_page_by_path for slug-based lookup.
	 *
	 * @param string $path URL path (e.g., /about/).
	 * @return int Post ID or 0 if not found.
	 */
	private function resolve_page_id( $path ) {
		$page_id = url_to_postid( home_url( $path ) );
		if ( $page_id ) {
			return $page_id;
		}

		$page_id = url_to_postid( home_url( rtrim( $path, '/' ) ) );
		if ( $page_id ) {
			return $page_id;
		}

		$slug = trim( $path, '/' );
		$post = get_page_by_path( $slug );
		if ( $post ) {
			return $post->ID;
		}

		return 0;
	}

	/**
	 * GET /rootz/v1/status — Self-scoring site status.
	 *
	 * @return WP_REST_Response
	 */
	public function get_status() {
		$data     = $this->build_status_data();
		$data     = $this->sign_response( $data );
		$response = rest_ensure_response( $data );
		$response->header( 'Access-Control-Allow-Origin', '*' );
		return $response;
	}

	/**
	 * Build the status data array (shared by get_status and get_context).
	 *
	 * @return array Status data without signature.
	 */
	private function build_status_data() {
		$breakdown = array();
		$total     = 0;

		// 1. Identity (max 15).
		$identity_score   = 0;
		$identity_missing = array();
		if ( get_option( 'rootz_organization_name', '' ) ) {
			$identity_score += 3;
		} else {
			$identity_missing[] = 'name'; }
		$identity_score += 3; // domain is always present.
		if ( get_option( 'rootz_legal_name', '' ) ) {
			$identity_score += 3;
		} else {
			$identity_missing[] = 'legalName'; }
		if ( get_option( 'rootz_sector', '' ) ) {
			$identity_score += 3;
		} else {
			$identity_missing[] = 'sector'; }
		if ( get_option( 'rootz_organization_tagline', '' ) || get_option( 'rootz_tagline', '' ) ) {
			$identity_score += 3;
		} else {
			$identity_missing[] = 'tagline'; }
		$breakdown['identity'] = array(
			'score'   => $identity_score,
			'max'     => 15,
			'details' => $identity_missing ? 'Missing: ' . implode( ', ', $identity_missing ) : 'Complete',
		);
		$total                += $identity_score;

		// 2. Policies (max 15).
		$policy_score = 0;
		if ( 'all-rights-reserved' !== get_option( 'rootz_content_license', 'all-rights-reserved' ) ) {
			$policy_score += 3;
		} else {
			++$policy_score; } // Even default gets a point.
		$discovered            = self::discover_policy_pages();
		$policy_score         += min( count( $discovered ) * 3, 12 );
		$breakdown['policies'] = array(
			'score'   => min( $policy_score, 15 ),
			'max'     => 15,
			'details' => count( $discovered ) . ' policy pages discovered',
		);
		$total                += min( $policy_score, 15 );

		// 3. Signing (max 15).
		$signing_score  = 0;
		$signing_detail = 'No wallet';
		$signer         = new Rootz_Signer();
		if ( $signer->has_key() ) {
			$signing_score += 5;
			$signing_detail = 'Wallet active';
			if ( Rootz_Signer::signing_available() ) {
				$signing_score += 5;
				$signing_detail = 'Wallet + signing active';
			}
		}
		if ( get_option( 'rootz_authorization_signature', '' ) ) {
			$signing_score  += 5;
			$signing_detail .= ', delegated';
		} else {
			$signing_detail .= ', self-signed';
		}
		$breakdown['signing'] = array(
			'score'   => $signing_score,
			'max'     => 15,
			'details' => $signing_detail,
		);
		$total               += $signing_score;

		// 4. Content (max 15).
		$pages          = get_pages(
			array(
				'post_status' => 'publish',
				'number'      => 20,
			)
		);
		$posts          = wp_count_posts();
		$page_count     = count( $pages );
		$post_count     = isset( $posts->publish ) ? $posts->publish : 0;
		$content_score  = 0;
		$content_score += min( $page_count, 5 ); // Up to 5 points for pages.
		$content_score += min( $post_count, 5 ); // Up to 5 points for posts.
		if ( '1' === get_option( 'rootz_enable_content', '0' ) ) {
			$content_score += 5; }
		$breakdown['content'] = array(
			'score'   => min( $content_score, 15 ),
			'max'     => 15,
			'details' => $page_count . ' pages with hashes, ' . $post_count . ' posts',
		);
		$total               += min( $content_score, 15 );

		// 5. Endpoints (max 15).
		$ep_score               = 15; // All 5 core endpoints always respond if plugin is active.
		$breakdown['endpoints'] = array(
			'score'   => $ep_score,
			'max'     => 15,
			'details' => 'All 5 endpoints responding',
		);
		$total                 += $ep_score;

		// 6. Contacts (max 10).
		$contact_score = 0;
		if ( get_option( 'rootz_contact_email', '' ) || get_option( 'admin_email', '' ) ) {
			$contact_score += 3; }
		if ( get_option( 'rootz_contact_operator', '' ) ) {
			$contact_score += 3; }
		if ( get_option( 'rootz_contact_ai_email', '' ) ) {
			$contact_score += 2; }
		if ( get_option( 'rootz_contact_privacy_email', '' ) ) {
			$contact_score += 2; }
		$breakdown['contacts'] = array(
			'score'   => $contact_score,
			'max'     => 10,
			'details' => $contact_score . ' of 10 contact points',
		);
		$total                += $contact_score;

		// 7. Knowledge (max 10).
		$knowledge_score = 0;
		$about           = $this->find_page_by_slug( array( 'about', 'about-us', 'who-we-are' ) );
		if ( $about ) {
			$knowledge_score += 4; }
		$products = $this->find_pages_by_slug( array( 'products', 'services', 'solutions', 'features' ) );
		if ( ! empty( $products ) ) {
			$knowledge_score += 3; }
		$concepts = get_option( 'rootz_core_concepts', '' );
		if ( ! empty( $concepts ) ) {
			$knowledge_score += 3; }
		$breakdown['knowledge'] = array(
			'score'   => $knowledge_score,
			'max'     => 10,
			'details' => ( $about ? 'About page' : 'No about page' ) . ', ' . count( $products ) . ' product pages',
		);
		$total                 += $knowledge_score;

		// 8. Metrics (max 5).
		$metrics_active       = class_exists( 'Rootz_Metrics' );
		$metrics_score        = $metrics_active ? 5 : 0;
		$breakdown['metrics'] = array(
			'score'   => $metrics_score,
			'max'     => 5,
			'details' => $metrics_active ? 'Logging active' : 'Not active',
		);
		$total               += $metrics_score;

		// Grade.
		if ( $total >= 90 ) {
			$grade = 'A'; } elseif ( $total >= 80 ) {
			$grade = 'B'; } elseif ( $total >= 70 ) {
				$grade = 'C'; } elseif ( $total >= 60 ) {
				$grade = 'D'; } else {
					$grade = 'F'; }

				// Metrics summary.
				$metrics_summary = array(
					'last24h'  => 0,
					'last7d'   => 0,
					'topAgent' => 'none',
				);
				if ( $metrics_active ) {
					$summary                    = Rootz_Metrics::get_summary( 7 );
					$metrics_summary['last24h'] = $summary['last24h'];
					$metrics_summary['last7d']  = $summary['last7d'];
					if ( ! empty( $summary['agents'] ) ) {
						$metrics_summary['topAgent'] = $summary['agents'][0]->ai_provider;
					}
				}

				$wallet_address = Rootz_Signer::stored_address();
				$authorization  = get_option( 'rootz_authorization_signature', '' ) ? 'delegated' : 'self-signed';

				return array(
					'domain'        => wp_parse_url( home_url(), PHP_URL_HOST ),
					'pluginVersion' => ROOTZ_AI_DISCOVERY_VERSION,
					'score'         => $total,
					'grade'         => $grade,
					'breakdown'     => $breakdown,
					'wallet'        => $wallet_address ? $wallet_address : 'none',
					'authorization' => $authorization,
					'endpoints'     => array(
						'ai_json'   => true,
						'policies'  => true,
						'knowledge' => '1' === get_option( 'rootz_enable_knowledge', '1' ),
						'feed'      => '1' === get_option( 'rootz_enable_feed', '1' ),
						'tools'     => true,
						'search'    => true,
						'verify'    => true,
						'status'    => true,
					),
					'metrics'       => $metrics_summary,
				);
	}

	/**
	 * GET /rootz/v1/context — AI assistant context file.
	 *
	 * Serves the ai.context.md shipped with the plugin so any AI assistant
	 * can learn how to help configure AI Discovery for this site.
	 *
	 * @return WP_REST_Response
	 */
	public function get_context() {
		$file = ROOTZ_AI_DISCOVERY_DIR . 'ai.context.md';
		if ( ! file_exists( $file ) ) {
			return new WP_REST_Response( array( 'error' => 'Context file not found' ), 404 );
		}

		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$content = $wp_filesystem->get_contents( $file );

		// Append current site status for the AI assistant.
		$status_data = $this->get_status_data();
		$content    .= "\n\n---\n\n## Current Site Status (auto-appended)\n\n";
		$content    .= '- **Domain**: ' . wp_parse_url( home_url(), PHP_URL_HOST ) . "\n";
		$content    .= '- **Plugin version**: ' . ROOTZ_AI_DISCOVERY_VERSION . "\n";
		$content    .= '- **Score**: ' . $status_data['score'] . '/100 (Grade ' . $status_data['grade'] . ")\n";
		$content    .= '- **Wallet**: ' . ( 'none' !== $status_data['wallet'] ? $status_data['wallet'] : 'Not configured' ) . "\n";
		$content    .= '- **Authorization**: ' . $status_data['authorization'] . "\n";
		$content    .= "\n### Score Breakdown\n\n";
		foreach ( $status_data['breakdown'] as $cat => $info ) {
			$content .= '- **' . ucfirst( $cat ) . '**: ' . $info['score'] . '/' . $info['max'] . ' — ' . $info['details'] . "\n";
		}

		// Setup log if one exists.
		$log = get_option( 'rootz_setup_log', '' );
		if ( ! empty( $log ) ) {
			$content .= "\n### Previous Setup Log\n\n```\n" . $log . "\n```\n";
		}

		$response = new WP_REST_Response( $content, 200 );
		$response->header( 'Content-Type', 'text/markdown; charset=utf-8' );
		$response->header( 'Access-Control-Allow-Origin', '*' );
		return $response;
	}

	/**
	 * Get status data used by get_context to append site status.
	 *
	 * @return array Status data array.
	 */
	private function get_status_data() {
		return $this->build_status_data();
	}

	/**
	 * Find a page by slug from a list of candidates.
	 *
	 * @param array $slugs Slugs to search for.
	 * @return WP_Post|null
	 */
	private function find_page_by_slug( $slugs ) {
		foreach ( $slugs as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page && 'publish' === $page->post_status ) {
				return $page;
			}
		}
		return null;
	}

	/**
	 * Find pages matching any of the given slugs.
	 *
	 * @param array $slugs Slugs to search for.
	 * @return array WP_Post objects.
	 */
	private function find_pages_by_slug( $slugs ) {
		$found = array();
		foreach ( $slugs as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page && 'publish' === $page->post_status ) {
				$found[] = $page;
			}
		}
		return $found;
	}

	/**
	 * Format license key.
	 *
	 * @param string $key License key.
	 * @return string Formatted name.
	 */
	private function format_license( $key ) {
		$map = array(
			'cc-by-4.0'           => 'CC-BY-4.0',
			'cc-by-sa-4.0'        => 'CC-BY-SA-4.0',
			'cc-by-nc-4.0'        => 'CC-BY-NC-4.0',
			'cc-by-nc-sa-4.0'     => 'CC-BY-NC-SA-4.0',
			'cc0'                 => 'CC0-1.0',
			'all-rights-reserved' => 'All Rights Reserved',
		);
		return isset( $map[ $key ] ) ? $map[ $key ] : $key;
	}

	/**
	 * GET /rootz/v1/page — Read any published page/post as structured markdown.
	 *
	 * This is the "conversation mode" tool — AI discovers a page via search or
	 * manifest, then requests its full content as clean markdown with provenance
	 * and freshness metadata.
	 *
	 * @param WP_REST_Request $request The REST request with path parameter.
	 * @return WP_REST_Response
	 */
	public function get_page_content( $request ) {
		$page_path = $request->get_param( 'path' );

		// Normalize: ensure leading slash.
		$page_path = '/' . ltrim( $page_path, '/' );

		// Resolve the page from WordPress.
		$page_id = $this->resolve_page_id( $page_path );

		if ( ! $page_id ) {
			$data     = array(
				'path'        => $page_path,
				'error'       => 'Page not found. Try searchContent to find available pages.',
				'_provenance' => $this->build_provenance(),
			);
			$data     = $this->sign_response( $data );
			$response = rest_ensure_response( $data );
			$response->set_status( 404 );
			$response->header( 'Access-Control-Allow-Origin', '*' );
			return $response;
		}

		$post = get_post( $page_id );

		if ( ! $post || 'publish' !== $post->post_status ) {
			$data     = array(
				'path'        => $page_path,
				'error'       => 'Page is not published.',
				'_provenance' => $this->build_provenance(),
			);
			$data     = $this->sign_response( $data );
			$response = rest_ensure_response( $data );
			$response->set_status( 404 );
			$response->header( 'Access-Control-Allow-Origin', '*' );
			return $response;
		}

		// Convert HTML to clean markdown using the llms.txt converter.
		$llms     = new Rootz_Llms_Txt();
		$markdown = $llms->html_to_markdown( $post->post_content );
		$markdown = html_entity_decode( $markdown, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		// Compute content hash from the plain text (same method as verify).
		$plain        = wp_strip_all_tags( $post->post_content );
		$plain        = preg_replace( '/\s+/', ' ', trim( $plain ) );
		$content_hash = 'sha256:' . hash( 'sha256', $plain );

		// Determine assertion type.
		$assertion_type = 'factual';
		if ( 'post' === $post->post_type ) {
			$assertion_type = 'editorial';
		}

		// Word count for AI context window planning.
		$word_count = str_word_count( $plain );

		// Get policies for this content.
		$license  = get_option( 'rootz_content_license', 'all-rights-reserved' );
		$quoting  = '1' === get_option( 'rootz_allow_quoting', '1' );
		$training = '1' === get_option( 'rootz_allow_training', '0' );

		// Categories and tags (for posts).
		$categories = array();
		$tags       = array();
		if ( 'post' === $post->post_type ) {
			$cats = wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
			if ( ! empty( $cats ) ) {
				$categories = $cats;
			}
			$tag_list = wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );
			if ( ! empty( $tag_list ) ) {
				$tags = $tag_list;
			}
		}

		// Author info.
		$author_name = get_the_author_meta( 'display_name', $post->post_author );

		$data = array(
			'path'          => $page_path,
			'title'         => $post->post_title,
			'url'           => get_permalink( $post ),
			'type'          => $post->post_type,
			'assertionType' => $assertion_type,
			'author'        => $author_name,
			'wordCount'     => $word_count,
			'content'       => $markdown,
			'contentHash'   => $content_hash,
			'policies'      => array(
				'license'  => $this->format_license( $license ),
				'quoting'  => $quoting ? 'allowed' : 'not-allowed',
				'training' => $training ? 'permitted' : 'not-permitted',
				'caching'  => $quoting ? 'cache_24h' : 'no-cache',
			),
			'_origin'       => array(
				'domain'      => wp_parse_url( home_url(), PHP_URL_HOST ),
				'publishedAt' => get_the_date( 'c', $post ),
				'modifiedAt'  => get_post_modified_time( 'c', true, $post ),
				'servedAt'    => gmdate( 'c' ),
				'signer'      => Rootz_Signer::stored_address() ? Rootz_Signer::stored_address() : 'none',
			),
			'_freshness'    => $this->build_freshness( $post ),
			'_provenance'   => $this->build_provenance(),
		);

		if ( ! empty( $categories ) ) {
			$data['categories'] = $categories;
		}
		if ( ! empty( $tags ) ) {
			$data['tags'] = $tags;
		}

		$data     = $this->sign_response( $data );
		$response = rest_ensure_response( $data );
		$response->header( 'Access-Control-Allow-Origin', '*' );
		return $response;
	}

	/**
	 * Build freshness metadata for a post.
	 *
	 * Tells AI agents when this content expires and should be re-fetched.
	 * Creates an incentive to return for fresh content rather than caching forever.
	 *
	 * @param WP_Post $post The post to build freshness for.
	 * @return array Freshness metadata.
	 */
	private function build_freshness( $post ) {
		$modified = strtotime( $post->post_modified_gmt );
		$age_days = ( time() - $modified ) / DAY_IN_SECONDS;

		// Adaptive freshness: recently modified content expires sooner.
		if ( $age_days < 1 ) {
			$max_age = 3600;         // 1 hour — actively being edited.
			$policy  = 'real-time';
		} elseif ( $age_days < 7 ) {
			$max_age = 86400;        // 1 day — recent content.
			$policy  = 'daily';
		} elseif ( $age_days < 30 ) {
			$max_age = 604800;       // 1 week — settling down.
			$policy  = 'weekly';
		} else {
			$max_age = 2592000;      // 30 days — stable content.
			$policy  = 'monthly';
		}

		return array(
			'maxAge'        => $max_age,
			'freshUntil'    => gmdate( 'c', time() + $max_age ),
			'refreshPolicy' => $policy,
			'lastModified'  => get_post_modified_time( 'c', true, $post ),
			'contentAge'    => round( $age_days, 1 ) . ' days',
		);
	}

	/**
	 * Build provenance metadata block.
	 *
	 * This block travels with every response so that even if the content
	 * is scraped, cached, or trained on, the origin is embedded.
	 *
	 * @return array Provenance metadata.
	 */
	private function build_provenance() {
		return array(
			'origin'      => wp_parse_url( home_url(), PHP_URL_HOST ),
			'servedAt'    => gmdate( 'c' ),
			'servedBy'    => 'rootz-ai-discovery/' . ROOTZ_AI_DISCOVERY_VERSION,
			'signer'      => Rootz_Signer::stored_address() ? Rootz_Signer::stored_address() : 'none',
			'specVersion' => ROOTZ_AI_DISCOVERY_SPEC,
			'standard'    => 'https://rootz.global/ai-discovery',
		);
	}

	/**
	 * Sign a data array and append a _signature block.
	 *
	 * If the plugin wallet + GMP are available, produces a full ECDSA signature.
	 * Otherwise, produces a hash-only attestation (content integrity without crypto proof).
	 *
	 * @param array $data The data to sign (will NOT be modified in place).
	 * @return array The data with _signature appended.
	 */
	private function sign_response( $data ) {
		$signer = new Rootz_Signer();
		if ( $signer->has_key() && Rootz_Signer::signing_available() ) {
			$data['_signature'] = $signer->sign_content( $data );
		} else {
			$content_json       = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			$data['_signature'] = array(
				'contentHash'   => 'sha256:' . hash( 'sha256', $content_json ),
				'signedAt'      => gmdate( 'c' ),
				'method'        => 'hash-only',
				'authorization' => 'none',
			);
		}
		return $data;
	}
}
