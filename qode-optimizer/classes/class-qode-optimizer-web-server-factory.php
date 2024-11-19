<?php
/**
 * Implementation of web server factory procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Web_Server_Factory {

	/**
	 * Server types allowed
	 */
	const ALLOWED_SERVER_TYPES = array(
		'general'               => array(
			'apache',
			'nginx',
		),
		'server_type_class_map' => array(
			'apache' => 'Qode_Optimizer_Web_Server_Apache',
			'nginx'  => 'Qode_Optimizer_Web_Server_Nginx',
		),
	);

	/**
	 * Web Server object creation
	 *
	 * @return Qode_Optimizer_Web_Server|false
	 */
	public static function create() {
		if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
			$server_software = strtolower( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) );

			foreach ( static::ALLOWED_SERVER_TYPES['general'] as $server_type ) {
				if ( false !== strpos( $server_software, $server_type ) ) {
					/**
					 * PHP 8+
					 * return new ( static::ALLOWED_SERVER_TYPES['server_type_class_map'][ $server_type ] )();
					 * */

					// PHP 7+.
					$webserver_classname = static::ALLOWED_SERVER_TYPES['server_type_class_map'][ $server_type ];
					return new $webserver_classname();
				}
			}
		}

		return false;
	}
}
