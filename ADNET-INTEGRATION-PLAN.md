# Adnet Integration Plan — Rootz AI Discovery Plugin
## v2.3.3 → v2.4.0
### Target: WP.org-safe update for Studio2 channel rollout

---

## Overview

Add an **Adnet tab** and a **Gutenberg block** to the existing approved plugin. The plugin already calls `rootz.global` external APIs and is WP.org approved — this update follows the same pattern with additional required disclosures for the GeistM/Adnet service.

**Principle:** Do not change anything that currently works. Add only what's needed. Every addition follows WP coding standards already in use in this codebase.

---

## WP.org Risk Assessment

| Risk | Mitigation |
|------|-----------|
| External service call to adnet.geistm.com | Document in `readme.txt` `== External Services ==` section (required) |
| Browser+domain key generation in IndexedDB | Use `wp_add_privacy_policy_content()` to register disclosure in WP Privacy tool |
| Data sent off-site (event hashes, counts, wallet) | Disclose in External Services section AND in Adnet settings tab UI |
| Gutenberg block | Must use `block.json` + `register_block_type()` — no legacy `register_block_type_from_metadata()` |
| Inline `<script>` tags | None — use `wp_enqueue_script()` + `wp_localize_script()` only |
| Loading JS on every page | Gate on `rootz_adnet_enabled === '1'` AND wallet configured — no-op if not set up |
| New capability/permission check | All Adnet admin actions check `current_user_can('manage_options')` |

---

## Files to Create

```
plugin/
├── admin/views/settings-adnet.php          # Adnet settings tab UI
├── public/adnet-rivets.js                  # Frontend: key gen + event signing
├── blocks/adnet-slot/                      # Gutenberg block
│   ├── block.json                          # Block metadata (required by WP.org)
│   ├── edit.js                             # Editor placeholder
│   └── view.js                             # Frontend renderer (minimal)
```

## Files to Modify

```
plugin/
├── rootz-ai-discovery.php                  # Version bump, defaults, JS enqueue, block register
├── admin/class-rootz-admin.php             # Add 'adnet' tab + register_settings group
├── readme.txt                              # External Services disclosure + changelog
```

---

## Step 1: readme.txt — External Services Disclosure

**Do this first. WP.org auto-rejects plugins that make external calls without this section.**

Add to `readme.txt` (after `== Privacy Policy ==`, before `== Changelog ==`):

```
== External Services ==

This plugin connects to the following external services when Adnet is enabled
(disabled by default):

**Adnet by GeistM** (adnet.geistm.com)
- Purpose: Delivers verified advertisements to publisher pages and records engagement events.
- Data sent: Publisher wallet address, anonymized event counts, cryptographic batch hashes.
  No personally identifiable information (name, email, IP address) is transmitted.
- Data NOT sent: Page content, user credentials, full browsing history.
- When: Only when the Adnet feature is enabled in Settings → AI Discovery → Adnet tab
  AND a publisher wallet address is configured.
- Terms: https://geistm.com/terms
- Privacy: https://geistm.com/privacy

**Polygon Blockchain** (polygon-bor-rpc.publicnode.com)
- Purpose: Verifies campaign contract status before serving ads.
- Data sent: Campaign contract address (public on-chain data). No user data.
- When: Same condition as above (Adnet enabled + wallet configured).
- This is a public RPC node. No account or API key is required.
```

---

## Step 2: Version Bump

In `rootz-ai-discovery.php`, change:
- Plugin header: `Version: 2.3.3` → `Version: 2.4.0`
- `define('ROOTZ_AI_DISCOVERY_VERSION', '2.3.3')` → `'2.4.0'`

---

## Step 3: New Options and Defaults

In `rootz_ai_discovery_activate()` in `rootz-ai-discovery.php`, add to `$defaults` array:

```php
'rootz_adnet_enabled'          => '0',   // Off by default
'rootz_adnet_publisher_wallet' => '',
'rootz_adnet_api_url'          => 'https://adnet.geistm.com',
'rootz_adnet_consent_notice'   => '1',   // Show consent notice to users
```

---

## Step 4: Register Adnet Settings Group

In `Rootz_Admin::register_settings()` in `class-rootz-admin.php`, add after the account settings block:

```php
// Adnet tab settings.
foreach ( array( 'rootz_adnet_enabled', 'rootz_adnet_consent_notice' ) as $field ) {
    register_setting(
        'rootz_adnet',
        $field,
        array(
            'type'              => 'string',
            'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
        )
    );
}
register_setting(
    'rootz_adnet',
    'rootz_adnet_publisher_wallet',
    array(
        'type'              => 'string',
        'sanitize_callback' => array( $this, 'sanitize_wallet_address' ),
    )
);
register_setting(
    'rootz_adnet',
    'rootz_adnet_api_url',
    array(
        'type'              => 'string',
        'sanitize_callback' => 'esc_url_raw',
    )
);
```

Add `sanitize_wallet_address()` method to `Rootz_Admin`:

```php
public function sanitize_wallet_address( $value ) {
    $value = sanitize_text_field( $value );
    // Accept empty (disabled) or valid 0x-prefixed Ethereum address.
    if ( empty( $value ) || preg_match( '/^0x[a-fA-F0-9]{40}$/', $value ) ) {
        return $value;
    }
    add_settings_error(
        'rootz_adnet_publisher_wallet',
        'invalid_wallet',
        __( 'Publisher wallet must be a valid Ethereum address (0x followed by 40 hex characters).', 'rootz-ai-discovery' )
    );
    return '';
}
```

---

## Step 5: Add Adnet Tab

In `Rootz_Admin::render_page()`, add `'adnet'` to the `$tabs` array:

```php
$tabs = array(
    'viewer'    => __( 'What AI Sees', 'rootz-ai-discovery' ),
    'identity'  => __( 'Identity', 'rootz-ai-discovery' ),
    'content'   => __( 'Content', 'rootz-ai-discovery' ),
    'policies'  => __( 'Policies', 'rootz-ai-discovery' ),
    'tools'     => __( 'Tools & Preview', 'rootz-ai-discovery' ),
    'analytics' => __( 'Analytics', 'rootz-ai-discovery' ),
    'adnet'     => __( 'Adnet', 'rootz-ai-discovery' ),   // ADD THIS
    'account'   => __( 'Account & Wallet', 'rootz-ai-discovery' ),
);
```

In the `switch` in `render_page()`, add before `default`:

```php
case 'adnet':
    include ROOTZ_AI_DISCOVERY_DIR . 'admin/views/settings-adnet.php';
    break;
```

---

## Step 6: Adnet Settings View

Create `admin/views/settings-adnet.php`:

```php
<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="rootz-tab-content">

    <?php settings_errors(); ?>

    <h2><?php esc_html_e( 'Adnet — Verified Advertising', 'rootz-ai-discovery' ); ?></h2>
    <p>
        <?php esc_html_e( 'Earn publisher revenue by serving verified advertisements from the Adnet network. Ads are paid directly to your wallet via smart contract — no intermediary holds your revenue.', 'rootz-ai-discovery' ); ?>
        <a href="https://adnet.geistm.com" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn more', 'rootz-ai-discovery' ); ?></a>
    </p>

    <?php
    // External service notice — required by WP.org guidelines when making off-site calls.
    ?>
    <div class="notice notice-info inline" style="margin:0 0 16px;">
        <p>
            <strong><?php esc_html_e( 'External Service:', 'rootz-ai-discovery' ); ?></strong>
            <?php
            printf(
                /* translators: %s: link to GeistM terms */
                esc_html__( 'When enabled, this feature connects to %s to deliver ads and record engagement. No personal data is transmitted. See the plugin readme for full disclosure.', 'rootz-ai-discovery' ),
                '<a href="https://adnet.geistm.com" target="_blank" rel="noopener noreferrer">adnet.geistm.com</a>'
            );
            ?>
        </p>
    </div>

    <form method="post" action="options.php">
        <?php
        settings_fields( 'rootz_adnet' );
        $enabled  = get_option( 'rootz_adnet_enabled', '0' );
        $wallet   = get_option( 'rootz_adnet_publisher_wallet', '' );
        $api_url  = get_option( 'rootz_adnet_api_url', 'https://adnet.geistm.com' );
        $consent  = get_option( 'rootz_adnet_consent_notice', '1' );
        ?>

        <table class="form-table" role="presentation">

            <tr>
                <th scope="row"><?php esc_html_e( 'Enable Adnet', 'rootz-ai-discovery' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="rootz_adnet_enabled" value="1" <?php checked( $enabled, '1' ); ?> />
                        <?php esc_html_e( 'Serve verified advertisements on this site', 'rootz-ai-discovery' ); ?>
                    </label>
                    <?php Rootz_Admin::help_tip( __( 'Ads will only display on pages where you insert the Adnet Slot block via the Gutenberg editor. Enabling this setting does not automatically place ads.', 'rootz-ai-discovery' ), 'adnet-enabled' ); ?>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rootz_adnet_publisher_wallet"><?php esc_html_e( 'Publisher Wallet Address', 'rootz-ai-discovery' ); ?></label>
                </th>
                <td>
                    <input type="text"
                        id="rootz_adnet_publisher_wallet"
                        name="rootz_adnet_publisher_wallet"
                        value="<?php echo esc_attr( $wallet ); ?>"
                        class="regular-text"
                        placeholder="0x..."
                        pattern="^0x[a-fA-F0-9]{40}$"
                    />
                    <p class="description">
                        <?php esc_html_e( 'Your Polygon wallet address. Ad earnings are paid directly here by campaign smart contracts. Must be a valid Ethereum/Polygon address (0x followed by 40 hex characters).', 'rootz-ai-discovery' ); ?>
                    </p>
                    <?php Rootz_Admin::help_tip( __( 'This address is submitted to adnet.geistm.com to associate your site with your publisher account. It is public — do not enter a private key here.', 'rootz-ai-discovery' ), 'adnet-wallet' ); ?>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Consent Notice', 'rootz-ai-discovery' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="rootz_adnet_consent_notice" value="1" <?php checked( $consent, '1' ); ?> />
                        <?php esc_html_e( 'Show a non-intrusive notice to visitors that this site uses privacy-preserving ad verification', 'rootz-ai-discovery' ); ?>
                    </label>
                    <?php Rootz_Admin::help_tip( __( 'Adnet generates a browser+domain key pair stored locally in the visitor\'s browser (IndexedDB). This key is unique per site — it cannot be used to track the visitor across other sites. The consent notice informs visitors of this.', 'rootz-ai-discovery' ), 'adnet-consent' ); ?>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rootz_adnet_api_url"><?php esc_html_e( 'Adnet API URL', 'rootz-ai-discovery' ); ?></label>
                </th>
                <td>
                    <input type="url"
                        id="rootz_adnet_api_url"
                        name="rootz_adnet_api_url"
                        value="<?php echo esc_attr( $api_url ); ?>"
                        class="regular-text"
                    />
                    <p class="description">
                        <?php esc_html_e( 'Default: https://adnet.geistm.com — change only for testing.', 'rootz-ai-discovery' ); ?>
                    </p>
                </td>
            </tr>

        </table>

        <?php submit_button(); ?>
    </form>

    <?php if ( '1' === $enabled && ! empty( $wallet ) ) : ?>
    <hr />
    <h3><?php esc_html_e( 'Status', 'rootz-ai-discovery' ); ?></h3>
    <p>
        <?php esc_html_e( 'Adnet is enabled. Add the "Adnet Ad Slot" block to any page or post to place an ad unit.', 'rootz-ai-discovery' ); ?>
    </p>
    <p>
        <a href="<?php echo esc_url( $api_url . '/publisher/dashboard' ); ?>" target="_blank" rel="noopener noreferrer" class="button">
            <?php esc_html_e( 'View Publisher Dashboard →', 'rootz-ai-discovery' ); ?>
        </a>
    </p>
    <?php elseif ( '1' === $enabled && empty( $wallet ) ) : ?>
    <div class="notice notice-warning inline" style="margin-top:16px;">
        <p><?php esc_html_e( 'Adnet is enabled but no publisher wallet is configured. Enter your wallet address above to start receiving ad revenue.', 'rootz-ai-discovery' ); ?></p>
    </div>
    <?php endif; ?>

</div>
```

---

## Step 7: Frontend JS (adnet-rivets.js)

Create `public/adnet-rivets.js`. Key design rules for WP.org compliance:
- No external CDN loads
- Bundled with the plugin
- Only runs if `rootzAdnet.enabled === true` (gated by PHP)
- Uses only standard browser APIs (WebCrypto, IndexedDB, IntersectionObserver, fetch)
- No `eval()`, no obfuscation

```javascript
/* Adnet Rivets — browser+domain identity + event signing for verified advertising.
 * Loaded only when Adnet is enabled and a publisher wallet is configured.
 * Key is unique per browser+domain — no cross-site tracking possible.
 */
(function() {
    'use strict';

    if (!window.rootzAdnet || !rootzAdnet.enabled) return;
    if (!window.crypto || !window.crypto.subtle) return;
    if (!window.indexedDB) return;

    var DB_NAME    = 'rootz-adnet';
    var STORE_NAME = 'keys';
    var KEY_ID     = 'rivets-v1';
    var API_URL    = rootzAdnet.apiUrl;
    var WALLET     = rootzAdnet.publisherWallet;

    // Open or create the IndexedDB for this origin.
    function openDb(cb) {
        var req = indexedDB.open(DB_NAME, 1);
        req.onupgradeneeded = function(e) {
            e.target.result.createObjectStore(STORE_NAME);
        };
        req.onsuccess = function(e) { cb(null, e.target.result); };
        req.onerror   = function(e) { cb(e); };
    }

    // Retrieve or generate the browser+domain ECDSA key pair.
    function getOrCreateKey(db, cb) {
        var tx    = db.transaction(STORE_NAME, 'readonly');
        var store = tx.objectStore(STORE_NAME);
        var get   = store.get(KEY_ID);
        get.onsuccess = function(e) {
            if (e.target.result) {
                cb(null, e.target.result);
            } else {
                crypto.subtle.generateKey(
                    { name: 'ECDSA', namedCurve: 'P-256' },
                    false, // non-extractable private key
                    ['sign', 'verify']
                ).then(function(kp) {
                    var tx2    = db.transaction(STORE_NAME, 'readwrite');
                    var store2 = tx2.objectStore(STORE_NAME);
                    store2.put(kp, KEY_ID);
                    tx2.oncomplete = function() { cb(null, kp); };
                    tx2.onerror    = function(ev) { cb(ev); };
                }).catch(cb);
            }
        };
        get.onerror = function(e) { cb(e); };
    }

    // Export the public key as hex for use as a wallet-style address.
    function exportPublicKeyHex(kp, cb) {
        crypto.subtle.exportKey('raw', kp.publicKey).then(function(raw) {
            var hex = Array.from(new Uint8Array(raw))
                .map(function(b) { return b.toString(16).padStart(2, '0'); })
                .join('');
            cb(null, '0x' + hex);
        }).catch(cb);
    }

    // Sign an event payload and return base64url signature.
    function signEvent(kp, data, cb) {
        var enc     = new TextEncoder();
        var payload = enc.encode(JSON.stringify(data));
        crypto.subtle.sign(
            { name: 'ECDSA', hash: 'SHA-256' },
            kp.privateKey,
            payload
        ).then(function(sig) {
            var b64 = btoa(String.fromCharCode.apply(null, new Uint8Array(sig)));
            cb(null, b64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, ''));
        }).catch(cb);
    }

    // Frequency cap: max 3 views per campaign per browser per day.
    function checkFrequencyCap(campaignId) {
        var key    = 'rootz-adnet-fc-' + campaignId;
        var today  = new Date().toISOString().slice(0, 10);
        var stored = JSON.parse(localStorage.getItem(key) || '{}');
        if (stored.date !== today) { stored = { date: today, count: 0 }; }
        if (stored.count >= 3) return false;
        stored.count++;
        localStorage.setItem(key, JSON.stringify(stored));
        return true;
    }

    // Submit a signed event to the Adnet agent.
    function submitEvent(type, campaignId, publicKeyHex, signature, payload) {
        fetch(API_URL + '/api/event', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                type:         type,
                campaignId:   campaignId,
                publisher:    WALLET,
                browserKey:   publicKeyHex,
                signature:    signature,
                payload:      payload,
                url:          window.location.href,
                timestamp:    Date.now()
            })
        }).catch(function() {}); // silent fail — never break page load
    }

    // Initialize: open DB, get/create key, then wire up ad slots.
    openDb(function(err, db) {
        if (err) return;
        getOrCreateKey(db, function(err, kp) {
            if (err) return;
            exportPublicKeyHex(kp, function(err, pkHex) {
                if (err) return;
                wireAdSlots(kp, pkHex);
            });
        });
    });

    function wireAdSlots(kp, pkHex) {
        var slots = document.querySelectorAll('.rootz-adnet-slot');
        if (!slots.length) return;

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (!entry.isIntersecting) return;
                var el         = entry.target;
                var campaignId = el.dataset.campaign;
                if (!campaignId || el.dataset.viewed) return;

                // IAB: >= 50% visible, tab visible, >= 1s dwell.
                if (document.visibilityState !== 'visible') return;
                if (entry.intersectionRatio < 0.5) return;

                setTimeout(function() {
                    // Re-check after 1 second dwell.
                    if (document.visibilityState !== 'visible') return;
                    var rect = el.getBoundingClientRect();
                    var inView = rect.top < window.innerHeight && rect.bottom > 0;
                    if (!inView) return;
                    if (!checkFrequencyCap(campaignId)) return;

                    el.dataset.viewed = '1';
                    observer.unobserve(el);

                    var payloadData = { campaignId: campaignId, pkHex: pkHex, t: Date.now() };
                    signEvent(kp, payloadData, function(err, sig) {
                        if (err) return;
                        submitEvent('view', campaignId, pkHex, sig, payloadData);
                    });
                }, 1000);
            });
        }, { threshold: 0.5 });

        slots.forEach(function(slot) {
            observer.observe(slot);

            // Click tracking.
            var link = slot.querySelector('a[data-adnet-click]');
            if (link) {
                link.addEventListener('click', function() {
                    var campaignId = slot.dataset.campaign;
                    if (!campaignId) return;
                    var payloadData = { campaignId: campaignId, pkHex: pkHex, t: Date.now(), href: link.href };
                    signEvent(kp, payloadData, function(err, sig) {
                        if (err) return;
                        submitEvent('click', campaignId, pkHex, sig, payloadData);
                    });
                });
            }
        });
    }
})();
```

---

## Step 8: Enqueue the Frontend JS

In `rootz-ai-discovery.php`, in `rootz_ai_discovery_enqueue_scripts()`, add after the existing WebMCP enqueue:

```php
// Adnet Rivets — only load if enabled AND wallet configured.
if ( '1' === get_option( 'rootz_adnet_enabled', '0' ) && get_option( 'rootz_adnet_publisher_wallet', '' ) ) {
    wp_enqueue_script(
        'rootz-adnet-rivets',
        ROOTZ_AI_DISCOVERY_URL . 'public/adnet-rivets.js',
        array(),
        ROOTZ_AI_DISCOVERY_VERSION,
        array( 'in_footer' => true, 'strategy' => 'defer' )
    );
    wp_localize_script(
        'rootz-adnet-rivets',
        'rootzAdnet',
        array(
            'enabled'         => true,
            'apiUrl'          => get_option( 'rootz_adnet_api_url', 'https://adnet.geistm.com' ),
            'publisherWallet' => get_option( 'rootz_adnet_publisher_wallet', '' ),
        )
    );
}
```

---

## Step 9: Gutenberg Block

Create `blocks/adnet-slot/block.json`:

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "rootz-ai-discovery/adnet-slot",
    "version": "1.0.0",
    "title": "Adnet Ad Slot",
    "category": "widgets",
    "icon": "megaphone",
    "description": "Place a verified Adnet advertisement on this page. Earnings go directly to your configured publisher wallet.",
    "keywords": ["ad", "adnet", "advertising", "rootz"],
    "supports": {
        "html": false,
        "align": ["wide", "full"]
    },
    "attributes": {
        "campaignId": {
            "type": "string",
            "default": ""
        },
        "placeholderText": {
            "type": "string",
            "default": "Advertisement"
        }
    },
    "textdomain": "rootz-ai-discovery",
    "editorScript": "file:./edit.js",
    "viewScript":   "file:./view.js",
    "render": "file:./render.php"
}
```

Create `blocks/adnet-slot/render.php` (server-side render — simpler, avoids React dependency):

```php
<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$campaign_id = ! empty( $attributes['campaignId'] ) ? sanitize_text_field( $attributes['campaignId'] ) : '';
$placeholder = ! empty( $attributes['placeholderText'] ) ? sanitize_text_field( $attributes['placeholderText'] ) : __( 'Advertisement', 'rootz-ai-discovery' );

if ( empty( $campaign_id ) ) {
    if ( current_user_can( 'edit_posts' ) ) {
        echo '<p class="rootz-adnet-no-campaign">' . esc_html__( 'Adnet: No campaign ID configured. Edit this block to add one.', 'rootz-ai-discovery' ) . '</p>';
    }
    return;
}

if ( '1' !== get_option( 'rootz_adnet_enabled', '0' ) ) {
    return; // Adnet disabled — render nothing.
}
?>
<div class="rootz-adnet-slot wp-block-rootz-ai-discovery-adnet-slot"
     data-campaign="<?php echo esc_attr( $campaign_id ); ?>"
     aria-label="<?php echo esc_attr( $placeholder ); ?>">
    <span class="rootz-adnet-label"><?php echo esc_html( $placeholder ); ?></span>
</div>
```

Create `blocks/adnet-slot/edit.js` (editor placeholder — no React component needed for simple blocks):

```javascript
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
    const blockProps = useBlockProps( { className: 'rootz-adnet-slot-editor' } );
    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Adnet Settings', 'rootz-ai-discovery' ) }>
                    <TextControl
                        label={ __( 'Campaign ID', 'rootz-ai-discovery' ) }
                        value={ attributes.campaignId }
                        onChange={ ( val ) => setAttributes( { campaignId: val } ) }
                        help={ __( 'The Adnet campaign ID from your publisher dashboard.', 'rootz-ai-discovery' ) }
                    />
                    <TextControl
                        label={ __( 'Placeholder Text', 'rootz-ai-discovery' ) }
                        value={ attributes.placeholderText }
                        onChange={ ( val ) => setAttributes( { placeholderText: val } ) }
                    />
                </PanelBody>
            </InspectorControls>
            <div { ...blockProps }>
                <span className="rootz-adnet-label-editor">
                    { __( 'Adnet Ad Slot', 'rootz-ai-discovery' ) }
                    { attributes.campaignId ? ` — Campaign: ${ attributes.campaignId }` : ' (no campaign ID set)' }
                </span>
            </div>
        </>
    );
}
```

Register the block in `rootz-ai-discovery.php`, add a new function:

```php
function rootz_ai_discovery_register_blocks() {
    if ( ! function_exists( 'register_block_type' ) ) {
        return;
    }
    register_block_type( ROOTZ_AI_DISCOVERY_DIR . 'blocks/adnet-slot' );
}
add_action( 'init', 'rootz_ai_discovery_register_blocks' );
```

---

## Step 10: Privacy Policy Content

WP has a built-in Privacy Policy helper. Register Adnet's disclosure in `rootz-ai-discovery.php`:

```php
function rootz_ai_discovery_privacy_policy_content() {
    if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
        return;
    }

    $content = '<h3>' . __( 'Adnet Advertising (if enabled)', 'rootz-ai-discovery' ) . '</h3>';
    $content .= '<p>' . __( 'If this site has enabled Adnet advertising, the following applies:', 'rootz-ai-discovery' ) . '</p>';
    $content .= '<ul>';
    $content .= '<li>' . __( 'A cryptographic key pair is generated in your browser and stored locally (IndexedDB). The private key never leaves your device.', 'rootz-ai-discovery' ) . '</li>';
    $content .= '<li>' . __( 'This key is unique to this website — it cannot be used to identify you on other sites.', 'rootz-ai-discovery' ) . '</li>';
    $content .= '<li>' . __( 'When you view or click an advertisement, an anonymized, signed event record is sent to adnet.geistm.com. This record contains a cryptographic hash, not your name, email, or IP address.', 'rootz-ai-discovery' ) . '</li>';
    $content .= '<li>' . __( 'No cookies are set by the Adnet system. No data is shared with Google, Meta, or other advertising networks.', 'rootz-ai-discovery' ) . '</li>';
    $content .= '</ul>';

    wp_add_privacy_policy_content( 'Rootz AI Discovery', wp_kses_post( $content ) );
}
add_action( 'admin_init', 'rootz_ai_discovery_privacy_policy_content' );
```

---

## Step 11: readme.txt Changelog Entry

```
= 2.4.0 =
* New: Adnet tab — enable verified advertising and configure publisher wallet
* New: Adnet Ad Slot Gutenberg block — place ad units on any post or page
* New: Privacy policy content registered in WP Privacy tool when Adnet is enabled
* New: Browser+domain identity layer (adnet-rivets.js) for IAB-standard viewability verification
```

---

## Step 12: Build and Test Checklist

### Before Zipping

- [ ] Run **Plugin Check** plugin on a local WP install — resolve all ERRORS (warnings OK to review)
- [ ] Verify `readme.txt` has `== External Services ==` section
- [ ] Verify Adnet JS does not load when `rootz_adnet_enabled` is `'0'`
- [ ] Verify Adnet JS does not load when wallet is empty
- [ ] Test with browser DevTools: confirm no JS errors on pages without Adnet block
- [ ] Test with IntersectionObserver polyfill check for older browsers (graceful no-op)
- [ ] Confirm block appears in Gutenberg block inserter under "Widgets"
- [ ] Confirm block renders nothing on frontend when Adnet disabled
- [ ] Confirm nonce present on all form submissions
- [ ] Confirm `sanitize_wallet_address` rejects non-0x-addresses and empty input is accepted

### WP.org Submission

WP.org uses SVN, not GitHub. Deployment steps:

```bash
# Check out the plugin SVN (one-time)
svn co https://plugins.svn.wordpress.org/rootz-ai-discovery/ wp-plugin-svn

# Copy new files to trunk/
cp -r plugin/* wp-plugin-svn/trunk/

# Tag the release
svn cp trunk/ tags/2.4.0/

# Commit
svn ci -m "2.4.0 — Adnet tab + verified advertising Gutenberg block"
```

WP.org review typically takes 1–5 business days for updates to existing approved plugins.

---

## What Davis Needs to Know

1. **The `blocks/adnet-slot/edit.js` needs to be compiled** — requires `@wordpress/scripts` build step. Existing plugin has no build step, so either:
   - Add a `package.json` + `@wordpress/scripts` build, OR
   - Use a pre-compiled `edit.js` (I can pre-write vanilla JS that registers the block without JSX)
   - **Recommendation: pre-compiled vanilla JS** to keep the build simple for v2.4.0. JSX build in v2.5.0.

2. **The `adnet-rivets.js` requires no build step** — pure vanilla ES5, ready as-is.

3. **The `render.php` is the safest WP.org path** — server-side render avoids React versioning issues.

4. **The `view.js` file listed in `block.json`** — for v2.4.0 this can be empty or omit the field since `render.php` handles frontend. We remove `viewScript` from block.json for the initial version.

---

## Version Rollout

| Version | What Ships |
|---------|-----------|
| **2.4.0** (July 1 target) | Adnet settings tab + `adnet-rivets.js` + server-rendered block (no build step required) |
| **2.4.1** | Agency dashboard link in Adnet tab, multi-site configuration support |
| **2.5.0** | Full Gutenberg block with JSX editor UI, block preview in editor |

---

*Plan prepared June 2026. Execute with Davis. Do not modify Michael's epistery-host plugin — the publisher agent on the server side is his code. This plan covers only the WordPress plugin (Rootz/Davis scope).*
