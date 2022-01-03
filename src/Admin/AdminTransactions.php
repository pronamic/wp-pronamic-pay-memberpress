<?php
/**
 * Admin transactions
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Admin;

use MeprTransactionsTable;
use WP_Post;
use WP_Query;

/**
 * Admin transactions
 *
 * @author  Remco Tolsma
 * @version 3.1.0
 * @since   1.0.0
 */
class AdminTransactions {
	/**
	 * Payments map.
	 *
	 * @var array<string, WP_Post>
	 */
	private $payments_map;

	/**
	 * Construct admin transactions.
	 */
	public function __construct() {
		$this->payments_map = array();
	}

	/**
	 * Setup.
	 * 
	 * @return void
	 */
	public function setup() {
		/**
		 * Filter for transactions columns.
		 * 
		 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/controllers/MeprTransactionsCtrl.php#L18-L22
		 */
		$hook = 'memberpress_page_memberpress-trans';

		\add_filter( 'manage_' . $hook . '_columns', array( $this, 'manage_transactions_columns' ), 15 );

		/**
		 * MemberPress admin transactions cell.
		 * 
		 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/views/admin/transactions/row.php#L196-L198
		 */
		\add_action( 'mepr-admin-transactions-cell', array( $this, 'admin_transactions_cell' ), 10, 3 );

		/**
		 * Load payments maps.
		 * 
		 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprView.php#L23-L66
		 */
		\add_filter( 'mepr_view_paths_get_string', array( $this, 'maybe_load_payments_map' ), 10, 3 );

		/**
		 * Extend transaction form.
		 */
		\add_filter( 'mepr_view_get_string', array( $this, 'extend_transaction_form' ), 10, 3 );
	}

	/**
	 * Manage transactions columns.
	 *
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public function manage_transactions_columns( $columns ) {
		$columns['pronamic_payment'] = __( 'Pronamic Payment', 'pronamic_ideal' );

		return $columns;
	}

	/**
	 * Admin transaction cell.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/views/admin/transactions/row.php#L196-L198
	 *
	 * @param string $column_name Column name.
	 * @param object $rec         Record.
	 * @param string $attributes  Attributes.
	 * @return void
	 */
	public function admin_transactions_cell( $column_name, $rec, $attributes ) {
		if ( 'pronamic_payment' !== $column_name ) {
			return;
		}

		if ( ! \property_exists( $rec, 'id' ) ) {
			return;
		}

		printf(
			'<td %s>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$attributes
		);

		$memberpress_transaction_id = $rec->id;

		if ( \array_key_exists( $memberpress_transaction_id, $this->payments_map ) ) {
			$pronamic_payment_post = $this->payments_map[ $memberpress_transaction_id ];

			\printf(
				'<a href="%s">%s</a>',
				\esc_attr( (string) \get_edit_post_link( $pronamic_payment_post ) ),
				\esc_html( (string) $pronamic_payment_post->ID )
			);
		} else {
			echo '—';
		}

		echo '</td>';
	}

	/**
	 * Maybe load payments map.
	 * 
	 * @param string[] $paths Paths.
	 * @param string   $slug  Slug.
	 * @param mixed[]  $vars  Variables.
	 * @return string[]
	 */
	public function maybe_load_payments_map( $paths, $slug, $vars ) {
		if ( '/admin/transactions/list' !== $slug ) {
			return $paths;
		}

		if ( ! \array_key_exists( 'list_table', $vars ) ) {
			return $paths;
		}

		$list_table = $vars['list_table'];

		if ( ! $list_table instanceof MeprTransactionsTable ) {
			return $paths;
		}

		$memberpress_transaction_ids = \wp_list_pluck( $list_table->items, 'id' );

		$query = new WP_Query(
			array(
				'post_type'   => 'pronamic_payment',
				'post_status' => 'any',
				'nopaging'    => true,
				'meta_query'  => array(
					array(
						'key'     => '_pronamic_payment_source',
						'compare' => '=',
						'value'   => 'memberpress_transaction',
					),
					array(
						'key'     => '_pronamic_payment_source_id',
						'compare' => 'IN',
						'value'   => $memberpress_transaction_ids,
					),
				),
			)
		);

		$payment_posts = array_filter(
			$query->posts,
			function( $post ) {
				return $post instanceof WP_Post;
			} 
		);

		foreach ( $payment_posts as $payment_post ) {
			$memberpress_transaction_id = (string) \get_post_meta( $payment_post->ID, '_pronamic_payment_source_id', true );

			$this->payments_map[ $memberpress_transaction_id ] = $payment_post;
		}

		return $paths;
	}

	/**
	 * Extend transaction form.
	 *
	 * @param string  $view View.
	 * @param string  $slug Slug.
	 * @param mixed[] $vars Variables.
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
