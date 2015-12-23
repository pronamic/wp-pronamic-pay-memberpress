<?php

/**
 * Title: WordPress pay MemberPress iDEAL gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2015
 * Company: Pronamic
 * @author Remco Tolsma
 * @version 1.0.0
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_MemberPress_IDealGateway extends MeprBaseRealGateway {
	/**
	 * Constructs and initialize iDEAL gateway.
	 */
	public function __construct() {
		// Set the name of this gateway.
		// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L12-13
		$this->name = __( 'iDEAL', 'pronamic_ideal' );

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
				'gateway'   => 'MeprIDealGateway',
				'id'        => $this->generate_id(),
				'label'     => '',
				'use_label' => true,
				'icon'      => '',
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
		if ( isset( $txn ) && $txn instanceof MeprTransaction ) {
			$usr = new MeprUser( $txn->user_id );
			$prd = new MeprProduct( $txn->product_id );
		} else {
			return;
		}

		$upgrade   = $txn->is_upgrade();
		$downgrade = $txn->is_downgrade();

		$txn->maybe_cancel_old_sub();

		if ( $upgrade ) {
			$this->upgraded_sub( $txn );
			$this->send_upgraded_txn_notices( $txn );
		} elseif ( $downgrade ) {
			$this->downgraded_sub( $txn );
			$this->send_downgraded_txn_notices( $txn );
		} else {
			$this->new_sub( $txn );
		}

		$txn->gateway   = $this->id;
		$txn->trans_num = 't_' . uniqid();

		$txn->store();

		$this->send_product_welcome_notices( $txn );
		$this->send_signup_notices( $txn );

		return $txn;
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

	}

	/**
	 * Record payment.
	 *
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L124-129
	 */
	public function record_payment() {

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
		if ( isset( $txn ) && $txn instanceof MeprTransaction ) {
			$usr = new MeprUser( $txn->user_id );

			$prd = new MeprProduct( $txn->product_id );
		} else {
			return;
		}

		$sub = $txn->subscription();

		// Not super thrilled about this but there are literally
		// no automated recurring profiles when paying offline
		$sub->subscr_id  = 'ts_' . uniqid();
		$sub->status     = MeprSubscription::$active_str;
		$sub->created_at = date( 'c' );
		$sub->gateway    = $this->id;

		// If this subscription has a paid trail, we need to change the price of this transaction to the trial price duh
		if ( $sub->trial ) {
			$txn->set_subtotal( MeprUtils::format_float( $sub->trial_amount ) );

			$expires_ts = time() + MeprUtils::days( $sub->trial_days );

			$txn->expires_at = date( 'c', $expires_ts );
		}

		// This will only work before maybe_cancel_old_sub is run
		$upgrade   = $sub->is_upgrade();
		$downgrade = $sub->is_downgrade();

		$sub->maybe_cancel_old_sub();

		if ( $upgrade ) {
			$this->upgraded_sub( $sub );
			$this->send_upgraded_sub_notices( $sub );
		} elseif ( $downgrade ) {
			$this->downgraded_sub( $sub );
			$this->send_downgraded_sub_notices( $sub );
		} else {
			$this->new_sub( $sub );
			$this->send_new_sub_notices( $sub );
		}

		$sub->store();

		$txn->gateway = $this->id;
		$txn->trans_num = 't_' . uniqid();

		$txn->store();

		$this->send_product_welcome_notices( $txn );
		$this->send_signup_notices( $txn );

		return array(
			'subscription' => $sub,
			'transaction'  => $txn,
		);
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
	 * Display payment page.
	 *
	 * @param $txn
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L219-223
	 */
	public function display_payment_page( $txn ) {

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
		$mepr_options = MeprOptions::fetch();

		$prd = new MeprProduct( $product_id );

		$coupon = false;

		$txn = new MeprTransaction( $txn_id );

		// Artifically set the price of the $prd in case a coupon was used
		if ( $prd->price !== $amount ) {
			$coupon = true;

			$prd->price = $amount;
		}

		$invoice = MeprTransactionsHelper::get_invoice( $txn );

		echo $invoice; // WPCS: XSS ok.

		?>
		<div class="mp_wrapper">
			<form action="" method="post" id="payment-form" class="mepr-form" novalidate>
				<input type="hidden" name="mepr_process_payment_form" value="Y" />
				<input type="hidden" name="mepr_transaction_id" value="<?php echo esc_attr( $txn_id ); ?>" />

				<div class="mepr_spacer">&nbsp;</div>

				<input type="submit" class="mepr-submit" value="<?php esc_attr_e( 'Submit', 'pronamic_ideal' ); ?>" />

				<?php MeprView::render( '/shared/has_errors', get_defined_vars() ); ?>
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

						foreach ( Pronamic_WP_Pay_Plugin::get_config_select_options() as $value => $label ) {
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
