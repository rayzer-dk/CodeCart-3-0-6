#!/usr/bin/env bash
# Rebuild first-party storefront stylesheet.min.css files from stylesheet.css.
# Requires Node.js (npx lightningcss-cli). The source SHA-256 marker is checked by tools/release_check.php.
set -euo pipefail
root="$(cd "$(dirname "$0")/.." && pwd)"
for theme in codecart default; do
  dir="$root/upload/catalog/view/theme/$theme/stylesheet"
  [ -f "$dir/stylesheet.css" ] || continue
  hash="$(sha256sum "$dir/stylesheet.css" | awk '{print $1}')"
  tmp="$(mktemp)"
  npx --yes lightningcss-cli@1 --minify "$dir/stylesheet.css" -o "$tmp"
  { printf '/*! CodeCart minified build; source stylesheet.css sha256:%s */\n' "$hash"; cat "$tmp"; } > "$dir/stylesheet.min.css"
  rm -f "$tmp"
  echo "built $theme/stylesheet.min.css"
done
