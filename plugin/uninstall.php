<?php
/**
 * Uninstall handler — removes all plugin data.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove all plugin options.
$options = array(
    'rootz_organization_name',
    'rootz_organization_tagline',
    'rootz_sector',
    'rootz_tagline',
    'rootz_legal_name',
    'rootz_founded',
    'rootz_headquarters',
    'rootz_contact_email',
    'rootz_contact_url',
    'rootz_digital_name',
    'rootz_blockchain',
    'rootz_identity_contract',
    'rootz_core_concepts',
    'rootz_ai_summary',
    'rootz_content_license',
    'rootz_allow_quoting',
    'rootz_allow_training',
    'rootz_webmcp_enabled',
    'rootz_enable_knowledge',
    'rootz_enable_feed',
    'rootz_enable_content',
    'rootz_content_include_pages',
    'rootz_content_include_posts',
    'rootz_content_include_custom_types',
    'rootz_content_include_media',
    'rootz_content_include_full_text',
    'rootz_content_posts_limit',
    'rootz_content_media_limit',
    'rootz_plugin_wallet',
    'rootz_account_status',
    'rootz_account_tier',
    'rootz_signing_key',
    'rootz_signing_address',
    'rootz_ai_api_key',
);

foreach ( $options as $option ) {
    delete_option( $option );
}

// Remove transients.
delete_transient( 'rootz_ai_json_cache' );
delete_transient( 'rootz_knowledge_cache' );
delete_transient( 'rootz_feed_cache' );
delete_transient( 'rootz_content_cache' );
delete_transient( 'rootz_llms_txt_cache' );

// Flush rewrite rules.
flush_rewrite_rules();
