<?php
namespace Cart;
class Currency {
	private $db;
	private $language;
	private $currencies = array();
	private $symbol_left_space = false;
	private $symbol_right_space = false;
	private $trim_zero_decimals = false;

	public function __construct($registry) {
		$this->db = $registry->get('db');
		$this->language = $registry->get('language');
		$this->symbol_left_space = (bool)$registry->get('config')->get('config_symbol_left_space');
		$this->symbol_right_space = (bool)$registry->get('config')->get('config_symbol_right_space');
		$this->trim_zero_decimals = (bool)$registry->get('config')->get('config_currency_trim_zeros');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "currency");

		foreach ($query->rows as $result) {
			$this->currencies[$result['code']] = array(
				'currency_id'   => $result['currency_id'],
				'title'         => $result['title'],
				'symbol_left'   => $result['symbol_left'],
				'symbol_right'  => $result['symbol_right'],
				'decimal_place' => $result['decimal_place'],
				'value'         => \CodeCart\Core\Money::rate($result['value'])
			);
		}
	}

	public function format($number, $currency, $value = '', $format = true) {
		$symbol_left = $this->currencies[$currency]['symbol_left'];
		$symbol_right = $this->currencies[$currency]['symbol_right'];
		$decimal_place = $this->currencies[$currency]['decimal_place'];

		if (!$value) {
			$value = $this->currencies[$currency]['value'];
		}

		$amount = $value ? \CodeCart\Core\Money::multiply($number, $value, 8) : \CodeCart\Core\Money::normalize($number, 8);
		$amount = \CodeCart\Core\Money::decimal($amount, (int)$decimal_place);
		
		if (!$format) {
			return $amount;
		}

		$string = '';

		if ($symbol_left) {
			$string .= $symbol_left;
			if ($this->symbol_left_space) {
				$string .= ' ';
			}
		}

		$decimal_point = (string)$this->language->get('decimal_point');
		$number = number_format($amount, (int)$decimal_place, $decimal_point, $this->language->get('thousand_point'));
		if ($this->trim_zero_decimals && (int)$decimal_place > 0) {
			$number = preg_replace('/' . preg_quote($decimal_point, '/') . '0+$/u', '', $number);
		}
		$string .= $number;

		if ($symbol_right) {
			if ($this->symbol_right_space) {
				$string .= ' ';
			}
			$string .= $symbol_right;
		}

		return $string;
	}

	public function convert($value, $from, $to) {
		if (isset($this->currencies[$from])) {
			$from = $this->currencies[$from]['value'];
		} else {
			$from = 1;
		}

		if (isset($this->currencies[$to])) {
			$to = $this->currencies[$to]['value'];
		} else {
			$to = 1;
		}

		$ratio = \CodeCart\Core\Money::divide($to, $from, \CodeCart\Core\Money::RATE_SCALE);
		return \CodeCart\Core\Money::decimal(\CodeCart\Core\Money::multiply($value, $ratio));
	}
	
	public function getId($currency) {
		if (isset($this->currencies[$currency])) {
			return $this->currencies[$currency]['currency_id'];
		} else {
			return 0;
		}
	}

	public function getSymbolLeft($currency) {
		if (isset($this->currencies[$currency])) {
			return $this->currencies[$currency]['symbol_left'];
		} else {
			return '';
		}
	}

	public function getSymbolRight($currency) {
		if (isset($this->currencies[$currency])) {
			return $this->currencies[$currency]['symbol_right'];
		} else {
			return '';
		}
	}

	public function getDecimalPlace($currency) {
		if (isset($this->currencies[$currency])) {
			return $this->currencies[$currency]['decimal_place'];
		} else {
			return 0;
		}
	}

	public function getValue($currency) {
		if (isset($this->currencies[$currency])) {
			return $this->currencies[$currency]['value'];
		} else {
			return 0;
		}
	}

	public function has($currency) {
		return isset($this->currencies[$currency]);
	}
}
