<?php
/**
 * MemberPress
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2018 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use MeprTransaction;

/**
 * Title: WordPress pay MemberPress
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.0.0
 */
class MemberPress {
	/**
	 * Transaction has status.
	 *
	 * @param MeprTransaction $transaction
	 * @param string|array    $status
	 *
	 * @return bool
	 */
	public static function transaction_has_status( MeprTransaction $transaction, $status ) {
		if ( is_array( $status ) ) {
			return in_array( $transaction->status, $status, true );
		}

		return ( $transaction->status === $status );
	}
}
