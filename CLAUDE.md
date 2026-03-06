# CLAUDE.md — ai-discovery

## What This Repo Is

The AI Discovery Standard specification and WordPress plugin source.

- `spec/` — Standard spec v1.0, v1.1, v1.2 (CC-BY-4.0)
- `plugin/` — WordPress plugin source (GPLv2+), currently v2.3.0

## Plugin Architecture

The plugin serves `/.well-known/ai` from WordPress with signed responses. Key classes:

- `class-rootz-rest-api.php` — REST API with 9 tools (getPage, searchContent, verifyPageHash, etc.)
- `class-rootz-signer.php` — secp256k1 ECDSA signing (plugin wallet)
- `class-rootz-ai-json.php` — Generates the ai.json manifest
- `class-rootz-llms-txt.php` — llms.txt generator + html_to_markdown()
- `class-rootz-metrics.php` — AI agent access tracking
- `class-rootz-content-endpoint.php` — Content endpoint (pages, posts, media)

## Key Patterns

- **Signing**: Every JSON response gets `_signature` block via `Rootz_Signer::sign_response()`
- **Freshness**: `_freshness` block with adaptive TTL based on content age
- **Provenance**: `_origin` + `_provenance` blocks embedded in tool responses
- **Vendor libs**: simplito/elliptic-php + kornrunner/keccak in `vendor/` (not committed, install via composer)
- **Help tips**: Every admin setting has `Rootz_Admin::help_tip()` for operator guidance

## Building

```bash
# Install PHP dependencies (needed for signing)
cd plugin && composer install

# Build distribution zip (use Python, NOT PowerShell)
python -c "import zipfile; ..."  # see rootz-wp-plugin/ai.context.md for full script
```

## Two-Site Architecture

- **rootz.global** — Express site, serves spec + scanner + releases (rootz-global/rootz-site repo)
- **discover.rootz.global** — WordPress lab site running the plugin (Oracle server 141.148.25.214)

Plugin zips for the self-hosted updater go in `rootz-global/rootz-site/releases/` with `update.json`.

## Related Files

- `plugin/ai.context.md` — Detailed internal reference manual with deployment patterns and checklists
