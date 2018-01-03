<?php

/**
 * Title: WordPress pay MemberPress PayPal gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.0.5
 * @since 1.0.5
 */
class Pronamic_WP_Pay_Extensions_MemberPress_PayPalGateway extends Pronamic_WP_Pay_Extensions_MemberPress_Gateway {
	/**
	 * Constructs and initialize PayPal gateway.
	 */
	public function __construct() {
		parent::__construct();

		// Set the name of this gateway.
		// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L12-13
		$this->name           = __( 'PayPal', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::PAYPAL;
	}

	/**
	 * Get icon function, please not that this is not a MemberPress function.
	 *
	 * @since 1.0.2
	 * @return string
	 */
	protected function get_icon() {
		return plugins_url( 'images/paypal/icon-32x32.png', Pronamic_WP_Pay_Plugin::$file );
	}

	/**
	 * Get class alias name.
	 *
	 * @return string
	 */
	public function get_alias() {
		return 'MeprPronamicPayPalGateway';
	}
}
