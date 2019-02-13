<?php
/**
 * Credit card gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways;

use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Plugin;

/**
 * WordPress pay MemberPress credit card gateway
 *
 * @author  Remco Tolsma
 * @version 2.0.1
 * @since   1.0.0
 */
class CreditCardGateway extends Gateway {
	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::CREDIT_CARD;

	/**
	 * Constructs and initialize credit card gateway.
	 */
	public function __construct() {
		parent::__construct();

		// Capabilities.
		$this->capabilities = array(
			'process-payments',
			'create-subscriptions',
			'cancel-subscriptions',
			'update-subscriptions',
			'suspend-subscriptions',
			'resume-subscriptions',
			'subscription-trial-payment',
		);
	}

	/**
	 * Get alias class name of this gateway.
	 *
	 * @return string
	 */
	public function get_alias() {
		return 'MeprCreditCardGateway';
	}

	/**
	 * Get icon function, please note that this is not a MemberPress function.
	 *
	 * @since 2.0.8
	 * @return string
	 */
	protected function get_icon() {
		return plugins_url( 'images/credit-card/icon-32x32.png', Plugin::$file );
	}
}
