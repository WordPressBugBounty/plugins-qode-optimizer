<?php
/**
 * Implementation of bulk procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Bulk {

	/**
	 * Initialization
	 */
	public function init() {
		// Ajax calls.
		if ( Qode_Optimizer_User::is_admin() ) {
			add_action( 'wp_ajax_bulk_action_optimize_and_webp', array( $this, 'handle_bulk_optimization_and_webp_creation' ) );
			add_action( 'wp_ajax_bulk_action_folders_optimize_and_webp', array( $this, 'handle_bulk_folders_optimization_and_webp_creation' ) );
			add_action( 'wp_ajax_bulk_action_thumbs_regenerate', array( $this, 'handle_bulk_thumbs_regeneration' ) );
			add_action( 'wp_ajax_bulk_action_restore', array( $this, 'handle_bulk_restoration' ) );
			add_action( 'wp_ajax_bulk_action_folders_restore', array( $this, 'handle_bulk_folders_restoration' ) );
		}
	}

	/**
	 * Ajax - bulk image optimization process
	 */
	public function handle_bulk_optimization_and_webp_creation() {

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

			$system_log = Qode_Optimizer_Log::get_instance();
			$system_log->add_log( '', true );
			$system_log->add_log( 'BULK AUTOMATIC OPTIMIZATION', true );

			$current_time        = microtime( true );
			$elapsed_time_params = array(
				'current' => $current_time,
				'local'   => 0.0,
				'total'   => 0.0,
			);

			$force_optimization = 'no';
			if (
				! empty( $_POST['options']['force_optimization'] ) &&
				in_array( $_POST['options']['force_optimization'], array( 'yes', 'no' ), true )
			) {
				$force_optimization = sanitize_text_field( wp_unslash( $_POST['options']['force_optimization'] ) );
			}

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
					if ( 'yes' === $force_optimization ) {
						// Restore files first.
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
					} else {
						$output->set_param( 'original_file', wp_basename( $current_file ) );
						$output->set_param( 'watermarked_skipped', true );
						$output->set_param( 'optimization_skipped', true );
						$output->set_param( 'optimization_result', esc_html__( 'skipped', 'qode-optimizer' ) );
						qode_optimizer_get_ajax_status( 'success', esc_html__( 'Images already optimized and skipped', 'qode-optimizer' ), $output );
					}
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
	 * Ajax - bulk folders image optimization process
	 */
	public function handle_bulk_folders_optimization_and_webp_creation() {

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

			$path = ! empty( $_POST['options']['path'] ) ? sanitize_text_field( wp_unslash( $_POST['options']['path'] ) ) : '';

			// Reset persistent backup already made option.
			Qode_Optimizer_Options::set_option( 'backup_already_made', false );

			$system_log = Qode_Optimizer_Log::get_instance();
			$system_log->add_log( '', true );
			$system_log->add_log( 'BULK AUTOMATIC OPTIMIZATION', true );

			$current_time        = microtime( true );
			$elapsed_time_params = array(
				'current' => $current_time,
				'local'   => 0.0,
				'total'   => 0.0,
			);

			$force_optimization = 'no';
			if (
				! empty( $_POST['options']['force_optimization'] ) &&
				in_array( $_POST['options']['force_optimization'], array( 'yes', 'no' ), true )
			) {
				$force_optimization = sanitize_text_field( wp_unslash( $_POST['options']['force_optimization'] ) );
			}

			$image = Qode_Optimizer_Image_Factory::create(
				array(
					'file'       => $path,
					'media_size' => 'folders',
				)
			);
			if ( $image ) {

				$system_log->add_log( 'Image: ' . wp_basename( $image->file ), true );
				$system_log->add_log( '', true );

				$system_log->add_log( '* SYSTEM FETCHING DATA', true );

				global $wpdb;

				$current_file = $image->file;

				$qo_db = new Qode_Optimizer_Db();

				$qo_db->init_charset();

				/**
				 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
				 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
				 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
				 */
				$modifications_query   = $wpdb->prepare( 'SELECT * FROM ' . $qo_db->get_modifications_table() . ' WHERE media_size = "folders" AND ( ( current_path <> previous_path AND is_converted = 1 ) OR is_optimized = 1 ) AND current_path = %s ORDER BY last_modification DESC, id ASC', $image->file ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$modifications_results = $wpdb->get_results( $modifications_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

				// Elapsed time checkpoint.
				$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );

				if ( ! empty( $modifications_results ) ) {
					if ( 'yes' === $force_optimization ) {
						// Restore files first.
						$output_restore = $image->folders_image_restore();
						if ( $output_restore->get_param( 'success' ) ) {
							$output->set_param( 'restoration_success', $output_restore->get_param( 'success' ) );
							$output->set_param( 'restoration_result', esc_html__( 'Successful', 'qode-optimizer' ) );

							// PATH change, after restoration.
							$output_data = $output_restore->get_param( 'data' );
							$path        = $output_data['file'];
							$image       = Qode_Optimizer_Image_Factory::create(
								array(
									'file'       => $path,
									'media_size' => 'folders',
								)
							);

							Qode_Optimizer_Options::set_option( 'backup_already_made', true );
						}

						// Elapsed time checkpoint.
						$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );
					} else {
						$output->set_param( 'original_file', wp_basename( $current_file ) );
						$output->set_param( 'watermarked_skipped', true );
						$output->set_param( 'optimization_skipped', true );
						$output->set_param( 'optimization_result', esc_html__( 'skipped', 'qode-optimizer' ) );
						qode_optimizer_get_ajax_status( 'success', esc_html__( 'Images already optimized and skipped', 'qode-optimizer' ), $output );
					}
				}
			}

			if ( $image ) {
				if ( Qode_Optimizer_Options::get_option( 'watermark_image_path' ) ) {

					$output_watermarked = $image->folders_image_add_watermark();

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
							'file'       => $path,
							'media_size' => 'folders',
						)
					);

					// Elapsed time checkpoint.
					$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );
				} else {
					$output->set_param( 'watermarked_skipped', true );
				}

				$output_optimization = $image->folders_image_optimize();

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
							'file'       => $path,
							'media_size' => 'folders',
						)
					);
					if ( $image ) {
						$output_conversion = $image->folders_image_convert();

						if ( $output_conversion->get_param( 'success' ) ) {
							$files = $output_conversion->get_param( 'files' );
							$output->set_param( 'conversion_files', $files );

							// PATH change, after conversion.
							$file = $files[0];
							if ( $file instanceof Qode_Optimizer_Output ) {
								$path = $file->get_param( 'file' );
							}
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
							$image = Qode_Optimizer_Image_Factory::create(
								array(
									'file'       => $path,
									'media_size' => 'folders',
									'additional_compression' => true,
								)
							);
							if ( $image ) {
								$output_optimization2 = $image->folders_image_optimize();

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
							'file'       => $path,
							'media_size' => 'folders',
						)
					);
					if ( $image ) {
						$output_webp = $image->folders_image_create_webp();

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

			// Reset persistent backup already made option.
			Qode_Optimizer_Options::set_option( 'backup_already_made', false );

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
	 * Ajax - bulk thumbnails regeneration process
	 */
	public function handle_bulk_thumbs_regeneration() {

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
			$system_log->add_log( 'BULK AUTOMATIC THUMB REGENERATION', true );

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
				qode_optimizer_get_ajax_status( 'success', esc_html__( 'Success', 'qode-optimizer' ), $output );
			} else {
				qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Failure', 'qode-optimizer' ), $output );
			}
		}
	}

	/**
	 * Ajax - bulk image restoration process
	 */
	public function handle_bulk_restoration() {

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
			$system_log->add_log( 'BULK AUTOMATIC RESTORATION', true );

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
				qode_optimizer_get_ajax_status( 'success', esc_html__( 'Success', 'qode-optimizer' ), $output );
			} else {
				qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Failure', 'qode-optimizer' ), $output );
			}
		}
	}

	/**
	 * Ajax - bulk folders image restoration process
	 */
	public function handle_bulk_folders_restoration() {

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

			$path = ! empty( $_POST['options']['path'] ) ? sanitize_text_field( wp_unslash( $_POST['options']['path'] ) ) : '';

			$system_log = Qode_Optimizer_Log::get_instance();
			$system_log->add_log( '', true );
			$system_log->add_log( 'BULK AUTOMATIC RESTORATION', true );

			$current_time        = microtime( true );
			$elapsed_time_params = array(
				'current' => $current_time,
				'local'   => 0.0,
				'total'   => 0.0,
			);

			$image = Qode_Optimizer_Image_Factory::create(
				array(
					'file'       => $path,
					'media_size' => 'folders',
				)
			);
			if ( $image ) {

				$system_log->add_log( 'Image: ' . wp_basename( $image->file ), true );
				$system_log->add_log( '', true );

				$output = $image->folders_image_restore();

				// Elapsed time checkpoint.
				$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );
			}

			$total_elapsed_time = number_format( $elapsed_time_params['total'], 4 );

			$output->set_param( 'elapsed_time', $total_elapsed_time . 's' );

			$system_log->add_log( 'Total elapsed time: ' . $total_elapsed_time . 's', true );

			$system_log->write_log();

			if ( $output->get_param( 'success' ) ) {
				qode_optimizer_get_ajax_status( 'success', esc_html__( 'Success', 'qode-optimizer' ), $output );
			} else {
				qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Failure', 'qode-optimizer' ), $output );
			}
		}
	}
}
