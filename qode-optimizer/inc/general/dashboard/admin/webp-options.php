<?php

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! function_exists( 'qode_optimizer_add_webp_options' ) ) {
	/**
	 * Function that add webp options for this module
	 */
	function qode_optimizer_add_webp_options( $page ) {

		if ( $page ) {

			$page->add_field_element(
				array(
					'field_type'    => 'yesno',
					'name'          => 'qodef_enable_webp_creation',
					'title'         => esc_html__( 'Enable WebP Image Creation', 'qode-optimizer' ),
					'description'   => esc_html__( 'Enable to perform additional WebP image creation as a part of any type of optimization process', 'qode-optimizer' ),
					'default_value' => 'no',
				)
			);

			$webp_conversion_methods = array(
				'native' => esc_html__( 'Native', 'qode-optimizer' ),
			);

			if (
				Qode_Optimizer_Support::is_tool_working( 'cwebp' ) &&
				Qode_Optimizer_Support::is_tool_working( 'gif2webp' )
			) {
				$webp_conversion_methods['tools'] = esc_html__( 'CL Tools', 'qode-optimizer' );
			}

			$page->add_field_element(
				array(
					'field_type'    => 'radio',
					'name'          => 'qodef_webp_conversion_method',
					'title'         => esc_html__( 'WebP Conversion Method', 'qode-optimizer' ),
					'options'       => $webp_conversion_methods,
					'default_value' => 'native',
				)
			);

			$page->add_field_element(
				array(
					'field_type'  => 'text',
					'name'        => 'qodef_webp_quality',
					'title'       => esc_html__( 'WebP Image Quality After Compression', 'qode-optimizer' ),
					'description' => esc_html__( 'Set quality for converted WebP images after compression in format [1-100], 1=min quality (max compression), 100=max quality (min compression). The default quality is set to 75, offering an optimal balance between image clarity and file compression', 'qode-optimizer' ),
				)
			);

			$page->add_field_element(
				array(
					'field_type'    => 'yesno',
					'name'          => 'qodef_insert_rewriting_rules',
					'title'         => esc_html__( 'Insert Rewriting Rules', 'qode-optimizer' ),
					'description'   => esc_html__( 'Insert/remove rewriting rules for delivering WebP images on Apache/LiteSpeed web servers', 'qode-optimizer' ),
					'default_value' => 'no',
				)
			);

			$page->add_field_element(
				array(
					'field_type'    => 'yesno',
					'name'          => 'qodef_picture_webp_rewriting',
					'title'         => esc_html__( 'Picture WebP Rewriting', 'qode-optimizer' ),
					'description'   => esc_html__( 'Apply a JavaScript-free rewriting method using picture tags', 'qode-optimizer' ),
					'default_value' => 'no',
				)
			);

			// Hook to include additional options after module options.
			do_action( 'qode_optimizer_action_after_webp_options_map', $page );
		}
	}

	add_action( 'qode_optimizer_action_webp_options_init', 'qode_optimizer_add_webp_options', qode_optimizer_get_admin_options_map_position( 'webp' ) );
}
