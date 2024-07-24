<?php
/**
 * Przelewy24 gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * WordPress pay MemberPress Przelewy24 gateway
 *
 * @author  Reüel van der Steege
 * @version 3.1.0
 * @since   2.2.0
 */
class Przelewy24Gateway extends Gateway {
	/**
	 * Constructs and initialize Przelewy24 gateway.
	 */
	public function __construct() {
		parent::__construct( 'MeprPrzelewy24Gateway', PaymentMethods::PRZELEWY24 );
	}
}
