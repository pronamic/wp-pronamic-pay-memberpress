<?php
/**
 * Klarna Pay Later gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * Klarna Pay Later gateway class
 */
class KlarnaPayLaterGateway extends Gateway {
	/**
	 * Constructs and initialize iDEAL gateway.
	 */
	public function __construct() {
		parent::__construct( 'MeprKlarnaPayLaterGateway', PaymentMethods::KLARNA_PAY_LATER );
	}
}
