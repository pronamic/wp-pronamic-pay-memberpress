<?php
/**
 * MemberPress
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use MeprTransaction;
use MeprOptions;

/**
 * WordPress pay MemberPress
 *
 * @author  Remco Tolsma
 * @version 2.0.1
 * @since   1.0.0
 */
class MemberPress {
	/**
	 * Transaction has status.
	 *
	 * @param MeprTransaction $transaction MemberPress transaction object.
	 * @param string|array    $status      MemberPress transaction status string.
	 *
	 * @return bool Returns true if the transaction has the specified status, false otherwise.
	 */
	public static function transaction_has_status( MeprTransaction $transaction, $status ) {
		if ( is_array( $status ) ) {
			return in_array( $transaction->status, $status, true );
		}

		return ( $transaction->status === $status );
	}

	/**
	 * Get currency.
	 *
	 * @return string
	 */
	public static function get_currency() {
		$mepr_options = MeprOptions::fetch();

		// @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/models/MeprOptions.php#L136-137
		return $mepr_options->currency_code;
	}
}
