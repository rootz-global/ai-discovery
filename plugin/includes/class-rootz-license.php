<?php
/**
 * License checking — calls rootz.global license API.
 *
 * Checks license status for the owner identity address.
 * Caches results in a WordPress transient (12 hours).
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rootz_License {

    const API_BASE      = 'https://rootz.global/api/license';
    const CACHE_KEY     = 'rootz_license_status';
    const CACHE_TTL     = 43200; // 12 hours
    const CHECKOUT_BASE = 'https://rootz.global/api/checkout';

    /**
     * Get the stored owner identity address.
     *
     * @return string Owner identity (0x...) or empty string.
     */
    public static function get_owner_identity() {
        return get_option( 'rootz_owner_identity', '' );
    }

    /**
     * Get the current license tier.
     * Returns cached value or 'free' if no identity.
     *
     * @return string Tier: free, standard, or pro.
     */
    public static function get_tier() {
        $status = self::get_cached_status();
        if ( $status && ! empty( $status['tier'] ) ) {
            return $status['tier'];
        }
        return 'free';
    }

    /**
     * Check if the current license is valid (active paid subscription).
     *
     * @return bool
     */
    public static function is_licensed() {
        $status = self::get_cached_status();
        return $status && ! empty( $status['valid'] ) && 'free' !== ( $status['tier'] ?? 'free' );
    }

    /**
     * Get cached license status or fetch fresh.
     *
     * @param bool $force_refresh Skip cache and fetch fresh.
     * @return array|null License status data or null.
     */
    public static function get_cached_status( $force_refresh = false ) {
        $identity = self::get_owner_identity();
        if ( empty( $identity ) ) {
            return null;
        }

        if ( ! $force_refresh ) {
            $cached = get_transient( self::CACHE_KEY );
            if ( false !== $cached ) {
                return $cached;
            }
        }

        return self::fetch_status( $identity );
    }

    /**
     * Fetch license status from rootz.global API.
     *
     * @param string $identity Owner identity address (0x...).
     * @return array|null Decoded JSON response or null on error.
     */
    public static function fetch_status( $identity ) {
        if ( empty( $identity ) || ! preg_match( '/^0x[a-fA-F0-9]{40}$/', $identity ) ) {
            return null;
        }

        $url = self::API_BASE . '/status?' . http_build_query( array( 'identity' => $identity ) );

        $response = wp_remote_get( $url, array(
            'timeout' => 10,
            'headers' => array( 'Accept' => 'application/json' ),
        ) );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            return null;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        if ( ! is_array( $data ) ) {
            return null;
        }

        // Cache the result.
        set_transient( self::CACHE_KEY, $data, self::CACHE_TTL );

        return $data;
    }

    /**
     * Register a plugin wallet (site) with the license server.
     *
     * @param string $identity Owner identity address.
     * @param string $site     Plugin wallet address.
     * @param string $domain   Site domain.
     * @return array|null Response data or null.
     */
    public static function register_site( $identity, $site, $domain ) {
        $url = self::API_BASE . '/register';

        $response = wp_remote_post( $url, array(
            'timeout' => 10,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'identity' => $identity,
                'site'     => $site,
                'domain'   => $domain,
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        // Clear cached status so next check is fresh.
        delete_transient( self::CACHE_KEY );

        return is_array( $data ) ? $data : null;
    }

    /**
     * Build a Stripe checkout URL with site metadata.
     *
     * @param string $plan Plan: 'standard' or 'pro'.
     * @return string Checkout URL.
     */
    public static function checkout_url( $plan = 'standard' ) {
        $signing_address = Rootz_Signer::stored_address();
        $domain          = wp_parse_url( home_url(), PHP_URL_HOST );
        $admin_email     = get_option( 'admin_email', '' );
        $identity        = self::get_owner_identity();

        $params = array(
            'plan'   => $plan,
            'email'  => $admin_email,
            'site'   => $signing_address,
            'domain' => $domain,
        );

        if ( ! empty( $identity ) ) {
            $params['identity'] = $identity;
        }

        return self::CHECKOUT_BASE . '?' . http_build_query( $params );
    }

    /**
     * Clear the license status cache.
     */
    public static function clear_cache() {
        delete_transient( self::CACHE_KEY );
    }
}
