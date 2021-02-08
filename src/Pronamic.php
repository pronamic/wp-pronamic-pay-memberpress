<?php
/**
 * Pronamic
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2020 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use MeprTransaction;
use Pronamic\WordPress\Money\TaxedMoney;
use Pronamic\WordPress\Pay\Address;
use Pronamic\WordPress\Pay\Customer;
use Pronamic\WordPress\Pay\ContactName;
use Pronamic\WordPress\Pay\Core\Util as Core_Util;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Payments\PaymentLines;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionInterval;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionPhase;

/**
 * Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.2.3
 * @since   2.0.5
 */
class Pronamic {
	/**
	 * Get payment method icon URL.
	 *
	 * @param string $method Payment method.
	 * @return string
	 */
	public static function get_method_icon_url( $method ) {
		$method = \str_replace( '_', '-', $method );

		return sprintf( 'https://cdn.wp-pay.org/jsdelivr.net/npm/@wp-pay/logos@1.6.5/dist/methods/%1$s/method-%1$s-640x360.svg', $method );
	}

	/**
	 * Get Pronamic payment from MemberPress transaction.
	 *
	 * @param MeprTransaction $memberpress_transaction MemberPress transaction object.
	 *
	 * @return Payment
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

		$payment->order_id    = $memberpress_transaction->id;
		$payment->title       = $title;
		$payment->description = $memberpress_product->post_title;
		$payment->user_id     = $memberpress_user->ID;
		$payment->source      = 'memberpress';
		$payment->source_id   = $memberpress_transaction->id;
		$payment->issuer      = null;

		// Contact.
		$contact_name = new ContactName();
		$contact_name->set_first_name( $memberpress_user->first_name );
		$contact_name->set_last_name( $memberpress_user->last_name );

		$customer = new Customer();
		$customer->set_name( $contact_name );
		$customer->set_email( $memberpress_user->user_email );
		$customer->set_user_id( $memberpress_user->ID );

		$payment->set_customer( $customer );

		/*
		 * Address.
		 * @link https://github.com/wp-premium/memberpress-business/blob/1.3.36/app/models/MeprUser.php#L1191-L1216
		 */
		$address = new Address();

		$address->set_name( $contact_name );

		$address_fields = array(
			'one'     => 'set_line_1',
			'two'     => 'set_line_2',
			'city'    => 'set_city',
			'state'   => 'set_region',
			'zip'     => 'set_postal_code',
			'country' => 'set_country_code',
		);

		foreach ( $address_fields as $field => $function ) {
			$value = $memberpress_user->address( $field, false );

			if ( empty( $value ) ) {
				continue;
			}

			call_user_func( array( $address, $function ), $value );
		}

		$payment->set_billing_address( $address );
		$payment->set_shipping_address( $address );

		/*
		 * Totals.
		 */
		$payment->set_total_amount(
			new TaxedMoney(
				$memberpress_transaction->total,
				MemberPress::get_currency(),
				$memberpress_transaction->tax_amount,
				$memberpress_transaction->tax_rate
			)
		);

		/*
		 * Vat number.
		 * @link https://github.com/wp-premium/memberpress-business/search?utf8=%E2%9C%93&q=mepr_vat_number&type=
		 * @todo
		 */

		/*
		 * Subscription.
		 * @link https://github.com/wp-premium/memberpress-business/blob/1.3.36/app/models/MeprTransaction.php#L603-L618
		 */
		$payment->subscription = self::get_subscription( $memberpress_transaction );

		if ( $payment->subscription ) {
			$payment->subscription_source_id = $memberpress_transaction->subscription_id;

			$payment->add_period( $payment->subscription->new_period() );

			if ( $memberpress_subscription->in_trial() ) {
				$payment->set_total_amount(
					new TaxedMoney(
						$memberpress_subscription->trial_amount,
						MemberPress::get_currency(),
						null, // Calculate tax value based on tax percentage.
						$memberpress_transaction->tax_rate
					)
				);
			}
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
		$line->set_product_url( get_permalink( $memberpress_product->ID ) );

		/*
		 * Return.
		 */
		return $payment;
	}

	/**
	 * Get Pronamic subscription from MemberPress transaction.
	 *
	 * @param MeprTransaction $memberpress_transaction MemberPress transaction object.
	 *
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

		$start_date = new \DateTimeImmutable();

		// Trial phase.
		if ( $memberpress_subscription->in_trial() ) {
			$trial_phase = new SubscriptionPhase(
				$subscription,
				$start_date,
				new SubscriptionInterval( 'P' . $memberpress_subscription->trial_days . 'D' ),
				new TaxedMoney( $memberpress_subscription->trial_amount, MemberPress::get_currency() )
			);

			$trial_phase->set_total_periods( 1 );
			$trial_phase->set_trial( true );

			$subscription->add_phase( $trial_phase );

			$start_date = $trial_phase->get_end_date();
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
			new TaxedMoney( $memberpress_transaction->total, MemberPress::get_currency() )
		);

		$regular_phase->set_total_periods( $total_periods );

		$subscription->add_phase( $regular_phase );

		return $subscription;
	}
}
