<?php
/**
 * Subscription statuses test
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2019 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use \MeprSubscription;
use Pronamic\WordPress\Pay\Payments\PaymentStatus;

/**
 * Subscription statuses test
 *
 * @author  Remco Tolsma
 * @version 2.0.1
 * @since   2.0.1
 */
class SubscriptionStatusesTest extends \PHPUnit_Framework_TestCase {
	/**
	 * Test transform.
	 *
	 * @dataProvider status_matrix_provider
	 *
	 * @param string $memberpress_status MemberPress status.
	 * @param string $expected           Expected WordPress Pay status.
	 */
	public function test_transform( $memberpress_status, $expected ) {
		$status = SubscriptionStatuses::transform( $memberpress_status );

		$this->assertEquals( $expected, $status );
	}

	/**
	 * Status matrix provider.
	 *
	 * @return array
	 */
	public function status_matrix_provider() {
		return array(
			array( MeprSubscription::$pending_str, PaymentStatus::OPEN ),
			array( MeprSubscription::$active_str, PaymentStatus::ACTIVE ),
			array( MeprSubscription::$suspended_str, null ),
			array( MeprSubscription::$cancelled_str, PaymentStatus::CANCELLED ),
			array( 'not existing status', null ),
		);
	}
}
