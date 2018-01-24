<?php
use Pronamic\WordPress\Pay\Plugin;

/**
 * Title: WordPress pay MemberPress Direct Debit mandate via Sofort gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.0.6
 * @since 1.0.6
 */
class Pronamic_WP_Pay_Extensions_MemberPress_DirectDebitSofortGateway extends Pronamic_WP_Pay_Extensions_MemberPress_Gateway {
	/**
	 * Constructs and initialize Direct Debit mandate via Sofort gateway.
	 */
	public function __construct() {
		parent::__construct();

		// Set the name of this gateway.
		// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprBaseGateway.php#L12-13
		$this->name           = __( 'Direct Debit (mandate via Sofort)', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::DIRECT_DEBIT_SOFORT;
	}

	/**
	 * Get icon function, please note that this is not a MemberPress function.
	 *
	 * @since unreleased
	 * @return string
	 */
	protected function get_icon() {
		return plugins_url( 'images/icon-24x24.png', Plugin::$file );
	}

	/**
	 * Get class alias name.
	 *
	 * @return string
	 */
	public function get_alias() {
		return 'MeprDirectDebitSofortGateway';
	}
}
