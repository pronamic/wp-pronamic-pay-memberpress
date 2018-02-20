<?php

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Plugin;

/**
 * Title: WordPress pay MemberPress PayPal gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Reüel van der Steege
 * @version 1.0.5
 * @since   1.0.5
 */
class PayPalGateway extends Gateway {
	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::PAYPAL;

	/////////////////////////////////////////////////

	/**
	 * Get icon function, please not that this is not a MemberPress function.
	 *
	 * @since 1.0.2
	 * @return string
	 */
	protected function get_icon() {
		return plugins_url( 'images/paypal/icon-32x32.png', Plugin::$file );
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
