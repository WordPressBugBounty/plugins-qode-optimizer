<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! function_exists( 'qode_optimizer_add_upgrade_button_to_nav' ) ) {
	function qode_optimizer_add_upgrade_button_to_nav() {
		echo '<a href="' . esc_url( QODE_OPTIMIZER_MARKET_URL ) . '" class="qodef-btn qodef-btn-solid qodef-btn-nav-upgrade" target="_blank">';
		echo '<span class="qodef-btn-text">' . esc_html__( 'Upgrade', 'qode-optimizer' ) . '</span>';
		echo '<span class="qodef-btn-icon"><svg xmlns="http://www.w3.org/2000/svg" width="15.675" height="15.675" viewBox="0 0 15.675 15.675"><path d="M7.917,9.5,6.809,8.353,9.619,5.542H0V3.959H9.619L6.809,1.148,7.917,0l4.75,4.75Z" transform="translate(0 8.957) rotate(-45)"></path></svg></span>';
		echo '</a>';
	}

	add_action( 'qode_optimizer_action_framework_before_custom_nav', 'qode_optimizer_add_upgrade_button_to_nav' );
}
