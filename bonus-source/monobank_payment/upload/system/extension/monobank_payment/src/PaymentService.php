<?php
namespace CodeCart\Extension\MonobankPayment;

final class PaymentService {
    private $registry;
    private $db;
    private $config;
    private $log;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
        $this->log = $registry->get('log');
    }

    public function ensureRegistered(): void {
        if (!class_exists(__NAMESPACE__ . '\\ApiClient', false)) {
            try { (new \CodeCart\Core\ModernExtensionRegistry($this->registry))->refresh(); } catch (\Throwable $e) {}
        }
    }

    public function api(): ApiClient {
        return new ApiClient((string)$this->config->get('payment_monobank_modern_token'));
    }

    public function createInvoice(array $order, $redirectUrl, $webhookUrl): array {
        $orderId = (int)$order['order_id'];
        $currency = isset($order['currency_code']) ? strtoupper((string)$order['currency_code']) : '';
        if ($currency !== 'UAH') { throw new \RuntimeException('Monobank module supports UAH orders only.'); }
        $currencyValue = isset($order['currency_value']) ? (float)$order['currency_value'] : 1.0;
        if ($currencyValue <= 0) { $currencyValue = 1.0; }
        $amount = (int)round(((float)$order['total'] * $currencyValue) * 100, 0, PHP_ROUND_HALF_UP);
        if ($amount < 1) { throw new \RuntimeException('Order total must be greater than zero.'); }

        $lockName = 'cc_mono_' . $orderId;
        $lock = $this->db->query("SELECT GET_LOCK('" . $this->db->escape($lockName) . "', 10) AS acquired");
        if (!$lock->num_rows || (int)$lock->row['acquired'] !== 1) { throw new \RuntimeException('Payment request is already being processed.'); }
        try {
            $existing = $this->db->query("SELECT invoice_id,page_url FROM `" . DB_PREFIX . "monobank_modern_invoice` WHERE order_id='" . $orderId . "' LIMIT 1");
            if ($existing->num_rows && !empty($existing->row['invoice_id']) && !empty($existing->row['page_url'])) {
                return array('invoiceId'=>(string)$existing->row['invoice_id'],'pageUrl'=>(string)$existing->row['page_url'],'reused'=>true);
            }

            $reference = 'CC-' . $orderId . '-' . substr(hash('sha256', $orderId . '|' . (string)$order['date_added']), 0, 12);
            $email = isset($order['email']) ? trim((string)$order['email']) : '';
            $payload = array(
                'amount' => $amount,
                'ccy' => 980,
                'merchantPaymInfo' => array(
                    'reference' => $reference,
                    'destination' => 'CodeCart order #' . $orderId,
                    'comment' => 'Order #' . $orderId,
                    'customerEmails' => $email !== '' ? array($email) : array()
                ),
                'redirectUrl' => (string)$redirectUrl,
                'webHookUrl' => (string)$webhookUrl,
                'validity' => 86400,
                'paymentType' => 'debit'
            );
            $result = $this->api()->createInvoice($payload);
            if (empty($result['invoiceId']) || empty($result['pageUrl'])) { throw new \RuntimeException('Monobank did not return invoiceId/pageUrl.'); }
            $this->saveInvoice($orderId, (string)$result['invoiceId'], (string)$result['pageUrl'], $reference, $amount, 'created', '');
            return $result;
        } finally {
            try { $this->db->query("SELECT RELEASE_LOCK('" . $this->db->escape($lockName) . "')"); } catch (\Throwable $ignored) {}
        }
    }

    public function verifyWebhook($body, $signature): bool {
        $verifier = new WebhookVerifier();
        $key = $this->loadPublicKey(false);
        if ($key !== '' && $verifier->verify($body, $signature, $key)) { return true; }
        $key = $this->loadPublicKey(true);
        return $key !== '' && $verifier->verify($body, $signature, $key);
    }

    public function processWebhook(array $payload): array {
        $invoiceId = isset($payload['invoiceId']) ? trim((string)$payload['invoiceId']) : '';
        $status = isset($payload['status']) ? trim((string)$payload['status']) : '';
        $modified = isset($payload['modifiedDate']) ? trim((string)$payload['modifiedDate']) : '';
        if ($invoiceId === '' || $status === '') { throw new \RuntimeException('Invalid Monobank webhook payload.'); }
        $this->db->beginTransaction();
        try {
            $q = $this->db->query("SELECT * FROM `" . DB_PREFIX . "monobank_modern_invoice` WHERE invoice_id = '" . $this->db->escape($invoiceId) . "' FOR UPDATE");
            if (!$q->num_rows) { $this->db->rollback(); return array('handled'=>false,'reason'=>'invoice_not_found'); }
            $row = $q->row;
            if ($modified !== '' && !empty($row['modified_date'])) {
                $incomingTs = strtotime($modified);
                $storedTs = strtotime((string)$row['modified_date']);
                if ($incomingTs !== false && $storedTs !== false && $incomingTs <= $storedTs) {
                    $this->db->commit(); return array('handled'=>true,'stale'=>true,'order_id'=>(int)$row['order_id']);
                }
            }
            $this->db->query("UPDATE `" . DB_PREFIX . "monobank_modern_invoice` SET status = '" . $this->db->escape($status) . "', modified_date = '" . $this->db->escape($modified) . "', date_modified = NOW() WHERE invoice_id = '" . $this->db->escape($invoiceId) . "'");
            $this->db->commit();
            return array('handled'=>true,'stale'=>false,'order_id'=>(int)$row['order_id'],'status'=>$status,'invoice_id'=>$invoiceId,'modified_date'=>$modified);
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function saveInvoice($orderId, $invoiceId, $pageUrl, $reference, $amount, $status, $modified): void {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "monobank_modern_invoice` SET order_id='" . (int)$orderId . "', invoice_id='" . $this->db->escape($invoiceId) . "', page_url='" . $this->db->escape($pageUrl) . "', reference='" . $this->db->escape($reference) . "', amount='" . (int)$amount . "', currency='UAH', status='" . $this->db->escape($status) . "', modified_date='" . $this->db->escape($modified) . "', date_added=NOW(), date_modified=NOW() ON DUPLICATE KEY UPDATE invoice_id=VALUES(invoice_id), page_url=VALUES(page_url), reference=VALUES(reference), amount=VALUES(amount), status=VALUES(status), date_modified=NOW()");
    }

    private function loadPublicKey($refresh): string {
        $dir = rtrim(DIR_STORAGE, '/\\') . '/codecart/monobank_payment';
        $file = $dir . '/pubkey.txt';
        if (!$refresh && is_file($file)) {
            $value = trim((string)file_get_contents($file));
            if ($value !== '') { return $value; }
        }
        try { $value = trim($this->api()->publicKey()); } catch (\Throwable $e) { $this->safeLog('pubkey_error', $e->getMessage()); return ''; }
        if ($value !== '') {
            if (!is_dir($dir)) { @mkdir($dir, 0750, true); }
            $tmp = $file . '.tmp.' . bin2hex(random_bytes(4));
            if (@file_put_contents($tmp, $value, LOCK_EX) !== false) { @rename($tmp, $file); }
        }
        return $value;
    }

    public function safeLog($operation, $message): void {
        if (!$this->config->get('payment_monobank_modern_debug') || !$this->log) { return; }
        $message = preg_replace('/[A-Za-z0-9_\-]{30,}/', '[masked]', (string)$message);
        $this->log->write('MonobankModern ' . preg_replace('/[^a-z0-9_.-]/i', '', (string)$operation) . ': ' . $message);
    }
}
