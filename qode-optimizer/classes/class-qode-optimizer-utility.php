<?php
/**
 * Implementation of utility procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Utility {

	/**
	 * Initialization
	 */
	public function init() {
		// Ajax calls.
		if ( Qode_Optimizer_User::is_admin() ) {
			add_action( 'wp_ajax_utility_action_clean_up_optimization_history', array( $this, 'handle_optimization_history_cleanup' ) );
			add_action( 'wp_ajax_utility_action_resolve_optimization_history_issues', array( $this, 'handle_optimization_history_issues_resolve' ) );
			add_action( 'wp_ajax_utility_action_delete_optimization_history', array( $this, 'handle_optimization_history_delete' ) );
			add_action( 'wp_ajax_utility_action_delete_webp_images', array( $this, 'handle_webp_images_delete' ) );
			add_action( 'wp_ajax_utility_action_delete_all_webp_images', array( $this, 'handle_all_webp_images_delete' ) );
		}
	}

	/**
	 * Counts already optimized images
	 *
	 * @return int
	 */
	public function optimization_history_count() {
		global $wpdb;

		$qo_db = new Qode_Optimizer_Db();

		$qo_db->init_charset();

		$count = 0;

		/**
		 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
		 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
		 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
		 */
		$modifications_count_query = 'SELECT COUNT( DISTINCT attachment_id ) FROM ' . $qo_db->get_modifications_table() . ' WHERE attachment_id > 0';
		$modifications_count       = $wpdb->get_var( $modifications_count_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! is_null( $modifications_count ) ) {
			$count += intval( $modifications_count );
		}

		/**
		 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
		 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
		 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
		 */
		$modifications_count_query = 'SELECT COUNT( * ) FROM ' . $qo_db->get_modifications_table() . ' WHERE media_size = "folders"';
		$modifications_count       = $wpdb->get_var( $modifications_count_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! is_null( $modifications_count ) ) {
			$count += intval( $modifications_count );
		}

		return $count;
	}

	/**
	 * Checks for duplicates and other irregularities in both, backup and modifications table.
	 *
	 * Note: This check uses $image_source option, with 'media' and 'folders' as possible values.
	 * 'folders' value is essential, as external folders images are prone to make duplicates
	 * in backup and modifications tables, not having their info saved in wp internal database tables,
	 * like media images do
	 *
	 * @param array $image_sources 'media' and 'folders' are possible values, 'folders' value is essential, and default
	 *
	 * @return array
	 */
	public function optimization_history_cleanup_check( $image_sources = array( 'folders' ) ) {
		$valid         = array(
			'valid_backup_ids'   => array(),
			'valid_modified_ids' => array(),
		);
		$cleanup_check = array(
			'backup_ids_for_deletion'         => array(),
			'backup_ids_for_deletion_count'   => 0,
			'modified_ids_for_deletion'       => array(),
			'modified_ids_for_deletion_count' => 0,
			'total_ids_for_deletion_count'    => 0,
		);

		if (
			! is_array( $image_sources ) ||
			empty( $image_sources ) ||
			! empty( array_diff( $image_sources, array( 'media', 'folders' ) ) )
		) {
			$image_sources = array( 'folders' );
		}

		global $wpdb;

		$qo_db = new Qode_Optimizer_Db();

		$qo_db->init_charset();

		$filesystem = new Qode_Optimizer_Filesystem();

		// Backup and optimization information cleanup.
		$backup_query        = 'SELECT * FROM ' . $qo_db->get_backup_table();
		$modifications_query = 'SELECT * FROM ' . $qo_db->get_modifications_table();

		$query_extension = '';

		if (
			! in_array( 'media', $image_sources, true ) ||
			! in_array( 'folders', $image_sources, true )
		) {
			$query_extension .= ' WHERE';

			$first_condition = true;
			if ( in_array( 'media', $image_sources, true ) ) {
				$query_extension .= ' attachment_id > 0';
				$first_condition  = false;
			}

			if ( in_array( 'folders', $image_sources, true ) ) {
				if ( ! $first_condition ) {
					$query_extension .= ' OR';
				}
				$query_extension .= ' media_size = "folders"';
			}
		}

		$backup_query        .= $query_extension;
		$modifications_query .= $query_extension;

		/**
		 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
		 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
		 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
		 */
		$backup_results = $wpdb->get_results( $backup_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! empty( $backup_results ) ) {
			foreach ( $backup_results as $result ) {
				$current_files = maybe_unserialize( $result['backup_paths'] );
				foreach ( $current_files as $current_file ) {
					if ( $filesystem->is_file( $current_file ) ) {
						// Do nothing, this is a valid backup information.
						$valid['valid_backup_ids'] = $result['id'];
					} elseif ( ! in_array( $result['id'], $cleanup_check['backup_ids_for_deletion'], true ) ) {
						$cleanup_check['backup_ids_for_deletion'][] = $result['id'];
					}
				}
			}
		}

		if ( ! empty( $cleanup_check['backup_ids_for_deletion'] ) ) {
			$cleanup_check['backup_ids_for_deletion_count'] = count( $cleanup_check['backup_ids_for_deletion'] );
		}

		/**
		 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
		 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
		 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
		 */
		$modifications_results = $wpdb->get_results( $modifications_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! empty( $modifications_results ) ) {
			foreach ( $modifications_results as $result ) {
				if (
					$filesystem->is_file( $result['current_path'] ) &&
					$filesystem->filesize( $result['current_path'] ) === (int) $result['current_size'] &&
					(
						(
							$result['current_path'] !== $result['previous_path'] &&
							1 === (int) $result['is_converted']
						) ||
						1 === (int) $result['is_optimized']
					)
				) {
					// Do nothing, this is a valid modification information.
					$valid['valid_modified_ids'] = $result['id'];
				} else {
					$cleanup_check['modified_ids_for_deletion'][] = $result['id'];
				}
			}
		}

		if ( ! empty( $cleanup_check['modified_ids_for_deletion'] ) ) {
			$cleanup_check['modified_ids_for_deletion_count'] = count( $cleanup_check['modified_ids_for_deletion'] );
		}

		$cleanup_check['total_ids_for_deletion_count'] = $cleanup_check['backup_ids_for_deletion_count'] + $cleanup_check['modified_ids_for_deletion_count'];

		return $cleanup_check;
	}

	/**
	 * Clean up duplicates and other irregularities in both, backup and modifications table.
	 *
	 * Note: This cleanup uses $image_source option, with 'media' and 'folders' as possible values.
	 * 'folders' value is essential, as external folders images are prone to make duplicates
	 * in backup and modifications tables, not having their info saved in wp internal database tables,
	 * like media images do
	 *
	 * @param array $image_sources 'media' and 'folders' are possible values, 'folders' value is essential, and default
	 */
	public function optimization_history_cleanup( $image_sources = array( 'folders' ) ) {
		$cleanup_check = $this->optimization_history_cleanup_check( $image_sources );

		if ( ! empty( $cleanup_check['backup_ids_for_deletion'] ) ) {
			Qode_Optimizer_Db::delete_records_from_backup_table( $cleanup_check['backup_ids_for_deletion'] );
		}

		if ( ! empty( $cleanup_check['modified_ids_for_deletion'] ) ) {
			Qode_Optimizer_Db::delete_records_from_modifications_table( $cleanup_check['modified_ids_for_deletion'] );
		}
	}

	/**
	 * Ajax - Clean up duplicates and other irregularities in both, backup and modifications table.
	 *
	 * Note: This cleanup uses $image_source option, with 'media' and 'folders' as possible values.
	 * 'folders' value is essential, as external folders images are prone to make duplicates
	 * in backup and modifications tables, not having their info saved in wp internal database tables,
	 * like media images do
	 */
	public function handle_optimization_history_cleanup() {

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
			$system_log->add_log( 'CLEANING UP OPTIMIZATION HISTORY', true );

			$current_time        = microtime( true );
			$elapsed_time_params = array(
				'current' => $current_time,
				'local'   => 0.0,
				'total'   => 0.0,
			);

			$this->optimization_history_cleanup( array( 'media', 'folders' ) );

			// Elapsed time checkpoint.
			$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );

			$total_elapsed_time = number_format( $elapsed_time_params['total'], 4 );

			$system_log->add_log( 'Total elapsed time: ' . $total_elapsed_time . 's', true );

			$system_log->write_log();

			qode_optimizer_get_ajax_status( 'success', esc_html__( 'Optimization history cleaned up', 'qode-optimizer' ) );
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

			$issue = new Qode_Optimizer_Issue();
			$issue->optimization_history_issues_resolve();

			// Elapsed time checkpoint.
			$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );

			$total_elapsed_time = number_format( $elapsed_time_params['total'], 4 );

			$system_log->add_log( 'Total elapsed time: ' . $total_elapsed_time . 's', true );

			$system_log->write_log();

			qode_optimizer_get_ajax_status( 'success', esc_html__( 'Optimization history issues resolved', 'qode-optimizer' ) );
		}
	}

	/**
	 * Ajax - Deletion of all optimization history
	 */
	public function handle_optimization_history_delete() {

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
			$system_log->add_log( 'DELETING OPTIMIZATION HISTORY', true );

			$current_time        = microtime( true );
			$elapsed_time_params = array(
				'current' => $current_time,
				'local'   => 0.0,
				'total'   => 0.0,
			);

			global $wpdb;

			$qo_db = new Qode_Optimizer_Db();

			$qo_db->init_charset();

			/**
			 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
			 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
			 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
			 */
			$wpdb->query( 'DELETE FROM ' . $qo_db->get_modifications_table() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			/**
			 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
			 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
			 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
			 */
			$wpdb->query( 'DELETE FROM ' . $qo_db->get_backup_table() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			// Elapsed time checkpoint.
			$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );

			$total_elapsed_time = number_format( $elapsed_time_params['total'], 4 );

			$system_log->add_log( 'Total elapsed time: ' . $total_elapsed_time . 's', true );

			$system_log->write_log();

			qode_optimizer_get_ajax_status( 'success', esc_html__( 'Optimization history deleted', 'qode-optimizer' ) );
		}
	}

	/**
	 * Checks for WebP images, created by system
	 *
	 * @return array
	 */
	public function webp_images_check() {
		global $wpdb;

		$qo_db = new Qode_Optimizer_Db();

		$qo_db->init_charset();

		$filesystem = new Qode_Optimizer_Filesystem();

		$webp_images_check = array(
			'webp_media_image_ids'           => array(),
			'webp_media_image_ids_count'     => 0,
			'webp_folders_image_paths'       => array(),
			'webp_folders_image_paths_count' => 0,
			'webp_total_image_count'         => 0,
		);

		/**
		 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
		 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
		 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
		 */
		$modifications_query   = 'SELECT attachment_id, current_path FROM ' . $qo_db->get_modifications_table() . ' WHERE attachment_id > 0';
		$modifications_results = $wpdb->get_results( $modifications_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! empty( $modifications_results ) ) {
			foreach ( $modifications_results as $result ) {
				$webp_file = $result['current_path'] . '.webp';
				if (
					! in_array( $result['attachment_id'], $webp_images_check['webp_media_image_ids'], true ) &&
					$filesystem->is_file( $webp_file )
				) {
					$webp_images_check['webp_media_image_ids'][] = $result['attachment_id'];
				}
			}
		}

		if ( ! empty( $webp_images_check['webp_media_image_ids'] ) ) {
			$webp_images_check['webp_media_image_ids_count'] = count( $webp_images_check['webp_media_image_ids'] );
		}

		/**
		 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
		 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
		 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
		 */
		$modifications_query   = 'SELECT * FROM ' . $qo_db->get_modifications_table() . ' WHERE media_size = "folders"';
		$modifications_results = $wpdb->get_results( $modifications_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! empty( $modifications_results ) ) {
			foreach ( $modifications_results as $result ) {
				$webp_file = $result['current_path'] . '.webp';
				if (
					$filesystem->is_file( $result['current_path'] ) &&
					$filesystem->filesize( $result['current_path'] ) === (int) $result['current_size'] &&
					(
						(
							$result['current_path'] !== $result['previous_path'] &&
							1 === (int) $result['is_converted']
						) ||
						1 === (int) $result['is_optimized']
					) &&
					! in_array( $result['current_path'], $webp_images_check['webp_folders_image_paths'], true ) &&
					$filesystem->is_file( $webp_file )
				) {
					$webp_images_check['webp_folders_image_paths'][] = realpath( $result['current_path'] );
				}
			}
		}

		if ( ! empty( $webp_images_check['webp_folders_image_paths'] ) ) {
			$webp_images_check['webp_folders_image_paths_count'] = count( $webp_images_check['webp_folders_image_paths'] );
		}

		$webp_images_check['webp_total_image_count'] = $webp_images_check['webp_media_image_ids_count'] + $webp_images_check['webp_folders_image_paths_count'];

		return $webp_images_check;
	}

	/**
	 * Deletes WebP images, created by system
	 */
	public function webp_images_delete() {
		$webp_images_check = $this->webp_images_check();

		if ( ! empty( $webp_images_check['webp_media_image_ids'] ) ) {
			foreach ( $webp_images_check['webp_media_image_ids'] as $id ) {
				$image = Qode_Optimizer_Image_Factory::create(
					array(
						'id'         => $id,
						'media_size' => 'original',
					)
				);
				if ( $image ) {
					$image->image_and_thumbs_remove_webp();
				}
			}
		}

		if ( ! empty( $webp_images_check['webp_folders_image_paths'] ) ) {
			foreach ( $webp_images_check['webp_folders_image_paths'] as $path ) {
				$image = Qode_Optimizer_Image_Factory::create(
					array(
						'file'       => $path,
						'media_size' => 'folders',
					)
				);
				if ( $image ) {
					$image->remove_webp();
				}
			}
		}
	}

	/**
	 * Ajax - Deletes WebP images, created by system
	 */
	public function handle_webp_images_delete() {

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
			$system_log->add_log( 'DELETING WEBP IMAGES', true );

			$current_time        = microtime( true );
			$elapsed_time_params = array(
				'current' => $current_time,
				'local'   => 0.0,
				'total'   => 0.0,
			);

			$this->webp_images_delete();

			// Elapsed time checkpoint.
			$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );

			$total_elapsed_time = number_format( $elapsed_time_params['total'], 4 );

			$system_log->add_log( 'Total elapsed time: ' . $total_elapsed_time . 's', true );

			$system_log->write_log();

			qode_optimizer_get_ajax_status( 'success', esc_html__( 'WebP images deleted', 'qode-optimizer' ) );
		}
	}

	/**
	 * Deletes all WebP images, created by system and all others currently located on server
	 */
	public function all_webp_images_delete() {
		$filesystem = new Qode_Optimizer_Filesystem();

		$all_webp_image_paths = $filesystem->scan_directory( qode_optimizer_get_home_path(), array( 'image/webp' ) );

		if ( ! empty( $all_webp_image_paths ) ) {
			foreach ( $all_webp_image_paths as $path ) {
				$filesystem->delete_file( $path );
			}
		}
	}

	/**
	 * Ajax - Deletes all WebP images, created by system and all others currently located on server
	 */
	public function handle_all_webp_images_delete() {

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
			$system_log->add_log( 'DELETING ALL WEBP IMAGES', true );

			$current_time        = microtime( true );
			$elapsed_time_params = array(
				'current' => $current_time,
				'local'   => 0.0,
				'total'   => 0.0,
			);

			$this->all_webp_images_delete();

			// Elapsed time checkpoint.
			$elapsed_time_params = $system_log->set_elapsed_time_checkpoint( $elapsed_time_params );

			$total_elapsed_time = number_format( $elapsed_time_params['total'], 4 );

			$system_log->add_log( 'Total elapsed time: ' . $total_elapsed_time . 's', true );

			$system_log->write_log();

			qode_optimizer_get_ajax_status( 'success', esc_html__( 'All WebP images deleted', 'qode-optimizer' ) );
		}
	}

	/**
	 * Check if variable is iterable
	 *
	 * @param mixed $variable
	 *
	 * @return bool
	 */
	public static function is_iterable( $variable ) {
		return ! empty( $variable ) &&
			(
				is_array( $variable ) ||
				$variable instanceof Traversable
			);
	}

	/**
	 * Check if multiple array keys exist in array
	 *
	 * @param array $keys
	 * @param array $items
	 *
	 * @return bool
	 */
	public static function multiple_array_keys_exist( $keys, $items ) {
		return is_array( $keys ) && is_array( $items ) && ! array_diff_key( array_flip( $keys ), $items );
	}

	/**
	 * Check if multiple array values exist in array
	 *
	 * @param array $values
	 * @param array $items
	 *
	 * @return bool
	 */
	public static function multiple_array_values_exist( $values, $items ) {
		return is_array( $values ) && is_array( $items ) && ! array_diff( $values, $items );
	}

	/**
	 * Gets the current PHP memory limit or finds a reasonable default
	 *
	 * @return int The memory limit in bytes
	 */
	public static function memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			// Reasonable default, current usage + 16M.
			$current_memory = memory_get_usage( true );
			$memory_limit   = round( $current_memory / ( 1024 * 1024 ) ) + 16;
		}

		if (
			! $memory_limit ||
			- 1 === intval( $memory_limit )
		) {
			// Unlimited, set to 32GB.
			$memory_limit = '32000M';
		}
		if ( stripos( $memory_limit, 'g' ) ) {
			$memory_limit = intval( $memory_limit ) * 1024 * 1024 * 1024;
		} else {
			$memory_limit = intval( $memory_limit ) * 1024 * 1024;
		}

		return $memory_limit;
	}

	/**
	 * Checks if additional memory is needed
	 *
	 * @param int $memory_required The amount of memory required to continue. Default 1048576 ( = 1M )
	 *
	 * @return bool
	 */
	public static function additional_memory_needed( $memory_required = 1048576 ) {
		$memory_limit = static::memory_limit();

		$current_memory = memory_get_usage( true ) + $memory_required;

		if ( $current_memory >= $memory_limit ) {
			return true;
		}

		return false;
	}

	/**
	 * Raise the memory limit even higher (to 512M) than WP default of 256M if necessary
	 *
	 * @param int|string $memory_limit The amount of memory to allocate
	 *
	 * @return int|string The new amount of memory to allocate, if it was only 256M or lower
	 */
	public static function raise_memory_limit( $memory_limit ) {
		if (
			'256M' === $memory_limit ||
			(
				is_int( $memory_limit ) &&
				$memory_limit <= 268435456 /* = 256M */
			)
		) {
			return '512M';
		} else {
			return $memory_limit;
		}
	}

	/**
	 * Handles memory requirements
	 *
	 * @param int $memory_required The amount of memory required to continue. Default 1048576 ( = 1M )
	 * @param string $context Context in which the function is called. Default 'image'.
	 */
	public static function handle_memory_requirements( $memory_required = 1048576, $context = 'image' ) {
		$memory_required = intval( $memory_required );
		if ( $memory_required < 0 ) {
			$memory_required = 0;
		}

		if (
			is_string( $context ) &&
			in_array( $context, array( 'image' ), true ) &&
			function_exists( 'wp_raise_memory_limit' ) &&
			static::additional_memory_needed( $memory_required )
		) {
			add_filter( $context . '_memory_limit', 'Qode_Optimizer_Utility::raise_memory_limit' );
			wp_raise_memory_limit( $context );
		}
	}

	/**
	 * Corrects integer values, typically supplied by system options
	 *
	 * @param mixed $actual_value
	 * @param int $min_value
	 * @param int $max_value
	 * @param int $default_value
	 *
	 * @return int
	 */
	public static function correct_integer( $actual_value, $min_value = 0, $max_value = 100, $default_value = 75 ) {
		$min_value     = intval( $min_value );
		$max_value     = intval( $max_value );
		$default_value = intval( $default_value );

		$actual_value = ! empty( $actual_value ) ? intval( $actual_value ) : $default_value;
		if (
			$min_value > $max_value ||
			$min_value > $default_value ||
			$max_value < $default_value ||
			$min_value > $actual_value ||
			$max_value < $actual_value
		) {
			$actual_value = $default_value;
		}

		return $actual_value;
	}

	/**
	 * Corrects yes/no values, typically supplied by system options
	 *
	 * @param mixed $actual_value
	 * @param string $default_value (yes/no)
	 *
	 * @return string (yes/no)
	 */
	public static function correct_yesno( $actual_value, $default_value = 'no' ) {
		if ( 'no' === $default_value ) {
			$other_value = 'yes';
		} else {
			$default_value = 'yes';
			$other_value   = 'no';
		}
		if (
			empty( $actual_value ) ||
			$other_value !== $actual_value
		) {
			$actual_value = $default_value;
		}

		return $actual_value;
	}

	/**
	 * Corrects select values, typically supplied by system options
	 *
	 * @param mixed $actual_value
	 * @param array $range_of_values
	 * @param string $default_value
	 *
	 * @return string
	 */
	public static function correct_select( $actual_value, $range_of_values, $default_value = '' ) {
		if ( ! is_array( $range_of_values ) ) {
			$range_of_values = array();
		}
		if (
			empty( $actual_value ) ||
			! in_array( $actual_value, $range_of_values, true )
		) {
			$actual_value = $default_value;
		}

		return $actual_value;
	}
}
