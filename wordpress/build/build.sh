#!/usr/bin/env bash
# Full pipeline: sync assets → unit tests → generate → deploy theme (+ prototype for diffing) →
# activate → import → render check → coverage check.
set -euo pipefail
cd "$(dirname "$0")"
PHP="${W270_PHP:-/Users/Bryce/Library/Application Support/Local/lightning-services/php-8.2.29+0/bin/darwin-arm64/bin/php}"
INI="${W270_INI:-/Users/Bryce/Library/Application Support/Local/run/USpouZOL3/conf/php/php.ini}"
./sync-assets.sh
python3 -m unittest discover -s tests -q
python3 seed_article.py
python3 generate.py
./deploy-theme.sh
./deploy-proto.sh
"$PHP" -c "$INI" activate-theme.php
"$PHP" -c "$INI" import.php --all
"$PHP" -c "$INI" render-check.php
python3 check-coverage.py
echo "BUILD OK"
