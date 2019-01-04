<?php
/**
 * Subscription statuses
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use MeprSubscription;
use Pronamic\WordPress\Pay\Core\Statuses;

/**
 * Subscription statuses
 *
 * @author  Remco Tolsma
 * @version 2.0.1
 * @since   2.0.1
 */
class SubscriptionStatuses {
	/**
	 * Transform a MemberPress subscription status to a WordPress Pay subscription status.
	 *
	 * @link https://github.com/wp-premium/memberpress-basic/blob/master/app/models/MeprSubscription.php#L5-L9
	 *
	 * @param string $status MemberPress subscription status value.
	 */
	public static function transform( $status ) {
		switch ( $status ) {
			case MeprSubscription::$pending_str:
				return Statuses::OPEN;
			case MeprSubscription::$active_str:
				return Statuses::ACTIVE;
			case MeprSubscription::$suspended_str:
				return null;
			case MeprSubscription::$cancelled_str:
				return Statuses::CANCELLED;
		}
	}
}
