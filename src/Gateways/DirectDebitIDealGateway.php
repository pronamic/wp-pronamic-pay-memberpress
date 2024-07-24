<?php
/**
 * Direct Debit mandate via iDEAL gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways;

use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Plugin;

/**
 * WordPress pay MemberPress Direct Debit mandate via iDEAL gateway
 *
 * @author  Reüel van der Steege
 * @version 3.1.0
 * @since   2.0.0
 */
class DirectDebitIDealGateway extends Gateway {
	/**
	 * Constructs and initialize credit card gateway.
	 */
	public function __construct() {
		parent::__construct( 'MeprDirectDebitIDealGateway', PaymentMethods::DIRECT_DEBIT_IDEAL );
	}
}
