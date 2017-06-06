<?php
namespace Ebanx\WooCommerce\Gateways;

if ( ! defined('ABSPATH') ) {
	exit;
}

use Ebanx\WooCommerce\WC_EBANX;

abstract class BaseGateway extends \WC_Payment_Gateway {
	private $ebanx;

	abstract protected function get_remote();

	public function get_ebanx() {
		if ( ! $this->ebanx ) {
			$this->ebanx = WC_EBANX::get_instance()->get_ebanx();
		}

		return $this->ebanx;
	}

	public function is_available() {
		$country = WC()->customer->get_country();
		if (!$country) {
			return false;
		}

		return $this->get_remote()->isAvailableForCountry($country);
	}
}
