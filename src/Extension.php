<?php

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use MeprOptions;
use MeprProduct;
use MeprTransaction;
use MeprSubscription;
use Pronamic\WordPress\Pay\Core\Statuses;
use Pronamic\WordPress\Pay\Payments\Payment;

/**
 * Title: WordPress pay MemberPress extension
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.0.0
 */
class Extension {
	/**
	 * The slug of this addon
	 *
	 * @var string
	 */
	const SLUG = 'memberpress';

	/**
	 * Bootstrap
	 */
	public static function bootstrap() {
		new self();
	}

	/**
	 * Constructs and initializes the MemberPress extension.
	 */
	public function __construct() {
		// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprGatewayFactory.php#L48-50
		add_filter( 'mepr-gateway-paths', array( $this, 'gateway_paths' ) );

		add_filter( 'pronamic_payment_redirect_url_' . self::SLUG, array( __CLASS__, 'redirect_url' ), 10, 2 );
		add_action( 'pronamic_payment_status_update_' . self::SLUG, array( __CLASS__, 'status_update' ), 10, 1 );

		add_filter( 'pronamic_payment_source_text_' . self::SLUG, array( __CLASS__, 'source_text' ), 10, 2 );
		add_filter( 'pronamic_payment_source_description_' . self::SLUG, array( __CLASS__, 'source_description' ), 10, 2 );
		add_filter( 'pronamic_payment_source_url_' . self::SLUG, array( __CLASS__, 'source_url' ), 10, 2 );

		add_action( 'mepr_subscription_pre_delete', array( $this, 'subscription_pre_delete' ), 10, 1 );
	}

	/**
	 * Gateway paths
	 *
	 * @param array $paths
	 *
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprGatewayFactory.php#L48-50
	 *
	 * @return array
	 */
	public function gateway_paths( $paths ) {
		$paths[] = dirname( __FILE__ ) . '/../gateways/';

		return $paths;
	}

	/**
	 * Payment redirect URL filter.
	 *
	 * @since 1.0.1
	 *
	 * @param string  $url
	 * @param Payment $payment
	 *
	 * @return string
	 */
	public static function redirect_url( $url, Payment $payment ) {
		global $transaction;

		$transaction_id = $payment->get_source_id();

		$transaction = new MeprTransaction( $transaction_id );

		switch ( $payment->get_status() ) {
			case Statuses::CANCELLED:
			case Statuses::EXPIRED:
			case Statuses::FAILURE:
				$product = $transaction->product();

				$url = add_query_arg(
					array(
						'action'   => 'payment_form',
						'txn'      => $transaction->trans_num,
						'_wpnonce' => wp_create_nonce( 'mepr_payment_form' ),
					),
					$product->url()
				);

				break;
			case Statuses::SUCCESS:
				// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/models/MeprOptions.php#L768-782
				$mepr_options = MeprOptions::fetch();

				$product         = new MeprProduct( $transaction->product_id );
				$sanitized_title = sanitize_title( $product->post_title );

				$args = array(
					'membership_id' => $product->ID,
					'membership'    => $sanitized_title,
					'trans_num'     => $transaction->trans_num,
				);

				$url = $mepr_options->thankyou_page_url( http_build_query( $args ) );

				break;
			case Statuses::OPEN:
			default:
				break;
		}

		return $url;
	}

	/**
	 * Update lead status of the specified payment
	 *
	 * @see https://github.com/Charitable/Charitable/blob/1.1.4/includes/gateways/class-charitable-gateway-paypal.php#L229-L357
	 *
	 * @param Payment $payment
	 */
	public static function status_update( Payment $payment ) {
		global $transaction;

		$transaction_id = $payment->get_source_id();

		$transaction = new MeprTransaction( $transaction_id );

		if ( $payment->get_recurring() ) {
			$sub = $transaction->subscription();

			// Same source ID and first transaction ID for recurring payment means we need to add a new transaction.
			if ( $payment->get_source_id() === $sub->first_txn_id ) {
				// First transaction.
				$first_txn = $sub->first_txn();

				if ( false === $first_txn || ! ( $first_txn instanceof MeprTransaction ) ) {
					$first_txn             = new MeprTransaction();
					$first_txn->user_id    = $sub->user_id;
					$first_txn->product_id = $sub->product_id;
					$first_txn->coupon_id  = $sub->coupon_id;
				}

				// Transaction number.
				$trans_num = $payment->get_transaction_id();

				if ( empty( $trans_num ) ) {
					$trans_num = uniqid();
				}

				// New transaction.
				$txn                  = new MeprTransaction();
				$txn->created_at      = $payment->post->post_date_gmt;
				$txn->user_id         = $first_txn->user_id;
				$txn->product_id      = $first_txn->product_id;
				$txn->coupon_id       = $first_txn->coupon_id;
				$txn->gateway         = $transaction->gateway;
				$txn->trans_num       = $trans_num;
				$txn->txn_type        = MeprTransaction::$payment_str;
				$txn->status          = MeprTransaction::$pending_str;
				$txn->subscription_id = $sub->id;

				$txn->set_gross( $payment->get_amount()->get_amount() );

				$txn->store();

				update_post_meta( $payment->get_id(), '_pronamic_payment_source_id', $txn->id );

				$transaction = $txn;
			}
		}

		$should_update = ! MemberPress::transaction_has_status( $transaction, array(
			MeprTransaction::$failed_str,
			MeprTransaction::$complete_str,
		) );

		if ( $should_update ) {
			$gateway = new Gateway();

			$gateway->mp_txn = $transaction;

			switch ( $payment->get_status() ) {
				case Statuses::CANCELLED:
				case Statuses::EXPIRED:
				case Statuses::FAILURE:
					$gateway->record_payment_failure();

					break;
				case Statuses::SUCCESS:
					if ( $payment->get_recurring() ) {
						$gateway->record_subscription_payment();
					} else {
						$gateway->record_payment();
					}

					break;
				case Statuses::OPEN:
				default:
					break;
			}
		}
	}

	/**
	 * Subscription deleted.
	 *
	 * @param int $subscription_id MemberPress subscription id.
	 *
	 * @return void
	 */
	public function subscription_pre_delete( $subscription_id ) {
		$subscription = get_pronamic_subscription_by_meta( '_pronamic_subscription_memberpress_subscription_id', $subscription_id );

		if ( ! $subscription ) {
			return;
		}

		// Add note.
		$note = sprintf(
			/* translators: %s: MemberPress */
			__( '%s subscription deleted.', 'pronamic_ideal' ),
			__( 'MemberPress', 'pronamic_ideal' )
		);

		$subscription->add_note( $note );

		// The status of canceled or completed subscriptions will not be changed automatically.
		if ( ! in_array( $subscription->get_status(), array( Statuses::CANCELLED, Statuses::COMPLETED ), true ) ) {
			$subscription->set_status( Statuses::CANCELLED );

			$subscription->save();
		}
	}

	/**
	 * Source text.
	 *
	 * @param string  $text
	 * @param Payment $payment
	 *
	 * @return string
	 */
	public static function source_text( $text, Payment $payment ) {
		$text = __( 'MemberPress', 'pronamic_ideal' ) . '<br />';

		$text .= sprintf(
			'<a href="%s">%s</a>',
			add_query_arg( array(
				'page'   => 'memberpress-trans',
				'action' => 'edit',
				'id'     => $payment->source_id,
			), admin_url( 'admin.php' ) ),
			/* translators: %s: payment source id */
			sprintf( __( 'Transaction %s', 'pronamic_ideal' ), $payment->source_id )
		);

		return $text;
	}

	/**
	 * Source description.
	 *
	 * @param string  $description
	 * @param Payment $payment
	 *
	 * @return string
	 */
	public static function source_description( $description, Payment $payment ) {
		return __( 'MemberPress Transaction', 'pronamic_ideal' );
	}

	/**
	 * Source URL.
	 *
	 * @param string  $url
	 * @param Payment $payment
	 *
	 * @return string
	 */
	public static function source_url( $url, Payment $payment ) {
		$url = add_query_arg( array(
			'page'   => 'memberpress-trans',
			'action' => 'edit',
			'id'     => $payment->source_id,
		), admin_url( 'admin.php' ) );

		return $url;
	}
}
