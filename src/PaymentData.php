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
 * WordPress pay MemberPress payment data
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.0.0
 */
class PaymentData extends Pay_PaymentData {
	/**
	 * MemberPress transaction.
	 *
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprTransaction.php
	 *
	 * @var MeprTransaction
	 */
	private $transaction;

	/**
	 * MemberPress transaction user.
	 *
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprTransaction.php#L596-L600
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprUser.php
	 *
	 * @var MeprUser
	 */
	private $user;

	/**
	 * MemberPress transaction subscription.
	 *
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprTransaction.php#L602-L617
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprSubscription.php
	 *
	 * @var MeprUser|false
	 */
	private $subscription;

	/**
	 * Constructs and initialize payment data object.
	 *
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprTransaction.php
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprTransaction.php#L596-L600
	 *
	 * @param MeprTransaction $txn MemberPress transaction object.
	 */
	public function __construct( MeprTransaction $transaction ) {
		parent::__construct();

		$this->transaction  = $transaction;
		$this->user         = $transaction->user();
		$this->subscription = $transaction->subscription();
		$this->recurring    = $this->subscription && $this->subscription->txn_count > 1;
	}

	/**
	 * Get source slug.
	 *
	 * @link https://github.com/pronamic/wp-pronamic-ideal/blob/5.0.0/classes/Payments/AbstractPaymentData.php#L56-L61
	 *
	 * @return string
	 */
	public function get_source() {
		return 'memberpress';
	}

	/**
	 * Get source ID.
	 *
	 * @link https://github.com/pronamic/wp-pronamic-ideal/blob/5.0.0/classes/Payments/AbstractPaymentData.php#L63-L70
	 *
	 * @return string|int
	 */
	public function get_source_id() {
		return $this->transaction->id;
	}

	/**
	 * Get order ID.
	 *
	 * @link https://github.com/pronamic/wp-pronamic-ideal/blob/5.0.0/classes/Payments/AbstractPaymentData.php#L88-L93
	 *
	 * @return string|int
	 */
	public function get_order_id() {
		return $this->transaction->id;
	}

	/**
	 * Get description.
	 *
	 * @link https://github.com/pronamic/wp-pronamic-ideal/blob/5.0.0/classes/Payments/AbstractPaymentData.php#L81-L86
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->transaction->product()->post_title;
	}

	/**
	 * Get items.
	 *
	 * @link https://github.com/pronamic/wp-pronamic-ideal/blob/5.0.0/classes/Payments/AbstractPaymentData.php#L95-L100
	 *
	 * @return Items
	 */
	public function get_items() {
		$items = new Items();

		$item = new Item();
		$item->setNumber( $this->get_order_id() );
		$item->setDescription( $this->get_description() );
		$item->setPrice( $this->transaction->total );
		$item->setQuantity( 1 );

		$items->addItem( $item );

		return $items;
	}

	/**
	 * Get currency alphabetic code.
	 *
	 * @link https://github.com/pronamic/wp-pronamic-ideal/blob/5.0.0/classes/Payments/AbstractPaymentData.php#L213-L218
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprOptions.php#L162-L163
	 *
	 * @return string
	 */
	public function get_currency_alphabetic_code() {
		$mepr_options = MeprOptions::fetch();

		// @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/models/MeprOptions.php#L136-137
		return $mepr_options->currency_code;
	}

	/**
	 * Get email.
	 *
	 * @link https://github.com/wp-premium/memberpress-business/blob/1.2.7/app/models/MeprUser.php#L1103-L1105
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprUser.php#L1229-L1231
	 *
	 * @return string
	 */
	public function get_email() {
		return $this->user->user_email;
	}

	/**
	 * Get first name.
	 *
	 * @link https://github.com/wp-premium/memberpress-business/blob/1.2.7/app/models/MeprUser.php#L316-L319
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprUser.php#L376-L378
	 *
	 * @return string
	 */
	public function get_first_name() {
		return $this->user->first_name;
	}

	/**
	 * Get last name.
	 *
	 * @link https://github.com/wp-premium/memberpress-business/blob/1.2.7/app/models/MeprUser.php#L316-L319
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprUser.php#L376-L378
	 *
	 * @return string
	 */
	public function get_last_name() {
		return $this->user->last_name;
	}

	/**
	 * Get customer name.
	 *
	 * @link https://github.com/wp-premium/memberpress-business/blob/1.2.7/app/models/MeprUser.php#L316-L319
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprUser.php#L376-L378
	 *
	 * @return string
	 */
	public function get_customer_name() {
		return $this->user->get_full_name();
	}

	/**
	 * Get address.
	 *
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprUser.php#L1115-L1140
	 *
	 * @return string|null
	 */
	public function get_address() {
		$value = $this->user->address( 'one', false );

		if ( false === $value ) {
			return null;
		}

		return $value;
	}

	/**
	 * Get city.
	 *
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprUser.php#L1115-L1140
	 *
	 * @return string|null
	 */
	public function get_city() {
		$value = $this->user->address( 'city', false );

		if ( false === $value ) {
			return null;
		}

		return $value;
	}

	/**
	 * Get ZIP.
	 *
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprUser.php#L1115-L1140
	 *
	 * @return string|null
	 */
	public function get_zip() {
		$value = $this->user->address( 'zip', false );

		if ( false === $value ) {
			return null;
		}

		return $value;
	}

	/**
	 * Get country.
	 *
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/models/MeprUser.php#L1115-L1140
	 *
	 * @return string|null
	 */
	public function get_country() {
		$value = $this->user->address( 'country', false );

		if ( false === $value ) {
			return null;
		}

		return $value;
	}

	/**
	 * Get normal return URL.
	 *
	 * @link https://github.com/wp-premium/memberpress-basic/blob/1.3.18/app/gateways/MeprPayPalStandardGateway.php#L1121
	 *
	 * @return string
	 */
	public function get_normal_return_url() {
		$mepr_options = MeprOptions::fetch();

		// @link https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/models/MeprOptions.php#L768-782
		return $mepr_options->thankyou_page_url( 'trans_num=' . $this->transaction->id );
	}

	/**
	 * Get cancel URL.
	 *
	 * @return string
	 */
	public function get_cancel_url() {
		return $this->get_normal_return_url();
	}

	/**
	 * Get success URL.
	 *
	 * @return string
	 */
	public function get_success_url() {
		return $this->get_normal_return_url();
	}

	/**
	 * Get error URL.
	 *
	 * @return string
	 */
	public function get_error_url() {
		return $this->get_normal_return_url();
	}

	/**
	 * Get subscription.
	 *
	 * @since 2.0.0
	 *
	 * @return Subscription|false
	 */
	public function get_subscription() {
		$product = $this->transaction->product();

		if ( $product->is_one_time_payment() ) {
			return false;
		}

		$mp_subscription = $this->transaction->subscription();

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
			$this->transaction->total,
			$this->get_currency_alphabetic_code()
		) );

		return $subscription;
	}

	/**
	 * Get subscription source ID.
	 *
	 * @since  2.0.0
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
