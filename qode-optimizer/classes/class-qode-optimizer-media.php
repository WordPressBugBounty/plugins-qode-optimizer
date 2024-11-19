<?php
/**
 * Implementation of media support procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Media {

	/**
	 * Initialization
	 */
	public function init() {
		add_filter( 'manage_media_columns', array( $this, 'manage_media_columns' ) );
		add_action( 'manage_media_custom_column', array( $this, 'add_media_custom_column' ), 10, 2 );
		add_filter( 'intermediate_image_sizes_advanced', array( $this, 'handle_image_sizes_advanced' ) );
		add_filter( 'wp_handle_upload', array( $this, 'handle_media_upload' ) );
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'handle_media_thumb_creation' ), 1000, 2 );
		add_action( 'delete_attachment', array( $this, 'handle_media_delete' ), 1000 );

		// Ajax calls.
		if ( Qode_Optimizer_User::is_admin() ) {
			add_action( 'wp_ajax_media_init_action_buttons_and_info', array( $this, 'ajax_init_action_buttons_and_info' ) );
			add_action( 'wp_ajax_media_include_action_buttons', array( $this, 'include_action_buttons' ) );
			add_action( 'wp_ajax_media_action_should_be_converted', array( $this, 'handle_media_should_be_converted' ) );
			add_action( 'wp_ajax_media_action_optimize_process', array( $this, 'handle_media_optimization_process' ) );
			add_action( 'wp_ajax_media_action_restore', array( $this, 'handle_media_restoration' ) );
			add_action( 'wp_ajax_media_action_regenerate', array( $this, 'handle_media_regeneration' ) );
			add_action( 'wp_ajax_media_action_add_watermark', array( $this, 'handle_media_adding_watermark' ) );
			add_action( 'wp_ajax_media_action_recover', array( $this, 'handle_media_recover' ) );
		}
	}

	/**
	 * Add column header for optimizer results in the media library listing.
	 *
	 * @param array $columns A list of columns in the media library.
	 *
	 * @return array The new list of columns.
	 */
	public function manage_media_columns( $columns ) {
		if ( Qode_Optimizer_User::is_admin() ) {
			$columns['qode-optimizer'] = esc_html__( 'Qode Optimizer', 'qode-optimizer' );
		}

		return $columns;
	}

	/**
	 * Add custom column in the media library
	 *
	 * @global object $wpdb
	 *
	 * @param string $column_name Custom column name
	 * @param int $id Image ID
	 **/
	public function add_media_custom_column( $column_name, $id ) {
		if (
			Qode_Optimizer_User::is_admin() &&
			is_string( $column_name ) &&
			'qode-optimizer' === $column_name
		) {
			$id = intval( $id );

			$html = $this->init_action_buttons_and_info( $id );

			echo qode_optimizer_framework_wp_kses_html( 'html', $html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Filters the image sizes automatically generated when uploading an image
	 *
	 * @param array $sizes Associative array of image sizes to be created
	 *
	 * @return array $sizes Associative array of image sizes to be created
	 */
	public function handle_image_sizes_advanced( $sizes ) {
		$disabled_sizes = Qode_Optimizer_Options::get_option( 'disable_image_creation' );

		if ( ! is_array( $disabled_sizes ) ) {
			$disabled_sizes = array();
		}

		return array_diff_key( $sizes, array_flip( $disabled_sizes ) );
	}

	/**
	 * During an upload, handles resizing, auto-rotation, and sets the 'new_image' global.
	 *
	 * @global bool $ewww_new_image True if there is a new image being uploaded.
	 *
	 * @param array $params Parameters related to the file being uploaded.
	 * @return array The unaltered parameters, we only need to read them.
	 *
	 * @throws ImagickException Throws ImagickException on error.
	 */
	public function handle_media_upload( $params ) {
		if (
			empty( $params['file'] ) &&
			empty( $params['tmp_name'] )
		) {
			return $params;
		}

		$file = ! empty( $params['file'] ) ? $params['file'] : $params['tmp_name'];

		$filesystem = new Qode_Optimizer_Filesystem();

		if (
			! $filesystem->is_file( $file ) ||
			! $filesystem->filesize( $file )
		) {
			clearstatcache();
			return $params;
		}

		$system_log = Qode_Optimizer_Log::get_instance();

		$uploaded_image = Qode_Optimizer_Image_Factory::create(
			array(
				'file'       => $file,
				'media_size' => 'original',
			)
		);
		if (
			$uploaded_image &&
			'yes' === $uploaded_image->enable_automatic_image_optimization
		) {
			$system_log->add_log( '', true );
			$system_log->add_log( 'MEDIA AUTOMATIC OPTIMIZATION AFTER UPLOAD', true );

			$system_log->add_log( 'Image: ' . wp_basename( $uploaded_image->file ), true );
			$system_log->add_log( '', true );

			// Do ONLY resize, without compression.
			$uploaded_image->optimize( false, true );
		}

		return $params;
	}

	/**
	 * Automatically optimize image thumbnails if proper option is enabled
	 *
	 * @param array $metadata image metadata
	 * @param int $attachment_id image id
	 *
	 * @return array updated image metadata
	 */
	public function handle_media_thumb_creation( $metadata, $attachment_id ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		$uploaded_image = Qode_Optimizer_Image_Factory::create(
			array(
				'id'         => $attachment_id,
				'media_size' => 'original',
			)
		);
		if (
			$uploaded_image &&
			'yes' === $uploaded_image->enable_automatic_image_optimization
		) {
			$current_time        = microtime( true );
			$elapsed_time_params = array(
				'current' => $current_time,
				'local'   => 0.0,
				'total'   => 0.0,
			);

			if ( Qode_Optimizer_Options::get_option( 'watermark_image_path' ) ) {
				$uploaded_image->image_and_thumbs_add_watermark();
				$uploaded_image = Qode_Optimizer_Image_Factory::create(
					array(
						'id'         => $attachment_id,
						'media_size' => 'original',
					)
				);

				// Elapsed time checkpoint.
				$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );
			}

			$uploaded_image->image_and_thumbs_optimize();

			// Elapsed time checkpoint.
			$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );

			// Additional conversion and optimization, if conversion is set in admin options.
			$convert_options = Qode_Optimizer_Options::get_convert_options();
			if ( 'yes' === $convert_options[ $uploaded_image::MIME_TYPE ] ) {

				$uploaded_image = Qode_Optimizer_Image_Factory::create(
					array(
						'id'         => $attachment_id,
						'media_size' => 'original',
					)
				);
				if ( $uploaded_image ) {
					$output_conversion = $uploaded_image->image_and_thumbs_convert();

					// Elapsed time checkpoint.
					$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );

					// Additional optimization only if conversion is successful.
					if ( $output_conversion->get_param( 'success' ) ) {
						$uploaded_image = Qode_Optimizer_Image_Factory::create(
							array(
								'id'                     => $attachment_id,
								'media_size'             => 'original',
								'additional_compression' => true,
							)
						);
						if ( $uploaded_image ) {
							$uploaded_image->image_and_thumbs_optimize();

							// Elapsed time checkpoint.
							$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );
						}
					}
				}
			}

			if ( 'yes' === Qode_Optimizer_Options::get_option( 'enable_webp_creation' ) ) {
				$uploaded_image = Qode_Optimizer_Image_Factory::create(
					array(
						'id'         => $attachment_id,
						'media_size' => 'original',
					)
				);
				if ( $uploaded_image ) {
					$uploaded_image->image_and_thumbs_create_webp();

					// Elapsed time checkpoint.
					$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );
				}
			}

			$metadata = $uploaded_image->metadata;

			$total_elapsed_time = number_format( $elapsed_time_params['total'], 4 );

			$system_log->add_log( 'Total elapsed time: ' . $total_elapsed_time . 's', true );

			$system_log->write_log();
		}

		return $metadata;
	}

	/**
	 * Cleans up when an attachment is being deleted.
	 *
	 * Removes any .webp images, backups from conversion, and removes related database records.
	 *
	 * @global object $wpdb
	 *
	 * @param int $attachment_id The id number for the attachment being deleted.
	 */
	public function handle_media_delete( $attachment_id ) {
		$image = Qode_Optimizer_Image_Factory::create(
			array(
				'id'         => $attachment_id,
				'media_size' => 'original',
			)
		);
		if ( $image ) {
			$image->image_and_thumbs_remove_webp();
			$image->image_and_thumbs_remove_backup_and_modifications();
		}
	}

	/**
	 * Get currently active action buttons
	 *
	 * @param int $id
	 * @param array $button_options
	 *
	 * @return array
	 */
	public function get_action_buttons( $id, $button_options = array() ) {
		$id = intval( $id );

		if ( ! is_array( $button_options ) ) {
			$button_options = array( 'optimize' );
		}

		$qo_nonce = wp_create_nonce( 'qo-nonce' );

		$buttons = array(
			'optimize' => '<a class="qodef-media-action-link qodef-optimize-manual" href="#" data-id="' . esc_attr( $id ) . '" data-qo-nonce="' . esc_attr( $qo_nonce ) . '">' . __( 'Optimize', 'qode-optimizer' ) . '</a>',
		);

		if ( in_array( 'restore', $button_options, true ) ) {
			$buttons['restore'] = '<a class="qodef-media-action-link qodef-restore-manual" href="#" data-id="' . esc_attr( $id ) . '" data-qo-nonce="' . esc_attr( $qo_nonce ) . '">' . __( 'Restore Original', 'qode-optimizer' ) . '</a>';
		}

		if (
			'always' === Qode_Optimizer_Options::get_option( 'show_regenerate_link' ) ||
			in_array( 'regenerate', $button_options, true )
		) {
			$buttons['regenerate'] = '<a class="qodef-media-action-link qodef-regenerate-manual" href="#" data-id="' . esc_attr( $id ) . '" data-qo-nonce="' . esc_attr( $qo_nonce ) . '">' . __( 'Regenerate Thumbnails', 'qode-optimizer' ) . '</a>';
		}

		if ( in_array( 'recover', $button_options, true ) ) {
			$buttons['recover'] = '<a class="qodef-media-action-link qodef-recover-manual" href="#" data-id="' . esc_attr( $id ) . '" data-qo-nonce="' . esc_attr( $qo_nonce ) . '">' . __( 'Try To Recover', 'qode-optimizer' ) . '</a>';
		}

		if ( in_array( 'watermark', $button_options, true ) ) {
			$buttons['watermark'] = '<a class="qodef-media-action-link qodef-add-watermark-manual" href="#" data-id="' . esc_attr( $id ) . '" data-qo-nonce="' . esc_attr( $qo_nonce ) . '">' . __( 'Add Watermark', 'qode-optimizer' ) . '</a>';
		}

		return $buttons;
	}

	/**
	 * Initialize action buttons and present additional image info
	 *
	 * @param int $id image id
	 *
	 * @return string HTML
	 */
	public function init_action_buttons_and_info( $id ) {
		$html = '';

		if ( Qode_Optimizer_User::is_admin() ) {

			global $wpdb;

			$qo_db = new Qode_Optimizer_Db();

			$qo_db->init_charset();

			$filesystem = new Qode_Optimizer_Filesystem();

			$result_output = array(
				'original_size'   => '',
				'current_size'    => '',
				'modified_result' => '',
				'webp_size'       => '',
				'webp_result'     => '',
			);

			$id = intval( $id );

			$original_file      = wp_get_original_image_path( $id );
			$current_file       = get_attached_file( $id );
			$current_media_size = $original_file !== $current_file && false !== strpos( $current_file, '-scaled.' ) ? 'scaled' : 'original';

			$current_size  = $filesystem->filesize( $current_file );
			$original_size = $current_size;

			/**
			 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
			 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
			 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
			 */
			$modifications_query   = $wpdb->prepare( 'SELECT * FROM ' . $qo_db->get_modifications_table() . ' WHERE media_size = %s AND attachment_id = %d ORDER BY last_modification DESC, id ASC LIMIT 1', $current_media_size, $id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$modifications_results = $wpdb->get_results( $modifications_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$important_modifications = array();
			if ( ! empty( $modifications_results ) ) {
				$important_modifications[ $modifications_results[0]['media_size'] ] = array(
					'current_path'      => $modifications_results[0]['current_path'],
					'previous_path'     => $modifications_results[0]['previous_path'],
					'result'            => $modifications_results[0]['result'],
					'current_size'      => $modifications_results[0]['current_size'],
					'original_size'     => $modifications_results[0]['original_size'],
					'is_optimized'      => $modifications_results[0]['is_optimized'],
					'is_converted'      => $modifications_results[0]['is_converted'],
					'last_modification' => $modifications_results[0]['last_modification'],
				);

				$current_file  = $important_modifications[ $current_media_size ]['current_path'];
				$current_size  = $important_modifications[ $current_media_size ]['current_size'];
				$original_size = $important_modifications[ $current_media_size ]['original_size'];

				// Conversion result.
				$result_output['modified_result'] = $filesystem->readable_filesize_savings( $original_size, $current_size );
			}

			// Original size.
			$result_output['original_size'] = $original_size;

			// Current size.
			$result_output['current_size'] = $current_size;

			$webp_file = $current_file . '.webp';

			if ( $filesystem->is_file( $webp_file ) ) {
				$webp_size   = $filesystem->filesize( $webp_file );
				$webp_result = $filesystem->readable_filesize_savings( $original_size, $webp_size );

				// WebP size.
				$result_output['webp_size'] = $webp_size;

				// WebP result.
				$result_output['webp_result'] = $webp_result;
			}

			$warnings = array();

			$metadata_in_db = maybe_unserialize( wp_get_attachment_metadata( $id ) );
			$filesize_in_db = $metadata_in_db['filesize'];
			$filesize_live  = $filesystem->filesize( $current_file );

			$regeneration_params = array(
				'has_scaled'       => 'scaled' === $current_media_size,
				'changed'          => false,
				'original_changed' => false,
				'scaled_changed'   => false,
			);

			if ( $filesize_live > 0 ) {
				if ( $filesize_in_db !== $filesize_live ) {
					$regeneration_params['changed'] = true;
					if ( 'scaled' === $current_media_size ) {
						$regeneration_params['scaled_changed'] = true;
					} else {
						$regeneration_params['original_changed'] = true;
					}
				}

				if ( 'scaled' === $current_media_size ) {
					/**
					 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
					 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
					 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
					 */
					$scaled_modifications_query   = $wpdb->prepare( 'SELECT * FROM ' . $qo_db->get_modifications_table() . ' WHERE media_size = "original" AND attachment_id = %d ORDER BY last_modification DESC, id ASC LIMIT 1', $id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$scaled_modifications_results = $wpdb->get_results( $scaled_modifications_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					if ( ! empty( $scaled_modifications_results ) ) {
						$original_filesize_in_db = (int) $scaled_modifications_results[0]['current_size'];
						$original_filesize_live  = $filesystem->filesize( $original_file );

						if (
							$original_filesize_live > 0 &&
							$original_filesize_in_db !== $original_filesize_live
						) {
							$regeneration_params['changed']          = true;
							$regeneration_params['original_changed'] = true;
						}
					}
				}
			} else {
				$warnings[] = 'recover';
			}

			if ( $regeneration_params['changed'] ) {
				$warnings[] = 'regeneration';

				if ( $regeneration_params['original_changed'] ) {
					$warnings[] = 'original_changed';
				} else {
					$warnings[] = 'scaled_only_changed';
				}
			}

			// Additional modification info template.
			$html .= '<div class="qodef-media-action-holder">';
			$html .= '<div class="qodef-media-action-links">';

			// Action buttons render.
			$button_options = array();
			if ( ! empty( $modifications_results ) ) {
				$button_options[] = 'restore';
			}
			if ( in_array( 'recover', $warnings, true ) ) {
				$button_options[] = 'recover';
			}
			if ( in_array( 'regeneration', $warnings, true ) ) {
				$button_options[] = 'regenerate';
			}

			foreach ( $this->get_action_buttons( $id, $button_options ) as $button ) {
				$html .= $button;
			}

			$html .= '<span class="qodef-spinner-loading qodef-hidden">';
			$html .= qode_optimizer_framework_get_svg_icon( 'spinner' );
			$html .= '<span class="qodef-action-label"></span>';
			$html .= '</span>';
			$html .= '</div>';
			$html .= '<div class="qodef-media-original-results">';
			$html .= '<div><span class="qodef-title">' . esc_html__( 'Original:', 'qode-optimizer' ) . '</span> <span class="qodef-value">' . ( $filesystem->readable_size_format( $result_output['original_size'] ) ) . '</span></div>';
			$html .= '</div>';
			$html .= '<div class="qodef-media-action-results qodef-init">';

			if ( ! empty( $result_output['modified_result'] ) ) {
				$html .= '<div><span class="qodef-title">' . esc_html__( 'Optimization:', 'qode-optimizer' ) . '</span> <span class="qodef-value">' . $filesystem->readable_size_format( $result_output['current_size'] ) . ', ' . esc_html( $important_modifications[ $current_media_size ]['result'] ) . '</span></div>';
			}

			if ( ! empty( $result_output['webp_result'] ) ) {
				$html .= '<div><span class="qodef-title">' . esc_html__( 'WebP:', 'qode-optimizer' ) . '</span> <span class="qodef-value">' . $filesystem->readable_size_format( $result_output['webp_size'] ) . ', ' . esc_html( $result_output['webp_result'] ) . '</span></div>';
			}

			if ( ! empty( $warnings ) ) {
				$html .= '<div class="qodef-media-warning-holder">';
				if ( in_array( 'regeneration', $warnings, true ) ) {
					$html              .= '<div class="qodef-warning">';
					$html              .= '<span class="qodef-title">' . esc_html__( 'Regeneration Warning:', 'qode-optimizer' ) . '</span>';
					$html              .= '<span class="qodef-value">';
					$regenerate_warning = esc_html__( 'System detects this image was replaced manually, and regeneration process should be done. ', 'qode-optimizer' );
					if ( in_array( 'scaled_only_changed', $warnings, true ) ) {
						$regenerate_warning .= '<span class="qodef-warning-note">' . esc_html__( 'System also detects only scaled image was replaced, so in order for regeneration process to create valid images you need to replace the original image as well, as regeneration process is using that original image. ', 'qode-optimizer' ) . '</span>';
					}
					$regenerate_warning .= esc_html__( 'Once regeneration is finished, all optimization info for this image will be reset and image will be available for optimization again', 'qode-optimizer' );
					$html               .= $regenerate_warning;
					$html               .= '</span>';
					$html               .= '</div>';
				} elseif ( in_array( 'recover', $warnings, true ) ) {
					$html .= '<div class="qodef-warning"><span class="qodef-title">' . esc_html__( 'Recover Warning:', 'qode-optimizer' ) . '</span> <span class="qodef-value">' . esc_html__( 'System detects this image is nowhere to be found, and recovering process should be attempted.', 'qode-optimizer' ) . '</span></div>';
				}
				$html .= '</div>';
			}

			$html .= '</div>';
			$html .= '</div>';
		}

		return $html;
	}

	/**
	 * Ajax - initialize action buttons and present additional image info on media modal in edit mode
	 */
	public function ajax_init_action_buttons_and_info() {

		if ( ! Qode_Optimizer_User::is_admin() ) {
			wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
		}

		/**
		 * Important: This is NOT a form processing procedure
		 */
		if ( isset( $_POST ) && ! empty( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification

			if ( ! wp_doing_ajax() ) {
				wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
			}

			$id = 0;
			if ( ! empty( $_POST['options']['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$id = intval( $_POST['options']['id'] ); // phpcs:ignore WordPress.Security.NonceVerification
			}

			$output = $this->init_action_buttons_and_info( $id );

			if ( ! empty( $output ) ) {
				qode_optimizer_get_ajax_status( 'success', esc_html__( 'Success', 'qode-optimizer' ), $output );
			} else {
				qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Fail', 'qode-optimizer' ), $output );
			}
		}
	}

	/**
	 * Ajax - Include action buttons
	 */
	public function include_action_buttons() {

		if ( ! Qode_Optimizer_User::is_admin() ) {
			wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
		}

		if ( isset( $_POST ) && ! empty( $_POST ) ) {

			if (
				empty( $_POST['options']['qo_nonce'] ) ||
				! wp_verify_nonce( sanitize_key( $_POST['options']['qo_nonce'] ), 'qo-nonce' )
			) {
				if ( ! wp_doing_ajax() ) {
					wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
				}

				wp_die( wp_json_encode( array( 'error' => esc_html__( 'Access denied.', 'qode-optimizer' ) ) ) );
			}

			$id = 0;
			if ( ! empty( $_POST['options']['id'] ) ) {
				$id = intval( $_POST['options']['id'] );
			}

			global $wpdb;

			$qo_db = new Qode_Optimizer_Db();

			$qo_db->init_charset();

			/**
			 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
			 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
			 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
			 */
			$modifications_query   = $wpdb->prepare( 'SELECT 1 FROM ' . $qo_db->get_modifications_table() . ' WHERE media_size IN ( "original", "scaled" ) AND attachment_id = %d ORDER BY last_modification DESC, id ASC', $id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$modifications_results = $wpdb->get_results( $modifications_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$button_options = array();
			if ( ! empty( $modifications_results ) ) {
				$button_options[] = 'restore';
			}

			$output = '';
			foreach ( $this->get_action_buttons( $id, $button_options ) as $button ) {
				$output .= $button;
			}

			if ( ! empty( $output ) ) {
				qode_optimizer_get_ajax_status( 'success', esc_html__( 'Success', 'qode-optimizer' ), $output );
			} else {
				qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Fail', 'qode-optimizer' ), $output );
			}
		}
	}

	/**
	 * Ajax - checks if image should be converted
	 */
	public function handle_media_should_be_converted() {

		if ( ! Qode_Optimizer_User::is_admin() ) {
			wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
		}

		if ( isset( $_POST ) && ! empty( $_POST ) ) {

			if (
				empty( $_POST['options']['qo_nonce'] ) ||
				! wp_verify_nonce( sanitize_key( $_POST['options']['qo_nonce'] ), 'qo-nonce' )
			) {
				if ( ! wp_doing_ajax() ) {
					wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
				}

				wp_die( wp_json_encode( array( 'error' => esc_html__( 'Access denied.', 'qode-optimizer' ) ) ) );
			}

			$id = 0;
			if ( ! empty( $_POST['options']['id'] ) ) {
				$id = intval( $_POST['options']['id'] );
			}

			$convert_options = Qode_Optimizer_Options::get_convert_options();

			$image = Qode_Optimizer_Image_Factory::create(
				array(
					'id'         => $id,
					'media_size' => 'original',
				)
			);
			if (
				$image &&
				'yes' === $convert_options[ $image::MIME_TYPE ]
			) {
				qode_optimizer_get_ajax_status( 'success', esc_html__( 'Image should be converted', 'qode-optimizer' ) );
			}

			qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Image should not be converted', 'qode-optimizer' ) );
		}
	}

	/**
	 * Ajax - Image optimization process
	 */
	public function handle_media_optimization_process() {

		if ( ! Qode_Optimizer_User::is_admin() ) {
			wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
		}

		if ( isset( $_POST ) && ! empty( $_POST ) ) {

			if (
				empty( $_POST['options']['qo_nonce'] ) ||
				! wp_verify_nonce( sanitize_key( $_POST['options']['qo_nonce'] ), 'qo-nonce' )
			) {
				if ( ! wp_doing_ajax() ) {
					wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
				}

				wp_die( wp_json_encode( array( 'error' => esc_html__( 'Access denied.', 'qode-optimizer' ) ) ) );
			}

			$output = new Qode_Optimizer_Output();
			$output->set_param( 'watermarked_files', array() );
			$output->set_param( 'optimization_files', array() );
			$output->set_param( 'conversion_files', array() );
			$output->set_param( 'optimization2_files', array() );
			$output->set_param( 'webp_files', array() );
			$output->set_param( 'restoration_success', false );
			$output->set_param( 'watermarked_success', false );
			$output->set_param( 'optimization_success', false );
			$output->set_param( 'conversion_success', false );
			$output->set_param( 'optimization2_success', false );
			$output->set_param( 'webp_success', false );
			$output->set_param( 'watermarked_skipped', false );
			$output->set_param( 'conversion_skipped', false );
			$output->set_param( 'optimization2_skipped', false );
			$output->set_param( 'webp_skipped', false );
			$output->set_param( 'elapsed_time', false );

			$id = 0;
			if ( ! empty( $_POST['options']['id'] ) ) {
				$id = intval( $_POST['options']['id'] );
			}

			// Reset persistent alpha option.
			Qode_Optimizer_Options::set_option( 'alpha_exists', array() );

			$system_log = Qode_Optimizer_Log::get_instance();
			$system_log->add_log( '', true );
			$system_log->add_log( 'MEDIA MANUAL OPTIMIZATION', true );

			$current_time        = microtime( true );
			$elapsed_time_params = array(
				'current' => $current_time,
				'local'   => 0.0,
				'total'   => 0.0,
			);

			$image = Qode_Optimizer_Image_Factory::create(
				array(
					'id'         => $id,
					'media_size' => 'original',
				)
			);
			if ( $image ) {

				$system_log->add_log( 'Image: ' . wp_basename( $image->file ), true );
				$system_log->add_log( '', true );

				$system_log->add_log( '* SYSTEM FETCHING DATA', true );

				// Restore image first.
				global $wpdb;

				$current_file       = get_attached_file( $image->id );
				$current_media_size = wp_get_original_image_path( $image->id ) !== $current_file && false !== strpos( $current_file, '-scaled.' ) ? 'scaled' : 'original';

				$qo_db = new Qode_Optimizer_Db();

				$qo_db->init_charset();

				/**
				 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
				 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
				 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
				 */
				$modifications_query   = $wpdb->prepare( 'SELECT 1 FROM ' . $qo_db->get_modifications_table() . ' WHERE media_size = %s AND ( ( current_path <> previous_path AND is_converted = 1 ) OR is_optimized = 1 ) AND attachment_id = %d ORDER BY last_modification DESC, id ASC', $current_media_size, $image->id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$modifications_results = $wpdb->get_results( $modifications_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

				// Elapsed time checkpoint.
				$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );

				if ( ! empty( $modifications_results ) ) {
					$output_restore = $image->image_and_thumbs_restore();
					if ( $output_restore->get_param( 'success' ) ) {
						$output->set_param( 'restoration_success', $output_restore->get_param( 'success' ) );
						$output->set_param( 'restoration_result', esc_html__( 'Successful', 'qode-optimizer' ) );
						$image = Qode_Optimizer_Image_Factory::create(
							array(
								'id'         => $id,
								'media_size' => 'original',
							)
						);
					}

					// Elapsed time checkpoint.
					$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );
				}
			}

			if ( $image ) {
				if ( Qode_Optimizer_Options::get_option( 'watermark_image_path' ) ) {

					$output_watermarked = $image->image_and_thumbs_add_watermark();

					if ( $output_watermarked->get_param( 'success' ) ) {
						$output->set_param( 'watermarked_files', $output_watermarked->get_param( 'files' ) );
					} else {
						$output->set_param( 'original_file', $output_watermarked->get_param( 'original_file' ) );
						$output->set_param( 'initial_size_raw', $output_watermarked->get_param( 'initial_size_raw' ) );
						$output->set_param( 'initial_size', $output_watermarked->get_param( 'initial_size' ) );
						$output->set_param( 'watermarked_result', $output_watermarked->get_param( 'result' ) );
					}

					$output->set_param( 'watermarked_success', $output_watermarked->get_param( 'success' ) );
					$image = Qode_Optimizer_Image_Factory::create(
						array(
							'id'         => $id,
							'media_size' => 'original',
						)
					);

					// Elapsed time checkpoint.
					$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );
				} else {
					$output->set_param( 'watermarked_skipped', true );
				}

				$output_optimization = $image->image_and_thumbs_optimize();

				if ( $output_optimization->get_param( 'success' ) ) {
					$output->set_param( 'optimization_files', $output_optimization->get_param( 'files' ) );
				} else {
					$output->set_param( 'original_file', $output_optimization->get_param( 'original_file' ) );
					$output->set_param( 'initial_size_raw', $output_optimization->get_param( 'initial_size_raw' ) );
					$output->set_param( 'initial_size', $output_optimization->get_param( 'initial_size' ) );
					$output->set_param( 'optimization_result', $output_optimization->get_param( 'result' ) );
				}

				$output->set_param( 'optimization_success', $output_optimization->get_param( 'success' ) );

				// Elapsed time checkpoint.
				$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );

				// Additional conversion and optimization, if conversion is set in admin options.
				$convert_options = Qode_Optimizer_Options::get_convert_options();
				if ( 'yes' === $convert_options[ $image::MIME_TYPE ] ) {

					$image = Qode_Optimizer_Image_Factory::create(
						array(
							'id'         => $id,
							'media_size' => 'original',
						)
					);
					if ( $image ) {
						$output_conversion = $image->image_and_thumbs_convert();

						if ( $output_conversion->get_param( 'success' ) ) {
							$output->set_param( 'conversion_files', $output_conversion->get_param( 'files' ) );
						} else {
							$output->set_param( 'original_file', $output_conversion->get_param( 'original_file' ) );
							$output->set_param( 'initial_size_raw', $output_conversion->get_param( 'initial_size_raw' ) );
							$output->set_param( 'initial_size', $output_conversion->get_param( 'initial_size' ) );
							$output->set_param( 'conversion_result', $output_conversion->get_param( 'result' ) );
						}

						$output->set_param( 'conversion_success', $output_conversion->get_param( 'success' ) );

						// Elapsed time checkpoint.
						$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );

						// Additional optimization only if conversion is successful.
						if ( $output->get_param( 'conversion_success' ) ) {

							if ( 'yes' === $image->delete_original_images ) {
								$image->image_and_thumbs_delete();
							}

							$image = Qode_Optimizer_Image_Factory::create(
								array(
									'id'         => $id,
									'media_size' => 'original',
									'additional_compression' => true,
								)
							);
							if ( $image ) {
								$output_optimization2 = $image->image_and_thumbs_optimize();

								if ( $output_optimization2->get_param( 'success' ) ) {
									$output->set_param( 'optimization2_files', $output_optimization2->get_param( 'files' ) );
								} else {
									$output->set_param( 'original_file', $output_optimization2->get_param( 'original_file' ) );
									$output->set_param( 'initial_size_raw', $output_optimization2->get_param( 'initial_size_raw' ) );
									$output->set_param( 'initial_size', $output_optimization2->get_param( 'initial_size' ) );
									$output->set_param( 'optimization2_result', $output_optimization2->get_param( 'result' ) );
								}

								$output->set_param( 'optimization2_success', $output_optimization2->get_param( 'success' ) );

								// Elapsed time checkpoint.
								$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );
							}
						} else {
							$output->set_param( 'optimization2_skipped', true );
						}
					}
				} else {
					$output->set_param( 'conversion_skipped', true );
					$output->set_param( 'optimization2_skipped', true );
				}

				if ( 'yes' === Qode_Optimizer_Options::get_option( 'enable_webp_creation' ) ) {
					$image = Qode_Optimizer_Image_Factory::create(
						array(
							'id'         => $id,
							'media_size' => 'original',
						)
					);
					if ( $image ) {
						$output_webp = $image->image_and_thumbs_create_webp();

						if ( $output_webp->get_param( 'success' ) ) {
							$output->set_param( 'webp_files', $output_webp->get_param( 'files' ) );
						} else {
							$output->set_param( 'original_file', $output_webp->get_param( 'original_file' ) );
							$output->set_param( 'initial_size_raw', $output_webp->get_param( 'initial_size_raw' ) );
							$output->set_param( 'initial_size', $output_webp->get_param( 'initial_size' ) );
							$output->set_param( 'webp_result', $output_webp->get_param( 'result' ) );
						}

						$output->set_param( 'webp_success', $output_webp->get_param( 'success' ) );

						// Elapsed time checkpoint.
						$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );
					}
				} else {
					$output->set_param( 'webp_skipped', true );
				}
			}

			$total_elapsed_time = number_format( $elapsed_time_params['total'], 4 );

			$output->set_param( 'elapsed_time', $total_elapsed_time . 's' );

			$system_log->add_log( 'Total elapsed time: ' . $total_elapsed_time . 's', true );

			$system_log->write_log();

			// Reset persistent alpha option.
			Qode_Optimizer_Options::set_option( 'alpha_exists', array() );

			if (
				$output->get_param( 'watermarked_success' ) ||
				$output->get_param( 'optimization_success' ) ||
				$output->get_param( 'conversion_success' ) ||
				$output->get_param( 'optimization2_success' ) ||
				$output->get_param( 'webp_success' )
			) {
				qode_optimizer_get_ajax_status( 'success', esc_html__( 'All images optimized successfully', 'qode-optimizer' ), $output );
			} else {
				qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Some/all images were not optimized', 'qode-optimizer' ), $output );
			}
		}
	}

	/**
	 * Ajax - Image restoration process
	 */
	public function handle_media_restoration() {

		if ( ! Qode_Optimizer_User::is_admin() ) {
			wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
		}

		if ( isset( $_POST ) && ! empty( $_POST ) ) {

			if (
				empty( $_POST['options']['qo_nonce'] ) ||
				! wp_verify_nonce( sanitize_key( $_POST['options']['qo_nonce'] ), 'qo-nonce' )
			) {
				if ( ! wp_doing_ajax() ) {
					wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
				}

				wp_die( wp_json_encode( array( 'error' => esc_html__( 'Access denied.', 'qode-optimizer' ) ) ) );
			}

			$output = new Qode_Optimizer_Output();
			$output->set_param( 'files', array() );
			$output->set_param( 'success', false );
			$output->set_param( 'elapsed_time', false );

			$id = 0;
			if ( ! empty( $_POST['options']['id'] ) ) {
				$id = intval( $_POST['options']['id'] );
			}

			$system_log = Qode_Optimizer_Log::get_instance();
			$system_log->add_log( '', true );
			$system_log->add_log( 'MEDIA MANUAL RESTORATION', true );

			$current_time        = microtime( true );
			$elapsed_time_params = array(
				'current' => $current_time,
				'local'   => 0.0,
				'total'   => 0.0,
			);

			$image = Qode_Optimizer_Image_Factory::create(
				array(
					'id'         => $id,
					'media_size' => 'original',
				)
			);
			if ( $image ) {

				$system_log->add_log( 'Image: ' . wp_basename( $image->file ), true );
				$system_log->add_log( '', true );

				$output = $image->image_and_thumbs_restore();

				// Elapsed time checkpoint.
				$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );
			}

			$total_elapsed_time = number_format( $elapsed_time_params['total'], 4 );

			$output->set_param( 'elapsed_time', $total_elapsed_time . 's' );

			$system_log->add_log( 'Total elapsed time: ' . $total_elapsed_time . 's', true );

			$system_log->write_log();

			if ( $output->get_param( 'success' ) ) {
				qode_optimizer_get_ajax_status( 'success', esc_html__( 'All images restored successfully', 'qode-optimizer' ), $output );
			} else {
				qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Some/all images were not restored', 'qode-optimizer' ), $output );
			}
		}
	}

	/**
	 * Ajax - image regeneration process
	 */
	public function handle_media_regeneration() {

		if ( ! Qode_Optimizer_User::is_admin() ) {
			wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
		}

		if ( isset( $_POST ) && ! empty( $_POST ) ) {

			if (
				empty( $_POST['options']['qo_nonce'] ) ||
				! wp_verify_nonce( sanitize_key( $_POST['options']['qo_nonce'] ), 'qo-nonce' )
			) {
				if ( ! wp_doing_ajax() ) {
					wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
				}

				wp_die( wp_json_encode( array( 'error' => esc_html__( 'Access denied.', 'qode-optimizer' ) ) ) );
			}

			$output = new Qode_Optimizer_Output();
			$output->set_param( 'success', false );
			$output->set_param( 'elapsed_time', false );

			$id = 0;
			if ( ! empty( $_POST['options']['id'] ) ) {
				$id = intval( $_POST['options']['id'] );
			}

			$system_log = Qode_Optimizer_Log::get_instance();
			$system_log->add_log( '', true );
			$system_log->add_log( 'MEDIA MANUAL THUMB REGENERATION', true );

			$current_time        = microtime( true );
			$elapsed_time_params = array(
				'current' => $current_time,
				'local'   => 0.0,
				'total'   => 0.0,
			);

			$image = Qode_Optimizer_Image_Factory::create(
				array(
					'id'         => $id,
					'media_size' => 'original',
				)
			);
			if ( $image ) {

				$system_log->add_log( 'Image: ' . wp_basename( $image->file ), true );
				$system_log->add_log( '', true );

				$output = $image->regenerate_thumbs();

				// Elapsed time checkpoint.
				$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );
			}

			$total_elapsed_time = number_format( $elapsed_time_params['total'], 4 );

			$output->set_param( 'elapsed_time', $total_elapsed_time . 's' );

			$system_log->add_log( 'Total elapsed time: ' . $total_elapsed_time . 's', true );

			$system_log->write_log();

			if ( $output->get_param( 'success' ) ) {
				qode_optimizer_get_ajax_status( 'success', esc_html__( 'All images regenerated successfully', 'qode-optimizer' ), $output );
			} else {
				qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Some/all images were not regenerated', 'qode-optimizer' ), $output );
			}
		}
	}

	/**
	 * Ajax - image watermarking process
	 */
	public function handle_media_adding_watermark() {

		if ( ! Qode_Optimizer_User::is_admin() ) {
			wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
		}

		if ( isset( $_POST ) && ! empty( $_POST ) ) {

			if (
				empty( $_POST['options']['qo_nonce'] ) ||
				! wp_verify_nonce( sanitize_key( $_POST['options']['qo_nonce'] ), 'qo-nonce' )
			) {
				if ( ! wp_doing_ajax() ) {
					wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
				}

				wp_die( wp_json_encode( array( 'error' => esc_html__( 'Access denied.', 'qode-optimizer' ) ) ) );
			}

			$output = new Qode_Optimizer_Output();
			$output->set_param( 'files', array() );
			$output->set_param( 'success', false );
			$output->set_param( 'elapsed_time', false );

			$id = 0;
			if ( ! empty( $_POST['options']['id'] ) ) {
				$id = intval( $_POST['options']['id'] );
			}

			$system_log = Qode_Optimizer_Log::get_instance();
			$system_log->add_log( '', true );
			$system_log->add_log( 'MEDIA MANUAL WATERMARKING', true );

			$current_time        = microtime( true );
			$elapsed_time_params = array(
				'current' => $current_time,
				'local'   => 0.0,
				'total'   => 0.0,
			);

			$image = Qode_Optimizer_Image_Factory::create(
				array(
					'id'         => $id,
					'media_size' => 'original',
				)
			);
			if ( $image ) {

				$system_log->add_log( 'Image: ' . wp_basename( $image->file ), true );
				$system_log->add_log( '', true );

				$output = $image->image_and_thumbs_add_watermark();

				// Elapsed time checkpoint.
				$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );
			}

			$total_elapsed_time = number_format( $elapsed_time_params['total'], 4 );

			$output->set_param( 'elapsed_time', $total_elapsed_time . 's' );

			$system_log->add_log( 'Total elapsed time: ' . $total_elapsed_time . 's', true );

			$system_log->write_log();

			if ( $output->get_param( 'success' ) ) {
				qode_optimizer_get_ajax_status( 'success', esc_html__( 'All images watermarked successfully', 'qode-optimizer' ), $output );
			} else {
				qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Some/all images were not watermarked', 'qode-optimizer' ), $output );
			}
		}
	}

	/**
	 * Ajax - image recover process
	 */
	public function handle_media_recover() {

		if ( ! Qode_Optimizer_User::is_admin() ) {
			wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
		}

		if ( isset( $_POST ) && ! empty( $_POST ) ) {

			if (
				empty( $_POST['options']['qo_nonce'] ) ||
				! wp_verify_nonce( sanitize_key( $_POST['options']['qo_nonce'] ), 'qo-nonce' )
			) {
				if ( ! wp_doing_ajax() ) {
					wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
				}

				wp_die( wp_json_encode( array( 'error' => esc_html__( 'Access denied.', 'qode-optimizer' ) ) ) );
			}

			$output = new Qode_Optimizer_Output();
			$output->set_param( 'files', array() );
			$output->set_param( 'success', false );
			$output->set_param( 'elapsed_time', false );

			$id = 0;
			if ( ! empty( $_POST['options']['id'] ) ) {
				$id = intval( $_POST['options']['id'] );
			}

			$system_log = Qode_Optimizer_Log::get_instance();
			$system_log->add_log( '', true );
			$system_log->add_log( 'MEDIA MANUAL RECOVERING', true );

			$system_log->add_log( 'Image ID: ' . $id, true );
			$system_log->add_log( '', true );

			$current_time        = microtime( true );
			$elapsed_time_params = array(
				'current' => $current_time,
				'local'   => 0.0,
				'total'   => 0.0,
			);

			// Trying to recover deleted images.
			$output = Qode_Optimizer_Image::image_and_thumbs_recover( $id );

			// Elapsed time checkpoint.
			$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );

			$total_elapsed_time = number_format( $elapsed_time_params['total'], 4 );

			$output->set_param( 'elapsed_time', $total_elapsed_time . 's' );

			$system_log->add_log( 'Total elapsed time: ' . $total_elapsed_time . 's', true );

			$system_log->write_log();

			if ( $output ) {
				qode_optimizer_get_ajax_status( 'success', esc_html__( 'All images restored successfully', 'qode-optimizer' ), $output );
			} else {
				qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Some/all images were not restored', 'qode-optimizer' ), $output );
			}
		}
	}

	/**
	 * Get all files from media
	 *
	 * @param bool $images_only
	 *
	 * @return array
	 */
	public static function get_all( $images_only = true ) {
		if ( ! is_bool( $images_only ) ) {
			$images_only = true;
		}

		$query_media_args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => - 1,
		);

		if ( $images_only ) {
			$query_media_args['post_mime_type'] = 'image';
		}

		$query_media = new WP_Query( $query_media_args );

		$all_media = array();
		foreach ( $query_media->posts as $media ) {
			$all_media[ $media->ID ] = array(
				'id'        => $media->ID,
				'name'      => $media->post_name,
				'url'       => $media->guid,
				'path'      => wp_get_original_image_path( $media->ID ),
				'mime-type' => $media->post_mime_type,
			);
		}

		return $all_media;
	}

	/**
	 * Get all files from media
	 *
	 * @param array $ids
	 *
	 * @return array
	 */
	public static function get_by_ids( $ids ) {

		if ( ! is_array( $ids ) ) {
			$ids = array();
		}

		$ids = array_map( 'intval', $ids );

		$query_media_args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => - 1,
		);

		if ( ! empty( $ids ) ) {
			$query_media_args['post__in'] = $ids;
		}

		$query_media = new WP_Query( $query_media_args );

		$all_media = array();
		foreach ( $query_media->posts as $media ) {
			$all_media[ $media->ID ] = array(
				'id'        => $media->ID,
				'name'      => $media->post_name,
				'url'       => $media->guid,
				'path'      => wp_get_original_image_path( $media->ID ),
				'mime-type' => $media->post_mime_type,
			);
		}

		return $all_media;
	}
}
