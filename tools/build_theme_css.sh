#!/usr/bin/env bash
# Rebuild first-party stylesheet.min.css files from stylesheet.css.
# Requires Node.js. Exact CLI versions are pinned for reproducible release builds.
# CSS: Lightning CSS CLI 1.33.0; JS: Terser 5.51.4.
# The source SHA-256 marker is checked by tools/release_check.php.
set -euo pipefail
root="$(cd "$(dirname "$0")/.." && pwd)"
for path in catalog/view/theme/codecart/stylesheet catalog/view/theme/default/stylesheet admin/view/stylesheet; do
  dir="$root/upload/$path"
  [ -f "$dir/stylesheet.css" ] || continue
  hash="$(sha256sum "$dir/stylesheet.css" | awk '{print $1}')"
  tmp="$(mktemp)"
  npx --yes lightningcss-cli@1.33.0 --minify "$dir/stylesheet.css" -o "$tmp"
  { printf '/*! CodeCart minified build; source stylesheet.css sha256:%s */\n' "$hash"; cat "$tmp"; } > "$dir/stylesheet.min.css"
  rm -f "$tmp"
  echo "built $path/stylesheet.min.css"
done

# Rebuild the common JavaScript variants used by the minification settings.
for side in admin catalog; do
  dir="$root/upload/$side/view/javascript"
  hash="$(sha256sum "$dir/common.js" | awk '{print $1}')"
  tmp="$(mktemp)"
  npx --yes terser@5.51.4 "$dir/common.js" --compress --mangle --comments some --output "$tmp"
  { printf '/*! CodeCart minified build; source common.js sha256:%s */\n' "$hash"; cat "$tmp"; } > "$dir/common.min.js"
  rm -f "$tmp"
  echo "built $side/view/javascript/common.min.js"
done
