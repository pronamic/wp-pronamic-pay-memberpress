<?php

/**
 * Title: WordPress pay MemberPress Bancontact/Mister Cash gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2015
 * Company: Pronamic
 * @author Remco Tolsma
 * @version 1.0.0
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_MemberPress_MisterCashGateway extends Pronamic_WP_Pay_Extensions_MemberPress_Gateway {
	/**
	 * Constructs and initialize iDEAL gateway.
	 */
	public function __construct() {
		parent::__construct();

		// Set the name of this gateway.
		// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L12-13
		$this->name           = __( 'Bancontact/Mister Cash', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::MISTER_CASH;
	}

	public function get_alias() {
		return 'MeprMisterCashGateway';
	}
}
