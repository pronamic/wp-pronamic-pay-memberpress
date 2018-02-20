<?php

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Plugin;

/**
 * Title: WordPress pay MemberPress Direct Debit mandate via Bancontact gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Reüel van der Steege
 * @version 1.0.6
 * @since   1.0.6
 */
class DirectDebitBancontactGateway extends Gateway {
	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::DIRECT_DEBIT_BANCONTACT;

	/////////////////////////////////////////////////

	/**
	 * Get icon function, please note that this is not a MemberPress function.
	 *
	 * @since unreleased
	 * @return string
	 */
	protected function get_icon() {
		return plugins_url( 'images/sepa-bancontact/icon-24x24.png', Plugin::$file );
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
