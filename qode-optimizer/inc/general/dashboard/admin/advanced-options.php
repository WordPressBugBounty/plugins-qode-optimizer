<?php

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! function_exists( 'qode_optimizer_add_advanced_options' ) ) {
	/**
	 * Function that add advanced options for this module
	 */
	function qode_optimizer_add_advanced_options( $page ) {

		if ( $page ) {

			$page->add_field_element(
				array(
					'field_type'    => 'select',
					'name'          => 'qodef_backup_method',
					'title'         => esc_html__( 'Backup Method', 'qode-optimizer' ),
					'description'   => esc_html__( 'Set backup method for original images', 'qode-optimizer' ),
					'options'       => array(
						'local' => esc_html__( 'Local Folders', 'qode-optimizer' ),
					),
					'default_value' => 'local',
				)
			);

			// translators: %s - filesystem path to the root of the WordPress installation.
			$description = sprintf( esc_html__( 'Include folder paths that contain images that should be optimized. Note that each line should contain 1 full filesystem path, and not the URL (e.g. filesystem path to the root of your WordPress installation is %s)', 'qode-optimizer' ), realpath( qode_optimizer_get_home_path() ) );

			$page->add_field_element(
				array(
					'field_type'  => 'textarea',
					'name'        => 'qodef_optimize_additional_folders',
					'title'       => esc_html__( 'Optimize Additional Folders', 'qode-optimizer' ),
					'description' => $description,
					'args'        => array(
						'custom_class' => 'qodef--extra-wide',
					),
				)
			);

			// translators: %s - filesystem path to the root of the WordPress installation.
			$description = sprintf( esc_html__( 'Include paths for images that shouldn\'t be optimized. Note that each line should contain 1 full filesystem path, and not the URL (e.g. filesystem path to the root of your WordPress installation is %s)', 'qode-optimizer' ), realpath( qode_optimizer_get_home_path() ) );

			$page->add_field_element(
				array(
					'field_type'  => 'textarea',
					'name'        => 'qodef_optimize_exclude_images',
					'title'       => esc_html__( 'Exclude Images From Optimization', 'qode-optimizer' ),
					'description' => $description,
					'args'        => array(
						'custom_class' => 'qodef--extra-wide',
					),
				)
			);

			$page->add_field_element(
				array(
					'field_type'    => 'yesno',
					'name'          => 'qodef_enable_system_log',
					'title'         => esc_html__( 'Enable System Log', 'qode-optimizer' ),
					'description'   => esc_html__( 'Enable to perform automatic system logging of any optimization action in a separate log file', 'qode-optimizer' ),
					'default_value' => 'no',
				)
			);

			// Hook to include additional options after module options.
			do_action( 'qode_optimizer_action_after_advanced_options_map', $page );
		}
	}

	add_action( 'qode_optimizer_action_advanced_options_init', 'qode_optimizer_add_advanced_options', qode_optimizer_get_admin_options_map_position( 'advanced' ) );
}
