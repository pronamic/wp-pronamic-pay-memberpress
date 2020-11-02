<?php
/**
 * Przelewy24 gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2020 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * WordPress pay MemberPress Przelewy24 gateway
 *
 * @author  Reüel van der Steege
 * @version 2.2.0
 * @since   2.2.0
 */
class Przelewy24Gateway extends Gateway {
	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::PRZELEWY24;

	/**
	 * Get alias class name of this gateway.
	 *
	 * @return string
	 */
	public function get_alias() {
		return 'MeprPrzelewy24Gateway';
	}
}
