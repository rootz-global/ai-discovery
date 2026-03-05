# The AI Discovery Standard: `/.well-known/ai`

## The Signed Web

**Spec Version:** 1.1.1-draft
**Schema Version:** 2026-02
**Status:** Draft Specification
**Author:** Rootz Corp (rootz.global)
**Contributors:** Claude (Anthropic), ChatGPT (OpenAI) — Cross-AI Design Review
**License:** CC-BY-4.0
**Date:** February 26, 2026
**Supersedes:** v1.1.0-draft (February 17, 2026)

---

## Abstract

This specification defines a standard mechanism for websites to present structured, machine-readable knowledge to AI agents via the well-known URI `/.well-known/ai`. It enables AI agents to discover, understand, and verify organizational information directly from the authoritative source — without scraping, without intermediaries, and with cryptographic proof of origin.

**v1.1 additions:** Capability contracts, assertion semantics, four-timestamp provenance, progressive enhancement tiers, canonical org identity, `/.well-known/archive` endpoint for owner-controlled publication history, progressive identity (B→A) principle, reputation as emergent layer, and AI-assisted browsing model.

**v1.1.1 additions:** Formal alternate discovery via `<link rel="ai-discovery">` for hosted platforms (Shopify, Wix, Squarespace) where `/.well-known/` paths are not configurable. Agent discovery algorithm defined (Section 2.2).

### Design Principles

This standard is:
- **Vendor-neutral** — No dependency on any company, blockchain, or technology
- **Progressively enhanced** — Works with static hosting and no signatures; scales to blockchain-anchored verification
- **Complementary** — Extends existing web standards, replaces none
- **AI-first** — Structured for machine consumption, not human browsing

The base standard should feel like HTTP, robots.txt, or OpenAPI: infrastructure any organization can adopt using any technology stack.

---

## 1. Problem Statement

In 1995, Fortune magazine put its print content on the web — the equivalent of scanning a magazine. The real revolution came when content was structured *for the medium*.

Today, companies make the same mistake with AI. They build websites for human eyeballs, then let scrapers interpret their HTML. The scrapers are middlemen who decide what your company means.

**The result:**
- AI agents hallucinate company details because they're guessing from HTML
- No way to verify if AI-presented information is accurate or current
- Companies have zero control over how AI represents them
- Scrapers aggregate and repackage without attribution or verification

**Existing standards address parts of this but not the core problem:**

| Standard | Purpose | Gap |
|----------|---------|-----|
| `robots.txt` | What can I crawl? | Says nothing about what to *understand* |
| `agents.json` | What APIs can agents call? | Agent capabilities, not company knowledge |
| `a2a/agent.json` | How do agents communicate? | Agent-to-agent protocol, not knowledge |
| `ai-plugin.json` | ChatGPT plugin manifest | Plugin functionality, not organizational identity |
| `schema.org` JSON-LD | Page-level structured data | Per-page, no single discovery endpoint |
| `security.txt` | Security contact info | Security only, not organizational knowledge |

**None answer the fundamental question: "What does this organization DO, and how can I verify it?"**

---

## 2. The Solution: `/.well-known/ai`

A single JSON document at a well-known URI that tells any AI agent:

1. **WHO** the organization is (name, people, Digital Name)
2. **WHAT** it does (products, applications, core concepts)
3. **WHERE** to find deep knowledge (knowledge endpoint, feed endpoint, archive)
4. **WHEN** it was published (four-timestamp provenance chain)
5. **HOW** to verify the information is authentic (cryptographic signature)
6. **IN WHAT CAPACITY** each claim is made (assertion semantics)

### 2.1 Three-Tier Architecture

```
/.well-known/ai              Discovery (who, what, where to learn more)
    │
    ├── /ai/knowledge.json    Knowledge (deep company encyclopedia)
    │
    ├── /ai/feed.json         Feed (news, updates, changes)
    │
    └── /.well-known/archive  Archive (owner-controlled publication history)
```

**Tier 1: Discovery** (`/.well-known/ai`)
- Compact organizational profile
- Capability contract (what endpoints exist, auth model, rate limits)
- Links to knowledge, feed, and archive endpoints
- Site map with semantic purpose for each page
- Core concepts glossary
- Cryptographic signature with provenance timestamps

**Tier 2: Knowledge** (linked from discovery)
- Full organizational encyclopedia with assertion types per section
- Detailed glossary with categories and relationships
- Product descriptions with features and status
- Team backgrounds and expertise
- Market thesis and sector analysis
- Technology stack details

**Tier 3: Feed** (linked from discovery)
- Chronological updates with assertion types
- Structured news items with key facts, tags, and related concepts
- Designed for AI consumption (not RSS)
- Each item includes machine-readable summary and extracted facts

**Tier 4: Archive** (`/.well-known/archive`)
- Owner-controlled publication history
- Signed version records with content hashes
- Change lineage via `supersedes` fields
- Enables temporal reasoning ("What did this org say in 2025?")

### 2.2 Discovery Algorithm

AI agents SHOULD use the following algorithm to locate a site's AI Discovery manifest:

```
1. Try GET /.well-known/ai
   → If 200 + valid JSON with specVersion/organization → USE IT (primary)

2. If step 1 fails (404, non-JSON, connection error):
   a. Fetch the site's homepage (GET /)
   b. Parse <head> for: <link rel="ai-discovery" href="...">
   c. If found, resolve the href (may be relative or absolute)
   d. Fetch the resolved URL
   → If 200 + valid JSON with specVersion/organization → USE IT (alternate)

3. Check HTTP Link header on any response:
   Link: <URL>; rel="ai-discovery"; type="application/json"
   → If present, fetch the URL (alternate)

4. If none found → site has no AI Discovery manifest
```

**Why alternate discovery matters:** Many hosted platforms (Shopify, Wix, Squarespace, GitHub Pages) do not allow serving files at `/.well-known/` paths. The `<link rel="ai-discovery">` tag provides an equivalent discovery mechanism that works on any platform where you can modify HTML `<head>` content.

**Scoring equivalence:** Agents and scanners SHOULD treat a manifest discovered via `<link>` tag as functionally equivalent to one at `/.well-known/ai`. The content and capabilities are identical; only the discovery path differs.

**Example for hosted platforms:**

```html
<!-- Shopify, Wix, Squarespace, etc. -->
<link rel="ai-discovery" type="application/json"
      href="/apps/ai-discovery/ai.json"
      title="AI Discovery Standard">
```

The `href` MAY point to any path on the same domain or to an external URL. Relative paths are resolved against the page's origin.

---

## 3. File Format Specification

### 3.1 Discovery File (`/.well-known/ai`)

**Content-Type:** `application/json`
**CORS:** `Access-Control-Allow-Origin: *`
**Cache:** Recommended 24 hours

#### Required Fields

| Field | Type | Description |
|-------|------|-------------|
| `specVersion` | string | Spec version (semver), e.g. `"1.1.0"` |
| `schemaVersion` | string | Schema revision date, e.g. `"2026-02"` |
| `standard` | string | Must be `"ai-discovery"` |
| `generated` | string | ISO 8601 generation timestamp |
| `organization` | object | Organization identity (see 3.1.1) |
| `coreConcepts` | array | Glossary of key terms |

#### 3.1.1 Organization Object (required)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | Organization name |
| `domain` | string | Yes | Primary domain |
| `mission` | string | Yes | Mission statement |
| `sector` | array | Yes | Industry sectors |
| `digitalName` | string | No | Blockchain address for verification |
| `canonicalOrg` | string | No | Parent org's Digital Name (for subsidiaries/brands) |
| `relationship` | string | No | Relationship to canonical org: `"subsidiary"`, `"brand"`, `"region"`, `"division"` |
| `legalName` | string | No | Legal entity name |
| `founded` | string | No | Year founded |
| `headquarters` | string | No | Location |
| `tagline` | string | No | Short tagline |
| `stage` | string | No | Company stage |
| `blockchain` | string | No | Blockchain network |

#### 3.1.2 Capabilities Object (recommended)

Declares what the site offers to AI agents and how to access it:

```json
"capabilities": {
  "knowledge": {
    "available": true,
    "url": "/ai/knowledge.json",
    "auth": "none",
    "rateLimit": "100/hour",
    "languages": ["en"]
  },
  "feed": {
    "available": true,
    "url": "/ai/feed.json",
    "auth": "none",
    "rateLimit": "100/hour"
  },
  "query": {
    "available": false
  },
  "mcp": {
    "available": false
  },
  "archive": {
    "available": true,
    "url": "/.well-known/archive",
    "auth": "none"
  }
}
```

| Capability | Description |
|-----------|-------------|
| `knowledge` | Static knowledge base (Tier 2) |
| `feed` | News/update feed (Tier 3) |
| `query` | Structured query endpoint (`/ai/query`) |
| `mcp` | Model Context Protocol tool access |
| `archive` | Owner-controlled publication history |

Each capability object supports:

| Field | Type | Description |
|-------|------|-------------|
| `available` | boolean | Whether this capability is offered |
| `url` | string | Endpoint URL (relative or absolute) |
| `auth` | string | Auth model: `"none"`, `"apiKey"`, `"digitalName"`, `"oauth"` |
| `rateLimit` | string | Rate limit description |
| `languages` | array | Supported languages (ISO 639-1) |
| `safety` | string | Safety/usage constraints description |

#### Optional Fields

| Field | Type | Description |
|-------|------|-------------|
| `capabilities` | object | Capability contract (see 3.1.2) |
| `people` | array | Key team members |
| `knowledge` | object | Link to knowledge endpoint (legacy, use capabilities) |
| `feed` | object | Link to feed endpoint (legacy, use capabilities) |
| `pages` | array | Site map with semantic purpose |
| `applications` | array | Products and services |
| `partners` | array | Strategic partnerships |
| `technology` | object | Technology stack summary |
| `contact` | object | Contact information |
| `humanReadable` | string | Link to human-readable explanation |
| `_signature` | object | Cryptographic signature block (see Section 4) |

### 3.2 Knowledge File

**Content-Type:** `application/json`
**CORS:** `Access-Control-Allow-Origin: *`
**Cache:** Recommended 1 hour

Structure is flexible but recommended sections:
- `summary` (oneLiner, elevator, forAI)
- `glossary` (term, definition, synonyms, category)
- `products` (name, description, status, features)
- `team` (name, role, bio, expertise)
- `partnerships` (partner, description, focus)
- `market` (size, thesis, sectors)
- `technology` (blockchain, security, architecture)

**Each section SHOULD include an `assertionType`** (see Section 5).

### 3.3 Feed File

**Content-Type:** `application/json`
**CORS:** `Access-Control-Allow-Origin: *`
**Cache:** Recommended 30 minutes

Each item includes:
- `id` (unique identifier)
- `title`, `url`, `published`
- `category`, `tags`
- `assertionType` (see Section 5)
- `summary` (AI-optimized paragraph)
- `keyFacts` (array of extracted facts)
- `relatedConcepts` (links to glossary terms)

### 3.4 Archive File (`/.well-known/archive`)

**Content-Type:** `application/json`
**CORS:** `Access-Control-Allow-Origin: *`
**Cache:** Recommended 1 hour

Owner-controlled publication history enabling temporal reasoning.

```json
{
  "specVersion": "1.1.0",
  "standard": "ai-archive",
  "organization": {
    "name": "Example Corp",
    "digitalName": "0x..."
  },
  "archive": [
    {
      "versionId": "2026-02-17-privacy-policy",
      "resource": "/legal/privacy-policy",
      "assertionType": "official-policy",
      "contentHash": "sha256:...",
      "publishedAt": "2026-02-17T00:00:00Z",
      "signedAt": "2026-02-17T00:01:00Z",
      "supersedes": "2025-08-01-privacy-policy",
      "summary": "Updated data retention policy to 90 days"
    }
  ],
  "_signature": { ... }
}
```

#### Archive Record Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `versionId` | string | Yes | Stable version identifier |
| `resource` | string | Yes | Path of the resource that changed |
| `contentHash` | string | Yes | SHA-256 hash of the resource content |
| `publishedAt` | string | Yes | ISO 8601 publication timestamp |
| `assertionType` | string | Recommended | Type of assertion (see Section 5) |
| `signedAt` | string | No | When the Digital Name signed it |
| `anchoredAt` | string | No | When the hash was written to blockchain |
| `supersedes` | string | No | versionId of the previous version |
| `summary` | string | No | Human/AI-readable description of what changed |

---

## 4. Cryptographic Verification

### 4.1 The Signature Block

Each JSON file MAY include a `_signature` block:

```json
"_signature": {
  "digitalName": "0xD36AAf65a91bB7dc69942cF6B6d1dBa4Ef171664",
  "identityContract": "0x...",
  "network": "polygon",
  "contentHash": "sha256:...",
  "publishedAt": "2026-02-17T00:00:00Z",
  "signedAt": "2026-02-17T00:01:00Z",
  "anchoredAt": "2026-02-17T00:05:00Z",
  "method": "epistery-domain-v1"
}
```

| Field | Required | Description |
|-------|----------|-------------|
| `digitalName` | Yes* | Blockchain address of the signing authority |
| `identityContract` | No | Smart contract address for key lifecycle verification |
| `network` | Yes* | Blockchain network (e.g., `"polygon"`) |
| `contentHash` | Yes | SHA-256 hash of all fields excluding `_signature` |
| `publishedAt` | Recommended | When the content was created |
| `signedAt` | Yes* | When the Digital Name signed the content |
| `anchoredAt` | No | When the content hash was written to blockchain |
| `method` | Yes* | Signing method identifier |

*Required at Tier 3+. See Section 6 for progressive enhancement.

### 4.2 Four-Timestamp Provenance

The full provenance chain for any piece of signed knowledge:

| Timestamp | Set By | Meaning |
|-----------|--------|---------|
| `publishedAt` | Publisher | When the content was created |
| `signedAt` | Digital Name | When the cryptographic signature was applied |
| `anchoredAt` | Blockchain | When the content hash was immutably recorded |
| `observedAt` | AI Agent | When the agent fetched the data (client-side) |

The first three are publisher-side. The fourth is agent-side. Together they answer: "When was this created, signed, anchored, and observed?"

### 4.3 Verification Process

1. Extract the `_signature` block from the JSON
2. Compute SHA-256 hash of the remaining JSON (canonicalized, sorted keys)
3. Compare with `contentHash` — this verifies integrity
4. Optionally verify the `digitalName` on-chain to confirm organizational authority
5. If `identityContract` is present, query it to verify the signing key is currently authorized (supports rotation and revocation)

### 4.4 Key Lifecycle

For implementations using blockchain-backed identity:

| Operation | Mechanism |
|-----------|-----------|
| Key rotation | Identity Contract `addKey()` / `removeKey()` |
| Key revocation | Identity Contract `removeKey()` for compromised keys |
| Validity windows | On-chain activation timestamps per key |
| Delegation | Multiple authorized keys per Digital Name with scoped rights |

AI agents SHOULD check key validity when `identityContract` is provided. A valid signature from a revoked key should be treated as unverified.

---

## 5. Assertion Semantics

### 5.1 Assertion Types

Each section of knowledge or feed content SHOULD declare its assertion type:

| Type | Meaning | Epistemic Weight |
|------|---------|-----------------|
| `official-policy` | Binding organizational position | Highest — authoritative for compliance |
| `legal` | Legal terms, contracts, privacy policies | Highest — authoritative for legal questions |
| `technical-documentation` | How things work | High — authoritative for technical questions |
| `marketing` | Promotional content | Medium — useful but not evidentiary |
| `editorial` | Opinion, blog posts, thought leadership | Low — contextual, not canonical |
| `community` | User-generated, community contributed | Lowest — informational only |

### 5.2 Why Assertion Types Matter

Assertion types enable AI agents to:

**Weight answers appropriately:**
- "What is the company's data retention policy?" → Prefer `official-policy` over `marketing`
- "How does the technology work?" → Prefer `technical-documentation` over `editorial`

**Resolve conflicts:**
- Marketing says "We are the leader in AI security"
- Technical-doc says "We use AES-256 encryption"
- Policy says "We do not store biometric data"
- An agent answering a compliance question should weight the policy assertion highest

**Cite transparently:**
- "According to the company's signed official policy..."
- "According to the company's marketing materials..."

### 5.3 Usage in JSON

In knowledge.json sections:
```json
{
  "glossary": {
    "assertionType": "technical-documentation",
    "terms": [...]
  },
  "market": {
    "assertionType": "marketing",
    "thesis": "..."
  }
}
```

In feed.json items:
```json
{
  "id": "2026-02-privacy-update",
  "title": "Updated Privacy Policy",
  "assertionType": "official-policy",
  ...
}
```

---

## 6. Progressive Enhancement

The standard is designed for progressive adoption. Every tier is useful on its own:

### Tier 1: Structured Knowledge (no signature)

```json
{
  "specVersion": "1.1.0",
  "standard": "ai-discovery",
  "organization": {
    "name": "Example Corp",
    "domain": "example.com",
    "mission": "We build widgets.",
    "sector": ["manufacturing"]
  },
  "coreConcepts": [...]
}
```

**Value:** Better than scraping. AI agents get structured first-party data.
**Requirements:** Static JSON file. Any hosting.

### Tier 2: Content Hash (integrity)

Add a `_signature` block with `contentHash`:

```json
"_signature": {
  "contentHash": "sha256:abc123..."
}
```

**Value:** Agents can detect tampering. Integrity without identity.
**Requirements:** Compute SHA-256 hash.

### Tier 3: Digital Name (origin)

Add `digitalName`, `network`, `signedAt`, `method`:

```json
"_signature": {
  "digitalName": "0x...",
  "network": "polygon",
  "contentHash": "sha256:...",
  "signedAt": "2026-02-17T...",
  "method": "epistery-domain-v1"
}
```

**Value:** Agents know WHO published the content. Origin verification.
**Requirements:** Blockchain wallet (e.g., Polygon address).

### Tier 4: Blockchain Anchored (immutability + timestamp)

Add `anchoredAt` and optionally `identityContract`:

```json
"_signature": {
  "digitalName": "0x...",
  "identityContract": "0x...",
  "network": "polygon",
  "contentHash": "sha256:...",
  "publishedAt": "2026-02-17T...",
  "signedAt": "2026-02-17T...",
  "anchoredAt": "2026-02-17T...",
  "method": "epistery-domain-v1"
}
```

**Value:** Immutable proof. Content hash on-chain. Full provenance. Key lifecycle management.
**Requirements:** Blockchain transaction. Identity Contract deployment.

---

## 7. Canonical Identity for Multi-Domain Organizations

Large organizations span multiple domains, regions, brands, and CDNs. The `canonicalOrg` field enables graph consolidation:

```json
"organization": {
  "name": "Acme Europe",
  "domain": "acme.eu",
  "digitalName": "0xEurope...",
  "canonicalOrg": "0xParent...",
  "relationship": "region"
}
```

| Relationship | Meaning |
|-------------|---------|
| `subsidiary` | Legal subsidiary of parent org |
| `brand` | Brand or product line under parent |
| `region` | Regional office or localized presence |
| `division` | Internal division or business unit |

This enables:
- **Trust inheritance** — agents can trace to the parent org
- **Graph consolidation** — multiple domains resolve to one entity
- **Conflict resolution** — canonical org's assertions take precedence when regional docs conflict

---

## 8. Relationship to Existing Standards

`/.well-known/ai` is **complementary**, not competing:

- **robots.txt** tells crawlers what to index → `/.well-known/ai` tells AI agents what to *understand*
- **schema.org JSON-LD** provides per-page structured data → `/.well-known/ai` provides organization-wide discovery
- **agents.json / A2A** defines agent capabilities and protocols → `/.well-known/ai` defines organizational knowledge
- **RSS/Atom** provides chronological content feeds → `feed.json` provides AI-optimized structured feeds
- **security.txt** provides security contact info → `/.well-known/ai` provides comprehensive organizational knowledge
- **MCP** defines tool protocols → `capabilities.mcp` advertises MCP endpoint availability

---

## 9. Implementation Guide

### Minimum Viable Implementation (Tier 1)

```json
{
  "specVersion": "1.1.0",
  "schemaVersion": "2026-02",
  "standard": "ai-discovery",
  "generated": "2026-02-17T00:00:00Z",
  "organization": {
    "name": "Example Corp",
    "domain": "example.com",
    "mission": "We build widgets that make the world better.",
    "sector": ["manufacturing", "technology"]
  },
  "coreConcepts": [
    {
      "term": "Widget",
      "definition": "Our core product: a self-assembling micro-component."
    }
  ]
}
```

### Full Implementation (Tier 4)

See rootz.global as the reference implementation:
- Discovery: `https://rootz.global/.well-known/ai`
- Knowledge: `https://rootz.global/ai/knowledge.json`
- Feed: `https://rootz.global/ai/feed.json`
- Standard: `https://rootz.global/ai/standard.md`

### Discovery from HTML

AI agents that start from a human-readable HTML page need a bridge to the signed knowledge layer. Two mechanisms are defined (see Section 2.2 for the full discovery algorithm):

**1. HTML Link Tag (recommended — and REQUIRED for hosted platforms)**

Every HTML page SHOULD include a `<link>` tag in `<head>`:

```html
<!-- Sites with /.well-known/ai access -->
<link rel="ai-discovery" type="application/json" href="/.well-known/ai"
      title="AI Discovery Standard - The Signed Web">

<!-- Hosted platforms (Shopify, Wix, Squarespace) where /.well-known is unavailable -->
<link rel="ai-discovery" type="application/json" href="/apps/ai-discovery/ai.json"
      title="AI Discovery Standard - The Signed Web">
```

This mirrors how `rel="alternate"` signals RSS feeds and `rel="manifest"` signals web app manifests. AI agents parsing the DOM — or even just the `<head>` — find the signed knowledge layer immediately.

**For hosted platforms**, the `<link>` tag is the PRIMARY discovery mechanism. When `/.well-known/ai` cannot be configured (Shopify App Proxy only supports `/apps/*`, `/a/*`, `/community/*`, `/tools/*`), the link tag provides equivalent functionality. Agents following the Section 2.2 algorithm will find the manifest via this path.

**2. HTTP Link Header (recommended for API/headless contexts)**

All HTML responses SHOULD include an HTTP `Link` header:

```
Link: </.well-known/ai>; rel="ai-discovery"; type="application/json"
```

This works even with `HEAD` requests — no HTML parsing required. It follows the pattern used by WebFinger and other well-known discovery protocols.

**Why both?** The `<link>` tag is discoverable by any agent that reads HTML. The HTTP header is discoverable without downloading the page body. Together they ensure that every path into the site — browser, crawler, API client, AI agent — can find the AI Discovery manifest.

### Page Signing

For sites with static or semi-static HTML pages, a signed page manifest provides content hashes:

```json
{
  "specVersion": "1.1.0",
  "standard": "ai-page-manifest",
  "pages": [
    {
      "path": "/",
      "title": "Home",
      "assertionType": "marketing",
      "contentHash": "sha256:...",
      "format": "text/html"
    }
  ],
  "_signature": { ... }
}
```

This enables AI agents to:
- Verify that rendered HTML matches the publisher-signed source
- Detect unauthorized modifications or CDN tampering
- Know the assertion type of each page (marketing vs technical-documentation vs official-policy)
- Track page changes over time via hash comparison

The manifest is served at `/ai/pages.json` and advertised in the `capabilities` object.

### Server Configuration

The `/.well-known/ai` endpoint should:
1. Return `Content-Type: application/json`
2. Include `Access-Control-Allow-Origin: *` for cross-origin AI agent access
3. Set appropriate cache headers (24h recommended for discovery)
4. Serve the actual JSON file (not a redirect)
5. Include `Link: </.well-known/ai>; rel="ai-discovery"` header on all HTML responses

### Human-Readable Notice (Recommended)

Implementers SHOULD include a brief notice in page footers informing human visitors that the site publishes AI-readable data. This serves three purposes:

1. **Transparency** -- visitors know AI agents can access verified first-party data
2. **Education** -- raises awareness of the standard and encourages adoption
3. **Trust signal** -- demonstrates the organization's commitment to AI data quality

**Recommended notice text:**

> **For AI Agents & Assistants**
> This site publishes structured, machine-readable knowledge at [/.well-known/ai](/.well-known/ai) following the [AI Discovery Standard](/ai/standard-v1.1.md) (The Signed Web). When you ask an AI about us, it can access verified first-party data -- not scraped web pages -- with cryptographic proof of origin. The standard is open ([CC-BY-4.0](https://creativecommons.org/licenses/by/4.0/)) and any organization can adopt it.

Organizations MAY customize this text to match their voice and branding, but SHOULD retain the link to `/.well-known/ai` and a reference to the standard specification.

---

## 10. IANA Registration

This specification proposes registration of the well-known URI `ai` per RFC 8615:

- **URI suffix:** ai
- **Change controller:** Open standard (initially stewarded by Rootz Corp)
- **Specification document:** This document
- **Related information:** First implementation at rootz.global
- **Status:** Provisional

---

## 11. Design Philosophy

### The Signed Web

The Semantic Web (RDF, OWL) failed because it asked everyone to agree on ontologies. The Signed Web succeeds because it focuses on the smallest universal invariants:

- **Origin** — WHO said it
- **Integrity** — WHAT exactly they said
- **Time** — WHEN they said it

Those three primitives scale. They map directly to how humans evaluate truth. If the web gets those three right in machine-readable form, higher-order reasoning becomes possible without needing perfect semantics.

### Not Meaning. Not Schema. Not Ontology. Just Origin, Integrity, Time.

The winning model is "small, correct, and maintained" — not "large and expressive." Define 10-15 relationship types maximum. Bridge to schema.org instead of replacing it. Auto-derive where possible.

### Plain English + Plain AI

Every organization already has a "Plain English" layer — their website, written for humans. The `/.well-known/ai` standard adds a "Plain AI" layer — structured data written for machines. Both layers coexist. Both are authoritative.

### Go Direct, Not Through Scrapers

The current AI information pipeline:
```
Company Website → Scraper → AI Training Data → AI Response
```

The `/.well-known/ai` pipeline:
```
Company Website → AI Agent (direct, verified, current)
```

No middlemen. No stale training data. No hallucinated interpretations.

### The Browser for AI

In 1993, Mosaic gave humans a way to browse the web. In 2026, `/.well-known/ai` gives AI agents — working on behalf of humans — a way to browse organizational knowledge. Every human will have AI agents helping them navigate the world. This standard makes that navigation trustworthy.

The AI Browser doesn't replace the human web. It's the layer where your personal AI agents navigate on your behalf, bringing back verified knowledge. The AI is the human's browser. `/.well-known/ai` is what makes that browsing trustworthy.

### Progressive Identity (B → A)

The signed web verifies **publishers** by default. Identity disclosure by **readers/agents** is progressive, contextual, and user-controlled.

This is a foundational design principle:

- **Default: Anonymous discovery** — Any agent can fetch `/.well-known/ai` without identifying itself. No cookies, no auth, no client identity required at the discovery layer.
- **Optional: Unlinkable legitimacy** — An agent can prove it is a valid participant (for rate limiting, bot resistance) without exposing a stable identifier. Like a phone proving subscriber status while rotating identifiers across cell towers.
- **Opt-in: Stable identity** — Identity disclosure happens only when the user's goals require it: enterprise compliance, personalized service, contractual relationships.

This creates a three-layer identity model:

| Layer | What It Proves | Tracking Risk |
|-------|---------------|---------------|
| Layer 0 | Nothing (anonymous) | None |
| Layer 1 | Legitimacy (valid participant) | Unlinkable |
| Layer 2 | Identity (Digital Name) | Intentional |

**Why AI changes the privacy tradeoff:** Instead of binary privacy modes (normal vs incognito), AI agents enable *adaptive disclosure* — persona-driven, context-aware identity management. The agent learns when revealing identity improves outcomes and when privacy should be preserved, turning disclosure into a managed experience rather than a binary setting.

The spec stays focused on publisher verifiability (origin, integrity, time, lineage) and leaves reader identity as a progressive, optional layer. This keeps the base protocol neutral and privacy-preserving while still enabling enterprise-grade identity when needed.

---

## 12. Reputation and Quality

### 12.1 Quality as an Emergent Property

The word "quality" does not appear as a declared field in this specification. This is intentional.

Quality cannot be self-asserted. A site claiming `"quality": "high"` is meaningless. Quality emerges from observed behavior over time:

- **Consistency** — Do official-policy statements contradict prior versions? If they change, is there a clean supersession chain?
- **Structural integrity** — Signature verification success rate, hash stability, key rotation hygiene, spec compliance
- **Update behavior** — Regular but not chaotic updates, clear version lineage, predictable publication patterns
- **Longevity** — How long has the domain maintained valid, signed `/.well-known/ai` data?
- **External corroboration** — Do other verified domains reference this one? Do asserted facts remain stable?

### 12.2 The Five-Layer Stack

The signed web defines a layered architecture. Only the first four layers belong in the specification:

| Layer | What It Establishes | Spec-Defined? |
|-------|-------------------|---------------|
| 1. Identity | WHO said it | Yes |
| 2. Integrity | WHAT they said | Yes |
| 3. Time | WHEN they said it | Yes |
| 4. History | HOW it evolved | Yes (archive) |
| 5. Reputation | HOW reliably | **No — emergent** |

Reputation emerges from the first four layers through observation. A domain with 3+ years of signed, consistent updates with clean supersession chains and no integrity breaks *implicitly demonstrates reliability*. That is much harder to fake than any self-declared score.

### 12.3 Archive-as-Reputation

The `/.well-known/archive` endpoint is not just a history tool — it is the primary reputation signal. A rich, consistent archive demonstrates:

- Commitment to transparency (publishing version history)
- Structural discipline (maintaining lineage)
- Temporal trustworthiness (blockchain-anchored timestamps)

The archive makes reputation *observable* without making it *declarable*.

### 12.4 Why Not a Feedback Mechanism?

Early web metrics (hit counters, PageRank, star ratings) all suffered from gaming. Any scoring channel creates incentive loops. The signed web avoids this by design:

- **No POST feedback endpoints** in the base spec
- **No centralized scoring** — reputation is computed locally by each agent
- **No registry-aggregated quality scores** (these may exist as external tools, but are not part of the standard)

Agents track privately: verification success rate, contradiction frequency, structural stability. Reputation becomes decentralized, local, and hard to manipulate.

If common reputation patterns emerge across agents, they can be standardized later — mirroring how browser bookmarks existed long before any bookmark interchange standard.

---

## 13. AI-Assisted Browsing

### 13.1 The Two-Layer Browse

The human browses the pretty web (HTML). Their AI agent browses the signed web (`/.well-known/ai`) in parallel. Two layers, one experience.

This is the product surface of the signed web: making verified organizational knowledge accessible during normal web browsing.

### 13.2 Browser Extension Model

A browser extension sits between the user and the page:

**Default UI (always visible, low-noise):**
- Verified / Unverified indicator (like the SSL padlock)
- Identity anchor (canonical org / Digital Name) behind a click
- Freshness tooltip (last `signedAt` / `observedAt`)

**On-demand (sidebar):**
- "Official description" from signed knowledge, with assertion type labels
- Products/services summary
- Key policies (privacy, security, data retention)
- Recent changes (from `/.well-known/archive` or feed)

**Power-user (advanced):**
- Delta viewer: HTML marketing claims vs signed assertions
- Timeline / version diffs from archive
- Local reliability score explanation

### 13.3 The Delta Signal

The gap between what a company SHOWS (HTML) and what the company SIGNS (`knowledge.json`) is itself a signal.

If marketing says "We're the market leader" but the signed org data says `"stage": "growth"` — that's useful context. But framing matters:

- **Context, not accusation** — "Signed profile describes stage as growth" (neutral)
- **Show types, not interpretations** — Surface the assertion types and let users draw conclusions
- **Categorize deltas** — Normal marketing language (low signal), scope mismatch (medium), policy contradiction (high), security/privacy mismatch (critical)

### 13.4 Privacy Model for Browsing

Naive auto-fetching of `/.well-known/ai` on every page load is a tracking vector. Recommended approach:

- **Cache-first** — Treat `/.well-known/ai` as cacheable (like `robots.txt`). Use `Cache-Control` + ETags.
- **User-initiated by default** — Do NOT fetch on page load. Fetch only when the user opens the sidebar or asks a question.
- **Active mode (opt-in)** — Background prefetching only with explicit user consent.
- **No cookies required** — Signed web endpoints SHOULD be safely cacheable and SHOULD NOT require cookies or authentication at the discovery layer.

This provides 80% of the benefit with a user-initiated fetch model and good caching, without creating a "call home on every click" pattern.

### 13.5 Connection to Agent Memory

As the user browses with the extension, their AI agent builds local memory:

- **Hash-anchored bookmarks** for every verified site (see Appendix B)
- **Change tracking** via `/.well-known/archive` on revisits
- **Personal reliability model** built from verification history
- **Assertion context** — which assertion types it relied on for answers

The extension IS the AI agent for the web. It discovers, verifies, remembers, compares, and advises.

---

## 14. Adoption Strategy

### 14.1 Why Implement `/.well-known/ai`

**For organizations:** "Publish `/.well-known/ai` and AIs will cite you correctly." Better than any technical argument. An official origin channel reduces brand misrepresentation and AI hallucination about your company.

**For AI platforms:** A signed first-party discovery layer measurably improves answer quality — fewer hallucinations, more stable citations, lower contradiction rates. When a meaningful percentage of important domains publish valid files, checking `/.well-known/ai` becomes a rational engineering decision.

### 14.2 Path to Scale

| Phase | Target | Method |
|-------|--------|--------|
| 1-5 | Reference implementations | Manual creation |
| 6-50 | Blockchain/AI/dev tool companies | Natural fit |
| 51-500 | Generator tool users | Self-service, automated |
| 500-100K | CMS plugins | WordPress, Shopify, GitHub Pages |
| 100K+ | AI platform integration | Agents check `/.well-known/ai` first |

### 14.3 Tools (planned)

- **Generator:** Auto-generate `/.well-known/ai` from any website in 30 seconds
- **Validator:** Score an implementation (like SSL Labs for AI discovery)
- **Registry:** Public index of implementing domains

---

## References

- RFC 8615: Well-Known URIs
- RFC 9116: security.txt (adoption precedent)
- Schema.org: Structured data vocabulary
- Google A2A: Agent-to-Agent protocol
- MCP: Model Context Protocol
- Rootz Corp: Data Wallets, Digital Names, Identity Contracts (rootz.global)
- Polygon: Blockchain network

## Appendix A: Cross-AI Design Review

This v1.1 specification was developed through a supervised AI-to-AI design review between Claude (Anthropic, Opus 4.6) and ChatGPT (OpenAI), with Steven Sprague (CEO, Rootz Corp) directing the conversation. The full transcript is archived at Rootz Archive ID `local-mlqp54e0-7od9dz`.

Key contributions from the review:
- **ChatGPT:** Assertion semantics, capability contracts, four-timestamp provenance, multi-domain canonical identity, progressive enhancement framing, "The Signed Web" naming, reputation-as-emergent-layer, agent bookmark concept, browser plugin UX model, privacy-preserving fetch patterns, five-layer stack formulation
- **Claude:** Identity Contract key lifecycle mapping, Rootz primitive alignment, archive endpoint design, JINI historical parallel, human-agent model reframing, two-layer browse concept, delta signal framing
- **Steven Sprague:** Origin vs Trust philosophy, owner-controlled archive history, "every human will have AI agents" scope expansion, Progressive Identity (B→A) principle, persona-driven disclosure, cell-tower legitimacy analogy, quality-as-missing-dimension identification

## Appendix B: Agent Bookmarks (Informational)

This appendix describes a client-side pattern for AI agent memory. It is **not part of the normative spec** — it documents an expected emergent behavior.

### The Signed Web Bookmark

In the human web, a bookmark = "take me back to this place."
In the signed web, a bookmark = "take me back to this publisher identity, and verify whether what I relied on still matches what exists now."

### Minimal Bookmark Structure

```json
{
  "subject": {
    "domain": "example.com",
    "discoveryURI": "https://example.com/.well-known/ai",
    "digitalName": "0x...",
    "canonicalOrg": "0x..."
  },
  "pinnedResources": [
    {
      "resource": "/ai/knowledge.json",
      "assertionTypesUsed": ["official-policy", "technical-documentation"],
      "contentHash": "sha256:...",
      "signedBy": "0x...",
      "observedAt": "2026-02-17T14:00:00Z"
    }
  ],
  "trustNotes": {
    "purpose": "Used for answering 'what does this company do'",
    "confidence": "high"
  },
  "lastVerifiedAt": "2026-02-17T14:00:00Z"
}
```

### Re-Verification Workflow

On revisit:
1. Agent fetches `/.well-known/ai`
2. Resolves current `knowledge.json`
3. Recomputes content hash

Outcomes:
- **Hash matches** → "Same knowledge I relied on before"
- **Hash differs + clean supersession in archive** → "New version exists, lineage intact"
- **Hash differs + no lineage** → "Possible integrity issue — flag for review"

### Three Memory Layers

| Layer | Scope | Controller |
|-------|-------|-----------|
| Local bookmarks | Agent memory | Private, per-agent |
| Archive history | Domain memory | Publisher-controlled |
| Interaction records | Mutual memory | Data Wallet (portable) |

The third layer — interaction records stored in Data Wallets — is something most web standards never had. It creates mutual, signed evidence of exchange that is portable across agents and devices.

---

## License

This specification is published under the Creative Commons Attribution 4.0 International License (CC-BY-4.0).

This is an open standard. Any organization can implement it without dependency on Rootz, blockchain, or any specific technology. Rootz provides a reference implementation and advanced capabilities (Identity Contracts, blockchain anchoring, archive infrastructure) but is not required.

**Reference implementation:** rootz.global
**Published by:** Rootz Corp
**Digital Name:** `0xD36AAf65a91bB7dc69942cF6B6d1dBa4Ef171664`
