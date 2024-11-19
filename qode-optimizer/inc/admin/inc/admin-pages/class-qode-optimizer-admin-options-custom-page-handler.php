<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! class_exists( 'Qode_Optimizer_Admin_Options_Custom_Page_Handler' ) ) {
	class Qode_Optimizer_Admin_Options_Custom_Page_Handler {

		private static $instance;
		private $sub_pages;

		public function __construct() {

			add_action( 'init', array( $this, 'load_subpages' ), 11 );
		}

		/**
		 * Instance of module class
		 *
		 * @return Qode_Optimizer_Admin_Options_Custom_Page_Handler
		 */
		public static function get_instance() {

			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function load_subpages() {

			if ( ! empty( $this->sub_pages ) && count( $this->sub_pages ) > 0 ) {

				ksort( $this->sub_pages );

				foreach ( $this->sub_pages as $subpage ) {
					add_filter( 'qode_optimizer_filter_framework_custom_nav', array( $subpage, 'add_to_custom_nav' ) );
					add_action( 'qode_optimizer_action_framework_' . $subpage->get_slug(), array( $subpage, 'render' ) );
					add_action( 'admin_enqueue_scripts', array( $subpage, 'enqueue_styles' ) );
					add_action( 'admin_enqueue_scripts', array( $subpage, 'enqueue_scripts' ) );
				}
			}
		}

		public function add_page( $params ) {

			$sub_object = new Qode_Optimizer_Admin_Options_Custom_Page( $params );

			$this->set_pages( $sub_object );
		}

		public function set_pages( Qode_Optimizer_Admin_Options_Custom_Page $sub_page ) {
			$this->sub_pages[ $sub_page->get_position() ] = $sub_page;
		}

		public function get_pages() {
			return $this->sub_pages;
		}
	}
}

Qode_Optimizer_Admin_Options_Custom_Page_Handler::get_instance();
