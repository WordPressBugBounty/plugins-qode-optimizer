<?php

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! function_exists( 'qode_optimizer_add_status_sub_page_to_list' ) ) {
	/**
	 * Function that add additional sub page item into general page list
	 *
	 * @param array $sub_pages
	 *
	 * @return array
	 */
	function qode_optimizer_add_status_sub_page_to_list( $sub_pages ) {
		$sub_pages[] = 'Qode_Optimizer_Admin_Page_Status';

		return $sub_pages;
	}

	add_filter( 'qode_optimizer_filter_add_sub_page', 'qode_optimizer_add_status_sub_page_to_list' );
}

if ( class_exists( 'Qode_Optimizer_Admin_Sub_Pages' ) ) {
	class Qode_Optimizer_Admin_Page_Status extends Qode_Optimizer_Admin_Sub_Pages {

		public function __construct() {

			parent::__construct();

			add_action( 'qode_optimizer_action_additional_scripts', array( $this, 'set_additional_scripts' ) );
		}

		public function get_header() {
			return '';
		}

		public function add_sub_page() {
			$this->set_base( 'status' );
			$this->set_menu_name( 'qode_optimizer_status' );
			$this->set_title( esc_html__( 'Status', 'qode-optimizer' ) );
			$this->set_position( 7 );
		}

		public function set_additional_scripts() {}
	}
}
