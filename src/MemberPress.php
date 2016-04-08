<?php

/**
 * Title: WordPress pay MemberPress
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.0.0
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_MemberPress_MemberPress {
	/**
	 * Transaction has status
	 *
	 * @param MeprTransaction $transaction
	 * @param string|array $status
	 */
	public static function transaction_has_status( $transaction, $status ) {
		$has_status = false;

		if ( is_array( $status ) ) {
			$has_status = in_array( $transaction->status, $status, true );
		} else {
			$has_status = ( $transaction->status === $status );
		}

		return $has_status;
	}
}
