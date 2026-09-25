<?php
namespace Cart;
class Cart {
	private $data = array();
	private $config;
	private $customer;
	private $session;
	private $db;
	private $tax;
	private $weight;
	private $productsCache = null;

	public function __construct($registry) {
		$this->config = $registry->get('config');
		$this->customer = $registry->get('customer');
		$this->session = $registry->get('session');
		$this->db = $registry->get('db');
		$this->tax = $registry->get('tax');
		$this->weight = $registry->get('weight');

		if ($this->customer->getId()) {
			// We want to change the session ID on all the old items in the customers cart
			$this->db->query("UPDATE " . DB_PREFIX . "cart SET session_id = '" . $this->db->escape($this->session->getId()) . "', date_added = NOW() WHERE api_id = '0' AND customer_id = '" . (int)$this->customer->getId() . "'");

			// Once the customer is logged in we want to update the customers cart
			$cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cart WHERE api_id = '0' AND customer_id = '0' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");

			foreach ($cart_query->rows as $cart) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE cart_id = '" . (int)$cart['cart_id'] . "'");

				// The advantage of using $this->add is that it will check if the products already exist and increaser the quantity if necessary.
				$this->add($cart['product_id'], $cart['quantity'], $this->decodeOptions($cart['option']), $cart['recurring_id']);
			}
		}
	}

	public function getProducts() {
		if ($this->productsCache !== null) {
			return $this->productsCache;
		}

		$api_id = isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0;
		$customer_id = (int)$this->customer->getId();
		$session_id = $this->db->escape($this->session->getId());
		$language_id = (int)$this->config->get('config_language_id');
		$store_id = (int)$this->config->get('config_store_id');
		$customer_group_id = (int)$this->config->get('config_customer_group_id');

		$cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cart WHERE api_id = '" . $api_id . "' AND customer_id = '" . $customer_id . "' AND session_id = '" . $session_id . "'");
		if (!$cart_query->rows) {
			$this->productsCache = array();
			return $this->productsCache;
		}

		$product_ids = array();
		$product_option_ids = array();
		$product_option_value_ids = array();
		$discount_quantities = array();
		$decoded_options = array();
		$recurring_pairs = array();
		foreach ($cart_query->rows as $cart) {
			$product_id = (int)$cart['product_id'];
			$product_ids[$product_id] = $product_id;
			$discount_quantities[$product_id] = isset($discount_quantities[$product_id]) ? $discount_quantities[$product_id] + (int)$cart['quantity'] : (int)$cart['quantity'];
			$options = $this->decodeOptions($cart['option']);
			$decoded_options[(int)$cart['cart_id']] = $options;
			foreach ($options as $product_option_id => $value) {
				$product_option_ids[(int)$product_option_id] = (int)$product_option_id;
				if (is_array($value)) {
					foreach ($value as $product_option_value_id) { $product_option_value_ids[(int)$product_option_value_id] = (int)$product_option_value_id; }
				} elseif ((int)$value > 0) {
					$product_option_value_ids[(int)$value] = (int)$value;
				}
			}
			if ((int)$cart['recurring_id'] > 0) {
				$recurring_pairs[$product_id . ':' . (int)$cart['recurring_id']] = array($product_id, (int)$cart['recurring_id']);
			}
		}
		$product_id_list = implode(',', array_values($product_ids));

		$product_map = array();
		$product_query = $this->db->query("SELECT p.*, pd.name, p2s.store_id FROM " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id=p2s.product_id AND p2s.store_id='" . $store_id . "') INNER JOIN " . DB_PREFIX . "product_description pd ON (p.product_id=pd.product_id AND pd.language_id='" . $language_id . "') WHERE p.product_id IN (" . $product_id_list . ") AND p.date_available <= NOW() AND p.status='1'");
		foreach ($product_query->rows as $row) { $product_map[(int)$row['product_id']] = $row; }

		$option_map = array();
		if ($product_option_ids) {
			$q = $this->db->query("SELECT po.product_option_id, po.product_id, po.option_id, od.name, o.type FROM " . DB_PREFIX . "product_option po INNER JOIN `" . DB_PREFIX . "option` o ON (po.option_id=o.option_id) INNER JOIN " . DB_PREFIX . "option_description od ON (o.option_id=od.option_id AND od.language_id='" . $language_id . "') WHERE po.product_option_id IN (" . implode(',', array_values($product_option_ids)) . ")");
			foreach ($q->rows as $row) { $option_map[(int)$row['product_option_id']] = $row; }
		}

		$option_value_map = array();
		if ($product_option_value_ids) {
			$q = $this->db->query("SELECT pov.product_option_value_id, pov.product_option_id, pov.option_value_id, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix, ovd.name FROM " . DB_PREFIX . "product_option_value pov INNER JOIN " . DB_PREFIX . "option_value_description ovd ON (pov.option_value_id=ovd.option_value_id AND ovd.language_id='" . $language_id . "') WHERE pov.product_option_value_id IN (" . implode(',', array_values($product_option_value_ids)) . ")");
			foreach ($q->rows as $row) { $option_value_map[(int)$row['product_option_value_id']] = $row; }
		}

		$discount_rows = array();
		$q = $this->db->query("SELECT product_id, quantity, priority, price FROM " . DB_PREFIX . "product_discount WHERE product_id IN (" . $product_id_list . ") AND customer_group_id='" . $customer_group_id . "' AND quantity > 0 AND date_start < NOW() AND (date_end < '1000-01-01' OR date_end > NOW()) ORDER BY product_id, quantity DESC, priority ASC, price ASC");
		foreach ($q->rows as $row) { $discount_rows[(int)$row['product_id']][] = $row; }

		$special_map = array();
		$q = $this->db->query("SELECT product_id, price FROM " . DB_PREFIX . "product_special WHERE product_id IN (" . $product_id_list . ") AND customer_group_id='" . $customer_group_id . "' AND date_start < NOW() AND (date_end < '1000-01-01' OR date_end > NOW()) ORDER BY product_id, priority ASC, price ASC");
		foreach ($q->rows as $row) { if (!isset($special_map[(int)$row['product_id']])) { $special_map[(int)$row['product_id']] = $row['price']; } }

		$reward_map = array();
		$q = $this->db->query("SELECT product_id, points FROM " . DB_PREFIX . "product_reward WHERE product_id IN (" . $product_id_list . ") AND customer_group_id='" . $customer_group_id . "'");
		foreach ($q->rows as $row) { $reward_map[(int)$row['product_id']] = (int)$row['points']; }

		$download_map = array();
		$q = $this->db->query("SELECT p2d.product_id, d.download_id, dd.name, d.filename, d.mask FROM " . DB_PREFIX . "product_to_download p2d INNER JOIN " . DB_PREFIX . "download d ON (p2d.download_id=d.download_id) INNER JOIN " . DB_PREFIX . "download_description dd ON (d.download_id=dd.download_id AND dd.language_id='" . $language_id . "') WHERE p2d.product_id IN (" . $product_id_list . ")");
		foreach ($q->rows as $row) { $download_map[(int)$row['product_id']][] = $row; }

		$recurring_map = array();
		if ($recurring_pairs) {
			$parts = array();
			foreach ($recurring_pairs as $pair) { $parts[] = "(pr.product_id='" . (int)$pair[0] . "' AND pr.recurring_id='" . (int)$pair[1] . "')"; }
			$q = $this->db->query("SELECT pr.product_id, r.recurring_id, rd.name, r.frequency, r.price, r.cycle, r.duration, r.trial_status, r.trial_frequency, r.trial_price, r.trial_cycle, r.trial_duration FROM " . DB_PREFIX . "product_recurring pr INNER JOIN " . DB_PREFIX . "recurring r ON (pr.recurring_id=r.recurring_id AND r.status=1) INNER JOIN " . DB_PREFIX . "recurring_description rd ON (r.recurring_id=rd.recurring_id AND rd.language_id='" . $language_id . "') WHERE pr.customer_group_id='" . $customer_group_id . "' AND (" . implode(' OR ', $parts) . ")");
			foreach ($q->rows as $row) { $recurring_map[(int)$row['product_id'] . ':' . (int)$row['recurring_id']] = $row; }
		}

		$product_data = array();
		$remove_ids = array();
		foreach ($cart_query->rows as $cart) {
			$product_id = (int)$cart['product_id'];
			if (!isset($product_map[$product_id]) || (int)$cart['quantity'] <= 0) { $remove_ids[] = (int)$cart['cart_id']; continue; }
			$product = $product_map[$product_id];
			$stock = true; $option_price = 0; $option_price_equal = null; $option_points = 0; $option_weight = 0; $option_data = array();

			foreach (isset($decoded_options[(int)$cart['cart_id']]) ? $decoded_options[(int)$cart['cart_id']] : array() as $product_option_id => $value) {
				$product_option_id = (int)$product_option_id;
				if (!isset($option_map[$product_option_id]) || (int)$option_map[$product_option_id]['product_id'] !== $product_id) { continue; }
				$option = $option_map[$product_option_id];
				$values = is_array($value) ? $value : array($value);
				if (in_array($option['type'], array('select','radio','checkbox'), true)) {
					foreach ($values as $product_option_value_id) {
						$product_option_value_id = (int)$product_option_value_id;
						if (!$product_option_value_id || !isset($option_value_map[$product_option_value_id])) { continue; }
						$ov = $option_value_map[$product_option_value_id];
						if ((int)$ov['product_option_id'] !== $product_option_id) { continue; }
						if ($ov['price_prefix'] === '+') { $option_price += (float)$ov['price']; } elseif ($ov['price_prefix'] === '-') { $option_price -= (float)$ov['price']; } elseif ($ov['price_prefix'] === '=' && in_array($option['type'], array('select','radio'), true)) { $option_price_equal = (float)$ov['price']; }
						if ($ov['points_prefix'] === '+') { $option_points += (int)$ov['points']; } elseif ($ov['points_prefix'] === '-') { $option_points -= (int)$ov['points']; }
						if ($ov['weight_prefix'] === '+') { $option_weight += (float)$ov['weight']; } elseif ($ov['weight_prefix'] === '-') { $option_weight -= (float)$ov['weight']; }
						if ((int)$ov['subtract'] && ((int)$ov['quantity'] < (int)$cart['quantity'])) { $stock = false; }
						$option_data[] = array('product_option_id'=>$product_option_id,'product_option_value_id'=>$product_option_value_id,'option_id'=>$option['option_id'],'option_value_id'=>$ov['option_value_id'],'name'=>$option['name'],'value'=>$ov['name'],'type'=>$option['type'],'quantity'=>$ov['quantity'],'subtract'=>$ov['subtract'],'price'=>$ov['price'],'price_prefix'=>$ov['price_prefix'],'points'=>$ov['points'],'points_prefix'=>$ov['points_prefix'],'weight'=>$ov['weight'],'weight_prefix'=>$ov['weight_prefix']);
					}
				} elseif (in_array($option['type'], array('text','textarea','file','date','datetime','time'), true)) {
					$option_data[] = array('product_option_id'=>$product_option_id,'product_option_value_id'=>'','option_id'=>$option['option_id'],'option_value_id'=>'','name'=>$option['name'],'value'=>$value,'type'=>$option['type'],'quantity'=>'','subtract'=>'','price'=>'','price_prefix'=>'','points'=>'','points_prefix'=>'','weight'=>'','weight_prefix'=>'');
				}
			}

			$price = (float)$product['price'];
			if (!empty($discount_rows[$product_id])) {
				foreach ($discount_rows[$product_id] as $discount) { if ((int)$discount['quantity'] <= (int)$discount_quantities[$product_id]) { $price = (float)$discount['price']; break; } }
			}
			if (isset($special_map[$product_id])) { $price = (float)$special_map[$product_id]; }
			$reward = isset($reward_map[$product_id]) ? $reward_map[$product_id] : 0;
			$download_data = array();
			foreach (isset($download_map[$product_id]) ? $download_map[$product_id] : array() as $download) { $download_data[] = array('download_id'=>$download['download_id'],'name'=>$download['name'],'filename'=>$download['filename'],'mask'=>$download['mask']); }

			$recurring = false;
			$recurring_key = $product_id . ':' . (int)$cart['recurring_id'];
			if ((int)$cart['recurring_id'] > 0 && isset($recurring_map[$recurring_key])) {
				$r = $recurring_map[$recurring_key];
				$recurring = array('recurring_id'=>$r['recurring_id'],'name'=>$r['name'],'frequency'=>$r['frequency'],'price'=>$r['price'],'cycle'=>$r['cycle'],'duration'=>$r['duration'],'trial'=>$r['trial_status'],'trial_frequency'=>$r['trial_frequency'],'trial_price'=>$r['trial_price'],'trial_cycle'=>$r['trial_cycle'],'trial_duration'=>$r['trial_duration']);
			}

			if ((int)$product['quantity'] < (int)$cart['quantity']) { $stock = false; }
			$unit_price = $option_price_equal !== null ? $option_price_equal : ($price + $option_price);
			$unit_price = \CodeCart\Core\Money::decimal($unit_price);
			$line_total = \CodeCart\Core\Money::decimal(\CodeCart\Core\Money::multiply($unit_price, (int)$cart['quantity']));
			$product_data[] = array('cart_id'=>$cart['cart_id'],'product_id'=>$product['product_id'],'name'=>$product['name'],'model'=>$product['model'],'sku'=>$product['sku'],'shipping'=>$product['shipping'],'image'=>$product['image'],'option'=>$option_data,'download'=>$download_data,'quantity'=>$cart['quantity'],'minimum'=>$product['minimum'],'subtract'=>$product['subtract'],'stock'=>$stock,'price'=>$unit_price,'total'=>$line_total,'reward'=>$reward*(int)$cart['quantity'],'points'=>($product['points'] ? ((int)$product['points']+$option_points)*(int)$cart['quantity'] : 0),'tax_class_id'=>$product['tax_class_id'],'weight'=>((float)$product['weight']+$option_weight)*(int)$cart['quantity'],'weight_class_id'=>$product['weight_class_id'],'length'=>$product['length'],'width'=>$product['width'],'height'=>$product['height'],'length_class_id'=>$product['length_class_id'],'recurring'=>$recurring);
		}
		if ($remove_ids) { $this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE cart_id IN (" . implode(',', array_map('intval',$remove_ids)) . ") AND api_id='" . $api_id . "' AND customer_id='" . $customer_id . "' AND session_id='" . $session_id . "'"); }
		$this->productsCache = $product_data;
		return $this->productsCache;
	}
	private function decodeOptions($value) {
		$options = json_decode((string)$value, true);
		return is_array($options) ? $options : array();
	}

	public function add($product_id, $quantity = 1, $option = array(), $recurring_id = 0) {
		$this->productsCache = null;
		$option = is_array($option) ? $option : array();
		$option_json = json_encode($option, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($option_json === false) { $option_json = '{}'; }

		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "cart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "' AND product_id = '" . (int)$product_id . "' AND recurring_id = '" . (int)$recurring_id . "' AND `option` = '" . $this->db->escape($option_json) . "'");

		if (!$query->row['total']) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "cart SET api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "', customer_id = '" . (int)$this->customer->getId() . "', session_id = '" . $this->db->escape($this->session->getId()) . "', product_id = '" . (int)$product_id . "', recurring_id = '" . (int)$recurring_id . "', `option` = '" . $this->db->escape($option_json) . "', quantity = '" . (int)$quantity . "', date_added = NOW()");
		} else {
			$this->db->query("UPDATE " . DB_PREFIX . "cart SET quantity = (quantity + " . (int)$quantity . "), date_added = NOW() WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "' AND product_id = '" . (int)$product_id . "' AND recurring_id = '" . (int)$recurring_id . "' AND `option` = '" . $this->db->escape($option_json) . "'");
		}
	}

	public function update($cart_id, $quantity) {
		$this->productsCache = null;
		$this->db->query("UPDATE " . DB_PREFIX . "cart SET quantity = '" . (int)$quantity . "', date_added = NOW() WHERE cart_id = '" . (int)$cart_id . "' AND api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
	}

	public function remove($cart_id) {
		$this->productsCache = null;
		$this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE cart_id = '" . (int)$cart_id . "' AND api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
	}

	public function clear() {
		$this->productsCache = null;
		$this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
	}

	public function getRecurringProducts() {
		$product_data = array();

		foreach ($this->getProducts() as $value) {
			if ($value['recurring']) {
				$product_data[] = $value;
			}
		}

		return $product_data;
	}

	public function getWeight() {
		$weight = 0;

		foreach ($this->getProducts() as $product) {
			if ($product['shipping']) {
				$weight += $this->weight->convert($product['weight'], $product['weight_class_id'], $this->config->get('config_weight_class_id'));
			}
		}

		return $weight;
	}

	public function getSubTotal() {
		$total = '0.0000';

		foreach ($this->getProducts() as $product) {
			$total = \CodeCart\Core\Money::add($total, $product['total']);
		}

		return \CodeCart\Core\Money::decimal($total);
	}

	public function getTaxes() {
		$tax_data = array();

		foreach ($this->getProducts() as $product) {
			if ($product['tax_class_id']) {
				$tax_rates = $this->tax->getRates($product['price'], $product['tax_class_id']);

				foreach ($tax_rates as $tax_rate) {
					$tax_line = \CodeCart\Core\Money::multiply($tax_rate['amount'], (int)$product['quantity']);
					if (!isset($tax_data[$tax_rate['tax_rate_id']])) {
						$tax_data[$tax_rate['tax_rate_id']] = \CodeCart\Core\Money::decimal($tax_line);
					} else {
						$tax_data[$tax_rate['tax_rate_id']] = \CodeCart\Core\Money::decimal(\CodeCart\Core\Money::add($tax_data[$tax_rate['tax_rate_id']], $tax_line));
					}
				}
			}
		}

		return $tax_data;
	}

	public function getTotal() {
		$total = '0.0000';

		foreach ($this->getProducts() as $product) {
			$line = \CodeCart\Core\Money::multiply($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), (int)$product['quantity']);
			$total = \CodeCart\Core\Money::add($total, $line);
		}

		return \CodeCart\Core\Money::decimal($total);
	}

	public function countProducts() {
		$product_total = 0;

		$products = $this->getProducts();

		foreach ($products as $product) {
			$product_total += $product['quantity'];
		}

		return $product_total;
	}

	public function hasProducts() {
		return count($this->getProducts());
	}

	public function hasRecurringProducts() {
		return count($this->getRecurringProducts());
	}

	public function hasStock() {
		foreach ($this->getProducts() as $product) {
			if (!$product['stock']) {
				return false;
			}
		}

		return true;
	}

	public function hasShipping() {
		foreach ($this->getProducts() as $product) {
			if ($product['shipping']) {
				return true;
			}
		}

		return false;
	}

	public function hasDownload() {
		foreach ($this->getProducts() as $product) {
			if ($product['download']) {
				return true;
			}
		}

		return false;
	}
}
