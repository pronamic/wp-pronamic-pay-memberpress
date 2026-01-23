<?php
/**
 * Gateway test
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress\Gateways;

use PHPUnit_Framework_TestCase;
use Pronamic\WordPress\Money\TaxedMoney;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Payments\PaymentLines;
use ReflectionClass;

/**
 * Gateway test class
 */
class GatewayTest extends PHPUnit_Framework_TestCase {
	/**
	 * Test minimum amount validation - empty setting.
	 */
	public function test_minimum_amount_empty_setting() {
		$gateway = new Gateway();
		
		$gateway->settings     = (object) [ 'minimum_amount' => '' ];
		
		$payment = $this->create_payment( 5.00 );
		
		$reflection = new ReflectionClass( $gateway );
		$method     = $reflection->getMethod( 'apply_minimum_amount_to_payment' );
		$method->setAccessible( true );
		
		$method->invoke( $gateway, $payment );
		
		$this->assertEquals( 5.00, $payment->get_total_amount()->get_value() );
		$this->assertCount( 1, $payment->lines->get_lines() );
	}
	
	/**
	 * Test minimum amount validation - payment above minimum.
	 */
	public function test_minimum_amount_above_minimum() {
		$gateway = new Gateway();
		
		$gateway->settings = (object) [ 'minimum_amount' => '5.00' ];
		
		$payment = $this->create_payment( 10.00 );
		
		$reflection = new ReflectionClass( $gateway );
		$method = $reflection->getMethod( 'apply_minimum_amount_to_payment' );
		$method->setAccessible( true );
		
		$method->invoke( $gateway, $payment );
		
		$this->assertEquals( 10.00, $payment->get_total_amount()->get_value() );
		$this->assertCount( 1, $payment->lines->get_lines() );
	}
	
	/**
	 * Test minimum amount validation - payment below minimum.
	 */
	public function test_minimum_amount_below_minimum() {
		$gateway = new Gateway();
		
		$gateway->settings = (object) [ 'minimum_amount' => '10.00' ];
		
		$payment = $this->create_payment( 5.00 );
		
		$reflection = new ReflectionClass( $gateway );
		$method = $reflection->getMethod( 'apply_minimum_amount_to_payment' );
		$method->setAccessible( true );
		
		$method->invoke( $gateway, $payment );
		
		$this->assertEquals( 10.00, $payment->get_total_amount()->get_value() );
		$this->assertCount( 2, $payment->lines->get_lines() );
		
		$lines = $payment->lines->get_lines();
		$adjustment_line = $lines[1];
		
		$this->assertEquals( 5.00, $adjustment_line->get_total_amount()->get_value() );
	}
	
	/**
	 * Test minimum amount validation - invalid value.
	 */
	public function test_minimum_amount_invalid_value() {
		$gateway = new Gateway();
		
		$gateway->settings = (object) [ 'minimum_amount' => 'invalid' ];
		
		$payment = $this->create_payment( 5.00 );
		
		$reflection = new ReflectionClass( $gateway );
		$method = $reflection->getMethod( 'apply_minimum_amount_to_payment' );
		$method->setAccessible( true );
		
		$method->invoke( $gateway, $payment );
		
		$this->assertEquals( 5.00, $payment->get_total_amount()->get_value() );
		$this->assertCount( 1, $payment->lines->get_lines() );
	}
	
	/**
	 * Test minimum amount validation - negative value.
	 */
	public function test_minimum_amount_negative_value() {
		$gateway = new Gateway();
		
		$gateway->settings = (object) [ 'minimum_amount' => '-5.00' ];
		
		$payment = $this->create_payment( 3.00 );
		
		$reflection = new ReflectionClass( $gateway );
		$method = $reflection->getMethod( 'apply_minimum_amount_to_payment' );
		$method->setAccessible( true );
		
		$method->invoke( $gateway, $payment );
		
		$this->assertEquals( 3.00, $payment->get_total_amount()->get_value() );
		$this->assertCount( 1, $payment->lines->get_lines() );
	}
	
	/**
	 * Test minimum amount validation - payment below minimum with tax.
	 */
	public function test_minimum_amount_below_minimum_with_tax() {
		$gateway = new Gateway();
		
		$gateway->settings = (object) [ 'minimum_amount' => '10.00' ];
		
		$payment = $this->create_payment_with_tax( 5.00, 1.05, 0.21 );
		
		$reflection = new ReflectionClass( $gateway );
		$method = $reflection->getMethod( 'apply_minimum_amount_to_payment' );
		$method->setAccessible( true );
		
		$method->invoke( $gateway, $payment );
		
		// Total should be the minimum amount.
		$this->assertEquals( 10.00, $payment->get_total_amount()->get_value() );
		
		// Tax should be recalculated: original tax (1.05) + adjustment tax (5.00 / 1.21 * 0.21 ≈ 0.87).
		// Total tax ≈ 1.92 (with some floating point variance).
		$expected_tax = 1.05 + ( 5.00 / 1.21 * 0.21 );
		$this->assertEqualsWithDelta( $expected_tax, $payment->get_total_amount()->get_tax_amount()->get_value(), 0.01 );
		
		// Tax rate should remain the same.
		$this->assertEquals( 0.21, $payment->get_total_amount()->get_tax_rate() );
		
		// Should have 2 lines: original + adjustment.
		$this->assertCount( 2, $payment->lines->get_lines() );
		
		// Verify adjustment line has tax applied.
		$lines = $payment->lines->get_lines();
		$adjustment_line = $lines[1];
		$this->assertNotNull( $adjustment_line->get_total_amount()->get_tax_amount() );
		$this->assertEquals( 0.21, $adjustment_line->get_total_amount()->get_tax_rate() );
	}
	
	/**
	 * Create a test payment.
	 *
	 * @param float $amount Payment amount.
	 * @return Payment
	 */
	private function create_payment( $amount ) {
		$payment = new Payment();
		
		$payment->set_total_amount( new TaxedMoney( $amount, 'EUR' ) );
		
		$payment->lines = new PaymentLines();
		$line = $payment->lines->new_line();
		$line->set_total_amount( new TaxedMoney( $amount, 'EUR' ) );
		
		return $payment;
	}
	
	/**
	 * Create a test payment with tax.
	 *
	 * @param float $amount     Payment amount.
	 * @param float $tax_amount Tax amount.
	 * @param float $tax_rate   Tax rate.
	 * @return Payment
	 */
	private function create_payment_with_tax( $amount, $tax_amount, $tax_rate ) {
		$payment = new Payment();
		
		$payment->set_total_amount( new TaxedMoney( $amount, 'EUR', $tax_amount, $tax_rate ) );
		
		$payment->lines = new PaymentLines();
		$line = $payment->lines->new_line();
		$line->set_total_amount( new TaxedMoney( $amount, 'EUR', $tax_amount, $tax_rate ) );
		
		return $payment;
	}
}
