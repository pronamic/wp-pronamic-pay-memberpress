<?php
/**
 * Plugin Name: Pronamic Pay MemberPress Add-On
 * Plugin URI: https://www.pronamic.eu/plugins/pronamic-pay-memberpress/
 * Description: Extend the Pronamic Pay plugin with MemberPress support to receive payments through a variety of payment providers.
 *
 * Version: 4.8.3
 * Requires at least: 4.7
 * Requires PHP: 7.4
 *
 * Author: Pronamic
 * Author URI: https://www.pronamic.eu/
 *
 * Text Domain: pronamic-pay-memberpress
 * Domain Path: /languages/
 *
 * License: GPL-3.0-or-later
 *
 * Requires Plugins: pronamic-ideal
 * Depends: wp-pay/core
 *
 * GitHub URI: https://github.com/pronamic/wp-pronamic-pay-memberpress
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

add_filter(
	'pronamic_pay_plugin_integrations',
	function ( $integrations ) {
		foreach ( $integrations as $integration ) {
			if ( $integration instanceof \Pronamic\WordPress\Pay\Extensions\MemberPress\Extension ) {
				return $integrations;
			}
		}

		$integrations[] = new \Pronamic\WordPress\Pay\Extensions\MemberPress\Extension();

		return $integrations;
	}
);
