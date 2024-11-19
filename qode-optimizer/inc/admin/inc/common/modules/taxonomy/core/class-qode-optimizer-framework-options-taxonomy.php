<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Framework_Options_Taxonomy extends Qode_Optimizer_Framework_Options {
	public function __construct() {
		parent::__construct();

		add_action( 'init', array( $this, 'init_taxonomy_fields' ) );
		add_action( 'init', array( $this, 'taxonomy_fields_add' ), 11 );
		add_action( 'init', array( $this, 'taxonomy_fields_edit' ), 11 );

		add_action( 'created_term', array( $this, 'save_taxonomy_fields' ), 10, 2 );
		add_action( 'edited_term', array( $this, 'update_taxonomy_fields' ), 10, 2 );
		add_filter( 'sanitize_term_meta_qode_optimizer_term_option', array( $this, 'sanitize_term_option' ) );

		// 5 is set to be same permission as Gutenberg plugin have.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_framework_taxonomy_scripts' ), 5 );
	}

	public function init_taxonomy_fields() {
		do_action( 'qode_optimizer_action_framework_custom_taxonomy_fields' );
	}

	public function taxonomy_fields_add() {
		foreach ( $this->get_child_elements() as $key => $child ) {
			foreach ( $child->get_scope() as $scope ) {
				add_action( $scope . '_add_form_fields', array( $this, 'taxonomy_fields_display_add' ) );
				break;
			}
		}
	}

	public function taxonomy_fields_edit() {
		foreach ( $this->get_child_elements() as $key => $child ) {
			foreach ( $child->get_scope() as $scope ) {
				add_action( $scope . '_edit_form_fields', array( $this, 'taxonomy_fields_display_edit' ), 10, 2 );
				break;
			}
		}
	}

	public function taxonomy_fields_display_add( $taxonomy ) {
		$params                = array();
		$params['this_object'] = $this;
		$params['taxonomy']    = $taxonomy;
		$params['layout']      = '';

		qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH, 'inc/common', 'modules/taxonomy/templates/holder', '', $params );
	}

	public function taxonomy_fields_display_edit( $term, $taxonomy ) {
		$params                = array();
		$params['this_object'] = $this;
		$params['taxonomy']    = $taxonomy;
		$params['layout']      = 'table';
		qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH, 'inc/common', 'modules/taxonomy/templates/holder', '', $params );
	}

	public function save_taxonomy_fields( $term_id ) {
		// Don't allow users without capabilities to modify term.
		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}

		if ( ! isset( $_POST['qode_optimizer_framework_taxonomy_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qode_optimizer_framework_taxonomy_nonce'] ) ), 'qode_optimizer_framework_taxonomy_nonce' ) ) {
			return;
		}

		foreach ( $this->get_options() as $key => $value ) {
			$value = sanitize_meta( 'qode_optimizer_term_option', array_key_exists( $key, $_POST ) ? wp_unslash( $_POST[ $key ] ) : '', 'term' );

			if ( '' !== $value ) {
				add_term_meta( $term_id, $key, $value );
			}
		}
	}

	public function update_taxonomy_fields( $term_id ) {
		// Don't allow users without capabilities to modify term.
		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}

		if ( ! isset( $_POST['qode_optimizer_framework_taxonomy_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qode_optimizer_framework_taxonomy_nonce'] ) ), 'qode_optimizer_framework_taxonomy_nonce' ) ) {
			return;
		}

		foreach ( $this->get_options() as $key => $value ) {
			$value = sanitize_meta( 'qode_optimizer_term_option', array_key_exists( $key, $_POST ) ? wp_unslash( $_POST[ $key ] ) : '', 'term' );

			if ( '' !== $value ) {
				update_term_meta( $term_id, $key, $value );
			} else {
				delete_term_meta( $term_id, $key );
			}
		}
	}

	public function sanitize_term_option( $value ) {
		$sanitized_value = '';
		$trim_value      = ! is_array( $value ) ? trim( $value ) !== '' : ! empty( array_filter( $value ) );

		if ( ( ! empty( $value ) || '0' === $value || 0 === $value ) && $trim_value ) {

			if ( is_array( $value ) ) {
				$sanitized_value = map_deep( wp_unslash( $value ), 'sanitize_text_field' );
			} elseif ( strpos( $value, '<svg' ) !== false ) {
				// Prevent sanitizing value in order to save svg option. We already escaped svg with our function.
				$sanitized_value = $value;
			} else {
				$sanitized_value = sanitize_text_field( $value );
			}
		}

		return $sanitized_value;
	}

	public function enqueue_framework_taxonomy_scripts( $hook ) {
		if ( 'edit-tags.php' === $hook || 'term.php' === $hook ) {
			$this->enqueue_dashboard_framework_scripts();
		}
	}
}
