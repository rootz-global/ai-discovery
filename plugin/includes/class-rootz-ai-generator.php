<?php
/**
 * Rootz AI Generator — AI-powered content generation for AI Discovery.
 *
 * Two paths:
 *   1. Rootz Proxy (primary): Signs request with plugin wallet → dev.epistery.host
 *      → Anthropic. No user API key needed. Free tier: 3/month.
 *   2. Direct Anthropic (fallback): User provides their own API key.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI-powered content generation using Rootz Proxy or direct Anthropic API.
 */
class Rootz_Ai_Generator {

	/**
	 * Rootz AI Proxy endpoint.
	 *
	 * @var string
	 */
	const PROXY_URL = 'https://dev.epistery.host/agent/rootz/ai-proxy/v1/generate';

	/**
	 * Rootz AI Proxy status endpoint.
	 *
	 * @var string
	 */
	const STATUS_URL = 'https://dev.epistery.host/agent/rootz/ai-proxy/v1/status';

	/**
	 * Direct Anthropic API fallback URL.
	 *
	 * @var string
	 */
	const DIRECT_API_URL = 'https://api.anthropic.com/v1/messages';

	/**
	 * Model identifier for direct Anthropic calls.
	 *
	 * @var string
	 */
	const MODEL = 'claude-haiku-4-5-20251001';

	/**
	 * User's own Anthropic API key (optional override).
	 *
	 * @var string
	 */
	private $direct_api_key;

	/**
	 * Constructor — initialize with optional direct API key.
	 *
	 * @param string $api_key Optional direct Anthropic API key.
	 */
	public function __construct( $api_key = '' ) {
		$this->direct_api_key = $api_key ? $api_key : get_option( 'rootz_ai_api_key', '' );
	}

	/**
	 * Check if AI generation is available via any path.
	 *
	 * @return bool
	 */
	public function is_available() {
		// Available if: plugin has a wallet (with GMP for signing) OR user has own API key.
		return ( Rootz_Signer::has_stored_key() && Rootz_Signer::has_gmp() )
			|| ! empty( $this->direct_api_key );
	}

	/**
	 * Check if proxy path is available (wallet + GMP).
	 *
	 * @return bool
	 */
	public function has_proxy() {
		return Rootz_Signer::has_stored_key() && Rootz_Signer::has_gmp();
	}

	/**
	 * Check if direct path is available (user API key).
	 *
	 * @return bool
	 */
	public function has_direct() {
		return ! empty( $this->direct_api_key );
	}

	/**
	 * Get wallet usage status from the proxy.
	 *
	 * @return array|null { tier, usage, domain } or null on failure.
	 */
	public static function get_proxy_status() {
		$address = Rootz_Signer::stored_address();
		if ( empty( $address ) ) {
			return null;
		}

		$url      = self::STATUS_URL . '?wallet=' . rawurlencode( $address );
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		return ( $data['ok'] ?? false ) ? $data : null;
	}

	/**
	 * Generate an AI summary for the site.
	 *
	 * Tries proxy first, falls back to direct Anthropic.
	 *
	 * @param string $site_name    Site name.
	 * @param string $site_desc    Site tagline.
	 * @param string $about_text   About page text (plain text, trimmed).
	 * @param array  $page_titles  Array of published page titles.
	 * @return string Generated summary, or empty on failure.
	 */
	public function generate_summary( $site_name, $site_desc, $about_text = '', $page_titles = array() ) {
		$site_data = array(
			'name'       => $site_name,
			'tagline'    => $site_desc,
			'aboutText'  => $about_text,
			'pageTitles' => $page_titles,
		);

		// Try wallet-authenticated proxy first.
		if ( $this->has_proxy() ) {
			$result = $this->call_proxy( 'summary', $site_data );
			if ( ! empty( $result ) ) {
				return $result;
			}
		}

		// Fall back to direct Anthropic call.
		if ( $this->has_direct() ) {
			return $this->call_direct_summary( $site_name, $site_desc, $about_text, $page_titles );
		}

		return '';
	}

	/**
	 * Generate core concepts from site content.
	 *
	 * @param string $site_name    Site name.
	 * @param string $about_text   About page text.
	 * @param array  $categories   Array of category names.
	 * @param array  $page_titles  Array of published page titles.
	 * @return string Generated concepts, or empty.
	 */
	public function generate_concepts( $site_name, $about_text = '', $categories = array(), $page_titles = array() ) {
		$site_data = array(
			'name'       => $site_name,
			'aboutText'  => ! empty( $about_text ) ? wp_trim_words( $about_text, 200 ) : '',
			'categories' => $categories,
			'pageTitles' => $page_titles,
		);

		// Try proxy first.
		if ( $this->has_proxy() ) {
			$result = $this->call_proxy( 'concepts', $site_data );
			if ( ! empty( $result ) ) {
				return $result;
			}
		}

		// Fall back to direct.
		if ( $this->has_direct() ) {
			return $this->call_direct_concepts( $site_name, $about_text, $categories, $page_titles );
		}

		return '';
	}

	/**
	 * Extract structured identity fields from site content using AI.
	 *
	 * Returns JSON with: legalName, sector, founded, headquarters.
	 * Only returns fields the AI can confidently extract from the content.
	 *
	 * @param string $site_name    Site name.
	 * @param string $about_text   About page text (plain text).
	 * @param string $contact_text Contact page text (plain text).
	 * @param array  $page_titles  Array of published page titles.
	 * @return array Extracted fields (only those found), or empty array.
	 */
	public function generate_identity( $site_name, $about_text = '', $contact_text = '', $page_titles = array() ) {
		$site_data = array(
			'name'        => $site_name,
			'aboutText'   => ! empty( $about_text ) ? wp_trim_words( $about_text, 300 ) : '',
			'contactText' => ! empty( $contact_text ) ? wp_trim_words( $contact_text, 300 ) : '',
			'pageTitles'  => $page_titles,
		);

		// Try proxy first.
		if ( $this->has_proxy() ) {
			$result = $this->call_proxy( 'identity', $site_data );
			if ( ! empty( $result ) ) {
				$parsed = json_decode( $result, true );
				if ( is_array( $parsed ) ) {
					return $parsed;
				}
			}
		}

		// Fall back to direct.
		if ( $this->has_direct() ) {
			return $this->call_direct_identity( $site_name, $about_text, $contact_text, $page_titles );
		}

		return array();
	}

	// Proxy path methods.

	/**
	 * Call the Rootz AI Proxy with wallet signature.
	 *
	 * @param string $type      Generation type (summary, concepts, ai-json, knowledge).
	 * @param array  $site_data Site data for the prompt.
	 * @return string Generated text, or empty on failure.
	 */
	private function call_proxy( $type, $site_data ) {
		$signer = new Rootz_Signer();
		$wallet = Rootz_Signer::stored_address();

		if ( empty( $wallet ) ) {
			return '';
		}

		// Build payload.
		$payload = array(
			'domain'    => wp_parse_url( get_site_url(), PHP_URL_HOST ) ? wp_parse_url( get_site_url(), PHP_URL_HOST ) : get_site_url(),
			'type'      => $type,
			'siteData'  => $site_data,
			'nonce'     => bin2hex( random_bytes( 16 ) ),
			'timestamp' => time(),
		);

		// JSON-encode and hash the payload.
		$payload_json = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$payload_hash = hash( 'sha256', $payload_json );

		// Sign the hash with plugin wallet.
		$sig = $signer->sign( $payload_hash );
		if ( ! $sig ) {
			return '';
		}

		// Send to proxy.
		$response = wp_remote_post(
			self::PROXY_URL,
			array(
				'timeout' => 30,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'wallet'    => $wallet,
						'payload'   => $payload,
						'signature' => $sig['signature'],
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return '';
		}

		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );

		// Store last proxy response for UI display.
		if ( is_array( $data ) && isset( $data['usage'] ) ) {
			set_transient( 'rootz_proxy_usage', $data['usage'], HOUR_IN_SECONDS );
		}

		if ( 200 !== $status || empty( $data['ok'] ) ) {
			// Store error for UI display.
			if ( is_array( $data ) && ! empty( $data['message'] ) ) {
				set_transient( 'rootz_proxy_error', $data['message'], 5 * MINUTE_IN_SECONDS );
			}
			return '';
		}

		return trim( $data['result'] ?? '' );
	}

	// Direct path methods (Anthropic).

	/**
	 * Generate summary via direct Anthropic API.
	 *
	 * @param string $site_name   Site name.
	 * @param string $site_desc   Site tagline.
	 * @param string $about_text  About page text.
	 * @param array  $page_titles Array of page titles.
	 * @return string Generated summary.
	 */
	private function call_direct_summary( $site_name, $site_desc, $about_text, $page_titles ) {
		$context = "Site name: {$site_name}\n";
		if ( ! empty( $site_desc ) ) {
			$context .= "Tagline: {$site_desc}\n";
		}
		if ( ! empty( $about_text ) ) {
			$context .= "About page content:\n{$about_text}\n";
		}
		if ( ! empty( $page_titles ) ) {
			$context .= 'Site pages: ' . implode( ', ', array_slice( $page_titles, 0, 20 ) ) . "\n";
		}

		$prompt = 'Based on this WordPress site information, write a 2-3 sentence summary for AI agents. ' .
					'The summary should clearly explain what this organization does and what information is available on the site. ' .
					'Write in third person, be factual and specific. Do not use marketing language or superlatives. ' .
					"Return ONLY the summary text, nothing else.\n\n{$context}";

		return $this->call_anthropic( $prompt, 200 );
	}

	/**
	 * Generate concepts via direct Anthropic API.
	 *
	 * @param string $site_name   Site name.
	 * @param string $about_text  About page text.
	 * @param array  $categories  Array of category names.
	 * @param array  $page_titles Array of page titles.
	 * @return string Generated concepts.
	 */
	private function call_direct_concepts( $site_name, $about_text, $categories, $page_titles ) {
		$context = "Organization: {$site_name}\n";
		if ( ! empty( $about_text ) ) {
			$context .= "About:\n" . wp_trim_words( $about_text, 200 ) . "\n";
		}
		if ( ! empty( $categories ) ) {
			$context .= 'Categories: ' . implode( ', ', $categories ) . "\n";
		}
		if ( ! empty( $page_titles ) ) {
			$context .= 'Pages: ' . implode( ', ', array_slice( $page_titles, 0, 20 ) ) . "\n";
		}

		$prompt = 'Based on this WordPress site information, identify 5-10 core concepts that AI agents should know about. ' .
					'Format each concept as: term: definition (one per line). ' .
					'Focus on domain-specific terms, products, services, or key topics. ' .
					'Keep definitions to one sentence each. Be factual and specific. ' .
					"Return ONLY the concept lines, nothing else.\n\n{$context}";

		return $this->call_anthropic( $prompt, 500 );
	}

	/**
	 * Extract identity fields via direct Anthropic API.
	 *
	 * @param string $site_name    Site name.
	 * @param string $about_text   About page text.
	 * @param string $contact_text Contact page text.
	 * @param array  $page_titles  Array of page titles.
	 * @return array Extracted identity fields.
	 */
	private function call_direct_identity( $site_name, $about_text, $contact_text, $page_titles ) {
		$context = "Site name: {$site_name}\n";
		if ( ! empty( $about_text ) ) {
			$context .= "About page content:\n{$about_text}\n\n";
		}
		if ( ! empty( $contact_text ) ) {
			$context .= "Contact page content:\n{$contact_text}\n\n";
		}
		if ( ! empty( $page_titles ) ) {
			$context .= 'Site pages: ' . implode( ', ', array_slice( $page_titles, 0, 20 ) ) . "\n";
		}

		$prompt = 'Extract structured identity information from this website content. ' .
					"Return ONLY a JSON object with these fields (include only fields you can confidently extract):\n" .
					"- legalName: The official legal/registered name of the organization\n" .
					"- sector: The industry or sector (e.g., \"Technology\", \"Healthcare\", \"Education\")\n" .
					"- founded: Year founded (4-digit year only)\n" .
					"- headquarters: Location (city, state/country)\n\n" .
					'If a field cannot be determined from the content, omit it entirely. ' .
					"Return ONLY valid JSON, no markdown formatting, no explanation.\n\n{$context}";

		$result = $this->call_anthropic( $prompt, 200 );
		if ( empty( $result ) ) {
			return array();
		}

		// Strip markdown code fences if present.
		$result = preg_replace( '/^```(?:json)?\s*/i', '', $result );
		$result = preg_replace( '/\s*```$/', '', $result );

		$parsed = json_decode( trim( $result ), true );
		return is_array( $parsed ) ? $parsed : array();
	}

	/**
	 * Call the Anthropic Messages API directly.
	 *
	 * @param string $prompt     The user prompt.
	 * @param int    $max_tokens Maximum response tokens.
	 * @return string The response text, or empty on failure.
	 */
	private function call_anthropic( $prompt, $max_tokens = 300 ) {
		if ( empty( $this->direct_api_key ) ) {
			return '';
		}

		$body = wp_json_encode(
			array(
				'model'      => self::MODEL,
				'max_tokens' => $max_tokens,
				'messages'   => array(
					array(
						'role'    => 'user',
						'content' => $prompt,
					),
				),
			)
		);

		$response = wp_remote_post(
			self::DIRECT_API_URL,
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type'      => 'application/json',
					'x-api-key'         => $this->direct_api_key,
					'anthropic-version' => '2023-06-01',
				),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return '';
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status ) {
			return '';
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['content'][0]['text'] ) ) {
			return '';
		}

		return trim( $data['content'][0]['text'] );
	}
}
