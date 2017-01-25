<?php

/**
 * Title: WordPress pay MemberPress extension
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.0.4
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

		add_filter( 'pronamic_payment_redirect_url_' . self::SLUG, array( __CLASS__, 'redirect_url' ), 10, 2 );
		add_action( 'pronamic_payment_status_update_' . self::SLUG, array( __CLASS__, 'status_update' ), 10, 1 );

		add_filter( 'pronamic_payment_source_text_' . self::SLUG,   array( __CLASS__, 'source_text' ), 10, 2 );
		add_filter( 'pronamic_payment_source_description_' . self::SLUG,   array( __CLASS__, 'source_description' ), 10, 2 );
		add_filter( 'pronamic_payment_source_url_' . self::SLUG,   array( __CLASS__, 'source_url' ), 10, 2 );
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
	 * Payment redirect URL fitler.
	 *
	 * @since 1.0.1
	 * @param string               $url
	 * @param Pronamic_Pay_Payment $payment
	 * @return string
	 */
	public static function redirect_url( $url, $payment ) {
		global $transaction;

		$transaction_id = $payment->get_source_id();

		$transaction = new MeprTransaction( $transaction_id );

		switch ( $payment->get_status() ) {
			case Pronamic_WP_Pay_Statuses::CANCELLED :
			case Pronamic_WP_Pay_Statuses::EXPIRED :
			case Pronamic_WP_Pay_Statuses::FAILURE :
				$product = $transaction->product();

				$url = add_query_arg(
					array(
						'action' => 'payment_form',
						'txn' => $transaction->trans_num,
						'_wpnonce' => wp_create_nonce( 'mepr_payment_form' ),
					),
					$product->url()
				);

				break;
			case Pronamic_WP_Pay_Statuses::SUCCESS :
				// @see https://gitlab.com/pronamic/memberpress/blob/1.2.4/app/models/MeprOptions.php#L768-782
				$mepr_options = MeprOptions::fetch();

				$product         = new MeprProduct( $transaction->product_id );
				$sanitized_title = sanitize_title( $product->post_title );

				$url = $mepr_options->thankyou_page_url( 'membership=' . $sanitized_title . '&trans_num=' . $transaction->trans_num );

				break;
			case Pronamic_WP_Pay_Statuses::OPEN :
			default:

				break;
		}

		return $url;
	}

	//////////////////////////////////////////////////

	/**
	 * Update lead status of the specified payment
	 *
	 * @see https://github.com/Charitable/Charitable/blob/1.1.4/includes/gateways/class-charitable-gateway-paypal.php#L229-L357
	 * @param Pronamic_Pay_Payment $payment
	 */
	public static function status_update( Pronamic_Pay_Payment $payment ) {
		global $transaction;

		$transaction_id = $payment->get_source_id();

		$transaction = new MeprTransaction( $transaction_id );

		$should_update = ! Pronamic_WP_Pay_Extensions_MemberPress_MemberPress::transaction_has_status( $transaction, array(
			MeprTransaction::$failed_str,
			MeprTransaction::$complete_str,
		) );

		if ( $should_update ) {
			$gateway = new Pronamic_WP_Pay_Extensions_MemberPress_Gateway();

			switch ( $payment->get_status() ) {
				case Pronamic_WP_Pay_Statuses::CANCELLED :
				case Pronamic_WP_Pay_Statuses::EXPIRED :
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

	/**
	 * Source description.
	 */
	public static function source_description( $description, Pronamic_Pay_Payment $payment ) {
		$description = __( 'MemberPress Transaction', 'pronamic_ideal' );

		return $description;
	}

	/**
	 * Source URL.
	 */
	public static function source_url( $url, Pronamic_Pay_Payment $payment ) {
		$url = add_query_arg( array(
			'page'   => 'memberpress-trans',
			'action' => 'edit',
			'id'     => $payment->source_id,
		), admin_url( 'admin.php' ) );

		return $url;
	}
}
