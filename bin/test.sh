#!/bin/sh
# Chạy test trên site DDEV handdn: đồng bộ plugin (rsync, không bind-mount) rồi chạy tests/run.php bằng WP-CLI.
# Dùng: sh bin/test.sh [lọc-tên-file]
set -e
export PATH="$PATH:/Applications/Docker.app/Contents/Resources/bin"
cd "$(dirname "$0")/.."
SITE="$HOME/Documents/project-wp/handdn"
rsync -a --delete --exclude node_modules --exclude dist --exclude .git --exclude .superpowers ./ "$SITE/wp-content/plugins/flatsome-block-manager/"
cd "$SITE"
ddev wp eval-file wp-content/plugins/flatsome-block-manager/tests/run.php "$@"
