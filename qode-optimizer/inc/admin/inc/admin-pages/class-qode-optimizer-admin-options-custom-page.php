<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Admin_Options_Custom_Page {

	private $slug;
	private $title;
	private $position;
	private $icon;
	private $style;
	private $script;

	public function __construct( $params ) {
		$this->slug     = isset( $params['slug'] ) ? $params['slug'] : '';
		$this->title    = isset( $params['title'] ) ? $params['title'] : '';
		$this->position = isset( $params['position'] ) ? $params['position'] : '';
		$this->icon     = isset( $params['icon'] ) ? $params['icon'] : '';
		$this->style    = isset( $params['style'] ) ? $params['style'] : true;
		$this->script   = isset( $params['script'] ) ? $params['script'] : true;
	}

	public function get_slug() {
		return $this->slug;
	}

	public function get_title() {
		return $this->title;
	}

	public function get_position() {
		return $this->position;
	}

	public function get_icon() {
		return $this->icon;
	}

	public function get_style() {
		return $this->style;
	}

	public function get_script() {
		return $this->script;
	}

	public function render() {
		qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH . '/inc', 'admin-pages/options-custom-pages/' . $this->slug, 'templates/' . $this->slug, '' );
	}

	public function add_to_custom_nav( $pages ) {
		$url = add_query_arg(
			array(
				'page'     => QODE_OPTIMIZER_MENU_NAME,
				'template' => $this->get_slug(),
			),
			admin_url( 'admin.php' )
		);

		$pages[ $this->get_position() ] = array(
			'name'  => $this->get_title(),
			'url'   => $url,
			// phpcs:ignore WordPress.Security.NonceVerification
			'class' => isset( $_GET['template'] ) && sanitize_text_field( wp_unslash( $_GET['template'] ) ) === $this->get_slug() ? 'qodef-active' : '',
			'icon'  => $this->get_icon(),
		);

		return $pages;
	}

	public function enqueue_styles() {
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( true === $this->get_style() && isset( $_GET['page'] ) && QODE_OPTIMIZER_MENU_NAME === sanitize_text_field( wp_unslash( $_GET['page'] ) ) && isset( $_GET['template'] ) && isset( $_GET['template'] ) && $this->get_slug() === sanitize_text_field( wp_unslash( $_GET['template'] ) ) ) {
			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters
			wp_enqueue_style( 'qode-optimizer-' . $this->get_slug() . '-style', QODE_OPTIMIZER_ADMIN_URL_PATH . '/inc/admin-pages/options-custom-pages/' . $this->get_slug() . '/assets/css/' . $this->get_slug() . '.min.css' );
		}
	}

	public function enqueue_scripts() {
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( true === $this->get_script() && isset( $_GET['page'] ) && QODE_OPTIMIZER_MENU_NAME === sanitize_text_field( wp_unslash( $_GET['page'] ) ) && isset( $_GET['template'] ) && $this->get_slug() === sanitize_text_field( wp_unslash( $_GET['template'] ) ) ) {
			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters
			wp_enqueue_script( 'qode-optimizer-' . $this->get_slug() . '-script', QODE_OPTIMIZER_ADMIN_URL_PATH . '/inc/admin-pages/options-custom-pages/' . $this->get_slug() . '/assets/js/' . $this->get_slug() . '.js' );
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_GET['page'] ) && QODE_OPTIMIZER_MENU_NAME === sanitize_text_field( wp_unslash( $_GET['page'] ) ) && isset( $_GET['template'] ) && $this->get_slug() === sanitize_text_field( wp_unslash( $_GET['template'] ) ) ) {
			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters
			do_action( 'qode_optimizer_action_additional_scripts_on_options_page_' . $this->get_slug() );
		}
	}
}
