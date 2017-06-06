<?php
/**
 * Plugin Name: EBANX Payment Gateway for WooCommerce
 * Plugin URI: https://www.ebanx.com/business/en/developers/integrations/extensions-and-plugins/woocommerce-plugin
 * Description: Offer Latin American local payment methods & increase your conversion rates with the solution used by AliExpress, AirBnB and Spotify in Brazil.
 * Author: EBANX
 * Author URI: https://www.ebanx.com/business/en
 * Version: 2.0.0
 * License: MIT
 * Text Domain: woocommerce-gateway-ebanx
 * Domain Path: /languages
 *
 * @package WooCommerce_EBANX
 */
namespace Ebanx\WooCommerce;

if ( ! defined('ABSPATH') ) {
	exit;
}
if ( class_exists('WC_EBANX') ) {
	return;
}

require __DIR__ . '/vendor/autoload.php';

use Ebanx\Benjamin\Facade as Benjamin;
use Ebanx\Benjamin\Models\Configs\Config;

class WC_EBANX {
	const DIR = __DIR__;

	/**
	 * Singleton holder.
	 *
	 * @var WC_EBANX
	 */
	protected static $instance;

	/**
	 * @var Benjamin
	 */
	private $ebanx;

	/**
	 * The singleton acessor.
	 *
	 * @return WC_EBANX
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_filter( 'woocommerce_payment_gateways', array($this, 'add_gateways') );

		$config_gateway = new Gateways\Configuration();

		$this->ebanx = EBANX(new Config(array(
			'baseCurrency' => strtoupper(get_woocommerce_currency()),
			'integration_key' => $config_gateway->get_setting_or_default('live_private_key'),
			'sandbox_integration_key' => $config_gateway->get_setting_or_default('sandbox_private_key'),
		)));
	}

	/**
	 * @return Benjamin
	 */
	public function get_ebanx() {
		return $this->ebanx;
	}

	/**
	 * Add the gateways to WooCommerce.
	 *
	 * @param  array $methods WooCommerce payment methods.
	 * @return array
	 */
	public function add_gateways($methods) {
		if ( is_admin() ) {
			$methods[] = 'Ebanx\WooCommerce\Gateways\Configuration';
			return $methods;
		}

		$methods[] = 'Ebanx\WooCommerce\Gateways\SampleGateway';

		return $methods;
	}
}

add_action('plugins_loaded', array('Ebanx\WooCommerce\WC_EBANX', 'get_instance'));
