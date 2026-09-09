#!/usr/bin/env bash
# Serves the static prototype from the Local site (http://270-west.local/proto/) so the
# same-origin layout diff (layout-diff.js) can compare it with the WordPress pages.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
SITE="${W270_SITE:-/Users/Bryce/Local Sites/270-west/app/public}"
mkdir -p "$SITE/proto"
rsync -a --delete --include='*.html' --include='css/***' --include='js/***' --include='img/***' --exclude='*' "$ROOT/" "$SITE/proto/"
echo "prototype deployed to $SITE/proto"
