<?php
/**
 * Apple Pay gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2020 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways;

use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Extensions\MemberPress\Pronamic;

/**
 * WordPress pay MemberPress Apple Pay gateway
 *
 * @author  Reüel van der Steege
 * @version 2.3.0
 * @since   2.3.0
 */
class ApplePayGateway extends Gateway {
	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::APPLE_PAY;

	/**
	 * Constructs and initialize Apple Pay gateway.
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
		return 'MeprApplePayGateway';
	}

	/**
	 * Get icon function, please note that this is not a MemberPress function.
	 *
	 * @since 2.0.8
	 * @return string
	 */
	protected function get_icon() {
		return Pronamic::get_method_icon_url( $this->payment_method );
	}
}
