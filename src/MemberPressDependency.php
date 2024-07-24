<?php
/**
 * MemberPress Dependency
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

namespace Pronamic\WordPress\Pay\Extensions\MemberPress;

use Pronamic\WordPress\Pay\Dependencies\Dependency;

/**
 * MemberPress Dependency
 *
 * @author  Reüel van der Steege
 * @version 3.1.0
 * @since   2.1.1
 */
class MemberPressDependency extends Dependency {
	/**
	 * Is met.
	 *
	 * @link
	 * @return bool True if dependency is met, false otherwise.
	 */
	public function is_met() {
		if ( ! \defined( '\MEPR_VERSION' ) ) {
			return false;
		}

		return true;
	}
}
