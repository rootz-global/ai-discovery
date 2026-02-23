# AI Discovery Standard: `/.well-known/ai`

**Origin, not Trust.** — Structured, signed, verifiable organizational knowledge for AI agents.

## The Problem

Today, AI agents learn about organizations by scraping HTML and guessing. Scrapers are middlemen who decide what your company means. The result:

- AI hallucinate company details because they're interpreting unstructured HTML
- No way to verify if AI-presented information is accurate or current
- Organizations have zero control over how AI represents them
- No proof of who published the data or when

**`robots.txt` tells crawlers what to index. Nothing tells AI agents what to understand — or how to verify it.**

## The Solution

A single well-known URI (`/.well-known/ai`) where organizations publish structured, machine-readable knowledge — signed with cryptographic proof of origin.

```json
{
  "organization": {
    "name": "Example Corp",
    "digitalName": "0xABC...123",
    "domain": "example.com",
    "mission": "We build things that matter."
  },
  "_signature": {
    "digitalName": "0xABC...123",
    "contentHash": "sha256:a1b2c3...",
    "signedAt": "2026-02-23T00:00:00Z",
    "method": "ecdsa-secp256k1"
  }
}
```

Every field is structured for machines. Every document is signed at the source. AI agents don't have to trust the data — they can **verify** it.

## Signed Manifests: Origin and Ownership

This is more than discovery. Every `/.well-known/ai` document includes:

**Digital Name** — A blockchain-anchored organizational identity (e.g., `0xD36A...1664` on Polygon). This is the organization's address on-chain, not just a domain name that can be transferred or spoofed.

**Content Hashes** — SHA-256 hash of every JSON document and every page in the site manifest. If a single byte changes, the hash breaks. AI agents can verify content integrity in one API call.

**Cryptographic Signature** — The document is signed by the organization's wallet. The signature block proves *who* published the data and *when*, with a verifiable chain to the on-chain identity.

**Per-Page Integrity** — The `pages[]` array includes content hashes for individual pages. An AI agent can verify that a specific page hasn't been tampered with since the manifest was signed.

```
Organization publishes /.well-known/ai
  -> Content is hashed (SHA-256)
  -> Hash is signed by organization's wallet
  -> Digital Name links to on-chain identity
  -> AI agent verifies: origin + integrity + freshness
```

**Why this matters:** Every other discovery standard serves unsigned data. An attacker who compromises a web server can feed false information to AI agents. With signed AI Discovery, the data carries proof of its own authenticity — origin and ownership are native to the format.

## Three-Tier Architecture

```
/.well-known/ai              Discovery — identity, concepts, signature
    |
    +-- /ai/knowledge.json   Knowledge — deep organizational encyclopedia
    |
    +-- /ai/feed.json        Feed — AI-optimized news and updates
```

**Tier 1: Discovery** — Compact organizational profile with Digital Name, core concepts, page manifest with content hashes, contact info, tools manifest, and cryptographic signature.

**Tier 2: Knowledge** — Full encyclopedia: glossary with categories and relationships, products with features and status, team backgrounds, market thesis, technology stack.

**Tier 3: Feed** — Chronological updates with structured facts, tags, and related concepts. Designed for AI consumption, not RSS.

All three tiers support signing. All content hashes are verifiable. The architecture is designed so that AI agents can establish a chain of trust from the well-known endpoint to any individual page on the site.

## What Makes This Different

| Standard | Purpose | Signing | Verification |
|----------|---------|---------|-------------|
| `robots.txt` | Crawl permissions | None | None |
| `llms.txt` | Text for LLMs | None | None |
| `schema.org` JSON-LD | Per-page structured data | None | None |
| `agents.json` / A2A | Agent capabilities | None | None |
| **`/.well-known/ai`** | **Organizational knowledge** | **SHA-256 + wallet** | **On-chain Digital Name** |

The signing layer is not optional decoration — it's the core differentiator. Discovery without verification is just another data source to hallucinate from.

## Tools Manifest

The `/.well-known/ai` document can declare tools that AI agents can invoke:

```json
"tools": {
  "version": "1.1.0",
  "categories": {
    "discovery": [...],
    "actions": [...],
    "meta": [...]
  }
}
```

Tools include `verifyPageHash` (prove content integrity in one API call), `searchContent` (structured content search), `getStatus` (self-scoring readiness assessment), and more. Tools are categorized and permissioned — anonymous, authenticated, or authorized.

## Live Implementations

| Site | Score | Signed | Description |
|------|-------|--------|-------------|
| [rootz.global](https://rootz.global/.well-known/ai) | 110/120 Grade A | Yes | Reference implementation |
| [m.miusa.one](https://m.miusa.one/.well-known/ai) | 110/120 Grade A | Yes | Government services (Made in USA) |
| [discover.rootz.global](https://discover.rootz.global/.well-known/ai) | 73/120 Grade C | Yes | WordPress plugin (auto-generated) |
| [dev.epistery.host](https://dev.epistery.host/.well-known/ai) | Active | Yes | Developer platform |

Test any site: **https://discover.rootz.global/scanner/**

## WordPress Plugin

The `wordpress-plugin/` directory contains a WordPress plugin that automatically generates `/.well-known/ai` from existing WordPress content. No manual JSON editing required.

- **Plugin Wallet** — Generates a secp256k1 keypair stored AES-256-CBC encrypted. Signs all endpoints automatically.
- **Per-Page Content Hashes** — SHA-256 hash of every page included in the manifest
- **7 Admin Tabs** — Identity, Content, Policies, Tools, Analytics, What AI Sees, Account & Signing
- **AI Access Metrics** — Tracks which AI agents visit, classifies by type, reports in Analytics tab
- **7 AI Tools** — Including `verifyPageHash` for content integrity verification
- **Self-Scoring Status** — 100-point scale across 8 categories with A-F letter grades
- **WebMCP Support** — Browser-native tool registration for Chrome 146+

## Repository Structure

```
spec/                   The AI Discovery Standard (CC-BY-4.0)
  standard.md           Full specification (v1.0.0-draft)
  ai.json               Reference implementation (rootz.global)
  knowledge.json        Reference knowledge endpoint
  feed.json             Reference feed endpoint

wordpress-plugin/       WordPress plugin (installable zip)
  README.md             Features, installation, endpoints
  rootz-ai-discovery-v1.8.0.zip   Ready to install
```

## IANA Registration

This specification proposes provisional registration of the well-known URI suffix `ai` per [RFC 8615](https://www.rfc-editor.org/rfc/rfc8615.html). Registration request: [pending]

## License

- **Specification** (`spec/`): [CC-BY-4.0](LICENSE) — open standard, free to implement
- **WordPress Plugin** (`wordpress-plugin/`): GPLv2 or later

## Links

- **Specification**: [spec/standard.md](spec/standard.md)
- **Live reference**: https://rootz.global/.well-known/ai
- **Scanner**: https://discover.rootz.global/scanner/
- **Rootz Corp**: https://rootz.global

---

*Published by Rootz Corp — Digital Name `0xD36AAf65a91bB7dc69942cF6B6d1dBa4Ef171664` on Polygon*

*"In 1993, Mosaic gave humans a browser. In 2026, `/.well-known/ai` gives AI agents a browser — with proof of origin built in."*
