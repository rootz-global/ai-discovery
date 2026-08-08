=== Rootz AI Discovery ===
Contributors: skswave
Tags: ai, seo, discovery, structured-data, ai-agent
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Make your WordPress site AI-agent-ready. Structured identity, policies, content API, and WebMCP tools at /.well-known/ai.

== Description ==

When an AI agent visits your WordPress site today, it has to scrape HTML, guess your organization, and parse legal pages written for humans. **Rootz AI Discovery** fixes this by serving structured, machine-readable data that AI agents can understand instantly.

**You get an AI Readiness Score.** Activate the plugin and it grades your site out of 120 points against the published AI Discovery Standard — then tells you exactly which fixes are worth the most points, and takes you to the right screen to make them. Most sites start around a C. The grade sits in your admin sidebar so you can see it change as you improve.

**What it does:**

* Serves `/.well-known/ai` with your organization identity, capabilities, and policies (RFC 8615)
* Structured content endpoint at `/.well-known/ai/content` with pages, posts, media, custom types
* Auto-generates knowledge base at `/.well-known/ai/knowledge`
* AI-optimized content feed at `/.well-known/ai/feed`
* Machine-readable policies at `/.well-known/ai/policies`
* Adds `<link rel="ai-discovery">` tag and HTTP Link header to every page
* Generates `/llms.txt` from your site structure
* Provides REST API endpoints for AI agents (`/wp-json/rootz/v1/`)
* Registers WebMCP tools for browser-based AI assistants
* Machine-readable content licensing (CC-BY, CC0, All Rights Reserved, etc.)
* AI training opt-in/opt-out declaration
* **Cryptographic signing** — every ai.json response and llms.txt is digitally signed (ECDSA secp256k1), proving origin and content integrity
* Digital identity via plugin wallet (Ethereum-compatible address generated locally)
* Content assertion types (factual, editorial, creative-work) per item
* AI access metrics and analytics dashboard
* Self-scoring status endpoint for monitoring

**Signed for origin and quality.** Each plugin install generates a unique cryptographic identity. Every manifest is digitally signed so AI agents can verify the data came from your site and hasn't been tampered with. No other WordPress plugin does this. For more information on managing your digital identity, see [rootz.global/identity](https://rootz.global/identity).

**Core data is generated locally** from your existing WordPress content. Optional AI-powered features use external services (see Third-Party Services below).

**Open standard.** The AI Discovery Standard is CC-BY-4.0 licensed. See [rootz.global/ai-discovery](https://rootz.global/ai-discovery).

= Third-Party Services =

This plugin connects to external services in the following cases. All connections use HTTPS.

**1. Adnet Verified Advertising (optional — disabled by default)**
When you enable Adnet in Settings → AI Discovery → Adnet and configure a publisher wallet, the plugin connects to the GeistM Adnet network to serve verified advertisements:
* Service: `adnet.geistm.com`
* Data sent: Publisher wallet address (public blockchain address), anonymized event counts, cryptographic batch hashes. No name, email, or IP address is transmitted.
* Browser data: A cryptographic key pair unique to this browser+domain is generated locally and stored in IndexedDB. The private key never leaves the visitor's device. This key cannot be used to track visitors across other sites.
* When: Only when Adnet is enabled AND a publisher wallet address is configured
* Terms: https://geistm.com/terms
* Privacy: https://geistm.com/privacy

**2. Polygon Blockchain RPC (optional — when Adnet is enabled)**
When Adnet is enabled, the plugin verifies campaign contract status via a public Polygon RPC node:
* Service: `polygon-bor-rpc.publicnode.com`
* Data sent: Campaign contract address (public on-chain data only). No user data.
* When: Same condition as Adnet above

**3. Rootz AI Proxy (optional)**
When you use the "Auto-Populate with AI" feature to generate your site description, summary, or core concepts, the plugin sends your site URL and basic metadata to:
* Service: `dev.epistery.host/agent/rootz/ai-proxy/`
* Data sent: Site URL, title, tagline
* When: Only when you click "Auto-Populate" in the admin panel
* Privacy: [rootz.global/privacy](https://rootz.global/privacy)

**2. Anthropic API (optional)**
If you enter your own Anthropic API key in settings, the plugin can call the Anthropic API directly as a fallback for AI content generation:
* Service: `api.anthropic.com`
* Data sent: Site URL, title, tagline (same as above)
* When: Only when you click "Auto-Populate" and have entered an API key
* Privacy: See https://www.anthropic.com/legal/privacy

**3. Plugin Updates (automatic)**
The plugin checks for available updates from the Rootz update server:
* Service: `rootz.global/api/plugin/update.json`
* Data sent: None (read-only GET request)
* When: Automatically every 12 hours (uses WordPress transients)
* Note: Disabled when the plugin is installed from WordPress.org (WP.org provides updates instead)
* Privacy: [rootz.global/privacy](https://rootz.global/privacy)

**4. License Verification (automatic when configured)**
If you enter an Owner Identity in the Account tab, the plugin verifies your subscription status:
* Service: `rootz.global/api/license/status`
* Data sent: Your Owner Identity address (a public blockchain address, not personal data)
* When: On admin page load, cached for 12 hours
* Privacy: [rootz.global/privacy](https://rootz.global/privacy)

== Installation ==

1. Upload the `rootz-ai-discovery` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to Settings > AI Discovery to customize your organization info
4. Visit `yoursite.com/.well-known/ai` to verify it's working
5. Scan your site at [rootz.global/ai-discovery](https://rootz.global/ai-discovery) to check your score

== Frequently Asked Questions ==

= Does this slow down my site? =

No. The plugin serves cached JSON responses and adds a single `<link>` tag to your HTML head. The WebMCP script is ~4KB and loads asynchronously.

= Does it send data to external servers? =

Core functionality runs entirely locally. The plugin generates all structured data from your existing WordPress content without any external calls.

Two optional features use external services: (1) AI-powered auto-populate sends your site URL to generate descriptions, and (2) license verification checks your subscription status. Both are clearly disclosed above. The plugin also checks for updates from rootz.global every 12 hours (a read-only request with no user data).

= What is the AI Discovery Standard? =

An open specification (CC-BY-4.0) for how websites should present structured information to AI agents. Think of it as "robots.txt for AI identity" — instead of blocking, it enables proper interaction. See [rootz.global/ai-discovery](https://rootz.global/ai-discovery).

= What is the content endpoint? =

The content endpoint at `/.well-known/ai/content` serves your pages, posts, media, and custom post types as structured JSON so AI agents can understand your site without scraping HTML. Each item includes an assertion type (factual, editorial, creative-work) so agents know how to interpret the information.

= What is WebMCP? =

WebMCP is a browser API that lets websites register tools with AI assistants. This plugin automatically registers tools like `getOrganizationInfo` and `getPolicies` so AI assistants can read your site's structured data directly.

= Do I need to flush permalinks after installation? =

The plugin does this automatically on activation. If `/.well-known/ai` returns a 404, go to Settings > Permalinks and click Save (this flushes rewrite rules).

== Screenshots ==

1. What AI Sees — live preview of your ai.json manifest that AI agents read
2. Identity — configure your organization name, mission, sector, and digital identity
3. Content — control which pages, posts, and media AI agents can access
4. Policies — set content licensing, quoting permissions, and AI training controls
5. Account & Wallet — plugin wallet, network status, subscription, and AI proxy settings

== Changelog ==

= 2.5.0 =
* New: AI Discovery now has its own top-level menu, with your current grade shown in the sidebar. Previously it was tucked under Settings, so nothing in wp-admin indicated the plugin was there at all.
* New: Activating the plugin takes you straight to your AI Readiness Score and tells you the single highest-value thing to fix next.
* New: Each unfinished check now shows what it is worth in points, so it is clear which fixes matter most.
* Fix: Updating the plugin no longer leaves `/.well-known/ai` reporting the previous version number (stale signed-manifest cache — BUG-004).
* Fix: Admin styles now load correctly from the new menu location.
* Change: The Adnet tab is hidden unless advertising is switched on. The feature is unchanged; it is simply no longer the first thing a new install sees.
* Compatibility: Tested up to WordPress 7.0.

= 2.4.0 =
* New: Adnet tab — enable verified advertising and configure your publisher wallet
* New: Adnet Ad Slot Gutenberg block — place cryptographically-verified ad units on any post or page
* New: adnet-rivets.js — browser+domain identity layer for IAB-standard viewability verification (loads only when Adnet enabled)
* New: Privacy policy disclosure registered in WP Privacy tool when Adnet is enabled
* Disclosure: Adnet feature connects to adnet.geistm.com (documented in Third-Party Services, disabled by default)

= 2.3.3 =
* Fix: License class now included in WP.org distribution (subscription and licensing features restored)
* Fix: Stripe checkout redirects back to user's WordPress site after payment
* New: Auto-register site wallet when owner identity is saved (eliminates manual registration step)
* New: Post-checkout auto-activation detects return from Stripe and refreshes license immediately
* UX: Renamed Owner Identity label to License Key / Owner Identity for clarity

= 2.3.2 =
* Fix: Moved inline network-status script to wp_add_inline_script() with wp_localize_script() for translations
* Fix: Removed Domain Path header (not needed for WP.org-hosted plugins)
* Fix: Updated Anthropic privacy URL to working path
* All view template variables prefixed with rootz_ per WordPress coding standards
* Security: Sanitize nonce inputs with sanitize_text_field(wp_unslash()) before wp_verify_nonce
* Security: REST /status and /context endpoints now require manage_options permission
* Security: JSON-LD output no longer uses JSON_UNESCAPED_SLASHES to prevent script context breakout
* WordPress.org compliance: License infrastructure made optional (excluded from WP.org distribution)

= 2.2.1 =
* CRITICAL FIX: Saving one settings tab no longer resets fields on other tabs
* Split shared settings group into per-tab groups (identity, content, policies, tools, account)
* This fixes the bug where AI Summary, Core Concepts, and other fields were lost after saving

= 2.2.0 =
* Adoption Registry: Opt-in checkbox to share domain with AI Discovery Standard registry
* Two-level identity documentation (Owner Identity Contract vs Plugin Wallet)
* Registry consent syncs domain + version to rootz.global/api/registry

= 2.1.1 =
* WordPress Plugin Check compliance — all errors resolved
* Improved output escaping across admin views (wp_kses, intval, esc_attr)
* Added translators comments for all internationalized strings
* Script loading with defer strategy for better performance
* Updated Tested up to WordPress 6.9
* Cleaner distribution zip (no vendor test files)

= 2.1.0 =
* Added /.well-known/ai/policies endpoint (rewrite rule + handler)
* Fixed generator.url in ai.json to point to correct plugin page
* Updated pricing tier integration (Free/Standard/Pro with PLAN_CONFIG)
* Self-hosted updater skips when installed from WordPress.org
* License file added for WordPress.org compliance

= 2.0.1 =
* Updated pricing tiers (Free/Standard $5/Pro $10)
* Fixed View Plans link in Account tab
* Improved checkout flow integration

= 2.0.0 =
* secp256k1 plugin wallet for cryptographic signing (AES-256-CBC encrypted storage)
* Wallet-authenticated AI proxy — auto-populate without user API key
* AI-powered identity extraction (legalName, sector, founded, headquarters)
* AI-generated site summary and core concepts
* Owner wallet delegation support
* Quick Start guide tab for new installs
* Analytics tab with AI access metrics and agent classification
* 8-tab admin interface (Identity, Content, Policies, Tools, Viewer, Analytics, Account, Quick Start)
* REST API search, verify, and status scoring endpoints
* Admin manifest review workflow (approve changes before re-signing)
* Self-scoring status endpoint (8 categories, 100-point scale, A-F grades)
* Tools manifest v1.1.0 with 3 categories (discovery, actions, meta)

= 1.8.0 =
* Per-page SHA-256 content hashes in ai.json pages[]
* All 5 JSON endpoints signed (not just ai.json)
* Contact fields: operator email, AI support email, privacy contact
* Generator metadata in ai.json
* Improved coreConcepts extraction
* 100-word policy summaries
* Full text option on policies endpoint (?full_text=1)

= 1.7.0 =
* Content endpoint with assertion types (factual, editorial, creative-work)
* Segmented content access: /content/pages, /content/posts, /content/media
* Full text option: serve complete post/page content
* Media support with EXIF data

= 1.2.0 =
* v1.2 URL hierarchy: all endpoints under /.well-known/ai/ (RFC 8615)
* Digital identity: blockchain wallet address, network, identity contract
* Organization details: legal name, founded, headquarters, contact info
* AI summary and core concepts fields
* 4-tab admin: Identity, Content, Policies, Tools & Preview
* Toggleable tier endpoints (knowledge, feed, content)
* Dynamic endpoint status in Tools tab

= 0.1.0 =
* Initial release
* AI Discovery endpoint at /.well-known/ai
* HTML link tag and HTTP Link header
* Admin settings page (Identity, Policies, Tools tabs)
* REST API endpoints (ai.json, policies, knowledge, feed, tools)
* WebMCP tool registration (getOrganizationInfo, getPolicies, getKnowledge, getFeed)
* Auto-generated llms.txt
* Content licensing declarations
* AI training opt-in/opt-out

== Upgrade Notice ==

= 2.4.0 =
Adds optional Adnet verified advertising tab and Gutenberg block. Adnet is disabled by default — no behaviour change unless you enable it in Settings → AI Discovery → Adnet.

= 2.1.1 =
WordPress Plugin Check compliance fixes, improved output escaping, and script defer loading.

= 2.1.0 =
Adds /.well-known/ai/policies endpoint, WordPress.org compliance improvements, and pricing tier updates.

== Privacy ==

= Data Storage =
All structured data (organization identity, policies, content) is generated from your existing WordPress content and stored in wp_options. No user data is collected.

= External Services =
The plugin connects to external services only when specific features are used. See the "Third-Party Services" section in the Description tab for complete details including what data is sent, when, and links to each service's privacy policy.

= Cookies =
This plugin does not set any cookies.

= Analytics =
The plugin tracks AI agent visits to your /.well-known/ai endpoints in a local database table (wp_rootz_metrics). This data stays on your server and is never transmitted externally. You can view it in the Analytics tab.
