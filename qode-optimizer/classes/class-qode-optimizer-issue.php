<?php
/**
 * Implementation of issue procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Issue {

	/**
	 * Initialization
	 */
	public function init() {
		// Ajax calls.
		if ( Qode_Optimizer_User::is_admin() ) {
			add_action( 'wp_ajax_issue_action_optimization_history_issues_resolve', array( $this, 'handle_optimization_history_issues_resolve' ) );
		}
	}

	/**
	 * Checks for issues in optimization history, if already optimized images, with backups created, are modified in filesystem by hand
	 *
	 * @return array Regeneration params
	 */
	public function optimization_history_issues_check() {
		global $wpdb;

		$qo_db = new Qode_Optimizer_Db();

		$qo_db->init_charset();

		$filesystem = new Qode_Optimizer_Filesystem();

		$regeneration_params = array(
			'media'                   => array(),
			'media_count'             => 0,
			'media_fixable'           => array(),
			'media_fixable_count'     => 0,
			'media_problematic'       => array(),
			'media_problematic_count' => 0,
			'folders'                 => array(),
			'folders_count'           => 0,
			'folders_fixable'         => array(),
			'folders_fixable_count'   => 0,
			'total_count'             => 0,
		);

		// All images from Media.
		$all_media = Qode_Optimizer_Media::get_all();
		if ( ! empty( $all_media ) ) {
			foreach ( $all_media as $media ) {
				$current_id = intval( $media['id'] );

				$original_file      = wp_get_original_image_path( $current_id );
				$current_file       = get_attached_file( $current_id );
				$current_media_size = $original_file !== $current_file && false !== strpos( $current_file, '-scaled.' ) ? 'scaled' : 'original';

				$metadata_in_db = maybe_unserialize( wp_get_attachment_metadata( $current_id ) );
				$filesize_in_db = $metadata_in_db['filesize'];
				$filesize_live  = $filesystem->filesize( $current_file );

				$regeneration_params['media'][ $current_id ] = array(
					'has_scaled'       => 'scaled' === $current_media_size,
					'changed'          => false,
					'original_changed' => false,
					'scaled_changed'   => false,
				);

				if ( $filesize_live > 0 ) {

					if ( $filesize_in_db !== $filesize_live ) {
						$regeneration_params['media'][ $current_id ]['changed'] = true;
						if ( 'scaled' === $current_media_size ) {
							$regeneration_params['media'][ $current_id ]['scaled_changed'] = true;
						} else {
							$regeneration_params['media'][ $current_id ]['original_changed'] = true;
						}
					}

					if ( 'scaled' === $current_media_size ) {
						/**
						 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
						 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
						 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
						 */
						$modifications_query   = $wpdb->prepare( 'SELECT * FROM ' . $qo_db->get_modifications_table() . ' WHERE media_size = "original" AND attachment_id = %d ORDER BY last_modification DESC, id ASC LIMIT 1', $current_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						$modifications_results = $wpdb->get_results( $modifications_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						if ( ! empty( $modifications_results ) ) {
							$original_filesize_in_db = (int) $modifications_results[0]['current_size'];
							$original_filesize_live  = $filesystem->filesize( $original_file );

							if (
								$original_filesize_live > 0 &&
								$original_filesize_in_db !== $original_filesize_live
							) {
								$regeneration_params['media'][ $current_id ]['changed']          = true;
								$regeneration_params['media'][ $current_id ]['original_changed'] = true;
							}
						}
					}

					// phpcs:ignore Squiz.Operators.IncrementDecrementUsage
					$regeneration_params['media_count']++;
				}

				if ( $regeneration_params['media'][ $current_id ]['changed'] ) {
					if ( $regeneration_params['media'][ $current_id ]['original_changed'] ) {
						$regeneration_params['media_fixable'][ $current_id ] = array(
							'path' => $original_file,
						);
						// phpcs:ignore Squiz.Operators.IncrementDecrementUsage
						$regeneration_params['media_fixable_count']++;
					} else {
						$regeneration_params['media_problematic'][ $current_id ] = array(
							'path' => $original_file,
						);
						// phpcs:ignore Squiz.Operators.IncrementDecrementUsage
						$regeneration_params['media_problematic_count']++;
					}
				}
			}
		}

		// All images from additional folders.

		/**
		 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
		 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
		 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
		 */
		$modifications_query   = 'SELECT * FROM ' . $qo_db->get_modifications_table() . ' WHERE media_size = "folders"'; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$modifications_results = $wpdb->get_results( $modifications_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! empty( $modifications_results ) ) {
			foreach ( $modifications_results as $result ) {
				$current_path = realpath( $result['current_path'] );
				if (
					$filesystem->is_file( $current_path ) &&
					! array_key_exists( $current_path, $regeneration_params['folders'] )
				) {
					$regeneration_params['folders'][ $current_path ] = array(
						'changed' => false,
					);

					$original_filesize_in_db = (int) $result['current_size'];
					$original_filesize_live  = $filesystem->filesize( $current_path );

					if (
						$original_filesize_live > 0 &&
						$original_filesize_in_db !== $original_filesize_live
					) {
						$regeneration_params['folders'][ $current_path ]['changed'] = true;
					}
				}

				if ( $regeneration_params['folders'][ $current_path ]['changed'] ) {
					$regeneration_params['folders_fixable'][] = $current_path;
					// phpcs:ignore Squiz.Operators.IncrementDecrementUsage
					$regeneration_params['folders_fixable_count']++;
				}
			}
		}

		$regeneration_params['total_count'] = $regeneration_params['media_fixable_count'] + $regeneration_params['media_problematic_count'] + $regeneration_params['folders_fixable_count'];

		return $regeneration_params;
	}

	/**
	 * Resolution of all optimization history issues found
	 */
	public function optimization_history_issues_resolve() {
		$issues_check = $this->optimization_history_issues_check();

		if ( ! empty( $issues_check['media'] ) ) {
			foreach ( $issues_check['media'] as $id => $params ) {
				// System shouldn't start issue resolution process for images with no change on ORIGINAL (uploaded) version of image.
				if (
					$params['changed'] &&
					$params['original_changed']
				) {
					$image = Qode_Optimizer_Image_Factory::create(
						array(
							'id'         => $id,
							'media_size' => 'original',
						)
					);
					if ( $image ) {
						$image->resolution();
					}
				}
			}
		}

		if ( ! empty( $issues_check['folders'] ) ) {
			foreach ( $issues_check['folders'] as $path => $params ) {
				if ( $params['changed'] ) {
					$image = Qode_Optimizer_Image_Factory::create(
						array(
							'file'       => $path,
							'media_size' => 'folders',
						)
					);
					if ( $image ) {
						$image->folders_image_resolution();
					}
				}
			}
		}
	}

	/**
	 * Ajax - Resolution of all optimization history issues found
	 */
	public function handle_optimization_history_issues_resolve() {

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

			$system_log = Qode_Optimizer_Log::get_instance();
			$system_log->add_log( '', true );
			$system_log->add_log( 'RESOLVING OPTIMIZATION HISTORY ISSUES', true );

			$current_time        = microtime( true );
			$elapsed_time_params = array(
				'current' => $current_time,
				'local'   => 0.0,
				'total'   => 0.0,
			);

			$this->optimization_history_issues_resolve();

			// Elapsed time checkpoint.
			$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );

			$total_elapsed_time = number_format( $elapsed_time_params['total'], 4 );

			$system_log->add_log( 'Total elapsed time: ' . $total_elapsed_time . 's', true );

			$system_log->write_log();

			qode_optimizer_get_ajax_status( 'success', esc_html__( 'Optimization history issues resolved', 'qode-optimizer' ) );
		}
	}
}
