<?php
/**
 * MemberPress transaction adapter.
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2021 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use MeprTransaction;

/**
 * MemberPress transaction adapter.
 *
 * @author  Remco Tolsma
 * @version 2.0.1
 * @since   1.0.0
 */
class MemberPressTransactionAdapter {
	/**
	 * MemberPress transaction.
	 * 
	 * @var MeprTransaction
	 */
	private $transaction;

	/**
	 * Construct MemberPress transaction adapter.
	 *
	 * @param MeprTransaction $transaction MemberPress transaction.
	 */
	public function __construct( MeprTransaction $transaction ) {
		$this->transaction = $transaction;
	}

	/**
	 * Get property value.
	 * 
	 * @param string $property Property name.
	 * @return mixed
	 * @throws \Exception Throws exception when property does not exists.
	 */
	public function get_property( $property ) {
		if ( ! \property_exists( $this->transaction, $property ) ) {
			throw new \Exception(
				\sprintf(
					'Property `%s` does not exists.',
					$property
				)
			);
		}

		return $this->transaction->{$property};
	}

	/**
	 * Get ID.
	 * 
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/models/MeprTransaction.php#L10
	 * @return int|null
	 */
	public function get_id() {
		return $this->get_property( 'id' );
	}

	/**
	 * Get status.
	 * 
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/models/MeprTransaction.php#L21
	 * @return string|null
	 */
	public function get_status() {
		return $this->get_property( 'status' );
	}

	/**
	 * Has status.
	 *
	 * @param string|string[] $status MemberPress transaction status string.
	 * @return bool Returns true if the transaction has the specified status, false otherwise.
	 */
	public function has_status( $status ) {
		if ( \is_array( $status ) ) {
			return \in_array( $this->get_status(), $status, true );
		}

		return ( $this->get_status() === $status );
	}
}
