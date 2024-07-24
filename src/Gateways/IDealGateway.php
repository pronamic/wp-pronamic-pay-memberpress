<?php
/**
 * IDeal gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * WordPress pay MemberPress iDEAL gateway
 *
 * @author  Remco Tolsma
 * @version 3.1.0
 * @since   1.0.0
 */
class IDealGateway extends Gateway {
	/**
	 * Constructs and initialize iDEAL gateway.
	 */
	public function __construct() {
		parent::__construct( 'MeprIDealGateway', PaymentMethods::IDEAL );
	}
}
