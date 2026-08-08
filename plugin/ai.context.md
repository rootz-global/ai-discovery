# AI Context — Rootz AI Discovery Plugin

> This file is the internal reference manual for the Rootz AI Discovery WordPress
> plugin. It documents features, architecture, deployment patterns, and how we do
> things. Read this before working on the plugin.

## What This Plugin Does

The Rootz AI Discovery plugin makes any WordPress site machine-readable for AI
agents. It creates structured endpoints that tell AI agents who you are, what
your policies are, what content you have, and what tools are available — all
cryptographically signed, all served in real time from the owner's WordPress database.

**Without this plugin**: AI agents scrape raw HTML, guess who runs the site,
don't know if they can quote your content, and hallucinate missing information.

**With this plugin**: AI agents get clean structured data with verified identity,
explicit permissions, content integrity hashes, freshness metadata, and origin
provenance — the website learns to speak AI.

## Plugin Version & Spec

- **Plugin**: Rootz AI Discovery v2.3.3
- **Standard**: AI Discovery Standard v1.2.0
- **License**: GPLv2+ (plugin), CC-BY-4.0 (standard specification)
- **Requires**: WordPress 6.0+, PHP 7.4+
- **Optional**: PHP GMP extension (for cryptographic signing)
- **Lab site**: discover.rootz.global (Oracle server 141.148.25.214)
- **Marketing site**: discover.rootz.global (WordPress, product pages + blog)
- **Scanner/Spec site**: rootz.global (Express, scanner API + standard)
- **Lab admin**: `rootzadmin` / `RzLab2026Admin`
- **Lab wallet**: `0xD08914339B176C36C49D9827733599e1c4e5DAfF`

## Core Architecture: Teaching the Web to Speak AI

The plugin acts as a **translator** between WordPress's dynamic HTML pages and
AI's need for structured, clean, permissioned content. Every WordPress page is
dynamically generated from the database. Our plugin intercepts that and serves it as:

- **Clean markdown** (not HTML soup)
- **With metadata** (author, date, type, assertion type, word count)
- **With permission signals** (license, quoting policy, training policy)
- **With integrity proofs** (SHA-256 content hash, ECDSA signature)
- **With freshness** (shelf life / TTL so AI knows when to come back)
- **With origin provenance** (embedded stamps that survive scraping/caching)
- **Under the operator's control** (they choose what's exposed)

### The Three Modes

| Mode | What | How |
|------|------|-----|
| **Broadcast** | Static snapshots (ai.json, llms.txt) | AI reads the brochure |
| **Search** | Interactive queries (searchContent) | AI asks questions |
| **Conversation** | On-demand page reads (getPage) | AI reads any page as markdown |

### Freshness Metadata (`_freshness`)

Every dynamic response includes shelf life so AI agents know when to re-fetch:

| Content Age | TTL | Policy | Meaning |
|-------------|-----|--------|---------|
| < 1 day | 1 hour | `real-time` | Actively being edited |
| 1-7 days | 1 day | `daily` | Recent content |
| 7-30 days | 1 week | `weekly` | Settling down |
| > 30 days | 30 days | `monthly` | Stable content |

### Origin Provenance (`_origin` + `_provenance`)

Every response embeds origin metadata that survives scraping, caching, and
training. Even if this content ends up in a Wayback Machine archive or AI
training set, the provenance travels with it:

```json
"_origin": {
    "domain": "discover.rootz.global",
    "publishedAt": "2026-02-21T23:04:25Z",
    "modifiedAt": "2026-02-26T14:02:59Z",
    "servedAt": "2026-03-04T20:21:47Z",
    "signer": "0xD089..."
},
"_provenance": {
    "origin": "discover.rootz.global",
    "servedBy": "rootz-ai-discovery/2.3.3",
    "signer": "0xD089...",
    "specVersion": "1.2.0",
    "standard": "https://rootz.global/ai-discovery"
}
```

## Endpoints & Tools (9 total)

### Static / Discovery Endpoints

| Endpoint | URL | Purpose |
|----------|-----|---------|
| Discovery | `/.well-known/ai` | Main JSON manifest (identity, pages, signing) |
| Policies | `/.well-known/ai/policies` | License, terms, quoting/training permissions |
| Knowledge | `/.well-known/ai/knowledge` | About, products, glossary |
| Feed | `/.well-known/ai/feed` | AI-optimized blog feed (last 20 posts) |
| Content | `/.well-known/ai/content` | Structured content (pages/posts/media) |
| Tools | `/.well-known/ai/tools` | Tool manifest (what AI agents can call) |
| llms.txt | `/llms.txt` | Concise site overview for LLMs (signed) |
| llms-full.txt | `/llms-full.txt` | Full content variant (signed, opt-in) |

### Interactive REST API Tools

| Tool | Endpoint | Parameters | Purpose | Access |
|------|----------|------------|---------|--------|
| **searchContent** | `GET /wp-json/rootz/v1/search` | `q` (required), `limit`, `offset`, `type` | Search site content with pagination | Public |
| **getPage** | `GET /wp-json/rootz/v1/page` | `path` (required) | Read any page as structured markdown with provenance | Public |
| **verifyPageHash** | `GET /wp-json/rootz/v1/verify` | `page` (required) | Verify content integrity against signed manifest | Public |
| **getStatus** | `GET /wp-json/rootz/v1/status` | none | Site AI readiness score (0-100, graded A-F) | **Admin only** |
| **getContext** | `GET /wp-json/rootz/v1/context` | none | AI assistant context (this info + live status) | **Admin only** |

> **Access note (v2.3.1+):** `getStatus` and `getContext` are **admin-only** —
> they require `current_user_can('manage_options')` and return HTTP 401
> (`rest_forbidden`) to logged-out/agent requests. This was changed from public
> (`__return_true`) during the WordPress.org review: they are management/setup
> console endpoints, not agent-facing tools. A 401 on these two from an
> unauthenticated request is **expected behavior, not a bug**. The seven
> agent-facing endpoints (static discovery + searchContent, getPage,
> verifyPageHash, tools) remain public and signed.

### The AI Agent Flow

1. **Discover**: Read `/.well-known/ai` or `/llms.txt` → learn who the site is
2. **Search**: `searchContent?q=pricing` → find relevant pages
3. **Read**: `getPage?path=/pricing/` → get full content as clean markdown
4. **Verify**: `verifyPageHash?page=/pricing/` → confirm content integrity
5. **Paginate**: `searchContent?q=AI&offset=10&limit=10` → get more results

### searchContent Pagination

The search tool now supports full pagination:

```
GET /wp-json/rootz/v1/search?q=AI&limit=10&offset=0

Response:
{
    "query": "AI",
    "results": [...],
    "returned": 10,
    "totalFound": 47,
    "offset": 0,
    "hasMore": true,
    "nextOffset": 10,
    "_provenance": {...}
}
```

Use `nextOffset` to fetch the next page. Filter by `type=post` or `type=page`.

### getPage Response Shape

```
GET /wp-json/rootz/v1/page?path=/about/

Response:
{
    "path": "/about/",
    "title": "About Us",
    "url": "https://example.com/about/",
    "type": "page",
    "assertionType": "factual",
    "author": "Admin",
    "wordCount": 296,
    "content": "## About Us\n\nWe build infrastructure for...",
    "contentHash": "sha256:abc123...",
    "policies": {
        "license": "CC-BY-4.0",
        "quoting": "allowed",
        "training": "not-permitted",
        "caching": "cache_24h"
    },
    "_origin": { "domain": "...", "publishedAt": "...", "modifiedAt": "...", "servedAt": "...", "signer": "..." },
    "_freshness": { "maxAge": 86400, "freshUntil": "...", "refreshPolicy": "daily", "contentAge": "6.3 days" },
    "_provenance": { "origin": "...", "servedBy": "rootz-ai-discovery/2.3.3", "signer": "...", ... },
    "_signature": { "signer": "...", "contentHash": "...", "signature": "0x...", "method": "ecdsa-secp256k1" }
}
```

## llms.txt Implementation

The plugin generates spec-compliant llms.txt (llmstxt.org) with signed attestation.

### Two Variants

| File | URL | Default | Content |
|------|-----|---------|---------|
| **llms.txt** | `/llms.txt` | Enabled | Concise: title, description, page links, post links, policies, agent endpoints |
| **llms-full.txt** | `/llms-full.txt` | Disabled | Full content: everything above + complete page/post text as markdown |

### llms.txt Sections

1. **Header**: H1 site name, blockquote summary, prose intro with sector
2. **About**: About page link, AI Discovery endpoint link, contact
3. **Key Pages**: Top 15 published pages with optional first-sentence excerpts
4. **Recent Posts**: Recent blog posts (configurable limit, default 10)
5. **Policies**: Discovered policy pages + license + quoting/training permissions
6. **For AI Agents**: Tool endpoints (search, verify, status, knowledge, feed, content)
7. **Optional**: Overflow pages beyond first 15

### Signing

Both variants are signed with the plugin wallet:
```
---
Content signed by Rootz AI Discovery
Content-Hash: sha256:abc123...
Signer: 0xD089...
Signed-At: 2026-03-04T20:21:47+00:00
Signature: 0x45a63a80d7e83072...
Standard: AI Discovery v1.2.0 — rootz.global/ai-discovery
```

### Admin Settings (Content tab)

| Setting | Option Name | Default | Description |
|---------|-------------|---------|-------------|
| Enable llms.txt | `rootz_enable_llms_txt` | `1` | Generate /llms.txt |
| Enable llms-full.txt | `rootz_enable_llms_full` | `0` | Generate /llms-full.txt (opt-in) |
| Include excerpts | `rootz_llms_include_excerpts` | `1` | One-sentence excerpts after links |
| Posts limit (llms.txt) | `rootz_llms_posts_limit` | `10` | Recent posts to list |
| Posts limit (llms-full) | `rootz_llms_full_posts_limit` | `50` | Posts with full text |
| Pages limit | `rootz_llms_pages_limit` | `30` | Pages to include |

### Caching

Both files are cached as WordPress transients for 1 hour:
- `rootz_llms_txt_cache` — llms.txt content
- `rootz_llms_full_cache` — llms-full.txt content

Cleared by: saving Content settings, signing manifest, or calling
`rootz_ai_discovery_clear_all_caches()`.

## Two-Level Identity Architecture

### Owner Identity Contract (on-chain)
The **Owner Identity** is a smart contract on Polygon created by IdentityFactory_V6. It represents the SITE OWNER (person or organization). The owner identity:
- Manages authorized wallets ("rivets") — add/remove devices
- Supports multi-device access (laptop, phone, plugin, Desktop V6)
- Uses EIP-1271 for contract-based signature verification

### Plugin Wallet (local, in WordPress)
The **Plugin Wallet** is a secp256k1 keypair generated inside the WordPress plugin on activation. It represents the PLUGIN INSTANCE on a specific site:
- Stored AES-256-CBC encrypted in `wp_options`
- Signs `_signature` blocks in all JSON endpoints
- Requires PHP GMP extension for key generation/signing
- Address always displayable via `Rootz_Signer::stored_address()`

### How They Relate: Authorization
```
Owner Identity Contract (Polygon)
    ├── Rivet 1: Owner's MetaMask wallet (human, can add/remove rivets)
    ├── Rivet 2: Plugin Wallet (server, authorized by owner)
    ├── Rivet 3: Desktop V6 wallet (optional)
    └── ...
```

### Contract Addresses (Polygon Mainnet)
| Contract | Address | Purpose |
|----------|---------|---------|
| IdentityFactory_V6 | `0xc6361e4780eb16ee8643538376600D97F9E4C9c0` | Creates owner identity contracts |
| IdentityTemplate (V3) | `0x738110028FF2C48fC415D27b48Ad958BBB217e2e` | Template cloned per identity |
| Registry | `0xDD5fE36EafC3a92e2d065187a5c4320B5e4297Eb` | Identity registry |

### Key Wallets
| Wallet | Address | Purpose |
|--------|---------|---------|
| **Licensing SSA** | `0xD2FC0141165bc6019E8079c1c68E44d581136b59` | Mints identity contracts for subscribers (on rootz.global server at `/opt/rootz-ssa/`, port 3022) |
| Deployer (Trezor) | `0x86670C5C580BBCCf21EbA5eaaEbCc3087bb37A19` | Steven's hardware wallet, deploys contracts (~71 POL) |
| Lab Plugin Wallet | `0xD08914339B176C36C49D9827733599e1c4e5DAfF` | discover.rootz.global plugin instance |

### Licensing SSA (rootz.global server — 129.158.237.179)
The identity minting wallet runs as a **Rootz SSA** at `/opt/rootz-ssa/` on the rootz.global server.
Full V6 stack managed by pm2. See `/opt/rootz-ssa/ai.context.md` for API details.
- API: `localhost:3022` (same server as harness/rootz.global)
- Credits: auto-purchased by credit-monitor daemon
- Already minted: `0x952248299364b00BC0C09655e1343FA1f91C6C94`

## AI Proxy Integration

The plugin's auto-populate uses AI via the **Rootz AI Proxy** (a separate
epistery agent). The plugin wallet is the credential — no user API key needed.

- **Proxy**: `https://dev.epistery.host/agent/rootz/ai-proxy/v1/generate`
- **Source**: `ai-proxy/` directory (sibling to `rootz-wp-plugin/`)
- **Types sent**: `summary`, `concepts`, `identity`
- **Fallback**: Direct Anthropic API if user provides own key

**Coordination**: If you add a new generation type to `class-rootz-ai-generator.php`,
you must also add the matching prompt template in `ai-proxy/anthropic.mjs` and
add the type to the `validTypes` array in `ai-proxy/index.mjs`. Then deploy.

## How We Do Things

### Deployment to Lab Site

```bash
# SCP files to Oracle server (from Windows)
/c/Windows/System32/OpenSSH/scp.exe -i C:/Users/StevenSprague/.ssh/rootz_server \
    "path/to/file.php" \
    ubuntu@141.148.25.214:/var/www/discover.rootz.global/wp-content/plugins/rootz-ai-discovery/path/to/file.php

# Flush rewrite rules after adding new URL endpoints
/c/Windows/System32/OpenSSH/ssh.exe -i C:/Users/StevenSprague/.ssh/rootz_server \
    ubuntu@141.148.25.214 \
    "cd /var/www/discover.rootz.global && wp rewrite flush"
```

**IMPORTANT**: Always use Windows native SSH (`/c/Windows/System32/OpenSSH/ssh.exe`),
not Git Bash ssh which hangs.

### Building the Plugin Zip

```bash
# Use Python zipfile — NOT PowerShell (backslash paths break WP Playground)
python -c "
import zipfile, os
with zipfile.ZipFile('rootz-ai-discovery.zip', 'w', zipfile.ZIP_DEFLATED) as zf:
    for root, dirs, files in os.walk('rootz-ai-discovery'):
        for f in files:
            filepath = os.path.join(root, f)
            arcname = filepath  # preserves rootz-ai-discovery/ prefix
            zf.write(filepath, arcname)
"
```

### Adding a New REST API Tool

1. Register the route in `class-rootz-rest-api.php` → `register_routes()`
2. Add the handler method in the same class
3. Update `tool_count()` to match
4. Add tool entry in `get_tools()` → appropriate category
5. Add `_provenance` to the response using `$this->build_provenance()`
6. Sign the response using `$this->sign_response( $data )`
7. Test via curl: `curl -s "https://discover.rootz.global/wp-json/rootz/v1/your-tool"`
8. Deploy via SCP to lab site

### Adding a New Admin Setting

1. Register in `class-rootz-admin.php` → `register_settings()` (correct settings group!)
2. Add UI in the appropriate `admin/views/settings-*.php`
3. Add a `Rootz_Admin::help_tip()` — every setting needs a help button explaining
   what it does in plain language
4. If it affects caching, add `delete_transient()` in the cache clear function
5. Set default value in `rootz_ai_discovery_activate()` in the main plugin file

### Vendor Libraries (Crypto)

Located in `vendor/` — downloaded from GitHub, custom PSR-4 autoloader:
- `simplito/elliptic-php` — secp256k1 curve operations
- `simplito/bn-php` — big number arithmetic
- `simplito/bigint-wrapper-php` — BigInteger wrapper
- `kornrunner/keccak` — Keccak-256 hashing (Ethereum addresses)

**Rule**: Never update these via Composer. We vendor them manually for WP
compatibility. Check GitHub for security patches only.

### HTML-to-Markdown Conversion

`Rootz_Llms_Txt::html_to_markdown($html)` (public) wraps the private
`strip_to_markdown()` method. Used by both llms.txt generation and the
`getPage` tool. Handles: headings, links, bold/italic, images, blockquotes,
lists, paragraphs, br tags. Always apply `html_entity_decode()` after calling.

### Signing Pattern

All JSON responses are signed via `sign_response($data)`:
- If plugin wallet + GMP available → full ECDSA secp256k1 signature
- If not → hash-only attestation (content integrity without crypto proof)
- Signature covers the JSON-encoded data (without the `_signature` block itself)

## Setup Workflow (for AI Assistants Helping Site Owners)

### Step 1: Identity (Tab: Identity)
Fill in organization fields. Run auto-populate first, then review.

| Field | Where to Find It | Required |
|-------|-------------------|----------|
| Organization Name | Site title | Yes |
| Tagline / Mission | Site description | Yes |
| Legal Name | Contact/about page | Recommended |
| Sector | Industry description | Recommended |
| AI Summary | 2-3 sentences, third person | Recommended |
| Core Concepts | 5-15 terms with definitions | Recommended |
| Contact Email | Admin email | Yes |

### Step 2: Content (Tab: Content)
Configure content endpoint and llms.txt. Every setting has a help tip (i) button.

### Step 3: Policies (Tab: Policies)
Set AI content policies (license, quoting, training). Recommendation: allow
quoting (free visibility), deny training (retain control).

### Step 4: Tools (Tab: Tools & Preview)
Enable/disable optional endpoints (knowledge, feed, content).

### Step 5: Account (Tab: Account & Wallet)
Plugin wallet auto-generates on activation. Check GMP is available for signing.

### Step 6: Verify (Tab: What AI Sees)
Preview what AI agents see. Sign the manifest.

### Step 7: Check Score
Visit `/wp-json/rootz/v1/status` — scored across 8 categories, 100 points total.

## Scoring Breakdown

| Category | Max | What Improves It |
|----------|-----|------------------|
| Identity | 15 | Fill all org fields, add AI summary, core concepts |
| Policies | 15 | Set license, add policy pages (privacy, terms, data) |
| Signing | 15 | Generate wallet, get owner delegation |
| Content | 15 | Enable content endpoint, add pages/posts |
| Endpoints | 15 | Enable knowledge + feed + content endpoints |
| Contacts | 10 | Add all contact fields (email, URL, operator, AI, privacy) |
| Knowledge | 10 | Have an about page, add product/service pages |
| Metrics | 5 | Metrics auto-activate (just needs traffic) |

**Grades**: A (90+), B (80-89), C (70-79), D (60-69), F (<60)

## File Reference

```
rootz-ai-discovery/
├── rootz-ai-discovery.php           # Main plugin file, hooks, routing, rewrite rules
├── ai.context.md                    # This file (internal reference manual)
├── readme.txt                       # WordPress plugin readme
├── uninstall.php                    # Cleanup on uninstall
├── admin/
│   ├── class-rootz-admin.php        # Admin page, tabs, settings, auto-populate, handlers
│   ├── assets/
│   │   ├── admin.css                # Admin styles (help tips, info boxes, tabs)
│   │   └── admin-help.js            # Help tip toggle behavior
│   └── views/
│       ├── settings-viewer.php      # "What AI Sees" tab (manifest preview)
│       ├── settings-identity.php    # Identity tab (org fields, AI summary, concepts)
│       ├── settings-content.php     # Content tab (content endpoint + llms.txt settings)
│       ├── settings-policies.php    # Policies tab (license, quoting, training)
│       ├── settings-tools.php       # Tools & Preview tab (endpoint toggles)
│       ├── settings-analytics.php   # Analytics tab (AI access metrics)
│       └── settings-account.php     # Account & Wallet tab (wallet, proxy, tiers)
├── includes/
│   ├── class-rootz-ai-json.php      # /.well-known/ai generator (identity, pages, hashes)
│   ├── class-rootz-ai-generator.php # AI content generation (proxy + direct Anthropic)
│   ├── class-rootz-content-endpoint.php # Content endpoint (pages/posts/media/custom)
│   ├── class-rootz-license.php      # License checking + registration (rootz.global)
│   ├── class-rootz-llms-txt.php     # llms.txt + llms-full.txt generator (signed)
│   ├── class-rootz-metrics.php      # AI access metrics & agent classification
│   ├── class-rootz-rest-api.php     # REST routes: search, getPage, verify, status, tools, etc.
│   ├── class-rootz-signer.php       # secp256k1 signing & AES key management
│   └── class-rootz-updater.php      # Self-hosted update checker (rootz.global)
├── public/
│   └── webmcp-tools.js             # Browser WebMCP tool registration
└── vendor/                          # Crypto: elliptic-php, keccak, bn-php
```

## Service Tiers

| Tier | Price | Features |
|------|-------|----------|
| Free | $0 | 5 AI auto-configures, basic manifest, community support |
| Standard | $5/site/mo | Unlimited AI auto-configure + updates, monitoring, score alerts |
| Pro | $10/site/mo | Interactive AI, dynamic content, web services, custodial wallet identity |

**Volume**: $4/$8 at 10+ sites, $3/$6 at 25+ sites.

## TODO / Known Issues

### Open Bugs
- **BUG-003**: Scanner URL validation error message on rootz.global
- **BUG-004**: Add `upgrader_process_complete` hook to clear signed manifest cache on plugin update

### Planned Features
- **Owner delegation**: MetaMask authorization delegation to plugin wallet
- **Per-page assertion types**: Allow operators to set factual/editorial/creative per page
- **Rate limiting**: Enforce rate limits declared in policies
- **Category/date filtering on searchContent**: `?category=security&after=2025-01-01`
- **getPage caching**: Optional transient cache per-page for high-traffic sites
- **Plugin v2.0 phases 2-5**: See `docs/DESIGN-plugin-v2-tools-auth-metrics.md`

### Architecture Decisions (v2.3.3)
- getPage returns content in real time (no cache) — freshness metadata handles staleness
- Freshness is adaptive to content age, not a fixed TTL
- Origin/provenance blocks travel with the content, not just in HTTP headers
- `html_to_markdown()` is a public method on Rootz_Llms_Txt, reused by getPage
- Pagination uses offset (not cursor) for simplicity — WordPress WP_Query native support

## Recent Changes

### v2.3.3 (current — deployed to discover.rootz.global)
- **License class restored in WP.org distribution**: subscription/licensing
  features work in the WP.org build again (was excluded in v2.3.1).
- **Stripe checkout return**: redirects back to the user's WordPress site after
  payment; post-checkout auto-activation refreshes the license immediately.
- **Auto-register site wallet** when the owner identity is saved (no manual
  registration step).
- **UX**: "Owner Identity" label renamed to "License Key / Owner Identity".

### v2.3.2 (WordPress.org review fixes, part 2)
- **Security: `/status` and `/context` REST endpoints now require
  `manage_options`** (changed from public `__return_true`). These are admin
  management/setup endpoints — a 401 to unauthenticated/agent requests is
  expected. See the Access note in the REST tools table above.
- **Security**: nonce inputs sanitized with `sanitize_text_field(wp_unslash())`
  before `wp_verify_nonce()`; JSON-LD output no longer uses
  `JSON_UNESCAPED_SLASHES` (prevents script-context breakout).
- All view template variables prefixed with `rootz_` (PHPCS naming).
- Moved inline network-status script to `wp_add_inline_script()` +
  `wp_localize_script()`; removed Domain Path header.
- License infrastructure made optional / excluded from WP.org distribution.

### v2.3.1 (WordPress.org review fixes, part 1 — Mar 11, 2026)
- First pass on WP.org Plugin Directory review feedback: nonce sanitization,
  REST permission callbacks, JSON-LD escaping, license class isolation,
  ~200 view variables prefixed. PCP: 0 errors. See context archive
  `2026-03-11-wporg-review-fixes-v231-submission.md`.

### v2.3.0 (Mar 4, 2026)
- **`getPage` tool**: Read any published page/post as structured markdown with
  origin provenance, content hash, freshness metadata, policy permissions, and
  ECDSA signature. The "conversation mode" tool.
- **Freshness metadata (`_freshness`)**: Adaptive shelf life on content responses.
  Tells AI when to re-fetch. Recently edited = 1 hour, stable = 30 days.
- **Origin provenance (`_origin` + `_provenance`)**: Embedded in every dynamic
  response. Stamps domain, publishedAt, modifiedAt, servedAt, signer. Survives
  scraping and caching.
- **searchContent pagination**: `offset` parameter, `totalFound`, `hasMore`,
  `nextOffset` in response. Limit raised to 50 (was 20).
- **searchContent type filter**: `?type=post` or `?type=page`
- **llms.txt signed generation**: Spec-compliant llms.txt and llms-full.txt with
  ECDSA signature footer. Enabled by default (llms.txt) / opt-in (llms-full.txt).
- **llms.txt admin settings**: Full Content tab section with help tips on every
  setting. Enable/disable, excerpts toggle, configurable limits.
- **Help tips everywhere**: Every Content tab setting now has an (i) button
  explaining what it does in plain language for operators.
- **Tool count**: 8 → 9 (added getPage)

### v2.2.1 (Feb 27, 2026)
- WordPress Plugin Check compliance, output escaping, i18n, deferred scripts

### v2.1.0 (Feb 27, 2026)
- Spec v1.2 alignment, content endpoint with assertion types

### v2.0.5 (Feb 23, 2026)
- verify endpoint optimization, refactored status builder

### v2.0.0-2.0.4 (Feb 22-23, 2026)
- SEO meta tags, AI Readiness Score, content hashes, signed endpoints,
  self-hosted update checker, pricing tiers

### v1.8.0 (Feb 22, 2026)
- AI access metrics, searchContent tool, verifyPageHash tool, self-scoring
  status endpoint (8 categories, 100-point scale, A-F grades), tools manifest

## Links

- Standard: https://rootz.global/the-standard/
- Pricing: https://discover.rootz.global/pricing/
- Plugin download: https://discover.rootz.global/plugin/
- Lab site: https://discover.rootz.global
- GitHub: https://github.com/rootz-global
- Support: ai@rootz.global
- Design docs: `docs/DESIGN-plugin-v2-tools-auth-metrics.md`, `docs/SPEC-llms-txt-upgrade.md`
