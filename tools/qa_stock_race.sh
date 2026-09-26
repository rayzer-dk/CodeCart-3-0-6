#!/usr/bin/env bash
set -euo pipefail
BASE="${BASE:-http://127.0.0.1:8080}"
PID="${PRODUCT_ID:-$(cat /tmp/product-id)}"
mysql -h127.0.0.1 -uroot -proot codecart -e "UPDATE oc_product SET quantity=1,subtract=1,minimum=1 WHERE product_id=$PID"
COUNTRY="$(mysql -h127.0.0.1 -uroot -proot -N codecart -e 'SELECT value FROM oc_setting WHERE `key`="config_country_id" LIMIT 1')"
ZONE="$(mysql -h127.0.0.1 -uroot -proot -N codecart -e 'SELECT value FROM oc_setting WHERE `key`="config_zone_id" LIMIT 1')"
prepare_checkout() {
  local cookie="$1" email="$2" prefix="$3"
  curl -fsS -c "$cookie" -b "$cookie" -X POST -d "product_id=$PID" -d 'quantity=1' "$BASE/index.php?route=checkout/cart/add" >/dev/null
  curl -fsS -c "$cookie" -b "$cookie" -X POST     -d 'firstname=Race' -d 'lastname=Tester' -d "email=$email" -d 'telephone=+4512345678'     -d 'company=' -d 'address_1=Race Street 1' -d 'address_2=' -d 'city=Kyiv' -d 'postcode=01001'     -d "country_id=$COUNTRY" -d "zone_id=$ZONE" -d 'shipping_address=1'     "$BASE/index.php?route=checkout/guest/save" >/dev/null
  curl -fsS -c "$cookie" -b "$cookie" "$BASE/index.php?route=checkout/shipping_method" >/dev/null
  curl -fsS -c "$cookie" -b "$cookie" -X POST -d 'shipping_method=flat.flat' -d 'comment=Race QA' "$BASE/index.php?route=checkout/shipping_method/save" >/dev/null
  curl -fsS -c "$cookie" -b "$cookie" "$BASE/index.php?route=checkout/payment_method" >/dev/null
  curl -fsS -c "$cookie" -b "$cookie" -X POST -d 'payment_method=cod' -d 'agree=1' "$BASE/index.php?route=checkout/payment_method/save" >/dev/null
  curl -fsS -c "$cookie" -b "$cookie" "$BASE/index.php?route=checkout/confirm" >"/tmp/$prefix-confirm.html"
  grep -o "codecart_payment_token: '[^']*'" "/tmp/$prefix-confirm.html" | head -1 | cut -d"'" -f2 >"/tmp/$prefix-token"
  test -s "/tmp/$prefix-token"
}
prepare_checkout /tmp/race1.cookies race1@example.test race1
prepare_checkout /tmp/race2.cookies race2@example.test race2
T1="$(cat /tmp/race1-token)"; T2="$(cat /tmp/race2-token)"
(curl -sS -c /tmp/race1.cookies -b /tmp/race1.cookies -X POST --data-urlencode "codecart_payment_token=$T1" "$BASE/index.php?route=extension/payment/cod/confirm" >/tmp/race1.json) & P1=$!
(curl -sS -c /tmp/race2.cookies -b /tmp/race2.cookies -X POST --data-urlencode "codecart_payment_token=$T2" "$BASE/index.php?route=extension/payment/cod/confirm" >/tmp/race2.json) & P2=$!
wait "$P1" || true
wait "$P2" || true
QTY="$(mysql -h127.0.0.1 -uroot -proot -N codecart -e "SELECT quantity FROM oc_product WHERE product_id=$PID")"
PROCESSED="$(mysql -h127.0.0.1 -uroot -proot -N codecart -e "SELECT COUNT(*) FROM oc_order WHERE email IN ('race1@example.test','race2@example.test') AND order_status_id>0")"
test "$QTY" = "0"
test "$PROCESSED" = "1"
echo "Stock race PASS: quantity=$QTY processed_orders=$PROCESSED"
