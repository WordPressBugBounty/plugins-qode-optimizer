<?php
/**
 * Implementation of PNG image support procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

require_once QODE_OPTIMIZER_CLASSES_FOLDER_PATH . '/class-qode-optimizer-image.php';

class Qode_Optimizer_Png extends Qode_Optimizer_Image {

	/**
	 * Mime-type
	 */
	const MIME_TYPE = 'image/png';

	/**
	 * Conversion mime-type
	 */
	const CONVERSION_MIME_TYPE = 'image/jpeg';

	/**
	 * Lossless PNG to WebP conversion
	 *
	 * @var string $lossless_png_to_webp_conversion
	 */
	public $lossless_png_to_webp_conversion = 'no';

	/**
	 * Create an image from file
	 *
	 * @param array $params
	 */
	public function __construct( $params ) {
		parent::__construct( $params );

		if (
			array_key_exists( 'additional_compression', $params ) &&
			is_bool( $params['additional_compression'] ) &&
			$params['additional_compression']
		) {
			$this->additional_compression = true;
		}

		if ( array_key_exists( 'compression_quality', $params ) ) {
			$this->compression_quality = Qode_Optimizer_Utility::correct_integer( $params['compression_quality'] );
		} elseif ( $this->additional_compression ) {
			$this->compression_quality = ! is_null( Qode_Optimizer_Options::get_option( 'additional_png_compression_quality' ) ) ?
				Qode_Optimizer_Options::get_option( 'additional_png_compression_quality' ) : 75;
		} else {
			$this->compression_quality = ! is_null( Qode_Optimizer_Options::get_option( 'png_compression_quality' ) ) ?
				Qode_Optimizer_Options::get_option( 'png_compression_quality' ) : 75;
		}

		$this->lossless_png_to_webp_conversion = ! is_null( Qode_Optimizer_Options::get_option( 'lossless_png_to_webp_conversion' ) ) ?
			Qode_Optimizer_Options::get_option( 'lossless_png_to_webp_conversion' ) : 'no';
	}

	/**
	 * Static GD Image create from PNG
	 *
	 * @param string $file File path
	 *
	 * @return resource|false an image resource identifier on success, false on errors
	 */
	public static function gd_image_create_static( $file ) {
		$image = Qode_Optimizer_Support::get_system_param( 'gd_support_exists' ) ? imagecreatefrompng( $file ) : '';

		if ( ! empty( $image ) ) {
			return $image;
		}

		return false;
	}

	/**
	 * GD Image create from PNG
	 *
	 * @return resource|false an image resource identifier on success, false on errors
	 */
	public function gd_image_create() {
		$image = Qode_Optimizer_Support::get_system_param( 'gd_support_exists' ) ? imagecreatefrompng( $this->file ) : '';

		if ( ! empty( $image ) ) {
			return $image;
		}

		return false;
	}

	/**
	 * Static GD Image save as PNG
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
			imagepng( $gd_image, $file );
			imagedestroy( $gd_image );

			return true;
		}

		return false;
	}

	/**
	 * GD Image save as PNG
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
			imagepng( $gd_image, $this->file );
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
	 * GD PNG transparency check
	 *
	 * @return bool
	 */
	public function gd_alpha_exists() {
		if ( Qode_Optimizer_Support::get_system_param( 'gd_support_exists' ) ) {
			$image = imagecreatefrompng( $this->file );
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
	 * Animated PNG check
	 *
	 * @return bool
	 */
	public function is_animated_png() {
		// Trying to open the file in read-only buffered mode. WP_Filesystem methods don't have the alternative for PHP native fopen MODE param, which is essential in our use case.
		$fh = fopen( $this->file, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $fh ) {
			return false;
		}

		$is_animated_png = false;

		$previousdata = '';
		// Trying to find acTL or IDAT chunk in the file.
		while ( ! feof( $fh ) ) {
			// Read 1kb at a time. WP_Filesystem methods don't have the alternative for PHP native fread LENGTH param, which is essential in our use case.
			$data = fread( $fh, 1024 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			if ( false !== strpos( $data, 'acTL' ) ) {
				$is_animated_png = true;
				break;
			} elseif ( false !== strpos( $previousdata . $data, 'acTL' ) ) {
				$is_animated_png = true;
				break;
			} elseif ( false !== strpos( $data, 'IDAT' ) ) {
				break;
			} elseif ( false !== strpos( $previousdata . $data, 'IDAT' ) ) {
				break;
			}
			$previousdata = $data;
		}

		// WP_Filesystem methods don't have the alternative for PHP native fclose function.
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return $is_animated_png;
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
		$compression_method = ! is_null( Qode_Optimizer_Options::get_option( 'png_compression_method' ) ) ?
			Qode_Optimizer_Options::get_option( 'png_compression_method' ) : '';

		if ( 'none' === $compression_method ) {
			return array();
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
		$compression_method = ! is_null( Qode_Optimizer_Options::get_option( 'additional_png_compression_method' ) ) ?
			Qode_Optimizer_Options::get_option( 'additional_png_compression_method' ) : '';

		if ( 'none' === $compression_method ) {
			return array();
		} elseif ( 'lossy-native' === $compression_method ) {
			return array( 'imagick', 'gd' );
		} else {
			return array( $compression_method );
		}
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

		$image->setImageFormat( 'PNG' );
		/**
		 * SetImageCompressionQuality not working on PNG images
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

		$image = imagecreatefrompng( $this->file );
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

		$compression_quality = (int) ( ( 100 - $this->compression_quality ) / 10 );

		imagepng( $image, $compressed_file, $compression_quality );
		imagedestroy( $image );

		$system_log->add_log( 'Image was successfully compressed using GD' );

		return true;
	}

	/**
	 * Lossless image compression using tools - Huge saves in contrast to Imagick and GD methods
	 *
	 * @param string $compressed_file Compressed image path
	 *
	 * @return bool
	 */
	public function lossless_clt_compress( $compressed_file ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		$filesystem = new Qode_Optimizer_Filesystem();

		if ( $filesystem->copy_file( $this->file, $compressed_file ) ) {
			$success = false;

			if ( $this->optipng_compress( $compressed_file ) ) {
				$success = true;
			}

			return $success;
		}

		return false;
	}

	/**
	 * Lossy image compression using tools - Huge saves in contrast to Imagick and GD methods
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

			if ( $this->pngquant_compress( $compressed_file ) ) {
				$success = true;
			}

			return $success;
		}

		return false;
	}

	/**
	 * Compress Image using PNGQUANT tool - lossy compression
	 *
	 * @param string $compressed_file Compressed image path
	 *
	 * @return bool
	 */
	public function pngquant_compress( $compressed_file ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( Qode_Optimizer_Support::is_tool_working( 'pngquant' ) ) {
			$system_log->add_log( 'Attempting to compress the image using Pngquant' );

			$filesystem = new Qode_Optimizer_Filesystem();

			if ( $filesystem->is_file( $compressed_file ) ) {

				$initial_filesize = $filesystem->filesize( $compressed_file );

				// Quality.
				$quality_option = '--quality=0-' . $this->compression_quality;

				$tool_path = Qode_Optimizer_Support::get_tool_working_path( 'pngquant' );

				$system_log->add_log( $tool_path . ' ' . $quality_option . ' -- ' . escapeshellarg( $compressed_file ) );
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
				if ( false !== exec( $tool_path . ' ' . $quality_option . ' -- ' . escapeshellarg( $compressed_file ) ) ) {

					// Temporary filename.
					$tmpfile = preg_replace( '/\.\w+$/', '-fs8.png', $compressed_file );

					if ( $filesystem->is_file( $tmpfile ) ) {
						$new_filesize = $filesystem->filesize( $tmpfile );

						if (
							$new_filesize &&
							$new_filesize < $initial_filesize
						) {

							if ( static::MIME_TYPE === $filesystem->get_mime_type( $tmpfile ) ) {
								$filesystem->rename_file( $tmpfile, $compressed_file );

								// Success.
								$system_log->add_log( 'Image was successfully compressed using Pngquant' );

								return true;
							}
						}

						$filesystem->delete_file( $tmpfile );
					}
				}
			}
		}

		$system_log->add_log( 'No compression was made using Pngquant' );

		return false;
	}

	/**
	 * Compress Image using OPTIPNG tool - lossless compression
	 *
	 * @param string $compressed_file Compressed image path
	 *
	 * @return bool
	 */
	public function optipng_compress( $compressed_file ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( Qode_Optimizer_Support::is_tool_working( 'optipng' ) ) {
			$system_log->add_log( 'Attempting to compress the image using Optipng' );

			$filesystem = new Qode_Optimizer_Filesystem();

			if ( $filesystem->is_file( $compressed_file ) ) {

				$initial_filesize = $filesystem->filesize( $compressed_file );

				// Metadata stripping.
				$strip_option = '';
				if ( 'yes' === $this->image_metadata_remove ) {
					$strip_option = '-strip all ';
				}

				$tmpfile = $compressed_file . '.tmp.png';
				$filesystem->copy_file( $compressed_file, $tmpfile );

				if ( $filesystem->is_file( $tmpfile ) ) {

					$tool_path = Qode_Optimizer_Support::get_tool_working_path( 'optipng' );
					// -o2, optimization level, level 2 is safe default.

					$system_log->add_log( $tool_path . ' -o2 -quiet ' . $strip_option . ' ' . escapeshellarg( $tmpfile ) );
					// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
					if ( false !== exec( $tool_path . ' -o2 -quiet ' . $strip_option . ' ' . escapeshellarg( $tmpfile ) ) ) {

						$new_filesize = $filesystem->filesize( $tmpfile );

						if (
							$new_filesize &&
							$new_filesize < $initial_filesize
						) {

							if ( static::MIME_TYPE === $filesystem->get_mime_type( $tmpfile ) ) {
								$filesystem->rename_file( $tmpfile, $compressed_file );

								// Success.
								$system_log->add_log( 'Image was successfully compressed using Optipng' );

								return true;
							}
						}
					}

					$filesystem->delete_file( $tmpfile );
				}
			}
		}

		$system_log->add_log( 'No compression was made using Optipng' );

		return false;
	}

	/**
	 * Compress Image using PNGOUT tool - lossless compression
	 *
	 * @param string $compressed_file Compressed image path
	 *
	 * @return bool
	 */
	public function pngout_compress( $compressed_file ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( Qode_Optimizer_Support::is_tool_working( 'pngout' ) ) {
			$system_log->add_log( 'Attempting to compress the image using Pngout' );

			$filesystem = new Qode_Optimizer_Filesystem();

			if ( $filesystem->is_file( $compressed_file ) ) {

				$initial_filesize = $filesystem->filesize( $compressed_file );

				$tmpfile = $compressed_file . '.tmp.png';
				$filesystem->copy_file( $compressed_file, $tmpfile );

				if ( $filesystem->is_file( $tmpfile ) ) {

					$tool_path = Qode_Optimizer_Support::get_tool_working_path( 'pngout' );

					$system_log->add_log( $tool_path . ' -k1 -q ' . escapeshellarg( $tmpfile ) );
					// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
					if ( false !== exec( $tool_path . ' -k1 -q ' . escapeshellarg( $tmpfile ) ) ) {

						$new_filesize = $filesystem->filesize( $tmpfile );

						if (
							$new_filesize &&
							$new_filesize < $initial_filesize
						) {

							if ( static::MIME_TYPE === $filesystem->get_mime_type( $tmpfile ) ) {
								$filesystem->rename_file( $tmpfile, $compressed_file );

								// Success.
								$system_log->add_log( 'Image was successfully compressed using Pngout' );

								return true;
							}
						}
					}

					$filesystem->delete_file( $tmpfile );
				}
			}
		}

		$system_log->add_log( 'No compression was made using Pngout' );

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
		$conversion_params = array();

		// If the user set a fill background for transparency.
		$background_fill_color = Qode_Optimizer_Options::get_option( 'jpg_background_color_fill' );
		if (
			false !== $background_fill_color &&
			! is_null( $background_fill_color )
		) {
			$conversion_params['red']        = hexdec( '0x' . strtoupper( substr( $background_fill_color, 0, 2 ) ) );
			$conversion_params['green']      = hexdec( '0x' . strtoupper( substr( $background_fill_color, 2, 2 ) ) );
			$conversion_params['blue']       = hexdec( '0x' . strtoupper( substr( $background_fill_color, 4, 2 ) ) );
			$conversion_params['fill_color'] = $background_fill_color;
		} else {
			$conversion_params['red']        = '';
			$conversion_params['green']      = '';
			$conversion_params['blue']       = '';
			$conversion_params['fill_color'] = '000000';
		}

		// If the user manually set the JPG quality.
		$conversion_params['quality'] = ! is_null( Qode_Optimizer_Options::get_option( 'jpg_compression_quality' ) ) ?
			Qode_Optimizer_Options::get_option( 'jpg_compression_quality' ) : 75;

		return $conversion_params;
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
				if ( $this->alpha_exists() ) {
					$gmagick_overlay = new Gmagick( $this->file );
					$gmagick         = new Gmagick();
					$gmagick->newimage( $gmagick_overlay->getimagewidth(), $gmagick_overlay->getimageheight(), '#' . $conversion_params['fill_color'] );
					$gmagick->compositeimage( $gmagick_overlay, 1, 0, 0 );
				} else {
					$gmagick = new Gmagick( $this->file );
				}
				$gmagick->setimageformat( 'JPG' );
				$gmagick->setcompressionquality( $conversion_params['quality'] );
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
				if ( $this->alpha_exists() ) {
					$imagick->setImageBackgroundColor( new ImagickPixel( '#' . $conversion_params['fill_color'] ) );
					$imagick = $imagick->flattenImages();
					$imagick->setImageAlphaChannel( 11 );
				}
				$imagick->setImageFormat( 'JPG' );
				$imagick->setImageCompressionQuality( $conversion_params['quality'] );
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
			$input                  = imagecreatefrompng( $this->file );
			list( $width, $height ) = wp_getimagesize( $this->file );

			$output = imagecreatetruecolor( $width, $height );
			if ( '' === $conversion_params['red'] ) {
				$conversion_params['red']   = 255;
				$conversion_params['green'] = 255;
				$conversion_params['blue']  = 255;
			}

			$rgb = imagecolorallocate( $output, $conversion_params['red'], $conversion_params['green'], $conversion_params['blue'] );
			imagefilledrectangle( $output, 0, 0, $width, $height, $rgb );
			imagecopy( $output, $input, 0, 0, 0, 0, $width, $height );
			imagejpeg( $output, $new_file, $conversion_params['quality'] );

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
	 * Creates WebP images alongside PNG files.
	 *
	 * @param array $conversion_methods_queue List of methods for trying to create WebP image with, if one fails system tries another one from the list
	 *
	 * @return Qode_Optimizer_Output
	 *
	 * @throws ImagickException Throws ImagickException on error.
	 */
	public function create_webp( $conversion_methods_queue = array( 'imagick', 'gd' ) ) {
		// Animated PNG, cannot be converted to WebP.
		if ( $this->is_animated_png() ) {
			$system_log = Qode_Optimizer_Log::get_instance();

			$output = new Qode_Optimizer_Output();
			$output->set_param( 'file', false );
			$output->add_message( 'Animated PNG, cannot be converted to WebP' );

			$system_log->add_log( 'Animated PNG, cannot be converted to WebP' );

			return $output;
		}

		return parent::create_webp( $conversion_methods_queue );
	}

	/**
	 * Create WebP Image using IMagick
	 *
	 * @param string $webp_file Created WebP image path
	 *
	 * @return bool
	 *
	 * @throws ImagickException Throws ImagickException on error.
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

		if ( 'yes' !== $this->lossless_png_to_webp_conversion ) {
			$image->setOption( 'webp:use-sharp-yuv', 'true' );
			$image->setImageCompressionQuality( $this->webp_quality );
		} else {
			$image->setOption( 'webp:lossless', true );
			$image->setOption( 'webp:alpha-quality', 100 );
		}

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

		$image = imagecreatefrompng( $this->file );
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
	 * Options for WebP image creation using tools
	 *
	 * @return string
	 */
	protected function tool_create_webp_additional_options() {
		if ( 'yes' !== $this->lossless_png_to_webp_conversion ) {
			return '-q ' . $this->webp_quality . ' -sharp_yuv';
		} else {
			return '-lossless';
		}
	}
}
