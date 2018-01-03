<?php

/**
 * Title: WordPress pay MemberPress Direct Debit mandate via Bancontact gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.0.6
 * @since 1.0.6
 */
class Pronamic_WP_Pay_Extensions_MemberPress_DirectDebitBancontactGateway extends Pronamic_WP_Pay_Extensions_MemberPress_Gateway {
	/**
	 * Constructs and initialize Direct Debit mandate via Bancontact gateway.
	 */
	public function __construct() {
		parent::__construct();

		// Set the name of this gateway.
		// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L12-13
		$this->name           = __( 'Direct Debit (mandate via Bancontact)', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::DIRECT_DEBIT_BANCONTACT;
	}

	/**
	 * Get icon function, please note that this is not a MemberPress function.
	 *
	 * @since unreleased
	 * @return string
	 */
	protected function get_icon() {
		return plugins_url( 'images/icon-24x24.png', Pronamic_WP_Pay_Plugin::$file );
	}

	/**
	 * Get class alias name.
	 *
	 * @return string
	 */
	public function get_alias() {
		return 'MeprDirectDebitBancontactGateway';
	}
}
