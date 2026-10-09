#!/usr/bin/env bash
# Deploys the 270west theme (with importer) to the GoDaddy Managed WordPress STAGING site over SSH,
# then runs the importer there. GoDaddy's CI/CD deploy user has a shell and WP-CLI but no git repo in
# the web root, so files go up with rsync instead of a push. Same importer flags as deploy-siteground.sh:
#
#   wordpress/build/deploy-godaddy.sh                        # sync + import
#   W270_GD_SKIP_IMPORT=1 wordpress/build/deploy-godaddy.sh  # sync only
#   W270_GD_PRO_PLUGINS=/folder/with/elementor-pro,acf-pro   # also upload the licensed plugins from here
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
GD_USER="${W270_GD_USER:-git_deployer_227104b450_1292707}"
GD_HOST="${W270_GD_HOST:-1292707.us8.ssh.myftpupload.com}"
GD_PATH="${W270_GD_PATH:-/html}"
GD_SITE_HOST="${W270_GD_SITE_HOST:-1292707.us8.myftpupload.com}"
SSH="ssh -o BatchMode=yes $GD_USER@$GD_HOST"
RS="rsync -az -e ssh"

# 1. Fresh build artefacts in the repo (theme assets, generated.css, out/*.json).
"$ROOT/wordpress/build/sync-assets.sh"
( cd "$ROOT/wordpress/build" && python3 -m unittest discover -s tests -q && python3 seed_article.py && python3 generate.py )

# 2. Theme (with the importer, its page JSON and source photos) and the content plugin.
$SSH "mkdir -p '$GD_PATH/wp-content/themes/270west/build' '$GD_PATH/wp-content/plugins/270west-content'"
$RS --delete --exclude 'build/tests' --exclude '__pycache__' "$ROOT/wordpress/theme/270west/" "$GD_USER@$GD_HOST:$GD_PATH/wp-content/themes/270west/"
$RS --delete --exclude 'tests' --exclude '__pycache__' --exclude '*.py' --exclude 'spike.php' --exclude 'deploy-*.sh' --exclude 'build.sh' --exclude 'sync-assets.sh' --exclude 'layout-diff.js' "$ROOT/wordpress/build/" "$GD_USER@$GD_HOST:$GD_PATH/wp-content/themes/270west/build/"
# acf-json is excluded from --delete: ACF writes field-group edits made in wp-admin back into it.
$RS --delete --exclude 'acf-json' "$ROOT/wordpress/plugins/270west-content/" "$GD_USER@$GD_HOST:$GD_PATH/wp-content/plugins/270west-content/"
$RS "$ROOT/wordpress/plugins/270west-content/acf-json/" "$GD_USER@$GD_HOST:$GD_PATH/wp-content/plugins/270west-content/acf-json/"

# 3. Licensed plugins (Elementor Pro, ACF Pro) are not on wordpress.org: upload them from a local
# copy when W270_GD_PRO_PLUGINS points at a folder holding elementor-pro/ and advanced-custom-fields-pro/.
if [ -n "${W270_GD_PRO_PLUGINS:-}" ]; then
  for p in elementor-pro advanced-custom-fields-pro; do
    [ -d "$W270_GD_PRO_PLUGINS/$p" ] && $RS --delete "$W270_GD_PRO_PLUGINS/$p/" "$GD_USER@$GD_HOST:$GD_PATH/wp-content/plugins/$p/"
  done
fi

# 4. On the server: dependencies, theme activation, content import, render check, cache flush.
[ "${W270_GD_SKIP_IMPORT:-}" = "1" ] && { echo "sync done (import skipped)"; exit 0; }
REFRESH=""
if [ "${W270_GD_REFRESH_TEMPLATES:-}" = "1" ]; then REFRESH="--refresh-templates"
elif [ -n "${W270_GD_REFRESH_TEMPLATES:-}" ]; then REFRESH="--refresh-templates=${W270_GD_REFRESH_TEMPLATES}"; fi
[ -n "${W270_GD_REFRESH_RESOURCES:-}" ] && REFRESH="$REFRESH --refresh-resources=${W270_GD_REFRESH_RESOURCES}"
[ -n "${W270_GD_REFRESH_FORMS:-}" ] && REFRESH="$REFRESH --refresh-forms=${W270_GD_REFRESH_FORMS}"
$SSH "cd '$GD_PATH' && \
  { wp plugin is-installed elementor || wp plugin install elementor; } && wp plugin activate elementor && \
  wp plugin is-installed elementor-pro && wp plugin activate elementor-pro && \
  wp plugin is-installed advanced-custom-fields-pro && wp plugin activate advanced-custom-fields-pro && \
  { wp plugin is-installed translatepress-multilingual || wp plugin install translatepress-multilingual; } && wp plugin activate translatepress-multilingual && \
  { wp plugin is-installed google-site-kit || wp plugin install google-site-kit; } && wp plugin activate google-site-kit && \
  { wp plugin is-installed wordpress-seo || wp plugin install wordpress-seo; } && wp plugin activate wordpress-seo && \
  wp plugin activate gravityforms gravityformswebhooks 270west-content && \
  { wp language core is-installed fr_CA || wp language core install fr_CA; } && \
  { wp theme is-installed hello-elementor || wp theme install hello-elementor; } && \
  W270_SITE='$GD_PATH' W270_HOST='$GD_SITE_HOST' php wp-content/themes/270west/build/activate-theme.php && \
  W270_SITE='$GD_PATH' W270_HOST='$GD_SITE_HOST' php wp-content/themes/270west/build/import.php --all $REFRESH && \
  W270_SITE='$GD_PATH' W270_HOST='$GD_SITE_HOST' php wp-content/themes/270west/build/render-check.php && \
  wp cache flush >/dev/null && echo 'object cache flushed' && \
  { wp eval 'do_action("wp_update_nav_menu", 0);' >/dev/null 2>&1 || true; } && echo 'page cache ban requested (GoDaddy gateway)'"
# The gateway (page) cache has no WP-CLI command; the system plugin bans it on the nav-menu hook.
# (switch_theme would also ban it, but Elementor hooks that action with a 3-argument callback and fatals.)
echo "GODADDY STAGING DEPLOY OK — https://$GD_SITE_HOST/"
