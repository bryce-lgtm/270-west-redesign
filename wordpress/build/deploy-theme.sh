#!/usr/bin/env bash
# Deploys the child theme into the Local site.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
SITE="${W270_SITE:-/Users/Bryce/Local Sites/270-west/app/public}"
DEST="$SITE/wp-content/themes/270west"
mkdir -p "$DEST"
rsync -a --delete "$ROOT/wordpress/theme/270west/" "$DEST/"
echo "theme deployed to $DEST"
mkdir -p "$SITE/wp-content/plugins/270west-content"
rsync -a --delete "$ROOT/wordpress/plugins/270west-content/" "$SITE/wp-content/plugins/270west-content/"
echo "plugin deployed to $SITE/wp-content/plugins/270west-content"
