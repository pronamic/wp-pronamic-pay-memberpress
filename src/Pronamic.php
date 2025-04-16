<?php
/**
 * Pronamic
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use MeprSubscription;
use MeprTransaction;
use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Money\TaxedMoney;
use Pronamic\WordPress\Pay\AddressHelper;
use Pronamic\WordPress\Pay\ContactName;
use Pronamic\WordPress\Pay\Core\Util as Core_Util;
use Pronamic\WordPress\Pay\Customer;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Payments\PaymentLines;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionInterval;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionPhase;

/**
 * Pronamic
 *
 * @author  Remco Tolsma
 * @version 4.2.0
 * @since   2.0.5
 */
class Pronamic {
	/**
	 * Get Pronamic payment from MemberPress transaction.
	 *
	 * @param MeprTransaction $memberpress_transaction MemberPress transaction object.
	 * @return Payment
	 * @throws \Exception Throws an exception as soon as no new subscription period can be created.
	 */
	public static function get_payment( MeprTransaction $memberpress_transaction ) {
		$payment = new Payment();

		// MemberPress.
		$memberpress_user         = $memberpress_transaction->user();
		$memberpress_product      = $memberpress_transaction->product();
		$memberpress_subscription = $memberpress_transaction->subscription();

		// Title.
		$title = sprintf(
			/* translators: %s: payment data title */
			__( 'Payment for %s', 'pronamic_ideal' ),
			sprintf(
				/* translators: %s: order id */
				__( 'MemberPress transaction %s', 'pronamic_ideal' ),
				$memberpress_transaction->id
			)
		);

		$payment->order_id  = $memberpress_transaction->id;
		$payment->title     = $title;
		$payment->source    = 'memberpress_transaction';
		$payment->source_id = $memberpress_transaction->id;

		$payment->set_description( $memberpress_product->post_title );

		// Contact.
		$contact_name = new ContactName();
		$contact_name->set_first_name( $memberpress_user->first_name );
		$contact_name->set_last_name( $memberpress_user->last_name );

		$customer = new Customer();
		$customer->set_name( $contact_name );
		$customer->set_email( $memberpress_user->user_email );
		$customer->set_user_id( $memberpress_user->ID );

		$payment->set_customer( $customer );

		/**
		 * Address.
		 *
		 * @link https://github.com/wp-premium/memberpress-business/blob/1.3.36/app/models/MeprUser.php#L1191-L1216
		 */
		$address = AddressHelper::from_array(
			[
				'line_1'       => $memberpress_user->address( 'one', false ),
				'line_2'       => $memberpress_user->address( 'two', false ),
				'postal_code'  => $memberpress_user->address( 'zip', false ),
				'city'         => $memberpress_user->address( 'city', false ),
				'region'       => $memberpress_user->address( 'state', false ),
				'country_code' => $memberpress_user->address( 'country', false ),
				'email'        => $memberpress_user->user_email,
			]
		);

		if ( null !== $address ) {
			$address->set_name( $contact_name );
		}

		$payment->set_billing_address( $address );
		$payment->set_shipping_address( $address );

		/**
		 * Total.
		 */
		$payment->set_total_amount(
			new TaxedMoney(
				$memberpress_transaction->total,
				MemberPress::get_currency(),
				$memberpress_transaction->tax_amount,
				$memberpress_transaction->tax_rate
			)
		);

		/**
		 * Vat number.
		 *
		 * @link https://github.com/wp-premium/memberpress-business/search?utf8=%E2%9C%93&q=mepr_vat_number&type=
		 * @todo
		 */

		/**
		 * Subscription.
		 */
		$subscription = self::get_subscription( $memberpress_transaction );

		if ( $subscription ) {
			$period = $subscription->new_period();

			if ( null === $period ) {
				throw new \Exception( 'Could not create new period for subscription.' );
			}

			$payment->add_period( $period );
		}

		/*
		 * Lines.
		 */
		$payment->lines = new PaymentLines();

		$line = $payment->lines->new_line();

		$line->set_id( $memberpress_product->ID );
		$line->set_name( $memberpress_product->post_title );
		$line->set_quantity( 1 );
		$line->set_unit_price( $payment->get_total_amount() );
		$line->set_total_amount( $payment->get_total_amount() );

		$product_url = \get_permalink( $memberpress_product->ID );

		if ( false !== $product_url ) {
			$line->set_product_url( $product_url );
		}

		/*
		 * Return.
		 */
		return $payment;
	}

	/**
	 * Get Pronamic subscription from MemberPress transaction.
	 *
	 * @param MeprTransaction $memberpress_transaction MemberPress transaction object.
	 * @return Subscription|null
	 */
	public static function get_subscription( MeprTransaction $memberpress_transaction ) {
		$memberpress_product = $memberpress_transaction->product();

		if ( $memberpress_product->is_one_time_payment() ) {
			return null;
		}

		$memberpress_subscription = $memberpress_transaction->subscription();

		if ( ! $memberpress_subscription ) {
			return null;
		}

		/**
		 * Subscription.
		 */
		$subscription = new Subscription();

		self::update_subscription_phases( $subscription, $memberpress_subscription );

		// Source.
		$subscription->source    = 'memberpress_subscription';
		$subscription->source_id = $memberpress_subscription->id;

		return $subscription;
	}

	/**
	 * Update subscription phases from MemberPress subscription.
	 *
	 * @param Subscription     $subscription             Subscription.
	 * @param MeprSubscription $memberpress_subscription MemberPress subscription.
	 * @return void
	 * @throws \Exception Throws exception on invalid MemberPress subscription start date.
	 */
	public static function update_subscription_phases( Subscription $subscription, MeprSubscription $memberpress_subscription ) {
		$start_date = new \DateTimeImmutable( $memberpress_subscription->created_at );

		$memberpress_product = $memberpress_subscription->product();

		$subscription->set_phases( [] );

		// Trial phase.
		if ( $memberpress_subscription->trial && ! empty( $memberpress_subscription->trial_days ) ) {
			/*
			 * We calculate the trial total as the `trial_total` property of a MemberPress subscription can be
			 * incorrectly empty on manual subscription updates even though a trial amount has been set in the subscription.
			 */
			$trial_amount     = new Money( $memberpress_subscription->trial_amount );
			$trial_tax_amount = new Money( $memberpress_subscription->trial_tax_amount );
			$trial_total      = $trial_amount->add( $trial_tax_amount );

			$trial_phase = new SubscriptionPhase(
				$subscription,
				$start_date,
				new SubscriptionInterval( 'P' . $memberpress_subscription->trial_days . 'D' ),
				new TaxedMoney(
					$trial_total->get_value(),
					MemberPress::get_currency(),
					$memberpress_subscription->trial_tax_amount,
					$memberpress_subscription->tax_rate
				)
			);

			$trial_phase->set_total_periods( 1 );
			$trial_phase->set_trial( true );

			$subscription->add_phase( $trial_phase );

			$trial_end_date = $trial_phase->get_end_date();

			if ( null !== $trial_end_date ) {
				$start_date = $trial_end_date;
			}
		}

		// Total periods.
		$total_periods = null;

		$limit_cycles_number = (int) $memberpress_subscription->limit_cycles_num;

		if ( $memberpress_subscription->limit_cycles && $limit_cycles_number > 0 ) {
			$total_periods = $limit_cycles_number;
		}

		// Regular phase.
		$regular_phase = new SubscriptionPhase(
			$subscription,
			$start_date,
			new SubscriptionInterval( 'P' . $memberpress_product->period . Core_Util::to_period( $memberpress_product->period_type ) ),
			new Money( $memberpress_subscription->total, MemberPress::get_currency() )
		);

		$regular_phase->set_total_periods( $total_periods );

		$subscription->add_phase( $regular_phase );
	}
}
