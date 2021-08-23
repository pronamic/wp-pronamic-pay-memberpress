<?php
/**
 * Apple Pay gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2021 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * WordPress pay MemberPress Apple Pay gateway
 *
 * @author  Reüel van der Steege
 * @version 2.3.0
 * @since   2.3.0
 */
class ApplePayGateway extends Gateway {
	/**
	 * Constructs and initialize Apple Pay gateway.
	 */
	public function __construct() {
		parent::__construct( 'MeprApplePayGateway', PaymentMethods::APPLE_PAY );

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
