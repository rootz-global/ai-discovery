"""Build the WordPress.org distribution zip.

BUG-002: never build these with PowerShell Compress-Archive — it writes Windows
backslash separators into the archive, which WP Playground and some unzip
implementations cannot read. Python zipfile with explicit forward-slash arcnames
is the only supported path.
"""
import os
import sys
import zipfile

ROOT = 'plugin'
SLUG = 'rootz-ai-discovery'
OUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), SLUG + '.zip')

# vendor/ IS shipped. Signing needs simplito/elliptic-php and kornrunner/keccak,
# and WordPress.org installs a zip verbatim — there is no composer step on the
# user's host. Run `composer install --no-dev` in plugin/ before building.
SKIP_DIRS = {'.git', 'node_modules', 'languages'}
SKIP_FILES = {'BUGS.md', 'CHANGELOG.md', 'ai.context.md', 'composer.json'}

count = 0
with zipfile.ZipFile(OUT, 'w', zipfile.ZIP_DEFLATED) as z:
    for dirpath, dirnames, filenames in os.walk(ROOT):
        dirnames[:] = [d for d in dirnames if d not in SKIP_DIRS]
        for name in filenames:
            if name in SKIP_FILES:
                continue
            full = os.path.join(dirpath, name)
            arc = SLUG + '/' + os.path.relpath(full, ROOT).replace(os.sep, '/')
            z.write(full, arc)
            count += 1

names = zipfile.ZipFile(OUT).namelist()
bad = [n for n in names if chr(92) in n]

print('zipped %d files -> %s' % (count, OUT))
print('sample:', names[:3])
print('backslash paths:', len(bad))
if bad:
    print('FAIL: archive contains Windows separators')
    sys.exit(1)
