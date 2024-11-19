<?php

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! function_exists( 'qode_optimizer_add_conversion_options' ) ) {
	/**
	 * Function that add conversion options for this module
	 */
	function qode_optimizer_add_conversion_options( $page ) {

		if ( $page ) {

			// Hook to include additional options after module options.
			do_action( 'qode_optimizer_action_after_conversion_options_map', $page );
		}
	}

	add_action( 'qode_optimizer_action_conversion_options_init', 'qode_optimizer_add_conversion_options', qode_optimizer_get_admin_options_map_position( 'conversion' ) );
}
