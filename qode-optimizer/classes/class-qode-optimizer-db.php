<?php
/**
 * Implementation of database procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Db {

	/**
	 * Table name for storing image meta data before optimization/conversion
	 *
	 * @var string $backup_table
	 */
	private $backup_table = 'qo_backup';

	/**
	 * Table name for storing image modification data
	 *
	 * @var string $modifications_table
	 */
	private $modifications_table = 'qo_modifications';

	/**
	 * Instantiate DB object
	 */
	public function __construct() {
		$this->init_charset();
	}

	/**
	 * Ensures use of some variant of utf8 for interacting with the images table.
	 */
	public function init_charset() {
		global $wpdb;

		if ( false === strpos( $wpdb->charset, 'utf8' ) ) {
			$wpdb->charset = 'utf8';
		}
	}

	/**
	 * Fetch backup table name
	 *
	 * @param bool $include_prefix
	 *
	 * @return string
	 */
	public function get_backup_table( $include_prefix = true ) {
		global $wpdb;
		return ( $include_prefix ? $wpdb->prefix : '' ) . $this->backup_table;
	}

	/**
	 * Set backup table name
	 *
	 * @param string $backup_table
	 */
	public function set_backup_table( $backup_table ) {
		if ( ! empty( $backup_table ) ) {
			$this->backup_table = $backup_table;
		}
	}

	/**
	 * Fetch modifications table name
	 *
	 * @param bool $include_prefix
	 *
	 * @return string
	 */
	public function get_modifications_table( $include_prefix = true ) {
		global $wpdb;
		return ( $include_prefix ? $wpdb->prefix : '' ) . $this->modifications_table;
	}

	/**
	 * Set modifications table name
	 *
	 * @param string $modifications_table
	 */
	public function set_modifications_table( $modifications_table ) {
		if ( ! empty( $modifications_table ) ) {
			$this->modifications_table = $modifications_table;
		}
	}

	/**
	 * Adds/upgrades table in db for storing status of all images that have been optimized.
	 *
	 * @global object $wpdb
	 */
	public function install_necessary_tables() {
		$qo_backup        = $this->get_backup_table();
		$qo_modifications = $this->get_modifications_table();

		// Include the upgrade library to install/upgrade a table.
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE $qo_backup (
			`id` int NOT NULL AUTO_INCREMENT,
			`attachment_id` int NOT NULL,
			`media_size` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
			`original_file` text COLLATE utf8mb4_general_ci NOT NULL,
			`original_mime_type` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
			`wp_attached_file` text COLLATE utf8mb4_general_ci NOT NULL,
			`wp_attachment_metadata` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
			`original_paths` text COLLATE utf8mb4_general_ci NOT NULL,
			`backup_paths` text COLLATE utf8mb4_general_ci NOT NULL,
			PRIMARY KEY (`id`),
			KEY `attachment_id` (`attachment_id`),
			KEY `wp_attached_file` (`wp_attached_file`(512))
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

		dbDelta( $sql );

		$sql = "CREATE TABLE $qo_modifications (
			`id` int NOT NULL AUTO_INCREMENT,
			`attachment_id` int NOT NULL,
			`media_size` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
			`current_path` text COLLATE utf8mb4_general_ci NOT NULL,
			`previous_path` text COLLATE utf8mb4_general_ci NOT NULL,
			`result` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
			`current_size` int NOT NULL DEFAULT '0',
			`original_size` int NOT NULL DEFAULT '0',
			`is_optimized` tinyint(1) NOT NULL DEFAULT '0',
  			`is_converted` tinyint(1) NOT NULL DEFAULT '0',
			`last_modification` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`),
			KEY `attachment_id` (`attachment_id`),
			KEY `current_path` (`current_path`(512))
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

		dbDelta( $sql );
	}

	/**
	 * Get info for already modified images
	 *
	 * @global object $wpdb
	 *
	 * @param int $attachment_id
	 *
	 * @return array
	 */
	public function get_already_modified_info( $attachment_id ) {
		global $wpdb;

		$this->init_charset();

		/**
		 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
		 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
		 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
		 */
		$backup_query   = $wpdb->prepare( 'SELECT * FROM ' . $this->get_backup_table() . ' WHERE attachment_id = %d', $attachment_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$backup_results = $wpdb->get_results( $backup_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		/**
		 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
		 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
		 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
		 */
		$modifications_query   = $wpdb->prepare( 'SELECT * FROM ' . $this->get_modifications_table() . ' WHERE attachment_id = %d', $attachment_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$modifications_results = $wpdb->get_results( $modifications_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return array(
			'backup'        => $backup_results,
			'modifications' => $modifications_results,
		);
	}

	/**
	 * Deletes records from BACKUP table
	 *
	 * @param array $ids List of ids from backup table to delete
	 *
	 * @return bool
	 */
	public static function delete_records_from_backup_table( $ids = array() ) {
		if ( ! is_array( $ids ) ) {
			$ids = array();
		}

		if ( ! empty( $ids ) ) {

			global $wpdb;

			$qo_db = new Qode_Optimizer_Db();

			$qo_db->init_charset();

			$sql = 'DELETE FROM ' . $qo_db->get_backup_table() . ' WHERE id IN (' . implode( ', ', array_fill( 0, count( $ids ), '%s' ) ) . ')';

			/**
			 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
			 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
			 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
			 */
			$wpdb->query( call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $ids ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			return true;
		}

		return false;
	}

	/**
	 * Deletes records from MODIFICATIONS table
	 *
	 * @param array $ids List of ids from modifications table to delete
	 *
	 * @return bool
	 */
	public static function delete_records_from_modifications_table( $ids = array() ) {
		if ( ! is_array( $ids ) ) {
			$ids = array();
		}

		if ( ! empty( $ids ) ) {

			global $wpdb;

			$qo_db = new Qode_Optimizer_Db();

			$qo_db->init_charset();

			$sql = 'DELETE FROM ' . $qo_db->get_modifications_table() . ' WHERE id IN (' . implode( ', ', array_fill( 0, count( $ids ), '%s' ) ) . ')';

			/**
			 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
			 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
			 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
			 */
			$wpdb->query( call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $ids ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			return true;
		}

		return false;
	}
}
