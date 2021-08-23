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
	 * Get ID.
	 * 
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/models/MeprTransaction.php#L10
	 * @return int|null
	 */
	public function get_id() {
		if ( \property_exists( $this->transaction, 'id' ) ) {
			return $this->transaction->id;
		}

		return null;
	}
}
