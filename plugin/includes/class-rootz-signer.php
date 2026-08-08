<?php
/**
 * Rootz Signer — secp256k1 key management and content signing.
 *
 * Generates an Ethereum-compatible keypair on activation,
 * stores the private key securely, and signs AI Discovery content.
 *
 * Day 1: self-signed (key proves consistency).
 * Day 2+: authorization layered on via on-chain delegation.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Load vendor libraries if they are present.
 *
 * This used to be an unconditional require_once, which meant that a plugin
 * directory missing vendor/ took the entire site down with a fatal error on
 * activation — a white screen, on every page, for a feature that is optional.
 * That can happen from an interrupted upload, a partial unzip, a security
 * scanner quarantining a file, or a host that strips directories it does not
 * recognise.
 *
 * Everything else in this class already degrades rather than dies when signing
 * is unavailable (see has_gmp() and its callers). This makes the vendor libs
 * behave the same way: without them the plugin still serves discovery data, it
 * simply cannot sign it.
 */
if ( file_exists( ROOTZ_AI_DISCOVERY_DIR . 'vendor/autoload.php' ) ) {
	require_once ROOTZ_AI_DISCOVERY_DIR . 'vendor/autoload.php';
}

use Elliptic\EC;
use kornrunner\Keccak;

/**
 * Secp256k1 key management and cryptographic content signing.
 */
class Rootz_Signer {

	/**
	 * Option key for the encrypted private key.
	 *
	 * @var string
	 */
	const OPTION_PRIVATE_KEY = 'rootz_signing_key';

	/**
	 * Option key for the public address.
	 *
	 * @var string
	 */
	const OPTION_ADDRESS = 'rootz_signing_address';

	/**
	 * Elliptic curve instance.
	 *
	 * @var EC|null
	 */
	private $ec;

	/**
	 * Loaded key pair for signing.
	 *
	 * @var EC\KeyPair|null
	 */
	private $key_pair;

	/**
	 * Initialize the elliptic curve instance if GMP is available.
	 */
	public function __construct() {
		if ( self::signing_available() ) {
			$this->ec = new EC( 'secp256k1' );
		}
	}

	/**
	 * Whether cryptographic signing can actually run here.
	 *
	 * Requires BOTH the GMP extension and the vendored elliptic library. Checking
	 * only one of the two is how a missing vendor/ turned into a fatal instead of
	 * a disabled feature.
	 *
	 * @return bool
	 */
	public static function signing_available() {
		return self::has_gmp() && class_exists( '\Elliptic\EC' ) && class_exists( '\kornrunner\Keccak' );
	}

	/**
	 * Get the stored signing address without requiring GMP.
	 * Safe to call anywhere — just reads from wp_options.
	 *
	 * @return string The 0x-prefixed address, or empty string.
	 */
	public static function stored_address() {
		return get_option( self::OPTION_ADDRESS, '' );
	}

	/**
	 * Check if a signing key exists in the database (does not require GMP).
	 *
	 * @return bool
	 */
	public static function has_stored_key() {
		return ! empty( get_option( self::OPTION_PRIVATE_KEY, '' ) );
	}

	/**
	 * Check if GMP extension is available (required for elliptic-php).
	 *
	 * @return bool
	 */
	public static function has_gmp() {
		return extension_loaded( 'gmp' );
	}

	/**
	 * Check if a signing key has been generated.
	 *
	 * @return bool
	 */
	public function has_key() {
		return ! empty( get_option( self::OPTION_PRIVATE_KEY, '' ) );
	}

	/**
	 * Get the plugin's public Ethereum address.
	 *
	 * @return string The 0x-prefixed address, or empty string.
	 */
	public function get_address() {
		return get_option( self::OPTION_ADDRESS, '' );
	}

	/**
	 * Generate a new signing keypair.
	 * Stores the private key encrypted in wp_options.
	 *
	 * @return string The generated Ethereum address.
	 */
	public function generate_key() {
		if ( ! self::signing_available() || ! $this->ec ) {
			return '';
		}

		$key_pair        = $this->ec->genKeyPair();
		$private_key_hex = $key_pair->getPrivate( 'hex' );
		$address         = $this->derive_address( $key_pair );

		// Encrypt private key before storing.
		$encrypted = $this->encrypt( $private_key_hex );
		update_option( self::OPTION_PRIVATE_KEY, $encrypted );
		update_option( self::OPTION_ADDRESS, $address );

		$this->key_pair = $key_pair;

		return $address;
	}

	/**
	 * Load the stored keypair into memory.
	 *
	 * @return bool True if key loaded successfully.
	 */
	public function load_key() {
		if ( ! self::signing_available() || ! $this->ec ) {
			return false;
		}

		$encrypted = get_option( self::OPTION_PRIVATE_KEY, '' );
		if ( empty( $encrypted ) ) {
			return false;
		}

		$private_key_hex = $this->decrypt( $encrypted );
		if ( empty( $private_key_hex ) ) {
			return false;
		}

		try {
			$this->key_pair = $this->ec->keyFromPrivate( $private_key_hex, 'hex' );
			return true;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Sign a content hash and return the signature components.
	 *
	 * @param string $content_hash Hex-encoded SHA-256 hash (no 0x prefix).
	 * @return array|null { r, s, v, signature } or null on failure.
	 */
	public function sign( $content_hash ) {
		if ( ! $this->key_pair ) {
			if ( ! $this->load_key() ) {
				return null;
			}
		}

		try {
			$sig = $this->key_pair->sign( $content_hash );

			$r = $sig->r;
			$s = $sig->s;
			$v = $sig->recoveryParam; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- External library property name.

			// BIP-62 / EIP-2: enforce low-s value.
			// If s > secp256k1.n / 2, replace with n - s and flip recovery param.
			$curve_n = $this->ec->n;
			$half_n  = $curve_n->shrn( 1 ); // Half of curve order (n >> 1).
			if ( $s->cmp( $half_n ) > 0 ) {
				$s = $curve_n->sub( $s );
				$v = $v ^ 1; // Flip recovery parameter.
			}

			$r_hex = str_pad( $r->toString( 16 ), 64, '0', STR_PAD_LEFT );
			$s_hex = str_pad( $s->toString( 16 ), 64, '0', STR_PAD_LEFT );
			$v_hex = dechex( $v + 27 );

			return array(
				'r'         => '0x' . $r_hex,
				's'         => '0x' . $s_hex,
				'v'         => $v + 27,
				'signature' => '0x' . $r_hex . $s_hex . $v_hex,
			);
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Sign AI Discovery content and return a complete _signature block.
	 *
	 * @param array $data The ai.json data (without _signature).
	 * @return array The _signature block.
	 */
	public function sign_content( $data ) {
		$content_json = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$content_hash = hash( 'sha256', $content_json );

		$sig_block = array(
			'signer'      => $this->get_address(),
			'contentHash' => 'sha256:' . $content_hash,
			'signedAt'    => gmdate( 'c' ),
			'method'      => 'ecdsa-secp256k1',
		);

		$sig = $this->sign( $content_hash );
		if ( $sig ) {
			$sig_block['signature']     = $sig['signature'];
			$sig_block['authorization'] = 'self-signed';
		} else {
			// Fallback: hash only, no cryptographic signature.
			$sig_block['method']        = 'hash-only';
			$sig_block['authorization'] = 'none';
		}

		// Digital Name / blockchain (from identity settings, if configured).
		$digital_name = get_option( 'rootz_digital_name', '' );
		if ( ! empty( $digital_name ) ) {
			$sig_block['digitalName'] = $digital_name;
		}

		$blockchain = get_option( 'rootz_blockchain', '' );
		if ( ! empty( $blockchain ) ) {
			$sig_block['network'] = $blockchain;
		}

		$identity_contract = get_option( 'rootz_identity_contract', '' );
		if ( ! empty( $identity_contract ) ) {
			$sig_block['identityContract'] = $identity_contract;
		}

		return $sig_block;
	}

	/**
	 * Derive Ethereum address from a keypair.
	 *
	 * @param EC\KeyPair $key_pair The elliptic curve key pair.
	 * @return string 0x-prefixed address.
	 */
	private function derive_address( $key_pair ) {
		// Get uncompressed public key (hex, starts with '04').
		$pub_hex = $key_pair->getPublic( false, 'hex' );

		// Remove the '04' prefix.
		$pub_without_prefix = substr( $pub_hex, 2 );

		// Keccak-256 hash of the public key bytes.
		$keccak = Keccak::hash( hex2bin( $pub_without_prefix ), 256 );

		// Last 20 bytes = Ethereum address.
		$address = '0x' . substr( $keccak, -40 );

		return $this->checksum_address( $address );
	}

	/**
	 * EIP-55 checksum encoding for an Ethereum address.
	 *
	 * @param string $address Lowercase 0x address.
	 * @return string Checksummed address.
	 */
	private function checksum_address( $address ) {
		$address_lower = strtolower( substr( $address, 2 ) );
		$hash          = Keccak::hash( $address_lower, 256 );
		$result        = '0x';

		for ( $i = 0; $i < 40; $i++ ) {
			if ( intval( $hash[ $i ], 16 ) >= 8 ) {
				$result .= strtoupper( $address_lower[ $i ] );
			} else {
				$result .= $address_lower[ $i ];
			}
		}

		return $result;
	}

	/**
	 * Encrypt a private key using WordPress salts.
	 * Uses AES-256-CBC with a key derived from SECURE_AUTH_KEY + SECURE_AUTH_SALT.
	 *
	 * @param string $plaintext The hex private key.
	 * @return string Base64-encoded ciphertext.
	 */
	private function encrypt( $plaintext ) {
		$key = $this->derive_encryption_key();
		$iv  = openssl_random_pseudo_bytes( 16 );

		$ciphertext = openssl_encrypt( $plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
		if ( false === $ciphertext ) {
			return '';
		}

		// Prepend IV to ciphertext.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Legitimate AES-256-CBC cryptographic key storage.
		return base64_encode( $iv . $ciphertext );
	}

	/**
	 * Decrypt a private key.
	 *
	 * @param string $encrypted Base64-encoded ciphertext (IV + ciphertext).
	 * @return string The hex private key.
	 */
	private function decrypt( $encrypted ) {
		$key = $this->derive_encryption_key();
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Legitimate AES-256-CBC cryptographic key retrieval.
		$data = base64_decode( $encrypted );
		if ( false === $data || strlen( $data ) < 17 ) {
			return '';
		}

		$iv         = substr( $data, 0, 16 );
		$ciphertext = substr( $data, 16 );

		$plaintext = openssl_decrypt( $ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
		if ( false === $plaintext ) {
			return '';
		}

		return $plaintext;
	}

	/**
	 * Derive an encryption key from WordPress security salts.
	 *
	 * @return string 32-byte key.
	 */
	private function derive_encryption_key() {
		$salt = '';
		if ( defined( 'SECURE_AUTH_KEY' ) ) {
			$salt .= SECURE_AUTH_KEY;
		}
		if ( defined( 'SECURE_AUTH_SALT' ) ) {
			$salt .= SECURE_AUTH_SALT;
		}
		if ( empty( $salt ) ) {
			// Fallback if salts aren't configured (shouldn't happen in production).
			$salt = 'rootz-fallback-' . ABSPATH;
		}

		return hash( 'sha256', $salt, true );
	}

	/**
	 * Delete the stored keypair.
	 *
	 * @return void
	 */
	public function delete_key() {
		delete_option( self::OPTION_PRIVATE_KEY );
		delete_option( self::OPTION_ADDRESS );
		$this->key_pair = null;
	}
}
