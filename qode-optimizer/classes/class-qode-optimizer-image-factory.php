<?php
/**
 * Implementation of image factory support procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Image_Factory {

	/**
	 * Mime-type to object mapping
	 */
	const MIME_TYPE_OBJECT_MAPPING = array(
		'image/jpeg'    => 'Qode_Optimizer_Jpeg',
		'image/png'     => 'Qode_Optimizer_Png',
		'image/gif'     => 'Qode_Optimizer_Gif',
		'image/svg+xml' => 'Qode_Optimizer_Svg',
	);

	/**
	 * Image object creation
	 *
	 * @param array $params
	 *
	 * @return Qode_Optimizer_Image|false
	 */
	public static function create( $params ) {
		if ( ! is_array( $params ) ) {
			$params = array();
		}

		if ( array_key_exists( 'id', $params ) ) {
			$params['file'] = wp_get_original_image_path( intval( $params['id'] ) );
		} elseif (
			! array_key_exists( 'file', $params ) ||
			! is_string( $params['file'] )
		) {
			$params['file'] = '';
		}

		$file = realpath( $params['file'] );

		$filesystem = new Qode_Optimizer_Filesystem();

		if ( $filesystem->is_file( $file ) ) {
			$mime_type = $filesystem->get_mime_type( $file );

			if ( array_key_exists( $mime_type, static::MIME_TYPE_OBJECT_MAPPING ) ) {
				/**
				 * PHP 8+
				 * return new ( static::MIME_TYPE_OBJECT_MAPPING[ $mime_type ] )( $params );
				 * */

				// PHP 7+.
				$image_classname = static::MIME_TYPE_OBJECT_MAPPING[ $mime_type ];
				return new $image_classname( $params );
			}
		}

		return false;
	}
}
