# Changelog

All notable changes to the Rootz AI Discovery plugin are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/).

## [2.3.3] — 2026-03 (current; deployed to discover.rootz.global)

### Fixed
- License class now included in the WordPress.org distribution — subscription and licensing features restored (had been excluded in 2.3.1 for review).
- Stripe checkout now redirects back to the user's WordPress site after payment.

### Added
- Auto-register the site wallet when the owner identity is saved (eliminates the manual registration step).
- Post-checkout auto-activation: detects return from Stripe and refreshes the license immediately.

### Changed
- Renamed the "Owner Identity" label to "License Key / Owner Identity" for clarity.

## [2.3.2] — 2026-03 (WordPress.org review fixes, part 2)

### Security
- **REST `/status` and `/context` endpoints now require `manage_options`** (changed from public `__return_true`). They are admin management/setup endpoints; unauthenticated and AI-agent requests now receive HTTP 401 (`rest_forbidden`) by design. The seven agent-facing endpoints (static discovery + searchContent, getPage, verifyPageHash, tools) remain public and signed.
- Sanitize nonce inputs with `sanitize_text_field( wp_unslash( ... ) )` before `wp_verify_nonce()` (6 locations).
- JSON-LD output no longer uses `JSON_UNESCAPED_SLASHES`, preventing `</script>` context breakout.

### Fixed
- Moved inline network-status script to `wp_add_inline_script()` with `wp_localize_script()` for translatable strings.
- Removed the Domain Path header (not needed for WP.org-hosted plugins); updated Anthropic privacy URL.

### Changed
- Prefixed ~200 view-template local variables with `rootz_` per WordPress/PHPCS naming standards.
- License infrastructure made optional and excluded from the WordPress.org distribution.

## [2.3.1] — 2026-03-11 (WordPress.org review fixes, part 1)

### Fixed
- First pass on WordPress.org Plugin Directory review feedback across 5 categories: nonce sanitization, REST permission callbacks, JSON-LD escaping, license-class isolation (`class_exists()` guards), and PHPCS variable prefixing.
- WordPress Plugin Check (PCP): 0 errors (32 advisory warnings).
- Removed `BUGS.md` from the WordPress.org zip (PCP `unexpected_markdown_file`).

**Reference**: context archive `2026-03-11-wporg-review-fixes-v231-submission.md`.

## [2.3.0] — 2026-03-04

### Added
- **`getPage` tool** (`/wp-json/rootz/v1/page`): read any published page/post as structured markdown with origin provenance, content hash, freshness metadata, policy permissions, and an ECDSA signature — the "conversation mode" tool. Tool count 8 → 9.
- **Freshness metadata (`_freshness`)**: adaptive shelf life on content responses (1 hour for recently edited, up to 30 days for stable).
- **Origin provenance (`_origin` + `_provenance`)**: embedded in every dynamic response (domain, publishedAt, modifiedAt, servedAt, signer); survives scraping and caching.
- **searchContent pagination**: `offset` parameter plus `totalFound`, `hasMore`, `nextOffset`; limit raised to 50. Added `type` filter (`post`/`page`).
- **llms.txt / llms-full.txt signed generation** (spec-compliant, ECDSA footer) with full Content-tab admin settings and help tips on every setting.

## [2.2.1] — 2026-03-02

### Fixed
- **CRITICAL: Settings data loss when saving any tab.** All 7 forms across 5 tabs shared a single WordPress settings group (`rootz_ai_discovery`). When any form was submitted to `options.php`, WordPress processed all ~30 registered settings and zeroed out any not in that form's POST data. Saving the Policies tab would wipe Identity tab fields (AI Summary, Core Concepts, org name, etc.).
- Split into 5 per-tab settings groups: `rootz_identity`, `rootz_content`, `rootz_policies`, `rootz_tools`, `rootz_account`.

### Technical Detail
WordPress `options.php` iterates all registered settings in a group on form submit. For any setting not present in `$_POST`, the sanitize callback receives an empty string, effectively resetting it. The fix assigns each tab's form to its own group so only that tab's fields are processed.

**Files changed**: `admin/class-rootz-admin.php` (register_settings), `admin/views/settings-identity.php`, `admin/views/settings-content.php`, `admin/views/settings-policies.php`, `admin/views/settings-tools.php`, `admin/views/settings-account.php`

## [2.2.0] — 2026-03-02

### Added
- Adoption Registry: opt-in checkbox in Account tab to share domain with AI Discovery Standard registry at `rootz.global/api/registry`
- Plugin sends domain, plugin version, spec version, and wallet address on consent
- Registry leave on unchecking (soft-delete with `left_at` timestamp)
- Two-level identity documentation in ai.context.md (Owner Identity Contract vs Plugin Wallet)

### Server-Side (rootz.global)
- `registry` SQLite table with domain, plugin_version, spec_version, wallet, joined_at, left_at, last_seen
- 4 API endpoints: `POST /api/registry/join`, `POST /api/registry/leave`, `GET /api/registry`, `GET /api/registry/count`
- CORS enabled for cross-origin plugin calls

**Files changed**: `admin/class-rootz-admin.php`, `admin/views/settings-account.php`

## [2.1.1] — 2026-02-26

### Fixed
- WordPress Plugin Check compliance — all errors resolved
- Improved output escaping across admin views (`wp_kses`, `intval`, `esc_attr`)
- Added translators comments for all internationalized strings
- Script loading with `defer` strategy for better performance
- Updated Tested up to WordPress 6.9
- Cleaner distribution zip (no vendor test files)

## [2.1.0] — 2026-02-24

### Added
- `/.well-known/ai/policies` endpoint (rewrite rule + handler)
- Self-hosted updater skips when installed from WordPress.org
- License file added for WordPress.org compliance

### Fixed
- `generator.url` in ai.json pointed to wrong page
- Updated pricing tier integration (Free/Standard/Pro with PLAN_CONFIG)

## [2.0.1] — 2026-02-23

### Fixed
- Updated pricing tiers (Free/Standard $5/Pro $10)
- Fixed View Plans link in Account tab
- Improved checkout flow integration

## [2.0.0] — 2026-02-22

### Added
- secp256k1 plugin wallet for cryptographic signing (AES-256-CBC encrypted storage)
- Wallet-authenticated AI proxy — auto-populate without user API key
- AI-powered identity extraction (legalName, sector, founded, headquarters)
- AI-generated site summary and core concepts
- Owner wallet delegation support
- Quick Start guide tab for new installs
- Analytics tab with AI access metrics and agent classification
- 8-tab admin interface (Identity, Content, Policies, Tools, Viewer, Analytics, Account, Quick Start)
- REST API search, verify, and status scoring endpoints
- Admin manifest review workflow (approve changes before re-signing)
- Self-scoring status endpoint (8 categories, 100-point scale, A-F grades)
- Tools manifest v1.1.0 with 3 categories (discovery, actions, meta)

## [1.8.0] — 2026-02-20

### Added
- Per-page SHA-256 content hashes in ai.json `pages[]`
- All 5 JSON endpoints signed (not just ai.json)
- Contact fields: operator email, AI support email, privacy contact
- Generator metadata in ai.json
- Improved coreConcepts extraction
- 100-word policy summaries
- Full text option on policies endpoint (`?full_text=1`)

## [1.7.0] — 2026-02-19

### Added
- Content endpoint with assertion types (factual, editorial, creative-work)
- Segmented content access: `/content/pages`, `/content/posts`, `/content/media`
- Full text option: serve complete post/page content
- Media support with EXIF data

## [1.2.0] — 2026-02-17

### Added
- v1.2 URL hierarchy: all endpoints under `/.well-known/ai/` (RFC 8615)
- Digital identity: blockchain wallet address, network, identity contract
- Organization details: legal name, founded, headquarters, contact info
- AI summary and core concepts fields
- 4-tab admin: Identity, Content, Policies, Tools & Preview
- Toggleable tier endpoints (knowledge, feed, content)
- Dynamic endpoint status in Tools tab

## [0.1.0] — 2026-02-13

### Added
- Initial release
- AI Discovery endpoint at `/.well-known/ai`
- HTML link tag and HTTP Link header
- Admin settings page (Identity, Policies, Tools tabs)
- REST API endpoints (ai.json, policies, knowledge, feed, tools)
- WebMCP tool registration (getOrganizationInfo, getPolicies, getKnowledge, getFeed)
- Auto-generated `llms.txt`
- Content licensing declarations
- AI training opt-in/opt-out
