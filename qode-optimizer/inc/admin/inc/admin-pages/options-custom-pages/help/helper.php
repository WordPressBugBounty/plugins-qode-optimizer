<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! function_exists( 'qode_optimizer_add_mailchimp_script' ) ) {
	/**
	 * Function add mailichim script
	 */
	function qode_optimizer_add_mailchimp_script() {
		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters
		wp_enqueue_script( 'mailchimp', QODE_OPTIMIZER_ADMIN_URL_PATH . '/inc/admin-pages/options-custom-pages/help/assets/plugins/mailchimp/mailchimp.min.js', array( 'jquery' ), false, true );
	}

	add_action( 'qode_optimizer_action_additional_scripts_on_options_page_help', 'qode_optimizer_add_mailchimp_script' );
}
