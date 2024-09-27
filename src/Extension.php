<?php
/**
 * Extension
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use MeprDb;
use MeprOptions;
use MeprProduct;
use MeprSubscription;
use MeprTransaction;
use MeprUtils;
use Pronamic\WordPress\Pay\AbstractPluginIntegration;
use Pronamic\WordPress\Pay\Payments\PaymentStatus;
use Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways\Gateway;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionStatus;

/**
 * WordPress pay MemberPress extension
 *
 * @author  Remco Tolsma
 * @version 4.2.0
 * @since   1.0.0
 */
class Extension extends AbstractPluginIntegration {
	/**
	 * The slug of this addon
	 *
	 * @var string
	 */
	const SLUG = 'memberpress';

	/**
	 * Construct MemberPress plugin integration.
	 *
	 * @param array<string, mixed> $args Arguments.
	 */
	public function __construct( $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'name'                => \__( 'MemberPress', 'pronamic_ideal' ),
				'slug'                => 'memberpress',
				'version_option_name' => 'pronamic_pay_memberpress_version',
			]
		);

		parent::__construct( $args );

		// Dependencies.
		$dependencies = $this->get_dependencies();

		$dependencies->add( new MemberPressDependency() );

		// Upgrades.
		$upgrades = $this->get_upgrades();

		$upgrades->set_executable( true );

		$upgrades->add( new Upgrade310() );
	}

	/**
	 * Setup.
	 */
	public function setup() {
		\add_filter( 'pronamic_subscription_source_description_memberpress_subscription', [ $this, 'subscription_source_description' ], 10, 2 );
		\add_filter( 'pronamic_payment_source_description_memberpress_transaction', [ $this, 'source_description' ], 10, 2 );

		// Check if dependencies are met and integration is active.
		if ( ! $this->is_active() ) {
			return;
		}

		// @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprGatewayFactory.php#L48-50
		\add_filter( 'mepr-gateway-paths', [ $this, 'gateway_paths' ] );

		\add_filter( 'pronamic_payment_redirect_url_memberpress_transaction', [ $this, 'redirect_url' ], 10, 2 );
		\add_action( 'pronamic_payment_status_update_memberpress_transaction', [ $this, 'status_update' ], 10, 1 );

		\add_action( 'pronamic_payment_status_update', [ $this, 'maybe_update_memberpress_subscription_gateway' ], 10, 1 );
		\add_action( 'pronamic_pay_new_payment', [ $this, 'maybe_create_memberpress_transaction' ], 10, 1 );
		\add_action( 'pronamic_pay_update_payment', [ $this, 'maybe_record_memberpress_transaction_refund' ], 10, 1 );

		\add_action( 'pronamic_subscription_status_update_memberpress_subscription', [ $this, 'subscription_status_update' ], 10, 1 );
		\add_filter( 'pronamic_subscription_source_text_memberpress_subscription', [ $this, 'subscription_source_text' ], 10, 2 );
		\add_filter( 'pronamic_subscription_source_url_memberpress_subscription', [ $this, 'subscription_source_url' ], 10, 2 );

		\add_filter( 'pronamic_payment_source_text_memberpress_transaction', [ $this, 'source_text' ], 10, 2 );
		\add_filter( 'pronamic_payment_source_url_memberpress_transaction', [ $this, 'source_url' ], 10, 2 );

		\add_action( 'mepr_subscription_pre_delete', [ $this, 'subscription_pre_delete' ], 10, 1 );

		\add_action( 'mepr_subscription_saved', [ $this, 'memberpress_subscription_saved' ], 10, 1 );

		// MemberPress subscription email parameters.
		\add_filter( 'mepr_subscription_email_params', [ $this, 'subscription_email_params' ], 10, 2 );
		\add_filter( 'mepr_transaction_email_params', [ $this, 'transaction_email_params' ], 10, 2 );
		\add_filter( 'mepr_subscription_email_vars', [ $this, 'email_variables' ], 10 );
		\add_filter( 'mepr_transaction_email_vars', [ $this, 'email_variables' ], 10 );

		// Hide MemberPress columns for payments and subscriptions.
		\add_action( 'registered_post_type', [ $this, 'post_type_columns_hide' ], 15, 1 );

		if ( \is_admin() ) {
			$admin_subscriptions = new Admin\AdminSubscriptions();
			$admin_transactions  = new Admin\AdminTransactions();

			$admin_subscriptions->setup();
			$admin_transactions->setup();
		}
	}

	/**
	 * Gateway paths.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprGatewayFactory.php#L49
	 * @param string[] $paths Array with gateway paths.
	 * @return string[]
	 */
	public function gateway_paths( $paths ) {
		$paths[] = __DIR__ . '/../gateways/';

		return $paths;
	}

	/**
	 * Hide MemberPress columns for payments and subscriptions.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/controllers/MeprAppCtrl.php#L129-146
	 * @param string $post_type Registered post type.
	 * @return void
	 */
	public function post_type_columns_hide( $post_type ) {
		if ( ! in_array( $post_type, [ 'pronamic_payment', 'pronamic_pay_subscr' ], true ) ) {
			return;
		}

		remove_filter( 'manage_edit-' . $post_type . '_columns', 'MeprAppCtrl::columns' );
	}

	/**
	 * Payment redirect URL filter.
	 *
	 * @since 1.0.1
	 *
	 * @param string  $url     Payment redirect URL.
	 * @param Payment $payment Payment to redirect for.
	 *
	 * @return string
	 */
	public function redirect_url( $url, Payment $payment ) {
		global $transaction;

		$transaction_id = $payment->get_source_id();

		$transaction = new MeprTransaction( $transaction_id );

		switch ( $payment->get_status() ) {
			case PaymentStatus::CANCELLED:
			case PaymentStatus::EXPIRED:
			case PaymentStatus::FAILURE:
				$product = $transaction->product();

				$url = add_query_arg(
					[
						'action'   => 'payment_form',
						'txn'      => $transaction->trans_num,
						'errors'   => [
							__( 'Payment failed. Please try again.', 'pronamic_ideal' ),
						],
						'_wpnonce' => wp_create_nonce( 'mepr_payment_form' ),
					],
					$product->url()
				);

				break;
			case PaymentStatus::AUTHORIZED:
			case PaymentStatus::SUCCESS:
				// @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/models/MeprOptions.php#L768-782
				$mepr_options = MeprOptions::fetch();

				$product         = new MeprProduct( $transaction->product_id );
				$sanitized_title = sanitize_title( $product->post_title );

				$args = [
					'membership_id' => $product->ID,
					'membership'    => $sanitized_title,
					'trans_num'     => $transaction->trans_num,
				];

				$url = $mepr_options->thankyou_page_url( http_build_query( $args ) );

				break;
			case PaymentStatus::OPEN:
			default:
				break;
		}

		return $url;
	}

	/**
	 * Maybe update MemberPress subscription gateway for the Pronamic payment.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/models/MeprSubscription.php
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L587-L714
	 * @param Payment $payment Payment.
	 * @return void
	 * @throws \Exception Throws an exception when the MemberPress subscription cannot be found.
	 */
	public function maybe_update_memberpress_subscription_gateway( Payment $payment ) {
		if ( 'subscription_payment_method_change' !== $payment->get_source() ) {
			return;
		}

		if ( PaymentStatus::SUCCESS !== $payment->get_status() ) {
			return;
		}

		$pronamic_subscriptions = $payment->get_subscriptions();

		foreach ( $pronamic_subscriptions as $pronamic_subscription ) {
			$memberpress_subscription_id = $pronamic_subscription->get_source_id();

			$memberpress_subscription = MemberPress::get_subscription_by_id( $memberpress_subscription_id );

			if ( null === $memberpress_subscription ) {
				throw new \Exception(
					\sprintf(
						'Could not find MemberPress subscription with ID: %s.',
						\esc_html( (string) $memberpress_subscription_id )
					)
				);
			}

			/**
			 * If the payment method is changed we have to update the MemberPress
			 * subscription.
			 *
			 * @link https://github.com/wp-pay-extensions/memberpress/commit/3631bcb24f376fb637c1317e15f540cb1f9136f4#diff-6f62438f6bf291e85f644dbdbb14b2a71a9a7ed205b01ce44290ed85abe2aa07L259-L290
			 */
			$memberpress_gateways = MeprOptions::fetch()->payment_methods();

			foreach ( $memberpress_gateways as $memberpress_gateway ) {
				if ( ! $memberpress_gateway instanceof Gateway ) {
					continue;
				}

				if ( null === $memberpress_gateway->get_payment_method() ) {
					$memberpress_subscription->gateway = $memberpress_gateway->id;
				}

				if ( $payment->get_payment_method() === $memberpress_gateway->get_payment_method() ) {
					$memberpress_subscription->gateway = $memberpress_gateway->id;

					break;
				}
			}

			/**
			 * Store the MemberPress subscription in case of gateway changes.
			 */
			$memberpress_subscription->store();
		}
	}

	/**
	 * Maybe create MemberPress transaction for the Pronamic payment.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/models/MeprSubscription.php
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L587-L714
	 * @param Payment $payment Payment.
	 * @return void
	 * @throws \Exception Throws an exception when the MemberPress subscription cannot be found.
	 */
	public function maybe_create_memberpress_transaction( Payment $payment ) {
		if ( ! in_array(
			$payment->get_source(),
			[
				'memberpress_subscription',
				/**
				 * Before version `3.1` we used 'memberpress' as source.
				 * The upgrade 3.1.0 script corrects this, but for backward
				 * compatibility we also accept 'memberpress'.
				 *
				 * @link https://github.com/wp-pay-extensions/memberpress/blob/3.0.3/src/Pronamic.php#L128
				 * @link https://github.com/pronamic/wp-pay-core/blob/3.0.1/src/Subscriptions/SubscriptionHelper.php#L98-L102
				 * @link https://github.com/pronamic/wp-pay-core/blob/3.0.1/src/Subscriptions/SubscriptionsModule.php#L446-L447
				 */
				'memberpress',
			],
			true
		) ) {
			return;
		}

		$memberpress_subscription_id = $payment->get_source_id();

		$memberpress_subscription = MemberPress::get_subscription_by_id( $memberpress_subscription_id );

		if ( null === $memberpress_subscription ) {
			throw new \Exception(
				\sprintf(
					'Could not find MemberPress subscription with ID: %s.',
					\esc_html( (string) $memberpress_subscription_id )
				)
			);
		}

		$memberpress_transaction = new MeprTransaction();

		$memberpress_transaction->user_id         = $memberpress_subscription->user_id;
		$memberpress_transaction->product_id      = $memberpress_subscription->product_id;
		$memberpress_transaction->txn_type        = MeprTransaction::$subscription_confirmation_str;
		$memberpress_transaction->status          = ( 'recurring' === $payment->get_meta( 'mollie_sequence_type' ) ? MeprTransaction::$confirmed_str : MeprTransaction::$pending_str );
		$memberpress_transaction->coupon_id       = $memberpress_subscription->coupon_id;
		$memberpress_transaction->trans_num       = $payment->get_transaction_id();
		$memberpress_transaction->subscription_id = $memberpress_subscription->id;
		$memberpress_transaction->gateway         = $memberpress_subscription->gateway;

		$periods = $payment->get_periods();

		if ( null !== $periods ) {
			$end_date = null;

			foreach ( $periods as $period ) {
				$end_date = \max( $end_date, $period->get_end_date() );
			}

			if ( null !== $end_date ) {
				$memberpress_transaction->expires_at = MeprUtils::ts_to_mysql_date( $end_date->getTimestamp(), 'Y-m-d 23:59:59' );
			}
		}

		/**
		 * Gross.
		 *
		 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/models/MeprTransaction.php#L1013-L1021
		 */
		$memberpress_transaction->set_gross( $payment->get_total_amount()->get_value() );

		$memberpress_transaction->store();

		/**
		 * Update payment source.
		 *
		 * @link https://github.com/wp-pay-extensions/restrict-content-pro/blob/3.0.0/src/Extension.php#L770-L776
		 */
		$payment->source    = 'memberpress_transaction';
		$payment->source_id = $memberpress_transaction->id;

		$payment->save();
	}

	/**
	 * Maybe record refund for MemberPress transaction.
	 *
	 * @param Payment $payment Payment.
	 * @return void
	 */
	public function maybe_record_memberpress_transaction_refund( Payment $payment ) {
		if ( 'memberpress_transaction' !== $payment->get_source() ) {
			return;
		}

		// Check if payment has been refunded or charged back.
		$amount_refunded = $payment->get_refunded_amount();

		$amount_charged_back = $payment->get_charged_back_amount();

		if ( $amount_refunded->get_value() <= 0 && null === $amount_charged_back ) {
			return;
		}

		// Update transaction status to 'Refunded'.
		$memberpress_transaction = MemberPress::get_transaction_by_id( $payment->get_source_id() );

		if ( null === $memberpress_transaction ) {
			return;
		}

		if ( MeprTransaction::$refunded_str === $memberpress_transaction->status ) {
			return;
		}

		$memberpress_transaction->status = MeprTransaction::$refunded_str;

		$memberpress_transaction->store();

		MeprUtils::send_refunded_txn_notices( $memberpress_transaction );
	}

	/**
	 * Process transition.
	 *
	 * @param MeprTransaction|MeprSubscription $memberpress_item    Item.
	 * @param Gateway                          $memberpress_gateway Gateway.
	 * @return void
	 */
	private function process_transition( $memberpress_item, Gateway $memberpress_gateway ) {
		/**
		 * Upgrade/downgrade magic.
		 *
		 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprPayPalProGateway.php#L350-L354
		 */
		$is_upgrade   = $memberpress_item->is_upgrade();
		$is_downgrade = $memberpress_item->is_downgrade();

		$event_txn = $memberpress_item->maybe_cancel_old_sub();

		if ( $is_upgrade ) {
			/**
			 * Upgrade subscription.
			 *
			 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L602-L611
			 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprArtificialGateway.php#L109-L122
			 */
			$memberpress_gateway->upgraded_sub( $memberpress_item, $event_txn );
		} elseif ( $is_downgrade ) {
			/**
			 * Downgraded subscription.
			 *
			 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L613-L622
			 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprArtificialGateway.php#L109-L122
			 */
			$memberpress_gateway->downgraded_sub( $memberpress_item, $event_txn );
		} else {
			/**
			 * New subscription.
			 *
			 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L624-L634
			 */
			$memberpress_gateway->new_sub( $memberpress_item );
		}
	}

	/**
	 * MemberPress subscription saved.
	 *
	 * @since 4.2.0
	 * @param MeprSubscription $memberpress_subscription MemberPress subscription.
	 * @return void
	 */
	public function memberpress_subscription_saved( MeprSubscription $memberpress_subscription ) {
		$subscriptions = \get_pronamic_subscriptions_by_source( 'memberpress_subscription', $memberpress_subscription->id );

		if ( empty( $subscriptions ) ) {
			return;
		}

		foreach ( $subscriptions as $subscription ) {
			Pronamic::update_subscription_phases( $subscription, $memberpress_subscription );

			$subscription->save();
		}
	}

	/**
	 * Update lead status of the specified payment.
	 *
	 * @param Payment $payment The payment whose status is updated.
	 * @return void
	 */
	public function status_update( Payment $payment ) {
		$payment_source_id = $payment->get_source_id();

		$memberpress_transaction = MemberPress::get_transaction_by_id( $payment_source_id );

		/**
		 * If we can't find a MemberPress transaction by the payment source ID
		 * we can't update the MemberPress transaction, bail out early.
		 */
		if ( null === $memberpress_transaction ) {
			return;
		}

		/**
		 * We don't update MemberPress transactions that already have the
		 * status 'failed' or 'complete'.
		 */
		if ( MemberPress::transaction_has_status(
			$memberpress_transaction,
			[
				MeprTransaction::$complete_str,
			]
		) ) {
			return;
		}

		/**
		 * Payment method.
		 *
		 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/models/MeprTransaction.php#L634-L637
		 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/models/MeprOptions.php#L798-L811
		 */
		$memberpress_gateway = $memberpress_transaction->payment_method();

		if ( ! $memberpress_gateway instanceof Gateway ) {
			return;
		}

		/**
		 * Ok.
		 */
		switch ( $payment->get_status() ) {
			case PaymentStatus::FAILURE:
			case PaymentStatus::CANCELLED:
			case PaymentStatus::EXPIRED:
				$memberpress_gateway->record_payment_failure();

				$memberpress_transaction->txn_type = MeprTransaction::$payment_str;
				$memberpress_transaction->status   = MeprTransaction::$failed_str;

				$memberpress_transaction->store();

				/**
				 * MemberPress subscription.
				 *
				 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/models/MeprTransaction.php#L605-L620
				 */
				$memberpress_subscription = $memberpress_transaction->subscription();

				if ( $memberpress_subscription instanceof MeprSubscription ) {
					$memberpress_subscription->expire_txns();

					$memberpress_subscription->store();
				}

				/**
				 * Send failed transaction notices.
				 *
				 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprUtils.php#L1515-L1528
				 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprAuthorizeGateway.php#L299
				 */
				MeprUtils::send_failed_txn_notices( $memberpress_transaction );

				break;
			case PaymentStatus::SUCCESS:
				$memberpress_gateway->record_payment();

				$memberpress_transaction->trans_num = $payment->get_transaction_id();
				$memberpress_transaction->txn_type  = MeprTransaction::$payment_str;
				$memberpress_transaction->status    = MeprTransaction::$complete_str;

				$this->process_transition( $memberpress_transaction, $memberpress_gateway );

				$memberpress_transaction->store();

				/**
				 * MemberPress subscription.
				 *
				 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/models/MeprTransaction.php#L605-L620
				 */
				$memberpress_subscription = $memberpress_transaction->subscription();

				if ( $memberpress_subscription instanceof MeprSubscription ) {
					$memberpress_subscription->status = MeprSubscription::$active_str;

					$this->process_transition( $memberpress_subscription, $memberpress_gateway );

					$memberpress_subscription->store();
				}

				/**
				 * Send signup notices.
				 *
				 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprUtils.php#L1361-L1390
				 */
				if ( 'recurring' !== $payment->get_meta( 'mollie_sequence_type' ) ) {
					MeprUtils::send_signup_notices( $memberpress_transaction );
				}

				/**
				 * Send transaction receipt notices.
				 *
				 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprUtils.php#L1396-L1418
				 */
				MeprUtils::send_transaction_receipt_notices( $memberpress_transaction );

				/**
				 * Payment fulfilled.
				 *
				 * @ignore Private action for now.
				 * @param Payment $payment Payment.
				 * @link https://github.com/pronamic/wp-pronamic-pay-mollie/issues/18#issuecomment-1373362874
				 */
				\do_action( 'pronamic_pay_payment_fulfilled', $payment );

				break;
			case PaymentStatus::OPEN:
			default:
				break;
		}
	}

	/**
	 * Perform limit reached actions on subscription completion.
	 *
	 * @param Subscription $pronamic_subscription Subscription.
	 * @return void
	 */
	public function subscription_status_update( Subscription $pronamic_subscription ) {
		// Check status.
		if ( SubscriptionStatus::COMPLETED != $pronamic_subscription->get_status() ) {
			return;
		}

		// Get MemberPress subscription.
		$memberpress_subscription_id = $pronamic_subscription->get_source_id();

		$memberpress_subscription = MemberPress::get_subscription_by_id( $memberpress_subscription_id );

		if ( null === $memberpress_subscription ) {
			return;
		}

		$memberpress_subscription->limit_reached_actions();
	}

	/**
	 * Subscription deleted.
	 *
	 * @param int $subscription_id MemberPress subscription id.
	 * @return void
	 */
	public function subscription_pre_delete( $subscription_id ) {
		$subscription = get_pronamic_subscription_by_meta( '_pronamic_subscription_source_id', (string) $subscription_id );

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
		if ( ! in_array( $subscription->get_status(), [ SubscriptionStatus::CANCELLED, SubscriptionStatus::COMPLETED ], true ) ) {
			$subscription->set_status( SubscriptionStatus::CANCELLED );

			$subscription->save();
		}
	}

	/**
	 * Filter email variables.
	 *
	 * @param string[] $variables Email variables.
	 * @return string[]
	 */
	public function email_variables( $variables ) {
		return \array_merge(
			$variables,
			[
				'pronamic_subscription_id',
				'pronamic_subscription_cancel_url',
				'pronamic_subscription_renewal_url',
				'pronamic_subscription_renewal_date',
			]
		);
	}

	/**
	 * Subscription email parameters.
	 *
	 * @param array<string, string> $params                   Email parameters.
	 * @param MeprSubscription      $memberpress_subscription MemberPress subscription.
	 * @return array<string, string>
	 */
	public function subscription_email_params( $params, MeprSubscription $memberpress_subscription ) {
		$subscriptions = \get_pronamic_subscriptions_by_source( 'memberpress_subscription', $memberpress_subscription->id );

		if ( empty( $subscriptions ) ) {
			return $params;
		}

		$subscription = reset( $subscriptions );

		// Add parameters.
		$next_payment_date = $subscription->get_next_payment_date();

		$date_format = \get_option( 'date_format' );

		if ( ! is_string( $date_format ) ) {
			$date_format = 'D j M Y';
		}

		return \array_merge(
			$params,
			[
				'pronamic_subscription_id'           => (string) $subscription->get_id(),
				'pronamic_subscription_cancel_url'   => $subscription->get_cancel_url(),
				'pronamic_subscription_renewal_url'  => $subscription->get_renewal_url(),
				'pronamic_subscription_renewal_date' => null === $next_payment_date ? '' : \date_i18n( $date_format, $next_payment_date->getTimestamp() ),
			]
		);
	}

	/**
	 * Transaction email parameters.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/helpers/MeprTransactionsHelper.php#L233
	 * @param array<string, string> $params      Parameters.
	 * @param MeprTransaction       $transaction MemberPress transaction.
	 * @return array<string, string>
	 */
	public function transaction_email_params( $params, MeprTransaction $transaction ) {
		// Get payment.
		$payments = \get_pronamic_payments_by_source( 'memberpress_transaction', $transaction->id );

		$payment = \reset( $payments );

		if ( false === $payment ) {
			return $params;
		}

		// Add parameters from subscription.
		$subscriptions = $payment->get_subscriptions();

		foreach ( $subscriptions as $subscription ) {
			$memberpress_subscription = new MeprSubscription( $subscription->get_source_id() );

			return $this->subscription_email_params( $params, $memberpress_subscription );
		}

		return $params;
	}

	/**
	 * Source text.
	 *
	 * @param string  $text    Source text.
	 * @param Payment $payment Payment to create the source text for.
	 *
	 * @return string
	 */
	public function source_text( $text, Payment $payment ) {
		$text = __( 'MemberPress', 'pronamic_ideal' ) . '<br />';

		$text .= sprintf(
			'<a href="%s">%s</a>',
			add_query_arg(
				[
					'page'   => 'memberpress-trans',
					'action' => 'edit',
					'id'     => $payment->source_id,
				],
				admin_url( 'admin.php' )
			),
			/* translators: %s: payment source id */
			sprintf( __( 'Transaction %s', 'pronamic_ideal' ), $payment->source_id )
		);

		return $text;
	}

	/**
	 * Subscription source text.
	 *
	 * @param string       $text         Source text.
	 * @param Subscription $subscription Subscription to create the source text for.
	 *
	 * @return string
	 */
	public function subscription_source_text( $text, Subscription $subscription ) {
		$text = __( 'MemberPress', 'pronamic_ideal' ) . '<br />';

		$text .= sprintf(
			'<a href="%s">%s</a>',
			add_query_arg(
				[
					'page'         => 'memberpress-subscriptions',
					'subscription' => $subscription->source_id,
				],
				admin_url( 'admin.php' )
			),
			/* translators: %s: subscription source */
			sprintf( __( 'Subscription %s', 'pronamic_ideal' ), $subscription->source_id )
		);

		return $text;
	}

	/**
	 * Source description.
	 *
	 * @param string  $description Description.
	 * @param Payment $payment     Payment to create the description for.
	 *
	 * @return string
	 */
	public function source_description( $description, Payment $payment ) {
		return __( 'MemberPress Transaction', 'pronamic_ideal' );
	}

	/**
	 * Subscription source description.
	 *
	 * @param string       $description  Description.
	 * @param Subscription $subscription Subscription to create the description for.
	 *
	 * @return string
	 */
	public function subscription_source_description( $description, Subscription $subscription ) {
		return __( 'MemberPress Subscription', 'pronamic_ideal' );
	}

	/**
	 * Source URL.
	 *
	 * @param string  $url     URL.
	 * @param Payment $payment The payment to create the source URL for.
	 *
	 * @return string
	 */
	public function source_url( $url, Payment $payment ) {
		$url = add_query_arg(
			[
				'page'   => 'memberpress-trans',
				'action' => 'edit',
				'id'     => $payment->source_id,
			],
			admin_url( 'admin.php' )
		);

		return $url;
	}

	/**
	 * Subscription source URL.
	 *
	 * @param string       $url          URL.
	 * @param Subscription $subscription Subscription.
	 *
	 * @return string
	 */
	public function subscription_source_url( $url, Subscription $subscription ) {
		$url = add_query_arg(
			[
				'page'         => 'memberpress-subscriptions',
				'subscription' => $subscription->source_id,
			],
			admin_url( 'admin.php' )
		);

		return $url;
	}

	/**
	 * MemberPress update subscription.
	 *
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/controllers/MeprSubscriptionsCtrl.php#L92-L111
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprSubscription.php#L100-L123
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprSubscription.php#L112
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/models/MeprSubscription.php#L122
	 * @param string           $status_old               Old status identifier.
	 * @param string           $status_new               New status identifier.
	 * @param MeprSubscription $memberpress_subscription MemberPress subscription object.
	 * @return void
	 */
	public function memberpress_subscription_transition_status( $status_old, $status_new, $memberpress_subscription ) {
		$subscription = get_pronamic_subscription_by_meta( '_pronamic_subscription_source_id', $memberpress_subscription->id );

		if ( empty( $subscription ) ) {
			return;
		}

		$status = SubscriptionStatuses::transform( $status_new );

		if ( null === $status ) {
			return;
		}

		$subscription->set_status( $status );

		$subscription->save();
	}
}
