<?php
/**
 * Admin settings page for Rootz AI Discovery v1.2.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rootz_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_init', array( $this, 'handle_auto_populate' ) );
        add_action( 'admin_init', array( $this, 'handle_generate_key' ) );
        add_action( 'admin_init', array( $this, 'handle_refresh_manifest' ) );
        add_action( 'admin_init', array( $this, 'handle_sign_manifest' ) );
        add_action( 'admin_init', array( $this, 'handle_dismiss_quickstart' ) );
        add_action( 'admin_init', array( $this, 'handle_refresh_license' ) );
        add_action( 'admin_init', array( $this, 'handle_register_site' ) );
        add_action( 'update_option_rootz_registry_consent', array( $this, 'sync_registry_consent' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function add_menu_page() {
        add_options_page(
            __( 'AI Discovery', 'rootz-ai-discovery' ),
            __( 'AI Discovery', 'rootz-ai-discovery' ),
            'manage_options',
            'rootz-ai-discovery',
            array( $this, 'render_page' )
        );
    }

    public function register_settings() {
        // ── Identity tab ────────────────────────────────────────────────
        $identity_text = array(
            'rootz_organization_name', 'rootz_organization_tagline', 'rootz_sector',
            'rootz_tagline', 'rootz_legal_name', 'rootz_founded', 'rootz_headquarters',
            'rootz_digital_name', 'rootz_blockchain', 'rootz_identity_contract',
            'rootz_contact_url', 'rootz_contact_operator',
        );
        foreach ( $identity_text as $field ) {
            register_setting( 'rootz_identity', $field, array(
                'type' => 'string', 'sanitize_callback' => 'sanitize_text_field',
            ) );
        }
        foreach ( array( 'rootz_contact_email', 'rootz_contact_ai_email', 'rootz_contact_privacy_email' ) as $field ) {
            register_setting( 'rootz_identity', $field, array(
                'type' => 'string', 'sanitize_callback' => 'sanitize_email',
            ) );
        }
        register_setting( 'rootz_identity', 'rootz_core_concepts', array(
            'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field',
        ) );
        register_setting( 'rootz_identity', 'rootz_ai_summary', array(
            'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field',
        ) );

        // ── Content tab ─────────────────────────────────────────────────
        $content_checkboxes = array(
            'rootz_enable_content', 'rootz_content_include_pages', 'rootz_content_include_posts',
            'rootz_content_include_custom_types', 'rootz_content_include_media',
            'rootz_content_include_full_text',
        );
        foreach ( $content_checkboxes as $field ) {
            register_setting( 'rootz_content', $field, array(
                'type' => 'string', 'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
            ) );
        }
        foreach ( array( 'rootz_content_posts_limit', 'rootz_content_media_limit' ) as $field ) {
            register_setting( 'rootz_content', $field, array(
                'type' => 'integer', 'sanitize_callback' => 'absint',
            ) );
        }

        // ── Policies tab ────────────────────────────────────────────────
        register_setting( 'rootz_policies', 'rootz_content_license', array(
            'type' => 'string', 'sanitize_callback' => array( $this, 'sanitize_license' ),
        ) );
        foreach ( array( 'rootz_allow_quoting', 'rootz_allow_training' ) as $field ) {
            register_setting( 'rootz_policies', $field, array(
                'type' => 'string', 'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
            ) );
        }

        // ── Tools tab ───────────────────────────────────────────────────
        $tools_checkboxes = array(
            'rootz_webmcp_enabled', 'rootz_enable_knowledge', 'rootz_enable_feed', 'rootz_enable_content',
        );
        foreach ( $tools_checkboxes as $field ) {
            register_setting( 'rootz_tools', $field, array(
                'type' => 'string', 'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
            ) );
        }

        // ── Account tab ─────────────────────────────────────────────────
        foreach ( array( 'rootz_owner_identity', 'rootz_plugin_wallet', 'rootz_ai_api_key' ) as $field ) {
            register_setting( 'rootz_account', $field, array(
                'type' => 'string', 'sanitize_callback' => 'sanitize_text_field',
            ) );
        }
        foreach ( array( 'rootz_enable_seo_tags', 'rootz_registry_consent' ) as $field ) {
            register_setting( 'rootz_account', $field, array(
                'type' => 'string', 'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
            ) );
        }
    }

    /**
     * Handle auto-populate form submission.
     * Reads existing WordPress data and fills in empty AI Discovery fields.
     * Won't overwrite fields the user has already populated.
     */
    public function handle_auto_populate() {
        if ( ! isset( $_POST['rootz_do_auto_populate'] ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['rootz_auto_populate_nonce'] ?? '', 'rootz_auto_populate' ) ) {
            return;
        }

        // Gather site data for both basic and AI-enhanced populate.
        $site_name  = get_bloginfo( 'name' );
        $site_desc  = get_bloginfo( 'description' );
        $about_page = null;
        $about_text = '';
        $about_html = '';

        foreach ( array( 'about', 'about-us', 'who-we-are', 'company' ) as $slug ) {
            $p = get_page_by_path( $slug );
            if ( $p && 'publish' === $p->post_status ) {
                $about_page = $p;
                $about_html = $p->post_content;
                $about_text = wp_strip_all_tags( $p->post_content );
                break;
            }
        }

        // Also look for contact page text (both plain text and raw HTML for extraction).
        $contact_text = '';
        $contact_html = '';
        foreach ( array( 'contact', 'contact-us', 'get-in-touch' ) as $slug ) {
            $p = get_page_by_path( $slug );
            if ( $p && 'publish' === $p->post_status ) {
                $contact_html = $p->post_content;
                $contact_text = wp_strip_all_tags( $p->post_content );
                break;
            }
        }

        $categories  = get_categories( array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 15, 'hide_empty' => true, 'exclude' => array( 1 ) ) );
        $cat_names   = wp_list_pluck( $categories, 'name' );
        $page_titles = get_pages( array( 'post_status' => 'publish', 'number' => 20 ) );
        $page_titles = wp_list_pluck( $page_titles ?: array(), 'post_title' );

        // Check if AI generation is available.
        $ai = new Rootz_Ai_Generator();
        $use_ai = $ai->is_available();

        // Organization Name — from site title.
        if ( empty( get_option( 'rootz_organization_name', '' ) ) ) {
            update_option( 'rootz_organization_name', $site_name );
        }

        // Tagline / Mission — from site description.
        if ( empty( get_option( 'rootz_organization_tagline', '' ) ) ) {
            update_option( 'rootz_organization_tagline', $site_desc );
        }

        // AI Summary.
        if ( empty( get_option( 'rootz_ai_summary', '' ) ) ) {
            if ( $use_ai ) {
                // AI-generated summary from all available site content.
                $summary = $ai->generate_summary( $site_name, $site_desc, wp_trim_words( $about_text, 300 ), $page_titles );
                if ( ! empty( $summary ) ) {
                    update_option( 'rootz_ai_summary', $summary );
                }
            } elseif ( ! empty( $about_text ) ) {
                // Basic fallback: first 80 words of about page.
                update_option( 'rootz_ai_summary', wp_trim_words( $about_text, 80 ) );
            }
        }

        // Core Concepts.
        if ( empty( get_option( 'rootz_core_concepts', '' ) ) ) {
            if ( $use_ai ) {
                // AI-generated concepts from site content.
                $concepts = $ai->generate_concepts( $site_name, wp_trim_words( $about_text, 300 ), $cat_names, $page_titles );
                if ( ! empty( $concepts ) ) {
                    update_option( 'rootz_core_concepts', $concepts );
                }
            } else {
                // Basic fallback: categories with descriptions.
                $concepts = array();
                foreach ( $categories as $cat ) {
                    if ( ! empty( $cat->description ) ) {
                        $concepts[] = $cat->name . ': ' . $cat->description;
                    }
                }
                if ( ! empty( $concepts ) ) {
                    update_option( 'rootz_core_concepts', implode( "\n", $concepts ) );
                }
            }
        }

        // Contact Email — from admin email.
        if ( empty( get_option( 'rootz_contact_email', '' ) ) ) {
            update_option( 'rootz_contact_email', get_option( 'admin_email' ) );
        }

        // Contact URL — from contact page if it exists.
        if ( empty( get_option( 'rootz_contact_url', '' ) ) ) {
            foreach ( array( 'contact', 'contact-us', 'get-in-touch' ) as $slug ) {
                $p = get_page_by_path( $slug );
                if ( $p && 'publish' === $p->post_status ) {
                    update_option( 'rootz_contact_url', get_permalink( $p ) );
                    break;
                }
            }
        }

        // Identity fields — AI extraction from about + contact pages.
        $identity_fields = array( 'rootz_legal_name', 'rootz_sector', 'rootz_founded', 'rootz_headquarters' );
        $any_identity_empty = false;
        foreach ( $identity_fields as $field ) {
            if ( empty( get_option( $field, '' ) ) ) {
                $any_identity_empty = true;
                break;
            }
        }

        if ( $any_identity_empty && ( ! empty( $about_text ) || ! empty( $contact_text ) ) ) {
            $identity = array();

            // Try AI extraction first.
            if ( $use_ai ) {
                $identity = $ai->generate_identity( $site_name, $about_text, $contact_text, $page_titles );
            }

            // Fall back to basic regex extraction if AI returned nothing.
            if ( empty( $identity ) ) {
                $identity = self::extract_identity_basic( $about_html . "\n" . $contact_html );
            }

            $field_map = array(
                'legalName'    => 'rootz_legal_name',
                'sector'       => 'rootz_sector',
                'founded'      => 'rootz_founded',
                'headquarters' => 'rootz_headquarters',
            );

            foreach ( $field_map as $key => $option ) {
                if ( ! empty( $identity[ $key ] ) && empty( get_option( $option, '' ) ) ) {
                    update_option( $option, sanitize_text_field( $identity[ $key ] ) );
                }
            }
        }

        // Clear caches so viewer shows fresh data.
        rootz_ai_discovery_clear_all_caches();

        // Redirect back to the Account tab with success flag.
        wp_safe_redirect( add_query_arg(
            array( 'page' => 'rootz-ai-discovery', 'tab' => 'account', 'auto-populate' => '1' ),
            admin_url( 'options-general.php' )
        ) );
        exit;
    }

    /**
     * Handle signing key generation.
     */
    public function handle_generate_key() {
        if ( ! isset( $_POST['rootz_do_generate_key'] ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['rootz_generate_key_nonce'] ?? '', 'rootz_generate_key' ) ) {
            return;
        }

        if ( ! Rootz_Signer::has_gmp() ) {
            return;
        }

        $signer = new Rootz_Signer();
        if ( ! $signer->has_key() ) {
            $signer->generate_key();
        }

        rootz_ai_discovery_clear_all_caches();

        wp_safe_redirect( add_query_arg(
            array( 'page' => 'rootz-ai-discovery', 'tab' => 'account', 'key-generated' => '1' ),
            admin_url( 'options-general.php' )
        ) );
        exit;
    }

    /**
     * Handle manifest refresh from the Viewer tab (cache clear only, no signing).
     */
    public function handle_refresh_manifest() {
        if ( ! isset( $_GET['refresh'] ) || '1' !== $_GET['refresh'] ) {
            return;
        }
        if ( ! isset( $_GET['page'] ) || 'rootz-ai-discovery' !== $_GET['page'] ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        rootz_ai_discovery_clear_all_caches();
    }

    /**
     * Handle explicit manifest signing from admin notice or Viewer tab.
     * This is the admin's deliberate approval — the security gate.
     */
    public function handle_sign_manifest() {
        if ( ! isset( $_GET['rootz_sign_manifest'] ) || '1' !== $_GET['rootz_sign_manifest'] ) {
            return;
        }
        if ( ! isset( $_GET['page'] ) || 'rootz-ai-discovery' !== $_GET['page'] ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'rootz_sign_manifest' ) ) {
            wp_die( 'Security check failed.' );
        }

        // Clear caches first so the manifest reflects current content.
        delete_transient( 'rootz_ai_json_cache' );
        delete_transient( 'rootz_knowledge_cache' );
        delete_transient( 'rootz_feed_cache' );
        delete_transient( 'rootz_content_cache' );
        delete_transient( 'rootz_llms_txt_cache' );

        // Sign the manifest — this is the admin-approved action.
        rootz_ai_discovery_sign_manifest();

        // Redirect back to Viewer tab with success flag.
        wp_safe_redirect( add_query_arg(
            array( 'page' => 'rootz-ai-discovery', 'tab' => 'viewer', 'signed' => '1' ),
            admin_url( 'options-general.php' )
        ) );
        exit;
    }

    /**
     * Basic identity extraction from page content (no AI fallback).
     * Handles both HTML tables (<td>Label</td><td>Value</td>) and
     * plain text patterns ("Legal Name: X").
     *
     * @param string $html Combined about + contact page HTML content.
     * @return array Extracted fields.
     */
    private static function extract_identity_basic( $html ) {
        $identity = array();

        // Pattern 1: HTML table rows — <td>Label</td><td>Value</td>
        $table_patterns = array(
            'legalName'    => '/Legal\s*Name/i',
            'sector'       => '/(?:Sector|Industry)/i',
            'founded'      => '/(?:Founded|Established)/i',
            'headquarters' => '/(?:Headquarters|Location|Located)/i',
        );

        // Extract all table row label/value pairs.
        if ( preg_match_all( '/<tr[^>]*>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>/is', $html, $rows, PREG_SET_ORDER ) ) {
            foreach ( $rows as $row ) {
                $label = wp_strip_all_tags( $row[1] );
                $value = wp_strip_all_tags( $row[2] );
                if ( empty( $value ) ) {
                    continue;
                }
                foreach ( $table_patterns as $key => $pattern ) {
                    if ( ! isset( $identity[ $key ] ) && preg_match( $pattern, $label ) ) {
                        $identity[ $key ] = trim( $value );
                    }
                }
            }
        }

        // Pattern 2: Plain text — "Label: Value" or "Label — Value".
        $text = wp_strip_all_tags( str_replace( array( '</td>', '</tr>', '</li>' ), "\n", $html ) );

        if ( ! isset( $identity['legalName'] ) && preg_match( '/Legal\s+Name\s*[:\-–—]\s*(.+)/i', $text, $m ) ) {
            $identity['legalName'] = trim( $m[1] );
        }
        if ( ! isset( $identity['sector'] ) && preg_match( '/(?:Sector|Industry)\s*[:\-–—]\s*(.+)/i', $text, $m ) ) {
            $identity['sector'] = trim( $m[1] );
        }
        if ( ! isset( $identity['founded'] ) && preg_match( '/(?:Founded|Established)\s*[:\-–—]?\s*((?:19|20)\d{2})/i', $text, $m ) ) {
            $identity['founded'] = $m[1];
        }
        if ( ! isset( $identity['headquarters'] ) && preg_match( '/(?:Headquarters|Located|Location)\s*[:\-–—]\s*(.+)/i', $text, $m ) ) {
            $identity['headquarters'] = trim( $m[1] );
        }

        return $identity;
    }

    public function sanitize_license( $value ) {
        $allowed = array( 'cc-by-4.0', 'cc-by-sa-4.0', 'cc-by-nc-4.0', 'cc-by-nc-sa-4.0', 'cc0', 'all-rights-reserved' );
        return in_array( $value, $allowed, true ) ? $value : 'all-rights-reserved';
    }

    public function sanitize_checkbox( $value ) {
        return $value ? '1' : '0';
    }

    /**
     * Handle Quick Start Guide dismissal.
     */
    public function handle_dismiss_quickstart() {
        if ( ! isset( $_GET['rootz_dismiss_quickstart'] ) || '1' !== $_GET['rootz_dismiss_quickstart'] ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'rootz_dismiss_quickstart' ) ) {
            return;
        }
        update_option( 'rootz_quickstart_dismissed', '1', false );
        wp_safe_redirect( add_query_arg(
            array( 'page' => 'rootz-ai-discovery', 'tab' => 'viewer' ),
            admin_url( 'options-general.php' )
        ) );
        exit;
    }

    /**
     * Handle license status refresh.
     */
    public function handle_refresh_license() {
        if ( ! isset( $_POST['rootz_do_refresh_license'] ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['rootz_refresh_license_nonce'] ?? '', 'rootz_refresh_license' ) ) {
            return;
        }

        Rootz_License::clear_cache();
        Rootz_License::get_cached_status( true );

        wp_safe_redirect( add_query_arg(
            array( 'page' => 'rootz-ai-discovery', 'tab' => 'account', 'license-refreshed' => '1' ),
            admin_url( 'options-general.php' )
        ) );
        exit;
    }

    /**
     * Handle site registration under owner identity license.
     */
    public function handle_register_site() {
        if ( ! isset( $_POST['rootz_do_register_site'] ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['rootz_register_site_nonce'] ?? '', 'rootz_register_site' ) ) {
            return;
        }

        $identity        = Rootz_License::get_owner_identity();
        $signing_address = Rootz_Signer::stored_address();
        $domain          = wp_parse_url( home_url(), PHP_URL_HOST );

        if ( ! empty( $identity ) && ! empty( $signing_address ) ) {
            Rootz_License::register_site( $identity, $signing_address, $domain );
        }

        wp_safe_redirect( add_query_arg(
            array( 'page' => 'rootz-ai-discovery', 'tab' => 'account', 'site-registered' => '1' ),
            admin_url( 'options-general.php' )
        ) );
        exit;
    }

    /**
     * Sync registry consent with rootz.global when the setting changes.
     * Called via update_option hook from __construct.
     */
    public function sync_registry_consent( $old_value, $new_value ) {
        $domain          = wp_parse_url( home_url(), PHP_URL_HOST );
        $signing_address = Rootz_Signer::stored_address();
        $plugin_version  = defined( 'ROOTZ_AI_DISCOVERY_VERSION' ) ? ROOTZ_AI_DISCOVERY_VERSION : '0.0.0';

        if ( empty( $domain ) ) {
            return;
        }

        $endpoint = '1' === $new_value
            ? 'https://rootz.global/api/registry/join'
            : 'https://rootz.global/api/registry/leave';

        $body = array(
            'domain'        => $domain,
            'pluginVersion' => $plugin_version,
            'specVersion'   => '1.2.0',
            'wallet'        => $signing_address ?: '',
        );

        wp_remote_post( $endpoint, array(
            'body'    => wp_json_encode( $body ),
            'headers' => array( 'Content-Type' => 'application/json' ),
            'timeout' => 10,
        ) );
    }

    public function enqueue_assets( $hook ) {
        if ( 'settings_page_rootz-ai-discovery' !== $hook ) {
            return;
        }
        wp_enqueue_style( 'rootz-admin', ROOTZ_AI_DISCOVERY_URL . 'admin/assets/admin.css', array(), ROOTZ_AI_DISCOVERY_VERSION );
        wp_enqueue_script( 'rootz-admin-help', ROOTZ_AI_DISCOVERY_URL . 'admin/assets/admin-help.js', array(), ROOTZ_AI_DISCOVERY_VERSION, true );
    }

    /**
     * Render a help tooltip button + hidden tip content.
     *
     * @param string $text Help text (HTML allowed via wp_kses_post).
     * @param string $id   Unique identifier for accessibility.
     */
    public static function help_tip( $text, $id = '' ) {
        $id_attr   = $id ? ' id="rootz-help-' . esc_attr( $id ) . '"' : '';
        $desc_attr = $id ? ' aria-describedby="rootz-help-' . esc_attr( $id ) . '"' : '';
        ?>
        <button type="button" class="rootz-help-toggle" aria-expanded="false"<?php echo wp_kses( $desc_attr, array() ); ?> title="<?php esc_attr_e( 'More info', 'rootz-ai-discovery' ); ?>">&#8505;</button>
        <div class="rootz-help-tip"<?php echo wp_kses( $id_attr, array() ); ?> role="note">
            <?php echo wp_kses_post( $text ); ?>
        </div>
        <?php
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'viewer';
        $tabs = array(
            'viewer'    => __( 'What AI Sees', 'rootz-ai-discovery' ),
            'identity'  => __( 'Identity', 'rootz-ai-discovery' ),
            'content'   => __( 'Content', 'rootz-ai-discovery' ),
            'policies'  => __( 'Policies', 'rootz-ai-discovery' ),
            'tools'     => __( 'Tools & Preview', 'rootz-ai-discovery' ),
            'analytics' => __( 'Analytics', 'rootz-ai-discovery' ),
            'account'   => __( 'Account & Wallet', 'rootz-ai-discovery' ),
        );

        ?>
        <div class="wrap rootz-admin-wrap">
            <h1><?php esc_html_e( 'Rootz AI Discovery', 'rootz-ai-discovery' ); ?> <small>v<?php echo esc_html( ROOTZ_AI_DISCOVERY_VERSION ); ?></small></h1>
            <p class="rootz-subtitle">
                <?php esc_html_e( 'Make your site AI-agent-ready with structured identity, policies, content, and tools.', 'rootz-ai-discovery' ); ?>
            </p>

            <nav class="nav-tab-wrapper">
                <?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( 'tab', $tab_key, admin_url( 'options-general.php?page=rootz-ai-discovery' ) ) ); ?>"
                       class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html( $tab_label ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php
            switch ( $active_tab ) {
                case 'viewer':
                    include ROOTZ_AI_DISCOVERY_DIR . 'admin/views/settings-viewer.php';
                    break;
                case 'content':
                    include ROOTZ_AI_DISCOVERY_DIR . 'admin/views/settings-content.php';
                    break;
                case 'policies':
                    include ROOTZ_AI_DISCOVERY_DIR . 'admin/views/settings-policies.php';
                    break;
                case 'tools':
                    include ROOTZ_AI_DISCOVERY_DIR . 'admin/views/settings-tools.php';
                    break;
                case 'analytics':
                    include ROOTZ_AI_DISCOVERY_DIR . 'admin/views/settings-analytics.php';
                    break;
                case 'account':
                    include ROOTZ_AI_DISCOVERY_DIR . 'admin/views/settings-account.php';
                    break;
                default:
                    include ROOTZ_AI_DISCOVERY_DIR . 'admin/views/settings-identity.php';
                    break;
            }
            ?>

            <div class="rootz-footer">
                <p>
                    <?php
                    printf(
                        /* translators: %s: link to AI Discovery Standard */
                        esc_html__( 'Part of the %s — the open standard for the AI-readable web.', 'rootz-ai-discovery' ),
                        '<a href="https://rootz.global/ai-discovery" target="_blank" rel="noopener">AI Discovery Standard v1.2</a>'
                    );
                    ?>
                    |
                    <a href="<?php echo esc_url( add_query_arg(
                        array( 'page' => 'rootz-ai-discovery', 'tab' => 'viewer', 'show-guide' => '1' ),
                        admin_url( 'options-general.php' )
                    ) ); ?>"><?php esc_html_e( 'Quick Start Guide', 'rootz-ai-discovery' ); ?></a>
                </p>
            </div>
        </div>
        <?php
    }
}
