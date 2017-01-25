<?php

/**
 * Title: WordPress pay MemberPress
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.0.1
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_MemberPress_MemberPress {
	/**
	 * Transaction has status.
	 *
	 * @param MeprTransaction $transaction
	 * @param string|array $status
	 */
	public static function transaction_has_status( $transaction, $status ) {
		if ( is_array( $status ) ) {
			return in_array( $transaction->status, $status, true );
		}

		return ( $transaction->status === $status );
	}
}
