<?php
/**
 * Credit card gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2021 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * WordPress pay MemberPress credit card gateway
 *
 * @author  Remco Tolsma
 * @version 2.0.1
 * @since   1.0.0
 */
class CreditCardGateway extends Gateway {
	/**
	 * Constructs and initialize credit card gateway.
	 */
	public function __construct() {
		parent::__construct( 'MeprCreditCardGateway', PaymentMethods::CREDIT_CARD );

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
}
