#!/usr/bin/env bash
# Deploys the 270west theme (with importer) to a SiteGround site through its Git repo, then runs the
# importer over SSH so pages, media, menus and Elementor settings exist on the server.
#
#   wordpress/build/deploy-siteground.sh            # push + import
#   W270_SG_SKIP_IMPORT=1 wordpress/build/deploy-siteground.sh   # push only
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
SG_USER="${W270_SG_USER:-u3253-6bupzx4ihm7m}"
SG_HOST="${W270_SG_HOST:-giowm1228.siteground.biz}"
SG_PORT="${W270_SG_PORT:-18765}"
SG_PATH="${W270_SG_PATH:-/home/customer/www/brycec57.sg-host.com/public_html}"
SG_SITE_HOST="${W270_SG_SITE_HOST:-brycec57.sg-host.com}"
CLONE="${W270_SG_CLONE:-$ROOT/../siteground-public_html}"
SSH="ssh -p $SG_PORT $SG_USER@$SG_HOST"

# 1. Fresh build artefacts in the repo (theme assets, generated.css, out/*.json).
"$ROOT/wordpress/build/sync-assets.sh"
( cd "$ROOT/wordpress/build" && python3 -m unittest discover -s tests -q && python3 seed_article.py && python3 generate.py )

# 2. Clone or update the SiteGround repo (public_html is the repo root).
if [ ! -d "$CLONE/.git" ]; then
  git clone "ssh://$SG_USER@$SG_HOST:$SG_PORT$SG_PATH" "$CLONE"
else
  git -C "$CLONE" pull --ff-only
fi

# 3. Copy the theme in, including the importer, its page JSON and the source photos.
DEST="$CLONE/wp-content/themes/270west"
mkdir -p "$DEST"
rsync -a --delete --exclude 'build/tests' --exclude '__pycache__' "$ROOT/wordpress/theme/270west/" "$DEST/"
mkdir -p "$DEST/build"
rsync -a --delete --exclude 'tests' --exclude '__pycache__' --exclude '*.py' --exclude 'spike.php' --exclude 'deploy-*.sh' --exclude 'build.sh' --exclude 'sync-assets.sh' --exclude 'layout-diff.js' "$ROOT/wordpress/build/" "$DEST/build/"
PDEST="$CLONE/wp-content/plugins/270west-content"
mkdir -p "$PDEST"
rsync -a --delete "$ROOT/wordpress/plugins/270west-content/" "$PDEST/"
git -C "$CLONE" add -A wp-content/plugins/270west-content
git -C "$CLONE" add -A wp-content/themes/270west
if git -C "$CLONE" diff --cached --quiet; then echo "theme unchanged on SiteGround"; else
  git -C "$CLONE" commit -q -m "Deploy 270west theme and importer ($(git -C "$ROOT" rev-parse --short HEAD))"
  git -C "$CLONE" push
fi

# 4. On the server: make sure Elementor + Hello exist, activate the theme, import content.
[ "${W270_SG_SKIP_IMPORT:-}" = "1" ] && { echo "push done (import skipped)"; exit 0; }
$SSH "cd '$SG_PATH' && \
  wp plugin is-installed elementor || wp plugin install elementor && wp plugin activate elementor && \
  wp theme is-installed hello-elementor || wp theme install hello-elementor && \
  W270_SITE='$SG_PATH' W270_HOST='$SG_SITE_HOST' php wp-content/themes/270west/build/activate-theme.php && \
  W270_SITE='$SG_PATH' W270_HOST='$SG_SITE_HOST' php wp-content/themes/270west/build/import.php --all && \
  W270_SITE='$SG_PATH' W270_HOST='$SG_SITE_HOST' php wp-content/themes/270west/build/render-check.php"
echo "SITEGROUND DEPLOY OK — https://$SG_SITE_HOST/"
