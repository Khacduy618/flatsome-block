#!/bin/sh
# Packages the plugin as dist/flatsome-block-manager.zip for Plugins > Add New > Upload Plugin.
# Run after `npm run build` (build/ must exist).
set -e
cd "$(dirname "$0")/.."
[ -f build/admin.js ] || { echo "Missing build/. Run: npm install && npm run build"; exit 1; }

NAME=flatsome-block-manager
STAGE=$(mktemp -d)
mkdir -p "$STAGE/$NAME" dist
rsync -a ./ "$STAGE/$NAME/" \
  --exclude node_modules --exclude dist --exclude '.git*' --exclude '.DS_Store' \
  --exclude '.playwright-mcp' --exclude 'package-lock.json' --exclude tests --exclude docs --exclude .superpowers --exclude CLAUDE.md
rm -f "dist/$NAME.zip"
( cd "$STAGE" && zip -qr -X "$OLDPWD/dist/$NAME.zip" "$NAME" )
rm -rf "$STAGE"
echo "Created dist/$NAME.zip ($(du -h "dist/$NAME.zip" | cut -f1))"
