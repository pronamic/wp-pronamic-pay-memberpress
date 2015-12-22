<?php

/**
 * Title: WordPress pay MemberPress extension
 * Description:
 * Copyright: Copyright (c) 2005 - 2015
 * Company: Pronamic
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
}
