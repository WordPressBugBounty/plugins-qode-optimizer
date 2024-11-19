<?php
/**
 * Implementation of GIF image support procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

require_once QODE_OPTIMIZER_CLASSES_FOLDER_PATH . '/class-qode-optimizer-image.php';

class Qode_Optimizer_Gif extends Qode_Optimizer_Image {

	/**
	 * Mime-type
	 */
	const MIME_TYPE = 'image/gif';

	/**
	 * Conversion mime-type
	 */
	const CONVERSION_MIME_TYPE = 'image/png';

	/**
	 * Create an image from file
	 *
	 * @param array $params
	 */
	public function __construct( $params ) {
		parent::__construct( $params );

		if ( array_key_exists( 'compression_quality', $params ) ) {
			$this->compression_quality = Qode_Optimizer_Utility::correct_integer( $params['compression_quality'] );
		} else {
			$this->compression_quality = ! is_null( Qode_Optimizer_Options::get_option( 'gif_compression_quality' ) ) ?
				Qode_Optimizer_Options::get_option( 'gif_compression_quality' ) : 75;
		}
	}

	/**
	 * Static GD Image create from GIF
	 *
	 * @param string $file File path
	 *
	 * @return resource|false an image resource identifier on success, false on errors
	 */
	public static function gd_image_create_static( $file ) {
		$image = Qode_Optimizer_Support::get_system_param( 'gd_support_exists' ) ? imagecreatefromgif( $file ) : '';

		if ( ! empty( $image ) ) {
			return $image;
		}

		return false;
	}

	/**
	 * GD Image create from GIF
	 *
	 * @return resource|false an image resource identifier on success, false on errors
	 */
	public function gd_image_create() {
		$image = Qode_Optimizer_Support::get_system_param( 'gd_support_exists' ) ? imagecreatefromgif( $this->file ) : '';

		if ( ! empty( $image ) ) {
			return $image;
		}

		return false;
	}

	/**
	 * Static GD Image save as GIF
	 *
	 * @param resource $gd_image GD image
	 * @param string $file File path
	 *
	 * @return bool
	 */
	public static function gd_image_save_static( $gd_image, $file ) {
		if (
			Qode_Optimizer_Support::get_system_param( 'gd_support_exists' ) &&
			$gd_image instanceof GdImage
		) {
			imagegif( $gd_image, $file );
			imagedestroy( $gd_image );

			return true;
		}

		return false;
	}

	/**
	 * GD Image save as GIF
	 *
	 * @param resource $gd_image GD image
	 *
	 * @return bool
	 */
	public function gd_image_save( $gd_image ) {
		if (
			Qode_Optimizer_Support::get_system_param( 'gd_support_exists' ) &&
			$gd_image instanceof GdImage
		) {
			imagegif( $gd_image, $this->file );
			imagedestroy( $gd_image );

			return true;
		}

		return false;
	}

	/**
	 * PNG transparency check.
	 *
	 * @return bool
	 */
	public function alpha_exists() {
		$system_log = Qode_Optimizer_Log::get_instance();
		$system_log->add_log( 'Check for alpha transparency existing' );

		$alpha_exists_option = Qode_Optimizer_Options::get_option( 'alpha_exists' );

		if (
			$alpha_exists_option &&
			Qode_Optimizer_Utility::multiple_array_keys_exist(
				array(
					'mime_type',
					'value',
				),
				$alpha_exists_option
			) &&
			static::MIME_TYPE === $alpha_exists_option['mime_type']
		) {
			$alpha_exists = $alpha_exists_option['value'];

			$system_log->add_log( 'Alpha transparency info fetched from cache (speeds up the process a lot)' );
		} else {
			if ( Qode_Optimizer_Support::get_system_param( 'gd_support_exists' ) ) {
				$alpha_exists = $this->gd_alpha_exists();
			} else {
				$alpha_exists = $this->plain_alpha_exists();
			}

			Qode_Optimizer_Options::set_option(
				'alpha_exists',
				array(
					'mime_type' => static::MIME_TYPE,
					'value'     => $alpha_exists,
				)
			);

			$system_log->add_log( 'Alpha transparency info fetched from reading a file, and cached for future use' );
		}

		$system_log->add_log( 'Alpha transparency detected: ' . ( $alpha_exists ? 'YES' : 'NO' ) );

		return $alpha_exists;
	}

	/**
	 * GD Gif transparency check
	 *
	 * @return bool
	 */
	public function gd_alpha_exists() {
		if ( Qode_Optimizer_Support::get_system_param( 'gd_support_exists' ) ) {
			$image = imagecreatefromgif( $this->file );
			if ( imagecolortransparent( $image ) >= 0 ) {
				return true;
			}

			list( $width, $height ) = wp_getimagesize( $this->file );
			for ( $h = 0; $h < $height; $h++ ) {
				for ( $w = 0; $w < $width; $w++ ) {
					$color_index = imagecolorat( $image, $w, $h );
					$rgb         = imagecolorsforindex( $image, $color_index );
					if ( $rgb['alpha'] > 0 ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Animated GIF check.
	 *
	 * @return bool
	 */
	public function is_animated_gif() {
		// Trying to open the file in read-only buffered mode. WP_Filesystem methods don't have the alternative for PHP native fopen MODE param, which is essential in our use case.
		$fh = fopen( $this->file, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $fh ) {
			return false;
		}

		$count = 0;
		/**
		 * An animated gif contains multiple "frames", with each frame having a header made up of:
		 * - a static 4-byte sequence (\x00\x21\xF9\x04)
		 * - 4 variable bytes
		 * - a static 2-byte sequence (\x00\x2C)
		 *
		 * We read through the file til we reach the end of the file, or we've found at least 2 frame headers
		 */
		while ( ! feof( $fh ) && $count < 2 ) {
			// read 100kb at a time. WP_Filesystem methods don't have the alternative for PHP native fread LENGTH param, which is essential in our use case.
			$chunk  = fread( $fh, 1024 * 100 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			$count += preg_match_all( '#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches );
		}

		// WP_Filesystem methods don't have the alternative for PHP native fclose function.
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return $count > 1;
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
		$compression_method = ! is_null( Qode_Optimizer_Options::get_option( 'gif_compression_method' ) ) ?
			Qode_Optimizer_Options::get_option( 'gif_compression_method' ) : '';

		if ( 'none' === $compression_method ) {
			return array();
		} elseif ( $this->is_animated_gif() ) {
			return array( 'lossy-clt' );
		} elseif ( 'lossy-native' === $compression_method ) {
			return array( 'imagick', 'gd' );
		} else {
			return array( $compression_method );
		}
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
	 *
	 * @throws ImagickException Throws ImagickException on error.
	 */
	public function imagick_compress( $compressed_file ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		$system_log->add_log( 'Attempting to compress the image using Imagick' );

		$image = new Imagick( $this->file );
		if ( false === $image ) {
			$system_log->add_log( 'No compression was made using Imagick' );

			return false;
		}

		$filesystem = new Qode_Optimizer_Filesystem();

		$image->setImageFormat( 'GIF' );
		/**
		SetImageCompressionQuality not working on GIF images
		$image->setImageCompressionQuality( Qode_Optimizer_Options::get_compression_quality() );
		*/

		$image_blob = $image->getImageBlob();
		// alternative for PHP native file_put_contents function.
		$filesystem->put_contents( $compressed_file, $image_blob );

		$system_log->add_log( 'Image was successfully compressed using Imagick' );

		return true;
	}

	/**
	 * Compress Image using GD
	 *
	 * @param string $compressed_file Compressed image path
	 *
	 * @return bool
	 */
	public function gd_compress( $compressed_file ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		$system_log->add_log( 'Attempting to compress the image using GD' );

		$image = imagecreatefromgif( $this->file );
		if ( false === $image ) {
			$system_log->add_log( 'No compression was made using GD' );

			return false;
		}
		if ( ! imageistruecolor( $image ) ) {
			// Converting to true color.
			imagepalettetotruecolor( $image );
		}
		if ( $this->alpha_exists() ) {
			// Saving alpha and disabling alpha blending.
			imagealphablending( $image, false );
			imagesavealpha( $image, true );
		}

		imagegif( $image, $compressed_file );
		imagedestroy( $image );

		$system_log->add_log( 'Image was successfully compressed using GD' );

		return true;
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
		$system_log = Qode_Optimizer_Log::get_instance();

		$filesystem = new Qode_Optimizer_Filesystem();

		if ( $filesystem->copy_file( $this->file, $compressed_file ) ) {
			$success = false;

			if ( $this->gifsicle_compress( $compressed_file ) ) {
				$success = true;
			}

			return $success;
		}

		return false;
	}

	/**
	 * Compress Image using GIFSICLE tool - lossy compression
	 *
	 * @param string $compressed_file Compressed image path
	 *
	 * @return bool
	 */
	public function gifsicle_compress( $compressed_file ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( Qode_Optimizer_Support::is_tool_working( 'gifsicle' ) ) {
			$system_log->add_log( 'Attempting to compress the image using Gifsicle' );

			$filesystem = new Qode_Optimizer_Filesystem();

			if ( $filesystem->is_file( $compressed_file ) ) {

				$initial_filesize = $filesystem->filesize( $compressed_file );

				// Lossy.
				$lossy_option = '--lossy=' . (int) ( 100 - $this->compression_quality );

				// Temporary filename.
				$tmpfile = $compressed_file . '.tmp';

				$tool_path = Qode_Optimizer_Support::get_tool_working_path( 'gifsicle' );

				$system_log->add_log( $tool_path . ' -O3 --careful ' . $lossy_option . ' -o ' . escapeshellarg( $tmpfile ) . ' ' . escapeshellarg( $compressed_file ) );

				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
				if ( false !== exec( $tool_path . ' -O3 --careful ' . $lossy_option . ' -o ' . escapeshellarg( $tmpfile ) . ' ' . escapeshellarg( $compressed_file ) ) ) {

					if ( $filesystem->is_file( $tmpfile ) ) {

						$new_filesize = $filesystem->filesize( $tmpfile );

						if (
							$new_filesize &&
							$new_filesize < $initial_filesize
						) {

							if ( static::MIME_TYPE === $filesystem->get_mime_type( $tmpfile ) ) {
								$filesystem->rename_file( $tmpfile, $compressed_file );

								// Success.
								$system_log->add_log( 'Image was successfully compressed using Gifsicle' );

								return true;
							}
						}

						$filesystem->delete_file( $tmpfile );
					}
				}
			}
		}

		$system_log->add_log( 'No compression was made using Gifsicle' );

		return false;
	}

	/**********************************************
	 * CONVERSION (OPTIONAL)
	 *
	 * 3rd step in optimizing images
	 *********************************************/

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
		$system_log = Qode_Optimizer_Log::get_instance();

		$system_log->add_log( 'Attempting to convert the image using Gmagick' );

		$filesystem = new Qode_Optimizer_Filesystem();

		if ( Qode_Optimizer_Support::get_system_param( 'gmagick_support_exists' ) ) {
			try {
				$gmagick = new Gmagick( $this->file );
				$gmagick->stripimage();
				$gmagick->setimageformat( 'PNG' );
				$gmagick->writeimage( $new_file );
			} catch ( Exception $gmagick_error ) {
				// Gmagick error report.
				$system_log->add_log( 'Some error occurred while converting image using Gmagick' );
			}

			$system_log->add_log( 'Image was successfully converted using Gmagick' );

			return $filesystem->filesize( $new_file );
		}

		$system_log->add_log( 'No conversion was made using Gmagick' );

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
		$system_log = Qode_Optimizer_Log::get_instance();

		$system_log->add_log( 'Attempting to convert the image using Imagick' );

		$filesystem = new Qode_Optimizer_Filesystem();

		if ( Qode_Optimizer_Support::get_system_param( 'imagick_support_exists' ) ) {
			try {
				$imagick = new Imagick( $this->file );
				$imagick->stripImage();
				$imagick->setImageFormat( 'PNG' );
				$imagick->writeImage( $new_file );
			} catch ( Exception $imagick_error ) {
				// Imagick error report.
				$system_log->add_log( 'Some error occurred while converting image using Imagick' );
			}

			$system_log->add_log( 'Image was successfully converted using Imagick' );

			return $filesystem->filesize( $new_file );
		}

		$system_log->add_log( 'No conversion was made using Imagick' );

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
		$system_log = Qode_Optimizer_Log::get_instance();

		$system_log->add_log( 'Attempting to convert the image using GD' );

		$filesystem = new Qode_Optimizer_Filesystem();

		if ( Qode_Optimizer_Support::get_system_param( 'gd_support_exists' ) ) {
			imagepng( imagecreatefromgif( $this->file ), $new_file );

			$system_log->add_log( 'Image was successfully converted using GD' );

			return $filesystem->filesize( $new_file );
		}

		$system_log->add_log( 'No conversion was made using GD' );

		return 0;
	}

	/**********************************************
	 * WEBP CREATION (OPTIONAL)
	 *
	 * 5th step in optimizing images
	 *********************************************/

	/**
	 * Override create webp method
	 *
	 * @param $conversion_methods_queue
	 *
	 * @return array
	 */
	public function override_create_webp_method( $conversion_methods_queue ) {
		if ( $this->is_animated_gif() ) {
			return array( 'gif2webp' );
		} elseif (
			'tools' === $this->webp_conversion_method &&
			$this->id > 0
		) {
			return array( 'tools' );
		}

		return $conversion_methods_queue;
	}

	/**
	 * Create WebP Image using IMagick
	 *
	 * @param string $webp_file Created WebP image path
	 *
	 * @return bool
	 *
	 * @throws ImagickException Throws ImagickException on error
	 */
	public function imagick_create_webp( $webp_file ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		$system_log->add_log( 'Attempting to create WebP image using Imagick' );

		$image = new Imagick( $this->file );
		if ( false === $image ) {
			$system_log->add_log( 'No WebP image was created using Imagick' );

			return false;
		}

		$filesystem = new Qode_Optimizer_Filesystem();

		$image->setImageFormat( 'WEBP' );

		$image->setOption( 'webp:use-sharp-yuv', 'true' );
		$image->setImageCompressionQuality( $this->webp_quality );

		$image_blob = $image->getImageBlob();
		// alternative for PHP native file_put_contents function.
		$filesystem->put_contents( $webp_file, $image_blob );

		$system_log->add_log( 'WebP image was successfully created using Imagick' );

		return true;
	}

	/**
	 * Create WebP Image using GD
	 *
	 * @param string $webp_file Created WebP image path
	 *
	 * @return bool
	 */
	public function gd_create_webp( $webp_file ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		$system_log->add_log( 'Attempting to create WebP image using GD' );

		$image = imagecreatefromgif( $this->file );
		if ( false === $image ) {
			$system_log->add_log( 'No WebP image was created using GD' );

			return false;
		}
		if ( ! imageistruecolor( $image ) ) {
			// Converting to true color.
			imagepalettetotruecolor( $image );
		}
		if ( $this->alpha_exists() ) {
			// Saving alpha and disabling alpha blending.
			imagealphablending( $image, false );
			imagesavealpha( $image, true );
		}

		imagewebp( $image, $webp_file, $this->webp_quality );
		imagedestroy( $image );

		$system_log->add_log( 'WebP image was successfully created using GD' );

		return true;
	}

	/**
	 * Create WebP Image using tools
	 *
	 * @param string $webp_file Created WebP image path
	 *
	 * @return bool
	 */
	public function tool_create_webp( $webp_file ) {
		return $this->gif2webp_create_webp( $webp_file );
	}

	/**
	 * Create WebP Image using GIF2WEBP
	 *
	 * @param string $webp_file Created WebP image path
	 *
	 * @return bool
	 */
	public function gif2webp_create_webp( $webp_file ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( Qode_Optimizer_Support::is_tool_working( 'gif2webp' ) ) {
			$system_log->add_log( 'Attempting to create WebP image using Gif2webp' );

			$tool_path = Qode_Optimizer_Support::get_tool_working_path( 'gif2webp' );

			$system_log->add_log( $tool_path . ' -lossy -q ' . $this->webp_quality . ' ' . escapeshellarg( $this->file ) . ' -o ' . escapeshellarg( $webp_file ) );

			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
			if ( false !== exec( $tool_path . ' -lossy -q ' . $this->webp_quality . ' ' . escapeshellarg( $this->file ) . ' -o ' . escapeshellarg( $webp_file ) ) ) {
				// Success.
				$system_log->add_log( 'WebP image was successfully created using Gif2webp' );

				return true;
			}
		}

		$system_log->add_log( 'No WebP image was created using Gif2webp' );

		return false;
	}
}
