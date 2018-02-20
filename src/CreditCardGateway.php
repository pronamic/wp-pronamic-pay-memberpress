<?php

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * Title: WordPress pay MemberPress credit card gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 1.0.4
 * @since   1.0.0
 */
class CreditCardGateway extends Gateway {
	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::CREDIT_CARD;

	/////////////////////////////////////////////////

	/**
	 * Constructs and initialize credit card gateway
	 */
	public function __construct() {
		parent::__construct();

		// Capabilities
		$this->capabilities = array(
			'process-payments',
			//'process-refunds',
			'create-subscriptions',
			'cancel-subscriptions',
			'update-subscriptions',
			'suspend-subscriptions',
			'resume-subscriptions',
			'subscription-trial-payment',
		);
	}

	public function get_alias() {
		return 'MeprCreditCardGateway';
	}
}
