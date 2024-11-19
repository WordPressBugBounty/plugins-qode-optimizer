<?php

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}
class Qode_Optimizer_Framework_Options_Attribute extends Qode_Optimizer_Framework_Options {

	public function __construct() {
		parent::__construct();

		add_action( 'admin_init', array( $this, 'init_attribute_fields' ) );
		add_action( 'woocommerce_after_add_attribute_fields', array( $this, 'attribute_fields_display_add' ) );
		add_action( 'woocommerce_after_edit_attribute_fields', array( $this, 'attribute_fields_display_edit' ) );
		add_action( 'woocommerce_attribute_added', array( $this, 'save_attribute_fields' ) );
		add_action( 'woocommerce_attribute_updated', array( $this, 'save_attribute_fields' ) );

		// 5 is set to be same permission as Gutenberg plugin have.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_framework_user_scripts' ), 5 );
	}

	public function init_attribute_fields() {
		do_action( 'qode_optimizer_action_framework_custom_attribute_fields' );
	}

	public function attribute_fields_display_add() {
		$params                = array();
		$params['this_object'] = $this;
		$params['type']        = 'product-attribute';
		$params['layout']      = 'div';
		qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH, 'inc/common', 'modules/attribute/templates/holder', '', $params );
	}

	public function attribute_fields_display_edit() {
		$params                = array();
		$params['this_object'] = $this;
		$params['type']        = 'product-attribute';
		$params['layout']      = 'table';
		qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH, 'inc/common', 'modules/attribute/templates/holder', '', $params );
	}

	public function save_attribute_fields( $id ) {
		if ( ! isset( $_POST['_wpnonce'] ) ) {
			return;
		}

		if ( ! isset( $_POST['qode_optimizer_framework_attribute_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qode_optimizer_framework_attribute_nonce'] ) ), 'qode_optimizer_framework_attribute_nonce' ) ) {
			return;
		}

		// Don't allow users without capabilities to create new attribute.
		// phpcs:ignore WordPress.WP.Capabilities.Unknown
		if ( ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}

		foreach ( $this->get_options() as $key => $value ) {
			$value  = array_key_exists( $key, $_POST ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
			$option = $key . '-' . strval( $id );

			if ( ( ! empty( $value ) || '0' === $value || 0 === $value ) && '' !== trim( $value ) ) {
				update_option( $option, sanitize_text_field( wp_unslash( $value ) ) );
			} else {
				delete_option( $option );
			}
		}
	}

	public function enqueue_framework_user_scripts( $hook ) {
		if ( 'product_page_product_attributes' === $hook ) {
			$this->enqueue_dashboard_framework_scripts();
		}
	}
}
