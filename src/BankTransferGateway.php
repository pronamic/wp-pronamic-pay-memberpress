<?php

/**
 * Title: WordPress pay MemberPress bank transfer gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.0.0
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_MemberPress_BankTransferGateway extends Pronamic_WP_Pay_Extensions_MemberPress_Gateway {
	/**
	 * Constructs and initialize iDEAL gateway.
	 */
	public function __construct() {
		parent::__construct();

		// Set the name of this gateway.
		// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L12-13
		$this->name           = __( 'Bank Transfer', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::BANK_TRANSFER;
	}

	public function get_alias() {
		return 'MeprBankTransferGateway';
	}
}
