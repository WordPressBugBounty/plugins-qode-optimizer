<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Framework_Options_Admin extends Qode_Optimizer_Framework_Options {
	private $menu_name;
	private $options_name;
	private $menu_label;
	private $icon_path;

	public function __construct( $menu_name = 'qode_optimizer_framework_menu', $options_name = 'qode_optimizer_framework_options', $params = array() ) {
		parent::__construct();

		$this->menu_name    = $menu_name;
		$this->options_name = $options_name;

		$this->menu_label = ! empty( $params['label'] ) ? $params['label'] : esc_html__( 'Qode Options', 'qode-optimizer' );
		$this->icon_path  = ! empty( $params['icon_path'] ) ? $params['icon_path'] : QODE_OPTIMIZER_ADMIN_URL_PATH . '/inc/common/modules/admin/assets/img/admin-logo-icon.png';

		add_action( 'init', array( $this, 'init_options' ), 11 );

		add_action( 'admin_menu', array( $this, 'framework_menu' ) );

		// 999 is set to be at the last place in admin bar.
		add_action( 'admin_bar_menu', array( $this, 'framework_admin_bar_menu' ), 999 );

		add_action( 'wp_ajax_qode_optimizer_action_framework_save_options_' . $this->get_options_name(), array( $this, 'save_options' ) );

		add_action( 'wp_ajax_qode_optimizer_action_framework_reset_options_' . $this->get_options_name(), array( $this, 'reset_options' ) );

		// 5 is set to be same permission as Gutenberg plugin have.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_framework_options_scripts' ), 5 );

		add_filter( 'admin_body_class', array( $this, 'add_admin_body_classes' ) );
	}

	public function get_menu_name() {
		return $this->menu_name;
	}

	public function get_options_name() {
		return $this->options_name;
	}

	public function get_menu_label() {
		return $this->menu_label;
	}

	public function get_icon_path() {
		return $this->icon_path;
	}

	public function framework_menu() {

		do_action( 'qode_optimizer_action_framework_before_options_map', $this->get_menu_name() );

		$child_elements = $this->get_child_elements();

		if ( ! empty( $child_elements ) ) {
			add_submenu_page(
				QODE_OPTIMIZER_GENERAL_MENU_NAME,
				$this->get_menu_label(),
				$this->get_menu_label(),
				'edit_theme_options',
				$this->get_menu_name(),
				array(
					$this,
					'render_options',
				),
				0
			);
		}
		remove_submenu_page( QODE_OPTIMIZER_GENERAL_MENU_NAME, QODE_OPTIMIZER_GENERAL_MENU_NAME );
		do_action( 'qode_optimizer_action_framework_after_options_map', $this->get_menu_name() );
	}

	public function framework_admin_bar_menu( $wp_admin_bar ) {
		$menu_name = $this->get_menu_name();

		if ( current_user_can( 'edit_theme_options' ) && ! empty( $menu_name ) && ! is_admin() ) {
			$menu_id = esc_attr( str_replace( '_', '-', $menu_name ) . '-admin-bar-options' );

			$args = array(
				'id'    => $menu_id,
				'title' => sprintf( '<span class="ab-icon dashicons-before dashicons-admin-generic"></span> %s', esc_html( $this->get_menu_label() ) ),
				'href'  => esc_url( admin_url( 'admin.php?page=' . esc_attr( $menu_name ) ) ),
			);

			$wp_admin_bar->add_node( $args );
		}
	}

	public function init_options() {

		do_action( 'qode_optimizer_action_framework_before_options_init_' . $this->get_options_name(), $this->get_options_name() );

		if ( ! get_option( $this->get_options_name() ) ) {
			add_option( $this->get_options_name(), $this->get_options() );
		}

		$this->populate_options();

		do_action( 'qode_optimizer_action_framework_after_options_init_' . $this->get_options_name(), $this->get_options_name() );
	}

	public function populate_options() {

		do_action( 'qode_optimizer_action_framework_before_options_populate', $this->get_options_name() );

		$db_options = get_option( $this->get_options_name() );

		if ( is_array( $db_options ) && ! empty( $db_options ) ) {
			$this->set_options( array_merge( $this->get_options(), get_option( $this->get_options_name() ) ) );
		}

		$this->register_options();

		do_action( 'qode_optimizer_action_framework_after_options_populate', $this->get_options_name() );
	}

	public function register_options() {

		do_action( 'qode_optimizer_action_framework_before_options_registered', $this->get_options_name() );

		register_setting( $this->get_menu_label(), $this->get_options_name() );

		do_action( 'qode_optimizer_action_framework_after_options_registered', $this->get_options_name() );
	}

	public function save_options() {

		if ( current_user_can( 'edit_theme_options' ) ) {
			// Get global options from db.
			$saved_global_options = $this->get_options();

			// Helper functions to sanitize whole request object and remove unnecessary fields from it.
			$new_global_options = qode_optimizer_framework_clean_global_options( qode_optimizer_framework_map_deep_sanitize( wp_unslash( $_REQUEST ) ), $saved_global_options );

			unset( $new_global_options['action'] );

			check_ajax_referer( 'qode_optimizer_framework_ajax_save_nonce', 'qode_optimizer_framework_ajax_save_nonce' );

			$options_name = isset( $new_global_options['options_name'] ) ? sanitize_text_field( wp_unslash( $new_global_options['options_name'] ) ) : '';
			unset( $new_global_options['options_name'] );

			if ( $options_name === $this->get_options_name() ) {

				do_action( 'qode_optimizer_action_framework_before_framework_option_save', $saved_global_options, $new_global_options );

				$this->set_options( array_merge( $saved_global_options, $new_global_options ) );

				update_option( $this->get_options_name(), $this->get_options() );

				esc_html_e( 'Saved', 'qode-optimizer' );

				do_action( 'qode_optimizer_action_framework_after_framework_option_save' );

			} else {
				esc_html_e( 'Wrong options trigger', 'qode-optimizer' );
			}

			die();
		}
	}

	public function reset_options() {
		if ( current_user_can( 'edit_theme_options' ) ) {

			if ( isset( $_REQUEST['action'] ) ) {
				unset( $_REQUEST['action'] );
			}

			check_ajax_referer( 'qode_optimizer_framework_ajax_save_nonce', 'qode_optimizer_framework_ajax_save_nonce' );

			$options_name = isset( $_REQUEST['options_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['options_name'] ) ) : '';

			if ( $options_name === $this->get_options_name() ) {

				delete_option( $this->get_options_name() );

				esc_html_e( 'Options reset to default', 'qode-optimizer' );

				do_action( 'qode_optimizer_action_framework_after_framework_option_reset' );

			} else {
				esc_html_e( 'Wrong options trigger', 'qode-optimizer' );
			}

			die();
		}
	}

	public function render_options() {
		$params                 = array();
		$params['options']      = $this;
		$params['options_name'] = $this->get_options_name();
		// phpcs:ignore WordPress.Security.NonceVerification
		$custom_holder = isset( $_GET['template'] ) ? 'custom' : '';
		// phpcs:ignore WordPress.Security.NonceVerification
		$params['custom_holder_slug'] = isset( $_GET['template'] ) ? sanitize_text_field( wp_unslash( $_GET['template'] ) ) : '';

		qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH, 'inc/common', 'modules/admin/templates/holder', $custom_holder, $params );
	}

	public function render_navigation() {
		$params                 = array();
		$params['pages']        = $this->get_child_elements();
		$params['options_name'] = $this->get_options_name();
		$params['menu_label']   = $this->get_menu_label();
		$params['use_icons']    = false;

		qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH, 'inc/common', 'modules/admin/templates/navigation', '', $params );
	}

	public function render_content() {
		$params                 = array();
		$pages                  = $this->get_child_elements();
		$params['pages']        = $pages;
		$params['options_name'] = $this->get_options_name();

		qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH, 'inc/common', 'modules/admin/templates/content', '', $params );
	}

	public function render_custom_content() {
		$params                 = array();
		$pages                  = $this->get_child_elements();
		$params['pages']        = $pages;
		$params['options_name'] = $this->get_options_name();

		qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH, 'inc/common', 'modules/admin/templates/content', 'custom', $params );
	}

	public function enqueue_framework_options_scripts() {
		// check if page is options page.
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_GET['page'] ) && strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), $this->get_menu_name() ) !== false ) {
			$this->enqueue_dashboard_framework_scripts();
		}
	}

	public function add_admin_body_classes( $classes ) {
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_GET['page'] ) && strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), $this->get_menu_name() ) !== false ) {
			$classes = $classes . ' qodef-framework-admin';
		}

		return $classes;
	}
}
