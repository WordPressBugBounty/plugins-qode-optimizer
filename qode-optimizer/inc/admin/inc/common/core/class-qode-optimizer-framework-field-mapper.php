<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}
class Qode_Optimizer_Framework_Field_Mapper implements Qode_Optimizer_Framework_Child_Interface {

	public $params;
	public $name;
	public $type;

	public function __construct( $params ) {
		$this->params = isset( $params ) ? $params : array();
		$this->name   = $params['name'];
		$this->type   = $params['type'];
	}

	public function get_name() {
		return $this->name;
	}

	public function render( $return_object = false, $post_id = null ) {
		if ( 'taxonomy' === $this->type || 'product-attribute' === $this->type ) {
			$class = 'Qode_Optimizer_Framework_Field_WP_' . ucfirst( $this->params['field_type'] );
		} elseif ( 'attachment' === $this->type ) {
			$class = 'Qode_Optimizer_Framework_Field_Attachment_' . ucfirst( $this->params['field_type'] );
		} elseif ( 'widget' === $this->type ) {
			$class = 'Qode_Optimizer_Framework_Field_Widget_' . ucfirst( $this->params['field_type'] );
		} else {
			$class = 'Qode_Optimizer_Framework_Field_' . ucfirst( $this->params['field_type'] );
		}

		$class = apply_filters( 'qode_optimizer_filter_framework_field_mapping', $class, $post_id );

		if ( class_exists( $class ) ) {
			$this->params['post_id'] = $post_id;

			if ( $return_object ) {
				return new $class( $this->params );
			} else {
				new $class( $this->params );
			}
		}

		return $return_object;
	}
}
