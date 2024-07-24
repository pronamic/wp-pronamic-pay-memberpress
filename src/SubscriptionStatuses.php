<?php
/**
 * Subscription statuses
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use MeprSubscription;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionStatus;

/**
 * Subscription statuses
 *
 * @author  Remco Tolsma
 * @version 3.1.0
 * @since   2.0.1
 */
class SubscriptionStatuses {
	/**
	 * Transform a MemberPress subscription status to a WordPress Pay subscription status.
	 *
	 * @link https://github.com/wp-premium/memberpress-basic/blob/master/app/models/MeprSubscription.php#L5-L9
	 *
	 * @param string $status MemberPress subscription status value.
	 *
	 * @return string|null
	 */
	public static function transform( $status ) {
		switch ( $status ) {
			case MeprSubscription::$pending_str:
				return SubscriptionStatus::OPEN;
			case MeprSubscription::$active_str:
				return SubscriptionStatus::ACTIVE;
			case MeprSubscription::$suspended_str:
				// @todo set to 'On hold'?
				return null;
			case MeprSubscription::$cancelled_str:
				return SubscriptionStatus::CANCELLED;
		}

		return null;
	}
}
