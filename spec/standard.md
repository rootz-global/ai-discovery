# The AI Discovery Standard: `/.well-known/ai`

## The Browser for AI

**Version:** 1.0.0-draft
**Status:** Draft Specification
**Author:** Rootz Corp (rootz.global)
**License:** CC-BY-4.0
**Date:** February 15, 2026

---

## Abstract

This specification defines a standard mechanism for websites to present structured, machine-readable knowledge to AI agents via the well-known URI `/.well-known/ai`. It enables AI agents to discover, understand, and verify organizational information directly from the authoritative source — without scraping, without intermediaries, and with cryptographic proof of origin.

## 1. Problem Statement

In 1995, Fortune magazine put its print content on the web. That was the equivalent of scanning a magazine. The real web revolution came when content was structured *for the medium* — hyperlinks, dynamic content, interactivity.

Today, companies are making the same mistake with AI. They build websites for human eyeballs, then let scrapers (Perplexity, ChatGPT Browse, Google AI Overviews) interpret their HTML. The scrapers are middlemen who decide what your company means.

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

**None answer the fundamental question: "What does this organization DO, and how can I verify it?"**

## 2. The Solution: `/.well-known/ai`

A single JSON document at a well-known URI that tells any AI agent:

1. **Who** the organization is (name, people, Digital Name)
2. **What** it does (products, applications, core concepts)
3. **Where** to find deep knowledge (knowledge endpoint, feed endpoint)
4. **How** to verify the information is authentic (cryptographic signature)

### 2.1 Three-Tier Architecture

```
/.well-known/ai          Discovery (who, what, where to learn more)
    │
    ├── /ai/knowledge.json    Knowledge (deep company encyclopedia)
    │
    └── /ai/feed.json         Feed (news, updates, changes)
```

**Tier 1: Discovery** (`/.well-known/ai`)
- Compact organizational profile
- Links to knowledge and feed endpoints
- Site map with semantic purpose for each page
- Core concepts glossary
- Cryptographic signature

**Tier 2: Knowledge** (linked from discovery)
- Full organizational encyclopedia
- Detailed glossary with categories and relationships
- Product descriptions with features and status
- Team backgrounds and expertise
- Market thesis and sector analysis
- Technology stack details

**Tier 3: Feed** (linked from discovery)
- Chronological updates
- Structured news items with key facts, tags, and related concepts
- Designed for AI consumption (not RSS)
- Each item includes machine-readable summary and extracted facts

## 3. File Format Specification

### 3.1 Discovery File (`/.well-known/ai`)

**Content-Type:** `application/json`
**CORS:** `Access-Control-Allow-Origin: *`
**Cache:** Recommended 24 hours

#### Required Fields

| Field | Type | Description |
|-------|------|-------------|
| `$schema` | string | Schema URL for validation |
| `version` | string | Spec version (semver) |
| `standard` | string | Must be `"rootz-ai-discovery"` |
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
| `legalName` | string | No | Legal entity name |
| `founded` | string | No | Year founded |
| `headquarters` | string | No | Location |
| `tagline` | string | No | Short tagline |
| `stage` | string | No | Company stage |
| `blockchain` | string | No | Blockchain network |

#### Optional Fields

| Field | Type | Description |
|-------|------|-------------|
| `people` | array | Key team members |
| `knowledge` | object | Link to knowledge endpoint |
| `feed` | object | Link to feed endpoint |
| `pages` | array | Site map with semantic purpose |
| `applications` | array | Products and services |
| `partners` | array | Strategic partnerships |
| `technology` | object | Technology stack summary |
| `contact` | object | Contact information |
| `humanReadable` | string | Link to human-readable explanation |
| `_signature` | object | Cryptographic signature block |

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

### 3.3 Feed File

**Content-Type:** `application/json`
**CORS:** `Access-Control-Allow-Origin: *`
**Cache:** Recommended 30 minutes

Each item includes:
- `id` (unique identifier)
- `title`, `url`, `published`
- `category`, `tags`
- `summary` (AI-optimized paragraph)
- `keyFacts` (array of extracted facts)
- `relatedConcepts` (links to glossary terms)

## 4. Cryptographic Verification

### 4.1 The Signature Block

Each JSON file MAY include a `_signature` block:

```json
"_signature": {
  "digitalName": "0x...",
  "network": "polygon",
  "contentHash": "sha256:...",
  "signedAt": "2026-02-15T00:00:00Z",
  "method": "epistery-domain-v1"
}
```

| Field | Description |
|-------|-------------|
| `digitalName` | Blockchain address of the signing authority |
| `network` | Blockchain network (e.g., "polygon") |
| `contentHash` | SHA-256 hash of all fields excluding `_signature` |
| `signedAt` | ISO 8601 timestamp of signing |
| `method` | Signing method identifier |

### 4.2 Verification Process

1. Extract the `_signature` block from the JSON
2. Compute SHA-256 hash of the remaining JSON (canonicalized, sorted keys)
3. Compare with `contentHash` — this verifies integrity
4. Optionally verify the `digitalName` on-chain to confirm organizational authority

### 4.3 Why This Matters

Every other discovery standard serves unsigned data. An attacker who compromises a web server can serve false information to AI agents. With signed AI discovery data:

- **Integrity**: Content hash detects any tampering
- **Attribution**: Digital Name traces to on-chain organizational identity
- **Non-repudiation**: Blockchain timestamp proves when the data was published
- **Verifiable origin**: AI agents can confirm WHO published the data, not just WHERE it was served from

## 5. Relationship to Existing Standards

`/.well-known/ai` is **complementary**, not competing:

- **robots.txt** tells crawlers what to index → `ai.json` tells AI agents what to *understand*
- **schema.org JSON-LD** provides per-page structured data → `ai.json` provides organization-wide discovery
- **agents.json / A2A** defines agent capabilities and protocols → `ai.json` defines organizational knowledge
- **RSS/Atom** provides chronological content feeds → `feed.json` provides AI-optimized structured feeds

## 6. Implementation Guide

### Minimum Viable Implementation

```json
{
  "$schema": "https://rootz.global/ai/schemas/ai-discovery-v1.json",
  "version": "1.0.0",
  "standard": "rootz-ai-discovery",
  "generated": "2026-02-15T00:00:00Z",
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

### Full Implementation

See rootz.global as the reference implementation:
- Discovery: `https://rootz.global/.well-known/ai`
- Knowledge: `https://rootz.global/ai/knowledge.json`
- Feed: `https://rootz.global/ai/feed.json`

### Server Configuration

The `/.well-known/ai` endpoint should:
1. Return `Content-Type: application/json`
2. Include `Access-Control-Allow-Origin: *` for cross-origin AI agent access
3. Set appropriate cache headers (24h recommended for discovery)
4. Serve the actual JSON file (not a redirect)

## 7. IANA Registration

This specification proposes registration of the well-known URI `ai` per RFC 8615:

- **URI suffix:** ai
- **Change controller:** Rootz Corp
- **Specification document:** This document
- **Related information:** First implementation at rootz.global

## 8. Design Philosophy

### Plain English + Plain AI

Every organization already has a "Plain English" layer — their website, written for humans. The `/.well-known/ai` standard adds a "Plain AI" layer — structured data written for machines. Both layers coexist. Both are authoritative. Together they form the **Twin Heart** of the AI-readable web.

### Go Direct, Not Through Scrapers

The current AI information pipeline:
```
Company Website → Scraper → AI Training Data → AI Response
```

The `/.well-known/ai` pipeline:
```
Company Website → AI Agent (direct, verified, current)
```

No middlemen. No stale training data. No hallucinated interpretations. The AI agent reads the authoritative source directly, verifies the signature, and responds with verified knowledge.

### The Browser for AI

In 1993, Mosaic gave humans a way to browse the web. In 2026, `/.well-known/ai` gives AI agents a way to browse organizational knowledge. Every AI agent is already a browser — this standard gives them a URL bar.

---

## References

- RFC 8615: Well-Known URIs
- Schema.org: Structured data vocabulary
- Google A2A: Agent-to-Agent protocol
- Rootz Corp: Data Wallets and Digital Names (rootz.global)
- Polygon: Blockchain network for signature verification

## License

This specification is published under the Creative Commons Attribution 4.0 International License (CC-BY-4.0).

**First implementation:** rootz.global
**Published by:** Rootz Corp — Digital Name `0xD36AAf65a91bB7dc69942cF6B6d1dBa4Ef171664`
