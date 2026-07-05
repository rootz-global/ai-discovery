<?php
/**
 * AI access metrics collection and analytics.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI access metrics collection, storage, and analytics.
 */
class Rootz_Metrics {

	/**
	 * Database table name without prefix.
	 *
	 * @var string
	 */
	const TABLE = 'rootz_ai_metrics';

	/**
	 * Pending log entry to write on shutdown.
	 *
	 * @var array|null
	 */
	private static $pending_log = null;

	/**
	 * Create the metrics table.
	 */
	public static function create_table() {
		global $wpdb;
		$table   = $wpdb->prefix . self::TABLE;
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            endpoint VARCHAR(100) NOT NULL,
            user_agent VARCHAR(500) DEFAULT '',
            ai_provider VARCHAR(50) DEFAULT 'unknown',
            ip_hash VARCHAR(64) DEFAULT '',
            response_ms INT UNSIGNED DEFAULT 0,
            status_code SMALLINT UNSIGNED DEFAULT 200,
            auth_tier VARCHAR(20) DEFAULT 'anonymous',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created (created_at),
            INDEX idx_provider (ai_provider),
            INDEX idx_endpoint (endpoint)
        ) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Schedule log entry to be written on shutdown (zero latency impact).
	 *
	 * @param string $endpoint    The endpoint being accessed.
	 * @param int    $status_code HTTP status code of the response.
	 */
	public static function schedule_log( $endpoint, $status_code = 200 ) {
		self::$pending_log = array(
			'endpoint'    => $endpoint,
			'status_code' => $status_code,
			'start_time'  => microtime( true ),
		);
		add_action( 'shutdown', array( __CLASS__, 'flush_log' ) );
	}

	/**
	 * Write pending log entry to database.
	 */
	public static function flush_log() {
		if ( null === self::$pending_log ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		$log   = self::$pending_log;

		$user_agent  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 500 ) : '';
		$ip          = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$response_ms = intval( ( microtime( true ) - $log['start_time'] ) * 1000 );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom metrics table, no WP API equivalent.
		$wpdb->insert(
			$table,
			array(
				'endpoint'    => $log['endpoint'],
				'user_agent'  => $user_agent,
				'ai_provider' => self::classify_agent( $user_agent ),
				'ip_hash'     => hash( 'sha256', $ip ),
				'response_ms' => $response_ms,
				'status_code' => $log['status_code'],
				'auth_tier'   => 'anonymous',
				'created_at'  => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
		);

		self::$pending_log = null;
	}

	/**
	 * Classify AI agent from User-Agent string.
	 *
	 * @param string $ua User-Agent header.
	 * @return string Provider name.
	 */
	public static function classify_agent( $ua ) {
		$ua_lower = strtolower( $ua );

		$patterns = array(
			'GPTBot'              => 'OpenAI',
			'ChatGPT-User'        => 'OpenAI',
			'ClaudeBot'           => 'Anthropic',
			'Claude-Web'          => 'Anthropic',
			'Googlebot'           => 'Google',
			'Google-Extended'     => 'Google',
			'Bingbot'             => 'Microsoft',
			'BingPreview'         => 'Microsoft',
			'PerplexityBot'       => 'Perplexity',
			'cohere-ai'           => 'Cohere',
			'CCBot'               => 'Common Crawl',
			'Applebot'            => 'Apple',
			'FacebookExternalHit' => 'Meta',
			'facebookexternalhit' => 'Meta',
			'Bytespider'          => 'ByteDance',
			'PetalBot'            => 'Huawei',
			'YandexBot'           => 'Yandex',
			'DuckDuckBot'         => 'DuckDuckGo',
		);

		foreach ( $patterns as $pattern => $provider ) {
			if ( false !== strpos( $ua, $pattern ) || false !== strpos( $ua_lower, strtolower( $pattern ) ) ) {
				return $provider;
			}
		}

		// Check for generic bot indicators.
		if ( preg_match( '/bot|crawler|spider|scraper|fetch/i', $ua ) ) {
			return 'Bot (other)';
		}

		return 'Human / Unknown';
	}

	/**
	 * Get analytics summary.
	 *
	 * @param int $days Number of days to summarize.
	 * @return array Summary data.
	 */
	public static function get_summary( $days = 30 ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		// Check table exists.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time check on information_schema.
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
				DB_NAME,
				$table
			)
		);
		if ( ! $exists ) {
			return self::empty_summary();
		}

		$since = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom metrics table; $table is $wpdb->prefix constant, not user input.
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE created_at >= %s",
				$since
			)
		);

		$last_24h = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE created_at >= %s",
				gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS )
			)
		);

		$last_7d = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE created_at >= %s",
				gmdate( 'Y-m-d H:i:s', time() - ( 7 * DAY_IN_SECONDS ) )
			)
		);

		// Top agents.
		$agents = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ai_provider, COUNT(*) as hits FROM {$table} WHERE created_at >= %s GROUP BY ai_provider ORDER BY hits DESC LIMIT 10",
				$since
			)
		);

		// Endpoint breakdown.
		$endpoints = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT endpoint, COUNT(*) as hits FROM {$table} WHERE created_at >= %s GROUP BY endpoint ORDER BY hits DESC LIMIT 10",
				$since
			)
		);

		// Recent requests.
		$recent = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT endpoint, ai_provider, response_ms, status_code, created_at FROM {$table} WHERE created_at >= %s ORDER BY created_at DESC LIMIT 20",
				gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS )
			)
		);

		// Average response time.
		$avg_ms = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(response_ms) FROM {$table} WHERE created_at >= %s",
				$since
			)
		);
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array(
			'period'    => $days . ' days',
			'total'     => $total,
			'last24h'   => $last_24h,
			'last7d'    => $last_7d,
			'avgMs'     => $avg_ms,
			'agents'    => $agents ? $agents : array(),
			'endpoints' => $endpoints ? $endpoints : array(),
			'recent'    => $recent ? $recent : array(),
		);
	}

	/**
	 * Return empty summary when table does not exist yet.
	 *
	 * @return array Empty summary data structure.
	 */
	private static function empty_summary() {
		return array(
			'period'    => '0 days',
			'total'     => 0,
			'last24h'   => 0,
			'last7d'    => 0,
			'avgMs'     => 0,
			'agents'    => array(),
			'endpoints' => array(),
			'recent'    => array(),
		);
	}

	/**
	 * Prune old metrics data.
	 *
	 * @param int $days Keep data for this many days.
	 */
	public static function prune( $days = 90 ) {
		global $wpdb;
		$table  = $wpdb->prefix . self::TABLE;
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom metrics table; $table is $wpdb->prefix constant.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE created_at < %s",
				$cutoff
			)
		);
	}
}
