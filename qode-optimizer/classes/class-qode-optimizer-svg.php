<?php
/**
 * Implementation of SVG image support procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

require_once QODE_OPTIMIZER_CLASSES_FOLDER_PATH . '/class-qode-optimizer-image.php';

class Qode_Optimizer_Svg extends Qode_Optimizer_Image {

	/**
	 * Mime-type
	 */
	const MIME_TYPE = 'image/svg+xml';

	/**
	 * Conversion mime-type
	 */
	const CONVERSION_MIME_TYPE = false;

	/**
	 * Static GD Image create from SVG
	 *
	 * @param string $file File path
	 *
	 * @return resource|false an image resource identifier on success, false on errors
	 */
	public static function gd_image_create_static( $file ) {
		return false;
	}

	/**
	 * GD Image create from SVG
	 *
	 * @return resource|false an image resource identifier on success, false on errors
	 */
	public function gd_image_create() {
		return false;
	}

	/**
	 * Static GD Image save as SVG
	 *
	 * @param resource $gd_image GD image
	 * @param string $file File path
	 *
	 * @return bool
	 */
	public static function gd_image_save_static( $gd_image, $file ) {
		return false;
	}

	/**
	 * GD Image save as SVG
	 *
	 * @param resource $gd_image GD image
	 *
	 * @return bool
	 */
	public function gd_image_save( $gd_image ) {
		return false;
	}

	/**********************************************
	 * RESIZING (OPTIONAL) AND COMPRESSING (MANDATORY)
	 *
	 * 2nd step in optimizing images
	 * 4th step also, after optional image conversion
	 *********************************************/

	/**
	 * Get compression method from options
	 *
	 * @return array
	 */
	public function get_compression_method_from_options() {
		return array();
	}

	/**
	 * Get additional compression method from options
	 *
	 * @return array
	 */
	public function get_additional_compression_method_from_options() {
		return array();
	}

	/**
	 * Compress Image using IMagick
	 *
	 * @param string $compressed_file Compressed image path
	 *
	 * @return bool
	 */
	public function imagick_compress( $compressed_file ) {
		return false;
	}

	/**
	 * Compress Image using GD
	 *
	 * @param string $compressed_file Compressed image path
	 *
	 * @return bool
	 */
	public function gd_compress( $compressed_file ) {
		return false;
	}

	/**
	 * Lossless image compression using tools
	 *
	 * @param string $compressed_file Compressed image path
	 *
	 * @return bool
	 */
	public function lossless_clt_compress( $compressed_file ) {
		return false;
	}

	/**
	 * Lossy image compression using tools
	 *
	 * @param string $compressed_file Compressed image path
	 *
	 * @return bool
	 */
	public function lossy_clt_compress( $compressed_file ) {
		return false;
	}

	/**********************************************
	 * CONVERSION (OPTIONAL)
	 *
	 * 3rd step in optimizing images
	 *********************************************/

	/**
	 * Image conversion using built-in PHP functions.
	 *
	 * @access public
	 *
	 * @param array $conversion_methods_queue List of methods for trying to convert image with, if one fails system tries another one from the list
	 * @param bool $check_size Converted/original file sizes comparison. Converted file size must be smaller than original file size
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function convert( $conversion_methods_queue = array( 'gmagick', 'imagick', 'gd' ), $check_size = false ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'file', false );
		$output->add_message( 'SVG conversion is not possible' );

		$system_log->add_log( 'SVG conversion is not possible' );

		return $output;
	}

	/**
	 * Get conversion params
	 *
	 * @return array
	 */
	protected function get_conversion_params() {
		return array();
	}

	/**
	 * Gmagick image conversion by mime-type
	 *
	 * @param string $new_file Converted image path
	 * @param array $conversion_params Conversion params
	 *
	 * @return int File size
	 */
	protected function gmagick_convert_by_mime_type( $new_file, $conversion_params ) {
		return 0;
	}

	/**
	 * Imagick image conversion by mime-type
	 *
	 * @param string $new_file Converted image path
	 * @param array $conversion_params Conversion params
	 *
	 * @return int File size
	 */
	protected function imagick_convert_by_mime_type( $new_file, $conversion_params ) {
		return 0;
	}

	/**
	 * GD image conversion by mime-type
	 *
	 * @param string $new_file Converted image path
	 * @param array $conversion_params Conversion params
	 *
	 * @return int File size
	 */
	protected function gd_convert_by_mime_type( $new_file, $conversion_params ) {
		return 0;
	}

	/**********************************************
	 * WEBP CREATION (OPTIONAL)
	 *
	 * 5th step in optimizing images
	 *********************************************/

	/**
	 * Creates WebP images alongside SVG files.
	 *
	 * @param array $conversion_methods_queue List of methods for trying to create WebP image with, if one fails system tries another one from the list
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function create_webp( $conversion_methods_queue = array( 'imagick', 'gd' ) ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'file', false );
		$output->add_message( 'SVG conversion to WebP is not possible' );

		$system_log->add_log( 'SVG conversion to WebP is not possible' );

		return $output;
	}

	/**
	 * Create WebP Image using IMagick
	 *
	 * @param string $webp_file Created WebP image path
	 *
	 * @return bool
	 */
	public function imagick_create_webp( $webp_file ) {
		return false;
	}

	/**
	 * Create WebP Image using GD
	 *
	 * @param string $webp_file Created WebP image path
	 *
	 * @return bool
	 */
	public function gd_create_webp( $webp_file ) {
		return false;
	}
}
