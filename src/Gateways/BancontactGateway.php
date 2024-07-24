<?php
/**
 * Bancontact/Mister Cash gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * WordPress pay MemberPress Bancontact/Mister Cash gateway
 *
 * @author  Remco Tolsma
 * @version 3.1.0
 * @since   1.0.0
 */
class BancontactGateway extends Gateway {
	/**
	 * Constructs and initialize Bancontact/Mister Cash gateway.
	 */
	public function __construct() {
		parent::__construct( 'MeprMisterCashGateway', PaymentMethods::BANCONTACT );
	}
}
