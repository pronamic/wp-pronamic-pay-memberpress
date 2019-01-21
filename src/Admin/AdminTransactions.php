<?php
/**
 * Admin transactions
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Admin;

use WP_Query;

/**
 * Admin transactions
 *
 * @author  Remco Tolsma
 * @version 2.0.4
 * @since   1.0.0
 */
class AdminTransactions {
	/**
	 * Setup.
	 */
	public function setup() {
		// @link https://github.com/wp-premium/memberpress-business/blob/1.3.36/app/controllers/MeprTransactionsCtrl.php
		$hook = 'memberpress_page_memberpress-trans';

		add_filter( 'manage_' . $hook . '_columns', array( $this, 'manage_transactions_columns' ), 10 );

		add_filter( 'mepr_view_get_string', array( $this, 'extend_transaction_row' ), 10, 3 );
		add_filter( 'mepr_view_get_string', array( $this, 'extend_transaction_form' ), 10, 3 );
	}

	/**
	 * Manage transactions columns.
	 *
	 * @param array $columns Columns.
	 */
	public function manage_transactions_columns( $columns ) {
		/*
		 * Unfortunately there is currently no filter to extend the transactions table row,
		 * we therefore do not add an column for the Pronamic payment:
		 *
		 * $columns['pronamic_payment'] = __( 'Pronamic Payment', 'pronamic_ideal' );
		 */

		return $columns;
	}

	/**
	 * Extend transaction row.
	 *
	 * @link https://github.com/wp-premium/memberpress-business/blob/1.3.36/app/controllers/MeprTransactionsCtrl.php#L479-L486
	 * @link https://github.com/wp-premium/memberpress-business/blob/1.3.36/app/views/admin/transactions/list.php
	 * @link https://github.com/wp-premium/memberpress-business/blob/1.3.36/app/views/admin/transactions/row.php
	 *
	 * @param string $view View.
	 * @param string $slug Slug.
	 * @param array  $vars Variables.
	 * @return string
	 */
	public function extend_transaction_row( $view, $slug, $vars ) {
		if ( '/admin/transactions/row' !== $slug ) {
			return $view;
		}

		/*
		 * Unfortunately there is currently no filter to extend the transactions table row.
		 * @link https://github.com/wp-premium/memberpress-business/blob/1.3.36/app/views/admin/transactions/row.php
		 *
		 * If we want to add a custom column we should extend the HTML/DOM.
		 * @link https://docs.gravityforms.com/gform_submit_button/#5-append-custom-css-classes-to-the-button
		 */

		return $view;
	}

	/**
	 * Extend transaction form.
	 *
	 * @param string $view View.
	 * @param string $slug Slug.
	 * @param array  $vars Variables.
	 * @return string
	 */
	public function extend_transaction_form( $view, $slug, $vars ) {
		if ( '/admin/transactions/trans_form' !== $slug ) {
			return $view;
		}

		if ( ! array_key_exists( 'txn', $vars ) ) {
			return $view;
		}

		$memberpress_transaction = $vars['txn'];

		/*
		 * Check if variable is a object, should be instance of `MeprTransaction`.
		 * @link https://github.com/wp-premium/memberpress-business/blob/1.3.36/app/models/MeprTransaction.php
		 */
		if ( ! is_object( $memberpress_transaction ) ) {
			return $view;
		}

		if ( ! isset( $memberpress_transaction->id ) ) {
			return $view;
		}

		$memberpress_transaction_id = $memberpress_transaction->id;

		ob_start();

		include dirname( __FILE__ ) . '/../../views/transaction-form.php';

		$view .= ob_get_clean();

		return $view;
	}
}
