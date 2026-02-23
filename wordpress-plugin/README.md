# Rootz AI Discovery — WordPress Plugin

**Version:** 1.8.0
**Requires:** WordPress 6.0+, PHP 7.4+ (PHP 8.0+ recommended)
**License:** GPLv2 or later

## What It Does

Automatically generates a signed `/.well-known/ai` manifest from your existing WordPress content. No manual JSON editing required. Your site becomes AI-discoverable and verifiable in minutes.

## Features

- **Signed Manifests** — Plugin generates a secp256k1 wallet and signs all endpoints with SHA-256 content hashes. Every AI agent can verify your content hasn't been tampered with.
- **Per-Page Content Hashes** — SHA-256 hash of every page in the site manifest for individual page integrity verification.
- **Three-Tier Generation** — Generates ai.json (discovery), knowledge.json (encyclopedia), and feed.json (AI-optimized news) from existing WordPress content.
- **7 AI Tools** — Declares tools AI agents can invoke: `verifyPageHash`, `searchContent`, `getStatus`, `getOrganizationInfo`, `getPolicies`, `getKnowledge`, `getFeed`.
- **AI Access Metrics** — Tracks which AI agents visit your site, classifies them by type, reports in the Analytics tab.
- **Self-Scoring Status** — 100-point scale across 8 categories with A-F letter grades. Know exactly how AI-ready your site is.
- **WebMCP Support** — Browser-native tool registration for Chrome 146+.
- **7 Admin Tabs** — Identity, Content, Policies, Tools, Analytics, What AI Sees (live viewer), Account & Signing.

## Installation

1. Download `rootz-ai-discovery-v1.8.0.zip` from this directory
2. In WordPress admin: Plugins > Add New > Upload Plugin
3. Upload the zip file and activate
4. Go to Settings > AI Discovery to configure

## Requirements

- **PHP GMP extension** required for wallet generation and signing (most hosts have this)
- Without GMP, the plugin works but signing is disabled

## Endpoints Created

| Endpoint | Description |
|----------|-------------|
| `/.well-known/ai` | Discovery manifest (signed) |
| `/wp-json/rootz/v1/knowledge` | Knowledge encyclopedia (signed) |
| `/wp-json/rootz/v1/feed` | AI-optimized feed (signed) |
| `/wp-json/rootz/v1/policies` | Policy declarations (signed) |
| `/wp-json/rootz/v1/content` | Full content with hashes |
| `/wp-json/rootz/v1/tools` | Tools manifest |
| `/wp-json/rootz/v1/status` | Self-scoring readiness check |

## Scanner

Test your site's AI readiness score after installation: https://discover.rootz.global/scanner/

## More Information

- **AI Discovery Standard**: [spec/standard.md](../spec/standard.md)
- **Rootz Corp**: https://rootz.global
