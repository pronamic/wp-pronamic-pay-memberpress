<?php

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use PHPUnit_Framework_TestCase;

/**
 * Class MemberPressTest.
 *
 * @package Pronamic\WordPress\Pay\Extensions\MemberPress
 */
class MemberPressTest extends PHPUnit_Framework_TestCase {
	/**
	 * Test class.
	 */
	public function test_class() {
		$this->assertTrue( class_exists( __NAMESPACE__ . '\MemberPress' ) );
	}
}
