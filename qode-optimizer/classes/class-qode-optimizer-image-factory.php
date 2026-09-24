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
	 * Resolve a readable local file path for an attachment.
	 * Prefers the original upload, then falls back to the attached/scaled file.
	 *
	 * @param int $attachment_id
	 *
	 * @return string|false
	 */
	public static function resolve_attachment_file( $attachment_id ) {
		$attachment_id = intval( $attachment_id );
		if ( $attachment_id < 1 ) {
			return false;
		}

		$filesystem = new Qode_Optimizer_Filesystem();
		$candidates = array(
			wp_get_original_image_path( $attachment_id ),
			get_attached_file( $attachment_id ),
		);

		foreach ( $candidates as $candidate ) {
			if ( ! is_string( $candidate ) || '' === $candidate ) {
				continue;
			}

			if ( $filesystem->is_file( $candidate ) ) {
				$real_path = realpath( $candidate );

				return false !== $real_path ? $real_path : $candidate;
			}
		}

		return false;
	}

	/**
	 * Inspect whether an image can be optimized and why not.
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public static function inspect( $params ) {
		$result = array(
			'ok'        => false,
			'file'      => '',
			'mime_type' => '',
			'reason'    => 'invalid',
			'message'   => esc_html__( 'File not found or unsupported type', 'qode-optimizer' ),
		);

		if ( ! is_array( $params ) ) {
			$params = array();
		}

		$file = '';
		if ( array_key_exists( 'id', $params ) ) {
			$resolved = static::resolve_attachment_file( intval( $params['id'] ) );
			$file     = false !== $resolved ? $resolved : '';
		} elseif ( array_key_exists( 'file', $params ) && is_string( $params['file'] ) ) {
			$file = $params['file'];
		}

		$filesystem = new Qode_Optimizer_Filesystem();

		if ( ! is_string( $file ) || '' === $file || ! $filesystem->is_file( $file ) ) {
			$result['reason']  = 'missing_file';
			$result['message'] = esc_html__( 'File not found', 'qode-optimizer' );

			return $result;
		}

		$real_path      = realpath( $file );
		$result['file'] = false !== $real_path ? $real_path : $file;
		$mime_type      = $filesystem->get_mime_type( $result['file'] );
		$result['mime_type'] = is_string( $mime_type ) ? $mime_type : '';

		if ( ! $mime_type || ! array_key_exists( $mime_type, static::MIME_TYPE_OBJECT_MAPPING ) ) {
			$result['reason'] = 'unsupported_type';

			if ( 'image/webp' === $mime_type ) {
				$result['message'] = esc_html__( 'WebP original images are already compressed and cannot be re-optimized', 'qode-optimizer' );
			} elseif ( is_string( $mime_type ) && '' !== $mime_type ) {
				$result['message'] = sprintf(
					/* translators: %s: MIME type */
					esc_html__( 'Unsupported type: %s', 'qode-optimizer' ),
					$mime_type
				);
			} else {
				$result['message'] = esc_html__( 'Unsupported type', 'qode-optimizer' );
			}

			return $result;
		}

		$result['ok']      = true;
		$result['reason']  = '';
		$result['message'] = '';

		return $result;
	}

	/**
	 * Image object creation
	 *
	 * @param array $params
	 *
	 * @return Qode_Optimizer_Image|false
	 */
	public static function create( $params ) {
		$inspection = static::inspect( $params );
		if ( empty( $inspection['ok'] ) ) {
			return false;
		}

		if ( ! is_array( $params ) ) {
			$params = array();
		}

		$params['file']  = $inspection['file'];
		$image_classname = static::MIME_TYPE_OBJECT_MAPPING[ $inspection['mime_type'] ];

		return new $image_classname( $params );
	}
}
