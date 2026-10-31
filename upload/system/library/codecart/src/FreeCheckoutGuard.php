<?php
namespace CodeCart\Core;

/**
 * Revalidate the pending zero-value order at the Free Checkout confirmation boundary.
 * Only this payment method uses the guard; other payment and total models are untouched.
 */
final class FreeCheckoutGuard {
    private $registry;

    public function __construct($registry) {
        $this->registry = $registry;
    }

    public function assertCurrentOrder(array $order, $orderModel) {
        $config = $this->registry->get('config');
        $session = $this->registry->get('session');
        $cart = $this->registry->get('cart');
        $load = $this->registry->get('load');
        $tax = $this->registry->get('tax');

        if (Money::compare($order['total'], '0') !== 0 || !$config->get('total_total_status')) {
            throw new \RuntimeException('free_checkout_order_total');
        }

        $products = $cart->getProducts();
        $vouchers = isset($session->data['vouchers']) && is_array($session->data['vouchers'])
            ? array_values($session->data['vouchers']) : array();

        if ((!$products && !$vouchers) || (!$config->get('config_stock_checkout') && !$cart->hasStock())
            || $cart->hasRecurringProducts()) {
            throw new \RuntimeException('free_checkout_cart');
        }

        // The selected quote and currency must still describe the pending order.
        if ((string)$order['currency_code'] !== (string)($session->data['currency'] ?? $config->get('config_currency'))) {
            throw new \RuntimeException('free_checkout_currency');
        }
        if ($cart->hasShipping()
            && (!isset($session->data['shipping_method']['code'])
                || (string)$order['shipping_code'] !== (string)$session->data['shipping_method']['code'])) {
            throw new \RuntimeException('free_checkout_shipping');
        }

        $quantities = array();
        foreach ($products as $product) {
            $id = (int)$product['product_id'];
            $quantities[$id] = ($quantities[$id] ?? 0) + (int)$product['quantity'];
        }
        foreach ($products as $product) {
            if ((int)$product['quantity'] <= 0
                || (int)$product['minimum'] > $quantities[(int)$product['product_id']]) {
                throw new \RuntimeException('free_checkout_minimum');
            }
        }

        // Compare line items and options, including quantities and prices. A new cart
        // with the same zero total must not be able to confirm an older order.
        $currentLines = array();
        foreach ($products as $product) {
            $currentLines[] = $this->lineSignature($product, $product['option'],
                $tax->getTax($product['price'], $product['tax_class_id']));
        }
        $savedLines = array();
        foreach ($orderModel->getOrderProducts((int)$order['order_id']) as $product) {
            $options = $orderModel->getOrderOptions((int)$order['order_id'], (int)$product['order_product_id']);
            $savedLines[] = $this->lineSignature($product, $options, $product['tax']);
        }
        sort($currentLines, SORT_STRING);
        sort($savedLines, SORT_STRING);
        if ($currentLines !== $savedLines) {
            throw new \RuntimeException('free_checkout_products_changed');
        }

        $currentVouchers = array();
        foreach ($vouchers as $voucher) {
            $currentVouchers[] = $this->voucherSignature($voucher);
        }
        $savedVouchers = array();
        foreach ($orderModel->getOrderVouchers((int)$order['order_id']) as $voucher) {
            $savedVouchers[] = $this->voucherSignature($voucher);
        }
        sort($currentVouchers, SORT_STRING);
        sort($savedVouchers, SORT_STRING);
        if ($currentVouchers !== $savedVouchers) {
            throw new \RuntimeException('free_checkout_vouchers_changed');
        }

        // Reuse OpenCart's enabled Total extensions and sort order. This preserves
        // discount, reward, voucher, tax and shipping calculations from extensions.
        $totals = array();
        $taxes = $cart->getTaxes();
        $total = 0;
        $totalData = array('totals' => &$totals, 'taxes' => &$taxes, 'total' => &$total);
        $load->model('setting/extension');
        $settingModel = $this->registry->get('model_setting_extension');
        $extensions = $settingModel->getExtensions('total');
        $sortOrder = array();
        foreach ($extensions as $key => $extension) {
            $sortOrder[$key] = (int)$config->get('total_' . $extension['code'] . '_sort_order');
        }
        array_multisort($sortOrder, SORT_ASC, $extensions);

        foreach ($extensions as $extension) {
            $code = $extension['code'];
            if ($config->get('total_' . $code . '_status')) {
                $load->model('extension/total/' . $code);
                $model = $this->registry->get('model_extension_total_' . $code);
                $model->getTotal($totalData);
            }
        }

        if (Money::compare($total, '0') !== 0) {
            throw new \RuntimeException('free_checkout_current_total');
        }

        // Compare individual adjustments, not just the sum. A stale reward amount
        // offset by another discount could otherwise debit the wrong points.
        $currentTotals = array();
        foreach ($totals as $item) {
            $currentTotals[] = $this->totalSignature($item);
        }
        $savedTotals = array();
        foreach ($orderModel->getOrderTotals((int)$order['order_id']) as $item) {
            $savedTotals[] = $this->totalSignature($item);
        }
        sort($currentTotals, SORT_STRING);
        sort($savedTotals, SORT_STRING);
        if ($currentTotals !== $savedTotals) {
            throw new \RuntimeException('free_checkout_adjustments_changed');
        }
    }

    private function lineSignature(array $product, array $options, $tax) {
        $optionParts = array();
        foreach ($options as $option) {
            $optionParts[] = json_encode(array(
                (int)$option['product_option_id'],
                (int)$option['product_option_value_id'],
                (string)$option['name'],
                (string)$option['value'],
                (string)$option['type']
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        sort($optionParts, SORT_STRING);

        return json_encode(array(
            (int)$product['product_id'],
            (int)$product['quantity'],
            (string)$product['name'],
            (string)$product['model'],
            Money::normalize($product['price']),
            Money::normalize($product['total']),
            Money::normalize($tax),
            (int)$product['reward'],
            $optionParts
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function voucherSignature(array $voucher) {
        return json_encode(array(
            Money::normalize($voucher['amount']),
            (int)$voucher['voucher_theme_id'],
            (string)$voucher['from_name'],
            (string)$voucher['from_email'],
            (string)$voucher['to_name'],
            (string)$voucher['to_email'],
            (string)$voucher['message']
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function totalSignature(array $total) {
        return json_encode(array(
            (string)$total['code'],
            (string)$total['title'],
            Money::normalize($total['value']),
            (int)$total['sort_order']
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
