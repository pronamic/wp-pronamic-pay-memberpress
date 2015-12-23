<?php

/**
 * Title: WordPress pay MemberPress payment data
 * Description:
 * Copyright: Copyright (c) 2005 - 2015
 * Company: Pronamic
 * @author Remco Tolsma
 * @version 1.0.0
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_MemberPress_PaymentData extends Pronamic_WP_Pay_PaymentData {
	/**
	 * Constructs and initialize payment data object.
	 */
	public function __construct() {
		parent::__construct();

		$this->test = false;
	}

	//////////////////////////////////////////////////

	public function get_source() {
		return 'memberpress';
	}

	public function get_order_id() {

	}

	public function get_description() {

	}

	public function get_items() {
		$items = new Pronamic_IDeal_Items();

		$item = new Pronamic_IDeal_Item();
		$item->setNumber( $this->get_order_id() );
		$item->setDescription( $this->get_description() );
		$item->setPrice( 0 );
		$item->setQuantity( 1 );

		$items->addItem( $item );

		return $items;
	}

	//////////////////////////////////////////////////
	// Currency
	//////////////////////////////////////////////////

	public function get_currency_alphabetic_code() {

	}

	//////////////////////////////////////////////////
	// Customer
	//////////////////////////////////////////////////

	public function get_email() {

	}

	public function getCustomerName() {

	}

	public function getOwnerAddress() {
		return '';
	}

	public function getOwnerCity() {
		return '';
	}

	public function getOwnerZip() {
		return '';
	}

	//////////////////

	public function get_normal_return_url() {

	}

	public function get_cancel_url() {

	}

	public function get_success_url() {

	}

	public function get_error_url() {

	}
}
