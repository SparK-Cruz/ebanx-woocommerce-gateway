<?php
namespace Ebanx\WooCommerce\Gateways;

class SampleGateway extends BaseGateway {
	private $ebanx_gateway;

	public function __construct() {
		$this->id                 = 'ebanx-sample';
		$this->title       = __('EBANX SAMPLE!', 'ebanx-woocommerce-gateway');
		$this->method_description = __('EBANX sample gateway implementation', 'ebanx-woocommerce-gateway');
	}

	protected function get_remote() {
		if ( ! $this->ebanx_gateway ) {
			$this->ebanx_gateway = $this->get_ebanx()->boleto();
		}

		return $this->ebanx_gateway;
	}

	public function payment_fields() {
		echo wp_kses_post(wpautop(wptexturize($this->method_description)));
	}
}
