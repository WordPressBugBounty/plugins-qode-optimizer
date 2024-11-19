<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}
abstract class Qode_Optimizer_Framework_Page implements Qode_Optimizer_Framework_Tree_Interface {
	private $scope;
	private $type;
	private $slug;
	private $layout;
	private $title;
	private $description;
	private $dependency;
	private $icon;
	private $children;

	public function __construct( $params ) {
		$this->scope       = isset( $params['scope'] ) ? $this->format_scope( $params['scope'] ) : array();
		$this->type        = isset( $params['type'] ) ? $params['type'] : '';
		$this->slug        = isset( $params['slug'] ) ? $params['slug'] : '';
		$this->layout      = isset( $params['layout'] ) ? $params['layout'] : '';
		$this->title       = isset( $params['title'] ) ? $params['title'] : '';
		$this->description = isset( $params['description'] ) ? $params['description'] : '';
		$this->dependency  = isset( $params['dependency'] ) ? $params['dependency'] : array();
		$this->icon        = isset( $params['icon'] ) ? $params ['icon'] : '';
		$this->children    = isset( $params['children'] ) ? $params['children'] : array();
	}

	public function get_scope() {
		return $this->scope;
	}

	public function format_scope( $scope ) {
		if ( is_string( $scope ) ) {
			$scope = array( $scope );
		}

		return $scope;
	}

	public function get_type() {
		return $this->type;
	}

	public function get_slug() {
		return $this->slug;
	}

	public function get_layout() {
		return $this->layout;
	}

	public function set_layout( $layout ) {
		$this->layout = $layout;
	}

	public function get_title() {
		return $this->title;
	}

	public function get_description() {
		return $this->description;
	}

	public function get_dependency() {
		return $this->dependency;
	}

	public function get_icon() {
		return $this->icon;
	}

	public function has_children() {
		return count( $this->children ) > 0;
	}

	public function get_children() {
		return $this->children;
	}

	public function get_child( $key ) {
		return $this->children[ $key ];
	}

	public function add_child( Qode_Optimizer_Framework_Child_Interface $field ) {
		$key                    = $field->get_name();
		$this->children[ $key ] = $field;
	}

	abstract public function add_tab_element( $params );

	abstract public function add_section_element( $params );

	abstract public function add_row_element( $params );

	public function add_repeater_element( $params ) {
		if ( isset( $params['name'] ) && ! empty( $params['name'] ) ) {
			$field = new Qode_Optimizer_Framework_Field_Repeater( $params );
			$this->add_child( $field );

			return $field;
		}

		return false;
	}

	public function add_field_element( $params ) {

		if ( isset( $params['name'] ) && ! empty( $params['name'] ) ) {
			$field = new Qode_Optimizer_Framework_Field_Mapper( $params );
			$this->add_child( $field );

			return $field;
		}

		return false;
	}

	public function render() {
		$params['this_object'] = $this;
		$params['page_slug']   = $this->get_slug();
		$params['class']       = 'qodef-options-' . $this->get_type();
		$params['class']      .= ' qodef-page-v4-optimizer';

		if ( $this->get_layout() === 'tabbed' ) {
			wp_enqueue_script( 'jquery-ui-tabs' );
		}

		qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH, 'inc/common', 'templates/page', $this->get_layout(), $params );
	}
}
