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

	public function __construct() {
		add_filter( 'mepr-gateway-paths', array( $this, 'gateway_paths' ) );
	}

	public function gateway_paths( $paths ) {
		$paths[] = dirname( __FILE__ ) . '/../gateways/';

		return $paths;
	}
}
