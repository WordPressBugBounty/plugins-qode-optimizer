<?php
/**
 * Implementation of image support procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

abstract class Qode_Optimizer_Image {

	/**
	 * Mime-types allowed
	 */
	const ALLOWED_MIME_TYPES = array(
		'general'                  => array(
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/svg+xml',
			'image/webp',
		),
		'optimizable'              => array(
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/svg+xml',
		),
		'convertible'              => array(
			'image/jpeg',
			'image/png',
			'image/gif',
		),
		'mime_type_extension_map'  => array(
			'image/jpeg'    => 'jpg',
			'image/png'     => 'png',
			'image/gif'     => 'gif',
			'image/svg+xml' => 'svg',
			'image/webp'    => 'webp',
		),
		'mime_type_conversion_map' => array(
			'image/jpeg' => 'image/png',
			'image/png'  => 'image/jpeg',
			'image/gif'  => 'image/png',
		),
		'mime_type_class_map'      => array(
			'image/jpeg'    => 'Qode_Optimizer_Jpeg',
			'image/png'     => 'Qode_Optimizer_Png',
			'image/gif'     => 'Qode_Optimizer_Gif',
			'image/svg+xml' => 'Qode_Optimizer_Svg',
		),
	);

	/**
	 * Mime-type
	 */
	const MIME_TYPE = false;

	/**
	 * Conversion mime-type
	 */
	const CONVERSION_MIME_TYPE = false;

	/**
	 * Default thumb image sizes
	 */
	const DEFAULT_THUMB_IMAGE_SIZES = array(
		'thumbnail',
		'medium',
		'medium_large',
		'large',
	);

	/**
	 * Database id
	 *
	 * @var int $id
	 */
	public $id = 0;

	/**
	 * Attachment id ( for images not in database, like thumbs, for making a relationship with uploaded image )
	 *
	 * @var $attachment_id
	 */
	public $attachment_id = 0;

	/**
	 * Get media size
	 *
	 * @var string $media_size
	 */
	public string $media_size = ''; // phpcs:ignore PHPCompatibility.Classes.NewTypedProperties.Found

	/**
	 * Check image has scaled size
	 *
	 * @var bool $has_scaled_size
	 */
	public $has_scaled_size = false;

	/**
	 * Image file
	 *
	 * @var string $file
	 */
	public $file = '';

	/**
	 * Image url
	 *
	 * @var string $url
	 */
	public $url = '';

	/**
	 * Image attached file
	 *
	 * @var string $attached_file
	 */
	public $attached_file = '';

	/**
	 * Image attached file meta
	 *
	 * @var string $attached_file_meta
	 */
	public $attached_file_meta = '';

	/**
	 * Image metadata
	 *
	 * @var array $metadata
	 */
	public $metadata = array();

	/**
	 * WebP's conversion method
	 *
	 * @var string $webp_conversion_method
	 */
	public $webp_conversion_method = '';

	/**
	 * WebP's conversion quality
	 *
	 * @var int $webp_quality
	 */
	public $webp_quality = 75;

	/**
	 * Additional compression
	 *
	 * @var bool $additional_compression
	 */
	public $additional_compression = false;

	/**
	 * Image conversion quality
	 *
	 * @var int $compression_quality
	 */
	public $compression_quality = 75;

	/**
	 * Image metadata remove
	 *
	 * @var string $image_metadata_remove
	 */
	public $image_metadata_remove = 'no';

	/**
	 * Delete original images
	 *
	 * @var string $delete_original_images
	 */
	public $delete_original_images = 'no';

	/**
	 * Image max width
	 *
	 * @var int $image_max_width
	 */
	public $image_max_width = 0;

	/**
	 * Image max height
	 *
	 * @var int $image_max_height
	 */
	public $image_max_height = 0;

	/**
	 * Enable automatic image optimization
	 *
	 * @var string $enable_automatic_image_optimization
	 */
	public $enable_automatic_image_optimization = 'no';

	/**
	 * Is file converted
	 *
	 * @var bool $is_converted
	 */
	public $is_converted = false;

	/**
	 * Converted image file
	 *
	 * @var string $converted_file
	 */
	public $converted_file = '';

	/**
	 * Create an image from media library or file
	 *
	 * @param array $params
	 */
	public function __construct( $params ) {
		if ( ! is_array( $params ) ) {
			$params = array();
		}

		if ( array_key_exists( 'id', $params ) ) {
			$this->id                 = intval( $params['id'] );
			$this->attachment_id      = $this->id;
			$this->file               = wp_get_original_image_path( $this->id );
			$this->url                = wp_get_attachment_url( $this->id );
			$this->attached_file      = get_attached_file( $this->id );
			$this->attached_file_meta = get_post_meta( $this->id, '_wp_attached_file', true );
			$this->metadata           = maybe_unserialize( wp_get_attachment_metadata( $this->id ) );
			$this->media_size         = 'original';
			$this->has_scaled_size    = $this->file !== $this->attached_file && false !== strpos( $this->attached_file, '-scaled.' );
		}

		$filesystem = new Qode_Optimizer_Filesystem();

		if (
			empty( $this->file ) &&
			array_key_exists( 'file', $params )
		) {
			if ( ! is_string( $params['file'] ) ) {
				$params['file'] = '';
			}

			if (
				$params['file'] &&
				$filesystem->is_file( $params['file'] )
			) {
				$this->file = $params['file'];
			}
		}

		if (
			0 === $this->attachment_id &&
			array_key_exists( 'attachment_id', $params )
		) {
			$this->attachment_id = intval( $params['attachment_id'] );
		}

		if (
			'' === $this->media_size &&
			array_key_exists( 'media_size', $params ) &&
			is_string( $params['media_size'] )
		) {
			$this->media_size = $params['media_size'];
		}

		if (
			empty( $this->url ) &&
			! empty( $this->file )
		) {
			if ( 'folders' === $this->media_size ) {
				$this->url = $filesystem->path_to_url( $this->file );
			} else {
				$uploads   = wp_get_upload_dir();
				$this->url = trailingslashit( $uploads['baseurl'] . '/' . _wp_get_attachment_relative_path( $this->file ) ) . wp_basename( $this->file );
			}
		}

		$this->webp_conversion_method              = ! is_null( Qode_Optimizer_Options::get_option( 'webp_conversion_method' ) ) ?
			Qode_Optimizer_Options::get_option( 'webp_conversion_method' ) : '';
		$this->webp_quality                        = ! is_null( Qode_Optimizer_Options::get_option( 'webp_quality' ) ) ?
			Qode_Optimizer_Options::get_option( 'webp_quality' ) : 75;
		$this->image_metadata_remove               = ! is_null( Qode_Optimizer_Options::get_option( 'image_metadata_remove' ) ) ?
			Qode_Optimizer_Options::get_option( 'image_metadata_remove' ) : 'no';
		$this->delete_original_images              = ! is_null( Qode_Optimizer_Options::get_option( 'delete_original_images' ) ) ?
			Qode_Optimizer_Options::get_option( 'delete_original_images' ) : 'no';
		$this->image_max_width                     = ! is_null( Qode_Optimizer_Options::get_option( 'image_max_width' ) ) ?
			Qode_Optimizer_Options::get_option( 'image_max_width' ) : 0;
		$this->image_max_height                    = ! is_null( Qode_Optimizer_Options::get_option( 'image_max_height' ) ) ?
			Qode_Optimizer_Options::get_option( 'image_max_height' ) : 0;
		$this->enable_automatic_image_optimization = ! is_null( Qode_Optimizer_Options::get_option( 'enable_automatic_image_optimization' ) ) ?
			Qode_Optimizer_Options::get_option( 'enable_automatic_image_optimization' ) : 'no';
	}

	/**
	 * Image mime-type
	 *
	 * @return string
	 */
	public function get_mime_type() {
		return static::MIME_TYPE;
	}

	/**
	 * Thumbs existence check
	 *
	 * @param string $procedure Procedure which thumbs info is getting fetched for
	 *
	 * @return bool
	 */
	public function has_thumbs( $procedure = 'optimization' ) {
		if (
			! is_string( $procedure ) ||
			! in_array( $procedure, array( 'optimization', 'creation' ), true )
		) {
			$procedure = 'optimization';
		}

		$filesystem = new Qode_Optimizer_Filesystem();

		foreach ( $this->get_all_image_thumb_path_info( $procedure ) as $thumb_path_info ) {
			if (
				array_key_exists( 'full_path', $thumb_path_info ) &&
				! empty( $thumb_path_info['full_path'] ) &&
				$filesystem->is_file( $thumb_path_info['full_path'] )
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * GD image create
	 *
	 * @return resource|false an image resource identifier on success, false on errors
	 */
	abstract public function gd_image_create();

	/**
	 * GD image save
	 *
	 * @param resource $gd_image GD image
	 *
	 * @return bool
	 */
	abstract public function gd_image_save( $gd_image );

	/**
	 * Plain PHP image transparency check
	 *
	 * @return bool
	 */
	public function plain_alpha_exists() {
		// Perhaps we can check if image is too large for memory limit.
		$filesystem    = new Qode_Optimizer_Filesystem();
		$file_contents = $filesystem->get_wpfilesystem()->get_contents( $this->file );
		$color_type    = ord( substr( $file_contents, 25, 1 ) );
		unset( $file_contents );

		if (
			4 === $color_type ||
			6 === $color_type
		) {
			return true;
		}

		return false;
	}

	/**
	 * Unique filename generation
	 *
	 * @param string $file File path
	 * @param string $extension New file extension
	 *
	 * @return string|bool
	 */
	public function create_unique_filename( $file, $extension ) {
		$filesystem = new Qode_Optimizer_Filesystem();

		if ( $filesystem->is_file( $file ) ) {
			$file_info          = pathinfo( $file );
			$allowed_extensions = static::ALLOWED_MIME_TYPES['mime_type_extension_map'];
			if (
				! empty( $file_info['dirname'] ) &&
				! empty( $file_info['filename'] ) &&
				in_array( $extension, $allowed_extensions, true )
			) {
				$unique_filepath = $file_info['dirname'] . DIRECTORY_SEPARATOR . $file_info['filename'] . '.' . $extension;
				if ( ! $filesystem->is_file( $unique_filepath ) ) {
					return $unique_filepath;
				}

				$unique_filename = wp_unique_filename( $file_info['dirname'], $file_info['filename'] . '.' . $extension );
				$unique_filepath = $file_info['dirname'] . DIRECTORY_SEPARATOR . $unique_filename;
				if ( ! $filesystem->is_file( $unique_filepath ) ) {
					return $unique_filepath;
				}
			}
		}

		return false;
	}

	/**********************************************
	 * WATERMARKING (OPTIONAL)
	 *
	 * 1st step in optimizing images
	 *********************************************/

	/**
	 * Adds supplied watermark to main image and it's thumbs
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function image_and_thumbs_add_watermark() {
		$system_log = Qode_Optimizer_Log::get_instance();

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );
		$output->set_param( 'success', false );

		$system_log->add_log( '* WATERMARKING OF MAIN IMAGE AND THUMBS', true );

		if ( 'local' === Qode_Optimizer_Options::get_option( 'backup_method' ) ) {
			$this->create_image_and_thumbs_backup();
		}

		// Adding Watermark - original image.
		$system_log->add_log( 'Start the process of watermarking the main image' );

		$main_file_output = $this->add_watermark();

		$system_log->add_log( 'Finish the process of watermarking the main image' );

		$all_files_params   = $output->get_param( 'files' );
		$all_files_params[] = $main_file_output;
		$output->set_param( 'files', $all_files_params );

		if ( $main_file_output->get_param( 'success' ) ) {
			$output->set_param( 'success', true );
		}

		// Adding Watermark - thumbs.
		if (
			$output->get_param( 'success' ) &&
			$this->has_thumbs()
		) {
			$thumbs_files_output = $this->thumbs_add_watermark();

			$all_files_params = $output->get_param( 'files' );
			$all_files_params = array_merge( $all_files_params, $thumbs_files_output->get_param( 'files' ) );
			$output->set_param( 'files', $all_files_params );

			$output->set_param( 'success', $thumbs_files_output->get_param( 'success' ) );
		}

		if ( $output->get_param( 'success' ) ) {
			$this->save_modifications( 'optimization', $output );

			// Update attachment's metadata.
			$metadata = $this->metadata_from_output( $this->file, $output );
			if (
				$metadata &&
				Qode_Optimizer_Utility::multiple_array_keys_exist(
					array(
						'wp_attached_file',
						'wp_attachment_metadata',
					),
					$metadata
				)
			) {
				static::update_attachment_metadata( $this->id, $metadata['wp_attached_file'], $metadata['wp_attachment_metadata'] );
			}
		}

		return $output;
	}

	/**
	 * Adds uploaded watermark to image thumbs
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function thumbs_add_watermark() {
		$system_log = Qode_Optimizer_Log::get_instance();

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );
		$output->set_param( 'success', false );

		$all_thumbs = new Qode_Optimizer_Images(
			array(
				'files'                  => $this->get_all_image_thumb_path_info(),
				'additional_compression' => $this->additional_compression,
			)
		);

		// Adding Watermark - thumbs.
		$system_log->add_log( 'Start the process of watermarking the thumb images' );

		$output = $all_thumbs->multiple_add_watermark();

		$system_log->add_log( 'Finish the process of watermarking the thumb images' );

		return $output;
	}

	/**
	 * Adds uploaded watermark to folders images
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function folders_image_add_watermark() {
		$system_log = Qode_Optimizer_Log::get_instance();

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );
		$output->set_param( 'success', false );

		$system_log->add_log( '* WATERMARKING OF THE FOLDERS IMAGE', true );

		if (
			! Qode_Optimizer_Options::get_option( 'backup_already_made' ) &&
			'local' === Qode_Optimizer_Options::get_option( 'backup_method' )
		) {
			$this->create_folders_image_and_thumbs_backup();
		}

		// Adding Watermark - original image.
		$system_log->add_log( 'Start the process of watermarking the folders image' );

		$folders_file_output = $this->add_watermark();

		$system_log->add_log( 'Finish the process of watermarking the folders image' );

		$all_files_params   = $output->get_param( 'files' );
		$all_files_params[] = $folders_file_output;
		$output->set_param( 'files', $all_files_params );

		if ( $folders_file_output->get_param( 'success' ) ) {
			$output->set_param( 'success', true );
		}

		if ( $output->get_param( 'success' ) ) {
			$this->save_modifications( 'optimization', $output );
		}

		return $output;
	}

	/**
	 * Adds uploaded watermark to image
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function add_watermark() {
		$system_log = Qode_Optimizer_Log::get_instance();

		$system_log->add_log( 'File to watermark: ' . wp_basename( $this->file ) );

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'file', $this->file );
		$output->set_param( 'file_basename', wp_basename( $this->file ) );
		$output->set_param( 'media_size', '' );
		$output->set_param( 'initial_size_raw', 0 );
		$output->set_param( 'initial_size', '' );
		$output->set_param( 'filesize_raw', 0 );
		$output->set_param( 'filesize', '' );
		$output->set_param( 'result', esc_html__( 'Unsuccessful', 'qode-optimizer' ) );
		$output->set_param( 'success', false );

		$filesystem = new Qode_Optimizer_Filesystem();

		if (
			! $filesystem->is_file( $this->file ) ||
			! $filesystem->is_writable( $this->file )
		) {
			$output->add_message( 'Some error occurred' );

			return $output;
		}

		$initial_size = $filesystem->filesize( $this->file );
		$output->set_param( 'media_size', $this->media_size );
		$output->set_param( 'initial_size_raw', $initial_size );
		$output->set_param( 'initial_size', $filesystem->readable_size_format( $initial_size ) );
		$output->set_param( 'filesize_raw', $initial_size );
		$output->set_param( 'filesize', $filesystem->readable_size_format( $initial_size ) );

		if ( $this->apply_clt_watermark() ) {
			$new_size = $filesystem->filesize( $this->file );

			$output->set_param( 'filesize_raw', $new_size );
			$output->set_param( 'filesize', $filesystem->readable_size_format( $new_size ) );
			$output->set_param( 'result', $filesystem->readable_filesize_savings( $initial_size, $new_size ) );
			$output->set_param( 'success', true );
		}

		return $output;
	}

	/**
	 * Apply CL tool watermark
	 *
	 * @return bool
	 */
	protected function apply_clt_watermark() {
		$system_log = Qode_Optimizer_Log::get_instance();

		$system_log->add_log( 'Attempting to watermark the image' );
		$system_log->add_log( 'Using watermarking method(s): Convert' );

		$watermark_image_path = Qode_Optimizer_Support::is_tool_working( 'convert' ) ? Qode_Optimizer_Options::get_option( 'watermark_image_path' ) : '';
		if ( $watermark_image_path ) {
			$watermark_position = Qode_Optimizer_Options::get_option( 'watermark_position' );

			$offset_x = 10;
			$offset_y = 10;

			switch ( $watermark_position ) {
				case 'top-left':
					$gravity = 'NorthWest';
					break;
				case 'top-right':
					$gravity = 'NorthEast';
					break;
				case 'bottom-left':
					$gravity = 'SouthWest';
					break;
				case 'bottom-right':
					$gravity = 'SouthEast';
					break;
				case '':
				case 'centered':
					$gravity  = 'Center';
					$offset_x = 0;
					$offset_y = 0;
					break;
				default:
					$gravity  = 'NorthWest';
					$offset_x = 0;
					$offset_y = 0;
					break;
			}

			$tool_path = Qode_Optimizer_Support::get_tool_working_path( 'convert' );

			$system_log->add_log( $tool_path . ' ' . escapeshellarg( $this->file ) . ' -coalesce -gravity ' . $gravity . ' -geometry +' . $offset_x . '+' . $offset_y . ' null: ' . escapeshellarg( $watermark_image_path ) . ' -layers composite -layers optimize ' . escapeshellarg( $this->file ) );

			/**
			 * $command = $tool_path . ' '
				. escapeshellarg( $this->file )
				. ' -coalesce -gravity ' . $gravity . ' -geometry +' . $offset_x . '+' . $offset_y . ' null: '
				. escapeshellarg( $watermark_image_path )
				. ' -layers composite -layers optimize '
				. escapeshellarg( $this->file );
			 */
			$command = $tool_path . ' ' . escapeshellarg( $this->file ) . ' -coalesce -gravity ' . $gravity . ' -geometry +' . $offset_x . '+' . $offset_y . ' null: ' . escapeshellarg( $watermark_image_path ) . ' -layers composite -layers optimize ' . escapeshellarg( $this->file );

			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
			if ( false !== exec( $command ) ) {
				$system_log->add_log( 'Image was successfully watermarked' );

				return true;
			}
		}

		$system_log->add_log( 'No watermark was applied to image' );

		return false;
	}

	/**********************************************
	 * RESIZING (OPTIONAL) AND COMPRESSING (MANDATORY)
	 *
	 * 2nd step in optimizing images
	 * 4th step also, after optional image conversion
	 *********************************************/

	/**
	 * Gets the image orientation/rotation
	 *
	 * @return int|bool
	 */
	public function get_orientation() {
		return false;
	}

	/**
	 * Get exif metadata with Pel library, and save it to the new image
	 *
	 * @param $new_file
	 * @param $image_rotation
	 *
	 * @return bool
	 */
	public function save_exif( $new_file, $image_rotation ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// only for jpeg images.
		return false;
	}

	/**
	 * Resizes Media Library uploads based on the maximum dimensions specified by the user
	 *
	 * @param bool $check_size
	 *
	 * @return int/bool New filesize in bytes on success, false on failure
	 * @global object $wpdb
	 **/
	public function resize( $check_size = true ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		$system_log->add_log( 'Attempting to resize image' );

		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'image' );
		}

		$max_width  = $this->image_max_width;
		$max_height = $this->image_max_height;

		if (
			0 === $max_width &&
			0 === $max_height
		) {
			$system_log->add_log( 'No resize was applied to image' );

			return false;
		}

		list( $current_width, $current_height ) = wp_getimagesize( $this->file );

		if (
			$current_width <= $max_width &&
			$current_height <= $max_height
		) {
			$system_log->add_log( 'No resize was applied to image' );

			return false;
		}

		$crop = false;

		if (
			$max_width && $current_width >= $max_width &&
			$max_height && $current_height >= $max_height &&
			apply_filters( 'qode_optimizer_crop_image', false )
		) {
			$crop       = true;
			$new_width  = $max_width;
			$new_height = $max_height;
		} else {
			list( $new_width, $new_height ) = wp_constrain_dimensions( $current_width, $current_height, $max_width, $max_height );
		}

		// 3 bytes per pixel +50%, just to be sure enough memory is used.
		$memory_required_coefficient = 4.5;
		$memory_required             = ( $current_width * $current_height + $new_width * $new_height ) * $memory_required_coefficient;
		Qode_Optimizer_Utility::handle_memory_requirements( $memory_required, 'image' );

		if ( ! function_exists( 'wp_get_image_editor' ) ) {
			// No image editor.
			$system_log->add_log( 'No resize was applied to image' );

			return false;
		}

		$editor = wp_get_image_editor( $this->file );
		if ( is_wp_error( $editor ) ) {
			// Image editor error.
			$system_log->add_log( 'No resize was applied to image' );

			return false;
		}

		// Check for image rotation. If 90 degrees rotation is detected, swapping image dimensions is in order.
		$orientation    = $this->get_orientation();
		$image_rotation = false;

		switch ( $orientation ) {
			case 3:
				$editor->rotate( 180 );
				$image_rotation = true;
				break;
			case 6:
				$editor->rotate( - 90 );
				$buffer         = $new_width;
				$new_width      = $new_height;
				$new_height     = $buffer;
				$image_rotation = true;
				break;
			case 8:
				$editor->rotate( 90 );
				$buffer         = $new_width;
				$new_width      = $new_height;
				$new_height     = $buffer;
				$image_rotation = true;
				break;
		}

		$resized_image = $editor->resize( $new_width, $new_height, $crop );
		if ( is_wp_error( $resized_image ) ) {
			// Resizing error.
			$system_log->add_log( 'No resize was applied to image' );

			return false;
		}

		$filesystem = new Qode_Optimizer_Filesystem();

		$new_file    = $editor->generate_filename( 'tmp' );
		$saving_file = $editor->save( $new_file );
		if ( is_wp_error( $saving_file ) ) {
			// Resized image error.
			$system_log->add_log( 'Saving resized image error' );
		}

		$initial_size = $filesystem->filesize( $this->file );
		$new_size     = $filesystem->filesize( $new_file );
		if (
			$new_size &&
			(
				! $check_size ||
				$new_size < $initial_size
			)
		) {
			// Get exif metadata with Pel library, and save it to the new image.
			$this->save_exif( $new_file, $image_rotation );

			if ( static::MIME_TYPE === $filesystem->get_mime_type( $new_file ) ) {
				$filesystem->rename_file( $new_file, $this->file );
			} else {
				// Invalid file type.
				wp_delete_file( $new_file );

				$system_log->add_log( 'No resize was applied to image' );

				return false;
			}

			// Success.
			$system_log->add_log( 'Image was successfully resized' );

			return $new_size;
		}

		if ( $filesystem->is_file( $new_file ) ) {
			// Resized image too large and should be deleted.
			wp_delete_file( $new_file );
		}

		$system_log->add_log( 'No resize was applied to image' );

		return false;
	}

	/**
	 * Selects Compression method queue
	 *
	 * @param array|bool $compression_methods_queue_override List of methods for trying to compress image with, if one fails system tries another one from the list
	 *
	 * @return array Selects final compression method queue to be used
	 */
	public function select_compression_method( $compression_methods_queue_override = array() ) {
		if (
			is_array( $compression_methods_queue_override ) &&
			! empty( $compression_methods_queue_override )
		) {
			$compression_methods_queue = $compression_methods_queue_override;
		} elseif ( false === $compression_methods_queue_override ) {
			$compression_methods_queue = array();
		} else {
			$compression_methods_queue = $this->get_compression_method_from_options();
		}

		return $compression_methods_queue;
	}

	/**
	 * Selects Additional Compression method queue
	 *
	 * @return array Selects final compression method queue to be used
	 */
	public function select_additional_compression_method() {
		return $this->get_additional_compression_method_from_options();
	}

	/**
	 * Compresses Media Library uploads based on the compression quality set
	 *
	 * @param array|bool $compression_methods_queue_override List of methods for trying to compress image with, if one fails system tries another one from the list
	 * @param bool $check_size
	 *
	 * @return int/bool New filesize in bytes on success, false on failure
	 *
	 * @throws ImagickException Throws ImagickException on error.
	 */
	public function compress( $compression_methods_queue_override = array(), $check_size = true ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		$system_log->add_log( 'Attempting to compress the image' );

		if ( $this->additional_compression ) {
			$compression_methods_queue = $this->select_additional_compression_method();
		} else {
			$compression_methods_queue = $this->select_compression_method( $compression_methods_queue_override );
		}

		if ( ! empty( $compression_methods_queue ) ) {
			$system_log->add_log( 'Using compression method(s): ' . implode( ', ', $compression_methods_queue ) );
		} else {
			$system_log->add_log( 'Using compression method(s): No compression' );
		}

		if ( ! function_exists( 'wp_get_image_editor' ) ) {
			// No image editor.
			$system_log->add_log( 'No compression was applied to image' );

			return false;
		}

		$editor = wp_get_image_editor( $this->file );
		if ( is_wp_error( $editor ) ) {
			// Image editor error.
			$system_log->add_log( 'No compression was applied to image' );

			return false;
		}

		$filesystem = new Qode_Optimizer_Filesystem();

		if (
			! $filesystem->is_file( $this->file ) ||
			! $filesystem->is_writable( $this->file )
		) {
			$system_log->add_log( 'No compression was applied to image' );

			return false;
		}

		list( $width, $height ) = wp_getimagesize( $this->file );
		if ( $width > 16383 || $height > 16383 ) {
			// Image dimensions are too great, cannot be compressed.
			$system_log->add_log( 'No compression was applied to image' );

			return false;
		}

		$new_file_created = false;
		$new_file         = $editor->generate_filename( 'tmp' );

		foreach ( $compression_methods_queue as $method ) {
			switch ( $method ) {
				case 'imagick':
					// Imagick compression.
					if (
						! $new_file_created &&
						Qode_Optimizer_Support::get_system_param( 'imagick_support_exists' )
					) {
						$new_file_created = $this->imagick_compress( $new_file );
					}
					break;
				case 'gd':
					// GD compression.
					if (
						! $new_file_created &&
						Qode_Optimizer_Support::get_system_param( 'gd_support_exists' )
					) {
						$new_file_created = $this->gd_compress( $new_file );
					}
					break;
				case 'lossless-clt':
					// Lossless clt compression.
					if ( ! $new_file_created ) {
						$new_file_created = $this->lossless_clt_compress( $new_file );
					}
					break;
				case 'lossy-clt':
					// Lossy clt compression.
					if ( ! $new_file_created ) {
						$new_file_created = $this->lossy_clt_compress( $new_file );
					}
					break;
				default:
					break;
			}
		}

		$initial_size = $filesystem->filesize( $this->file );
		$new_size     = $filesystem->filesize( $new_file );
		if (
			$new_size &&
			(
				! $check_size ||
				$new_size < $initial_size
			)
		) {
			// Use this action to perform any operations on the original file before it is overwritten with the new, smaller file.
			do_action( 'qode_optimizer_image_compressed', $this->file, $new_file );

			if ( static::MIME_TYPE === $filesystem->get_mime_type( $new_file ) ) {
				$filesystem->rename_file( $new_file, $this->file );
			} else {
				// Created invalid file type.
				wp_delete_file( $new_file );

				$system_log->add_log( 'No compression was applied to image' );

				return false;
			}

			// Success.
			$system_log->add_log( 'Image was successfully compressed' );

			return $new_size;
		}

		if ( $filesystem->is_file( $new_file ) ) {
			// Compressed image too large and should be deleted.
			wp_delete_file( $new_file );
		}

		$system_log->add_log( 'No compression was applied to image' );

		return false;
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
	abstract public function imagick_compress( $compressed_file );

	/**
	 * Compress Image using GD
	 *
	 * @param string $compressed_file Compressed image path
	 *
	 * @return bool
	 */
	abstract public function gd_compress( $compressed_file );

	/**
	 * Lossless image compression using tools
	 *
	 * @param string $compressed_file Compressed image path
	 *
	 * @return bool
	 */
	abstract public function lossless_clt_compress( $compressed_file );

	/**
	 * Lossy image compression using tools
	 *
	 * @param string $compressed_file Compressed image path
	 *
	 * @return bool
	 */
	abstract public function lossy_clt_compress( $compressed_file );

	/**
	 * Get compression method from options
	 *
	 * @return array
	 */
	abstract public function get_compression_method_from_options();

	/**
	 * Get additional compression method from options
	 *
	 * @return array
	 */
	abstract public function get_additional_compression_method_from_options();

	/**
	 * Optimize images for main image and its thumbs
	 *
	 * @param array|bool $compression_methods_queue_override List of methods for trying to compress image with, if one fails system tries another one from the list
	 * @param bool $do_resize
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function image_and_thumbs_optimize( $compression_methods_queue_override = array(), $do_resize = true ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( $this->additional_compression ) {
			$compression_methods_queue = $this->select_additional_compression_method();
		} else {
			$compression_methods_queue = $this->select_compression_method( $compression_methods_queue_override );
		}

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );
		$output->set_param( 'success', true );

		$system_log->add_log( '* OPTIMIZATION OF MAIN IMAGE AND THUMBS', true );

		if ( 'local' === Qode_Optimizer_Options::get_option( 'backup_method' ) ) {
			$this->create_image_and_thumbs_backup();
		}

		// Optimization - original image.
		try {
			$system_log->add_log( 'Start the process of optimizing the main image' );

			$main_file_output = $this->optimize( $compression_methods_queue, $do_resize );

			$system_log->add_log( 'Finish the process of optimizing the main image' );
		} catch ( Exception $exception ) {
			$system_log->add_log( 'Some error occurred during a process of optimizing the original image' );
		}

		$all_files_params   = $output->get_param( 'files' );
		$all_files_params[] = $main_file_output;
		$output->set_param( 'files', $all_files_params );

		// Optimization - thumbs.
		if ( $this->has_thumbs() ) {
			try {
				$thumbs_files_output = $this->thumbs_optimize( $compression_methods_queue, $do_resize );
			} catch ( Exception $exception ) {
				$system_log->add_log( 'Some error occurred during a process of optimizing the thumb images' );
			}

			$all_files_params = $output->get_param( 'files' );
			$all_files_params = array_merge( $all_files_params, $thumbs_files_output->get_param( 'files' ) );
			$output->set_param( 'files', $all_files_params );
		}

		$this->save_modifications( 'optimization', $output );

		// Update attachment's metadata.
		$metadata = $this->metadata_from_output( $this->file, $output );
		if (
			$metadata &&
			Qode_Optimizer_Utility::multiple_array_keys_exist(
				array(
					'wp_attached_file',
					'wp_attachment_metadata',
				),
				$metadata
			)
		) {
			static::update_attachment_metadata( $this->id, $metadata['wp_attached_file'], $metadata['wp_attachment_metadata'] );
		}

		return $output;
	}

	/**
	 * Optimize images for main image thumbs
	 *
	 * @param array|bool $compression_methods_queue_override List of methods for trying to compress image with, if one fails system tries another one from the list
	 * @param bool $do_resize
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function thumbs_optimize( $compression_methods_queue_override = array(), $do_resize = true ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( $this->additional_compression ) {
			$compression_methods_queue = $this->select_additional_compression_method();
		} else {
			$compression_methods_queue = $this->select_compression_method( $compression_methods_queue_override );
		}

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );

		$all_thumbs = new Qode_Optimizer_Images(
			array(
				'files'                  => $this->get_all_image_thumb_path_info(),
				'additional_compression' => $this->additional_compression,
			)
		);

		// Optimization - thumbs.
		try {
			$system_log->add_log( 'Start the process of optimizing the thumb images' );

			$output = $all_thumbs->multiple_optimize( $compression_methods_queue, $do_resize );

			$system_log->add_log( 'Finish the process of optimizing the thumb images' );
		} catch ( Exception $exception ) {
			$system_log->add_log( 'Some error occurred during a process of optimizing multiple images' );
		}

		return $output;
	}

	/**
	 * Optimize folders images
	 *
	 * @param array|bool $compression_methods_queue_override List of methods for trying to compress image with, if one fails system tries another one from the list
	 * @param bool $do_resize
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function folders_image_optimize( $compression_methods_queue_override = array(), $do_resize = true ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( $this->additional_compression ) {
			$compression_methods_queue = $this->select_additional_compression_method();
		} else {
			$compression_methods_queue = $this->select_compression_method( $compression_methods_queue_override );
		}

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );
		$output->set_param( 'success', true );

		$system_log->add_log( '* OPTIMIZATION OF THE FOLDERS IMAGE', true );

		if (
			! Qode_Optimizer_Options::get_option( 'backup_already_made' ) &&
			'local' === Qode_Optimizer_Options::get_option( 'backup_method' )
		) {
			$this->create_folders_image_and_thumbs_backup();
		}

		// Optimization - original image.
		try {
			$system_log->add_log( 'Start the process of optimizing the folders image' );

			$folders_file_output = $this->optimize( $compression_methods_queue, $do_resize );

			$system_log->add_log( 'Finish the process of optimizing the folders image' );
		} catch ( Exception $exception ) {
			$system_log->add_log( 'Some error occurred during a process of optimizing the folders image' );
		}

		$all_files_params   = $output->get_param( 'files' );
		$all_files_params[] = $folders_file_output;
		$output->set_param( 'files', $all_files_params );

		$this->save_folders_modifications( 'optimization', $output );

		return $output;
	}

	/**
	 * Optimize images
	 *
	 * @param array|bool $compression_methods_queue_override List of methods for trying to compress image with, if one fails system tries another one from the list
	 * @param bool $do_resize
	 *
	 * @return Qode_Optimizer_Output
	 *
	 * @throws ImagickException Throws ImagickException on error.
	 */
	public function optimize( $compression_methods_queue_override = array(), $do_resize = true ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( $this->additional_compression ) {
			$compression_methods_queue = $this->select_additional_compression_method();
		} else {
			$compression_methods_queue = $this->select_compression_method( $compression_methods_queue_override );
		}

		$system_log->add_log( 'File to optimize: ' . wp_basename( $this->file ) );

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'file', $this->file );
		$output->set_param( 'file_basename', wp_basename( $this->file ) );
		$output->set_param( 'media_size', '' );
		$output->set_param( 'initial_size_raw', 0 );
		$output->set_param( 'initial_size', '' );
		$output->set_param( 'filesize_raw', 0 );
		$output->set_param( 'filesize', '' );
		$output->set_param( 'success', false );

		$filesystem = new Qode_Optimizer_Filesystem();

		if (
			! $filesystem->is_file( $this->file ) ||
			! $filesystem->is_writable( $this->file )
		) {
			$output->add_message( 'Some error occurred' );

			return $output;
		}

		$initial_size = $filesystem->filesize( $this->file );
		$output->set_param( 'media_size', $this->media_size );
		$output->set_param( 'initial_size_raw', $initial_size );
		$output->set_param( 'initial_size', $filesystem->readable_size_format( $initial_size ) );
		$output->set_param( 'filesize_raw', $initial_size );
		$output->set_param( 'filesize', $filesystem->readable_size_format( $initial_size ) );

		$new_size = $do_resize && in_array( $this->media_size, array( 'original', 'scaled' ), true ) ? $this->resize() : '';
		if ( $new_size ) {
			$output->set_param( 'filesize_raw', $new_size );
			$output->set_param( 'filesize', $filesystem->readable_size_format( $new_size ) );
			$output->set_param( 'result', $filesystem->readable_filesize_savings( $initial_size, $new_size ) );
			$output->set_param( 'success', true );
		}

		$new_size = ! empty( $compression_methods_queue ) ? $this->compress( $compression_methods_queue ) : '';
		if ( $new_size ) {
			$output->set_param( 'filesize_raw', $new_size );
			$output->set_param( 'filesize', $filesystem->readable_size_format( $new_size ) );
			$output->set_param( 'result', $filesystem->readable_filesize_savings( $initial_size, $new_size ) );
			$output->set_param( 'success', true );
		}

		if ( ! $output->get_param( 'success' ) ) {
			$output->set_param( 'result', 'No Savings' );
		}

		return $output;
	}

	/**********************************************
	 * CONVERSION (OPTIONAL)
	 *
	 * 3rd step in optimizing images
	 *********************************************/

	/**
	 * Convert main and thumb images
	 *
	 * @param array $conversion_methods_queue List of methods for trying to convert image with, if one fails system tries another one from the list
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function image_and_thumbs_convert( $conversion_methods_queue = array( 'gmagick', 'imagick', 'gd' ) ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( ! is_array( $conversion_methods_queue ) ) {
			$conversion_methods_queue = array( 'gmagick', 'imagick', 'gd' );
		}

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );
		$output->set_param( 'success', false );

		$system_log->add_log( '* CONVERSION OF MAIN IMAGE AND THUMBS', true );

		if ( 'local' === Qode_Optimizer_Options::get_option( 'backup_method' ) ) {
			$this->create_image_and_thumbs_backup();
		}

		$this->image_and_thumbs_remove_webp();

		$filesystem = new Qode_Optimizer_Filesystem();

		// Conversion - original image.
		try {
			$system_log->add_log( 'Start the process of converting the main image' );

			$main_file_output = $this->convert( $conversion_methods_queue );

			$system_log->add_log( 'Finish the process of converting the main image' );

			$current_file = get_attached_file( $this->id );
			$current_size = $filesystem->filesize( $current_file );

			$output->set_param( 'original_file', wp_basename( $current_file ) );
			$output->set_param( 'initial_size_raw', $current_size );
			$output->set_param( 'initial_size', $filesystem->readable_size_format( $current_size ) );

			// Main image conversion failed.
			if ( ! $main_file_output->get_param( 'file' ) ) {

				if ( $main_file_output->get_param( 'result' ) ) {
					$output->set_param( 'result', $main_file_output->get_param( 'result' ) );
				}

				$system_log->add_log( 'The main image was not converted successfully. Conversion process is stopped' );

				return $output;
			}
		} catch ( Exception $exception ) {
			$system_log->add_log( 'Some error occurred during a process of converting the original image' );
		}

		$all_files_params   = $output->get_param( 'files' );
		$all_files_params[] = $main_file_output;
		$output->set_param( 'files', $all_files_params );

		if ( $main_file_output->get_param( 'file' ) ) {
			$output->set_param( 'success', true );
		}

		// Conversion - thumbs.
		if ( $this->has_thumbs() ) {
			try {
				$thumbs_files_output = $this->thumbs_convert( $conversion_methods_queue );

				// One or more thumb image conversions failed, rollback, delete any of successfully converted files so far.
				if ( ! $thumbs_files_output->get_param( 'success' ) ) {
					$output->set_param( 'success', false );

					// Delete converted main file.
					$main_file = $main_file_output->get_param( 'file' );
					if ( $main_file ) {
						$filesystem->delete_file( $main_file_output->get_param( 'file' ) );
					}

					// Delete converted thumb files.
					$thumb_output_files = $thumbs_files_output->get_param( 'files' );
					foreach ( $thumb_output_files as $file ) {

						if ( $file instanceof Qode_Optimizer_Output ) {
							$current_thumb_file = $file->get_param( 'file' );
							if ( $current_thumb_file ) {
								$filesystem->delete_file( $current_thumb_file );
							} elseif ( $file->get_param( 'result' ) ) {
								$output->set_param( 'result', $file->get_param( 'result' ) );
							}
						}
					}

					$system_log->add_log( 'One or more thumb images was not converted successfully, rollback, delete any of successfully converted files so far. Conversion process is stopped' );

					return $output;
				}
			} catch ( Exception $exception ) {
				$system_log->add_log( 'Some error occurred during a process of converting the thumb images' );
			}

			$all_files_params = $output->get_param( 'files' );
			$all_files_params = array_merge( $all_files_params, $thumbs_files_output->get_param( 'files' ) );
			$output->set_param( 'files', $all_files_params );

			if ( $output->get_param( 'success' ) ) {
				$output->set_param( 'success', $thumbs_files_output->get_param( 'success' ) );
			}
		}

		if ( $output->get_param( 'success' ) ) {
			$this->save_modifications( 'conversion', $output );

			// Update attachment's file path and mime-type.
			static::update_attachment_info( $this->id, $this->converted_file, static::CONVERSION_MIME_TYPE );

			// Update attachment's metadata.
			$metadata = $this->metadata_from_output( $this->converted_file, $output );
			if (
				$metadata &&
				Qode_Optimizer_Utility::multiple_array_keys_exist(
					array(
						'wp_attached_file',
						'wp_attachment_metadata',
					),
					$metadata
				)
			) {
				static::update_attachment_metadata( $this->id, $metadata['wp_attached_file'], $metadata['wp_attachment_metadata'] );
			}

			// Update all posts' content including attachment's url.
			$update_data = static::prepare_db_update_url_params( $this->id, 'output', $output );
			if ( $update_data ) {
				static::db_update_url( $update_data );
			}
		}

		return $output;
	}

	/**
	 * Convert main image thumbs
	 *
	 * @param array $conversion_methods_queue List of methods for trying to convert image with, if one fails system tries another one from the list
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function thumbs_convert( $conversion_methods_queue = array( 'gmagick', 'imagick', 'gd' ) ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( ! is_array( $conversion_methods_queue ) ) {
			$conversion_methods_queue = array( 'gmagick', 'imagick', 'gd' );
		}

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );
		$output->set_param( 'success', false );

		$all_thumbs = new Qode_Optimizer_Images(
			array(
				'files'                  => $this->get_all_image_thumb_path_info(),
				'additional_compression' => $this->additional_compression,
			)
		);

		// Conversion - thumbs.
		try {
			$system_log->add_log( 'Start the process of converting the thumb images' );

			$output = $all_thumbs->multiple_convert( $conversion_methods_queue );

			$system_log->add_log( 'Finish the process of converting the thumb images' );
		} catch ( Exception $exception ) {
			$system_log->add_log( 'Some error occurred during a process of converting multiple images' );
		}

		return $output;
	}

	/**
	 * Convert folders images
	 *
	 * @param array $conversion_methods_queue List of methods for trying to convert image with, if one fails system tries another one from the list
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function folders_image_convert( $conversion_methods_queue = array( 'gmagick', 'imagick', 'gd' ) ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( ! is_array( $conversion_methods_queue ) ) {
			$conversion_methods_queue = array( 'gmagick', 'imagick', 'gd' );
		}

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );
		$output->set_param( 'success', false );

		$system_log->add_log( '* CONVERSION OF THE FOLDERS IMAGE', true );

		if (
			! Qode_Optimizer_Options::get_option( 'backup_already_made' ) &&
			'local' === Qode_Optimizer_Options::get_option( 'backup_method' )
		) {
			$this->create_folders_image_and_thumbs_backup();
		}

		$this->remove_webp();

		$filesystem = new Qode_Optimizer_Filesystem();

		// Conversion - folders image.
		try {
			$system_log->add_log( 'Start the process of converting the folders image' );

			$folders_file_output = $this->convert( $conversion_methods_queue );

			$system_log->add_log( 'Finish the process of converting the folders image' );

			$current_file = $this->file;
			$current_size = $filesystem->filesize( $current_file );

			$output->set_param( 'original_file', wp_basename( $current_file ) );
			$output->set_param( 'initial_size_raw', $current_size );
			$output->set_param( 'initial_size', $filesystem->readable_size_format( $current_size ) );

			// Folders image conversion failed.
			if ( ! $folders_file_output->get_param( 'file' ) ) {

				if ( $folders_file_output->get_param( 'result' ) ) {
					$output->set_param( 'result', $folders_file_output->get_param( 'result' ) );
				}

				$system_log->add_log( 'Folder image was not converted successfully. Conversion process is stopped' );

				return $output;
			}
		} catch ( Exception $exception ) {
			$system_log->add_log( 'Some error occurred during a process of converting the folders image' );
		}

		$all_files_params   = $output->get_param( 'files' );
		$all_files_params[] = $folders_file_output;
		$output->set_param( 'files', $all_files_params );

		if ( $folders_file_output->get_param( 'file' ) ) {
			$output->set_param( 'success', true );
		}

		if ( $output->get_param( 'success' ) ) {
			$this->save_folders_modifications( 'conversion', $output );

			// Update all posts' content including attachment's url.
			$update_data = static::folders_image_prepare_db_update_url_params( $this->file, 'output', $output );
			if ( $update_data ) {
				static::db_update_url( $update_data );
			}
		}

		return $output;
	}

	/**
	 * Image conversion using built-in PHP functions.
	 *
	 * @access public
	 *
	 * @param array $conversion_methods_queue List of methods for trying to convert image with, if one fails system tries another one from the list
	 * @param bool $check_size Converted/original file sizes comparison. Converted file size must be smaller than original file size
	 *
	 * @return Qode_Optimizer_Output
	 *
	 * @throws GmagickException On error
	 * @throws ImagickException Throws ImagickException on error
	 */
	public function convert( $conversion_methods_queue = array( 'gmagick', 'imagick', 'gd' ), $check_size = true ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( ! is_array( $conversion_methods_queue ) ) {
			$conversion_methods_queue = array( 'gmagick', 'imagick', 'gd' );
		}

		$system_log->add_log( 'File to convert: ' . wp_basename( $this->file ) );

		$filesystem = new Qode_Optimizer_Filesystem();
		$output     = new Qode_Optimizer_Output();
		$output->set_param( 'file', false );

		if (
			! $filesystem->is_file( $this->file ) ||
			! $filesystem->is_writable( $this->file ) ||
			! static::MIME_TYPE ||
			! in_array( static::MIME_TYPE, static::ALLOWED_MIME_TYPES['general'], true )
		) {
			$output->add_message( 'Some error occurred' );

			return $output;
		}

		$output         = $this->convert_by_mime_type( $conversion_methods_queue, $check_size );
		$converted_file = $output->get_param( 'file' );

		if ( $converted_file ) {
			$converted_file_real = realpath( $converted_file );

			if (
				$converted_file_real &&
				$filesystem->is_file( $converted_file_real )
			) {
				$this->converted_file = $converted_file;
				$this->is_converted   = true;
			}
		}

		return $output;
	}

	/**
	 * Image conversion by mime-type
	 *
	 * @param array $conversion_methods_queue List of methods for trying to convert image with, if one fails system tries another one from the list
	 * @param string $check_size Converted/original file sizes comparison. Converted file size must be smaller than original file size
	 *
	 * @return Qode_Optimizer_Output
	 *
	 * @throws GmagickException On error
	 * @throws ImagickException Throws ImagickException on error
	 */
	protected function convert_by_mime_type( $conversion_methods_queue, $check_size ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( ! is_array( $conversion_methods_queue ) ) {
			$conversion_methods_queue = array( 'gmagick', 'imagick', 'gd' );
		}

		$system_log->add_log( 'Using conversion method(s): ' . implode( ', ', $conversion_methods_queue ) );

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'file', false );
		$output->set_param( 'previous_file', $this->file );
		$output->set_param( 'previous_file_basename', wp_basename( $this->file ) );
		$output->set_param( 'previous_url', trailingslashit( dirname( $this->url ) ) . wp_basename( $this->file ) );
		$output->set_param( 'media_size', '' );
		$output->set_param( 'initial_size_raw', 0 );
		$output->set_param( 'initial_size', '' );
		$output->set_param( 'filesize_raw', 0 );
		$output->set_param( 'filesize', '' );

		$filesystem = new Qode_Optimizer_Filesystem();

		// Conversion.
		$new_filesize = 0;
		$initial_size = $filesystem->filesize( $this->file );
		$output->set_param( 'initial_size_raw', $initial_size );
		$output->set_param( 'initial_size', $filesystem->readable_size_format( $initial_size ) );

		$conversion_extension = static::ALLOWED_MIME_TYPES['mime_type_extension_map'][ static::CONVERSION_MIME_TYPE ];

		// Target file for conversion doesn't have unique filename.
		$new_file = $this->create_unique_filename( $this->file, $conversion_extension );
		if ( ! $new_file ) {
			$output->set_param( 'result', 'Unique filename creation failed, conversion skipped' );

			$system_log->add_log( 'Unique filename creation failed. Conversion process is stopped' );

			return $output;
		}

		// Target file for conversion already exists.
		if ( $filesystem->is_file( $new_file ) ) {
			$output->set_param( 'result', 'Target file already exists, conversion skipped' );

			$system_log->add_log( 'Target file for conversion already exists (another image already exists with that filename). Conversion process is stopped' );

			return $output;
		}

		$conversion_params = $this->get_conversion_params();

		foreach ( $conversion_methods_queue as $method ) {
			switch ( $method ) {
				case 'gmagick':
					// Gmagick conversion.
					if (
						! $new_filesize &&
						Qode_Optimizer_Support::get_system_param( 'gmagick_support_exists' )
					) {
						$output->add_message( 'Conversion via Gmagick' );
						$new_filesize = $this->gmagick_convert_by_mime_type( $new_file, $conversion_params );
					}
					break;
				case 'imagick':
					// Imagick conversion.
					if (
						! $new_filesize &&
						Qode_Optimizer_Support::get_system_param( 'imagick_support_exists' )
					) {
						$output->add_message( 'Conversion via Imagick' );
						$new_filesize = $this->imagick_convert_by_mime_type( $new_file, $conversion_params );
					}
					break;
				case 'gd':
					// GD conversion.
					if (
						! $new_filesize &&
						Qode_Optimizer_Support::get_system_param( 'gd_support_exists' )
					) {
						$output->add_message( 'Conversion via GD' );
						$new_filesize = $this->gd_convert_by_mime_type( $new_file, $conversion_params );
					}
					break;
				default:
					break;
			}
		}

		if (
			$filesystem->is_file( $new_file ) &&
			static::CONVERSION_MIME_TYPE === $filesystem->get_mime_type( $new_file )
		) {
			if (
				! $check_size ||
				$new_filesize < $initial_size
			) {
				$output->set_param( 'file', $new_file );
				$output->set_param( 'file_basename', wp_basename( $new_file ) );
				$output->set_param( 'media_size', $this->media_size );
				$output->set_param( 'filesize_raw', $new_filesize );
				$output->set_param( 'filesize', $filesystem->readable_size_format( $new_filesize ) );
				$output->set_param( 'url', trailingslashit( dirname( $this->url ) ) . wp_basename( $new_file ) );
				$output->set_param( 'result', $filesystem->readable_filesize_savings( $initial_size, $new_filesize ) );
				$output->add_message( 'Results: ' . $filesystem->readable_filesize_savings( $initial_size, $new_filesize ) );

				return $output;
			} elseif (
				$check_size &&
				$new_filesize >= $initial_size
			) {
				$filesystem->delete_file( $new_file );
				$output->set_param( 'result', 'Image too large and deleted' );
				$output->add_message( 'Image was too large and was deleted' );

				return $output;
			}
		} else {
			$output->add_message( 'Image conversion failed' );
		}

		return $output;
	}

	/**
	 * Get conversion params
	 *
	 * @return array
	 */
	abstract protected function get_conversion_params();

	/**
	 * Gmagick image conversion by mime-type
	 *
	 * @param string $new_file Converted image path
	 * @param array $conversion_params Conversion params
	 *
	 * @return int File size
	 *
	 * @throws GmagickException On error
	 */
	abstract protected function gmagick_convert_by_mime_type( $new_file, $conversion_params );

	/**
	 * Imagick image conversion by mime-type
	 *
	 * @param string $new_file Converted image path
	 * @param array $conversion_params Conversion params
	 *
	 * @return int File size
	 *
	 * @throws ImagickException Throws ImagickException on error
	 */
	abstract protected function imagick_convert_by_mime_type( $new_file, $conversion_params );

	/**
	 * GD image conversion by mime-type
	 *
	 * @param string $new_file Converted image path
	 * @param array $conversion_params Conversion params
	 *
	 * @return int File size
	 */
	abstract protected function gd_convert_by_mime_type( $new_file, $conversion_params );

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
		if ( 'tools' === $this->webp_conversion_method ) {
			return array( 'tools' );
		}

		return $conversion_methods_queue;
	}

	/**
	 * Creates WebP images for main image and its thumbs
	 *
	 * @param array $conversion_methods_queue List of methods for trying to create WebP image with, if one fails system tries another one from the list
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function image_and_thumbs_create_webp( $conversion_methods_queue = array( 'imagick', 'gd' ) ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( ! is_array( $conversion_methods_queue ) ) {
			$conversion_methods_queue = array( 'imagick', 'gd' );
		}

		$conversion_methods_queue = $this->override_create_webp_method( $conversion_methods_queue );

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );
		$output->set_param( 'success', false );

		$system_log->add_log( '* CREATION OF WEBP FILES FOR THE MAIN IMAGE AND THUMBS', true );

		$filesystem = new Qode_Optimizer_Filesystem();

		// WebP creation - original image.
		try {
			$system_log->add_log( 'Start the process of creating WebP files for the main image' );

			$main_file_output = $this->create_webp( $conversion_methods_queue );

			$system_log->add_log( 'Finish the process of creating WebP files for the main image' );

			$current_file = get_attached_file( $this->id );
			$current_size = $filesystem->filesize( $current_file );

			$output->set_param( 'original_file', wp_basename( $current_file ) );
			$output->set_param( 'initial_size_raw', $current_size );
			$output->set_param( 'initial_size', $filesystem->readable_size_format( $current_size ) );

			// Main image webp creation failed.
			if ( ! $main_file_output->get_param( 'file' ) ) {

				if ( $main_file_output->get_param( 'result' ) ) {
					$output->set_param( 'result', $main_file_output->get_param( 'result' ) );
				}

				return $output;
			}
		} catch ( Exception $exception ) {
			$system_log->add_log( 'Some error occurred during a process of creating WebP image from the original image' );
		}

		$all_files_params   = $output->get_param( 'files' );
		$all_files_params[] = $main_file_output;
		$output->set_param( 'files', $all_files_params );

		if ( $main_file_output->get_param( 'file' ) ) {
			$output->set_param( 'success', true );
		}

		// WebP creation - thumbs.
		if ( $this->has_thumbs() ) {
			try {
				$thumbs_files_output = $this->thumbs_create_webp( $conversion_methods_queue );

				// One or more thumb image webp creation failed, rollback, delete any of successfully created files so far.
				if ( ! $thumbs_files_output->get_param( 'success' ) ) {
					$output->set_param( 'success', false );

					// Delete created main webp file.
					$main_file = $main_file_output->get_param( 'file' );
					if ( $main_file ) {
						$filesystem->delete_file( $main_file_output->get_param( 'file' ) );
					}

					// Delete created thumb webp files.
					$thumb_output_files = $thumbs_files_output->get_param( 'files' );
					foreach ( $thumb_output_files as $file ) {

						if ( $file instanceof Qode_Optimizer_Output ) {
							$current_thumb_file = $file->get_param( 'file' );
							if ( $current_thumb_file ) {
								$filesystem->delete_file( $current_thumb_file );
							} elseif ( $file->get_param( 'result' ) ) {
								$output->set_param( 'result', $file->get_param( 'result' ) );
							}
						}
					}

					return $output;
				}
			} catch ( Exception $exception ) {
				$system_log->add_log( 'Some error occurred during a process of creating WebP images from the thumb images' );
			}

			$all_files_params = $output->get_param( 'files' );
			$all_files_params = array_merge( $all_files_params, $thumbs_files_output->get_param( 'files' ) );
			$output->set_param( 'files', $all_files_params );

			if ( $output->get_param( 'success' ) ) {
				$output->set_param( 'success', $thumbs_files_output->get_param( 'success' ) );
			}
		}

		return $output;
	}

	/**
	 * Creates WebP images for main image thumbs
	 *
	 * @param array $conversion_methods_queue List of methods for trying to create WebP image with, if one fails system tries another one from the list
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function thumbs_create_webp( $conversion_methods_queue = array( 'imagick', 'gd' ) ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( ! is_array( $conversion_methods_queue ) ) {
			$conversion_methods_queue = array( 'imagick', 'gd' );
		}

		$conversion_methods_queue = $this->override_create_webp_method( $conversion_methods_queue );

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );
		$output->set_param( 'success', false );

		$all_thumbs = new Qode_Optimizer_Images(
			array(
				'files'                  => $this->get_all_image_thumb_path_info(),
				'additional_compression' => $this->additional_compression,
			)
		);

		// WebP creation - thumbs.
		try {
			$system_log->add_log( 'Start the process of creating WebP files for the thumb images' );

			$output = $all_thumbs->multiple_create_webp( $conversion_methods_queue );

			$system_log->add_log( 'Finish the process of creating WebP files for the thumb images' );
		} catch ( Exception $exception ) {
			$system_log->add_log( 'Some error occurred during a process of creating WebP images from multiple images' );
		}

		return $output;
	}

	/**
	 * Creates WebP images for folders images
	 *
	 * @param array $conversion_methods_queue List of methods for trying to create WebP image with, if one fails system tries another one from the list
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function folders_image_create_webp( $conversion_methods_queue = array( 'imagick', 'gd' ) ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( ! is_array( $conversion_methods_queue ) ) {
			$conversion_methods_queue = array( 'imagick', 'gd' );
		}

		$conversion_methods_queue = $this->override_create_webp_method( $conversion_methods_queue );

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );
		$output->set_param( 'success', false );

		$system_log->add_log( '* CREATION OF WEBP FILES FOR THE FOLDERS IMAGE', true );

		$filesystem = new Qode_Optimizer_Filesystem();

		// WebP creation - folders image.
		try {
			$system_log->add_log( 'Start the process of creating WebP files for the folders image' );

			$folders_file_output = $this->create_webp( $conversion_methods_queue );

			$system_log->add_log( 'Finish the process of creating WebP files for the folders image' );

			$current_file = $this->file;
			$current_size = $filesystem->filesize( $current_file );

			$output->set_param( 'original_file', wp_basename( $current_file ) );
			$output->set_param( 'initial_size_raw', $current_size );
			$output->set_param( 'initial_size', $filesystem->readable_size_format( $current_size ) );

			// Folders image webp creation failed.
			if ( ! $folders_file_output->get_param( 'file' ) ) {

				if ( $folders_file_output->get_param( 'result' ) ) {
					$output->set_param( 'result', $folders_file_output->get_param( 'result' ) );
				}

				return $output;
			}
		} catch ( Exception $exception ) {
			$system_log->add_log( 'Some error occurred during a process of creating WebP image from the folders image' );
		}

		$all_files_params   = $output->get_param( 'files' );
		$all_files_params[] = $folders_file_output;
		$output->set_param( 'files', $all_files_params );

		if ( $folders_file_output->get_param( 'file' ) ) {
			$output->set_param( 'success', true );
		}

		return $output;
	}

	/**
	 * Creates WebP images alongside JPG and PNG files.
	 *
	 * @param array $conversion_methods_queue List of methods for trying to create WebP image with, if one fails system tries another one from the list
	 *
	 * @return Qode_Optimizer_Output
	 *
	 * @throws ImagickException Throws ImagickException on error.
	 */
	public function create_webp( $conversion_methods_queue = array( 'imagick', 'gd' ) ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( ! is_array( $conversion_methods_queue ) ) {
			$conversion_methods_queue = array( 'imagick', 'gd' );
		}

		$conversion_methods_queue = $this->override_create_webp_method( $conversion_methods_queue );

		$webp_file = $this->file . '.webp';

		$system_log->add_log( 'WebP file to create: ' . wp_basename( $webp_file ) );

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'file', false );
		$output->set_param( 'previous_file', $this->file );
		$output->set_param( 'previous_file_basename', wp_basename( $this->file ) );
		$output->set_param( 'previous_url', trailingslashit( dirname( $this->url ) ) . wp_basename( $this->file ) );
		$output->set_param( 'media_size', '' );
		$output->set_param( 'initial_size_raw', 0 );
		$output->set_param( 'initial_size', '' );
		$output->set_param( 'filesize_raw', 0 );
		$output->set_param( 'filesize', '' );

		$filesystem = new Qode_Optimizer_Filesystem();

		if (
			! $filesystem->is_file( $this->file ) ||
			! $filesystem->is_writable( $this->file )
		) {
			$output->add_message( 'Some error occurred' );

			return $output;
		}

		list( $width, $height ) = wp_getimagesize( $this->file );
		if ( $width > 16383 || $height > 16383 ) {
			// Image dimensions are too great, cannot be converted to WebP.
			$output->add_message( 'Image dimensions are too great' );

			return $output;
		}

		$webp_file_created = false;
		$initial_size      = $filesystem->filesize( $this->file );
		$output->set_param( 'initial_size_raw', $initial_size );
		$output->set_param( 'initial_size', $filesystem->readable_size_format( $initial_size ) );

		foreach ( $conversion_methods_queue as $method ) {
			switch ( $method ) {
				case 'imagick':
					// Imagick conversion.
					if (
						! $webp_file_created &&
						Qode_Optimizer_Support::get_system_param( 'imagick_supports_webp' )
					) {
						$output->add_message( 'Create WebP via Imagick' );
						$webp_file_created = $this->imagick_create_webp( $webp_file );
					}
					break;
				case 'gd':
					// GD conversion.
					if (
						! $webp_file_created &&
						Qode_Optimizer_Support::get_system_param( 'gd_supports_webp' )
					) {
						$output->add_message( 'Create WebP via GD' );
						$webp_file_created = $this->gd_create_webp( $webp_file );
					}
					break;
				case 'tools':
					// TOOLS conversion.
					if (
						! $webp_file_created &&
						Qode_Optimizer_Support::tool_support_exists( array( 'cwebp' ) )
					) {
						$output->add_message( 'Create WebP via TOOLS' );
						$webp_file_created = $this->tool_create_webp( $webp_file );
					}
					break;
				case 'gif2webp':
					// GIF2WEBP conversion.
					if (
						! $webp_file_created &&
						Qode_Optimizer_Support::tool_support_exists( array( 'gif2webp' ) )
					) {
						$output->add_message( 'Create WebP via GIF2WEBP' );
						$webp_file_created = $this->gif2webp_create_webp( $webp_file );
					}
					break;
				default:
					break;
			}
		}

		if ( $filesystem->is_file( $webp_file ) ) {
			$webp_size = $filesystem->filesize( $webp_file );

			if ( $initial_size < $webp_size ) {
				// Created WebP is bigger than original.
				$filesystem->delete_file( $webp_file );
				$output->set_param( 'result', 'WebP image too large and deleted' );
				$output->add_message( 'WebP image was too large and was deleted' );

				return $output;
			}

			$output->set_param( 'file', $webp_file );
			$output->set_param( 'filesize_raw', $webp_size );
			$output->set_param( 'filesize', $filesystem->readable_size_format( $webp_size ) );
			$output->set_param( 'url', trailingslashit( dirname( $this->url ) ) . wp_basename( $webp_file ) );
			$output->set_param( 'media_size', $this->media_size );
			$output->set_param( 'result', $filesystem->readable_filesize_savings( $initial_size, $webp_size ) );
			$output->add_message( 'WebP: ' . $filesystem->readable_filesize_savings( $initial_size, $webp_size ) );
		} else {
			$output->add_message( 'WebP image conversion failed' );
		}

		return $output;
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
	abstract public function imagick_create_webp( $webp_file );

	/**
	 * Create WebP Image using GD
	 *
	 * @param string $webp_file Created WebP image path
	 *
	 * @return bool
	 */
	abstract public function gd_create_webp( $webp_file );

	/**
	 * Options for WebP image creation using tools
	 *
	 * @return string
	 */
	protected function tool_create_webp_additional_options() {
		return '-q ' . $this->webp_quality . ' -sharp_yuv';
	}

	/**
	 * Create WebP Image using tools
	 *
	 * @param string $webp_file Created WebP image path
	 *
	 * @return bool
	 */
	protected function tool_create_webp( $webp_file ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( Qode_Optimizer_Support::is_tool_working( 'cwebp' ) ) {
			$system_log->add_log( 'Attempting to create WebP image using Cwebp' );

			$tool_path = Qode_Optimizer_Support::get_tool_working_path( 'cwebp' );
			/**
			 * Additional options to try:
			 * -mt
			 * -low_memory
			 */
			$system_log->add_log( $tool_path . ' -quiet ' . $this->tool_create_webp_additional_options() . ' ' . escapeshellarg( $this->file ) . ' -o ' . escapeshellarg( $webp_file ) );
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
			if ( false !== exec( $tool_path . ' -quiet ' . $this->tool_create_webp_additional_options() . ' ' . escapeshellarg( $this->file ) . ' -o ' . escapeshellarg( $webp_file ) ) ) {
				// Success.
				$system_log->add_log( 'WebP image was successfully created using Cwebp' );

				return true;
			}
		}

		$system_log->add_log( 'No WebP image was created using Cwebp' );

		return false;
	}

	/**
	 * Create WebP Image using GIF2WEBP
	 *
	 * @param string $webp_file Created WebP image path
	 *
	 * @return bool
	 */
	protected function gif2webp_create_webp( $webp_file ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return false;
	}

	/**********************************************
	 * RESTORATION (OPTIONAL)
	 *
	 * This step must be done during optimization process, which
	 * started on already optimized images. If this scenario happens,
	 * system must restore original images first, before any
	 * additional optimization is done
	 *********************************************/

	/**
	 * Restore main and thumb images
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function image_and_thumbs_restore() {
		$system_log = Qode_Optimizer_Log::get_instance();

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'success', false );

		$system_log->add_log( '* RESTORATION OF THE MAIN IMAGE AND THUMBS', true );

		$system_log->add_log( 'Start the process of restoring the main image and thumbs' );

		$this->image_and_thumbs_remove_webp();

		$files_for_deletion = array( 'original' => realpath( $this->file ) );
		foreach ( $this->get_all_image_thumb_path_info() as $path_info ) {
			if (
				Qode_Optimizer_Utility::multiple_array_keys_exist(
					array(
						'media_size',
						'full_path',
					),
					$path_info
				) &&
				! empty( $path_info['media_size'] ) &&
				! empty( $path_info['full_path'] )
			) {
				$files_for_deletion[ $path_info['media_size'] ] = realpath( $path_info['full_path'] );
			}
		}

		$system_log->add_log( 'Attempting to restore the main image and thumbs from backup folder' );

		// Restoration - original image and thumbs.
		$backup           = new Qode_Optimizer_Backup();
		$backup_data      = $backup->restore_file_from_db_and_folder( $this->id );
		$current_metadata = maybe_unserialize( wp_get_attachment_metadata( $this->id ) );
		if (
			! $backup_data ||
			! Qode_Optimizer_Utility::multiple_array_keys_exist(
				array(
					'original_file',
					'original_mime_type',
					'wp_attached_file',
					'wp_attachment_metadata',
					'original_paths',
				),
				$backup_data
			) ||
			! $current_metadata
		) {
			$output_data = array(
				'file'          => $this->file,
				'file_basename' => wp_basename( $this->file ),
				'url'           => trailingslashit( dirname( $this->url ) ) . wp_basename( $this->file ),
			);
			$output->set_param( 'data', $output_data );
			$output->set_param( 'result', 'Failed' );

			$system_log->add_log( 'Failed to restore the main image and thumbs from the backup folder' );

			return $output;
		}

		$output->set_param( 'success', true );

		$system_log->add_log( 'The main image and thumbs were successfully restored from the backup folder' );

		$output_data = array(
			'file'          => $backup_data['wp_attached_file'],
			'file_basename' => wp_basename( $backup_data['wp_attached_file'] ),
			'url'           => trailingslashit( dirname( $this->url ) ) . wp_basename( $backup_data['wp_attached_file'] ),
		);
		$output->set_param( 'data', $output_data );
		$output->set_param( 'result', 'Successful' );

		$files_for_deletion = array_diff_assoc( $files_for_deletion, $backup_data['original_paths'] );
		if ( $files_for_deletion ) {
			$filesystem = new Qode_Optimizer_Filesystem();
			foreach ( $files_for_deletion as $file_for_deletion ) {
				$filesystem->delete_file( $file_for_deletion );
			}

			$system_log->add_log( 'The old main image and thumbs files were successfully deleted' );
		}

		static::procedure_after_restoration( $this->id );

		// Update attachment's file path and mime-type.
		static::update_attachment_info( $this->id, $backup_data['original_file'], $backup_data['original_mime_type'] );

		// Update attachment's metadata.
		static::update_attachment_metadata( $this->id, $backup_data['wp_attached_file'], $backup_data['wp_attachment_metadata'] );

		// Update all posts' content including attachment's url.
		$update_data = static::prepare_db_update_url_params(
			$this->id,
			'backup',
			array(
				'backup'                 => $backup_data,
				'wp_attachment_metadata' => $current_metadata,
			)
		);
		if ( $update_data ) {
			static::db_update_url( $update_data );
		}

		$system_log->add_log( 'Finish the process of restoring the main image and thumbs' );

		return $output;
	}

	/**
	 * Restore folder image
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function folders_image_restore() {
		$system_log = Qode_Optimizer_Log::get_instance();

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'success', false );

		$system_log->add_log( '* RESTORATION OF THE FOLDERS IMAGE', true );

		$system_log->add_log( 'Start the process of restoring the folders image' );

		$this->remove_webp();

		$files_for_deletion = array( 'folders' => realpath( $this->file ) );

		$system_log->add_log( 'Attempting to restore the folders image from backup folder' );

		// Restoration - original image and thumbs.
		$backup      = new Qode_Optimizer_Backup();
		$backup_data = $backup->restore_folders_file_from_db_and_folder( $this->file );

		if (
			! $backup_data ||
			! Qode_Optimizer_Utility::multiple_array_keys_exist(
				array(
					'original_file',
					'original_mime_type',
					'original_paths',
				),
				$backup_data
			)
		) {
			$output_data = array(
				'file'          => $this->file,
				'file_basename' => wp_basename( $this->file ),
				'url'           => trailingslashit( dirname( $this->url ) ) . wp_basename( $this->file ),
			);
			$output->set_param( 'data', $output_data );
			$output->set_param( 'result', 'Failed' );

			$system_log->add_log( 'Failed to restore the folders image from the backup folder' );

			return $output;
		}

		$output->set_param( 'success', true );

		$system_log->add_log( 'The folders image were successfully restored from the backup folder' );

		$output_data = array(
			'file'          => $backup_data['original_file'],
			'file_basename' => wp_basename( $backup_data['original_file'] ),
			'url'           => trailingslashit( dirname( $this->url ) ) . wp_basename( $backup_data['original_file'] ),
		);
		$output->set_param( 'data', $output_data );
		$output->set_param( 'result', 'Successful' );

		$files_for_deletion = array_diff_assoc( $files_for_deletion, $backup_data['original_paths'] );
		if ( $files_for_deletion ) {
			$filesystem = new Qode_Optimizer_Filesystem();
			foreach ( $files_for_deletion as $file_for_deletion ) {
				$filesystem->delete_file( $file_for_deletion );
			}

			$system_log->add_log( 'The old folders image files were successfully deleted' );
		}

		static::folders_image_procedure_after_restoration( $this->file );

		// Update all posts' content including attachment's url.
		$update_data = static::folders_image_prepare_db_update_url_params(
			$this->file,
			'backup',
			array(
				'backup' => $backup_data,
			)
		);
		if ( $update_data ) {
			static::db_update_url( $update_data );
		}

		$system_log->add_log( 'Finish the process of restoring the folders image' );

		return $output;
	}

	/**
	 * Regenerate thumb images
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function regenerate_thumbs() {
		$system_log = Qode_Optimizer_Log::get_instance();

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'file_basename', wp_basename( $this->file ) );
		$output->set_param( 'success', false );
		$output->set_param( 'result', 'Failed' );

		$system_log->add_log( '* REGENERATION OF THE IMAGE THUMBS', true );

		$system_log->add_log( 'Start the process of regenerating image thumbs' );

		$metadata = wp_generate_attachment_metadata( $this->id, $this->file );
		if ( $metadata ) {

			if ( array_key_exists( 'filesize', $metadata ) ) {
				$this->image_and_thumbs_remove_webp();

				$output->set_param( 'success', true );
				$output->set_param( 'result', 'Successful' );

				static::procedure_after_regeneration( $this->id );
			}
		}

		$system_log->add_log( 'Finish the process of regenerating image thumbs' );

		return $output;
	}

	/**
	 * Recover main and thumb images
	 *
	 * @param int $id
	 *
	 * @return Qode_Optimizer_Output
	 */
	public static function image_and_thumbs_recover( $id ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		$id = intval( $id );

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );
		$output->set_param( 'success', false );

		$system_log->add_log( '* RECOVER OF THE MAIN IMAGE AND THUMBS', true );

		$system_log->add_log( 'Start the process of recovering the main image and thumbs' );

		$system_log->add_log( 'Attempting to recover the main image and thumbs from backup folder' );

		$backup           = new Qode_Optimizer_Backup();
		$backup_data      = $backup->restore_file_from_db_and_folder( $id );
		$current_metadata = maybe_unserialize( wp_get_attachment_metadata( $id ) );
		if (
			! $backup_data ||
			! Qode_Optimizer_Utility::multiple_array_keys_exist(
				array(
					'original_file',
					'original_mime_type',
					'wp_attached_file',
					'wp_attachment_metadata',
				),
				$backup_data
			) ||
			! $current_metadata
		) {
			$output->set_param( 'result', 'Failed' );

			$system_log->add_log( 'Failed to recover the main image and thumbs from the backup folder' );

			return $output;
		}

		$output->set_param( 'success', true );

		$system_log->add_log( 'The main image and thumbs were successfully recovered from the backup folder' );

		$output_data = array(
			'file'          => $backup_data['wp_attached_file'],
			'file_basename' => wp_basename( $backup_data['wp_attached_file'] ),
			'url'           => trailingslashit( dirname( wp_get_attachment_url( $id ) ) ) . wp_basename( $backup_data['wp_attached_file'] ),
		);
		$output->set_param( 'data', $output_data );
		$output->set_param( 'result', 'Successful' );

		static::procedure_after_restoration( $id );

		// Update attachment's file path and mime-type.
		static::update_attachment_info( $id, $backup_data['original_file'], $backup_data['original_mime_type'] );

		// Update attachment's metadata.
		static::update_attachment_metadata( $id, $backup_data['wp_attached_file'], $backup_data['wp_attachment_metadata'] );

		// Update all posts' content including attachment's url.
		$update_data = static::prepare_db_update_url_params(
			$id,
			'backup',
			array(
				'backup'                 => $backup_data,
				'wp_attachment_metadata' => $current_metadata,
			)
		);
		if ( $update_data ) {
			static::db_update_url( $update_data );
		}

		$system_log->add_log( 'Finish the process of recovering the main image and thumbs' );

		return $output;
	}

	/**
	 * WP database attachment's info update
	 *
	 * @param int $id
	 * @param string $file File path
	 * @param string $mime_type Mime-type
	 *
	 * @return bool
	 * @global object $wpdb
	 */
	public static function update_attachment_info( $id, $file, $mime_type ) {
		$id = intval( $id );

		$filesystem = new Qode_Optimizer_Filesystem();

		$file = realpath( $file );

		if (
			! $filesystem->is_file( $file ) ||
			! in_array( $mime_type, static::ALLOWED_MIME_TYPES['general'], true )
		) {
			return false;
		}

		$guid = trailingslashit( dirname( wp_get_attachment_url( $id ) ) ) . wp_basename( $file );

		global $wpdb;

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->posts,
			array(
				'guid'           => $guid,
				'post_mime_type' => $mime_type,
			),
			array(
				'ID' => $id,
			)
		);

		return true;
	}

	/**
	 * Create metadata from output data
	 *
	 * @param string $new_file File path
	 * @param Qode_Optimizer_Output $output
	 *
	 * @return bool|array
	 */
	public function metadata_from_output( $new_file, $output ) {
		$filesystem = new Qode_Optimizer_Filesystem();

		if (
			! is_string( $new_file ) || empty( $new_file ) || ! $filesystem->is_file( realpath( $new_file ) ) ||
			! ( $output instanceof Qode_Optimizer_Output ) ||
			empty( $this->attached_file_meta ) ||
			empty( $this->metadata )
		) {
			return false;
		}

		$files = $output->get_param( 'files' );
		if ( $files ) {
			$result = array(
				'wp_attached_file'       => '',
				'wp_attachment_metadata' => '',
			);

			// _wp_attachment_metadata replacement.
			$new_metadata = $this->metadata;

			foreach ( $files as $file ) {
				$current_file = $file instanceof Qode_Optimizer_Output ? $file->get_param( 'file' ) : '';

				if ( $current_file ) {
					$current_filesize   = $file->get_param( 'filesize_raw' );
					$current_media_size = $file->get_param( 'media_size' );

					if ( 'original' === $current_media_size ) {
						$new_metadata['file']           = trailingslashit( _wp_get_attachment_relative_path( $current_file ) ) . wp_basename( $current_file );
						$new_metadata['filesize']       = $current_filesize;
						$new_metadata['original_image'] = wp_basename( $current_file );
					} elseif ( 'scaled' === $current_media_size ) {
						$main_file          = trailingslashit( _wp_get_attachment_relative_path( $current_file ) ) . wp_basename( $current_file );
						$main_filesize      = $current_filesize;
						$main_file_basename = wp_basename( $current_file );
					} elseif ( array_key_exists( $current_media_size, $new_metadata['sizes'] ) ) {
						$new_metadata['sizes'][ $current_media_size ]['file']      = wp_basename( $current_file );
						$new_metadata['sizes'][ $current_media_size ]['mime-type'] = static::MIME_TYPE;
						$new_metadata['sizes'][ $current_media_size ]['filesize']  = $current_filesize;
					}
				}
			}

			if (
				isset( $main_file ) &&
				isset( $main_filesize )
			) {
				$new_metadata['file']     = $main_file;
				$new_metadata['filesize'] = $main_filesize;
			}

			$result['wp_attachment_metadata'] = $new_metadata;

			// _wp_attached_file replacement.
			$new_wp_attached_file = trailingslashit( dirname( $this->attached_file_meta ) );

			if ( isset( $main_file_basename ) ) {
				$new_wp_attached_file .= $main_file_basename;
			} else {
				$new_wp_attached_file .= wp_basename( $new_file );
			}

			$result['wp_attached_file'] = $new_wp_attached_file;

			return $result;
		}

		return false;
	}

	/**
	 * WP database attachment's metadata update
	 *
	 * @param int $id
	 * @param string $attached_file
	 * @param string $attachment_metadata
	 *
	 * @return bool
	 *
	 * @global object $wpdb
	 */
	public static function update_attachment_metadata( $id, $attached_file, $attachment_metadata ) {
		$id = intval( $id );

		if (
			! is_string( $attached_file ) ||
			! is_array( $attachment_metadata ) ||
			empty( $attached_file ) ||
			empty( $attachment_metadata )
		) {
			return false;
		}

		update_attached_file( $id, $attached_file );

		wp_update_attachment_metadata( $id, $attachment_metadata );

		return true;
	}

	/**
	 * Prepare url params for database update process
	 *
	 * @param int $id
	 * @param string $source
	 * @param mixed $file_data
	 *
	 * @return bool|array
	 */
	public static function prepare_db_update_url_params( $id, $source, $file_data ) {
		$id = intval( $id );

		if (
			! is_string( $source ) ||
			empty( $source )
		) {
			return false;
		}

		switch ( $source ) {
			case 'output':
				$files = $file_data instanceof Qode_Optimizer_Output ? $file_data->get_param( 'files' ) : '';

				if ( empty( $files ) ) {
					return false;
				}

				$result = array();

				foreach ( $files as $file ) {
					if ( $file instanceof Qode_Optimizer_Output ) {
						$result[] = array(
							'guid'     => trailingslashit( dirname( wp_get_attachment_url( $id ) ) ) . wp_basename( $file->get_param( 'previous_file' ) ),
							'new_guid' => trailingslashit( dirname( wp_get_attachment_url( $id ) ) ) . wp_basename( $file->get_param( 'file' ) ),
						);
					}
				}

				return $result;
			case 'backup':
				if (
					! is_array( $file_data ) ||
					empty( $file_data ) ||
					! Qode_Optimizer_Utility::multiple_array_keys_exist(
						array(
							'backup',
							'wp_attachment_metadata',
						),
						$file_data
					)
				) {
					return false;
				}

				$result = array();
				foreach ( $file_data['backup']['original_paths'] as $media_size => $file ) {
					$previous_file = '';
					if ( 'original' === $media_size ) {
						$previous_file = array_key_exists( 'original_image', $file_data['wp_attachment_metadata'] )
							? $file_data['wp_attachment_metadata']['original_image']
							: $file_data['wp_attachment_metadata']['file'];
					} elseif ( 'scaled' === $media_size ) {
						$previous_file = $file_data['wp_attachment_metadata']['file'];
					} elseif ( array_key_exists( $media_size, $file_data['wp_attachment_metadata']['sizes'] ) ) {
						$previous_file = $file_data['wp_attachment_metadata']['sizes'][ $media_size ]['file'];
					}

					if (
						! empty( $previous_file ) &&
						! empty( $file )
					) {

						$result[] = array(
							'guid'     => trailingslashit( dirname( wp_get_attachment_url( $id ) ) ) . wp_basename( $previous_file ),
							'new_guid' => trailingslashit( dirname( wp_get_attachment_url( $id ) ) ) . wp_basename( $file ),
						);
					}
				}

				return $result;
			default:
				break;
		}

		return false;
	}

	/**
	 * Prepare url params for database update process for folders image
	 *
	 * @param string $source_file File path
	 * @param string $source
	 * @param mixed $file_data
	 *
	 * @return bool|array
	 **/
	public static function folders_image_prepare_db_update_url_params( $source_file, $source, $file_data ) {

		if (
			! is_string( $source ) ||
			empty( $source )
		) {
			return false;
		}

		$filesystem = new Qode_Optimizer_Filesystem();

		switch ( $source ) {
			case 'output':
				$files = $file_data instanceof Qode_Optimizer_Output ? $file_data->get_param( 'files' ) : '';

				if ( empty( $files ) ) {
					return false;
				}

				$result = array();

				foreach ( $files as $file ) {
					if ( $file instanceof Qode_Optimizer_Output ) {
						$result[] = array(
							'guid'     => $filesystem->path_to_url( $file->get_param( 'previous_file' ) ),
							'new_guid' => $filesystem->path_to_url( $file->get_param( 'file' ) ),
						);
					}
				}

				return $result;
			case 'backup':
				if (
					! is_array( $file_data ) ||
					empty( $file_data ) ||
					! array_key_exists( 'backup', $file_data )
				) {
					return false;
				}

				$result = array();
				foreach ( $file_data['backup']['original_paths'] as $media_size => $file ) {
					$previous_file = $source_file;

					if (
						! empty( $previous_file ) &&
						! empty( $file )
					) {

						$result[] = array(
							'guid'     => $filesystem->path_to_url( $previous_file ),
							'new_guid' => $filesystem->path_to_url( $file ),
						);
					}
				}

				return $result;
			default:
				break;
		}

		return false;
	}

	/**
	 * WP database url update
	 *
	 * @param array $update_data
	 *
	 * @return bool
	 *
	 * @global object $wpdb
	 **/
	public static function db_update_url( $update_data ) {
		if (
			! is_array( $update_data ) ||
			empty( $update_data )
		) {
			return false;
		}

		global $wpdb;

		foreach ( $update_data as $data ) {
			if (
				is_array( $data ) && ! empty( $data ) &&
				Qode_Optimizer_Utility::multiple_array_keys_exist( array( 'guid', 'new_guid' ), $data )
			) {
				/**
				 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
				 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
				 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
				 */
				$query   = $wpdb->prepare( "SELECT ID, post_content FROM $wpdb->posts WHERE post_content LIKE %s", '%' . $wpdb->esc_like( $data['guid'] ) . '%' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$results = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( Qode_Optimizer_Utility::is_iterable( $results ) ) {
					foreach ( $results as $row ) {
						$post_content = str_replace( $data['guid'], $data['new_guid'], $row['post_content'] );
						$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->posts,
							array(
								'post_content' => $post_content,
							),
							array(
								'ID' => $row['ID'],
							)
						);
					}
				}
			}
		}
	}

	/**
	 * Deletes main image and its thumbs
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function image_and_thumbs_delete() {
		$system_log = Qode_Optimizer_Log::get_instance();

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'success', false );

		// Deletion - thumbs.
		if ( $this->has_thumbs() ) {
			$thumbs_files_output = $this->thumbs_delete();
			$output->set_param( 'success', $thumbs_files_output->get_param( 'success' ) );
		}

		// Deletion - original image.
		$system_log->add_log( 'Start the process of deleting the main image' );

		$main_file_output = $this->delete();

		$system_log->add_log( 'Finish the process of deleting the main image' );

		if ( $output->get_param( 'success' ) ) {
			$output->set_param( 'success', $main_file_output->get_param( 'success' ) );
		}

		return $output;
	}

	/**
	 * Deletes main image thumbs
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function thumbs_delete() {
		$system_log = Qode_Optimizer_Log::get_instance();

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'success', false );

		$all_thumbs = new Qode_Optimizer_Images(
			array(
				'files'                  => $this->get_all_image_thumb_path_info(),
				'additional_compression' => $this->additional_compression,
			)
		);

		// Deletion - thumbs.
		$system_log->add_log( 'Start the process of deleting the thumb images' );

		$output = $all_thumbs->multiple_delete();

		$system_log->add_log( 'Finish the process of deleting the thumb images' );

		return $output;
	}

	/**
	 * Deletes main image
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function delete() {
		$system_log = Qode_Optimizer_Log::get_instance();

		$output = new Qode_Optimizer_Output();

		$system_log->add_log( 'File to delete: ' . wp_basename( $this->file ) );

		$filesystem = new Qode_Optimizer_Filesystem();

		$success = $filesystem->delete_file( $this->file );
		$output->set_param( 'success', $success );

		$system_log->add_log( 'The image was successfully deleted (in case there was a file)' );

		return $output;
	}

	/**
	 * Deletes WebP images for main image and its thumbs
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function image_and_thumbs_remove_webp() {
		$system_log = Qode_Optimizer_Log::get_instance();

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );
		$output->set_param( 'success', false );

		// WebP deletion - original image.
		$system_log->add_log( 'Start the process of removing the WebP file for the main image' );

		$main_file_output = $this->remove_webp();

		$system_log->add_log( 'Finish the process of removing the WebP file for the main image' );

		$all_files_params   = $output->get_param( 'files' );
		$all_files_params[] = $main_file_output;
		$output->set_param( 'files', $all_files_params );

		$success = $main_file_output->get_param( 'success' );
		$output->set_param( 'success', $success );

		// WebP deletion - thumbs.
		if ( $this->has_thumbs() ) {
			$thumbs_files_output = $this->thumbs_remove_webp();

			$all_files_params = $output->get_param( 'files' );
			$all_files_params = array_merge( $all_files_params, $thumbs_files_output->get_param( 'files' ) );
			$output->set_param( 'files', $all_files_params );

			if ( $output->get_param( 'success' ) ) {
				$output->set_param( 'success', $thumbs_files_output->get_param( 'success' ) );
			}
		}

		return $output;
	}

	/**
	 * Deletes WebP images for main image thumbs
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function thumbs_remove_webp() {
		$system_log = Qode_Optimizer_Log::get_instance();

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );
		$output->set_param( 'success', false );

		$all_thumbs = new Qode_Optimizer_Images(
			array(
				'files'                  => $this->get_all_image_thumb_path_info(),
				'additional_compression' => $this->additional_compression,
			)
		);

		// WebP deletion - thumbs.
		$system_log->add_log( 'Start the process of removing WebP files for the thumb images' );

		$output = $all_thumbs->multiple_remove_webp();

		$system_log->add_log( 'Finish the process of removing WebP files for the thumb images' );

		return $output;
	}

	/**
	 * Remove WebP images
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function remove_webp() {
		$system_log = Qode_Optimizer_Log::get_instance();

		$webp_file = $this->file . '.webp';

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'file', $webp_file );

		$system_log->add_log( 'File to remove: ' . wp_basename( $webp_file ) );

		$filesystem = new Qode_Optimizer_Filesystem();

		$success = $filesystem->delete_file( $webp_file );
		$output->set_param( 'success', $success );

		$system_log->add_log( 'WebP file for the image was successfully removed (in case there was a file)' );

		return $output;
	}

	/**
	 * Resolution of the issue created by manual replacement of the original image in the filesystem
	 */
	public function resolution() {
		$system_log = Qode_Optimizer_Log::get_instance();

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'file_basename', wp_basename( $this->file ) );
		$output->set_param( 'success', false );
		$output->set_param( 'result', 'Failed' );

		$system_log->add_log( '* ISSUE RESOLUTION OF THE IMAGE', true );

		$system_log->add_log( 'Start the process of issue resolution of the image' );

		$this->regenerate_thumbs();

		$output->set_param( 'success', true );
		$output->set_param( 'result', 'Successful' );

		$system_log->add_log( 'Finish the process of issue resolution of the image' );

		return $output;
	}

	/**
	 * Resolution of the issue created by manual replacement of the original folders image in the filesystem
	 */
	public function folders_image_resolution() {
		$system_log = Qode_Optimizer_Log::get_instance();

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'file_basename', wp_basename( $this->file ) );
		$output->set_param( 'success', false );
		$output->set_param( 'result', 'Failed' );

		$system_log->add_log( '* ISSUE RESOLUTION OF THE FOLDERS IMAGE', true );

		$system_log->add_log( 'Start the process of issue resolution of the folders image' );

		$this->remove_webp();

		static::folders_image_procedure_after_resolution( $this->file );

		$output->set_param( 'success', true );
		$output->set_param( 'result', 'Successful' );

		$system_log->add_log( 'Finish the process of issue resolution of the folders image' );

		return $output;
	}

	/**
	 * Removes backup and modifications for main image and its thumbs
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function image_and_thumbs_remove_backup_and_modifications() {
		global $wpdb;

		$qo_db = new Qode_Optimizer_Db();

		$qo_db->init_charset();

		$already_modified_info = $qo_db->get_already_modified_info( $this->id );

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'success', true );

		$filesystem = new Qode_Optimizer_Filesystem();

		// Backup removal.
		foreach ( $already_modified_info['backup'] as $backup_row ) {
			$backup_paths = maybe_unserialize( $backup_row['backup_paths'] );
			foreach ( $backup_paths as $path ) {
				$filesystem->delete_file( $path );
			}
		}

		/**
		 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
		 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
		 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
		 */
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $qo_db->get_backup_table() . ' WHERE attachment_id = %d', $this->id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Modifications removal.

		/**
		 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
		 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
		 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
		 */
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $qo_db->get_modifications_table() . ' WHERE attachment_id = %d', $this->id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $output;
	}

	/**
	 * Get all image thumb info
	 *
	 * @return array
	 */
	public function get_all_image_thumb_info() {
		$thumb_info = array();

		if (
			is_array( $this->metadata ) &&
			! empty( $this->metadata ) &&
			array_key_exists( 'sizes', $this->metadata )
		) {
			$thumb_info = (array) $this->metadata['sizes'];
		}

		return $thumb_info;
	}

	/**
	 * Get all image thumb paths info
	 *
	 * @param string $procedure Procedure which thumbs info is getting fetched for
	 *
	 * @return array
	 */
	public function get_all_image_thumb_path_info( $procedure = 'optimization' ) {
		if (
			! is_string( $procedure ) ||
			! in_array( $procedure, array( 'optimization', 'creation' ), true )
		) {
			$procedure = 'optimization';
		}

		$thumb_paths = array();

		$thumb_info = $this->get_all_image_thumb_info();
		if ( ! empty( $thumb_info ) ) {
			foreach ( $thumb_info as $size => $info ) {
				$thumb_path                  = pathinfo( $info['file'] );
				$thumb_path['dirname']       = pathinfo( $this->file, PATHINFO_DIRNAME );
				$thumb_path['full_path']     = $thumb_path['dirname'] . DIRECTORY_SEPARATOR . $thumb_path['basename'];
				$thumb_path['media_size']    = $size;
				$thumb_path['attachment_id'] = $this->id;
				$thumb_paths[]               = $thumb_path;

			}
		} else {
			$image_size_info = static::get_all_image_thumb_size_info();
			foreach ( $image_size_info as $size => $info ) {
				$thumb_path                  = array(
					'dirname'   => pathinfo( $this->file, PATHINFO_DIRNAME ),
					'filename'  => pathinfo( $this->file, PATHINFO_FILENAME ) . '-' . $info['width'] . 'x' . $info['height'],
					'extension' => pathinfo( $this->file, PATHINFO_EXTENSION ),
				);
				$thumb_path['basename']      = $thumb_path['filename'] . '.' . $thumb_path['extension'];
				$thumb_path['full_path']     = $thumb_path['dirname'] . DIRECTORY_SEPARATOR . $thumb_path['basename'];
				$thumb_path['media_size']    = $size;
				$thumb_path['attachment_id'] = $this->id;
				$thumb_paths[]               = $thumb_path;
			}
		}

		// Add SCALED thumb info, in case of very large uploaded image.
		$thumb_path                  = array(
			'dirname'   => pathinfo( $this->file, PATHINFO_DIRNAME ),
			'filename'  => pathinfo( $this->file, PATHINFO_FILENAME ) . '-scaled',
			'extension' => pathinfo( $this->file, PATHINFO_EXTENSION ),
		);
		$thumb_path['basename']      = $thumb_path['filename'] . '.' . $thumb_path['extension'];
		$thumb_path['full_path']     = $thumb_path['dirname'] . DIRECTORY_SEPARATOR . $thumb_path['basename'];
		$thumb_path['media_size']    = 'scaled';
		$thumb_path['attachment_id'] = $this->id;
		$thumb_paths[]               = $thumb_path;

		// Remove disabled image sizes.
		switch ( $procedure ) {
			case 'optimization':
				$disabled_sizes = Qode_Optimizer_Options::get_option( 'disable_image_optimization' );
				break;
			case 'creation':
				$disabled_sizes = Qode_Optimizer_Options::get_option( 'disable_image_creation' );
				break;
			default:
				$disabled_sizes = array();
				break;
		}

		if ( ! is_array( $disabled_sizes ) ) {
			$disabled_sizes = array();
		}

		$thumb_paths = array_filter(
			$thumb_paths,
			function ( $item ) use ( $disabled_sizes ) {
				return ! in_array( $item['media_size'], $disabled_sizes, true );
			}
		);

		return $thumb_paths;
	}

	/**
	 * Get all thumb sizes
	 *
	 * @return array
	 */
	public static function get_all_thumb_sizes() {
		$all_thumb_sizes = array();

		$image_size_info = static::get_all_image_thumb_size_info();
		foreach ( $image_size_info as $size => $info ) {
			$all_thumb_sizes[ $size ] = esc_html( $size . ' [ ' . $info['width'] . 'x' . $info['height'] . 'px ]' );
		}

		return $all_thumb_sizes;
	}

	/**
	 *  Get all image thumb size info
	 *
	 * @return array
	 */
	public static function get_all_image_thumb_size_info() {
		$all_image_sizes        = get_intermediate_image_sizes();
		$additional_image_sizes = wp_get_additional_image_sizes();

		$size_params = array();

		foreach ( $all_image_sizes as $image_size ) {
			if ( in_array( $image_size, static::DEFAULT_THUMB_IMAGE_SIZES, true ) ) {
				// Standard WP image sizes.
				$size_params[ $image_size ] = array(
					'width'  => get_option( $image_size . '_size_w' ),
					'height' => get_option( $image_size . '_size_h' ),
					'crop'   => get_option( $image_size . '_crop' ),
				);
				if ( 'medium_large' === $image_size ) {
					if ( 0 === intval( $size_params[ $image_size ]['width'] ) ) {
						$size_params[ $image_size ]['width'] = '768';
					}
					if ( 0 === intval( $size_params[ $image_size ]['height'] ) ) {
						$size_params[ $image_size ]['height'] = '9999';
					}
				}
			} elseif ( isset( $additional_image_sizes[ $image_size ] ) ) {
				// Additional WP image sizes.
				$size_params[ $image_size ] = array(
					'width'  => $additional_image_sizes[ $image_size ]['width'],
					'height' => $additional_image_sizes[ $image_size ]['height'],
					'crop'   => $additional_image_sizes[ $image_size ]['crop'],
				);
			}
		}

		return $size_params;
	}

	/**
	 * Create image and thumbs backup
	 *
	 * @param bool $recreate
	 */
	protected function create_image_and_thumbs_backup( $recreate = false ) {
		$backup = new Qode_Optimizer_Backup();

		if (
			is_bool( $recreate ) &&
			$recreate
		) {
			$backup->delete_file_in_db_and_folder( $this );
		}

		$backup->backup_file_in_db_and_folder( $this );
	}

	/**
	 * Create image and thumbs backup
	 *
	 * @param bool $recreate
	 */
	protected function create_folders_image_and_thumbs_backup( $recreate = false ) {
		$backup = new Qode_Optimizer_Backup();

		if (
			is_bool( $recreate ) &&
			$recreate
		) {
			$backup->delete_folders_file_in_db_and_folder( $this );
		}

		$backup->backup_folders_file_in_db_and_folder( $this );
	}

	/**
	 * Save modifications
	 *
	 * @param string $source
	 * @param Qode_Optimizer_Output $output
	 */
	protected function save_modifications( $source, $output ) {
		$files = is_string( $source ) && in_array( $source, array( 'optimization', 'conversion' ), true ) && $output instanceof Qode_Optimizer_Output ? $output->get_param( 'files' ) : '';

		if ( $files ) {
			global $wpdb;

			$qo_db = new Qode_Optimizer_Db();

			$qo_db->init_charset();

			$filesystem = new Qode_Optimizer_Filesystem();

			// Collect info for all previous modifications.

			/**
			 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
			 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
			 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
			 */
			$modifications_query   = $wpdb->prepare( 'SELECT * FROM ' . $qo_db->get_modifications_table() . ' WHERE attachment_id = %d', $this->id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$modifications_results = $wpdb->get_results( $modifications_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$previous_modifications = array();
			if ( ! empty( $modifications_results ) ) {
				foreach ( $modifications_results as $result ) {
					if ( ! array_key_exists( $result['media_size'], $previous_modifications ) ) {
						$previous_modifications[ $result['media_size'] ] = array();
					}
					$previous_modifications[ $result['media_size'] ][] = array(
						'id'                => $result['id'],
						'current_path'      => realpath( $result['current_path'] ),
						'previous_path'     => realpath( $result['previous_path'] ),
						'result'            => $result['result'],
						'current_size'      => $result['current_size'],
						'original_size'     => $result['original_size'],
						'is_optimized'      => $result['is_optimized'],
						'is_converted'      => $result['is_converted'],
						'last_modification' => $result['last_modification'],
					);
				}
			}

			// Include recent modifications.
			foreach ( $files as $file ) {
				$current_path = $file instanceof Qode_Optimizer_Output ? $file->get_param( 'file' ) : '';

				if ( $current_path ) {
					$media_size = $file->get_param( 'media_size' );
					$new_size   = $file->get_param( 'filesize_raw' );

					switch ( $source ) {
						case 'optimization':
							$success = $file->get_param( 'success' );

							if ( array_key_exists( $media_size, $previous_modifications ) ) {
								$active_index = 0;
								if ( count( $previous_modifications[ $media_size ] ) > 1 ) {
									// Delete all logs that aren't up-to-date, leave only relevant one.
									$active_index = $this->clean_modifications_log( $previous_modifications[ $media_size ] );
								}

								$initial_size = $previous_modifications[ $media_size ][ $active_index ]['original_size'];
								$result       = $filesystem->readable_filesize_savings( $initial_size, $new_size );

								// Update existing modifications' info.
								if (
									$success &&
									$new_size < $previous_modifications[ $media_size ][ $active_index ]['current_size']
								) {
									$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
										$qo_db->get_modifications_table(),
										array(
											'result'       => $result,
											'current_size' => $new_size,
											'is_optimized' => 1,
										),
										array(
											'id' => $previous_modifications[ $media_size ][ $active_index ]['id'],
										)
									);
								}
							} else {

								$initial_size = $file->get_param( 'initial_size_raw' );
								$result       = $filesystem->readable_filesize_savings( $initial_size, $new_size );

								// Insert new modifications' info.
								$insert_data = array(
									'attachment_id' => $this->attachment_id,
									'media_size'    => $media_size,
									'current_path'  => realpath( $current_path ),
									'previous_path' => realpath( $current_path ),
									'result'        => $result,
									'current_size'  => $new_size,
									'original_size' => $initial_size,
									'is_optimized'  => $success ? 1 : 0,
								);
								$wpdb->insert( $qo_db->get_modifications_table(), $insert_data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
							}

							break;
						case 'conversion':
							$previous_path = $file->get_param( 'previous_file' );

							if ( array_key_exists( $media_size, $previous_modifications ) ) {
								$active_index = 0;
								if ( count( $previous_modifications[ $media_size ] ) > 1 ) {
									// Delete all logs that aren't up-to-date, leave only relevant one.
									$active_index = $this->clean_modifications_log( $previous_modifications[ $media_size ] );
								}

								$initial_size = $previous_modifications[ $media_size ][ $active_index ]['original_size'];
								$result       = $filesystem->readable_filesize_savings( $initial_size, $new_size );

								// Update existing modifications' info.
								if ( $new_size < $previous_modifications[ $media_size ][ $active_index ]['current_size'] ) {
									$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
										$qo_db->get_modifications_table(),
										array(
											'current_path' => realpath( $current_path ),
											'result'       => $result,
											'current_size' => $new_size,
											'is_converted' => 1,
										),
										array(
											'id' => $previous_modifications[ $media_size ][ $active_index ]['id'],
										)
									);
								}
							} else {

								$initial_size = $file->get_param( 'initial_size_raw' );
								$result       = $filesystem->readable_filesize_savings( $initial_size, $new_size );

								// Insert new modifications' info.
								$insert_data = array(
									'attachment_id' => $this->attachment_id,
									'media_size'    => $media_size,
									'current_path'  => realpath( $current_path ),
									'previous_path' => realpath( $previous_path ),
									'result'        => $result,
									'current_size'  => $new_size,
									'original_size' => $initial_size,
									'is_converted'  => 1,
								);
								$wpdb->insert( $qo_db->get_modifications_table(), $insert_data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
							}

							break;
						default:
							break;
					}
				}
			}
		}
	}

	/**
	 * Save folders modifications
	 *
	 * @param string $source
	 * @param Qode_Optimizer_Output $output
	 */
	protected function save_folders_modifications( $source, $output ) {
		$files = is_string( $source ) && in_array( $source, array( 'optimization', 'conversion' ), true ) && $output instanceof Qode_Optimizer_Output ? $output->get_param( 'files' ) : '';

		if ( $files ) {
			global $wpdb;

			$qo_db = new Qode_Optimizer_Db();

			$qo_db->init_charset();

			$filesystem = new Qode_Optimizer_Filesystem();

			// Collect info for all previous modifications.

			/**
			 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
			 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
			 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
			 */
			$modifications_query   = $wpdb->prepare( 'SELECT * FROM ' . $qo_db->get_modifications_table() . ' WHERE current_path = %s', $this->file ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$modifications_results = $wpdb->get_results( $modifications_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$previous_modifications = array();
			if ( ! empty( $modifications_results ) ) {
				foreach ( $modifications_results as $result ) {
					if ( ! array_key_exists( $result['media_size'], $previous_modifications ) ) {
						$previous_modifications[ $result['media_size'] ] = array();
					}
					$previous_modifications[ $result['media_size'] ][] = array(
						'id'                => $result['id'],
						'current_path'      => realpath( $result['current_path'] ),
						'previous_path'     => realpath( $result['previous_path'] ),
						'result'            => $result['result'],
						'current_size'      => $result['current_size'],
						'original_size'     => $result['original_size'],
						'is_optimized'      => $result['is_optimized'],
						'is_converted'      => $result['is_converted'],
						'last_modification' => $result['last_modification'],
					);
				}
			}

			// Include recent modifications.
			foreach ( $files as $file ) {
				$current_path = $file instanceof Qode_Optimizer_Output ? $file->get_param( 'file' ) : '';

				if ( $current_path ) {
					$media_size = $file->get_param( 'media_size' );
					$new_size   = $file->get_param( 'filesize_raw' );

					switch ( $source ) {
						case 'optimization':
							$success = $file->get_param( 'success' );

							if ( array_key_exists( $media_size, $previous_modifications ) ) {
								$active_index = 0;
								if ( count( $previous_modifications[ $media_size ] ) > 1 ) {
									// Delete all logs that aren't up-to-date, leave only relevant one.
									$active_index = $this->clean_modifications_log( $previous_modifications[ $media_size ] );
								}

								$initial_size = $previous_modifications[ $media_size ][ $active_index ]['original_size'];
								$result       = $filesystem->readable_filesize_savings( $initial_size, $new_size );

								// Update existing modifications' info.
								if (
									$success &&
									$new_size < $previous_modifications[ $media_size ][ $active_index ]['current_size']
								) {
									$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
										$qo_db->get_modifications_table(),
										array(
											'result'       => $result,
											'current_size' => $new_size,
											'is_optimized' => 1,
										),
										array(
											'id' => $previous_modifications[ $media_size ][ $active_index ]['id'],
										)
									);
								}
							} else {

								$initial_size = $file->get_param( 'initial_size_raw' );
								$result       = $filesystem->readable_filesize_savings( $initial_size, $new_size );

								// Insert new modifications' info.
								$insert_data = array(
									'media_size'    => $media_size,
									'current_path'  => realpath( $current_path ),
									'previous_path' => realpath( $current_path ),
									'result'        => $result,
									'current_size'  => $new_size,
									'original_size' => $initial_size,
									'is_optimized'  => $success ? 1 : 0,
								);
								$wpdb->insert( $qo_db->get_modifications_table(), $insert_data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
							}

							break;
						case 'conversion':
							$previous_path = $file->get_param( 'previous_file' );

							if ( array_key_exists( $media_size, $previous_modifications ) ) {
								$active_index = 0;
								if ( count( $previous_modifications[ $media_size ] ) > 1 ) {
									// Delete all logs that aren't up-to-date, leave only relevant one.
									$active_index = $this->clean_modifications_log( $previous_modifications[ $media_size ] );
								}

								$initial_size = $previous_modifications[ $media_size ][ $active_index ]['original_size'];
								$result       = $filesystem->readable_filesize_savings( $initial_size, $new_size );

								// Update existing modifications' info.
								if ( $new_size < $previous_modifications[ $media_size ][ $active_index ]['current_size'] ) {
									$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
										$qo_db->get_modifications_table(),
										array(
											'current_path' => realpath( $current_path ),
											'result'       => $result,
											'current_size' => $new_size,
											'is_converted' => 1,
										),
										array(
											'id' => $previous_modifications[ $media_size ][ $active_index ]['id'],
										)
									);
								}
							} else {

								$initial_size = $file->get_param( 'initial_size_raw' );
								$result       = $filesystem->readable_filesize_savings( $initial_size, $new_size );

								// Insert new modifications' info.
								$insert_data = array(
									'media_size'    => $media_size,
									'current_path'  => realpath( $current_path ),
									'previous_path' => realpath( $previous_path ),
									'result'        => $result,
									'current_size'  => $new_size,
									'original_size' => $initial_size,
									'is_converted'  => 1,
								);
								$wpdb->insert( $qo_db->get_modifications_table(), $insert_data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
							}

							break;
						default:
							break;
					}
				}
			}
		}
	}

	/**
	 * Clean Modifications log from all records that aren't up-to-date. Leave only relevant one
	 *
	 * @param array $records
	 *
	 * @return int
	 */
	public function clean_modifications_log( $records ) {
		if (
			! is_array( $records ) ||
			count( $records ) <= 1
		) {
			return 0;
		}

		return 0;
	}

	/**
	 * Procedure after image restoration
	 *
	 * @param int $id
	 */
	public static function procedure_after_restoration( $id ) {
		$id = intval( $id );

		global $wpdb;

		$qo_db = new Qode_Optimizer_Db();

		$qo_db->init_charset();

		/**
		 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
		 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
		 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
		 */
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $qo_db->get_modifications_table() . ' WHERE attachment_id = %d', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Procedure after folder image restoration
	 *
	 * @param string $file File path
	 */
	public static function folders_image_procedure_after_restoration( $file ) {
		global $wpdb;

		$qo_db = new Qode_Optimizer_Db();

		$qo_db->init_charset();

		/**
		 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
		 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
		 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
		 */
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $qo_db->get_modifications_table() . ' WHERE media_size = "folders" AND current_path = %s', $file ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Procedure after image regeneration
	 *
	 * @param int $id
	 */
	public static function procedure_after_regeneration( $id ) {
		$id = intval( $id );

		global $wpdb;

		$qo_db = new Qode_Optimizer_Db();

		$qo_db->init_charset();

		/**
		 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
		 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
		 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
		 */
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $qo_db->get_modifications_table() . ' WHERE attachment_id = %d', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( 'local' === Qode_Optimizer_Options::get_option( 'backup_method' ) ) {
			$image = Qode_Optimizer_Image_Factory::create(
				array(
					'id'         => $id,
					'media_size' => 'original',
				)
			);
			if ( $image ) {
				$image->create_image_and_thumbs_backup( true );
			}
		}
	}

	/**
	 * Procedure after image resolution
	 *
	 * @param string $file File path
	 */
	public static function folders_image_procedure_after_resolution( $file ) {
		global $wpdb;

		$qo_db = new Qode_Optimizer_Db();

		$qo_db->init_charset();

		/**
		 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
		 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
		 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
		 */
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $qo_db->get_modifications_table() . ' WHERE media_size = "folders" AND current_path = %s', $file ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( 'local' === Qode_Optimizer_Options::get_option( 'backup_method' ) ) {
			$image = Qode_Optimizer_Image_Factory::create(
				array(
					'file'       => $file,
					'media_size' => 'folders',
				)
			);
			if ( $image ) {
				$image->create_folders_image_and_thumbs_backup( true );
			}
		}
	}
}
