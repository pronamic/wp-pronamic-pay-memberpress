<?php
/**
 * Payment data
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2018 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use MeprOptions;
use MeprTransaction;
use MeprUser;
use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Pay\Core\Util as Core_Util;
use Pronamic\WordPress\Pay\Payments\PaymentData as Pay_PaymentData;
use Pronamic\WordPress\Pay\Payments\Item;
use Pronamic\WordPress\Pay\Payments\Items;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;

/**
 * Title: WordPress pay MemberPress payment data
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.0.0
 */
class PaymentData extends Pay_PaymentData {
	/**
	 * MemberPress transaction.
	 *
	 * @var MeprTransaction
	 */
	private $txn;

	/**
	 * MemberPress transaction user.
	 *
	 * @var MeprUser
	 */
	private $member;

	/**
	 * Constructs and initialize payment data object.
	 */
	public function __construct( MeprTransaction $txn ) {
		parent::__construct();

		$this->txn       = $txn;
		$this->member    = $this->txn->user();
		$this->recurring = ( $txn->subscription() && $txn->subscription()->txn_count > 1 );
	}

	public function get_source() {
		return 'memberpress';
	}

	public function get_source_id() {
		return $this->txn->id;
	}

	public function get_order_id() {
		return $this->txn->id;
	}

	public function get_description() {
		return $this->txn->product()->post_title;
	}

	public function get_items() {
		$items = new Items();

		$item = new Item();
		$item->setNumber( $this->get_order_id() );
		$item->setDescription( $this->get_description() );
		$item->setPrice( $this->txn->total );
		$item->setQuantity( 1 );

		$items->addItem( $item );

		return $items;
	}

	public function get_currency_alphabetic_code() {
		$mepr_options = MeprOptions::fetch();

		// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/models/MeprOptions.php#L136-137
		return $mepr_options->currency_code;
	}

	public function get_email() {
		if ( $this->member instanceof MeprUser ) {
			return $this->member->user_email;
		}
	}

	public function get_first_name() {
		if ( $this->member instanceof MeprUser ) {
			return $this->member->first_name;
		}
	}

	public function get_last_name() {
		if ( $this->member instanceof MeprUser ) {
			return $this->member->last_name;
		}
	}

	public function get_customer_name() {
		if ( $this->member instanceof MeprUser ) {
			return $this->member->get_full_name();
		}
	}

	public function get_address() {
		return '';
	}

	public function get_city() {
		return '';
	}

	public function get_zip() {
		return '';
	}

	public function get_normal_return_url() {
		$mepr_options = MeprOptions::fetch();

		// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/models/MeprOptions.php#L768-782
		return $mepr_options->thankyou_page_url( 'trans_num=' . $this->txn->id );
	}

	public function get_cancel_url() {
		return $this->get_normal_return_url();
	}

	public function get_success_url() {
		return $this->get_normal_return_url();
	}

	public function get_error_url() {
		return $this->get_normal_return_url();
	}

	/**
	 * Get subscription.
	 *
	 * @since 2.0.0
	 *
	 * @return Subscription|bool
	 */
	public function get_subscription() {
		$product = $this->txn->product();

		if ( $product->is_one_time_payment() ) {
			return false;
		}

		$mp_subscription = $this->txn->subscription();

		if ( ! $mp_subscription ) {
			return false;
		}

		$frequency = '';

		if ( $mp_subscription->limit_cycles ) {
			$frequency = $mp_subscription->limit_cycles;
		}

		$subscription                  = new Subscription();
		$subscription->frequency       = $frequency;
		$subscription->interval        = $product->period;
		$subscription->interval_period = Core_Util::to_period( $product->period_type );
		$subscription->description     = sprintf(
			'Order #%s - %s',
			$this->get_source_id(),
			$this->get_description()
		);

		$subscription->set_amount( new Money(
			$this->txn->total,
			$this->get_currency_alphabetic_code()
		) );

		return $subscription;
	}

	/**
	 * Get subscription source ID.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_subscription_source_id() {
		$subscription = $this->get_subscription();

		if ( ! $subscription ) {
			return false;
		}

		return $this->get_source_id();
	}
}
