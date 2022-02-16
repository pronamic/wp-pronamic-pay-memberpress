<?php
/**
 * Gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
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
use Pronamic\WordPress\Pay\Extensions\MemberPress\MemberPress;
use Pronamic\WordPress\Pay\Extensions\MemberPress\Pronamic;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionStatus;
use ReflectionClass;

/**
 * WordPress pay MemberPress gateway
 *
 * @author  Remco Tolsma
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
		$capabilities = array();

		// Capabilities.
		$gateway = Plugin::get_gateway( $this->get_config_id() );

		if (
			null !== $gateway
				&&
			$gateway->supports( 'recurring' )
				&&
			(
				PaymentMethods::is_recurring_method( $this->payment_method )
					||
				\in_array( $this->payment_method, PaymentMethods::get_recurring_methods(), true )
			)
		) {
			$capabilities = array(
				'process-payments',
				'create-subscriptions',
				'cancel-subscriptions',
				'update-subscriptions',
				'subscription-trial-payment',
			);
		}

		$this->capabilities = $capabilities;

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
	 * @param MeprTransaction $transaction MemberPress transaction object.
	 * @return void
	 */
	public function process_payment( $transaction ) {
		// Gateway.
		$config_id = $this->get_config_id();

		$gateway = Plugin::get_gateway( $config_id );

		if ( null === $gateway ) {
			return;
		}

		// Create Pronamic payment.
		$payment = Pronamic::get_payment( $transaction );

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
			 * The MemberPress transaction total does not contain the 
			 * prorated or trial amount.
			 * 
			 * We stole this code from the `MeprArtificialGateway` also
			 * known as the 'Offline Payment' gateway.
			 * 
			 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/gateways/MeprArtificialGateway.php#L217
			 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/lib/MeprBaseGateway.php#L306-L311
			 */
			if ( $subscription->trial ) {
				$transaction->set_subtotal( MeprUtils::format_float( $subscription->trial_amount ) );

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
		if ( ! in_array( $pronamic_subscription->get_status(), array( SubscriptionStatus::CANCELLED, SubscriptionStatus::COMPLETED ), true ) ) {
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
		if ( ! in_array( $pronamic_subscription->get_status(), array( SubscriptionStatus::CANCELLED, SubscriptionStatus::COMPLETED ), true ) ) {
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
		if ( ! in_array( $pronamic_subscription->get_status(), array( SubscriptionStatus::CANCELLED, SubscriptionStatus::COMPLETED ), true ) ) {
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
	 * @link https://github.com/wp-premium/memberpress/blob/1.9.21/app/controllers/MeprAccountCtrl.php#L438
	 * @param string   $sub_id  Subscription ID.
	 * @param string[] $errors  Array with errors.
	 * @param string   $message Update message.
	 * @return void
	 */
	public function display_update_account_form( $sub_id, $errors = array(), $message = '' ) {
		$subscriptions = \get_pronamic_subscriptions_by_source( 'memberpress_subscription', $sub_id );

		$subscriptions = ( null === $subscriptions ) ? array() : $subscriptions;

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
