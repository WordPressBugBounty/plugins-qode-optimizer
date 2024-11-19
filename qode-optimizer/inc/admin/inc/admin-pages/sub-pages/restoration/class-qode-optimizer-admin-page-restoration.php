<?php

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! function_exists( 'qode_optimizer_add_restoration_sub_page_to_list' ) ) {
	/**
	 * Function that add additional sub page item into general page list
	 *
	 * @param array $sub_pages
	 *
	 * @return array
	 */
	function qode_optimizer_add_restoration_sub_page_to_list( $sub_pages ) {
		$sub_pages[] = 'Qode_Optimizer_Admin_Page_Restoration';

		return $sub_pages;
	}

	add_filter( 'qode_optimizer_filter_add_sub_page', 'qode_optimizer_add_restoration_sub_page_to_list' );
}

if ( class_exists( 'Qode_Optimizer_Admin_Sub_Pages' ) ) {
	class Qode_Optimizer_Admin_Page_Restoration extends Qode_Optimizer_Admin_Sub_Pages {

		public function add_sub_page() {
			$this->set_base( 'restoration' );
			$this->set_menu_name( 'restoration' );
			$this->set_title( esc_html__( 'Restoration (Premium)', 'qode-optimizer' ) );
			$this->set_position( 5 );
		}

		public function get_header() {
			return '';
		}
	}
}
