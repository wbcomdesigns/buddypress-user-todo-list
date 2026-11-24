#!/usr/bin/env bash
# Build dist/bp-user-to-do-list-{version}.zip (runs local CI first). The zip folder is the WordPress.org slug.
# Same entries as `npx grunt release`; .distignore mirrors its copy:build excludes.
set -euo pipefail
cd "$(dirname "$0")/.."
slug=bp-user-to-do-list
main=bp-user-todo-list.php
bin/local-ci.sh
npx grunt build
version=$(grep -m1 -E '^\s*\*\s*Version:' "$main" | awk '{print $NF}')
rm -rf dist && mkdir -p "dist/$slug"
# Unanchored patterns only: macOS openrsync ignores "/path" anchors.
rsync -a --exclude-from=<(grep -vE '^\s*(#|$)' .distignore) ./ "dist/$slug/"
# Deflate every entry: the zip CLI stores tiny files (index.php stubs) as plain text, which upload firewalls block.
( cd dist && python3 -I -c 'import os,sys,zipfile
with zipfile.ZipFile(sys.argv[1], "w", zipfile.ZIP_DEFLATED) as z:
    for r, d, f in os.walk(sys.argv[2]):
        d.sort()
        for n in sorted(f): z.write(os.path.join(r, n))' "$slug-$version.zip" "$slug" )
rm -rf "dist/$slug"
zip="dist/$slug-$version.zip"
# --- WordPress.org folder lock (Wbcom standard - do not edit) ----------------------
top=$(unzip -Z1 "$zip" | head -1 | cut -d/ -f1)
[ "$top" = "$slug" ] || { echo "Zip folder '$top' is not the WordPress.org slug '$slug'"; exit 1; }
unzip -Z1 "$zip" | grep -qx "$slug/$main" || { echo "Main file $slug/$main missing from zip"; exit 1; }
[ "$(LC_ALL=C grep -a -c '<?php' "$zip" || true)" = 0 ] || { echo "Zip has plain-text PHP (upload firewall)"; exit 1; }
# -----------------------------------------------------------------------------------
entries=$(unzip -Z1 "dist/$slug-$version.zip")
leaks=$(echo "$entries" | grep -iE '/docs/|/audit/|/bin/|/tests/|node_modules|CLAUDE|gruntfile|package(-lock)?\.json|\.md$' || true)
[ -z "$leaks" ] || { echo "Zip contains dev files:"; echo "$leaks"; exit 1; }
for l in de_DE es_ES fr_FR it_IT pt_BR; do
	grep -qx "$slug/languages/bp-user-to-do-list-$l.l10n.php" <<<"$entries" || { echo "Missing $l translations"; exit 1; }
done
echo "Built dist/$slug-$version.zip"
