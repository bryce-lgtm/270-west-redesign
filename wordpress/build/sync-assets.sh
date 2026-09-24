#!/usr/bin/env bash
# Copies the prototype's stylesheet, script and images into the child theme.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
T="$ROOT/wordpress/theme/270west/assets"
mkdir -p "$T/css" "$T/js" "$T/img"
cp "$ROOT/css/styles.css" "$T/css/styles.css"
cp "$ROOT/css/landing.css" "$T/css/landing.css"
cp "$ROOT/js/main.js" "$T/js/main.js"
rsync -a --delete "$ROOT/img/" "$T/img/"
echo "assets synced"
