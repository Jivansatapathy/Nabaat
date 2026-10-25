#!/bin/bash
# SessionStart hook for Claude Code cloud sessions.
# The plugins are plain PHP with no Composer/npm dependencies; this only makes
# sure the tools used to check them are present:
#   php -l <file>                                  (syntax lint)
#   python3 -I nabaat-luxury-product-page/tools/check_category_rules.py <export.json>
set -euo pipefail

if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
  exit 0
fi

missing=()
command -v php >/dev/null 2>&1 || missing+=(php-cli)
command -v python3 >/dev/null 2>&1 || missing+=(python3)

if [ ${#missing[@]} -gt 0 ]; then
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -qq
  apt-get install -y -qq "${missing[@]}"
fi

php -v | head -n 1
python3 --version
