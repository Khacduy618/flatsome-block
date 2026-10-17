#!/bin/sh
# Đóng gói plugin thành dist/flatsome-block-manager.zip để upload ở Plugins > Add New > Upload Plugin.
# Chạy sau `npm run build` (build/ phải có sẵn).
set -e
cd "$(dirname "$0")/.."
[ -f build/admin.js ] || { echo "Thiếu build/. Chạy: npm install && npm run build"; exit 1; }

NAME=flatsome-block-manager
STAGE=$(mktemp -d)
mkdir -p "$STAGE/$NAME" dist
rsync -a ./ "$STAGE/$NAME/" \
  --exclude node_modules --exclude dist --exclude '.git*' --exclude '.DS_Store' \
  --exclude '.playwright-mcp' --exclude 'package-lock.json'
rm -f "dist/$NAME.zip"
( cd "$STAGE" && zip -qr -X "$OLDPWD/dist/$NAME.zip" "$NAME" )
rm -rf "$STAGE"
echo "Đã tạo dist/$NAME.zip ($(du -h "dist/$NAME.zip" | cut -f1))"
