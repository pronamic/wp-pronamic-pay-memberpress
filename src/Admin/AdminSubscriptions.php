<?php
/**
 * Admin subscriptions
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Admin;

use WP_Post;
use WP_Query;

/**
 * Admin subscriptions
 *
 * @author  Remco Tolsma
 * @version 3.1.0
 * @since   1.0.0
 */
class AdminSubscriptions {
	/**
	 * Subscriptions map.
	 *
	 * @var array<string, WP_Post>|null
	 */
	private $subscriptions_map;

	/**
	 * Setup.
	 * 
	 * @return void
	 */
	public function setup() {
		/**
		 * Filter for subscriptions columns.
		 * 
		 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/controllers/MeprSubscriptionsCtrl.php#L20-L27
		 */
		$hook = 'memberpress_page_memberpress-subscriptions';

		add_filter( 'manage_' . $hook . '_columns', array( $this, 'manage_subscriptions_columns' ), 15 );

		add_action( 'mepr-admin-subscriptions-cell', array( $this, 'admin_subscriptions_cell' ), 10, 4 );

		add_filter( 'mepr_view_get_string', array( $this, 'extend_subscription_form' ), 10, 3 );
	}

	/**
	 * Manage subscriptions columns.
	 *
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public function manage_subscriptions_columns( $columns ) {
		$columns['pronamic_subscription'] = __( 'Pronamic Subscription', 'pronamic_ideal' );

		return $columns;
	}

	/**
	 * Get subscriptions map.
	 *
	 * @param object $table Table.
	 * @return array<string, WP_Post>|null
	 */
	private function get_subscriptions_map( $table ) {
		if ( is_array( $this->subscriptions_map ) ) {
			return $this->subscriptions_map;
		}

		$this->subscriptions_map = array();

		if ( ! isset( $table->items ) ) {
			return null;
		}

		$memberpress_subscriptions = $table->items;

		if ( ! is_array( $memberpress_subscriptions ) || empty( $memberpress_subscriptions ) ) {
			return null;
		}

		$memberpress_subscription_ids = wp_list_pluck( $memberpress_subscriptions, 'id' );

		$query = new WP_Query(
			array(
				'post_type'   => 'pronamic_pay_subscr',
				'post_status' => 'any',
				'nopaging'    => true,
				'meta_query'  => array(
					array(
						'key'     => '_pronamic_subscription_source',
						'compare' => '=',
						'value'   => 'memberpress_subscription',
					),
					array(
						'key'     => '_pronamic_subscription_source_id',
						'compare' => 'IN',
						'value'   => $memberpress_subscription_ids,
					),
				),
			)
		);

		$subscription_posts = $query->get_posts();

		foreach ( $subscription_posts as $subscription_post ) {
			if ( ! $subscription_post instanceof WP_Post ) {
				continue;
			}

			$memberpress_subscription_id = (string) \get_post_meta( $subscription_post->ID, '_pronamic_subscription_source_id', true );

			$this->subscriptions_map[ $memberpress_subscription_id ] = $subscription_post;
		}

		return $this->subscriptions_map;
	}

	/**
	 * Admin subscription cell.
	 *
	 * @link https://github.com/wp-premium/memberpress-business/blob/1.3.36/app/controllers/MeprSubscriptionsCtrl.php#L73
	 * @link https://github.com/wp-premium/memberpress-business/blob/1.3.36/app/lib/MeprSubscriptionsTable.php#L230
	 * @link https://github.com/wp-premium/memberpress-business/blob/1.3.36/app/lib/MeprView.php#L49
	 * @link https://github.com/wp-premium/memberpress-business/blob/1.3.36/app/views/admin/subscriptions/row.php
	 *
	 * @param string $column_name Column name.
	 * @param object $rec         Record.
	 * @param object $table       Table.
	 * @param string $attributes  Attributes.
	 * @return void
	 */
	public function admin_subscriptions_cell( $column_name, $rec, $table, $attributes ) {
		if ( 'pronamic_subscription' !== $column_name ) {
			return;
		}

		if ( ! \property_exists( $rec, 'id' ) ) {
			return;
		}

		$map = $this->get_subscriptions_map( $table );

		if ( null === $map ) {
			return;
		}

		printf(
			'<td %s>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$attributes
		);

		$memberpress_subscription_id = $rec->id;

		if ( isset( $map[ $memberpress_subscription_id ] ) ) {
			$pronamic_subscription_post = $map[ $memberpress_subscription_id ];

			\printf(
				'<a href="%s">%s</a>',
				\esc_attr( (string) \get_edit_post_link( $pronamic_subscription_post ) ),
				\esc_html( (string) $pronamic_subscription_post->ID )
			);
		} else {
			echo '—';
		}

		echo '</td>';
	}

	/**
	 * Extend subscription form.
	 *
	 * @link https://github.com/wp-premium/memberpress-business/blob/1.3.36/app/controllers/MeprSubscriptionsCtrl.php#L105-L133
	 * @link https://github.com/wp-premium/memberpress-business/blob/1.3.36/app/views/admin/subscriptions/edit.php
	 * @link https://github.com/wp-premium/memberpress-business/blob/1.3.36/app/views/admin/subscriptions/form.php
	 *
	 * @param string  $view View.
	 * @param string  $slug Slug.
	 * @param mixed[] $vars Variables.
	 * @return string
	 */
	public function extend_subscription_form( $view, $slug, $vars ) {
		if ( '/admin/subscriptions/form' !== $slug ) {
			return $view;
		}

		if ( ! array_key_exists( 'sub', $vars ) ) {
			return $view;
		}

		$memberpress_subscription = $vars['sub'];

		/*
		 * Check if variable is a object, should be instance of `MeprSubscription`.
		 * @link https://github.com/wp-premium/memberpress-business/blob/1.3.36/app/models/MeprSubscription.php
		 */
		if ( ! is_object( $memberpress_subscription ) ) {
			return $view;
		}

		if ( ! isset( $memberpress_subscription->id ) ) {
			return $view;
		}

		$memberpress_subscription_id = $memberpress_subscription->id;

		ob_start();

		include dirname( __FILE__ ) . '/../../views/subscription-form.php';

		$view .= ob_get_clean();

		return $view;
	}
}
