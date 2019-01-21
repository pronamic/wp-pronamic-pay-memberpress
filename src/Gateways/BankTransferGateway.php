<?php
/**
 * Bank transfer gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * WordPress pay MemberPress bank transfer gateway
 *
 * @author  Remco Tolsma
 * @version 2.0.1
 * @since   1.0.0
 */
class BankTransferGateway extends Gateway {
	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::BANK_TRANSFER;

	/**
	 * Get alias class name of this gateway.
	 *
	 * @return string
	 */
	public function get_alias() {
		return 'MeprBankTransferGateway';
	}
}
