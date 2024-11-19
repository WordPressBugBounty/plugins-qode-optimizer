<?php

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! function_exists( 'qode_optimizer_add_optimization_options' ) ) {
	/**
	 * Function that add optimization options for this module
	 */
	function qode_optimizer_add_optimization_options( $page ) {

		$welcome_section = $page->add_section_element(
			array(
				'layout'      => 'welcome',
				'name'        => 'qode_optimizer_global_plugins_options_welcome_section',
				'title'       => esc_html__( 'Welcome to Qode Optimizer', 'qode-optimizer' ),
				'description' => esc_html__( 'It\'s time to speed up your website with optimized images', 'qode-optimizer' ),
				'icon'        => QODE_OPTIMIZER_ASSETS_URL_PATH . '/img/icon.png',
			)
		);

		if ( $page ) {

			$page->add_field_element(
				array(
					'field_type'  => 'text',
					'name'        => 'qodef_image_max_width',
					'title'       => esc_html__( 'Image Max Width (px)', 'qode-optimizer' ),
					'description' => esc_html__( 'Set max width for uploaded images', 'qode-optimizer' ),
				)
			);

			$page->add_field_element(
				array(
					'field_type'  => 'text',
					'name'        => 'qodef_image_max_height',
					'title'       => esc_html__( 'Image Max Height (px)', 'qode-optimizer' ),
					'description' => esc_html__( 'Set max height for uploaded images', 'qode-optimizer' ),
				)
			);

			$jpg_compression_methods = array(
				'none'         => esc_html__( 'No Compression', 'qode-optimizer' ),
				'lossy-native' => esc_html__( 'Lossy (Imagick/GD Lib)', 'qode-optimizer' ),
			);

			if ( Qode_Optimizer_Support::is_tool_working( 'jpegtran' ) ) {
				$jpg_compression_methods['lossless-clt'] = esc_html__( 'Lossless (CL Tools)', 'qode-optimizer' );
			}

			if ( Qode_Optimizer_Support::is_tool_working( 'jpegoptim' ) ) {
				$jpg_compression_methods['lossy-clt'] = esc_html__( 'Lossy (CL Tools)', 'qode-optimizer' );
			}

			$page->add_field_element(
				array(
					'field_type'    => 'select',
					'name'          => 'qodef_jpg_compression_method',
					'title'         => esc_html__( 'JPG Compression Method', 'qode-optimizer' ),
					'options'       => $jpg_compression_methods,
					'default_value' => 'lossy-native',
				)
			);

			$page->add_field_element(
				array(
					'field_type'  => 'text',
					'name'        => 'qodef_jpg_compression_quality',
					'title'       => esc_html__( 'JPG Image Quality After Compression (Lossy)', 'qode-optimizer' ),
					'description' => esc_html__( 'Set quality for JPG images after compression in format [1-100], 1=min quality (max compression), 100=max quality (min compression). The default quality is set to 75, offering an optimal balance between image clarity and file compression', 'qode-optimizer' ),
					'dependency'  => array(
						'show' => array(
							'qodef_jpg_compression_method' => array(
								'values'        => array( 'lossy-native', 'lossy-clt' ),
								'default_value' => 'lossy-native',
							),
						),
					),
				)
			);

			$png_compression_methods = array(
				'none'         => esc_html__( 'No Compression', 'qode-optimizer' ),
				'lossy-native' => esc_html__( 'Lossy (Imagick/GD Lib)', 'qode-optimizer' ),
			);

			if ( Qode_Optimizer_Support::is_tool_working( 'optipng' ) ) {
				$png_compression_methods['lossless-clt'] = esc_html__( 'Lossless (CL Tools)', 'qode-optimizer' );
			}

			if ( Qode_Optimizer_Support::is_tool_working( 'pngquant' ) ) {
				$png_compression_methods['lossy-clt'] = esc_html__( 'Lossy (CL Tools)', 'qode-optimizer' );
			}

			$page->add_field_element(
				array(
					'field_type'    => 'select',
					'name'          => 'qodef_png_compression_method',
					'title'         => esc_html__( 'PNG Compression Method', 'qode-optimizer' ),
					'options'       => $png_compression_methods,
					'default_value' => 'lossy-native',
				)
			);

			$page->add_field_element(
				array(
					'field_type'  => 'text',
					'name'        => 'qodef_png_compression_quality',
					'title'       => esc_html__( 'PNG Image Quality After Compression (Lossy)', 'qode-optimizer' ),
					'description' => esc_html__( 'Set quality for PNG images after compression in format [1-100], 1=min quality (max compression), 100=max quality (min compression). The default quality is set to 75, offering an optimal balance between image clarity and file compression', 'qode-optimizer' ),
					'dependency'  => array(
						'show' => array(
							'qodef_png_compression_method' => array(
								'values'        => array( 'lossy-native', 'lossy-clt' ),
								'default_value' => 'lossy-native',
							),
						),
					),
				)
			);

			$gif_compression_methods = array(
				'none'         => esc_html__( 'No Compression', 'qode-optimizer' ),
				'lossy-native' => esc_html__( 'Lossy (Imagick/GD Lib)', 'qode-optimizer' ),
			);

			if ( Qode_Optimizer_Support::is_tool_working( 'gifsicle' ) ) {
				$gif_compression_methods['lossy-clt'] = esc_html__( 'Lossy (CL Tools)', 'qode-optimizer' );
			}

			$page->add_field_element(
				array(
					'field_type'    => 'select',
					'name'          => 'qodef_gif_compression_method',
					'title'         => esc_html__( 'GIF Compression Method', 'qode-optimizer' ),
					'options'       => $gif_compression_methods,
					'default_value' => 'lossy-native',
				)
			);

			$page->add_field_element(
				array(
					'field_type'  => 'text',
					'name'        => 'qodef_gif_compression_quality',
					'title'       => esc_html__( 'GIF Image Quality After Compression (Lossy)', 'qode-optimizer' ),
					'description' => esc_html__( 'Set quality for GIF images after compression in format [1-100], 1=min quality (max compression), 100=max quality (min compression). The default quality is set to 75, offering an optimal balance between image clarity and file compression', 'qode-optimizer' ),
					'dependency'  => array(
						'show' => array(
							'qodef_gif_compression_method' => array(
								'values'        => array( 'lossy-clt', 'lossy-native' ),
								'default_value' => 'lossy-native',
							),
						),
					),
				)
			);

			$page->add_field_element(
				array(
					'field_type'    => 'yesno',
					'name'          => 'qodef_image_metadata_remove',
					'title'         => esc_html__( 'Remove Image Metadata', 'qode-optimizer' ),
					'description'   => esc_html__( 'Enable to remove metadata from images in optimization process', 'qode-optimizer' ),
					'default_value' => 'no',
				)
			);

			// Hook to include additional options after module options.
			do_action( 'qode_optimizer_action_after_optimization_options_map', $page );
		}
	}

	add_action( 'qode_optimizer_action_optimization_options_init', 'qode_optimizer_add_optimization_options', qode_optimizer_get_admin_options_map_position( 'optimization' ) );
}
