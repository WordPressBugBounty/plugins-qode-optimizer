<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

abstract class Qode_Optimizer_Admin_Sub_Pages {
	private $base;
	private $slug = '';
	private $menu_name;
	private $title;
	private $position;
	private $atts = array();

	public function __construct() {
		$this->add_sub_page();
	}

	abstract public function add_sub_page();

	public function get_base() {
		return $this->base;
	}

	public function set_base( $base ) {
		$this->base = $base;
	}

	public function get_slug() {
		return $this->slug;
	}

	public function set_slug( $slug ) {
		$this->slug = $slug;
	}

	public function get_menu_name() {
		return $this->menu_name;
	}

	public function set_menu_name( $menu_name ) {
		$this->menu_name = $menu_name;
	}

	public function get_title() {
		return $this->title;
	}

	public function set_title( $title ) {
		$this->title = $title;
	}

	public function get_position() {
		return $this->position;
	}

	public function set_position( $position ) {
		$this->position = $position;
	}

	public function get_atts() {
		return $this->atts;
	}

	public function set_atts( $atts ) {
		$this->atts = $atts;
	}

	public function render() {
		$args                = $this->get_atts();
		$args['this_object'] = $this;
		qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH . '/inc', 'admin-pages', 'templates/holder', '', $args );
	}

	public function get_header() {
		$object = ! empty( $object ) ? $object : $this;

		$args = array(
			'title' => $object->get_title(),
		);

		qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH . '/inc', 'admin-pages', 'templates/header', '', $args );
	}

	public function get_footer() {
		qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH . '/inc', 'admin-pages', 'templates/footer' );
	}

	public function get_content() {
		qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH . '/inc/admin-pages', 'sub-pages/' . $this->get_base(), 'templates/' . $this->get_base(), '', $this->get_atts() );
	}
}
