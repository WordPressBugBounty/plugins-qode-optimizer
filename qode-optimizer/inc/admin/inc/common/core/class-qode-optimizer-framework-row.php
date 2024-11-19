<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}
abstract class Qode_Optimizer_Framework_Row implements Qode_Optimizer_Framework_Tree_Interface, Qode_Optimizer_Framework_Child_Interface {

	private $scope;
	private $type;
	private $name;
	private $layout;
	private $title;
	private $description;
	private $dependency;
	private $icon;
	private $children;

	public function __construct( $params ) {
		$this->scope       = isset( $params['scope'] ) ? $params['scope'] : '';
		$this->type        = isset( $params['type'] ) ? $params['type'] : '';
		$this->name        = isset( $params['name'] ) ? $params['name'] : '';
		$this->layout      = isset( $params['layout'] ) ? $params['layout'] : 'normal';
		$this->title       = isset( $params['title'] ) ? $params['title'] : '';
		$this->description = isset( $params['description'] ) ? $params['description'] : '';
		$this->dependency  = isset( $params['dependency'] ) ? $params['dependency'] : array();
		$this->icon        = isset( $params['icon'] ) ? $params ['icon'] : '';
		$this->children    = isset( $params['children'] ) ? $params['children'] : array();
	}

	public function get_scope() {
		return $this->scope;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_children() {
		return $this->children;
	}

	public function get_title() {
		return $this->title;
	}

	public function get_description() {
		return $this->description;
	}

	public function has_children() {
		return count( $this->children ) > 0;
	}

	public function get_child( $key ) {
		return $this->children[ $key ];
	}

	public function add_child( Qode_Optimizer_Framework_Child_Interface $field ) {
		$key                    = $field->get_name();
		$this->children[ $key ] = $field;
	}

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
		$dependency_data = array();
		$class           = array();

		$params['this_object'] = $this;
		$class[]               = 'qodef-row-' . $this->layout;
		$class[]               = 'qodef-row-name-' . $this->get_name();

		if ( ! empty( $this->dependency ) ) {
			$class[] = 'qodef-dependency-holder';

			$repeater = false;
			$show     = array_key_exists( 'show', $this->dependency ) ? qode_optimizer_framework_return_dependency_options_array( $this->scope, $this->type, $this->dependency['show'], true, $repeater ) : array();
			$hide     = array_key_exists( 'hide', $this->dependency ) ? qode_optimizer_framework_return_dependency_options_array( $this->scope, $this->type, $this->dependency['hide'], false, $repeater ) : array();
			$relation = array_key_exists( 'relation', $this->dependency ) ? $this->dependency['relation'] : 'and';

			$class[]         = qode_optimizer_framework_return_dependency_classes( $show, $hide );
			$dependency_data = qode_optimizer_framework_return_dependency_data( $show, $hide, $relation );
		}

		$params['class']           = implode( ' ', $class );
		$params['dependency_data'] = $dependency_data;

		qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH, 'inc/common', 'templates/row', $this->layout, $params );
	}
}
