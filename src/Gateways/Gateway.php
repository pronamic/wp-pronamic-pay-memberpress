<?php
/**
 * Gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways;

use MeprBaseRealGateway;
use MeprDb;
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
use Pronamic\WordPress\Pay\Core\Statuses;
use Pronamic\WordPress\Pay\Core\Util as Core_Util;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Plugin;
use Pronamic\WordPress\Pay\Extensions\MemberPress\Pronamic;
use Pronamic\WordPress\Pay\Extensions\MemberPress\MemberPress;
use ReflectionClass;

/**
 * WordPress pay MemberPress gateway
 *
 * @author  Remco Tolsma
 * @version 2.0.4
 * @since   1.0.0
 */
class Gateway extends MeprBaseRealGateway {
	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method;

	/**
	 * MemberPress transaction.
	 *
	 * @var MeprTransaction
	 */
	public $mp_txn;

	/**
	 * Pronamic payment.
	 *
	 * @var Payment
	 */
	public $pronamic_payment;

	/**
	 * Constructs and initialize iDEAL gateway.
	 */
	public function __construct() {
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
	}

	/**
	 * Load the specified settings.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L69-70
	 *
	 * @param array $settings MemberPress gateway settings array.
	 */
	public function load( $settings ) {
		$this->settings = (object) $settings;

		$this->set_defaults();
	}

	/**
	 * Custom helper function to send transaction notices.
	 *
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/lib/MeprUtils.php#L1333-L1351
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprTransaction.php
	 *
	 * @param MeprTransaction $transaction MemberPress transaction object.
	 * @param string          $method      PHP function name to call.
	 *
	 * @return mixed
	 */
	public function send_transaction_notices( $transaction, $method ) {
		$class = 'MeprUtils';

		if ( ! Core_Util::class_method_exists( $class, $method ) ) {
			$class = $this;
		}

		if ( 'MeprUtils' === $class && 'send_product_welcome_notices' === $method ) {
			// `send_product_welcome_notices` is called from `send_signup_notices` in newer versions.
			return;
		}

		return call_user_func( array( $class, $method ), $transaction );
	}

	/**
	 * Get icon function (this is not a MemberPress function).
	 *
	 * @since 1.0.2
	 * @return string
	 */
	protected function get_icon() {
		return '';
	}

	/**
	 * Get class alias name.
	 *
	 * @return string
	 */
	public function get_alias() {
		return 'MeprPronamicGateway';
	}

	/**
	 * Set the default settings.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L72-73
	 */
	protected function set_defaults() {
		if ( ! isset( $this->settings ) ) {
			$this->settings = array();
		}

		$this->settings = (object) array_merge(
			array(
				'gateway'   => $this->get_alias(),
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
	 * @param MeprTransaction $txn MemberPress transaction object.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L119-122
	 */
	public function process_payment( $txn ) {

	}

	/**
	 * Record subscription payment.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L140-145
	 */
	public function record_subscription_payment() {
		$transaction = $this->mp_txn;

		$transaction->status = MeprTransaction::$complete_str;
		$transaction->store();

		$subscription = $transaction->subscription();

		if ( $subscription ) {
			if ( MeprSubscription::$active_str !== $subscription->status ) {
				$subscription->status = MeprSubscription::$active_str;
				$subscription->store();
			}

			$subscription->expire_confirmation_txn();

			$subscription->limit_payment_cycles();
		}

		$this->send_transaction_notices( $transaction, 'send_transaction_receipt_notices' );

		return $transaction;
	}

	/**
	 * Record payment failure.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L147-148
	 */
	public function record_payment_failure() {
		$transaction = $this->mp_txn;

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

		$this->send_transaction_notices( $transaction, 'send_failed_txn_notices' );

		return $transaction;
	}

	/**
	 * Record payment.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L124-129
	 */
	public function record_payment() {
		$transaction = $this->mp_txn;

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

		/*
		 * For some reasons the `send_product_welcome_notices` function accepts 1 or 3 arguments. We are not sure
		 * if this is a difference in the 'Business' and 'Developer' edition or between version `1.2.4` and `1.2.7`.
		 *
		 * @link https://github.com/wp-premium/memberpress-developer/blob/1.2.4/app/lib/MeprBaseGateway.php#L596-L612
		 * @link https://github.com/wp-premium/memberpress-business/blob/1.2.7/app/lib/MeprBaseGateway.php#L609-L619
		 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/models/MeprTransaction.php#L51
		 */
		$reflection = new ReflectionClass( 'MeprBaseRealGateway' );

		if ( $reflection->hasMethod( 'send_product_welcome_notices' ) && 3 === $reflection->getMethod( 'send_product_welcome_notices' )->getNumberOfParameters() ) {
			$uemail = MeprEmailFactory::fetch(
				'MeprUserProductWelcomeEmail',
				'MeprBaseProductEmail',
				array(
					array(
						'product_id' => $transaction->product_id,
					),
				)
			);

			/**
			 * The `send_product_welcome_notices` method is only available in earlier version of MemberPress.
			 *
			 * @scrutinizer ignore-call
			 */
			$this->send_product_welcome_notices(
				$uemail,
				MeprTransactionsHelper::get_email_params( $transaction ),
				$transaction->user()
			);
		} else {
			$this->send_transaction_notices( $transaction, 'send_product_welcome_notices' );
		}

		// Send upgrade/downgrade notices.
		$product = $transaction->product();

		if ( 'lifetime' === $product->period_type ) {
			if ( $upgrade ) {
				$this->upgraded_sub( $transaction, $event_transaction );
				$this->send_transaction_notices( $transaction, 'send_upgraded_txn_notices' );
			} elseif ( $downgrade ) {
				$this->downgraded_sub( $transaction, $event_transaction );
				$this->send_transaction_notices( $transaction, 'send_downgraded_txn_notices' );
			} else {
				$this->new_sub( $transaction );
			}
		}

		$this->send_transaction_notices( $transaction, 'send_signup_notices' );
		$this->send_transaction_notices( $transaction, 'send_transaction_receipt_notices' );

		return $transaction;
	}

	/**
	 * Process refund.
	 *
	 * @param MeprTransaction $txn MemberPress transaction object.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L131-133
	 */
	public function process_refund( MeprTransaction $txn ) {

	}

	/**
	 * Record refund.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L135-138
	 */
	public function record_refund() {

	}

	/**
	 * Process trial payment.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L150-157
	 *
	 * @param MeprTransaction $transaction MemberPress transaction object.
	 */
	public function process_trial_payment( $transaction ) {

	}

	/**
	 * Reord trial payment.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L159-161
	 *
	 * @param MeprTransaction $transaction MemberPress transaction object.
	 */
	public function record_trial_payment( $transaction ) {

	}

	/**
	 * Process create subscription.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L163-167
	 *
	 * @param MeprTransaction $txn MemberPress transaction object.
	 */
	public function process_create_subscription( $txn ) {

	}

	/**
	 * Record create subscription.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L169-174
	 */
	public function record_create_subscription() {

	}

	/**
	 * Process update subscription.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L176
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/lib/MeprBaseGateway.php#L194
	 *
	 * @param int $sub_id Subscription ID.
	 */
	public function process_update_subscription( $sub_id ) {

	}

	/**
	 * Record update subscription.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L178-182
	 */
	public function record_update_subscription() {

	}

	/**
	 * Process suspend subscription.
	 *
	 * @param int $sub_id Subscription id.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L184-186
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
			/* translators: %s: MemberPress */
			__( '%s subscription on hold.', 'pronamic_ideal' ),
			__( 'MemberPress', 'pronamic_ideal' )
		);

		$subscription->add_note( $note );

		// The status of canceled or completed subscriptions will not be changed automatically.
		if ( ! in_array( $subscription->get_status(), array( Statuses::CANCELLED, Statuses::COMPLETED ), true ) ) {
			$subscription->set_status( Statuses::ON_HOLD );

			$subscription->save();
		}
	}

	/**
	 * Record suspend subscription.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L188-191
	 */
	public function record_suspend_subscription() {

	}

	/**
	 * Process resume subscription.
	 *
	 * @param int $sub_id Subscription id.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L193-195
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
			/* translators: %s: MemberPress */
			__( '%s subscription reactivated.', 'pronamic_ideal' ),
			__( 'MemberPress', 'pronamic_ideal' )
		);

		$subscription->add_note( $note );

		// The status of canceled or completed subscriptions will not be changed automatically.
		if ( ! in_array( $subscription->get_status(), array( Statuses::CANCELLED, Statuses::COMPLETED ), true ) ) {
			$subscription->set_status( Statuses::ACTIVE );

			$subscription->save();
		}
	}

	/**
	 * Record resume subscription.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L197-201
	 */
	public function record_resume_subscription() {

	}

	/**
	 * Process cancel subscription.
	 *
	 * @param int $sub_id Subscription id.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L202-206
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

		$sub->status = MeprSubscription::$cancelled_str;

		$sub->store();

		// Expire the grace period (confirmation) if no completed payments have come through.
		if ( (int) $sub->txn_count <= 0 ) {
			$sub->expire_txns();
		}

		$sub->limit_reached_actions();

		// Send cancelled subscription notices.
		MeprUtils::send_cancelled_sub_notices( $sub );

		// Add note.
		$note = sprintf(
			/* translators: %s: MemberPress */
			__( '%s subscription cancelled.', 'pronamic_ideal' ),
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
	 * Record cancel subscription.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L208-212
	 */
	public function record_cancel_subscription() {

	}

	/**
	 * Process signup form.
	 *
	 * Gets called when the signup form is posted used for running any payment
	 * method specific actions when processing the customer signup form.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L214-217
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/controllers/MeprCheckoutCtrl.php#L262
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/lib/MeprBaseGateway.php#L232-L235
	 *
	 * @param MeprTransaction $txn MemberPress transaction object.
	 */
	public function process_signup_form( $txn ) {

	}

	/**
	 * Payment redirect.
	 *
	 * @since 1.0.2
	 *
	 * @param MeprTransaction $txn MemberPress transaction object.
	 */
	public function payment_redirect( $txn ) {
		$txn = new MeprTransaction( $txn->id );

		// Gateway.
		$config_id = $this->settings->config_id;

		$gateway = Plugin::get_gateway( $config_id );

		if ( ! $gateway ) {
			return;
		}

		// Create Pronamic payment.
		$payment = Pronamic::get_payment( $txn );

		$payment->config_id = $this->settings->config_id;
		$payment->method    = $this->payment_method;

		$payment = Plugin::start_payment( $payment );

		/*
		 * Update transaction subtotal.
		 *
		 * Notes:
		 * - MemberPress also uses trial amount for prorated upgrade/downgrade
		 * - Not updated BEFORE payment start, as transaction total amount is used for subscription amount.
		 */
		$subscription = $txn->subscription();

		if ( $subscription && $subscription->in_trial() ) {
			$txn->set_subtotal( $subscription->trial_amount );
			$txn->store();
		}

		$error = $gateway->get_error();

		if ( ! is_wp_error( $error ) ) {
			// Redirect.
			$gateway->redirect( $payment );
		}
	}

	/**
	 * Display payment page.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L219-223
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/controllers/MeprCheckoutCtrl.php#L290
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/gateways/MeprPayPalGateway.php#L775-L850
	 *
	 * @param MeprTransaction $txn MemberPress transaction object.
	 */
	public function display_payment_page( $txn ) {
		// Gateway.
		$config_id = $this->settings->config_id;

		$gateway = Plugin::get_gateway( $config_id );

		if ( $gateway && '' === $gateway->get_input_html() ) {
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
	 * @param  MeprTransaction $txn MemberPress transaction object.
	 * @return bool
	 */
	public function process_payment_form( $txn ) {
		if ( ! filter_has_var( INPUT_POST, 'pronamic_pay_memberpress_pay' ) ) {
			return false;
		}

		// Gateway.
		$config_id = $this->settings->config_id;

		$gateway = Plugin::get_gateway( $config_id );

		if ( $gateway ) {
			$this->payment_redirect( $txn );
		}
	}

	/**
	 * Enqueue payment form scripts.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L219-223
	 */
	public function enqueue_payment_form_scripts() {

	}

	/**
	 * Display payment form.
	 *
	 * This spits out html for the payment form on the registration / payment
	 * page for the user to fill out for payment.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L230-233
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/lib/MeprBaseGateway.php#L248-L251
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/controllers/MeprCheckoutCtrl.php#L318
	 *
	 * @param float    $amount     Transaction amount to create a payment form for.
	 * @param MeprUser $user       MemberPress user object.
	 * @param int      $product_id Product ID.
	 * @param int      $txn_id     Transaction ID.
	 */
	public function display_payment_form( $amount, $user, $product_id, $txn_id ) {
		$product = new MeprProduct( $product_id );

		$coupon = false;

		$txn = new MeprTransaction( $txn_id );

		// Artifically set the price of the $prd in case a coupon was used.
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
				<input type="hidden" name="mepr_transaction_id" value="<?php echo esc_attr( $txn_id ); ?>"/>
				<input type="hidden" name="pronamic_pay_memberpress_pay" value="1"/>

				<div class="mepr_spacer">&nbsp;</div>

				<?php

				// Gateway.
				$config_id = $this->settings->config_id;

				$gateway = Plugin::get_gateway( $config_id );

				if ( $gateway ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $gateway->get_input_html();
				}

				?>

				<div class="mepr_spacer">&nbsp;</div>

				<input type="submit" class="mepr-submit" value="<?php esc_attr_e( 'Pay', 'pronamic_ideal' ); ?>"/>
				<img src="<?php echo esc_attr( admin_url( 'images/loading.gif' ) ); ?>" style="display: none;" class="mepr-loading-gif"/>
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
	 * Validate payment form.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L235-236
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/controllers/MeprCheckoutCtrl.php#L330
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/lib/MeprBaseGateway.php#L253-L254
	 *
	 * @param array $errors Array with errors.
	 * @return array
	 */
	public function validate_payment_form( $errors ) {
		return $errors;
	}

	/**
	 * Display options form.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L291-292
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/gateways/MeprAuthorizeGateway.php#L1027-1037
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
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L294-295
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/gateways/MeprPayPalGateway.php#L909-L924
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprOptions.php#L416-L423
	 *
	 * @param array $errors Array with errors.
	 * @return array
	 */
	public function validate_options_form( $errors ) {
		return $errors;
	}

	/**
	 * Enqueue user account scripts.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L297-302
	 */
	public function enqueue_user_account_scripts() {

	}

	/**
	 * Display update account form.
	 *
	 * @param int    $sub_id  Subscription ID.
	 * @param array  $errors  Array with errors.
	 * @param string $message Update message.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L365-366
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/lib/MeprBaseStaticGateway.php#L160-L161
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/gateways/MeprStripeGateway.php#L1108-L1168
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/controllers/MeprAccountCtrl.php#L388
	 */
	public function display_update_account_form( $sub_id, $errors = array(), $message = '' ) {

	}

	/**
	 * Validate update account form.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L368-369
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/gateways/MeprStripeGateway.php#L1170-L1173
	 *
	 * @param  array $errors Array with errors.
	 * @return array
	 */
	public function validate_update_account_form( $errors = array() ) {
		return $errors;
	}

	/**
	 * Process update account form.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L371-372
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/gateways/MeprStripeGateway.php#L1175-L1181
	 *
	 * @param int $sub_id Subscription ID.
	 */
	public function process_update_account_form( $sub_id ) {

	}

	/**
	 * Is test mode.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L374-375
	 *
	 * @return boolean
	 */
	public function is_test_mode() {
		return false;
	}

	/**
	 * Force SSL.
	 *
	 * @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L377-378
	 *
	 * @return boolean
	 */
	public function force_ssl() {
		return false;
	}
}
