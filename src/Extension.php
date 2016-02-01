<?php

/**
 * Title: WordPress pay MemberPress extension
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.0.0
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_MemberPress_Extension {
	/**
	 * The slug of this addon
	 *
	 * @var string
	 */
	const SLUG = 'memberpress';

	//////////////////////////////////////////////////

	/**
	 * Bootstrap
	 */
	public static function bootstrap() {
		new self();
	}

	/**
	 * Constructs and initializes the MemberPress extension.
	 */
	public function __construct() {
		// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprGatewayFactory.php#L48-50
		add_filter( 'mepr-gateway-paths', array( $this, 'gateway_paths' ) );

		add_action( 'pronamic_payment_status_update_' . self::SLUG, array( __CLASS__, 'status_update' ), 10, 2 );
		add_filter( 'pronamic_payment_source_text_' . self::SLUG,   array( __CLASS__, 'source_text' ), 10, 2 );
	}

	/**
	 * Gateway paths
	 *
	 * @param array $paths
	 * @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/lib/MeprGatewayFactory.php#L48-50
	 */
	public function gateway_paths( $paths ) {
		$paths[] = dirname( __FILE__ ) . '/../gateways/';

		return $paths;
	}

	//////////////////////////////////////////////////

	/**
	 * Update lead status of the specified payment
	 *
	 * @see https://github.com/Charitable/Charitable/blob/1.1.4/includes/gateways/class-charitable-gateway-paypal.php#L229-L357
	 * @param Pronamic_Pay_Payment $payment
	 */
	public static function status_update( Pronamic_Pay_Payment $payment, $can_redirect = false ) {
		global $transaction;

		$transaction_id = $payment->get_source_id();

		$transaction = new MeprTransaction( $transaction_id );

		$mepr_options = MeprOptions::fetch();

		// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/models/MeprOptions.php#L768-782
		$url = $mepr_options->thankyou_page_url( 'trans_num=' . $transaction_id );

		$gateway = new Pronamic_WP_Pay_Extensions_MemberPress_Gateway();

		switch ( $payment->get_status() ) {
			case Pronamic_WP_Pay_Statuses::CANCELLED :
				$gateway->record_payment_failure();

				break;
			case Pronamic_WP_Pay_Statuses::EXPIRED :
				$gateway->record_payment_failure();

				break;
			case Pronamic_WP_Pay_Statuses::FAILURE :
				$gateway->record_payment_failure();

				break;
			case Pronamic_WP_Pay_Statuses::SUCCESS :
				$gateway->record_payment();

				break;
			case Pronamic_WP_Pay_Statuses::OPEN :
			default:

				break;
		}

		if ( $can_redirect ) {
			wp_redirect( $url );

			exit;
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Source column
	 */
	public static function source_text( $text, Pronamic_WP_Pay_Payment $payment ) {
		$text  = '';

		$text .= __( 'MemberPress', 'pronamic_ideal' ) . '<br />';

		$text .= sprintf(
			'<a href="%s">%s</a>',
			add_query_arg( array(
				'page'   => 'memberpress-trans',
				'action' => 'edit',
				'id'     => $payment->source_id,
			), admin_url( 'admin.php' ) ),
			sprintf( __( 'Transaction %s', 'pronamic_ideal' ), $payment->source_id )
		);

		return $text;
	}
}
