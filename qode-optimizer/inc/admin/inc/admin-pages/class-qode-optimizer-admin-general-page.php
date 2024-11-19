<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! class_exists( 'Qode_Optimizer_Admin_General_Page' ) ) {
	class Qode_Optimizer_Admin_General_Page {

		private static $instance;
		private $menu_name;
		private $menu_title;
		private $sub_pages;

		public function __construct() {
			$this->menu_name  = QODE_OPTIMIZER_GENERAL_MENU_NAME;
			$this->menu_title = esc_html__( 'Qode Optimizer', 'qode-optimizer' );

			// Extend plugin with additional actions.
			add_filter( 'plugin_row_meta', array( $this, 'extend_plugin_info' ), 10, 2 );
			add_filter( 'plugin_action_links_' . QODE_OPTIMIZER_PLUGIN_BASE_FILE, array( $this, 'plugin_action_links' ) );

			// action is init because of shortcode register on init - 0.
			add_action( 'init', array( $this, 'register_sub_pages' ) );
			add_action( 'admin_menu', array( $this, 'dashboard_add_page' ) );
			add_filter( 'admin_body_class', array( $this, 'add_admin_body_classes' ) );
		}

		/**
		 * Instance of module class
		 *
		 * @return Qode_Optimizer_Admin_General_Page
		 */
		public static function get_instance() {

			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function set_sub_pages( Qode_Optimizer_Admin_Sub_Pages $sub_page ) {
			$this->sub_pages[ $sub_page->get_position() ] = $sub_page;
		}

		public function get_sub_pages() {
			return $this->sub_pages;
		}

		public function get_menu_name() {
			return $this->menu_name;
		}

		public function get_menu_title() {
			return $this->menu_title;
		}

		public function extend_plugin_info( $plugin_meta, $plugin_file ) {

			if ( QODE_OPTIMIZER_PLUGIN_BASE_FILE === $plugin_file ) {
				$plugin_meta['qode-optimizer-support']   = '<a href="https://helpcenter.qodeinteractive.com/" target="_blank">' . esc_html__( 'Help Center', 'qode-optimizer' ) . '</a>';
				$plugin_meta['qode-optimizer-video']     = '<a href="https://www.youtube.com/@QodeInteractiveVideos/videos" target="_blank">' . esc_html__( 'Video Tutorials', 'qode-optimizer' ) . '</a>';
				$plugin_meta['qode-optimizer-templates'] = '<a href="https://qodeinteractive.com/products/plugins?utm_source=dash&utm_medium=qodeoptimizer&utm_campaign=gopremium" target="_blank">' . esc_html__( 'Qode Optimizer', 'qode-optimizer' ) . '</a>';
			}

			return $plugin_meta;
		}

		public function plugin_action_links( $links ) {

			if ( ! qode_optimizer_is_installed( 'optimizer-premium' ) ) {
				// translators: 1. Premium plugin url.
				$links['premium'] = sprintf( '<a href="%1$s" target="_blank" class="qode-optimizer-premium-link" style="color:#ee2852;font-weight:700">%2$s</a>', 'https://qodeinteractive.com/products/plugins/qode-optimizer?utm_source=dash&utm_medium=qodeoptimizer&utm_campaign=gopremium', esc_html__( 'Upgrade', 'qode-optimizer' ) );
			}

			return $links;
		}

		public function dashboard_add_page() {

			if ( empty( $GLOBALS['admin_page_hooks'][ QODE_OPTIMIZER_GENERAL_MENU_NAME ] ) ) {
				$page = add_menu_page(
					$this->get_menu_title(),
					$this->get_menu_title(),
					'edit_theme_options',
					$this->get_menu_name(),
					null,
					QODE_OPTIMIZER_ADMIN_URL_PATH . '/inc/common/modules/admin/assets/img/admin-logo-icon.png',
					998
				);

				add_action( 'load-' . $page, array( $this, 'load_admin_css' ) );
			}

			global $submenu;
			$subpages_array = $this->get_sub_pages();

			if ( is_array( $subpages_array ) && ! empty( $subpages_array ) ) {

				ksort( $subpages_array );

				foreach ( $subpages_array as $sub_page => $sub_page_value ) {

					if ( ! isset( $page ) && ( $this->get_menu_name() === $sub_page_value->get_menu_name() ) ) {
						continue;
					}

					if ( isset( $submenu[ $this->get_menu_name() ] ) && in_array( $sub_page_value->get_menu_name(), wp_list_pluck( $submenu[ $this->get_menu_name() ], 2 ), true ) ) {
						continue;
					}

					$sub_page_instance = add_submenu_page(
						$this->get_menu_name(),
						$sub_page_value->get_title(),
						$sub_page_value->get_title(),
						'edit_theme_options',
						$sub_page_value->get_menu_name(),
						array( $sub_page_value, 'render' ),
						$sub_page_value->get_position()
					);

					if ( false !== $sub_page_instance ) {
						add_action( 'load-' . $sub_page_instance, array( $this, 'load_admin_css' ) );
					}
				}
			}
			remove_submenu_page( QODE_OPTIMIZER_GENERAL_MENU_NAME, QODE_OPTIMIZER_GENERAL_MENU_NAME );
		}

		public function get_header( $class_object = null ) {
			$class_object = ! empty( $class_object ) ? $class_object : $this;

			$args = array(
				'menu_slug'  => $class_object->get_menu_name(),
				'menu_title' => $class_object->get_title(),
				'menu_url'   => admin_url( 'admin.php?page=' . $this->get_menu_name() ),
			);

			qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH . '/inc', 'admin-pages', 'templates/header', '', $args );
		}

		public function get_footer() {
			qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH . '/inc', 'admin-pages', 'templates/footer' );
		}

		public function get_content() {
			$args = array();
			qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH . '/inc', 'admin-pages', 'templates/general', '', $args );
		}

		public function render() {

			$args                = array();
			$args['this_object'] = $this;
			qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH . '/inc', 'admin-pages', 'templates/holder', '', $args );
		}

		public function register_sub_pages() {
			$sub_pages = apply_filters( 'qode_optimizer_filter_add_sub_page', $sub_pages = array() );

			if ( ! empty( $sub_pages ) ) {
				foreach ( $sub_pages as $sub_page ) {
					$sub_object = new $sub_page();
					$this->set_sub_pages( $sub_object );
				}
			}
		}

		public function load_admin_css() {
			qode_optimizer_framework_options()->enqueue_dashboard_framework_scripts();
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		public function enqueue_styles() {
			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters
			wp_enqueue_style( 'qode-optimizer-dashboard-style', QODE_OPTIMIZER_ADMIN_URL_PATH . '/inc/admin-pages/assets/css/dashboard.min.css' );
		}

		public function enqueue_scripts() {
			do_action( 'qode_optimizer_action_additional_scripts' );
		}

		public function add_admin_body_classes( $classes ) {

			$pages = $this->get_all_dashboard_names();

			// phpcs:ignore WordPress.Security.NonceVerification
			if ( isset( $_GET['page'] ) && in_array( sanitize_text_field( wp_unslash( $_GET['page'] ) ), $pages, true ) ) {
				$classes = $classes . ' qode-optimizer-admin-pages';
			}

			return $classes;
		}

		public function get_all_dashboard_names() {

			$pages = array(
				$this->get_menu_name(),
			);

			if ( is_array( $this->sub_pages ) && ! empty( $this->sub_pages ) ) {
				foreach ( $this->sub_pages as $sub_page ) {
					$pages[] = $sub_page->get_menu_name();
				}
			}

			return $pages;
		}
	}
}

Qode_Optimizer_Admin_General_Page::get_instance();
