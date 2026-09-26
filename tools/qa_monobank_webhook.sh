#!/usr/bin/env bash
set -euo pipefail
BASE="${BASE:-http://127.0.0.1:8080}"
cp -a bonus-source/monobank_payment/upload/. runtime/upload/
ORDER_ID="$(mysql -h127.0.0.1 -uroot -proot -N codecart -e "SELECT order_id FROM oc_order WHERE email='browser@example.test' ORDER BY order_id DESC LIMIT 1")"
test -n "$ORDER_ID"
mysql -h127.0.0.1 -uroot -proot codecart <<SQL
CREATE TABLE IF NOT EXISTS oc_monobank_modern_invoice (
  monobank_invoice_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  invoice_id VARCHAR(128) NOT NULL,
  page_url VARCHAR(2048) NOT NULL DEFAULT '',
  reference VARCHAR(128) NOT NULL,
  amount BIGINT UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'UAH',
  status VARCHAR(32) NOT NULL DEFAULT 'created',
  modified_date VARCHAR(40) NOT NULL DEFAULT '',
  date_added DATETIME NOT NULL,
  date_modified DATETIME NOT NULL,
  PRIMARY KEY (monobank_invoice_id),
  UNIQUE KEY uq_invoice_id (invoice_id),
  UNIQUE KEY uq_order_id (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
DELETE FROM oc_setting WHERE code='payment_monobank_modern';
INSERT INTO oc_setting(store_id,code,\`key\`,value,serialized) VALUES
(0,'payment_monobank_modern','payment_monobank_modern_status','1',0),
(0,'payment_monobank_modern','payment_monobank_modern_success_status_id','2',0),
(0,'payment_monobank_modern','payment_monobank_modern_processing_status_id','1',0),
(0,'payment_monobank_modern','payment_monobank_modern_failure_status_id','10',0),
(0,'payment_monobank_modern','payment_monobank_modern_debug','1',0),
(0,'payment_monobank_modern','payment_monobank_modern_token','qa-token-not-real',0);
INSERT INTO oc_monobank_modern_invoice(order_id,invoice_id,page_url,reference,amount,currency,status,modified_date,date_added,date_modified)
VALUES ($ORDER_ID,'qa-invoice','https://example.test/pay','QA-$ORDER_ID',100,'UAH','created','',NOW(),NOW());
SQL
STORAGE="$(php -r "require 'runtime/upload/config.php'; echo DIR_STORAGE;")"
mkdir -p "$STORAGE/codecart/monobank_payment"
openssl ecparam -name prime256v1 -genkey -noout -out /tmp/mono-private.pem
openssl ec -in /tmp/mono-private.pem -pubout -out /tmp/mono-public.pem >/dev/null 2>&1
base64 -w0 /tmp/mono-public.pem > "$STORAGE/codecart/monobank_payment/pubkey.txt"
BODY='{"invoiceId":"qa-invoice","status":"success","modifiedDate":"2026-09-26T18:00:00Z"}'
printf '%s' "$BODY" | openssl dgst -sha256 -sign /tmp/mono-private.pem -binary | base64 -w0 >/tmp/mono.sig
CODE="$(curl -sS -o /tmp/mono.out -w '%{http_code}' -H "X-Sign: $(cat /tmp/mono.sig)" -H 'Content-Type: application/json' --data "$BODY" "$BASE/index.php?route=extension/payment/monobank_modern/webhook")"
test "$CODE" = "200"
test "$(cat /tmp/mono.out)" = "OK"
test "$(mysql -h127.0.0.1 -uroot -proot -N codecart -e "SELECT status FROM oc_monobank_modern_invoice WHERE invoice_id='qa-invoice'")" = "success"
HIST1="$(mysql -h127.0.0.1 -uroot -proot -N codecart -e "SELECT COUNT(*) FROM oc_order_history WHERE order_id=$ORDER_ID")"
OLD='{"invoiceId":"qa-invoice","status":"processing","modifiedDate":"2026-09-26T17:00:00Z"}'
printf '%s' "$OLD" | openssl dgst -sha256 -sign /tmp/mono-private.pem -binary | base64 -w0 >/tmp/mono-old.sig
test "$(curl -sS -o /tmp/mono-old.out -w '%{http_code}' -H "X-Sign: $(cat /tmp/mono-old.sig)" -H 'Content-Type: application/json' --data "$OLD" "$BASE/index.php?route=extension/payment/monobank_modern/webhook")" = "200"
test "$(mysql -h127.0.0.1 -uroot -proot -N codecart -e "SELECT status FROM oc_monobank_modern_invoice WHERE invoice_id='qa-invoice'")" = "success"
test "$(mysql -h127.0.0.1 -uroot -proot -N codecart -e "SELECT COUNT(*) FROM oc_order_history WHERE order_id=$ORDER_ID")" = "$HIST1"
echo "Monobank signed webhook PASS"
