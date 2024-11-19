<?php
/**
 * Implementation of images support procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Images {

	/**
	 * Image files
	 *
	 * @var array $files List of images ( Qode_Optimizer_Image )
	 */
	public $files = array();

	/**
	 * Create image list from files
	 *
	 * @param array $params
	 */
	public function __construct( $params ) {
		if ( ! is_array( $params ) ) {
			$params = array();
		}

		$filesystem = new Qode_Optimizer_Filesystem();

		if ( array_key_exists( 'files', $params ) ) {
			if ( ! is_array( $params['files'] ) ) {
				$params['files'] = array();
			}

			foreach ( $params['files'] as $file ) {
				if (
					array_key_exists( 'full_path', $file ) &&
					! empty( $file['full_path'] ) &&
					$filesystem->is_file( $file['full_path'] )
				) {
					$create_params = array(
						'file'                   => $file['full_path'],
						'media_size'             => array_key_exists( 'media_size', $file ) ? $file['media_size'] : '',
						'attachment_id'          => array_key_exists( 'attachment_id', $file ) ? $file['attachment_id'] : 0,
						'additional_compression' => array_key_exists( 'additional_compression', $params ) ? $params['additional_compression'] : false,
					);
					$this->files[] = Qode_Optimizer_Image_Factory::create( $create_params );
				}
			}
		}
	}

	/**
	 * Multiple image optimization
	 *
	 * @param array|bool $compression_methods_queue_override List of methods for trying to compress image with, if one fails system tries another one from the list
	 * @param bool $do_resize
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function multiple_optimize( $compression_methods_queue_override = array(), $do_resize = true ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );

		$unique_file_list = array();

		if ( ! empty( $this->files ) ) {
			foreach ( $this->files as $file ) {
				if ( $file instanceof Qode_Optimizer_Image ) {
					$single_file_output = new Qode_Optimizer_Output();
					$single_file_output->set_param( 'file', false );

					if ( $file->additional_compression ) {
						$compression_methods_queue = $file->select_additional_compression_method();
					} else {
						$compression_methods_queue = $file->select_compression_method( $compression_methods_queue_override );
					}

					try {
						if ( ! array_key_exists( $file->file, $unique_file_list ) ) {
							$single_file_output              = $file->optimize( $compression_methods_queue, $do_resize );
							$unique_file_list[ $file->file ] = clone $single_file_output;
						} elseif ( $unique_file_list[ $file->file ] instanceof Qode_Optimizer_Output ) {
							$single_file_output = clone $unique_file_list[ $file->file ];
							$single_file_output->set_param( 'media_size', $file->media_size );

							$system_log->add_log( 'File to optimize: ' . wp_basename( $file->file ) );
							$system_log->add_log( 'Optimization was skipped due to image was already optimized' );
						}
					} catch ( Exception $exception ) {
						$system_log->add_log( 'Some error occurred during a process of optimizing multiple images' );
					}

					$all_files_params   = $output->get_param( 'files' );
					$all_files_params[] = $single_file_output;
					$output->set_param( 'files', $all_files_params );
				}
			}
		}

		return $output;
	}

	/**
	 * Multiple image adding watermark
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function multiple_add_watermark() {
		$system_log = Qode_Optimizer_Log::get_instance();

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );
		$output->set_param( 'success', false );

		$unique_file_list = array();

		if ( ! empty( $this->files ) ) {
			$output->set_param( 'success', true );
			foreach ( $this->files as $file ) {
				if ( $file instanceof Qode_Optimizer_Image ) {
					$single_file_output = new Qode_Optimizer_Output();
					$single_file_output->set_param( 'file', false );

					if ( ! array_key_exists( $file->file, $unique_file_list ) ) {
						$single_file_output              = $file->add_watermark();
						$unique_file_list[ $file->file ] = clone $single_file_output;
					} elseif ( $unique_file_list[ $file->file ] instanceof Qode_Optimizer_Output ) {
						$single_file_output = clone $unique_file_list[ $file->file ];
						$single_file_output->set_param( 'media_size', $file->media_size );

						$system_log->add_log( 'File to watermark: ' . wp_basename( $file->file ) );
						$system_log->add_log( 'Watermarking was skipped due to image was already watermarked' );
					}

					$all_files_params   = $output->get_param( 'files' );
					$all_files_params[] = $single_file_output;
					$output->set_param( 'files', $all_files_params );

					if ( $output->get_param( 'success' ) && ! $single_file_output->get_param( 'success' ) ) {
						$output->set_param( 'success', false );
					}
				}
			}
		}

		return $output;
	}

	/**
	 * Multiple webP image conversion
	 *
	 * @param array $conversion_methods_queue List of methods for trying to create WebP image with, if one fails system tries another one from the list
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function multiple_create_webp( $conversion_methods_queue = array( 'imagick', 'gd' ) ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( ! is_array( $conversion_methods_queue ) ) {
			$conversion_methods_queue = array( 'imagick', 'gd' );
		}

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );
		$output->set_param( 'success', false );

		$unique_file_list = array();

		if ( ! empty( $this->files ) ) {
			$output->set_param( 'success', true );
			foreach ( $this->files as $file ) {
				if ( $file instanceof Qode_Optimizer_Image ) {
					$single_file_output = new Qode_Optimizer_Output();
					$single_file_output->set_param( 'file', false );

					try {
						if ( ! array_key_exists( $file->file, $unique_file_list ) ) {
							$single_file_output              = $file->create_webp( $conversion_methods_queue );
							$unique_file_list[ $file->file ] = clone $single_file_output;
						} elseif ( $unique_file_list[ $file->file ] instanceof Qode_Optimizer_Output ) {
							$single_file_output = clone $unique_file_list[ $file->file ];
							$single_file_output->set_param( 'media_size', $file->media_size );

							$system_log->add_log( 'WebP file to create: ' . wp_basename( $file->file ) );
							$system_log->add_log( 'Creation of WebP file was skipped due to WebP file was already created' );
						}
					} catch ( Exception $exception ) {
						$system_log->add_log( 'Some error occurred during a process of creating WebP multiple images' );
					}

					$all_files_params   = $output->get_param( 'files' );
					$all_files_params[] = $single_file_output;
					$output->set_param( 'files', $all_files_params );

					if ( $output->get_param( 'success' ) && ! $single_file_output->get_param( 'file' ) ) {
						$output->set_param( 'success', false );
					}
				}
			}
		}

		return $output;
	}

	/**
	 * Multiple webP image deletion
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function multiple_remove_webp() {
		$system_log = Qode_Optimizer_Log::get_instance();

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );
		$output->set_param( 'success', false );

		$unique_file_list = array();

		if ( ! empty( $this->files ) ) {
			$output->set_param( 'success', true );
			foreach ( $this->files as $file ) {
				if ( $file instanceof Qode_Optimizer_Image ) {

					$webp_file = $file->file . '.webp';

					if ( ! array_key_exists( $file->file, $unique_file_list ) ) {
						$single_file_output              = $file->remove_webp();
						$unique_file_list[ $file->file ] = clone $single_file_output;
					} elseif ( $unique_file_list[ $file->file ] instanceof Qode_Optimizer_Output ) {
						$single_file_output = clone $unique_file_list[ $file->file ];
						$single_file_output->set_param( 'media_size', $file->media_size );

						$system_log->add_log( 'File to remove: ' . wp_basename( $webp_file ) );
						$system_log->add_log( 'File removing was skipped due to file was already removed' );
					}

					$all_files_params   = $output->get_param( 'files' );
					$all_files_params[] = $single_file_output;
					$output->set_param( 'files', $all_files_params );

					if ( $output->get_param( 'success' ) ) {
						$output->set_param( 'success', $single_file_output->get_param( 'success' ) );
					}
				}
			}
		}

		return $output;
	}

	/**
	 * Multiple image conversion
	 *
	 * @param array $conversion_methods_queue List of methods for trying to convert image with, if one fails system tries another one from the list
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function multiple_convert( $conversion_methods_queue = array( 'gmagick', 'imagick', 'gd' ) ) {
		$system_log = Qode_Optimizer_Log::get_instance();

		if ( ! is_array( $conversion_methods_queue ) ) {
			$conversion_methods_queue = array( 'gmagick', 'imagick', 'gd' );
		}

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'files', array() );
		$output->set_param( 'success', false );

		$unique_file_list = array();

		if ( ! empty( $this->files ) ) {
			$output->set_param( 'success', true );
			foreach ( $this->files as $file ) {
				if ( $file instanceof Qode_Optimizer_Image ) {
					$single_file_output = new Qode_Optimizer_Output();
					$single_file_output->set_param( 'file', false );

					try {
						if ( ! array_key_exists( $file->file, $unique_file_list ) ) {
							$single_file_output              = $file->convert( $conversion_methods_queue );
							$unique_file_list[ $file->file ] = clone $single_file_output;
						} elseif ( $unique_file_list[ $file->file ] instanceof Qode_Optimizer_Output ) {
							$single_file_output = clone $unique_file_list[ $file->file ];
							$single_file_output->set_param( 'media_size', $file->media_size );

							$system_log->add_log( 'File to convert: ' . wp_basename( $file->file ) );
							$system_log->add_log( 'Conversion was skipped due to image was already converted' );
						}
					} catch ( Exception $exception ) {
						$system_log->add_log( 'Some error occurred during a process of converting multiple images' );
					}

					$all_files_params   = $output->get_param( 'files' );
					$all_files_params[] = $single_file_output;
					$output->set_param( 'files', $all_files_params );

					if ( $output->get_param( 'success' ) && ! $single_file_output->get_param( 'file' ) ) {
						$output->set_param( 'success', false );
					}
				}
			}
		}

		return $output;
	}

	/**
	 * Multiple image deletion
	 *
	 * @return Qode_Optimizer_Output
	 */
	public function multiple_delete() {
		$system_log = Qode_Optimizer_Log::get_instance();

		$output = new Qode_Optimizer_Output();
		$output->set_param( 'success', false );

		$unique_file_list = array();

		if ( ! empty( $this->files ) ) {
			$output->set_param( 'success', true );
			foreach ( $this->files as $file ) {
				if ( $file instanceof Qode_Optimizer_Image ) {

					if ( ! array_key_exists( $file->file, $unique_file_list ) ) {
						$single_file_output              = $file->delete();
						$unique_file_list[ $file->file ] = clone $single_file_output;
					} elseif ( $unique_file_list[ $file->file ] instanceof Qode_Optimizer_Output ) {
						$single_file_output = clone $unique_file_list[ $file->file ];
						$single_file_output->set_param( 'media_size', $file->media_size );

						$system_log->add_log( 'File to delete: ' . wp_basename( $file->file ) );
						$system_log->add_log( 'File deleting was skipped due to file was already deleted' );
					}

					if ( $output->get_param( 'success' ) ) {
						$output->set_param( 'success', $single_file_output->get_param( 'success' ) );
					}
				}
			}
		}

		return $output;
	}
}
