#!/usr/bin/env bash
set -euo pipefail
BASE="${BASE:-http://127.0.0.1:8080}"
PID="${PRODUCT_ID:-$(cat /tmp/product-id)}"
seq 1 200 | xargs -P 20 -I{} curl -fsS -o /dev/null "$BASE/"
seq 1 100 | xargs -P 10 -I{} curl -fsS -o /dev/null "$BASE/index.php?route=product/product&product_id=$PID"
echo "Parallel HTTP load smoke PASS"
