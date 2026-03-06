<?php
/**
 * Self-hosted plugin update checker.
 *
 * Hooks into WordPress's native update system to check rootz.global
 * for new plugin versions. Updates appear in Dashboard > Updates
 * just like any WordPress.org-hosted plugin.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rootz_Updater {

    /**
     * The plugin slug (directory name).
     *
     * @var string
     */
    private $plugin_slug = 'rootz-ai-discovery';

    /**
     * The plugin basename (e.g., rootz-ai-discovery/rootz-ai-discovery.php).
     *
     * @var string
     */
    private $plugin_basename;

    /**
     * URL of the remote update manifest.
     *
     * @var string
     */
    private $update_url = 'https://rootz.global/api/plugin/update.json';

    /**
     * Transient key for caching the remote check.
     *
     * @var string
     */
    private $cache_key = 'rootz_update_check';

    /**
     * Cache TTL in seconds (12 hours).
     *
     * @var int
     */
    private $cache_ttl = 43200;

    /**
     * Constructor — register WordPress hooks.
     *
     * @param string $plugin_file Full path to the main plugin file.
     */
    public function __construct( $plugin_file ) {
        $this->plugin_basename = plugin_basename( $plugin_file );

        // Skip self-hosted updater when installed from WordPress.org.
        // WP.org provides its own update mechanism — running both causes conflicts.
        $wp_updates = get_site_transient( 'update_plugins' );
        if ( $wp_updates && (
            isset( $wp_updates->response[ $this->plugin_basename ] ) ||
            isset( $wp_updates->no_update[ $this->plugin_basename ] )
        ) ) {
            // WP.org already tracks this plugin — let it handle updates.
            return;
        }

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
        add_action( 'upgrader_process_complete', array( $this, 'clear_cache' ), 10, 2 );
    }

    /**
     * Check for updates and inject into the update_plugins transient.
     *
     * Called by WordPress when it refreshes the plugin update list.
     *
     * @param object $transient The update_plugins transient data.
     * @return object Modified transient with our update info (if available).
     */
    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $remote = $this->get_remote_data();
        if ( ! $remote ) {
            return $transient;
        }

        $current_version = ROOTZ_AI_DISCOVERY_VERSION;

        if ( version_compare( $remote->version, $current_version, '>' ) ) {
            $update = new stdClass();
            $update->slug        = $this->plugin_slug;
            $update->plugin      = $this->plugin_basename;
            $update->new_version = $remote->version;
            $update->url         = isset( $remote->homepage ) ? $remote->homepage : 'https://discover.rootz.global/';
            $update->package     = $remote->download_url;
            $update->tested      = isset( $remote->tested ) ? $remote->tested : '';
            $update->requires    = isset( $remote->requires ) ? $remote->requires : '6.0';
            $update->requires_php = isset( $remote->requires_php ) ? $remote->requires_php : '7.4';

            if ( isset( $remote->icons ) ) {
                $update->icons = (array) $remote->icons;
            }

            if ( isset( $remote->banners ) ) {
                $update->banners = (array) $remote->banners;
            }

            $transient->response[ $this->plugin_basename ] = $update;
        } else {
            // No update — report to WordPress that we checked (removes false positives).
            $no_update = new stdClass();
            $no_update->slug        = $this->plugin_slug;
            $no_update->plugin      = $this->plugin_basename;
            $no_update->new_version = $current_version;
            $no_update->url         = isset( $remote->homepage ) ? $remote->homepage : 'https://discover.rootz.global/';
            $no_update->package     = '';

            $transient->no_update[ $this->plugin_basename ] = $no_update;
        }

        return $transient;
    }

    /**
     * Provide plugin details for the "View Details" popup in Dashboard > Updates.
     *
     * @param false|object|array $result The result object or array.
     * @param string             $action The type of information being requested.
     * @param object             $args   Plugin API arguments.
     * @return false|object Plugin info or false if not our plugin.
     */
    public function plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action ) {
            return $result;
        }

        if ( ! isset( $args->slug ) || $args->slug !== $this->plugin_slug ) {
            return $result;
        }

        $remote = $this->get_remote_data();
        if ( ! $remote ) {
            return $result;
        }

        $info = new stdClass();
        $info->name          = $remote->name;
        $info->slug          = $this->plugin_slug;
        $info->version       = $remote->version;
        $info->author        = '<a href="https://rootz.global">Rootz Corp</a>';
        $info->author_profile = 'https://rootz.global';
        $info->homepage      = isset( $remote->homepage ) ? $remote->homepage : 'https://discover.rootz.global/';
        $info->requires      = isset( $remote->requires ) ? $remote->requires : '6.0';
        $info->requires_php  = isset( $remote->requires_php ) ? $remote->requires_php : '7.4';
        $info->tested        = isset( $remote->tested ) ? $remote->tested : '';
        $info->download_link = $remote->download_url;
        $info->last_updated  = isset( $remote->last_updated ) ? $remote->last_updated : '';

        if ( isset( $remote->sections ) ) {
            $info->sections = (array) $remote->sections;
        }

        if ( isset( $remote->banners ) ) {
            $info->banners = (array) $remote->banners;
        }

        return $info;
    }

    /**
     * Clear the update cache after an upgrade completes.
     *
     * @param WP_Upgrader $upgrader Upgrader instance.
     * @param array       $options  Upgrade info (action, type, plugins).
     */
    public function clear_cache( $upgrader, $options ) {
        if (
            isset( $options['action'] ) && 'update' === $options['action'] &&
            isset( $options['type'] ) && 'plugin' === $options['type'] &&
            isset( $options['plugins'] ) && is_array( $options['plugins'] )
        ) {
            if ( in_array( $this->plugin_basename, $options['plugins'], true ) ) {
                delete_transient( $this->cache_key );
            }
        }
    }

    /**
     * Fetch and cache the remote update manifest.
     *
     * @return object|false Remote data object or false on failure.
     */
    private function get_remote_data() {
        $cached = get_transient( $this->cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $response = wp_remote_get( $this->update_url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );

        if ( empty( $data ) || ! isset( $data->version ) || ! isset( $data->download_url ) ) {
            return false;
        }

        set_transient( $this->cache_key, $data, $this->cache_ttl );

        return $data;
    }
}
