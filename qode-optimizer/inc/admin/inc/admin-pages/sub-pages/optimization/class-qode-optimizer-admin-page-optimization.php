<?php

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! function_exists( 'qode_optimizer_add_optimization_sub_page_to_list' ) ) {
	/**
	 * Function that add additional sub page item into general page list
	 *
	 * @param array $sub_pages
	 *
	 * @return array
	 */
	function qode_optimizer_add_optimization_sub_page_to_list( $sub_pages ) {
		$sub_pages[] = 'Qode_Optimizer_Admin_Page_Optimization';

		return $sub_pages;
	}

	add_filter( 'qode_optimizer_filter_add_sub_page', 'qode_optimizer_add_optimization_sub_page_to_list' );
}

if ( class_exists( 'Qode_Optimizer_Admin_Sub_Pages' ) ) {
	class Qode_Optimizer_Admin_Page_Optimization extends Qode_Optimizer_Admin_Sub_Pages {

		public function __construct() {

			parent::__construct();

			add_action( 'qode_optimizer_action_additional_scripts', array( $this, 'set_additional_scripts' ) );
		}

		public function add_sub_page() {
			$this->set_base( 'optimization' );
			$this->set_menu_name( 'optimization' );
			$this->set_title( esc_html__( 'Optimization', 'qode-optimizer' ) );
			$this->set_position( 3 );
		}

		public function get_header() {
			return '';
		}
		public function set_additional_scripts() {

			if ( isset( $_GET['page'] ) && $_GET['page'] === $this->get_menu_name() ) { // phpcs:ignore WordPress.Security.NonceVerification
				wp_enqueue_script( 'jquery-ui-core' );
				wp_enqueue_script( 'jquery-ui-progressbar' );
				wp_enqueue_script( 'qode-optimizer-optimization', QODE_OPTIMIZER_ADMIN_URL_PATH . '/inc/admin-pages/sub-pages/optimization/assets/js/optimization.min.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-progressbar' ), QODE_OPTIMIZER_VERSION, true );
			}
		}
	}
}
