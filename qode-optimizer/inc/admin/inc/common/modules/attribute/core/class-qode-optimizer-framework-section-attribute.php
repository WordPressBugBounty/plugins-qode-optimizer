<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Framework_Section_Attribute extends Qode_Optimizer_Framework_Section {

	public function add_row_element( $params ) {
		return false;
	}

	public function add_section_element( $params ) {

		if ( isset( $params['name'] ) && ! empty( $params['name'] ) ) {
			$params['type'] = 'product-attribute';
			$field          = new Qode_Optimizer_Framework_Section_Attribute( $params );
			$this->add_child( $field );

			return $field;
		}

		return false;
	}

	public function add_repeater_element( $params ) {
		return false;
	}

	public function add_field_element( $params ) {
		$params['type']          = 'product-attribute';
		$params['default_value'] = isset( $params['default_value'] ) ? $params['default_value'] : '';
		qode_optimizer_framework_get_framework_root()->get_product_attribute_options()->set_option( $params['name'], $params['default_value'], $params['field_type'] );
		parent::add_field_element( $params );
	}
}
