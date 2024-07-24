<?php
/**
 * MemberPress
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use MeprSubscription;
use MeprTransaction;
use MeprOptions;

/**
 * WordPress pay MemberPress
 *
 * @author  Remco Tolsma
 * @version 3.1.0
 * @since   1.0.0
 */
class MemberPress {
	/**
	 * MemberPress has no unambiguous way to request a transaction via an ID,
	 * so we have implemented a method for this.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L928-L933
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/models/MeprTransaction.php#L156-L161
	 * @param int|string|null $id ID.
	 * @return MeprTransaction|null
	 */
	public static function get_transaction_by_id( $id ) {
		$object = MeprTransaction::get_one( $id );

		if ( ! \is_object( $object ) ) {
			return null;
		}

		if ( ! isset( $object->id ) ) {
			return null;
		}

		$transaction = new MeprTransaction();

		$transaction->load_data( $object );

		return $transaction;
	}

	/**
	 * Get MemberPress subscription by ID.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L1468
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/models/MeprSubscription.php#L211-L241
	 * @param int|string|null $id ID.
	 * @return MeprSubscription|null
	 */
	public static function get_subscription_by_id( $id ) {
		$object = MeprSubscription::get_one( $id );

		if ( ! \is_object( $object ) ) {
			return null;
		}

		if ( ! isset( $object->id ) ) {
			return null;
		}

		$subscription = new MeprSubscription();

		$subscription->load_data( $object );

		return $subscription;
	}

	/**
	 * Transaction has status.
	 *
	 * @param MeprTransaction $transaction MemberPress transaction object.
	 * @param string|string[] $status      MemberPress transaction status string.
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
