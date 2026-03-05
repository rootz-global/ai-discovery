<?php
/**
 * Plugin Name:       Rootz AI Discovery
 * Plugin URI:        https://rootz.global/ai-discovery
 * Description:       Make your WordPress site AI-agent-ready. Serves /.well-known/ai with structured identity, policies, content, and WebMCP tools so AI agents can discover, understand, and interact with your site properly.
 * Version:           2.2.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Rootz Corp
 * Author URI:        https://rootz.global
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rootz-ai-discovery
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ROOTZ_AI_DISCOVERY_VERSION', '2.2.1' );
define( 'ROOTZ_AI_DISCOVERY_SPEC', '1.2.0' );
define( 'ROOTZ_AI_DISCOVERY_FILE', __FILE__ );
define( 'ROOTZ_AI_DISCOVERY_DIR', plugin_dir_path( __FILE__ ) );
define( 'ROOTZ_AI_DISCOVERY_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoload plugin classes from includes/ and admin/ directories.
 */
spl_autoload_register( function ( $class ) {
    $prefix = 'Rootz_';
    if ( strpos( $class, $prefix ) !== 0 ) {
        return;
    }

    $file = str_replace( '_', '-', strtolower( $class ) );
    $file = 'class-' . $file . '.php';

    $paths = array(
        ROOTZ_AI_DISCOVERY_DIR . 'includes/' . $file,
        ROOTZ_AI_DISCOVERY_DIR . 'admin/' . $file,
    );

    foreach ( $paths as $path ) {
        if ( file_exists( $path ) ) {
            require_once $path;
            return;
        }
    }
} );

/**
 * Activation: flush rewrite rules so /.well-known/ai works immediately.
 */
function rootz_ai_discovery_activate() {
    // Set default options if not already set.
    $defaults = array(
        'rootz_organization_name'    => get_bloginfo( 'name' ),
        'rootz_organization_tagline' => get_bloginfo( 'description' ),
        'rootz_content_license'      => 'cc-by-4.0',
        'rootz_allow_quoting'        => '1',
        'rootz_allow_training'       => '0',
        'rootz_webmcp_enabled'       => '1',
        'rootz_sector'               => '',
        'rootz_enable_knowledge'     => '1',
        'rootz_enable_feed'          => '1',
        'rootz_enable_content'       => '1',
        'rootz_content_include_pages'        => '1',
        'rootz_content_include_posts'        => '1',
        'rootz_content_include_custom_types' => '0',
        'rootz_content_include_media'        => '1',
        'rootz_content_include_full_text'    => '0',
        'rootz_plugin_wallet'                => '',
        'rootz_owner_identity'               => '',
        'rootz_enable_seo_tags'              => '1',
    );

    foreach ( $defaults as $key => $value ) {
        if ( false === get_option( $key ) ) {
            update_option( $key, $value );
        }
    }

    // Register rewrite rules and flush.
    rootz_ai_discovery_register_rewrites();
    flush_rewrite_rules();

    // Clear any cached data.
    rootz_ai_discovery_clear_all_caches();

    // Generate signing key if GMP is available and no key exists yet.
    if ( Rootz_Signer::has_gmp() ) {
        $signer = new Rootz_Signer();
        if ( ! $signer->has_key() ) {
            $signer->generate_key();
        }
    }

    // Create metrics table.
    Rootz_Metrics::create_table();
}
register_activation_hook( __FILE__, 'rootz_ai_discovery_activate' );

/**
 * Deactivation: clean up rewrite rules.
 */
function rootz_ai_discovery_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'rootz_ai_discovery_deactivate' );

/**
 * Register custom rewrite rules — v1.2 URL hierarchy under /.well-known/ai/.
 */
function rootz_ai_discovery_register_rewrites() {
    // v1.2: Everything under /.well-known/ai/ hierarchy (RFC 8615).
    add_rewrite_rule( '\.well-known/ai/?$', 'index.php?rootz_endpoint=discovery', 'top' );
    add_rewrite_rule( '\.well-known/ai/knowledge/?$', 'index.php?rootz_endpoint=knowledge', 'top' );
    add_rewrite_rule( '\.well-known/ai/feed/?$', 'index.php?rootz_endpoint=feed', 'top' );
    add_rewrite_rule( '\.well-known/ai/policies/?$', 'index.php?rootz_endpoint=policies', 'top' );
    add_rewrite_rule( '\.well-known/ai/content/?$', 'index.php?rootz_endpoint=content', 'top' );
    add_rewrite_rule( '\.well-known/ai/content/pages/?$', 'index.php?rootz_endpoint=content_pages', 'top' );
    add_rewrite_rule( '\.well-known/ai/content/posts/?$', 'index.php?rootz_endpoint=content_posts', 'top' );
    add_rewrite_rule( '\.well-known/ai/content/media/?$', 'index.php?rootz_endpoint=content_media', 'top' );
    add_rewrite_rule( '\.well-known/ai/content/([^/]+)/?$', 'index.php?rootz_endpoint=content_custom&rootz_content_type=$matches[1]', 'top' );

    // llms.txt
    add_rewrite_rule( 'llms\.txt$', 'index.php?rootz_endpoint=llms_txt', 'top' );

    // Prevent WordPress from redirecting .well-known/ai URLs.
    add_filter( 'redirect_canonical', 'rootz_ai_discovery_prevent_redirect', 10, 2 );
}
add_action( 'init', 'rootz_ai_discovery_register_rewrites' );

/**
 * Prevent WordPress canonical redirects for our endpoints.
 */
function rootz_ai_discovery_prevent_redirect( $redirect_url, $requested_url ) {
    if ( get_query_var( 'rootz_endpoint' ) ) {
        return false;
    }
    return $redirect_url;
}

/**
 * Register query vars.
 */
function rootz_ai_discovery_query_vars( $vars ) {
    $vars[] = 'rootz_endpoint';
    $vars[] = 'rootz_content_type';
    return $vars;
}
add_filter( 'query_vars', 'rootz_ai_discovery_query_vars' );

/**
 * Serve JSON with standard headers.
 */
function rootz_ai_discovery_serve_json( $data, $cache_seconds = 3600 ) {
    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'Access-Control-Allow-Origin: *' );
    header( 'Access-Control-Allow-Methods: GET, OPTIONS' );
    header( 'Access-Control-Allow-Headers: Content-Type' );
    header( 'Cache-Control: public, max-age=' . intval( $cache_seconds ) );
    header( 'X-Rootz-AI-Discovery: ' . ROOTZ_AI_DISCOVERY_VERSION );

    echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
    exit;
}

/**
 * Handle all /.well-known/ai/* requests.
 */
function rootz_ai_discovery_template_redirect() {
    $endpoint = get_query_var( 'rootz_endpoint' );
    if ( ! $endpoint ) {
        return;
    }

    switch ( $endpoint ) {
        case 'discovery':
            $generator = new Rootz_Ai_Json();
            rootz_ai_discovery_serve_json( $generator->generate() );
            break;

        case 'knowledge':
            if ( '1' !== get_option( 'rootz_enable_knowledge', '1' ) ) {
                status_header( 404 );
                exit;
            }
            $rest = new Rootz_Rest_Api();
            rootz_ai_discovery_serve_json( $rest->generate_knowledge() );
            break;

        case 'feed':
            if ( '1' !== get_option( 'rootz_enable_feed', '1' ) ) {
                status_header( 404 );
                exit;
            }
            $rest = new Rootz_Rest_Api();
            rootz_ai_discovery_serve_json( $rest->generate_feed(), 1800 );
            break;

        case 'policies':
            $rest = new Rootz_Rest_Api();
            $response = $rest->get_policies();
            // get_policies returns WP_REST_Response — extract data array
            $data = ( $response instanceof WP_REST_Response ) ? $response->get_data() : $response;
            rootz_ai_discovery_serve_json( $data );
            break;

        case 'content':
            if ( '1' !== get_option( 'rootz_enable_content', '0' ) ) {
                status_header( 404 );
                exit;
            }
            $content = new Rootz_Content_Endpoint();
            rootz_ai_discovery_serve_json( $content->generate_all() );
            break;

        case 'content_pages':
            if ( '1' !== get_option( 'rootz_enable_content', '0' ) ) {
                status_header( 404 );
                exit;
            }
            $content = new Rootz_Content_Endpoint();
            rootz_ai_discovery_serve_json( $content->generate_segment( 'pages' ) );
            break;

        case 'content_posts':
            if ( '1' !== get_option( 'rootz_enable_content', '0' ) ) {
                status_header( 404 );
                exit;
            }
            $content = new Rootz_Content_Endpoint();
            rootz_ai_discovery_serve_json( $content->generate_segment( 'posts' ) );
            break;

        case 'content_media':
            if ( '1' !== get_option( 'rootz_enable_content', '0' ) ) {
                status_header( 404 );
                exit;
            }
            $content = new Rootz_Content_Endpoint();
            rootz_ai_discovery_serve_json( $content->generate_segment( 'media' ) );
            break;

        case 'content_custom':
            if ( '1' !== get_option( 'rootz_enable_content', '0' ) ) {
                status_header( 404 );
                exit;
            }
            $type = sanitize_key( get_query_var( 'rootz_content_type' ) );
            $content = new Rootz_Content_Endpoint();
            rootz_ai_discovery_serve_json( $content->generate_segment( $type ) );
            break;

        case 'llms_txt':
            $generator = new Rootz_Llms_Txt();
            header( 'Content-Type: text/plain; charset=utf-8' );
            header( 'Cache-Control: public, max-age=3600' );
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain text output, not HTML context.
            echo $generator->generate();
            exit;
            break;
    }
}
add_action( 'template_redirect', 'rootz_ai_discovery_template_redirect' );

/**
 * Add <link rel="ai-discovery"> and optional SEO meta tags to HTML <head>.
 */
function rootz_ai_discovery_wp_head() {
    // AI Discovery link tag (always).
    $url = home_url( '/.well-known/ai' );
    echo '<link rel="ai-discovery" type="application/json" href="' . esc_url( $url ) . '" title="AI Discovery Standard">' . "\n";

    // SEO tags: meta description, OpenGraph, JSON-LD.
    // Only output if enabled AND no known SEO plugin is active.
    if ( '1' !== get_option( 'rootz_enable_seo_tags', '1' ) ) {
        return;
    }

    $seo_plugins = array(
        'wordpress-seo/wp-seo.php',
        'seo-by-rank-math/rank-math.php',
        'all-in-one-seo-pack/all_in_one_seo_pack.php',
        'wp-seopress/seopress.php',
    );
    foreach ( $seo_plugins as $plugin ) {
        if ( is_plugin_active( $plugin ) ) {
            return; // Let the SEO plugin handle meta tags.
        }
    }

    $org_name    = get_option( 'rootz_organization_name', get_bloginfo( 'name' ) );
    $description = get_option( 'rootz_ai_summary', '' );
    if ( empty( $description ) ) {
        $description = get_option( 'rootz_organization_tagline', get_bloginfo( 'description' ) );
    }

    // Meta description (all pages).
    if ( ! empty( $description ) ) {
        echo '<meta name="description" content="' . esc_attr( wp_trim_words( wp_strip_all_tags( $description ), 30 ) ) . '">' . "\n";
    }

    // OpenGraph + JSON-LD (front page only to avoid duplication on every page).
    if ( is_front_page() || is_home() ) {
        echo '<meta property="og:title" content="' . esc_attr( $org_name ) . '">' . "\n";
        if ( ! empty( $description ) ) {
            echo '<meta property="og:description" content="' . esc_attr( wp_trim_words( wp_strip_all_tags( $description ), 30 ) ) . '">' . "\n";
        }
        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:url" content="' . esc_url( home_url( '/' ) ) . '">' . "\n";

        $site_icon_id = get_option( 'site_icon' );
        if ( $site_icon_id ) {
            $icon_url = wp_get_attachment_image_url( $site_icon_id, 'full' );
            if ( $icon_url ) {
                echo '<meta property="og:image" content="' . esc_url( $icon_url ) . '">' . "\n";
            }
        }

        // JSON-LD Organization schema.
        $jsonld = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'Organization',
            'name'        => $org_name,
            'url'         => home_url( '/' ),
        );
        if ( ! empty( $description ) ) {
            $jsonld['description'] = wp_strip_all_tags( $description );
        }
        $sector = get_option( 'rootz_sector', '' );
        if ( empty( $sector ) ) {
            $sector = get_option( 'rootz_organization_sector', '' );
        }
        if ( ! empty( $sector ) ) {
            $jsonld['knowsAbout'] = array_map( 'trim', explode( ',', $sector ) );
        }
        $contact_email = get_option( 'rootz_contact_email', get_option( 'admin_email', '' ) );
        if ( ! empty( $contact_email ) ) {
            $jsonld['contactPoint'] = array(
                '@type' => 'ContactPoint',
                'email' => $contact_email,
            );
        }
        if ( $site_icon_id ) {
            $icon_url = wp_get_attachment_image_url( $site_icon_id, 'full' );
            if ( $icon_url ) {
                $jsonld['logo'] = $icon_url;
            }
        }

        echo '<script type="application/ld+json">' . wp_json_encode( $jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
    }
}
add_action( 'wp_head', 'rootz_ai_discovery_wp_head' );

/**
 * Add Link HTTP header to all responses.
 */
function rootz_ai_discovery_send_headers() {
    if ( headers_sent() ) {
        return;
    }
    $url = home_url( '/.well-known/ai' );
    header( 'Link: <' . esc_url( $url ) . '>; rel="ai-discovery"; type="application/json"' );
}
add_action( 'send_headers', 'rootz_ai_discovery_send_headers' );

/**
 * Enqueue WebMCP tools script on frontend if enabled.
 */
function rootz_ai_discovery_enqueue_scripts() {
    if ( '1' !== get_option( 'rootz_webmcp_enabled', '1' ) ) {
        return;
    }
    wp_enqueue_script(
        'rootz-webmcp-tools',
        ROOTZ_AI_DISCOVERY_URL . 'public/webmcp-tools.js',
        array(),
        ROOTZ_AI_DISCOVERY_VERSION,
        array(
            'in_footer' => true,
            'strategy'  => 'defer',
        )
    );

    wp_localize_script( 'rootz-webmcp-tools', 'rootzAiDiscovery', array(
        'siteUrl'    => home_url(),
        'apiUrl'     => rest_url( 'rootz/v1/' ),
        'wellKnown'  => home_url( '/.well-known/ai' ),
        'siteName'   => get_option( 'rootz_organization_name', get_bloginfo( 'name' ) ),
        'version'    => ROOTZ_AI_DISCOVERY_VERSION,
    ) );
}
add_action( 'wp_enqueue_scripts', 'rootz_ai_discovery_enqueue_scripts' );

/**
 * Register REST API routes (also accessible via /wp-json/rootz/v1/).
 */
function rootz_ai_discovery_register_rest_routes() {
    $rest = new Rootz_Rest_Api();
    $rest->register_routes();
}
add_action( 'rest_api_init', 'rootz_ai_discovery_register_rest_routes' );

/**
 * Log REST API requests to rootz/v1/* endpoints for AI access metrics.
 */
function rootz_ai_discovery_log_rest_request( $response, $handler, $request ) {
    $route = $request->get_route();
    if ( strpos( $route, '/rootz/v1/' ) === 0 ) {
        $endpoint = str_replace( '/rootz/v1/', '', $route );
        $status   = $response instanceof WP_REST_Response ? $response->get_status() : 200;
        Rootz_Metrics::schedule_log( $endpoint, $status );
    }
    return $response;
}
add_filter( 'rest_post_dispatch', 'rootz_ai_discovery_log_rest_request', 10, 3 );

/**
 * Log /.well-known/ai/* requests for metrics.
 */
function rootz_ai_discovery_log_wellknown_request() {
    $endpoint = get_query_var( 'rootz_endpoint' );
    if ( $endpoint ) {
        Rootz_Metrics::schedule_log( 'well-known/' . $endpoint );
    }
}
add_action( 'template_redirect', 'rootz_ai_discovery_log_wellknown_request', 1 );

/**
 * Schedule daily metrics pruning.
 */
function rootz_ai_discovery_schedule_prune() {
    if ( ! wp_next_scheduled( 'rootz_metrics_prune' ) ) {
        wp_schedule_event( time(), 'daily', 'rootz_metrics_prune' );
    }
}
add_action( 'init', 'rootz_ai_discovery_schedule_prune' );
add_action( 'rootz_metrics_prune', array( 'Rootz_Metrics', 'prune' ) );

/**
 * Clear all transient caches.
 *
 * Does NOT auto-sign the manifest. Instead, flags it as needing review.
 * The admin must explicitly approve and sign via the admin notice or Viewer tab.
 * This prevents FTP-corrupted or injected content from being silently signed.
 */
function rootz_ai_discovery_clear_all_caches() {
    delete_transient( 'rootz_ai_json_cache' );
    delete_transient( 'rootz_knowledge_cache' );
    delete_transient( 'rootz_feed_cache' );
    delete_transient( 'rootz_content_cache' );
    delete_transient( 'rootz_llms_txt_cache' );

    // Flag that content has changed and the manifest needs re-signing.
    update_option( 'rootz_manifest_needs_signing', '1', false );
}

/**
 * Generate, sign, and store the manifest as an admin-approved snapshot.
 * Called when an admin triggers a cache clear (settings save, page edit, manual refresh).
 */
function rootz_ai_discovery_sign_manifest() {
    $generator = new Rootz_Ai_Json();
    $data = $generator->generate_unsigned();

    // Sign with plugin wallet.
    $signer = new Rootz_Signer();
    if ( $signer->has_key() && Rootz_Signer::has_gmp() ) {
        $data['_signature'] = $signer->sign_content( $data );
        $data['_signature']['approvedBy'] = 'admin';
    } else {
        $content_hash = hash( 'sha256', wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
        $data['_signature'] = array(
            'contentHash'   => 'sha256:' . $content_hash,
            'signedAt'      => gmdate( 'c' ),
            'method'        => 'hash-only',
            'authorization' => 'none',
            'approvedBy'    => 'admin',
        );
    }

    update_option( 'rootz_signed_manifest', $data, false );
    update_option( 'rootz_manifest_signed_at', gmdate( 'c' ), false );

    // Clear the needs-signing flag — admin has approved.
    delete_option( 'rootz_manifest_needs_signing' );

    // Clear the ai.json transient so the signed manifest is served immediately.
    delete_transient( 'rootz_ai_json_cache' );
}

/**
 * Clear caches when relevant options change.
 */
function rootz_ai_discovery_clear_cache( $option ) {
    if ( strpos( $option, 'rootz_' ) === 0 || in_array( $option, array( 'blogname', 'blogdescription', 'siteurl', 'home' ), true ) ) {
        rootz_ai_discovery_clear_all_caches();
    }
}
add_action( 'updated_option', 'rootz_ai_discovery_clear_cache' );

/**
 * Clear caches on post publish/update.
 */
function rootz_ai_discovery_clear_content_cache( $post_id ) {
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }
    rootz_ai_discovery_clear_all_caches();
}
add_action( 'save_post', 'rootz_ai_discovery_clear_content_cache' );

/**
 * Show admin notice when manifest needs signing (content changed since last sign).
 *
 * This is the security mechanism: content changes via FTP/hack won't be auto-signed.
 * The admin must see and approve changes before the manifest is re-signed.
 */
function rootz_ai_discovery_manifest_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $needs_signing = get_option( 'rootz_manifest_needs_signing', '0' );
    $has_signed    = get_option( 'rootz_signed_manifest', false );
    $signed_at     = get_option( 'rootz_manifest_signed_at', '' );

    // No notice needed if manifest is up to date.
    if ( '1' !== $needs_signing && ! empty( $has_signed ) ) {
        return;
    }

    $sign_url = wp_nonce_url(
        add_query_arg(
            array( 'page' => 'rootz-ai-discovery', 'tab' => 'viewer', 'rootz_sign_manifest' => '1' ),
            admin_url( 'options-general.php' )
        ),
        'rootz_sign_manifest'
    );

    if ( empty( $has_signed ) ) {
        // Never signed — first install.
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <strong>AI Discovery:</strong>
                <?php esc_html_e( 'Your AI manifest has not been signed yet. Review your settings and sign to enable verified AI discovery.', 'rootz-ai-discovery' ); ?>
                <a href="<?php echo esc_url( $sign_url ); ?>" class="button button-primary" style="margin-left: 10px;">
                    <?php esc_html_e( 'Review & Sign Manifest', 'rootz-ai-discovery' ); ?>
                </a>
            </p>
        </div>
        <?php
    } else {
        // Content changed since last signing.
        ?>
        <div class="notice notice-warning">
            <p>
                <strong>AI Discovery:</strong>
                <?php
                printf(
                    /* translators: %s: date and time when the manifest was last signed */
                    esc_html__( 'Content changes detected since the manifest was last signed (%s). Please review and approve the updated manifest.', 'rootz-ai-discovery' ),
                    esc_html( $signed_at ? wp_date( 'M j, Y g:i A', strtotime( $signed_at ) ) : 'unknown' )
                );
                ?>
                <a href="<?php echo esc_url( $sign_url ); ?>" class="button button-primary" style="margin-left: 10px;">
                    <?php esc_html_e( 'Approve & Sign Manifest', 'rootz-ai-discovery' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
add_action( 'admin_notices', 'rootz_ai_discovery_manifest_notice' );

/**
 * Self-hosted update checker — hooks into WordPress native update system.
 * Checks rootz.global/api/plugin/update.json every 12 hours.
 * Not included in WordPress.org distribution (WP.org provides its own updates).
 */
if ( file_exists( ROOTZ_AI_DISCOVERY_DIR . 'includes/class-rootz-updater.php' ) ) {
    new Rootz_Updater( ROOTZ_AI_DISCOVERY_FILE );
}

/**
 * Load admin settings page.
 */
if ( is_admin() ) {
    require_once ROOTZ_AI_DISCOVERY_DIR . 'admin/class-rootz-admin.php';
    new Rootz_Admin();
}
