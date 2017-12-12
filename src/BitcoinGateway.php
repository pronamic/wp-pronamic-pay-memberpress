<?php

/**
 * Title: WordPress pay MemberPress Bitcoin gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.0.5
 * @since 1.0.5
 */
class Pronamic_WP_Pay_Extensions_MemberPress_BitcoinGateway extends Pronamic_WP_Pay_Extensions_MemberPress_Gateway {
	/**
	 * Constructs and initialize Bitcoin gateway.
	 */
	public function __construct() {
		parent::__construct();

		// Set the name of this gateway.
		// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L12-13
		$this->name           = __( 'Bitcoin', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::BITCOIN;
	}

	public function get_alias() {
		return 'MeprBitcoinGateway';
	}
}
