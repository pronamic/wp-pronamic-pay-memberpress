<?php

/**
 * Title: WordPress pay MemberPress gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.0.4
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_MemberPress_Gateway extends MeprBaseRealGateway {
	/**
	 * The payment method
	 *
	 * @var string
	 */
	protected $payment_method;

	/**
	 * Constructs and initialize iDEAL gateway.
	 */
	public function __construct() {
		// Set the name of this gateway.
		// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L12-13
		$this->name = __( 'Pronamic', 'pronamic_ideal' );

		// Set the default settings.
		// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L72-73
		$this->set_defaults();

		// Set the capabilities of this gateway.
		// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L36-37
		$this->capabilities = array();

		// Setup the notification actions for this gateway
		$this->notifiers = array();
	}

	/**
	 * Load the specified settings.
	 *
	 * @param array $settings
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L69-70
	 */
	public function load( $settings ) {
		$this->settings = (object) $settings;

		$this->set_defaults();
	}

	/**
	 * Send MemberPress transaction notices.
	 *
	 * @param $transaction
	 * @param $method
	 *
	 * @return mixed|void
	 */
	public function send_transaction_notices( $transaction, $method ) {
		$class = 'MeprUtils';

		if ( ! Pronamic_WP_Pay_Class::method_exists( $class, $method ) ) {
			$class = $this;
		}

		if ( 'MeprUtils' === $class && 'send_product_welcome_notices' === $method ) {
			// `send_product_welcome_notices` is called from `send_signup_notices` in newer versions.

			return;
		}

		return call_user_func( array( $class, $method ), $transaction );
	}

	/**
	 * Get icon function, please not that this is not a MemberPress function.
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
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L72-73
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
	 * @param MeprTransaction $txn
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L119-122
	 */
	public function process_payment( $txn ) {

	}

	/**
	 * Record subscription payment.
	 *
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L140-145
	 */
	public function record_subscription_payment() {

	}

	/**
	 * Record payment failure.
	 *
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L147-148
	 */
	public function record_payment_failure() {
		global $transaction;

		// @see // @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/models/MeprTransaction.php#L50
		$transaction->status = MeprTransaction::$failed_str;
		$transaction->store();

		$this->send_transaction_notices( $transaction, 'send_failed_txn_notices' );

		return $transaction;
	}

	/**
	 * Record payment.
	 *
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L124-129
	 */
	public function record_payment() {
		global $transaction;

		/*
		 * For some reasons the `send_product_welcome_notices` function accepts 1 or 3 arguments. We are not sure
		 * if this is a difference in the 'Business' and 'Developer' edition or between version `1.2.4` and `1.2.7`.
		 *
		 * @see https://github.com/wp-premium/memberpress-developer/blob/1.2.4/app/lib/MeprBaseGateway.php#L596-L612
		 * @see https://github.com/wp-premium/memberpress-business/blob/1.2.7/app/lib/MeprBaseGateway.php#L609-L619
		 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/models/MeprTransaction.php#L51
		 */
		$transaction->status = MeprTransaction::$complete_str;

		// This will only work before maybe_cancel_old_sub is run
		$upgrade = $transaction->is_upgrade();
		$downgrade = $transaction->is_downgrade();

		$transaction->maybe_cancel_old_sub();
		$transaction->store();

		$subscription = $transaction->subscription();

		if ( $subscription ) {
			$subscription->status     = MeprSubscription::$active_str;
			$subscription->created_at = $transaction->created_at;
			$subscription->store();
		}

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

			$this->send_product_welcome_notices(
				$uemail,
				MeprTransactionsHelper::get_email_params( $transaction ),
				$transaction->user()
			);
		} else {
			$this->send_transaction_notices( $transaction, 'send_product_welcome_notices' );
		}

		// Send upgrade/downgrade notices
		if ( $upgrade ) {
			$this->upgraded_sub( $transaction );
			$this->send_transaction_notices( $transaction, 'send_upgraded_txn_notices' );
		} elseif ( $downgrade ) {
			$this->downgraded_sub( $transaction );
			$this->send_transaction_notices( $transaction, 'send_downgraded_txn_notices' );
		}

		$this->send_transaction_notices( $transaction, 'send_signup_notices' );
		$this->send_transaction_notices( $transaction, 'send_transaction_receipt_notices' );

		return $transaction;
	}

	/**
	 * Process refund.
	 *
	 * @param MeprTransaction $txn
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L131-133
	 */
	public function process_refund( MeprTransaction $txn ) {

	}

	/**
	 * Record refund.
	 *
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L135-138
	 */
	public function record_refund() {

	}

	/**
	 * Process trial payment.
	 *
	 * @param $transaction
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L150-157
	 */
	public function process_trial_payment( $transaction ) {

	}

	/**
	 * Reord trial payment.
	 *
	 * @param $transaction
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L159-161
	 */
	public function record_trial_payment( $transaction ) {

	}

	/**
	 * Process create subscription.
	 *
	 * @param $txn
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L163-167
	 */
	public function process_create_subscription( $txn ) {

	}

	/**
	 * Record create subscription.
	 *
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L169-174
	 */
	public function record_create_subscription() {

	}

	/**
	 * Process update subscription.
	 *
	 * @param int $sub_id
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L176
	 */
	public function process_update_subscription( $sub_id ) {

	}

	/**
	 * Record update subscription.
	 *
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L178-182
	 */
	public function record_update_subscription() {

	}

	/**
	 * Process suspend subscription.
	 *
	 * @param int $sub_id
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L184-186
	 */
	public function process_suspend_subscription( $sub_id ) {

	}

	/**
	 * Record suspend subscription.
	 *
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L188-191
	 */
	public function record_suspend_subscription() {

	}

	/**
	 * Process resume subscription.
	 *
	 * @param int $sub_id
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L193-195
	 */
	public function process_resume_subscription( $sub_id ) {

	}

	/**
	 * Record resume subscription.
	 *
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L197-201
	 */
	public function record_resume_subscription() {

	}

	/**
	 * Process cancel subscription.
	 *
	 * @param int $sub_id
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L202-206
	 */
	public function process_cancel_subscription( $sub_id ) {

	}

	/**
	 * Record cancel subscription.
	 *
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L208-212
	 */
	public function record_cancel_subscription() {

	}

	/**
	 * Process signup form.
	 *
	 * @param $txn
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L214-217
	 */
	public function process_signup_form( $txn ) {

	}

	/**
	 * Payment redirect.
	 *
	 * @since 1.0.2
	 * @param $txn
	 */
	public function payment_redirect( $txn ) {
		$txn = new MeprTransaction( $txn->id );

		// Gateway
		$config_id = $this->settings->config_id;

		$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $config_id );

		if ( $gateway ) {
			// Data
			$data = new Pronamic_WP_Pay_Extensions_MemberPress_PaymentData( $txn );

			$payment = Pronamic_WP_Pay_Plugin::start( $config_id, $gateway, $data, $this->payment_method );

			$error = $gateway->get_error();

			if ( ! is_wp_error( $error ) ) {
				// Redirect
				$gateway->redirect( $payment );
			}
		}
	}

	/**
	 * Display payment page.
	 *
	 * @param $txn
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L219-223
	 */
	public function display_payment_page( $txn ) {
		if ( ! $txn instanceof MeprTransaction ) {
			return false;
		}

		// Gateway
		$config_id = $this->settings->config_id;

		$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $config_id );

		if ( $gateway && '' === $gateway->get_input_html() ) {
			$this->payment_redirect( $txn );
		}
	}

	/**
	 * Process payment form.
	 *
	 * @param $txn
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L239-289
	 */
	public function process_payment_form( $txn ) {
		if ( ! $txn instanceof MeprTransaction ) {
			return false;
		}

		if ( ! filter_has_var( INPUT_POST, 'pronamic_pay_memberpress_pay' ) ) {
			return false;
		}

		// Gateway
		$config_id = $this->settings->config_id;

		$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $config_id );

		if ( $gateway ) {
			$this->payment_redirect( $txn );
		}
	}

	/**
	 * Enqueue payment form scripts.
	 *
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L219-223
	 */
	public function enqueue_payment_form_scripts() {

	}

	/**
	 * Display payment form.
	 *
	 * @param float $amount
	 * @param       $user
	 * @param int   $product_id
	 * @param int   $txn_id
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L230-233
	 */
	public function display_payment_form( $amount, $user, $product_id, $txn_id ) {
		$product = new MeprProduct( $product_id );

		$coupon = false;

		$txn = new MeprTransaction( $txn_id );

		// Artifically set the price of the $prd in case a coupon was used
		if ( $product->price !== $amount ) {
			$coupon         = true;
			$product->price = $amount;
		}

		$invoice = MeprTransactionsHelper::get_invoice( $txn );

		echo $invoice; // WPCS: XSS ok.

		?>
		<div class="mp_wrapper mp_payment_form_wrapper">
			<form action="" method="post" id="payment-form" class="mepr-form" novalidate>
				<input type="hidden" name="mepr_process_payment_form" value="Y" />
				<input type="hidden" name="mepr_transaction_id" value="<?php echo esc_attr( $txn_id ); ?>" />
				<input type="hidden" name="pronamic_pay_memberpress_pay" value="1" />

				<div class="mepr_spacer">&nbsp;</div>

				<?php

				// Gateway
				$config_id = $this->settings->config_id;

				$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $config_id );

				if ( $gateway ) {
					echo $gateway->get_input_html(); // WPCS: XSS ok.
				}

				?>

				<div class="mepr_spacer">&nbsp;</div>

				<input type="submit" class="mepr-submit" value="<?php esc_attr_e( 'Pay', 'pronamic_ideal' ); ?>" />
				<img src="<?php echo esc_attr( admin_url( 'images/loading.gif' ) ); ?>" style="display: none;" class="mepr-loading-gif" />
				<?php MeprView::render( '/shared/has_errors', get_defined_vars() ); ?>

				<noscript><p class="mepr_nojs"><?php esc_html_e( 'JavaScript is disabled in your browser. You will not be able to complete your purchase until you either enable JavaScript in your browser, or switch to a browser that supports it.', 'pronamic_ideal' ); ?></p></noscript>
			</form>
		</div>
		<?php
	}

	/**
	 * Validate payment form.
	 *
	 * @param $errors
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L235-236
	 */
	public function validate_payment_form( $errors ) {

	}

	/**
	 * Display options form.
	 *
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L291-292
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

				// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/gateways/MeprAuthorizeGateway.php#L1027-1037

				?>
				<td>
					<?php esc_html_e( 'Configuration', 'pronamic_ideal' ); ?>
				</td>
				<td>
					<select name="<?php echo esc_attr( $name ); ?>">
						<?php

						foreach ( Pronamic_WP_Pay_Plugin::get_config_select_options( $this->payment_method ) as $value => $label ) {
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
	 * @param $errors
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L294-295
	 */
	public function validate_options_form( $errors ) {
		return $errors;
	}

	/**
	 * Enqueue user account scripts.
	 *
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L297-302
	 */
	public function enqueue_user_account_scripts() {

	}

	/**
	 * Display update account form.
	 *
	 * @param int    $sub_id
	 * @param array  $errors
	 * @param string $message
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L365-366
	 */
	public function display_update_account_form( $sub_id, $errors = array(), $message = '' ) {

	}

	/**
	 * Validate update account form.
	 *
	 * @param array $errors
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L368-369
	 */
	public function validate_update_account_form( $errors = array() ) {
		return $errors;
	}

	/**
	 * Process update account form.
	 *
	 * @param int $sub_id
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L371-372
	 */
	public function process_update_account_form( $sub_id ) {

	}

	/**
	 * Is test mode.
	 *
	 * @return boolean
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L374-375
	 */
	public function is_test_mode() {
		return false;
	}

	/**
	 * Force SSL.
	 *
	 * @return boolean
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L377-378
	 */
	public function force_ssl() {
		return false;
	}
}
