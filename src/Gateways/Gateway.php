<?php
/**
 * Gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways;

use MeprBaseRealGateway;
use MeprGatewayException;
use MeprOptions;
use MeprProduct;
use MeprSubscription;
use MeprTransaction;
use MeprTransactionsHelper;
use MeprUser;
use MeprUtils;
use MeprView;
use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Money\TaxedMoney;
use Pronamic\WordPress\Number\Number;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Extensions\MemberPress\MemberPress;
use Pronamic\WordPress\Pay\Extensions\MemberPress\Pronamic;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Plugin;
use Pronamic\WordPress\Pay\Refunds\Refund;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionStatus;

/**
 * WordPress pay MemberPress gateway
 *
 * @version 3.1.0
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
		$this->set_capabilities();

		// Setup the notification actions for this gateway.
		$this->notifiers = [];

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

		$this->set_capabilities();
	}

	/**
	 * Set capabilities.
	 *
	 * @return void
	 */
	public function set_capabilities() {
		$this->capabilities = [];

		$config_id = $this->get_config_id();

		$gateway = Plugin::get_gateway( (int) $config_id );

		if ( null === $gateway ) {
			return;
		}

		$capabilities = [ 'process-payments' ];

		$payment_method = $gateway->get_payment_method( (string) $this->payment_method );

		if ( null !== $payment_method && $payment_method->supports( 'recurring' ) ) {
			$capabilities = \array_merge(
				$capabilities,
				[
					'create-subscriptions',
					'cancel-subscriptions',
					'update-subscriptions',
					'subscription-trial-payment',
				]
			);
		}

		if ( $gateway->supports( 'refunds' ) ) {
			$capabilities[] = 'process-refunds';
		}

		$this->capabilities = $capabilities;
	}

	/**
	 * Get icon function (this is not a MemberPress function).
	 *
	 * @since 1.0.2
	 * @return string|null
	 */
	protected function get_icon() {
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
			$this->settings = [];
		}

		$this->settings = (object) array_merge(
			[
				'gateway'        => $this->class_alias,
				'id'             => $this->generate_id(),
				'label'          => '',
				'use_label'      => true,
				'icon'           => $this->get_icon(),
				'use_icon'       => true,
				'desc'           => '',
				'use_desc'       => true,
				'config_id'      => '',
				'email'          => '',
				'sandbox'        => false,
				'debug'          => false,
				'minimum_amount' => '',
			],
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
	 * @param MeprTransaction $transaction MemberPress transaction object.
	 * @return void
	 * @throws \Exception Throws exception if gateway only supports one time payments.
	 */
	public function process_payment( $transaction ) {
		// Gateway.
		$config_id = $this->get_config_id();

		$gateway = Plugin::get_gateway( (int) $config_id );

		if ( null === $gateway ) {
			return;
		}

		/**
		 * Recurring product at gateways without recurring support.
		 *
		 * @link https://github.com/pronamic/wp-pronamic-pay-memberpress/issues/6
		 */
		if ( ! $transaction->is_one_time_payment() && ! $gateway->supports( 'recurring' ) ) {
			throw new \Exception( \esc_html__( 'This gateway only supports one time payments.', 'pronamic_ideal' ) );
		}

		/**
		 * Get invoice to get updated transaction total for trial.
		 *
		 * @link https://github.com/pronamic/wp-pronamic-pay-memberpress/issues/13
		 * @link https://github.com/pronamic/wp-pronamic-pay-memberpress/issues/17
		 * @link https://github.com/pronamic/memberpress/blob/v1.11.6/app/helpers/MeprTransactionsHelper.php#L252-L254
		 */
		MeprTransactionsHelper::get_invoice( $transaction );

		$transaction->store();

		// Create Pronamic payment.
		$payment = Pronamic::get_payment( $transaction );

		$this->apply_minimum_amount_to_payment( $payment );

		$payment->config_id = $config_id;

		$payment->set_payment_method( $this->payment_method );

		$payment = Plugin::start_payment( $payment );

		$gateway->redirect( $payment );
	}

	/**
	 * Get payment method.
	 *
	 * @return string|null
	 */
	public function get_payment_method() {
		return $this->payment_method;
	}

	/**
	 * Record subscription payment.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L170-L175
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L587-L714
	 * @return void
	 */
	public function record_subscription_payment() {
	}

	/**
	 * Record payment failure.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L177-L178
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L833-L910
	 * @return void
	 */
	public function record_payment_failure() {
	}

	/**
	 * Record payment.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L154-L159
	 * @return void
	 */
	public function record_payment() {
	}

	/**
	 * Process refund.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L161-L163
	 * @param MeprTransaction $transaction MemberPress transaction object.
	 * @return void
	 * @throws MeprGatewayException Throws MemberPress gateway exception when unable to process refund.
	 */
	public function process_refund( MeprTransaction $transaction ) {
		$payments = \get_pronamic_payments_by_source( 'memberpress_transaction', $transaction->id );

		$payment = reset( $payments );

		if ( false === $payment ) {
			throw new MeprGatewayException( \esc_html__( 'Unable to process refund because payment does not exist.', 'pronamic_ideal' ) );
		}

		try {
			$refund = new Refund( $payment, $payment->get_total_amount() );

			Plugin::create_refund( $refund );

			$transaction->status = MeprTransaction::$refunded_str;

			$transaction->store();

			MeprUtils::send_refunded_txn_notices( $transaction );
		} catch ( \Exception $exception ) {
			throw new MeprGatewayException( \esc_html( $exception->getMessage() ) );
		}

		$this->record_refund();
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
		$this->process_payment( $transaction );
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
	 * @param MeprTransaction $transaction MemberPress transaction object.
	 * @return void
	 */
	public function process_create_subscription( $transaction ) {
		$subscription = $transaction->subscription();

		/**
		 * In the `process_create_subscription` function, every MemberPress
		 * transaction will be linked to a MemberPress subscription, but
		 * just to be sure we check this.
		 *
		 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L312
		 */
		if ( false !== $subscription ) {
			/**
			 * The MemberPress transaction does not contain the
			 * prorated or trial amount and trial expiry date.
			 *
			 * We stole this code from the `MeprArtificialGateway` also
			 * known as the 'Offline Payment' gateway.
			 *
			 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprArtificialGateway.php#L217-L219
			 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L306-L311
			 */
			if ( $subscription->trial ) {
				$transaction->set_subtotal( MeprUtils::format_float( $subscription->trial_amount ) );

				$transaction->expires_at = MeprUtils::ts_to_mysql_date( \time() + MeprUtils::days( $subscription->trial_days ), 'Y-m-d 23:59:59' );

				$transaction->store();
			}
		}

		$this->process_payment( $transaction );
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
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/controllers/MeprAccountCtrl.php#L339-L360
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L214-L216
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L1429-L1459
	 * @param int $subscription_id Subscription id.
	 * @return void
	 */
	public function process_suspend_subscription( $subscription_id ) {
		$memberpress_subscription = MemberPress::get_subscription_by_id( $subscription_id );

		if ( null === $memberpress_subscription ) {
			return;
		}

		/**
		 * If the MemberPress subscription is already suspended we bail out.
		 */
		if ( MeprSubscription::$suspended_str === $memberpress_subscription->status ) {
			return;
		}

		$pronamic_subscription = \get_pronamic_subscription_by_meta( '_pronamic_subscription_source_id', $memberpress_subscription->id );

		if ( ! $pronamic_subscription ) {
			return;
		}

		$memberpress_subscription->status = MeprSubscription::$suspended_str;

		$memberpress_subscription->store();

		// Send suspended subscription notices.
		MeprUtils::send_suspended_sub_notices( $memberpress_subscription );

		$note = sprintf(
			/* translators: %s: extension name */
			__( '%s subscription on hold.', 'pronamic_ideal' ),
			__( 'MemberPress', 'pronamic_ideal' )
		);

		$pronamic_subscription->add_note( $note );

		// The status of canceled or completed subscriptions will not be changed automatically.
		if ( ! in_array( $pronamic_subscription->get_status(), [ SubscriptionStatus::CANCELLED, SubscriptionStatus::COMPLETED ], true ) ) {
			$pronamic_subscription->set_status( SubscriptionStatus::ON_HOLD );

			$pronamic_subscription->save();
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
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/controllers/MeprAccountCtrl.php#L362-L383
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L223-L225
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L1489-L1550
	 * @param int $subscription_id Subscription id.
	 * @return void
	 */
	public function process_resume_subscription( $subscription_id ) {
		$memberpress_subscription = MemberPress::get_subscription_by_id( $subscription_id );

		if ( null === $memberpress_subscription ) {
			return;
		}

		/**
		 * If the MemberPress subscription is already active we bail out.
		 */
		if ( MeprSubscription::$active_str === $memberpress_subscription->status ) {
			return;
		}

		$pronamic_subscription = get_pronamic_subscription_by_meta( '_pronamic_subscription_source_id', $memberpress_subscription->id );

		if ( ! $pronamic_subscription ) {
			return;
		}

		$memberpress_subscription->status = MeprSubscription::$active_str;

		$memberpress_subscription->store();

		/**
		 * If the Pronamic subscription requires a follow-up payment start this.
		 *
		 * @todo
		 */

		// Send resumed subscription notices.
		MeprUtils::send_resumed_sub_notices( $memberpress_subscription );

		// Add note.
		$note = sprintf(
			/* translators: %s: extension name */
			__( '%s subscription reactivated.', 'pronamic_ideal' ),
			__( 'MemberPress', 'pronamic_ideal' )
		);

		$pronamic_subscription->add_note( $note );

		// The status of canceled or completed subscriptions will not be changed automatically.
		if ( ! in_array( $pronamic_subscription->get_status(), [ SubscriptionStatus::CANCELLED, SubscriptionStatus::COMPLETED ], true ) ) {
			$pronamic_subscription->set_status( SubscriptionStatus::ACTIVE );

			$pronamic_subscription->save();
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
	 * @param int $subscription_id Subscription id.
	 * @return void
	 */
	public function process_cancel_subscription( $subscription_id ) {
		$memberpress_subscription = MemberPress::get_subscription_by_id( $subscription_id );

		if ( null === $memberpress_subscription ) {
			return;
		}

		/**
		 * If the MemberPress subscription is already cancelled we bail out.
		 */
		if ( MeprSubscription::$cancelled_str === $memberpress_subscription->status ) {
			return;
		}

		$pronamic_subscription = get_pronamic_subscription_by_meta( '_pronamic_subscription_source_id', $memberpress_subscription->id );

		if ( ! $pronamic_subscription ) {
			return;
		}

		// Add note.
		$note = sprintf(
			/* translators: %s: extension name */
			__( '%s subscription cancelled.', 'pronamic_ideal' ),
			__( 'MemberPress', 'pronamic_ideal' )
		);

		$pronamic_subscription->add_note( $note );

		// The status of canceled or completed subscriptions will not be changed automatically.
		if ( ! in_array( $pronamic_subscription->get_status(), [ SubscriptionStatus::CANCELLED, SubscriptionStatus::COMPLETED ], true ) ) {
			$pronamic_subscription->set_status( SubscriptionStatus::CANCELLED );

			$pronamic_subscription->save();
		}

		// Cancel MemberPress subscription.
		$memberpress_subscription->status = MeprSubscription::$cancelled_str;

		$memberpress_subscription->store();

		// Expire the grace period (confirmation) if no completed payments have come through.
		if ( (int) $memberpress_subscription->txn_count <= 0 ) {
			$memberpress_subscription->expire_txns();
		}

		$memberpress_subscription->limit_reached_actions();

		// Send cancelled subscription notices.
		MeprUtils::send_cancelled_sub_notices( $memberpress_subscription );
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
	 * Display payment page.
	 *
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L249-L253
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprStripeGateway.php#L1766-L1768
	 * @param MeprTransaction $txn MemberPress transaction object.
	 * @return void
	 * @throws \Exception Throws exception on gateway payment start error.
	 */
	public function display_payment_page( $txn ) {
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

		$gateway = Plugin::get_gateway( (int) $config_id );

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

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $this->spc_payment_fields();

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
		$output = '';

		/**
		 * Description.
		 *
		 * @link https://github.com/pronamic/memberpress/blob/3a428785b5a2b6b0581ec4080b98992aef2d5b1a/app/gateways/MeprArtificialGateway.php#L100-L108
		 * @link https://github.com/pronamic/memberpress/blob/3a428785b5a2b6b0581ec4080b98992aef2d5b1a/app/gateways/MeprPayPalStandardGateway.php#L1026-L1036
		 */
		if ( $this->settings->use_desc ) {
			$output = \wpautop( $this->settings->desc );
		}

		// Gateway.
		$config_id = $this->get_config_id();

		$gateway = Plugin::get_gateway( (int) $config_id );

		if ( null === $gateway ) {
			return $output;
		}

		$payment_method = $gateway->get_payment_method( (string) $this->payment_method );

		if ( null === $payment_method ) {
			return $output;
		}

		$fields = $payment_method->get_fields();

		if ( empty( $fields ) ) {
			return $output;
		}

		foreach ( $fields as $field ) {
			try {
				$output .= sprintf(
					'<div class="mp-form-row">
						<div class="mp-form-label">
							<label>%s</label>
						</div>
						%s
					</div>',
					\esc_html( $field->get_label() ),
					$field->render()
				);
			} catch ( \Exception $e ) {
				if ( \current_user_can( 'manage_options' ) ) {
					$output .= sprintf(
						'%s<ul><li>%s</li></ul>',
						\esc_html( __( 'For admins only: an error occurred while retrieving fields for the selected payment method. Please check payment method settings.', 'pronamic_ideal' ) ),
						\esc_html( $e->getMessage() ),
					);
				}

				continue;
			}
		}

		return $output;
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
			<tr>
				<?php

				$name = sprintf(
					'%s[%s][%s]',
					$mepr_options->integrations_str,
					$this->id,
					'minimum_amount'
				);

				?>
				<td>
					<?php esc_html_e( 'Minimum Amount', 'pronamic_ideal' ); ?>
				</td>
				<td>
					<input type="number" step="any" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $this->settings->minimum_amount ); ?>" />
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<label><?php esc_html_e( 'Description', 'pronamic_ideal' ); ?></label><br/>
					<?php

					/**
					 * Follow the MemberPress artificial (Offline Payment) gateway implementation.
					 *
					 * @link https://github.com/pronamic/memberpress/blob/3a428785b5a2b6b0581ec4080b98992aef2d5b1a/app/gateways/MeprArtificialGateway.php#L634-L638
					 */
					$name = sprintf(
						'%s[%s][%s]',
						$mepr_options->integrations_str,
						$this->id,
						'desc'
					);

					?>
					<textarea name="<?php echo \esc_attr( $name ); ?>" rows="3" cols="45"><?php echo \esc_textarea( $this->settings->desc ); ?></textarea>
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
		$mepr_options = MeprOptions::fetch();

		$minimum_amount = isset( $_POST[ $mepr_options->integrations_str ][ $this->id ]['minimum_amount'] ) ? sanitize_text_field( wp_unslash( $_POST[ $mepr_options->integrations_str ][ $this->id ]['minimum_amount'] ) ) : '';

		if ( '' !== $minimum_amount && ! is_numeric( $minimum_amount ) ) {
			$errors[] = __( 'Minimum Amount must be a valid number.', 'pronamic_ideal' );
		}

		if ( '' !== $minimum_amount && $minimum_amount < 0 ) {
			$errors[] = __( 'Minimum Amount must be greater than or equal to zero.', 'pronamic_ideal' );
		}

		return $errors;
	}

	/**
	 * Validate and parse minimum amount.
	 *
	 * @param mixed $value Value to validate.
	 * @return numeric-string|null Returns the validated numeric-string value or null if invalid.
	 */
	private function validate_minimum_amount( $value ) {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		if ( $value <= 0 ) {
			return null;
		}

		return (string) $value;
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
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/controllers/MeprAccountCtrl.php#L438
	 * @param string   $sub_id  Subscription ID.
	 * @param string[] $errors  Array with errors.
	 * @param string   $message Update message.
	 * @return void
	 */
	public function display_update_account_form( $sub_id, $errors = [], $message = '' ) {
		$subscriptions = \get_pronamic_subscriptions_by_source( 'memberpress_subscription', $sub_id );

		$subscription = \reset( $subscriptions );

		$message = \__( 'The payment method for this subscription can not be updated manually.', 'pronamic_ideal' );

		if ( false !== $subscription ) {
			// Set URL to mandate selection URL.
			$url = $subscription->get_mandate_selection_url();

			// Maybe set URL to subscription renewal,
			// to catch up with last failed payment.
			$renewal_period = $subscription->get_renewal_period();

			if ( SubscriptionStatus::ACTIVE !== $subscription->get_status() && null !== $renewal_period ) {
				$url = $subscription->get_renewal_url();
			}

			$message = \sprintf(
				/* translators: %s: mandate selection URL anchor */
				\__( 'To update the payment method for this subscription, please visit the %s page.', 'pronamic_ideal' ),
				\sprintf(
					'<a href="%1$s" title="%2$s">%3$s</a>',
					\esc_url( $url ),
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
	public function validate_update_account_form( $errors = [] ) {
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
	 * Apply minimum amount adjustment to payment.
	 *
	 * @param Payment $payment Pronamic payment object.
	 * @return void
	 */
	protected function apply_minimum_amount_to_payment( Payment $payment ) {
		$minimum_amount = $this->validate_minimum_amount( $this->settings->minimum_amount );

		if ( null === $minimum_amount ) {
			return;
		}

		$current_total = $payment->get_total_amount();

		if ( null === $current_total ) {
			return;
		}

		$current_total_value = $current_total->get_value();

		if ( $current_total_value >= $minimum_amount ) {
			return;
		}

		$adjustment_amount = new TaxedMoney(
			$minimum_amount - $current_total_value,
			$current_total->get_currency(),
			null,
			$current_total instanceof TaxedMoney ? $current_total->get_tax_percentage() : null
		);

		$adjustment_line = $payment->lines->new_line();

		$adjustment_line->set_name( \__( 'Minimum amount adjustment', 'pronamic_ideal' ) );
		$adjustment_line->set_quantity( new Number( 1 ) );
		$adjustment_line->set_unit_price( $adjustment_amount );
		$adjustment_line->set_total_amount( $adjustment_amount );

		$payment->set_total_amount( $payment->lines->get_amount() );
	}

	/**
	 * Get config ID.
	 *
	 * @return int|null
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

		return (int) $config_id;
	}
}
