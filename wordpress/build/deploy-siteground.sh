#!/usr/bin/env bash
# Deploys the 270west theme, the importer and the content plugin to a SiteGround site through its Git
# repo, then activates everything over SSH.
#
# CODE ONLY by default. Since 2026-10-26 the site's pages are edited directly in Elementor, so a plain
# deploy never runs the importer. Same flags as deploy-godaddy.sh:
#
#   wordpress/build/deploy-siteground.sh                     # push code only (pages, menus, kit untouched)
#   W270_SG_IMPORT=1 wordpress/build/deploy-siteground.sh    # FULL import: rewrites every page, menu and the
#                                                            # Elementor kit from the repo, wiping Elementor
#                                                            # edits. Fresh/empty sites only.
#   W270_SG_REFRESH_FEEDS=contact,booking wordpress/build/deploy-siteground.sh   # push + only that targeted
#                                                            # --refresh-* (feeds, forms, templates, resources)
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
# acf-json is excluded from --delete: ACF writes field-group edits made in wp-admin back into
# this folder, and those belong to the client. New group files are still copied up.
rsync -a --delete --exclude 'acf-json' "$ROOT/wordpress/plugins/270west-content/" "$PDEST/"
rsync -a "$ROOT/wordpress/plugins/270west-content/acf-json/" "$PDEST/acf-json/"
git -C "$CLONE" add -A wp-content/plugins/270west-content
git -C "$CLONE" add -A wp-content/themes/270west
if git -C "$CLONE" diff --cached --quiet; then echo "theme unchanged on SiteGround"; else
  git -C "$CLONE" commit -q -m "Deploy 270west theme and importer ($(git -C "$ROOT" rev-parse --short HEAD))"
  git -C "$CLONE" push
fi

# 4. On the server: make sure Elementor + Hello exist, activate the theme, (opt-in) import content.
# The importer only runs when asked for. W270_SG_IMPORT=1 is the full --all import (destructive: pages,
# menus and the Elementor kit are rewritten from the repo). The W270_SG_REFRESH_* flags run the importer
# with just that --refresh-* flag, which touches only the named seed-once items and nothing else:
#   W270_SG_REFRESH_TEMPLATES=1|name,…  re-seeds the Pro header/footer/single templates from the repo
#   W270_SG_REFRESH_RESOURCES=slug-a,…  re-seeds those resources' copy and fields
#   W270_SG_REFRESH_FORMS=checker,…     re-applies those Gravity Forms (and their feeds)
#   W270_SG_REFRESH_FEEDS=contact,…     re-applies only those forms' Creatio feeds (forms stay as wp-admin has them)
IMPORT_ARGS=""
[ "${W270_SG_IMPORT:-}" = "1" ] && IMPORT_ARGS="--all"
if [ "${W270_SG_REFRESH_TEMPLATES:-}" = "1" ]; then IMPORT_ARGS="$IMPORT_ARGS --refresh-templates"
elif [ -n "${W270_SG_REFRESH_TEMPLATES:-}" ]; then IMPORT_ARGS="$IMPORT_ARGS --refresh-templates=${W270_SG_REFRESH_TEMPLATES}"; fi
[ -n "${W270_SG_REFRESH_RESOURCES:-}" ] && IMPORT_ARGS="$IMPORT_ARGS --refresh-resources=${W270_SG_REFRESH_RESOURCES}"
[ -n "${W270_SG_REFRESH_FORMS:-}" ] && IMPORT_ARGS="$IMPORT_ARGS --refresh-forms=${W270_SG_REFRESH_FORMS}"
[ -n "${W270_SG_REFRESH_FEEDS:-}" ] && IMPORT_ARGS="$IMPORT_ARGS --refresh-feeds=${W270_SG_REFRESH_FEEDS}"
IMPORT_ARGS="${IMPORT_ARGS# }"
IMPORT_STEP=""
if [ -n "$IMPORT_ARGS" ]; then
  case " $IMPORT_ARGS " in *" --all "*) echo "WARNING: full import — every page, menu and the Elementor kit will be rewritten from the repo (Elementor edits lost)";; esac
  echo "importer: import.php $IMPORT_ARGS"
  IMPORT_STEP="W270_SITE='$SG_PATH' W270_HOST='$SG_SITE_HOST' php wp-content/themes/270west/build/import.php $IMPORT_ARGS && "
else
  echo "importer: skipped (code-only deploy; set W270_SG_IMPORT=1 or a W270_SG_REFRESH_* flag to run it)"
fi
$SSH "cd '$SG_PATH' && \
  wp plugin is-installed elementor || wp plugin install elementor && wp plugin activate elementor && \
  { wp plugin is-installed translatepress-multilingual || wp plugin install translatepress-multilingual; } && wp plugin activate translatepress-multilingual && \
  { wp plugin is-installed google-site-kit || wp plugin install google-site-kit; } && wp plugin activate google-site-kit && \
  { wp plugin is-installed wordpress-seo || wp plugin install wordpress-seo; } && wp plugin activate wordpress-seo && \
  { wp language core is-installed fr_CA || wp language core install fr_CA; } && \
  wp theme is-installed hello-elementor || wp theme install hello-elementor && \
  W270_SITE='$SG_PATH' W270_HOST='$SG_SITE_HOST' php wp-content/themes/270west/build/activate-theme.php && \
  ${IMPORT_STEP}\
  W270_SITE='$SG_PATH' W270_HOST='$SG_SITE_HOST' php wp-content/themes/270west/build/render-check.php"
echo "SITEGROUND DEPLOY OK — https://$SG_SITE_HOST/"
