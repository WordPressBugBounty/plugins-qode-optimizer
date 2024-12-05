<?php
/**
 * Plugin uninstall procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

if ( ! function_exists( 'qode_optimizer_get_htaccess_path' ) ) {
	/**
	 * Get .htaccess path
	 *
	 * @return string .htaccess file path
	 */
	function qode_optimizer_get_htaccess_path() {
		$htaccess_folder = qode_optimizer_get_home_path();
		$htaccess_file   = $htaccess_folder . '.htaccess';

		$home     = get_option( 'home' );
		$site_url = get_option( 'siteurl' );

		// Site Url is sub-domain of WP.
		if ( $home !== $site_url ) {
			$url_base       = rtrim( $htaccess_folder, '/' );
			$url_difference = ltrim( str_replace( $home, '', $site_url ), '/' );

			$subdomain_htaccess_folder = trailingslashit( $url_base . '/' . $url_difference );
			$subdomain_htaccess_file   = $subdomain_htaccess_folder . '.htaccess';

			if ( is_file( $subdomain_htaccess_file ) ) {
				return $subdomain_htaccess_file;
			}
		}

		return $htaccess_file;
	}
}

if ( current_user_can( 'delete_plugins' ) ) {
	// Check for .htaccess rules the plugin might have added and, if any found, delete them.
	$added_rules = extract_from_markers(
		qode_optimizer_get_htaccess_path(),
		'QODE OPTIMIZER'
	);

	if ( ! empty( $added_rules ) ) {
		insert_with_markers(
			qode_optimizer_get_htaccess_path(),
			'QODE OPTIMIZER',
			''
		);
	}

	// Delete db tables the plugin added.
	global $wpdb;

	$added_db_tables = array(
		$wpdb->prefix . 'qo_backup',
		$wpdb->prefix . 'qo_modifications',
	);

	foreach ( $added_db_tables as $added_db_table ) {
		/**
		 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
		 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
		 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
		 */
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $added_db_table ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	}
}
