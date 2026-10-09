#!/usr/bin/env bash
# One-off cleanup of the old Salient site's leftovers on PRODUCTION (270westconsulting.ca) after the
# 2026-10-09 launch, plus the two settings the launch did not carry over. Run from this Mac:
#
#   bash wordpress/build/prod-cleanup-2026-10-09.sh            # cleanup + redirects + translate key
#   W270_DELETE_NF_SUBS=1 bash wordpress/build/prod-cleanup-2026-10-09.sh   # also purge Ninja Forms entries
#
# Everything here is in the 2026-10-07 production clone (~/270west-production-clone-2026-10-07) if it
# is ever needed again. Pages and posts go to the trash, not deleted.
set -euo pipefail
PROD="git_deployer_d5f991bfa3_53790@53790.us8.ssh.myftpupload.com"
SG="giowm1228.siteground.biz"
SGPATH="/home/customer/www/brycec57.sg-host.com/public_html"
P="wp_ccebcc3c3e_"

echo "== 1. Old Salient pages and job posts -> trash"
ssh -o BatchMode=yes "$PROD" "cd /html && for id in 4 19 23 177 591 776 904 957 902 990; do wp post update \$id --post_status=trash 2>/dev/null | grep -E '^(Success|Error)' | sed \"s/^/   \$id: /\"; done"

echo "== 2. 301 redirects for the old URLs (Redirection plugin, group 1)"
ssh -o BatchMode=yes "$PROD" "cd /html && wp db query \"UPDATE ${P}redirection_items SET action_data='https://270westconsulting.ca/', title='Old careers category' WHERE id=1\" 2>/dev/null && echo '   /category/careers/ -> /'
for pair in '/home-page/|/' '/our-services/|/services/' '/about-us/|/about/' '/claims/|/services/claims/' '/privacy-policy/|/privacy/' '/get-started/|/vac-status-checker/' '/overview-of-services/|/services/' '/careers/|/' '/benefit-navigator/|/' '/client-care-administrator/|/'; do
  src=\${pair%%|*}; dst=\${pair##*|}; m=\${src%/}
  wp db query \"INSERT INTO ${P}redirection_items (url, match_url, action_data, regex, position, last_access, group_id, status, action_type, action_code, match_type, title) VALUES ('\$src', '\$m', 'https://270westconsulting.ca\$dst', 0, 0, '1970-01-01 00:00:00', 1, 'enabled', 'url', 301, 'url', 'Old site: \$src')\" 2>/dev/null && printf '   %-30s -> %s\n' \"\$src\" \"\$dst\"
done"

echo "== 3. Orphan menus from the old site"
ssh -o BatchMode=yes "$PROD" "cd /html && wp menu delete 'Main Left' 'Main Right' 2>/dev/null | grep -E '^(Success|Error)'"

echo "== 4. Plugins: WP Migrate DB off; inactive leftovers removed"
ssh -o BatchMode=yes "$PROD" "cd /html && wp plugin deactivate wp-migrate-db 2>/dev/null | grep -E '^(Success|Warning)'; wp plugin delete classic-editor insert-headers-and-footers wp-mail-smtp wp-migrate-db 2>/dev/null | grep -E '^(Success|Warning|Error)'"

if [ "${W270_DELETE_NF_SUBS:-}" = "1" ]; then
  echo "== 5. Ninja Forms submissions (old site's form entries, 2025-05 to 2026-08)"
  ssh -o BatchMode=yes "$PROD" "cd /html && wp db query \"DELETE m FROM ${P}postmeta m JOIN ${P}posts p ON p.ID=m.post_id WHERE p.post_type='nf_sub'\" 2>/dev/null && wp db query \"DELETE FROM ${P}posts WHERE post_type='nf_sub'\" 2>/dev/null && echo '   removed'"
else
  echo "== 5. Ninja Forms submissions kept (set W270_DELETE_NF_SUBS=1 to remove the 361 old entries)"
fi

echo "== 6. TranslatePress: copy the Google Translate key from SiteGround (the launch left it empty)"
KEY=$(ssh -o BatchMode=yes "$SG" "cd '$SGPATH' && wp option pluck trp_machine_translation_settings google-translate-key" 2>/dev/null | grep -E '^AIza' | head -1)
if [ -n "$KEY" ]; then
  for H in "$PROD" "git_deployer_82b7f03cfd_1292707@1292707.us8.ssh.myftpupload.com"; do
    ssh -o BatchMode=yes "$H" "cd /html && wp option patch update trp_machine_translation_settings google-translate-key '$KEY' 2>/dev/null | grep -E '^(Success|Error)' | sed 's/^/   /'"
  done
else
  echo "   could not read the key from SiteGround; set it in wp-admin > Settings > TranslatePress > Automatic Translation"
fi

echo "== 7. Flush every GoDaddy cache (page, CDN, object, transients)"
ssh -o BatchMode=yes "$PROD" "cd /html && wp cache flush 2>/dev/null | grep -E '^Success' | head -1"

echo "== 8. Check"
for u in /home-page/ /our-services/ /about-us/ /claims/ /privacy-policy/ /get-started/ /overview-of-services/ /careers/ /benefit-navigator/; do
  printf '   %-28s %s -> %s\n' "$u" "$(curl -s -o /dev/null -A Mozilla/5.0 -w '%{http_code}' "https://270westconsulting.ca$u")" "$(curl -s -o /dev/null -A Mozilla/5.0 -w '%{redirect_url}' "https://270westconsulting.ca$u")"
done
printf '   /fr/ french words: %s\n' "$(curl -sL -A Mozilla/5.0 "https://270westconsulting.ca/fr/?nc=$RANDOM" | grep -ciE 'anciens combattants|prestations|Votre service')"
echo "DONE"
