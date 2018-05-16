<?php
/**
 * Direct Debit mandate via Sofort gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2018 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Plugin;

/**
 * WordPress pay MemberPress Direct Debit mandate via Sofort gateway
 *
 * @author  Reüel van der Steege
 * @version 2.0.0
 * @since   1.0.6
 */
class DirectDebitSofortGateway extends Gateway {
	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::DIRECT_DEBIT_SOFORT;

	/**
	 * Get icon function, please note that this is not a MemberPress function.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	protected function get_icon() {
		return plugins_url( 'images/sepa-sofort/icon-24x24.png', Plugin::$file );
	}

	/**
	 * Get alias class name of this gateway.
	 *
	 * @return string
	 */
	public function get_alias() {
		return 'MeprDirectDebitSofortGateway';
	}
}
