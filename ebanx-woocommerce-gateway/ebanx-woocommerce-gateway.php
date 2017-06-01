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
	 * The singleton acessor.
	 *
	 * @return WC_EBANX
	 */
	public static function get_instance()
	{
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->ebanx = EBANX(new Config());

		add_filter( 'woocommerce_payment_gateways', 'add_gateways' );

		add_action( 'admin_notices', function() {
			echo '<div class="notice notice-info"><p>WE HAVE A PROJECT SEED!</p></div>';
			echo '<div class="notice notice-info"><p>This project uses our brand new lib: '.get_class($this->ebanx).'</p></div>';
		} );
	}

	/**
	 * Add the gateways to WooCommerce.
	 *
	 * @param  array $methods WooCommerce payment methods.
	 * @return array
	 */
	public function add_gateways($methods)
	{
		// Sample
		//$methods[] = 'Ebanx\WooCommerce\Gateways\Something';
		return $methods;
	}
}

add_action('plugins_loaded', array('Ebanx\WooCommerce\WC_EBANX', 'get_instance'));
