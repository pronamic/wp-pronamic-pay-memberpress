<?php
/**
 * Direct Debit mandate via Bancontact gateway
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
 * WordPress pay MemberPress Direct Debit mandate via Bancontact gateway
 *
 * @author  Reüel van der Steege
 * @version 3.1.0
 * @since   1.0.6
 */
class DirectDebitBancontactGateway extends Gateway {
	/**
	 * Constructs and initialize credit card gateway.
	 */
	public function __construct() {
		parent::__construct( 'MeprDirectDebitBancontactGateway', PaymentMethods::DIRECT_DEBIT_BANCONTACT );
	}
}
