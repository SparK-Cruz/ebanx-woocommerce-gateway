<?php
namespace Ebanx\WooCommerce\Gateways;

if (!defined('ABSPATH')) {
	exit;
}

use Ebanx\Benjamin\Models\Currency;
use Ebanx\Benjamin\Models\Configs\CreditCardConfig;
use Ebanx\Benjamin\Services\Gateways\CreditCard;

final class Configuration extends \WC_Payment_Gateway {
	/**
	 * Default values
	 *
	 * @var array
	 */
	public static $defaults = array(
		'sandbox_private_key' => '',
		'sandbox_public_key' => '',
		'sandbox_mode_enabled' => 'yes',
		'debug_enabled' => 'yes',
		'brazil_payment_methods' => array(
			'ebanx-credit-card-br',
			'ebanx-banking-ticket',
			'ebanx-tef',
			'ebanx-account',
		),
		'mexico_payment_methods' => array(
			'ebanx-credit-card-mx',
			'ebanx-debit-card',
			'ebanx-oxxo',
		),
		'chile_payment_methods' => array(
			'ebanx-sencillito',
			'ebanx-servipag',
		),
		'colombia_payment_methods' => array(
			'ebanx-eft',
			'ebanx-baloto',
		),
		'peru_payment_methods' => array(
			'ebanx-safetypay',
			'ebanx-pagoefectivo',
		),
		'save_card_data' => 'yes',
		'one_click' => 'yes',
		'capture_enabled' => 'yes',
		'credit_card_instalments' => '1',
		'due_date_days' => '3',
		'brazil_taxes_options' => 'cpf',
		'interest_rates_enabled' => 'no',
		'min_instalment_value_brl' => '20',
		'min_instalment_value_usd' => '0',
		'min_instalment_value_eur' => '0',
		'min_instalment_value_mxn' => '100'
	);

	public function __construct() {
		$this->id                 = 'ebanx-config';
		$this->title              = __('EBANX', 'ebanx-woocommerce-gateway');
		$this->method_title       = $this->title;
		$this->method_description = __('EBANX easy-to-setup checkout allows your business to accept local payments in Brazil, Mexico, Colombia, Chile & Peru.', 'ebanx-woocommerce-gateway');

		$this->init_form_fields();
		$this->init_settings();

		$this->enabled = true;

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
	}

	/**
	 * @return boolean This gateway is fake and hidden from customer
	 */
	public function is_available() {
		return false;
	}

	/**
	 * Define the fields on EBANX WooCommerce settings page and set the defaults when the plugin is installed
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$currency_code = strtoupper(get_woocommerce_currency());

		if ( ! in_array($currency_code, Currency::all()) ) {
			return array(
				'bad_title' => array(
					'title' => 'EBANX does not support ' . $currency_code . ', please change your currency.',
					'type'  => 'title',
				),
			);
		}

		$is_currency_global = Currency::isGlobal($currency_code);
		$acquirer_min_instalment_value = 0;

		if (CreditCard::acceptsCurrency($currency_code) && !$is_currency_global) {
			$acquirer_min_instalment_value = CreditCardConfig::acquirerMinInstalmentValueForCurrency($currency_code);
		}

		$fields = array(
			'integration_title' => array(
				'title' => __('Integration', 'woocommerce-gateway-ebanx'),
				'type' => 'title',
				'description' => sprintf(__('You can obtain the integration keys in the settings section, logging in to the <a href="https://www.ebanx.com/business/en/dashboard">EBANX Dashboard.</a>', 'woocommerce-gateway-ebanx'), 'https://google.com'),
			),
			'sandbox_private_key' => array(
				'title'       => __('Sandbox Integration Key', 'woocommerce-gateway-ebanx'),
				'type'        => 'text',
			),
			'sandbox_public_key' => array(
				'title'       => __('Sandbox Public Integration Key', 'woocommerce-gateway-ebanx'),
				'type'        => 'text',
			),
			'live_private_key' => array(
				'title'       => __('Live Integration Key', 'woocommerce-gateway-ebanx'),
				'type'        => 'text',
			),
			'live_public_key' => array(
				'title'       => __('Live Public Integration Key', 'woocommerce-gateway-ebanx'),
				'type'        => 'text',
			),
			'sandbox_mode_enabled' => array(
				'title'       => __('EBANX Sandbox', 'woocommerce-gateway-ebanx'),
				'type'        => 'checkbox',
				'label'       => __('Enable Sandbox Mode', 'woocommerce-gateway-ebanx'),
				'description' => __('EBANX Sandbox is a testing environment that mimics the live environment. Use it to make payment requests to see how your ecommerce processes them.', 'woocommerce-gateway-ebanx'),
				'desc_tip'    => true
			),
			'display_methods_title' => array(
				'title' => __('Enable Payment Methods', 'woocommerce-gateway-ebanx'),
				'type'  => 'title',
				'description' => sprintf(__('Set up payment methods for your checkout. Confirm that method is enabled on your contract.', 'woocommerce-gateway-ebanx'), 'http://google.com')
			),
			'brazil_payment_methods' => array(
				'title'       => __('Brazil', 'woocommerce-gateway-ebanx'),
				'type'        => 'multiselect',
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'ebanx-credit-card-br' => 'Credit Card',
					'ebanx-banking-ticket' => 'Boleto EBANX',
					'ebanx-tef'            => 'Online Banking (TEF)',
					'ebanx-account'        => 'EBANX Wallet',
				),
			),
			'mexico_payment_methods' => array(
				'title'       => __('Mexico', 'woocommerce-gateway-ebanx'),
				'type'        => 'multiselect',
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'ebanx-credit-card-mx' => 'Credit Card',
					'ebanx-debit-card'  => 'Debit Card',
					'ebanx-oxxo'        => 'OXXO',
				),
			),
			'chile_payment_methods' => array(
				'title'       => __('Chile', 'woocommerce-gateway-ebanx'),
				'type'        => 'multiselect',
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'ebanx-sencillito' => 'Sencillito',
					'ebanx-servipag'   => 'Servipag',
				),
			),
			'colombia_payment_methods' => array(
				'title'       => __('Colombia', 'woocommerce-gateway-ebanx'),
				'type'        => 'multiselect',
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'ebanx-eft' => 'PSE - Pago Seguros en Línea (EFT)',
					'ebanx-baloto' => 'Baloto',
				),
			),
			'peru_payment_methods' => array(
				'title'       => __('Peru', 'woocommerce-gateway-ebanx'),
				'type'        => 'multiselect',
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'ebanx-safetypay'    => 'SafetyPay',
					'ebanx-pagoefectivo' => 'PagoEfectivo',
				),
			),
			'payments_options_title' => array(
				'title' => __('Payment Options', 'woocommerce-gateway-ebanx'),
				'type'  => 'title'
			),
			'credit_card_options_title' => array(
				'title' => __('Credit Card', 'woocommerce-gateway-ebanx'),
				'type'  => 'title',
				'class' => 'ebanx-payments-option'
			),
			'save_card_data' => array(
				'title' => __('Save Card Data', 'woocommerce-gateway-ebanx'),
				'type'  => 'checkbox',
				'label' => __('Enable saving card data', 'woocommerce-gateway-ebanx'),
				'description' => __('Allow your customer to save credit card and debit card data for future purchases.', 'woocommerce-gateway-ebanx'),
				'desc_tip' => true,
				'class' => 'ebanx-payments-option'
			),
			'one_click' => array(
				'type'        => 'checkbox',
				'title'       => __('One-Click Payment', 'woocommerce-gateway-ebanx'),
				'label'       => __('Enable one-click-payment', 'woocommerce-gateway-ebanx'),
				'description' => __('Allow your customer to complete payments in one-click using credit cards saved.', 'woocommerce-gateway-ebanx'),
				'desc_tip' => true,
				'class' => 'ebanx-payments-option'
			),
			'capture_enabled' => array(
				'type'    => 'checkbox',
				'title'   => __('Enable Auto-Capture', 'woocommerce-gateway-ebanx'),
				'label'   => __('Capture the payment immediately', 'woocommerce-gateway-ebanx'),
				'description' => __('Automatically capture payments from your customers, just for credit card. Otherwise you will need to capture the payment going to: WooCommerce -> Orders. Not captured payments will be cancelled in 4 days.', 'woocommerce-gateway-ebanx'),
				'desc_tip' => true,
				'class' => 'ebanx-payments-option'
			),
			'credit_card_instalments' => array(
				'title'       => __('Maximum nº of Instalments', 'woocommerce-gateway-ebanx'),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select ebanx-payments-option',
				'options'     => array(
					'1'  => '1',
					'2'  => '2',
					'3'  => '3',
					'4'  => '4',
					'5'  => '5',
					'6'  => '6',
					'7'  => '7',
					'8'  => '8',
					'9'  => '9',
					'10' => '10',
					'11' => '11',
					'12' => '12',
				),
				'description' => __('Establish the maximum number of instalments in which your customer can pay, as consented on your contract.', 'woocommerce-gateway-ebanx'),
				'desc_tip' => true
			)
		);

		if ( CreditCard::acceptsCurrency($currency_code) ) {
			$fields["min_instalment_value_$currency_code"] = array(
				'title' => sprintf(__('Minimum Instalment (%s)', 'woocommerce-gateway-ebanx'), $currency_code),
				'type' => 'number',
				'class' => 'ebanx-payments-option',
				'placeholder' => sprintf(__('The default is %d', 'woocommerce-gateway-ebanx'),
					$acquirer_min_instalment_value ),
				'custom_attributes' => array(
					'min' => $acquirer_min_instalment_value,
					'step' => '0.01'
				),
				'desc_tip' => true,
				'description' => __('Set the minimum instalment value during checkout. The minimum EBANX allows for BRL is 20 and MXN is 100, lower values in these currencies will be ignored.', 'woocommerce-gateway-ebanx')
			);
		}

		$fields = array_merge($fields, array(
			'interest_rates_enabled' => array(
				'type'    => 'checkbox',
				'title'   => __('Interest Rates', 'woocommerce-gateway-ebanx'),
				'label'   => __('Enable Interest Rates', 'woocommerce-gateway-ebanx'),
				'description' => __('Enable and set a custom interest rate for your customers according to the number of Instalments you allow the payment.'),
				'desc_tip' => true,
				'class' => 'ebanx-payments-option'
			)
		));
		$interest_rates_array = array();
		$interest_rates_array['interest_rates_01'] = array(
			'title' => __('1x Interest Rate in %', 'woocommerce-gateway-ebanx'),
			'type' => 'number',
			'custom_attributes' => array(
				'min'  => '0',
				'step' => 'any',
			),
			'class' => 'interest-rates-fields ebanx-payments-option',
			'placeholder' => __('eg: 15.7%', 'woocommerce-gateway-ebanx')
		);

		for ($i=2; $i <= 12; $i++) {
			$interest_rates_array['interest_rates_'.sprintf("%02d", $i)] = array(
				'title' => __($i.'x Interest Rate', 'woocommerce-gateway-ebanx'),
				'type' => 'number',
				'custom_attributes' => array(
					'min'  => '0',
					'step' => 'any',
				),
				'class' => 'interest-rates-fields ebanx-payments-option',
				'placeholder' => __('eg: 15.7%', 'woocommerce-gateway-ebanx')
			);
		}

		$fields = array_merge($fields, $interest_rates_array);

		$fields = array_merge($fields, array(
			'cash_options_title' => array(
				'title' => __('Cash Payments', 'woocommerce-gateway-ebanx'),
				'type'  => 'title',
				'class' => 'ebanx-payments-option'
			),
			'due_date_days' => array(
				'title' => __('Days to Expiration', 'woocommerce-gateway-ebanx'),
				'class' => 'wc-enhanced-select ebanx-due-cash-date ebanx-payments-option',
				'description' => __('Define the maximum number of days on which your customer can complete the payment of: Boleto, OXXO, Sencilito, PagoEfectivo and SafetyPay.', 'woocommerce-gateway-ebanx'),
				'desc_tip' => true
			),
		));

		$fields['due_date_days']['type'] = 'number';
		if ($is_currency_global) {
			$fields['due_date_days']['type'] = 'select';
			$fields['due_date_days']['class'] .= ' wc-enhanced-select';
			$fields['due_date_days']['options'] = array(
				'1' => '1',
				'2' => '2',
				'3' => '3',
			);
		}

		$fields = array_merge($fields, array(
			'advanced_options_title' => array(
				'title' => __('Advanced Options', 'woocommerce-gateway-ebanx'),
				'type' => 'title'
			),
			'brazil_taxes_options' => array(
				'title' => __('Enable Checkout for:', 'woocommerce-gateway-ebanx'),
				'type'        => 'multiselect',
				'required'    => true,
				'class'       => 'wc-enhanced-select ebanx-advanced-option brazil-taxes',
				'options'     => array(
					'cpf' => __('CPF - Individuals', 'woocommerce-gateway-ebanx'),
					'cnpj' => __('CNPJ - Companies', 'woocommerce-gateway-ebanx')
				),
				'default' => array('cpf'),
				'description' => __('In order to process with the EBANX Plugin in Brazil there a few mandatory fields such as CPF identification for individuals and CNPJ for companies.'),
				'desc_tip' => true
			),
			'checkout_manager_enabled' => array(
				'title' => __('Checkout Manager', 'woocommerce-gateway-ebanx'),
				'label' => __('Use my checkout manager fields', 'woocommerce-gateway-ebanx'),
				'type' => 'checkbox',
				'class' => 'ebanx-advanced-option ebanx-advanced-option-enable',
				'description' => __('If you make use of a Checkout Manager, please identify the HTML name attribute of the fields.', 'woocommerce-gateway-ebanx'),
				'desc_tip' => true
			),
			'checkout_manager_brazil_person_type' => array(
				'title' => __('Entity Type Selector', 'woocommerce-gateway-ebanx'),
				'type' => 'text',
				'class' => 'ebanx-advanced-option ebanx-checkout-manager-field cpf_cnpj',
				'placeholder' => __('eg: billing_brazil_entity', 'woocommerce-gateway-ebanx')
			),
			'checkout_manager_cpf_brazil' => array(
				'title' => __('CPF', 'woocommerce-gateway-ebanx'),
				'type' => 'text',
				'class' => 'ebanx-advanced-option ebanx-checkout-manager-field cpf',
				'placeholder' => __('eg: billing_brazil_cpf', 'woocommerce-gateway-ebanx')
			),
			'checkout_manager_birthdate' => array(
				'title' => __('Birthdate Brazil', 'woocommerce-gateway-ebanx'),
				'type' => 'text',
				'class' => 'ebanx-advanced-option ebanx-checkout-manager-field cpf',
				'placeholder' => __('eg: billing_brazil_birth_date', 'woocommerce-gateway-ebanx')
			),
			'checkout_manager_cnpj_brazil' => array(
				'title' => __('CNPJ', 'woocommerce-gateway-ebanx'),
				'type' => 'text',
				'class' => 'ebanx-advanced-option ebanx-checkout-manager-field cnpj',
				'placeholder' => __('eg: billing_brazil_cnpj', 'woocommerce-gateway-ebanx')
			),
			'checkout_manager_chile_document' => array(
				'title' => __('RUT', 'woocommerce-gateway-ebanx'),
				'type' => 'text',
				'class' => 'ebanx-advanced-option ebanx-checkout-manager-field ebanx-chile-document',
				'placeholder' => __('eg: billing_chile_document', 'woocommerce-gateway-ebanx')
			),
			'checkout_manager_chile_birth_date' => array(
				'title' => __('Birthdate Chile', 'woocommerce-gateway-ebanx'),
				'type' => 'text',
				'class' => 'ebanx-advanced-option ebanx-checkout-manager-field ebanx-chile-bdate',
				'placeholder' => __('eg: billing_chile_birth_date', 'woocommerce-gateway-ebanx')
			),
			'checkout_manager_colombia_document' => array(
				'title' => __('DNI', 'woocommerce-gateway-ebanx'),
				'type' => 'text',
				'class' => 'ebanx-advanced-option ebanx-checkout-manager-field ebanx-colombia-document',
				'placeholder' => __('eg: billing_colombia_document', 'woocommerce-gateway-ebanx')
			),
		));

		$this->form_fields = apply_filters('ebanx_settings_form_fields', $fields);

		$this->inject_defaults();
	}

	/**
	 * Inject the default data
	 *
	 * @return void
	 */
	private function inject_defaults(){
		foreach($this->form_fields as $field => &$properties){
			if (!isset(self::$defaults[$field])) {
				continue;
			}

			$properties['default'] = self::$defaults[$field];
		}
	}

	/**
	 * Fetches a single setting from the gateway settings if found, otherwise it returns an optional default value
	 *
	 * @param  string $name    The setting name to fetch
	 * @param  mixed  $default The default value in case setting is not present
	 * @return mixed
	 */
	public function get_setting_or_default($name, $default=null) {
		if ( ! isset($this->settings[$name]) || empty($this->settings[$name])) {
			return $default;
		}

		return $this->settings[$name];
	}
}
