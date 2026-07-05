# Known Bugs & Issues

Tracking known bugs, their root causes, and fix status.

## Fixed

### BUG-001: Settings data loss when saving any tab (CRITICAL)
- **Reported**: 2026-03-02 by Dean & Laurie at thetavern.cz
- **Fixed in**: v2.2.1
- **Symptom**: Saving settings on one tab (e.g. Policies) wipes all fields on other tabs (e.g. AI Summary, Core Concepts, organization name disappear).
- **Root cause**: All 7 forms across 5 tabs used the same WordPress settings group `rootz_ai_discovery`. WordPress `options.php` processes ALL registered settings on form submit — any not in the POST data get their sanitize callback called with empty string, effectively resetting them.
- **Fix**: Split into 5 per-tab groups: `rootz_identity`, `rootz_content`, `rootz_policies`, `rootz_tools`, `rootz_account`. Each form only processes its own fields.
- **Files**: `admin/class-rootz-admin.php`, all `admin/views/settings-*.php`
- **Note**: This is a well-known WordPress anti-pattern. Never use a single settings group for multi-tab forms.

### BUG-002: Plugin zip breaks WP Playground (backslash paths)
- **Discovered**: 2026-02-22
- **Fixed in**: Build process
- **Symptom**: Plugin zip built with PowerShell `Compress-Archive` uses backslash paths in the archive, which WordPress Playground and some Linux unzip tools can't handle.
- **Root cause**: PowerShell uses Windows path separators in zip entries.
- **Fix**: Build zips using Python `zipfile` module with explicit forward-slash `arcname`. Never use PowerShell for plugin zips.
- **Build command**: `python -c "import zipfile, os; ..."` (see build instructions in ai.context.md)

## Open

### BUG-003: "String did not match expected pattern" on rootz.global scanner
- **Reported**: 2026-03-02 by Dean & Laurie at thetavern.cz
- **Severity**: Low (cosmetic/UX)
- **Symptom**: Error message "The string did not match the expected pattern" appears in the "Scan Your Site" section on rootz.global/ai-discovery.
- **Root cause**: The `new URL()` constructor in the scanner's server-side `POST /api/scan` throws when the input doesn't parse as a valid URL. The error message is the raw JS exception rather than a user-friendly message.
- **Workaround**: Enter the full URL including `https://` prefix, or just the bare domain like `thetavern.cz`.
- **Fix needed**: Better error message and input validation on the rootz.global scanner page.
- **Location**: `rootz-site-repo/index.mjs` line 348-351, `rootz-site-repo/pages/ai-discovery.html` line 1621

### BUG-004: Signed manifest caches stale version after plugin update
- **Discovered**: 2026-03-02
- **Severity**: Medium
- **Symptom**: After updating the plugin, `/.well-known/ai` continues to show the old version number in the `generator.version` field.
- **Root cause**: The `rootz_signed_manifest` option stores a snapshot of the entire ai.json response including the version. The `rootz_ai_json_cache` transient then caches this for 1 hour. On plugin update, neither is cleared.
- **Workaround**: Admin visits Settings > AI Discovery > Viewer tab and re-signs the manifest. Or use WP-CLI: `wp transient delete rootz_ai_json_cache && wp option delete rootz_signed_manifest`.
- **Fix needed**: Clear `rootz_signed_manifest` and `rootz_ai_json_cache` on plugin upgrade via the `upgrader_process_complete` hook.
- **Location**: `includes/class-rootz-ai-json.php` lines 22-31, needs upgrade hook in `rootz-ai-discovery.php`
- **Still reproducing 2026-06-09**: On discover.rootz.global, plugin header + `/llms.txt` footer report **2.3.3** while `/.well-known/ai` `generator.version` reports **2.3.2** (stale signed-manifest cache). Cosmetic only; re-signing the manifest in the Viewer tab clears it.

## Not a Bug (documented to prevent re-investigation)

### REST `/status` and `/context` return HTTP 401 to logged-out requests — INTENDED
- **Confirmed**: 2026-06-09 (status test)
- **Behavior**: `GET /wp-json/rootz/v1/status` and `/context` return `401 rest_forbidden` ("Sorry, you are not allowed to do that.") for unauthenticated/AI-agent requests.
- **Why it's correct**: In v2.3.1/2.3.2 (WordPress.org review), these two endpoints were deliberately changed from `__return_true` to `current_user_can('manage_options')`. They are admin management/setup console endpoints, not agent-facing tools. WP.org flagged public exposure as a security issue.
- **Do not "fix" by reverting to `__return_true`** — that regresses the WordPress.org-approved security posture. The seven agent-facing endpoints (static discovery + `searchContent`, `getPage`, `verifyPageHash`, `tools`) are the public surface and remain signed and reachable.

## Patterns to Avoid

1. **Never use a single WordPress settings group for multi-tab forms.** Each tab must have its own group. See BUG-001.
2. **Never build plugin zips with PowerShell.** Use Python `zipfile` with forward slashes. See BUG-002.
3. **Always clear signed manifest cache on version changes.** See BUG-004.
4. **Test with a fresh install** — not just the dev/lab site where options are already set.
