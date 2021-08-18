<?php
/**
 * Giropay gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2021 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * WordPress pay MemberPress Giropay gateway
 *
 * @author  Remco Tolsma
 * @version 2.0.1
 * @since   1.0.0
 */
class GiropayGateway extends Gateway {
	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::GIROPAY;

	/**
	 * Get alias class name of this gateway.
	 *
	 * @return string
	 */
	public function get_alias() {
		return 'MeprGiropayGateway';
	}

	/**
	 * Get icon function, please note that this is not a MemberPress function.
	 *
	 * @since 1.0.2
	 * @return string
	 */
	protected function get_icon() {
		return PaymentMethods::get_icon_url( PaymentMethods::GIROPAY );
	}
}
