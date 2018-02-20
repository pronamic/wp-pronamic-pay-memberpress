<?php

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * Title: WordPress pay MemberPress bank transfer gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 1.0.0
 * @since   1.0.0
 */
class BankTransferGateway extends Gateway {
	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::BANK_TRANSFER;

	/////////////////////////////////////////////////

	public function get_alias() {
		return 'MeprBankTransferGateway';
	}
}
