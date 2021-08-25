<?php
/**
 * Gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2021 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways;

use MeprBaseRealGateway;
use MeprEmailFactory;
use MeprOptions;
use MeprProduct;
use MeprSubscription;
use MeprTransaction;
use MeprTransactionsHelper;
use MeprUser;
use MeprUtils;
use MeprView;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Core\Util as Core_Util;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Plugin;
use Pronamic\WordPress\Pay\Extensions\MemberPress\Pronamic;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionStatus;
use ReflectionClass;

/**
 * WordPress pay MemberPress gateway
 *
 * @author  Remco Tolsma
 * @version 2.3.1
 * @since   1.0.0
 */
class Gateway extends MeprBaseRealGateway {
	/**
	 * Payment method.
	 *
	 * @var string|null
	 */
	protected $payment_method;

	/**
	 * Class alias.
	 *
	 * @var string
	 */
	protected $class_alias;

	/**
	 * Key.
	 * 
	 * The key property is not defined in the MemberPress library,
	 * but it is a MemberPress property.
	 * 
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L12
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/helpers/MeprOptionsHelper.php#L192
	 * @var string
	 */
	public $key;

	/**
	 * MemberPress transaction.
	 *
	 * @var MeprTransaction|null
	 */
	private $memberpress_transaction;

	/**
	 * Pronamic payment.
	 *
	 * @var Payment|null
	 */
	private $pronamic_payment;

	/**
	 * Constructs and initialize gateway.
	 * 
	 * @param string      $class_alias    Class alias.
	 * @param string|null $payment_method Payment method.
	 */
	public function __construct( $class_alias = 'MeprPronamicGateway', $payment_method = null ) {
		$this->class_alias = $class_alias;

		$this->payment_method = $payment_method;

		// Set the name of this gateway.
		// @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L12-13.
		$this->name = __( 'Pronamic', 'pronamic_ideal' );

		if ( ! empty( $this->payment_method ) ) {
			$this->name = sprintf(
				/* translators: %s: payment method name */
				__( 'Pronamic - %s', 'pronamic_ideal' ),
				PaymentMethods::get_name( $this->payment_method )
			);
		}

		// Set the default settings.
		// @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L72-73.
		$this->set_defaults();

		// Set the capabilities of this gateway.
		// @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L36-37.
		$this->capabilities = array();

		// Setup the notification actions for this gateway.
		$this->notifiers = array();

		// Support single-page checkout.
		$this->has_spc_form = true;

		// Key.
		$key = 'pronamic_pay';

		if ( null !== $this->payment_method ) {
			$key = sprintf( 'pronamic_pay_%s', $this->payment_method );
		}

		$this->key = $key;
	}

	/**
	 * Load the specified settings.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L73-L74
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprGatewayFactory.php#L18
	 * @param mixed $settings MemberPress gateway settings array.
	 * @return void
	 */
	public function load( $settings ) {
		$this->settings = (object) $settings;

		$this->set_defaults();
	}

	/**
	 * Get icon function (this is not a MemberPress function).
	 *
	 * @since 1.0.2
	 * @return string|null
	 */
	private function get_icon() {
		return PaymentMethods::get_icon_url( $this->payment_method );
	}

	/**
	 * Set the default settings.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L76-L77
	 * @return void
	 */
	protected function set_defaults() {
		if ( ! isset( $this->settings ) ) {
			$this->settings = array();
		}

		$this->settings = (object) array_merge(
			array(
				'gateway'   => $this->class_alias,
				'id'        => $this->generate_id(),
				'label'     => '',
				'use_label' => true,
				'icon'      => $this->get_icon(),
				'use_icon'  => true,
				'desc'      => '',
				'use_desc'  => true,
				'config_id' => '',
				'email'     => '',
				'sandbox'   => false,
				'debug'     => false,
			),
			(array) $this->settings
		);

		$this->id        = $this->settings->id;
		$this->label     = $this->settings->label;
		$this->use_label = $this->settings->use_label;
		$this->icon      = $this->settings->icon;
		$this->use_icon  = $this->settings->use_icon;
		$this->desc      = $this->settings->desc;
		$this->use_desc  = $this->settings->use_desc;
	}

	/**
	 * Process payment.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L149-L152
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L520-L585
	 * @param MeprTransaction $txn MemberPress transaction object.
	 * @return void
	 */
	public function process_payment( $txn ) {

	}

	/**
	 * Set record data.
	 * 
	 * @param Payment         $pronamic_payment        Pronamic payment.
	 * @param MeprTransaction $memberpress_transaction MemberPress transaction.
	 */
	public function set_record_data( $pronamic_payment, $memberpress_transaction ) {
		$this->pronamic_payment        = $pronamic_payment
		$this->memberpress_transaction = $memberpress_transaction;
	}

	/**
	 * Record subscription payment.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L170-L175
	 * @return void
	 */
	public function record_subscription_payment() {
		if ( nulll === $this->pronamic_payment ) {
			return;
		}

		if ( nulll === $this->memberpress_transaction ) {
			return;
		}

		$transaction = $this->memberpress_transaction;

		$transaction->status     = MeprTransaction::$complete_str;
		$transaction->expires_at = MeprUtils::ts_to_mysql_date( $this->pronamic_payment->get_end_date()->getTimestamp(), 'Y-m-d 23:59:59' );
		$transaction->store();

		$subscription = $transaction->subscription();

		if ( $subscription ) {
			$should_activate = ! \in_array(
				$subscription->status,
				array(
					MeprSubscription::$active_str,
					MeprSubscription::$cancelled_str,
				),
				true
			);

			if ( $should_activate ) {
				$subscription->status = MeprSubscription::$active_str;
				$subscription->store();
			}

			$subscription->expire_confirmation_txn();

			$subscription->limit_payment_cycles();
		}

		/**
		 * Send transaction receipt notices.
		 * 
		 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprUtils.php#L1396-L1418
		 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprAuthorizeGateway.php#L249
		 */
		MeprUtils::send_transaction_receipt_notices( $transaction );
	}

	/**
	 * Record payment failure.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L177-L178
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L833-L910
	 * @return void
	 */
	public function record_payment_failure() {
		if ( nulll === $this->pronamic_payment ) {
			return;
		}

		if ( nulll === $this->memberpress_transaction ) {
			return;
		}

		$transaction = $this->memberpress_transaction;

		// @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/models/MeprTransaction.php#L50.
		$transaction->status = MeprTransaction::$failed_str;
		$transaction->store();

		// Expire associated subscription transactions for non-recurring payments.
		if ( ! ( isset( $this->pronamic_payment ) && $this->pronamic_payment->get_recurring() ) ) {
			$subscription = $transaction->subscription();

			if ( $subscription ) {
				$subscription->expire_txns();
				$subscription->store();
			}
		}

		/**
		 * Send failed transaction notices.
		 * 
		 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprUtils.php#L1515-L1528
		 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprAuthorizeGateway.php#L299
		 */
		MeprUtils::send_failed_txn_notices( $transaction );
	}

	/**
	 * Record payment.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L154-L159
	 * @return void
	 */
	public function record_payment() {
		if ( nulll === $this->pronamic_payment ) {
			return;
		}

		if ( nulll === $this->memberpress_transaction ) {
			return;
		}

		$transaction = $this->memberpress_transaction;

		$transaction->status = MeprTransaction::$complete_str;

		// This will only work before maybe_cancel_old_sub is run.
		$upgrade   = $transaction->is_upgrade();
		$downgrade = $transaction->is_downgrade();

		$event_transaction = $transaction->maybe_cancel_old_sub();

		$subscription = $transaction->subscription();

		if ( $subscription ) {
			$event_subscription = $subscription->maybe_cancel_old_sub();

			$subscription->status     = MeprSubscription::$active_str;
			$subscription->created_at = $transaction->created_at;
			$subscription->store();

			if ( false === $event_transaction && false !== $event_subscription ) {
				$event_transaction = $event_subscription;
			}
		}

		$transaction->store();

		// Send upgrade/downgrade notices.
		$product = $transaction->product();

		if ( 'lifetime' === $product->period_type ) {
			if ( $upgrade ) {
				$this->upgraded_sub( $transaction, $event_transaction );
			} elseif ( $downgrade ) {
				$this->downgraded_sub( $transaction, $event_transaction );
			} else {
				$this->new_sub( $transaction );
			}
		}

		/**
		 * Send signup notices.
		 * 
		 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprUtils.php#L1361-L1390
		 */
		MeprUtils::send_signup_notices( $transaction );

		/**
		 * Send transaction receipt notices.
		 * 
		 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprUtils.php#L1396-L1418
		 */
		MeprUtils::send_transaction_receipt_notices( $transaction );
	}

	/**
	 * Process refund.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L161-L163
	 * @param MeprTransaction $txn MemberPress transaction object.
	 * @return void
	 */
	public function process_refund( MeprTransaction $txn ) {

	}

	/**
	 * Record refund.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L165-L168
	 * @return void
	 */
	public function record_refund() {

	}

	/**
	 * Process trial payment.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L180-L187
	 * @param MeprTransaction $transaction MemberPress transaction object.
	 * @return void
	 */
	public function process_trial_payment( $transaction ) {

	}

	/**
	 * Record trial payment.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L189-L191
	 * @param MeprTransaction $transaction MemberPress transaction object.
	 * @return void
	 */
	public function record_trial_payment( $transaction ) {

	}

	/**
	 * Process create subscription.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L193-L197
	 * @param MeprTransaction $txn MemberPress transaction object.
	 * @return void
	 */
	public function process_create_subscription( $txn ) {

	}

	/**
	 * Record create subscription.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L199-L204
	 * @return void
	 */
	public function record_create_subscription() {

	}

	/**
	 * Process update subscription.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L206
	 * @param int $sub_id Subscription ID.
	 * @return void
	 */
	public function process_update_subscription( $sub_id ) {

	}

	/**
	 * Record update subscription.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L208-L212
	 * @return void
	 */
	public function record_update_subscription() {

	}

	/**
	 * Process suspend subscription.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L214-L216
	 * @param int $sub_id Subscription id.
	 * @return void
	 */
	public function process_suspend_subscription( $sub_id ) {
		if ( ! MeprSubscription::exists( $sub_id ) ) {
			return;
		}

		$sub = new MeprSubscription( $sub_id );

		if ( MeprSubscription::$suspended_str === $sub->status ) {
			// Subscription is already suspended.
			return;
		}

		$subscription = get_pronamic_subscription_by_meta( '_pronamic_subscription_source_id', $sub->id );

		if ( ! $subscription ) {
			return;
		}

		$sub->status = MeprSubscription::$suspended_str;

		$sub->store();

		// Send suspended subscription notices.
		MeprUtils::send_suspended_sub_notices( $sub );

		$note = sprintf(
			/* translators: %s: extension name */
			__( '%s subscription on hold.', 'pronamic_ideal' ),
			__( 'MemberPress', 'pronamic_ideal' )
		);

		$subscription->add_note( $note );

		// The status of canceled or completed subscriptions will not be changed automatically.
		if ( ! in_array( $subscription->get_status(), array( SubscriptionStatus::CANCELLED, SubscriptionStatus::COMPLETED ), true ) ) {
			$subscription->set_status( SubscriptionStatus::ON_HOLD );

			$subscription->save();
		}
	}

	/**
	 * Record suspend subscription.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L218-L221
	 * @return void
	 */
	public function record_suspend_subscription() {

	}

	/**
	 * Process resume subscription.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L223-L225
	 * @param int $sub_id Subscription id.
	 * @return void
	 */
	public function process_resume_subscription( $sub_id ) {
		if ( ! MeprSubscription::exists( $sub_id ) ) {
			return;
		}

		$sub = new MeprSubscription( $sub_id );

		if ( MeprSubscription::$active_str === $sub->status ) {
			// Subscription is already active.
			return;
		}

		$subscription = get_pronamic_subscription_by_meta( '_pronamic_subscription_source_id', $sub->id );

		if ( ! $subscription ) {
			return;
		}

		$sub->status = MeprSubscription::$active_str;

		$sub->store();

		// Check if prior txn is expired yet or not, if so create a temporary txn so the user can access the content immediately.
		$prior_txn = $sub->latest_txn();

		if ( false === $prior_txn || ! ( $prior_txn instanceof MeprTransaction ) || strtotime( $prior_txn->expires_at ) < time() ) {
			$txn                  = new MeprTransaction();
			$txn->subscription_id = $sub->id;
			$txn->trans_num       = $sub->subscr_id . '-' . uniqid();
			$txn->status          = MeprTransaction::$confirmed_str;
			$txn->txn_type        = MeprTransaction::$subscription_confirmation_str;
			$txn->response        = (string) $sub;
			$txn->expires_at      = MeprUtils::ts_to_mysql_date( time() + MeprUtils::days( 1 ), 'Y-m-d 23:59:59' );

			$txn->set_subtotal( 0.00 ); // Just a confirmation txn.

			$txn->store();
		}

		// Send resumed subscription notices.
		MeprUtils::send_resumed_sub_notices( $sub );

		// Add note.
		$note = sprintf(
			/* translators: %s: extension name */
			__( '%s subscription reactivated.', 'pronamic_ideal' ),
			__( 'MemberPress', 'pronamic_ideal' )
		);

		$subscription->add_note( $note );

		// The status of canceled or completed subscriptions will not be changed automatically.
		if ( ! in_array( $subscription->get_status(), array( SubscriptionStatus::CANCELLED, SubscriptionStatus::COMPLETED ), true ) ) {
			$subscription->set_status( SubscriptionStatus::ACTIVE );

			$subscription->save();
		}
	}

	/**
	 * Record resume subscription.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L227-L230
	 * @return void
	 */
	public function record_resume_subscription() {

	}

	/**
	 * Process cancel subscription.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L232-L236
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L1687-L1715
	 * @param int $sub_id Subscription id.
	 * @return void
	 */
	public function process_cancel_subscription( $sub_id ) {
		if ( ! MeprSubscription::exists( $sub_id ) ) {
			return;
		}

		$sub = new MeprSubscription( $sub_id );

		if ( MeprSubscription::$cancelled_str === $sub->status ) {
			// Subscription is already cancelled.
			return;
		}

		$subscription = get_pronamic_subscription_by_meta( '_pronamic_subscription_source_id', $sub->id );

		if ( ! $subscription ) {
			return;
		}

		// Add note.
		$note = sprintf(
			/* translators: %s: extension name */
			__( '%s subscription cancelled.', 'pronamic_ideal' ),
			__( 'MemberPress', 'pronamic_ideal' )
		);

		$subscription->add_note( $note );

		// The status of canceled or completed subscriptions will not be changed automatically.
		if ( ! in_array( $subscription->get_status(), array( SubscriptionStatus::CANCELLED, SubscriptionStatus::COMPLETED ), true ) ) {
			$subscription->set_status( SubscriptionStatus::CANCELLED );

			$subscription->next_payment_date          = null;
			$subscription->next_payment_delivery_date = null;

			// Delete next payment post meta.
			$subscription->set_meta( 'next_payment', null );
			$subscription->set_meta( 'next_payment_delivery_date', null );

			$subscription->save();
		}

		// Cancel MemberPress subscription.
		$sub->status = MeprSubscription::$cancelled_str;

		$sub->store();

		// Expire the grace period (confirmation) if no completed payments have come through.
		if ( (int) $sub->txn_count <= 0 ) {
			$sub->expire_txns();
		}

		$sub->limit_reached_actions();

		// Send cancelled subscription notices.
		MeprUtils::send_cancelled_sub_notices( $sub );
	}

	/**
	 * Record cancel subscription.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L238-L242
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L1717-L1753
	 * @return void
	 */
	public function record_cancel_subscription() {

	}

	/**
	 * Process signup form.
	 *
	 * Gets called when the signup form is posted used for running any payment
	 * method specific actions when processing the customer signup form.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L244-L247
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L1755-L1764
	 * @param MeprTransaction $txn MemberPress transaction object.
	 * @return void
	 */
	public function process_signup_form( $txn ) {

	}

	/**
	 * Payment redirect.
	 * 
	 * Note: this is not a MemberPress method.
	 *
	 * @since 1.0.2
	 * @param MeprTransaction $txn MemberPress transaction object.
	 * @return void
	 * @throws \Exception Throws exception on gateway payment start error.
	 */
	private function payment_redirect( $txn ) {
		// Gateway.
		$config_id = $this->get_config_id();

		$gateway = Plugin::get_gateway( $config_id );

		if ( null === $gateway ) {
			return;
		}

		// Create Pronamic payment.
		$txn = new MeprTransaction( $txn->id );

		$payment = Pronamic::get_payment( $txn );

		$payment->config_id = $config_id;
		$payment->method    = $this->payment_method;

		$error = null;

		try {
			$payment = Plugin::start_payment( $payment );
		} catch ( \Exception $e ) {
			$error = $e;
		}

		/*
		 * Update trial transaction.
		 *
		 * Notes:
		 * - MemberPress also uses trial amount for prorated upgrade/downgrade
		 * - Not updated BEFORE payment start, as transaction total amount is used for subscription amount.
		 * - Reload transaction to make sure actual status is being used (i.e. on free downgrade).
		 */
		$txn = new MeprTransaction( $txn->id );

		$subscription = $txn->subscription();

		if ( $subscription && $subscription->in_trial() ) {
			$txn->expires_at = MeprUtils::ts_to_mysql_date( $payment->get_end_date()->getTimestamp(), 'Y-m-d 23:59:59' );

			$txn->set_subtotal( $subscription->trial_amount );
			$txn->store();
		}

		if ( $error instanceof \Exception ) {
			// Rethrow error, caught by MemberPress.
			throw $error;
		}

		// Redirect.
		$gateway->redirect( $payment );
	}

	/**
	 * Display payment page.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L249-L253
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L1766-L1768
	 * @param MeprTransaction $txn MemberPress transaction object.
	 * @return void
	 * @throws \Exception Throws exception on gateway payment start error.
	 */
	public function display_payment_page( $txn ) {
		// Gateway.
		$config_id = $this->get_config_id();

		$gateway = Plugin::get_gateway( $config_id );

		if ( null === $gateway ) {
			return;
		}

		// Redirect payment on empty input HTML.
		$gateway->set_payment_method( $this->payment_method );

		$html = $gateway->get_input_html();

		if ( empty( $html ) ) {
			$this->payment_redirect( $txn );
		}
	}

	/**
	 * Process payment form.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L239-289
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/controllers/MeprCheckoutCtrl.php#L336
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/gateways/MeprPayPalGateway.php#L1011
	 *
	 * @param MeprTransaction $txn MemberPress transaction object.
	 *
	 * @return void
	 * @throws \Exception Throws exception on gateway payment start error.
	 */
	public function process_payment_form( $txn ) {
		// Gateway.
		$config_id = $this->get_config_id();

		$gateway = Plugin::get_gateway( $config_id );

		if ( null === $gateway ) {
			return;
		}

		// Redirect.
		$this->payment_redirect( $txn );
	}

	/**
	 * Enqueue payment form scripts.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L255-L258
	 * @return void
	 */
	public function enqueue_payment_form_scripts() {

	}

	/**
	 * Display payment form.
	 *
	 * This spits out html for the payment form on the registration / payment
	 * page for the user to fill out for payment.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/controllers/MeprCheckoutCtrl.php#L571
	 * @param float    $amount     Transaction amount to create a payment form for.
	 * @param MeprUser $user       MemberPress user object.
	 * @param int      $product_id Product ID.
	 * @param int      $txn_id     Transaction ID.
	 * @return void
	 */
	public function display_payment_form( $amount, $user, $product_id, $txn_id ) {
		// Gateway.
		$config_id = $this->get_config_id();

		$gateway = Plugin::get_gateway( $config_id );

		if ( null === $gateway ) {

			$admin_message = null;

			if ( \current_user_can( 'manage_options' ) ) {
				$admin_message = __( 'For admins only: check payment method settings in MemberPress.', 'pronamic_ideal' );
			}

			printf(
				'<div class="mp_wrapper mp_payment_form_wrapper"><ul><li>%s</li>%s</ul></div>',
				\esc_html( Plugin::get_default_error_message() ),
				null === $admin_message ? '' : sprintf( '<li><em>%s</em></li>', \esc_html( $admin_message ) )
			);

			return;
		}

		// Invoice.
		$product = new MeprProduct( $product_id );

		$coupon = false;

		$txn = new MeprTransaction( $txn_id );

		// Artificially set the price of the $prd in case a coupon was used.
		if ( $product->price !== $amount ) {
			$coupon         = true;
			$product->price = $amount;
		}

		$invoice = MeprTransactionsHelper::get_invoice( $txn );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $invoice;

		?>
		<div class="mp_wrapper mp_payment_form_wrapper">
			<form action="" method="post" id="payment-form" class="mepr-form" novalidate>
				<input type="hidden" name="mepr_process_payment_form" value="Y"/>
				<input type="hidden" name="mepr_transaction_id" value="<?php echo \esc_attr( (string) $txn_id ); ?>"/>
				<input type="hidden" name="pronamic_pay_memberpress_pay" value="1"/>

				<div class="mepr_spacer">&nbsp;</div>

				<?php

				$gateway->set_payment_method( $this->payment_method );

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $gateway->get_input_html();

				?>

				<div class="mepr_spacer">&nbsp;</div>

				<input type="submit" class="mepr-submit" value="<?php esc_attr_e( 'Pay', 'pronamic_ideal' ); ?>"/>
				<img src="<?php echo \esc_url( admin_url( 'images/loading.gif' ) ); ?>" style="display: none;" class="mepr-loading-gif"/>
				<?php MeprView::render( '/shared/has_errors', get_defined_vars() ); ?>

				<noscript>
					<p class="mepr_nojs">
						<?php esc_html_e( 'JavaScript is disabled in your browser. You will not be able to complete your purchase until you either enable JavaScript in your browser, or switch to a browser that supports it.', 'pronamic_ideal' ); ?>
					</p>
				</noscript>
			</form>
		</div>
		<?php
	}

	/**
	 * Single-page checkout payment fields.
	 *
	 * @return string
	 */
	public function spc_payment_fields() {
		// Gateway.
		$config_id = $this->get_config_id();

		$gateway = Plugin::get_gateway( $config_id );

		if ( null === $gateway ) {
			return '';
		}

		// Input HTML.
		$gateway->set_payment_method( $this->payment_method );

		$html = $gateway->get_input_html();

		if ( empty( $html ) ) {
			return '';
		}

		return $html;
	}

	/**
	 * Validate payment form.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/controllers/MeprCheckoutCtrl.php#L648
	 * @param string[] $errors Array with errors.
	 * @return string[]
	 */
	public function validate_payment_form( $errors ) {
		return $errors;
	}

	/**
	 * Display options form.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/views/admin/options/gateway.php#L41
	 * @return void
	 */
	public function display_options_form() {
		$mepr_options = MeprOptions::fetch();

		?>
		<table>
			<tr>
				<?php

				$name = sprintf(
					'%s[%s][%s]',
					$mepr_options->integrations_str,
					$this->id,
					'config_id'
				);

				?>
				<td>
					<?php esc_html_e( 'Configuration', 'pronamic_ideal' ); ?>
				</td>
				<td>
					<select name="<?php echo esc_attr( $name ); ?>">
						<?php

						foreach ( Plugin::get_config_select_options( $this->payment_method ) as $value => $label ) {
							printf(
								'<option value="%s" %s>%s</option>',
								esc_attr( $value ),
								selected( $value, $this->settings->config_id, false ),
								esc_html( $label )
							);
						}

						?>
					</select>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Validate options form.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/models/MeprOptions.php#L468
	 * @ilnk https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L2006-L2026
	 * @param string[] $errors Array with errors.
	 * @return string[]
	 */
	public function validate_options_form( $errors ) {
		return $errors;
	}

	/**
	 * Enqueue user account scripts.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/controllers/MeprAccountCtrl.php#L126
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L2028-L2044
	 * @return void
	 */
	public function enqueue_user_account_scripts() {

	}

	/**
	 * Display update account form.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/controllers/MeprAccountCtrl.php#L423
	 * @param int      $sub_id  Subscription ID.
	 * @param string[] $errors  Array with errors.
	 * @param string   $message Update message.
	 * @return void
	 */
	public function display_update_account_form( $sub_id, $errors = array(), $message = '' ) {
		$subscriptions = \get_pronamic_subscriptions_by_source( 'memberpress', (int) $sub_id );

		$message = \__( 'The payment method for this subscription can not be updated manually.', 'pronamic_ideal' );

		if ( \is_array( $subscriptions ) ) {
			$subscription = \array_shift( $subscriptions );

			$message = \sprintf(
				/* translators: %s: mandate selection URL anchor */
				\__( 'To update the payment method for this subscription, please visit the %s page.', 'pronamic_ideal' ),
				\sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					\esc_url( $subscription->get_mandate_selection_url() ),
					\esc_attr( \__( 'payment method update', 'pronamic_ideal' ) ),
					\esc_html( \__( 'payment method update', 'pronamic_ideal' ) )
				)
			);
		}

		?>

		<h3>
			<?php echo \esc_html( __( 'Update payment method', 'pronamic_ideal' ) ); ?>
		</h3>

		<div>
			<?php echo \wp_kses_post( $message ); ?>
		</div>

		<?php
	}

	/**
	 * Validate update account form.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprAuthorizeGateway.php#L1182-L1197
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L2100-L2103
	 * @param string[] $errors Array with errors.
	 * @return string[]
	 */
	public function validate_update_account_form( $errors = array() ) {
		return $errors;
	}

	/**
	 * Process update account form.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/controllers/MeprAccountCtrl.php#L430
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L2105-L2111
	 * @param int $sub_id Subscription ID.
	 * @return void
	 */
	public function process_update_account_form( $sub_id ) {

	}

	/**
	 * Is test mode.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L374-375
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L2113-L2121
	 * @return boolean
	 */
	public function is_test_mode() {
		return false;
	}

	/**
	 * Force SSL.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L377-378
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L2123-L2125
	 * @return boolean
	 */
	public function force_ssl() {
		return false;
	}

	/**
	 * Get config ID.
	 *
	 * @return string|int|null
	 */
	protected function get_config_id() {
		// Get config ID setting.
		$config_id = $this->settings->config_id;

		// Check empty config ID.
		if ( empty( $config_id ) ) {
			$config_id = \get_option( 'pronamic_pay_config_id' );
		}

		// Check empty config ID.
		if ( empty( $config_id ) ) {
			$config_id = null;
		}

		return $config_id;
	}
}
