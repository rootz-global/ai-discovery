# AI Context — Rootz AI Discovery Plugin

> This file is for AI assistants helping a WordPress site owner configure the
> Rootz AI Discovery plugin. Read this to understand the plugin, guide setup,
> and record what you did.

## What This Plugin Does

The Rootz AI Discovery plugin makes any WordPress site machine-readable for AI
agents. It creates a structured JSON endpoint at `/.well-known/ai` (RFC 8615)
that tells AI agents who you are, what your policies are, what content you have,
and what tools are available — all cryptographically signed.

**Without this plugin**: AI agents scrape raw HTML, guess who runs the site,
don't know if they can quote your content, and hallucinate missing information.

**With this plugin**: AI agents get clean structured data with verified identity,
explicit permissions, and content integrity hashes in a single API call.

## Plugin Version & Spec

- Plugin: Rootz AI Discovery v2.1.1
- Standard: AI Discovery Standard v1.2.0
- License: GPLv2+ (plugin), CC-BY-4.0 (standard specification)
- Requires: WordPress 6.0+, PHP 7.4+
- Optional: PHP GMP extension (for cryptographic signing)
- **Lab site**: discover.rootz.global (Oracle server 141.148.25.214)
- **Marketing site**: discover.rootz.global (WordPress, product pages + blog)
- **Scanner/Spec site**: rootz.global (Express, scanner API + standard)

## Two-Level Identity Architecture

The Rootz identity model has TWO distinct identities that work together:

### Owner Identity Contract (on-chain)
The **Owner Identity** is a smart contract on Polygon created by IdentityFactory_V6. It represents the SITE OWNER (person or organization). The owner identity:
- Manages authorized wallets ("rivets") — add/remove devices
- Supports multi-device access (laptop, phone, plugin, Desktop V6)
- Enables key rotation without losing ownership
- Uses EIP-1271 for contract-based signature verification

### Plugin Wallet (local, in WordPress)
The **Plugin Wallet** is a secp256k1 keypair generated inside the WordPress plugin on activation. It represents the PLUGIN INSTANCE on a specific site. The plugin wallet:
- Stored AES-256-CBC encrypted in `wp_options`
- Signs `_signature` blocks in all JSON endpoints
- Requires PHP GMP extension for key generation/signing
- Address always displayable via `Rootz_Signer::stored_address()`
- **Lab site wallet**: `0xD08914339B176C36C49D9827733599e1c4e5DAfF`

### How They Relate: Authorization
The Owner Identity Contract **authorizes** the Plugin Wallet by adding it as a "rivet" (authorized device). The plugin wallet can then act on behalf of the owner identity:

```
Owner Identity Contract (Polygon)
    ├── Rivet 1: Owner's MetaMask wallet (human, can add/remove rivets)
    ├── Rivet 2: Plugin Wallet (server, authorized by owner)
    ├── Rivet 3: Desktop V6 wallet (optional)
    └── ...
```

The owner can **revoke** the plugin wallet at any time (remove the rivet). The plugin wallet can never lock the owner out — the owner always retains control.

### Contract Addresses (Polygon Mainnet)
| Contract | Address | Purpose |
|----------|---------|---------|
| IdentityFactory_V6 | `0xc6361e4780eb16ee8643538376600D97F9E4C9c0` | Creates owner identity contracts |
| IdentityTemplate (V3) | `0x738110028FF2C48fC415D27b48Ad958BBB217e2e` | Template cloned per identity |
| Registry | `0xDD5fE36EafC3a92e2d065187a5c4320B5e4297Eb` | Identity registry |

### Key Wallets
| Wallet | Address | Purpose |
|--------|---------|---------|
| Deployer/Funder | `0x86670C5C580BBCCf21EbA5eaaEbCc3087bb37A19` | Deploys contracts, funds gas (~71 POL) |
| Primary Operator | `0x3f07D9DE...` | Funded by deployer, calls IdentityFactory |
| Lab Plugin Wallet | `0xD08914339B176C36C49D9827733599e1c4e5DAfF` | discover.rootz.global plugin instance |

### Current Licensing Flow (Phase 1 — SQLite Only)
The Stripe webhook flow currently stores licensing data in SQLite on rootz.global. **No on-chain identity contracts are created by this flow yet.**

1. User subscribes via Stripe checkout on rootz.global
2. `deriveIdentityAddress()` hashes Stripe customer ID into a deterministic Ethereum address (NOT a real contract — just a placeholder address)
3. Identity record saved to rootz.global SQLite with tier, max sites, expiration
4. Plugin wallet calls `POST /api/license/register` with domain + wallet address
5. rootz.global SQLite records the site registration under the derived identity

**Code comment at `license-routes.mjs:33`**: *"When we deploy real Identity Contracts, this gets replaced with the contract address."*

### Planned Phase 2 — Identity at Install
The intended architecture creates a real on-chain Owner Identity Contract when the plugin is installed:

1. Plugin activates → generates plugin wallet → calls `POST /api/identity/create` on rootz.global
2. rootz.global server calls `IdentityFactory_V6.createIdentity()` on Polygon (Rootz pays gas)
3. Rootz server wallet is initial controller (custodial — holds the key)
4. Plugin wallet is added as a rivet on the identity contract
5. When user pays via Stripe, their personal wallet gets added as a controller
6. User can remove Rootz server key at any time (self-custody / "fire" Rootz)

This means identity exists BEFORE payment — the plugin has a real on-chain identity from day one. Payment upgrades the tier, not the identity.

### Infrastructure Already Built (in rootz-v6)
The on-chain identity infrastructure is fully implemented in `rootz-v6/packages/identity-provider/`:
- **IdentityManager** (`identity-manager.ts`): `createIdentity()`, `addRivet()`, `removeRivet()`, `getRivets()`
- **KeyVault** (on-chain storage): `setKeyVault(key, value, isPublic)`, `getKeyVault()`, `hasKeyVault()`, `deleteKeyVault()`
- **Multi-device encryption** (`multi-device-encryption.ts`): ECDH key sharing between rivets
- **Device invite flow** (`device-invite-manager.ts`): 1-click authorization for new devices
- **Design doc**: `docs/DESIGN-wallet-native-licensing.md` — full architecture for using KeyVault to store license data (tier, maxSites, expires) on-chain

The license data model maps to KeyVault:
- Key `"license"` → JSON with tier, maxSites, stripeCustomerId, expiresAt
- Key `"sites"` → JSON array of registered plugin wallets with domains
- `addRivet(pluginWallet)` → register a new site

**What's NOT connected**: The rootz.global Express server (`license-routes.mjs`) doesn't call any of this yet — it only does SQLite with derived addresses. The bridge between Stripe webhook → IdentityFactory → KeyVault → addRivet is designed but not wired up.

### Existing On-Chain Identity Contracts
The ~16 identity contracts on IdentityFactory_V6 were created during **Desktop V6 testing** (by `0x3f07D9DE...` funded by the deployer). These are NOT from the plugin licensing flow.

### Monitoring
- **IdentityFactory transactions**: https://polygonscan.com/address/0xc6361e4780eb16ee8643538376600D97F9E4C9c0
- **Deployer POL balance**: https://polygonscan.com/address/0x86670C5C580BBCCf21EbA5eaaEbCc3087bb37A19
- **Gas costs**: Identity creation ~2.5M gas (~0.5-1 POL at current prices)

## AI Proxy Integration

The plugin's auto-populate uses AI via the **Rootz AI Proxy** (a separate
epistery agent). The plugin wallet is the credential — no user API key needed.

- **Proxy**: `https://dev.epistery.host/agent/rootz/ai-proxy/v1/generate`
- **Source**: `ai-proxy/` directory (sibling to `rootz-wp-plugin/`)
- **Context file**: `ai-proxy/ai.context.md`
- **Types sent by plugin**: `summary`, `concepts`, `identity`
- **Fallback**: Direct Anthropic API if user provides own key

**Coordination**: If you add a new generation type to `class-rootz-ai-generator.php`,
you must also add the matching prompt template in `ai-proxy/anthropic.mjs` and
add the type to the `validTypes` array in `ai-proxy/index.mjs`. Then deploy.

## Setup Workflow

Follow these steps in order. Each step corresponds to a plugin tab.

### Step 1: Identity (Tab: Identity)

Fill in the organization's identity. These fields appear in `/.well-known/ai`:

| Field | Where to Find It | Required |
|-------|-------------------|----------|
| Organization Name | Site title (auto-populated) | Yes |
| Tagline / Mission | Site description (auto-populated) | Yes |
| Legal Name | Contact page, footer, legal docs | Recommended |
| Sector | The organization's industry | Recommended |
| Founded | Year the organization was established | Optional |
| Headquarters | City/Country | Optional |
| Contact Email | Admin email (auto-populated) | Yes |
| Contact URL | Link to contact page | Recommended |
| Operator | Name of the person managing the site | Recommended |
| AI Support Email | Email for AI-related inquiries | Optional |
| Privacy Email | Email for privacy/GDPR inquiries | Optional |

**AI Summary**: Write 2-3 sentences explaining what this organization does and
what information is available on this site. Write in third person, be factual.
This is the first thing AI agents read.

**Core Concepts**: List 5-15 domain-specific terms with definitions, one per
line, in the format `term: definition`. These help AI agents understand your
vocabulary.

**Digital Identity** (advanced):
- Digital Name: An Ethereum-compatible wallet address (0x...)
- Blockchain: The network (e.g., "polygon")
- Identity Contract: On-chain identity contract address

### Step 2: Content (Tab: Content)

Configure which content types AI agents can access via `/.well-known/ai/content`:

- **Pages**: Include published pages (recommended)
- **Posts**: Include published blog posts (recommended)
- **Custom post types**: Include WooCommerce products, portfolios, etc.
- **Media**: Include images with EXIF metadata
- **Full text**: Serve complete post content (vs. excerpts only)
- **Post limit**: How many posts to include (default: 50)

The content endpoint is **disabled by default**. Enable it only if you want AI
agents to access your full content programmatically.

### Step 3: Policies (Tab: Policies)

Set your AI content policies. These are machine-readable — AI agents will
respect them:

| Setting | What It Means |
|---------|---------------|
| Content License | Default license for your content (CC-BY, CC0, All Rights Reserved) |
| Allow Quoting | AI agents may quote and summarize your content |
| Allow Training | AI agents may use your content for model training |

**Recommendation**: Most sites should allow quoting (it's free visibility) but
deny training (retain control over your content).

### Step 4: Tools & Endpoints (Tab: Tools & Preview)

Enable or disable optional endpoints:

| Endpoint | URL | Purpose |
|----------|-----|---------|
| Discovery | `/.well-known/ai` | Always active. Main manifest. |
| Knowledge | `/.well-known/ai/knowledge` | Encyclopedia about the organization |
| Feed | `/.well-known/ai/feed` | AI-optimized blog feed |
| Content | `/.well-known/ai/content` | Full content access (pages/posts/media) |

**REST API Tools** (always available when plugin is active):
- `GET /wp-json/rootz/v1/search?q={query}` — Search site content
- `GET /wp-json/rootz/v1/verify?page=/path/` — Verify page content integrity
- `GET /wp-json/rootz/v1/status` — Site readiness score (0-100)
- `GET /wp-json/rootz/v1/tools` — List all available tools
- `GET /wp-json/rootz/v1/ai.json` — AI manifest (REST mirror)
- `GET /wp-json/rootz/v1/policies` — Machine-readable policies

### Step 5: Account & Signing (Tab: Account & Wallet)

**Plugin Wallet**: The plugin generates a secp256k1 keypair for cryptographic
signing. The private key is stored AES-256-CBC encrypted in the WordPress
database. Requires PHP GMP extension.

- If GMP is available: Plugin auto-generates a key on activation
- If GMP is missing: Plugin works in read-only mode (no signing)
- The signing address appears in `_signature` blocks in every JSON response

**Owner Wallet**: Optionally enter the site owner's wallet address (from
MetaMask or similar). Future feature: the owner wallet can delegate authority
to the plugin wallet via a signed message.

**Auto-Populate**: Click this button to automatically fill identity fields from
your existing WordPress content:
- Organization Name ← site title
- Tagline ← site description
- AI Summary ← AI-generated from about page (or first 80 words fallback)
- Core Concepts ← AI-generated from categories and content
- Contact Email ← admin email
- Contact URL ← contact page permalink
- Legal Name ← extracted from about/contact page content
- Sector ← extracted from about/contact page content
- Founded ← extracted from about/contact page content
- Headquarters ← extracted from about/contact page content

Identity extraction works best when the site has a **contact page** or **about
page** with structured company information (in an HTML table or labeled text like
"Legal Name: Acme Corp").

### Step 6: Verify (Tab: What AI Sees)

The "What AI Sees" tab shows a rendered preview of your `/.well-known/ai`
endpoint. Check that:

1. Organization name and mission are correct
2. Contact information is complete
3. Policies match your intent
4. Content hashes are present (one per published page)
5. Signature block shows a valid signer address

### Step 7: Check Your Score

Visit: `{your-site}/wp-json/rootz/v1/status`

The status endpoint scores your site across 8 categories (100 points total):

| Category | Max | What Improves It |
|----------|-----|------------------|
| Identity | 15 | Fill all org fields, add AI summary, core concepts |
| Policies | 15 | Set license, add policy pages (privacy, terms, data) |
| Signing | 15 | Generate wallet, get owner delegation (future) |
| Content | 15 | Enable content endpoint, add pages/posts |
| Endpoints | 15 | Enable knowledge + feed + content endpoints |
| Contacts | 10 | Add all contact fields (email, URL, operator, AI, privacy) |
| Knowledge | 10 | Have an about page, add product/service pages |
| Metrics | 5 | Metrics auto-activate (just needs traffic) |

**Grades**: A (90+), B (80-89), C (70-79), D (60-69), F (<60)

## Auto-Populate Tips

The auto-populate feature extracts identity from your site's existing content.
For best results, ensure your **about page** or **contact page** includes:

- The organization's legal/registered name
- Industry or sector
- Year founded
- Location/headquarters
- Contact information

These can appear as:
- **HTML table rows**: `<tr><td>Legal Name</td><td>Acme Corp</td></tr>`
- **Labeled text**: `Legal Name: Acme Corp`
- **Definition lists**: `<dt>Founded</dt><dd>2020</dd>`

If AI generation is available (via Rootz proxy or direct API key), the plugin
uses Claude to intelligently extract these fields from natural language content.
Otherwise, it falls back to pattern matching.

## For AI Assistants: How to Help

When helping a site owner configure this plugin:

1. **Read their site first**: Visit `/.well-known/ai` to see current state
2. **Check the score**: Visit `/wp-json/rootz/v1/status` for specific gaps
3. **Look at their content**: Read the about page and contact page for identity info
4. **Suggest improvements**: Use the score breakdown to prioritize
5. **Don't over-fill**: Only populate fields you can verify from the site's own content
6. **Verify after changes**: Re-check `/.well-known/ai` and the score endpoint

### Common Tasks

**"Set up AI Discovery from scratch"**
→ Run auto-populate first, then review and correct each field manually.

**"Improve my score"**
→ Check `/wp-json/rootz/v1/status` and address the lowest-scoring categories.

**"Verify my content integrity"**
→ Use `/wp-json/rootz/v1/verify?page=/about/` to check any page's hash.

**"What do AI agents see?"**
→ Fetch `/.well-known/ai` and explain each section to the user.

**"Add a new tool for AI agents"**
→ Tools are registered in `class-rootz-rest-api.php` → `get_tools()`. Each tool
  needs a REST route, a handler method, and an entry in the tools manifest.

## Setup Log

When you finish helping configure the plugin, record what was done. Use this
format and store it in the WordPress options table as `rootz_setup_log`:

```
Setup completed by: [AI assistant name]
Date: [YYYY-MM-DD]
Plugin version: [version]
Score before: [score]
Score after: [score]

Actions taken:
- [action 1]
- [action 2]
- ...

Fields populated:
- [field]: [value] (source: [where you found it])

Remaining recommendations:
- [recommendation 1]
- [recommendation 2]
```

To save the log via WP-CLI:
```bash
wp option update rootz_setup_log "$(cat setup-log.txt)"
```

Or programmatically:
```php
update_option( 'rootz_setup_log', $log_text );
```

## File Reference

```
rootz-ai-discovery/
├── rootz-ai-discovery.php           # Main plugin file, hooks, routing
├── ai.context.md                    # This file (AI assistant instructions)
├── readme.txt                       # WordPress plugin readme
├── uninstall.php                    # Cleanup on uninstall
├── admin/
│   ├── class-rootz-admin.php        # Admin page, tabs, auto-populate
│   ├── assets/admin.css             # Admin styles
│   └── views/
│       ├── settings-viewer.php      # "What AI Sees" tab
│       ├── settings-identity.php    # Identity tab
│       ├── settings-content.php     # Content tab
│       ├── settings-policies.php    # Policies tab
│       ├── settings-tools.php       # Tools & Preview tab
│       ├── settings-analytics.php   # Analytics tab
│       └── settings-account.php     # Account & Wallet tab
├── includes/
│   ├── class-rootz-ai-json.php      # /.well-known/ai generator
│   ├── class-rootz-ai-generator.php # AI content generation (proxy + direct)
│   ├── class-rootz-content-endpoint.php # Content endpoint (pages/posts/media)
│   ├── class-rootz-llms-txt.php     # llms.txt generator
│   ├── class-rootz-metrics.php      # AI access metrics & analytics
│   ├── class-rootz-rest-api.php     # REST routes, knowledge, feed, tools
│   ├── class-rootz-signer.php       # secp256k1 signing & key management
│   └── class-rootz-updater.php      # Self-hosted update checker (rootz.global)
├── public/
│   └── webmcp-tools.js             # Browser WebMCP tool registration
└── vendor/                          # Crypto libraries (elliptic-php, keccak, bn)
```

## Service Tiers (as of v2.1.1)

| Tier | Price | Features |
|------|-------|----------|
| Free | $0 | 5 AI auto-configures, basic manifest, community support |
| Standard | $5/site/mo | Unlimited AI auto-configure + updates, monitoring, score alerts |
| Pro | $10/site/mo | Interactive AI, dynamic content, web services, custodial wallet identity |

**Volume**: $4/$8 at 10+ sites, $3/$6 at 25+ sites.

Account & Signing tab shows tiers with "View Plans" linking to `https://discover.rootz.global/pricing/`.

## Recent Changes (v2.1.1 — Feb 27, 2026)
- **WordPress Plugin Check compliance**: Passes all WP.org automated checks
- **Output escaping**: All admin views use proper `esc_html()`, `esc_attr()`, `wp_kses()`
- **Internationalization**: All user-facing strings wrapped in `__()` / `esc_html__()`
- **Deferred scripts**: Admin JS uses `defer` for better page load
- **Two-zip build**: rootz.global zip WITH updater (74 files), WP.org zip WITHOUT (73 files)

## Recent Changes (v2.1.0 — Feb 27, 2026)
- **Spec v1.2 alignment**: Updated all references from v1.1 to v1.2
- **Content endpoint**: Full content access with assertion types

## Recent Changes (v2.0.5 — Feb 23, 2026)
- **verify endpoint optimization**: No longer busts ai.json cache on every call — uses cached manifest
- **Refactored status builder**: Extracted `build_status_data()` shared by `/status` and `/context` endpoints

## Recent Changes (v2.0.4 — Feb 23, 2026)
- **Self-hosted update checker**: Updates now appear in Dashboard > Updates automatically
- **Update manifest**: `rootz.global/api/plugin/update.json` with content hash signature
- **Pricing tiers updated**: Free / Standard $5/mo / Pro $10/mo (was Free/Starter/Agency)
- **View Plans link fixed**: Points to discover.rootz.global/pricing/

## Recent Changes (v2.0.0-2.0.2 — Feb 22, 2026)
- **SEO meta tags**: Open Graph + Twitter Cards on all JSON endpoints
- **AI Readiness Score preview**: Local score panel in "What AI Sees" tab
- **Scanner v2.0 compatibility**: Updated to match 5-tier 120-point scoring
- **Content hashes**: SHA-256 per page in ai.json pages[] array
- **Signed endpoints**: All 5 JSON endpoints signed (not just ai.json)

## Links

- Standard: https://rootz.global/the-standard/
- Pricing: https://discover.rootz.global/pricing/
- Plugin download: https://discover.rootz.global/plugin/
- Lab site: https://discover.rootz.global
- GitHub: https://github.com/rootz-global
- Support: ai@rootz.global
