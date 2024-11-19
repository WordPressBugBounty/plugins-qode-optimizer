<?php
/**
 * Implementation of backup procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Backup {

	/**
	 * Backup folder name
	 *
	 * @var string $backup_folder_name
	 */
	private $backup_folder_name = 'backup';

	/**
	 * Retrieve image backup folder name
	 *
	 * @return string
	 */
	public function get_backup_folder_name() {
		return $this->backup_folder_name;
	}

	/**
	 * Set image backup folder name
	 *
	 * @param string $folder_name
	 */
	public function set_backup_folder_name( $folder_name = '' ) {
		if ( ! empty( $folder_name ) ) {
			$this->backup_folder_name = $folder_name;
		}
	}

	/**
	 * Backup image in database and local folder
	 *
	 * @param Qode_Optimizer_Image $image
	 */
	public function backup_file_in_db_and_folder( $image ) {
		if ( $image instanceof Qode_Optimizer_Image ) {
			global $wpdb;

			$qo_db = new Qode_Optimizer_Db();

			$qo_db->init_charset();

			// Check if image already has backup, if not make one.

			/**
			 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
			 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
			 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
			 */
			$backup_query   = $wpdb->prepare( 'SELECT * FROM ' . $qo_db->get_backup_table() . ' WHERE attachment_id = %d', $image->id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$backup_results = $wpdb->get_results( $backup_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if (
				! is_null( $backup_results ) &&
				empty( $backup_results )
			) {
				$backup = $this->create_backup( $image );

				if ( ! empty( $backup ) ) {
					$insert_data = array(
						'attachment_id'          => $image->id,
						'media_size'             => $image->media_size,
						'original_file'          => realpath( $image->file ),
						'original_mime_type'     => $image->get_mime_type(),
						'wp_attached_file'       => $image->attached_file_meta,
						'wp_attachment_metadata' => maybe_serialize( $image->metadata ),
						'original_paths'         => maybe_serialize( $backup['original_paths'] ),
						'backup_paths'           => maybe_serialize( $backup['backup_paths'] ),
					);
					$wpdb->insert( $qo_db->get_backup_table(), $insert_data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				}
			}
		}
	}

	/**
	 * Backup folders image in database and local folder
	 *
	 * @param Qode_Optimizer_Image $image
	 */
	public function backup_folders_file_in_db_and_folder( $image ) {
		if ( $image instanceof Qode_Optimizer_Image ) {
			global $wpdb;

			$qo_db = new Qode_Optimizer_Db();

			$qo_db->init_charset();

			// Check if image already has backup, if not make one.

			/**
			 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
			 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
			 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
			 */
			$backup_query   = $wpdb->prepare( 'SELECT * FROM ' . $qo_db->get_backup_table() . ' WHERE media_size = "folders" AND original_file = %s', $image->file ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$backup_results = $wpdb->get_results( $backup_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( ! is_null( $backup_results ) ) {
				if ( empty( $backup_results ) ) {
					$backup = $this->create_folders_backup( $image );

					if ( ! empty( $backup ) ) {
						$insert_data = array(
							'media_size'         => $image->media_size,
							'original_file'      => realpath( $image->file ),
							'original_mime_type' => $image->get_mime_type(),
							'original_paths'     => maybe_serialize( $backup['original_paths'] ),
							'backup_paths'       => maybe_serialize( $backup['backup_paths'] ),
						);
						$wpdb->insert( $qo_db->get_backup_table(), $insert_data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					}
				}

				Qode_Optimizer_Options::set_option( 'backup_already_made', true );
			}
		}
	}

	/**
	 * Checks if a genuine folders image backup already exists. Genuine backup should have the same file size as original
	 *
	 * @param Qode_Optimizer_Image $image
	 */
	public function genuine_folders_file_backup_exists( $image ) {}

	/**
	 * Create image backup
	 *
	 * @param Qode_Optimizer_Image $image
	 * @param bool $move_original
	 *
	 * @return array|bool
	 */
	public function create_backup( $image, $move_original = false ) {
		if ( $image instanceof Qode_Optimizer_Image ) {
			$filesystem = new Qode_Optimizer_Filesystem();

			$backup_folder = pathinfo( $image->file, PATHINFO_DIRNAME ) . DIRECTORY_SEPARATOR . $this->get_backup_folder_name();

			$output = array(
				'original_paths' => array(),
				'backup_paths'   => array(),
			);

			if ( wp_mkdir_p( $backup_folder ) ) {
				// Backup original image.
				$output['original_paths']['original'] = realpath( $image->file );
				$output['backup_paths']['original']   = $backup_folder . DIRECTORY_SEPARATOR . pathinfo( $image->file, PATHINFO_BASENAME );

				if ( $move_original ) {
					if ( ! $filesystem->rename_file(
						$image->file,
						$output['backup_paths']['original']
					) ) {
						return false;
					}
				} else {
					if ( ! $filesystem->copy_file(
						$image->file,
						$output['backup_paths']['original']
					) ) {
						return false;
					}
				}

				$output['backup_paths']['original'] = realpath( $output['backup_paths']['original'] );

				$thumb_path_info = $image->get_all_image_thumb_path_info( 'creation' );
				foreach ( $thumb_path_info as $info ) {
					$thumb_path = $info['dirname'] . DIRECTORY_SEPARATOR . $info['basename'];
					if ( $filesystem->is_file( $thumb_path ) ) {
						// Backup thumbs.
						$output['original_paths'][ $info['media_size'] ] = realpath( $thumb_path );
						$output['backup_paths'][ $info['media_size'] ]   = $backup_folder . DIRECTORY_SEPARATOR . $info['basename'];

						if ( $move_original ) {
							if ( ! $filesystem->rename_file(
								$thumb_path,
								$output['backup_paths'][ $info['media_size'] ]
							) ) {
								return false;
							}
						} else {
							if ( ! $filesystem->copy_file(
								$thumb_path,
								$output['backup_paths'][ $info['media_size'] ]
							) ) {
								return false;
							}
						}

						$output['backup_paths'][ $info['media_size'] ] = realpath( $output['backup_paths'][ $info['media_size'] ] );
					}
				}

				return $output;
			}
		}

		return false;
	}

	/**
	 * Create folders image backup
	 *
	 * @param Qode_Optimizer_Image $image
	 * @param bool $move_original
	 *
	 * @return array|bool
	 */
	public function create_folders_backup( $image, $move_original = false ) {
		if ( $image instanceof Qode_Optimizer_Image ) {
			$filesystem = new Qode_Optimizer_Filesystem();

			$backup_folder = pathinfo( $image->file, PATHINFO_DIRNAME ) . DIRECTORY_SEPARATOR . $this->get_backup_folder_name();

			$output = array(
				'original_paths' => array(),
				'backup_paths'   => array(),
			);

			if ( wp_mkdir_p( $backup_folder ) ) {
				// Backup folders image.
				$output['original_paths']['folders'] = realpath( $image->file );
				$output['backup_paths']['folders']   = $backup_folder . DIRECTORY_SEPARATOR . pathinfo( $image->file, PATHINFO_BASENAME );

				if ( $move_original ) {
					if ( ! $filesystem->rename_file(
						$image->file,
						$output['backup_paths']['folders']
					) ) {
						return false;
					}
				} else {
					if ( ! $filesystem->copy_file(
						$image->file,
						$output['backup_paths']['folders']
					) ) {
						return false;
					}
				}

				$output['backup_paths']['folders'] = realpath( $output['backup_paths']['folders'] );

				return $output;
			}
		}

		return false;
	}

	/**
	 * Get image info from backup
	 *
	 * @param int $id
	 *
	 * @return array|bool
	 */
	public static function get_backup_data( $id ) {
		$id = intval( $id );

		global $wpdb;

		$qo_db = new Qode_Optimizer_Db();

		$qo_db->init_charset();

		// Check if image already has backup, if it has restore image.

		/**
		 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
		 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
		 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
		 */
		$backup_query   = $wpdb->prepare( 'SELECT * FROM ' . $qo_db->get_backup_table() . ' WHERE attachment_id = %d LIMIT 1', $id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$backup_results = $wpdb->get_row( $backup_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! empty( $backup_results ) ) {
			return array(
				'attachment_id'          => $backup_results['attachment_id'],
				'media_size'             => $backup_results['media_size'],
				'original_file'          => $backup_results['original_file'],
				'original_mime_type'     => $backup_results['original_mime_type'],
				'wp_attached_file'       => $backup_results['wp_attached_file'],
				'wp_attachment_metadata' => maybe_unserialize( $backup_results['wp_attachment_metadata'] ),
				'original_paths'         => maybe_unserialize( $backup_results['original_paths'] ),
				'backup_paths'           => maybe_unserialize( $backup_results['backup_paths'] ),
			);
		}

		return false;
	}

	/**
	 * Get folders image info from backup
	 *
	 * @param string $file File path
	 *
	 * @return array|bool
	 */
	public static function get_folders_backup_data( $file ) {
		global $wpdb;

		$qo_db = new Qode_Optimizer_Db();

		$qo_db->init_charset();

		// Check if image already has backup, if it has restore image.

		/**
		 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
		 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
		 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
		 */
		$modifications_query   = $wpdb->prepare( 'SELECT * FROM ' . $qo_db->get_modifications_table() . ' WHERE media_size = "folders" AND ( ( current_path <> previous_path AND is_converted = 1 ) OR is_optimized = 1 ) AND current_path = %s ORDER BY last_modification DESC, id ASC LIMIT 1', $file ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$modifications_results = $wpdb->get_row( $modifications_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! empty( $modifications_results ) ) {
			/**
			 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
			 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
			 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
			 */
			$backup_query   = $wpdb->prepare( 'SELECT * FROM ' . $qo_db->get_backup_table() . ' WHERE media_size = "folders" AND original_file = %s LIMIT 1', $modifications_results['previous_path'] ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$backup_results = $wpdb->get_row( $backup_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( ! empty( $backup_results ) ) {
				return array(
					'attachment_id'          => $backup_results['attachment_id'],
					'media_size'             => $backup_results['media_size'],
					'original_file'          => $backup_results['original_file'],
					'original_mime_type'     => $backup_results['original_mime_type'],
					'wp_attached_file'       => $backup_results['wp_attached_file'],
					'wp_attachment_metadata' => maybe_unserialize( $backup_results['wp_attachment_metadata'] ),
					'original_paths'         => maybe_unserialize( $backup_results['original_paths'] ),
					'backup_paths'           => maybe_unserialize( $backup_results['backup_paths'] ),
				);
			}
		}

		return false;
	}

	/**
	 * Restore image from database and local folder
	 *
	 * @param int $id
	 *
	 * @return array|bool
	 */
	public function restore_file_from_db_and_folder( $id ) {
		$id          = intval( $id );
		$backup_data = static::get_backup_data( $id );

		if ( ! empty( $backup_data ) ) {
			return static::restore_backup( $backup_data ) ? $backup_data : false;
		}

		return false;
	}

	/**
	 * Restore image from database and local folder
	 *
	 * @param string $file File path
	 *
	 * @return array|bool
	 */
	public function restore_folders_file_from_db_and_folder( $file ) {
		$backup_data = ! empty( $file ) ? static::get_folders_backup_data( $file ) : '';

		if ( ! empty( $backup_data ) ) {
			return static::restore_folders_backup( $backup_data ) ? $backup_data : false;
		}

		return false;
	}

	/**
	 * Restore image backup
	 *
	 * @param array $backup_data
	 * @param bool $move_backup
	 *
	 * @return bool
	 */
	public static function restore_backup( $backup_data, $move_backup = false ) {
		if (
			is_array( $backup_data ) &&
			Qode_Optimizer_Utility::multiple_array_keys_exist(
				array(
					'attachment_id',
					'wp_attached_file',
					'wp_attachment_metadata',
					'original_paths',
					'backup_paths',
				),
				$backup_data
			)
		) {
			global $wpdb;

			$qo_db = new Qode_Optimizer_Db();

			$qo_db->init_charset();

			$filesystem = new Qode_Optimizer_Filesystem();

			foreach ( $backup_data['backup_paths'] as $index => $backup_path ) {
				if (
					$filesystem->is_file( $backup_path ) &&
					array_key_exists( $index, $backup_data['original_paths'] )
				) {
					if ( $move_backup ) {
						if ( ! $filesystem->rename_file(
							$backup_path,
							$backup_data['original_paths'][ $index ]
						) ) {
							return false;
						}
					} else {
						if ( ! $filesystem->copy_file(
							$backup_path,
							$backup_data['original_paths'][ $index ]
						) ) {
							return false;
						}
					}
				}
			}

			if ( $move_backup ) {
				/**
				 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
				 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
				 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
				 */
				$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $qo_db->get_backup_table() . ' WHERE attachment_id = %d', $backup_data['attachment_id'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}

			return true;
		}

		return false;
	}

	/**
	 * Restore folders image backup
	 *
	 * @param array $backup_data
	 * @param bool $move_backup
	 *
	 * @return bool
	 */
	public static function restore_folders_backup( $backup_data, $move_backup = false ) {
		if (
			is_array( $backup_data ) &&
			Qode_Optimizer_Utility::multiple_array_keys_exist(
				array(
					'original_file',
					'original_paths',
					'backup_paths',
				),
				$backup_data
			)
		) {
			global $wpdb;

			$qo_db = new Qode_Optimizer_Db();

			$qo_db->init_charset();

			$filesystem = new Qode_Optimizer_Filesystem();

			foreach ( $backup_data['backup_paths'] as $index => $backup_path ) {
				if (
					$filesystem->is_file( $backup_path ) &&
					array_key_exists( $index, $backup_data['original_paths'] )
				) {
					if ( $move_backup ) {
						if ( ! $filesystem->rename_file(
							$backup_path,
							$backup_data['original_paths'][ $index ]
						) ) {
							return false;
						}
					} else {
						if ( ! $filesystem->copy_file(
							$backup_path,
							$backup_data['original_paths'][ $index ]
						) ) {
							return false;
						}
					}
				}
			}

			if ( $move_backup ) {
				/**
				 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
				 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
				 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
				 */
				$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $qo_db->get_backup_table() . ' WHERE media_size = "folders" AND original_file = %s', $backup_data['original_file'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}

			return true;
		}

		return false;
	}

	/**
	 * Delete image in database and local folder
	 *
	 * @param Qode_Optimizer_Image $image
	 *
	 * @return bool
	 */
	public function delete_file_in_db_and_folder( $image ) {
		if ( $image instanceof Qode_Optimizer_Image ) {
			$backup_data = static::get_backup_data( $image->id );
			if (
				is_array( $backup_data ) &&
				Qode_Optimizer_Utility::multiple_array_keys_exist(
					array(
						'attachment_id',
						'wp_attached_file',
						'wp_attachment_metadata',
						'original_paths',
						'backup_paths',
					),
					$backup_data
				)
			) {
				global $wpdb;

				$qo_db = new Qode_Optimizer_Db();

				$qo_db->init_charset();

				$filesystem = new Qode_Optimizer_Filesystem();

				foreach ( $backup_data['backup_paths'] as $backup_path ) {
					if ( $filesystem->is_file( $backup_path ) ) {
						$filesystem->delete_file( $backup_path );
					}
				}

				/**
				 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
				 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
				 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
				 */
				$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $qo_db->get_backup_table() . ' WHERE attachment_id = %d', $image->id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

				return true;
			}
		}

		return false;
	}

	/**
	 * Delete folders image in database and local folder
	 *
	 * @param Qode_Optimizer_Image $image
	 *
	 * @return bool
	 */
	public function delete_folders_file_in_db_and_folder( $image ) {
		if ( $image instanceof Qode_Optimizer_Image ) {
			$backup_data = static::get_folders_backup_data( $image->file );
			if (
				is_array( $backup_data ) &&
				Qode_Optimizer_Utility::multiple_array_keys_exist(
					array(
						'original_paths',
						'backup_paths',
					),
					$backup_data
				)
			) {
				global $wpdb;

				$qo_db = new Qode_Optimizer_Db();

				$qo_db->init_charset();

				$filesystem = new Qode_Optimizer_Filesystem();

				foreach ( $backup_data['backup_paths'] as $backup_path ) {
					if ( $filesystem->is_file( $backup_path ) ) {
						$filesystem->delete_file( $backup_path );
					}
				}

				/**
				 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
				 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
				 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
				 */
				$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $qo_db->get_backup_table() . ' WHERE media_size = "folders" AND original_file = %s', $image->file ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

				return true;
			}
		}

		return false;
	}
}
