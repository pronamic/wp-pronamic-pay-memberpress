<?php

/**
 * Title: WordPress pay MemberPress payment data
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.0.2
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_MemberPress_PaymentData extends Pronamic_WP_Pay_PaymentData {
	/**
	 * Constructs and initialize payment data object.
	 */
	public function __construct( MeprTransaction $txn ) {
		parent::__construct();

		$this->amount  = $txn->total;
		$this->user    = $txn->user();
		$this->product = $txn->product();
		$this->txn_id  = $txn->id;
	}

	//////////////////////////////////////////////////

	public function get_source() {
		return 'memberpress';
	}

	public function get_source_id() {
		return $this->txn_id;
	}

	public function get_order_id() {
		return $this->txn_id;
	}

	public function get_description() {
		return $this->product->post_title;
	}

	public function get_items() {
		$items = new Pronamic_IDeal_Items();

		$item = new Pronamic_IDeal_Item();
		$item->setNumber( $this->get_order_id() );
		$item->setDescription( $this->get_description() );
		$item->setPrice( $this->amount );
		$item->setQuantity( 1 );

		$items->addItem( $item );

		return $items;
	}

	//////////////////////////////////////////////////
	// Currency
	//////////////////////////////////////////////////

	public function get_currency_alphabetic_code() {
		$mepr_options = MeprOptions::fetch();

		// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/models/MeprOptions.php#L136-137
		return $mepr_options->currency_code;
	}

	//////////////////////////////////////////////////
	// Customer
	//////////////////////////////////////////////////

	public function get_email() {
		return $this->user->user_email;
	}

	public function get_customer_name() {
		return '';
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

	//////////////////

	public function get_normal_return_url() {
		$mepr_options = MeprOptions::fetch();

		// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/models/MeprOptions.php#L768-782
		return $mepr_options->thankyou_page_url( 'trans_num=' . $this->txn_id );
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
}
