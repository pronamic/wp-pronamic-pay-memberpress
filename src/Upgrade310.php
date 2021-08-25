<?php
/**
 * Upgrade 3.1.0
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2021 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Upgrades
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use Pronamic\WordPress\Pay\Subscriptions\SubscriptionStatus;
use Pronamic\WordPress\Pay\Upgrades\Upgrade;

/**
 * Upgrade 3.1.0
 *
 * @author  Remco Tolsma
 * @version 3.1.0
 * @since   3.1.0
 */
class Upgrade310 extends Upgrade {
	/**
	 * Construct 2.1.6 upgrade.
	 */
	public function __construct() {
		parent::__construct( '3.1.0' );

		if ( \defined( '\WP_CLI' ) && \WP_CLI ) {
			$this->cli_init();
		}
	}

	/**
	 * Execute.
	 *
	 * @return void
	 */
	public function execute() {
		$this->upgrade_subscriptions();
		$this->upgrade_payments();
	}

	/**
	 * WP-CLI initialize.
	 *
	 * @link https://github.com/wp-cli/wp-cli/issues/4818
	 * @return void
	 */
	public function cli_init() {
		\WP_CLI::add_command(
			'pronamic-pay memberpress upgrade-310 execute',
			function( $args, $assoc_args ) {
				\WP_CLI::log( 'Upgrade 3.1.0' );

				$this->execute();
			},
			array(
				'shortdesc' => 'Execute MemberPress upgrade 3.1.0.',
			)
		);

		\WP_CLI::add_command(
			'pronamic-pay memberpress upgrade-310 list-subscriptions',
			function( $args, $assoc_args ) {
				\WP_CLI::log( 'Upgrade 3.1.0 - Subscriptions List' );

				$posts = $this->get_subscription_posts();

				\WP_CLI\Utils\format_items( 'table', $posts, array( 'ID', 'post_title', 'post_status' ) );
			},
			array(
				'shortdesc' => 'Execute MemberPress upgrade 3.1.0.',
			)
		);

		\WP_CLI::add_command(
			'pronamic-pay memberpress upgrade-310 upgrade-subscriptions',
			function( $args, $assoc_args ) {
				\WP_CLI::log( 'Upgrade 3.1.0 - Subscriptions' );

				$this->upgrade_subscriptions(
					array(
						'skip-no-match' => \WP_CLI\Utils\get_flag_value( $assoc_args, 'skip-no-match', true ),
						'reactivate'    => \WP_CLI\Utils\get_flag_value( $assoc_args, 'reactivate', true ),
						'dry-run'       => \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', true ),
						'post__in'      => \WP_CLI\Utils\get_flag_value( $assoc_args, 'post__in', null ),
					)
				);
			},
			array(
				'shortdesc' => 'Execute MemberPress upgrade 2.1.6.',
			)
		);

		\WP_CLI::add_command(
			'pronamic-pay memberpress upgrade-310 list-payments',
			function( $args, $assoc_args ) {
				\WP_CLI::log( 'Upgrade 3.1.0 - Payments List' );

				$posts = $this->get_payment_posts();

				\WP_CLI\Utils\format_items( 'table', $posts, array( 'ID', 'post_title', 'post_status' ) );
			},
			array(
				'shortdesc' => 'Execute MemberPress upgrade 2.1.6.',
			)
		);
	}

	/**
	 * Get subscription posts to upgrade.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	private function get_subscription_posts( $args = array() ) {
		$args['post_type']     = 'pronamic_pay_subscr';
		$args['post_status']   = 'any';
		$args['nopaging']      = true;
		$args['no_found_rows'] = true;
		$args['order']         = 'DESC';
		$args['orderby']       = 'ID';
		$args['meta_query']    = array(
			array(
				'key'   => '_pronamic_subscription_source',
				'value' => 'memberpress',
			),
		);

		$query = new \WP_Query( $args );

		return $query->posts;
	}

	/**
	 * Get payment posts to upgrade.
	 *
	 * @return array
	 */
	private function get_payment_posts() {
		$query = new \WP_Query(
			array(
				'post_type'     => 'pronamic_payment',
				'post_status'   => 'any',
				'meta_query'    => array(
					array(
						'key'   => '_pronamic_payment_source',
						'value' => 'memberpress',
					),
				),
				'nopaging'      => true,
				'no_found_rows' => true,
				'order'         => 'DESC',
				'orderby'       => 'ID',
			)
		);

		return $query->posts;
	}

	/**
	 * Log.
	 *
	 * @link https://make.wordpress.org/cli/handbook/internal-api/wp-cli-log/
	 * @param string $message Message.
	 * @return void
	 */
	private function log( $message ) {
		if ( method_exists( '\WP_CLI', 'log' ) ) {
			\WP_CLI::log( $message );
		}
	}

	/**
	 * Upgrade subscriptions.
	 *
	 * @param array $args Arguments.
	 */
	private function upgrade_subscriptions( $args = array() ) {
		$args = \wp_parse_args(
			$args,
			array(
				'skip-no-match' => false,
				'reactivate'    => false,
				'dry-run'       => false,
				'post__in'      => null,
			)
		);

		$skip_no_match = \filter_var( $args['skip-no-match'], FILTER_VALIDATE_BOOLEAN );
		$reactivate    = \filter_var( $args['reactivate'], FILTER_VALIDATE_BOOLEAN );
		$dry_run       = \filter_var( $args['dry-run'], FILTER_VALIDATE_BOOLEAN );

		$query_args = array();

		if ( null !== $args['post__in'] ) {
			$query_args['post__in'] = \explode( ',', $args['post__in'] );
		}

		$subscription_posts = $this->get_subscription_posts( $query_args );

		$this->log(
			\sprintf(
				'Processing %d subscription posts…',
				\number_format_i18n( \count( $subscription_posts ) )
			)
		);

		foreach ( $subscription_posts as $subscription_post ) {
			$subscription_post_id = $subscription_post->ID;

			$this->log(
				\sprintf(
					'Subscription post %s',
					$subscription_post_id
				)
			);

			/**
			 * Get subscription.
			 *
			 * @link https://github.com/wp-pay/core/blob/2.2.4/includes/functions.php#L158-L180
			 */
			$subscription = \get_pronamic_subscription( $subscription_post_id );

			if ( null === $subscription ) {
				continue;
			}

			/**
			 * Get source.
			 */
			$subscription_source    = \get_post_meta( $subscription_post_id, '_pronamic_subscription_source', true );
			$subscription_source_id = \get_post_meta( $subscription_post_id, '_pronamic_subscription_source_id', true );

			\update_post_meta( $subscription_post_id, '_pronamic_subscription_memberpress_update_source', $subscription_source );
			\update_post_meta( $subscription_post_id, '_pronamic_subscription_memberpress_update_source_id', $subscription_source_id );
		}
	}

	/**
	 * Upgrade payments.
	 */
	private function upgrade_payments() {
		$payment_posts = $this->get_payment_posts();

		foreach ( $payment_posts as $payment_post ) {
			$payment_post_id = $payment_post->ID;

			/**
			 * Get payment.
			 *
			 * @link https://github.com/wp-pay/core/blob/2.2.4/includes/functions.php#L24-L46
			 */
			$payment = \get_pronamic_payment( $payment_post_id );

			if ( null === $payment ) {
				continue;
			}

			/**
			 * Get source.
			 */
			$payment_source    = \get_post_meta( $payment_post_id, '_pronamic_payment_source', true );
			$payment_source_id = \get_post_meta( $payment_post_id, '_pronamic_payment_source_id', true );

			\update_post_meta( $payment_post_id, '_pronamic_payment_memberpress_update_source', $payment_source );
			\update_post_meta( $payment_post_id, '_pronamic_payment_memberpress_update_source_id', $payment_source_id );
		}
	}
}