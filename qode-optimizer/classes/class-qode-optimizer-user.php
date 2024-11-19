<?php
/**
 * Implementation of user procedures
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_User {

	/**
	 * Checks if current user is admin
	 *
	 * @return bool
	 */
	public static function is_admin() {
		return current_user_can( 'activate_plugins' );
	}
}
