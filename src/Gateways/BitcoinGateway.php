<?php
/**
 * Bitcoin gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * WordPress pay MemberPress Bitcoin gateway
 *
 * @author  Reüel van der Steege
 * @version 3.1.0
 * @since   1.0.5
 */
class BitcoinGateway extends Gateway {
	/**
	 * Constructs and initialize Bitcoin gateway.
	 */
	public function __construct() {
		parent::__construct( 'MeprBitcoinGateway', PaymentMethods::BITCOIN );
	}
}
