<?php
/**
 * Klarna Pay Over Time gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * Klarna Pay Over Time gateway class
 */
class KlarnaPayOverTimeGateway extends Gateway {
	/**
	 * Constructs and initialize iDEAL gateway.
	 */
	public function __construct() {
		parent::__construct( 'MeprKlarnaPayOverTimeGateway', PaymentMethods::KLARNA_PAY_OVER_TIME );
	}
}
