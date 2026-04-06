<?php
/**
 * Implementation of JPEG image support procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

require_once QODE_OPTIMIZER_CLASSES_FOLDER_PATH . '/class-qode-optimizer-image.php';

class Qode_Optimizer_Jpeg extends Qode_Optimizer_Image {

	/**
	 * Mime-type
	 */
	const MIME_TYPE = 'image/jpeg';

	/**
	 * Conversion mime-type
	 */
	const CONVERSION_MIME_TYPE = 'image/png';

	/**
	 * Create an image from file
	 *
	 * @param array $params
	 *
	 * @throws ImagickException Throws ImagickException on error.
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
			if ( qode_optimizer_is_installed( 'optimizer-premium' ) ) {
				$this->compression_quality = Qode_OptimizerPremium_Options::get_additional_jpg_compression_quality();
			}
		} else {
			$this->compression_quality = ! is_null( Qode_Optimizer_Options::get_option( 'jpg_compression_quality' ) ) ?
				Qode_Optimizer_Options::get_option( 'jpg_compression_quality' ) : 75;
		}
	}

	/**
	 * Static GD Image create from JPG
	 *
	 * @param string $file File path
	 *
	 * @return resource|false an image resource identifier on success, false on errors
	 */
	public static function gd_image_create_static( $file ) {
		$image = Qode_Optimizer_Support::get_system_param( 'gd_support_exists' ) ? imagecreatefromjpeg( $file ) : '';

		if ( ! empty( $image ) ) {
			return $image;
		}

		return false;
	}

	/**
	 * GD Image create from JPG
	 *
	 * @return resource|false an image resource identifier on success, false on errors
	 */
	public function gd_image_create() {
		$image = Qode_Optimizer_Support::get_system_param( 'gd_support_exists' ) ? imagecreatefromjpeg( $this->file ) : '';

		if ( ! empty( $image ) ) {
			return $image;
		}

		return false;
	}

	/**
	 * Static GD Image save as JPG
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
			imagejpeg( $gd_image, $file );
            unset( $gd_image );

			return true;
		}

		return false;
	}

	/**
	 * GD Image save as JPG
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
			imagejpeg( $gd_image, $this->file );
            unset( $gd_image );

			return true;
		}

		return false;
	}

	/**********************************************
	 * RESIZING (OPTIONAL) AND COMPRESSING (MANDATORY)
	 *
	 * 2nd step in optimizing images
	 * 4th step also, after optional image conversion
	 *********************************************/

	/**
	 * Gets the orientation/rotation of a JPG image using the EXIF data.
	 *
	 * @return int|bool
	 */
	public function get_orientation() {
		$filesystem = new Qode_Optimizer_Filesystem( true );
		$exif_data  = function_exists( 'exif_read_data' ) && $filesystem->is_readable( $this->file ) ? exif_read_data( $this->file ) : '';

		if ( ! empty( $exif_data ) ) {
			return array_key_exists( 'Orientation', $exif_data ) ? $exif_data['Orientation'] : false;
		}

		return false;
	}

	/**
	 * Get exif metadata with Pel library, and save it to the new image
	 *
	 * @param $new_file
	 * @param $image_rotation
	 *
	 * @return bool
	 *
	 * @throws lsolesen\pel\PelException
	 */
	public function save_exif( $new_file, $image_rotation ) {
		if ( ! Qode_Optimizer_Support::get_system_param( 'image_metadata_remove' ) ) {
			// copying exif metadata to new image using Pel library.
			try {
				$original_jpeg = new lsolesen\pel\PelJpeg( $this->file );
				$original_exif = $original_jpeg->getExif();
				$new_jpeg      = new lsolesen\pel\PelJpeg( $new_file );
			} catch ( Exception $pelerror ) {
				$original_exif = null;
			}

			if ( ! is_null( $original_exif ) ) {
				if ( $image_rotation ) {
					$tiff        = $original_exif->getTiff();
					$ifd0        = $tiff->getIfd();
					$orientation = $ifd0->getEntry( lsolesen\pel\PelTag::ORIENTATION );
					if ( ! is_null( $orientation ) ) {
						$orientation->setValue( 1 );
					}
				}
				if (
					isset( $new_jpeg ) &&
					$new_jpeg instanceof lsolesen\pel\PelJpeg
				) {
					$new_jpeg->setExif( $original_exif );
				}
			}
			if (
				isset( $new_jpeg ) &&
				$new_jpeg instanceof lsolesen\pel\PelJpeg
			) {
				$new_jpeg->saveFile( $new_file );
			}

			return true;
		}

		return false;
	}

	/**
	 * Get compression method from options
	 *
	 * @return array
	 */
	public function get_compression_method_from_options() {
		$compression_method = ! is_null( Qode_Optimizer_Options::get_option( 'jpg_compression_method' ) ) ?
			Qode_Optimizer_Options::get_option( 'jpg_compression_method' ) : '';

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
		$compression_method = '';
		if ( qode_optimizer_is_installed( 'optimizer-premium' ) ) {
			$compression_method = Qode_OptimizerPremium_Options::get_additional_jpg_compression_method();
		}

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

		$filesystem = new Qode_Optimizer_Filesystem( true );

		$image->setImageFormat( 'JPG' );

		$profiles = array();

		$image_metadata_remove = $this->image_metadata_remove;
		if ( 'yes' === $image_metadata_remove ) {
			// Getting possible color profiles.
			$profiles = $image->getImageProfiles( 'icc', true );
		}

		$color = $image->getImageColorspace();

		if ( Imagick::COLORSPACE_CMYK === $color ) {
			// CMYK image.
			if ( is_file( QODE_OPTIMIZER_ABS_PATH . '/vendor/icc/sRGB2014.icc' ) ) {
				// add icc profile.
				// alternative for PHP native file_get_contents function.
				$icc_profile = $filesystem->get_contents( QODE_OPTIMIZER_ABS_PATH . '/vendor/icc/sRGB2014.icc' );
				$image->profileImage( 'icc', $icc_profile );
			}
			$image->transformImageColorspace( Imagick::COLORSPACE_SRGB );

			// Remove icc profile.
			$image->setImageProfile( '*', null );
			$profiles = array();
		}
		$image->setImageCompressionQuality( $this->compression_quality );

		if ( 'yes' === $image_metadata_remove ) {
			$image->stripImage();

			if ( ! empty( $profiles ) && array_key_exists( 'icc', $profiles ) ) {
				// add WebP color profile.
				$image->profileImage( 'icc', $profiles['icc'] );
			}
		}

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

		$image = imagecreatefromjpeg( $this->file );
		if ( false === $image ) {
			$system_log->add_log( 'No compression was made using GD' );

			return false;
		}

		imagejpeg( $image, $compressed_file, $this->compression_quality );
        unset( $image );

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
		$system_log = Qode_Optimizer_Log::get_instance();

		$filesystem = new Qode_Optimizer_Filesystem( true );

		if ( $filesystem->copy_file( $this->file, $compressed_file ) ) {
			$success = false;

			if ( $this->jpegtran_compress( $compressed_file ) ) {
				$success = true;
			}

			return $success;
		}

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

		$filesystem = new Qode_Optimizer_Filesystem( true );

		if ( $filesystem->copy_file( $this->file, $compressed_file ) ) {
			$success = false;

			if ( $this->jpegoptim_compress( $compressed_file ) ) {
				$success = true;
			}

			return $success;
		}

		return false;
	}

	/**
	 * Compress Image using JPEGTRAN tool - lossless compression
	 *
	 * @param string $compressed_file Compressed image path
	 *
	 * @return bool
	 */
	public function jpegtran_compress( $compressed_file ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( Qode_Optimizer_Support::is_tool_working( 'jpegtran' ) ) {
			// Progressive optimization.
			$system_log->add_log( 'Attempting to compress the image using Jpegtran' );

			$filesystem = new Qode_Optimizer_Filesystem( true );

			if ( $filesystem->is_file( $compressed_file ) ) {

				$initial_filesize = $filesystem->filesize( $compressed_file );

				// Metadata stripping.
				$copy_option = 'all';
				if ( 'yes' === $this->image_metadata_remove ) {
					$copy_option = 'none';
				}

				$progressive_option = '';
				if ( $initial_filesize > 10240 ) {
					$progressive_option = '-progressive';
				}

				// Temporary filename.
				$tmpfile = $compressed_file . '.tmp';

				$tool_path = Qode_Optimizer_Support::get_tool_working_path( 'jpegtran' );

				$system_log->add_log( $tool_path . ' -copy ' . $copy_option . ' -optimize ' . $progressive_option . ' -outfile ' . escapeshellarg( $tmpfile ) . ' ' . escapeshellarg( $compressed_file ) );
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
				if ( false !== exec( $tool_path . ' -copy ' . $copy_option . ' -optimize ' . $progressive_option . ' -outfile ' . escapeshellarg( $tmpfile ) . ' ' . escapeshellarg( $compressed_file ) ) ) {

					if ( $filesystem->is_file( $tmpfile ) ) {

						$new_filesize = $filesystem->filesize( $tmpfile );

						if (
							$new_filesize &&
							$new_filesize < $initial_filesize
						) {

							if ( static::MIME_TYPE === $filesystem->get_mime_type( $tmpfile ) ) {
								$filesystem->rename_file( $tmpfile, $compressed_file );

								// Success.
								$system_log->add_log( 'Image was successfully compressed using Jpegtran' );

								return true;
							}
						}

						$filesystem->delete_file( $tmpfile );
					}
				}
			}
		}

		$system_log->add_log( 'No compression was made using Jpegtran' );

		return false;
	}

	/**
	 * Compress Image using JPEGOPTIM tool - lossy compression
	 *
	 * @param string $compressed_file Compressed image path
	 *
	 * @return bool
	 */
	public function jpegoptim_compress( $compressed_file ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( Qode_Optimizer_Support::is_tool_working( 'jpegoptim' ) ) {
			$system_log->add_log( 'Attempting to compress the image using Jpegoptim' );

			$filesystem = new Qode_Optimizer_Filesystem( true );

			if ( $filesystem->is_file( $compressed_file ) ) {

				$initial_filesize = $filesystem->filesize( $compressed_file );

				// Max quality.
				$max_quality_option = '--max=' . $this->compression_quality;

				// Metadata stripping.
				$strip_option = '';
				if ( 'yes' === $this->image_metadata_remove ) {
					$strip_option = '--strip-all';
				}

				$progressive_option = '';
				if ( $initial_filesize > 10240 ) {
					$progressive_option = '--all-progressive';
				}

				$tmpfile = $compressed_file . '.tmp';
				$filesystem->copy_file( $compressed_file, $tmpfile );

				if ( $filesystem->is_file( $tmpfile ) ) {

					$tool_path = Qode_Optimizer_Support::get_tool_working_path( 'jpegoptim' );

					$system_log->add_log( $tool_path . ' -q ' . $max_quality_option . ' ' . $strip_option . ' ' . $progressive_option . ' ' . escapeshellarg( $tmpfile ) );
					// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
					if ( false !== exec( $tool_path . ' -q ' . $max_quality_option . ' ' . $strip_option . ' ' . $progressive_option . ' ' . escapeshellarg( $tmpfile ) ) ) {

						$new_filesize = $filesystem->filesize( $tmpfile );

						if (
							$new_filesize &&
							$new_filesize < $initial_filesize
						) {

							if ( static::MIME_TYPE === $filesystem->get_mime_type( $tmpfile ) ) {
								$filesystem->rename_file( $tmpfile, $compressed_file );

								// Success.
								$system_log->add_log( 'Image was successfully compressed using Jpegoptim' );

								return true;
							}
						}
					}

					$filesystem->delete_file( $tmpfile );
				}
			}
		}

		$system_log->add_log( 'No compression was made using Jpegoptim' );

		return false;
	}

	/**********************************************
	 * WEBP CREATION (OPTIONAL)
	 *
	 * 5th step in optimizing images
	 *********************************************/

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

		$filesystem = new Qode_Optimizer_Filesystem( true );

		$image->setImageFormat( 'WEBP' );

		$profiles = array();

		$image_metadata_remove = $this->image_metadata_remove;
		if ( 'yes' === $image_metadata_remove ) {
			// Getting possible color profiles.
			$profiles = $image->getImageProfiles( 'icc', true );
		}

		$color = $image->getImageColorspace();

		if ( Imagick::COLORSPACE_CMYK === $color ) {
			// CMYK image.
			if ( is_file( QODE_OPTIMIZER_ABS_PATH . '/vendor/icc/sRGB2014.icc' ) ) {
				// add icc profile.
				// alternative for PHP native file_get_contents function.
				$icc_profile = $filesystem->get_contents( QODE_OPTIMIZER_ABS_PATH . '/vendor/icc/sRGB2014.icc' );
				$image->profileImage( 'icc', $icc_profile );
			}
			$image->transformImageColorspace( Imagick::COLORSPACE_SRGB );

			// Remove icc profile.
			$image->setImageProfile( '*', null );
			$profiles = array();
		}
		$image->setOption( 'webp:use-sharp-yuv', 'true' );
		$image->setImageCompressionQuality( $this->webp_quality );

		if ( 'yes' === $image_metadata_remove ) {
			$image->stripImage();

			if ( ! empty( $profiles ) && array_key_exists( 'icc', $profiles ) ) {
				// add WebP color profile.
				$image->profileImage( 'icc', $profiles['icc'] );
			}
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

		$image = imagecreatefromjpeg( $this->file );
		if ( false === $image ) {
			$system_log->add_log( 'No WebP image was created using GD' );

			return false;
		}

		imagewebp( $image, $webp_file, $this->webp_quality );
        unset( $image );

		$system_log->add_log( 'WebP image was successfully created using GD' );

		return true;
	}
}
