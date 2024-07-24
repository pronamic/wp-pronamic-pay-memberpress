<?php
/**
 * In3 gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * In3 gateway class
 */
class In3Gateway extends Gateway {
	/**
	 * Constructs and initialize an in3 gateway.
	 */
	public function __construct() {
		parent::__construct( 'MeprIn3Gateway', PaymentMethods::IN3 );
	}
}
