<?php
/**
 * Implementation of options procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Options {

	/**
	 * Options
	 *
	 * @var array $options
	 */
	protected static $options = array();

	/**
	 * Ajax options
	 *
	 * @var array $ajax_options_list
	 */
	protected static $ajax_options_list = array();

	/**
	 * Get all options
	 *
	 * @return array
	 */
	public static function get_options() {
		if ( ! is_array( static::$options ) ) {
			return array();
		}

		return static::$options;
	}

	/**
	 * Get specific option
	 *
	 * @param $option string
	 *
	 * @return mixed
	 */
	public static function get_option( $option ) {
		if (
			! is_string( $option ) ||
			'' === $option
		) {
			return null;
		}

		$all_options = static::get_options();
		if ( ! array_key_exists( $option, $all_options ) ) {
			return null;
		}

		return $all_options[ $option ];
	}

	/**
	 * Set specific option
	 *
	 * @param $option string
	 * @param $value mixed
	 */
	public static function set_option( $option, $value ) {
		if (
			is_string( $option ) &&
			'' !== $option
		) {
			static::$options[ $option ] = $value;
		}
	}

	/**
	 * Get all ajax options
	 *
	 * @return array
	 */
	public static function get_ajax_options_list() {
		if ( ! is_array( static::$ajax_options_list ) ) {
			return array();
		}

		return static::$ajax_options_list;
	}

	/**
	 * Initialization
	 */
	public static function init() {
		static::$options = array();

		// Optimization Options.
		static::$options['image_max_width']         = static::get_image_max_width();
		static::$options['image_max_height']        = static::get_image_max_height();
		static::$options['jpg_compression_method']  = static::get_jpg_compression_method();
		static::$options['jpg_compression_quality'] = static::get_jpg_compression_quality();
		static::$options['png_compression_method']  = static::get_png_compression_method();
		static::$options['png_compression_quality'] = static::get_png_compression_quality();
		static::$options['gif_compression_method']  = static::get_gif_compression_method();
		static::$options['gif_compression_quality'] = static::get_gif_compression_quality();
		static::$options['image_metadata_remove']   = static::get_image_metadata_remove();

		// WebP Options.
		static::$options['enable_webp_creation']   = static::get_enable_webp_creation();
		static::$options['webp_conversion_method'] = static::get_webp_conversion_method();
		static::$options['webp_quality']           = static::get_webp_quality();
		static::$options['insert_rewriting_rules'] = static::get_insert_rewriting_rules();
		static::$options['picture_webp_rewriting'] = static::get_picture_webp_rewriting();

		// Advanced Options.
		static::$options['backup_method']               = static::get_backup_method();
		static::$options['optimize_additional_folders'] = static::get_optimize_additional_folders();
		static::$options['optimize_exclude_images']     = static::get_optimize_exclude_images();
		static::$options['enable_system_log']           = static::get_enable_system_log();

		static::$ajax_options_list = array();

		static::$ajax_options_list[] = 'qodef_insert_rewriting_rules';

		// Ajax calls.
		if ( Qode_Optimizer_User::is_admin() ) {
			add_action( 'wp_ajax_options_action_get_convert_options', array( 'Qode_Optimizer_Options', 'get_convert_options_ajax' ) );
			add_action( 'wp_ajax_options_action_ajax_option_add_description', array( 'Qode_Optimizer_Options', 'handle_ajax_option_add_description' ) );
			add_action( 'wp_ajax_options_action_ajax_option_action', array( 'Qode_Optimizer_Options', 'handle_ajax_option_action' ) );
		}
	}

	/**
	 * Gets admin options set for conversion
	 *
	 * @return array
	 */
	public static function get_convert_options() {

		$options = array();

		foreach (
			array(
				'image/jpeg' => 'enable_jpg_to_png_conversion',
				'image/png'  => 'enable_png_to_jpg_conversion',
				'image/gif'  => 'enable_gif_to_png_conversion',
			) as $mime_type => $option
		) {
			$options[ $mime_type ] = static::get_option( $option );
		}

		return $options;
	}

	/**
	 * Ajax - Gets admin options set for conversion
	 */
	public static function get_convert_options_ajax() {

		if ( isset( $_POST ) && ! empty( $_POST ) ) {

			if (
				empty( $_POST['options']['qo_nonce'] ) ||
				! wp_verify_nonce( sanitize_key( $_POST['options']['qo_nonce'] ), 'qo-nonce' )
			) {
				if ( ! wp_doing_ajax() ) {
					wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
				}

				wp_die( wp_json_encode( array( 'error' => esc_html__( 'Access denied.', 'qode-optimizer' ) ) ) );
			}

			$options = array();

			foreach (
				array(
					'enable_jpg_to_png_conversion',
					'enable_png_to_jpg_conversion',
					'enable_gif_to_png_conversion',
				) as $mime_type => $option
			) {
				$options[ $mime_type ] = static::get_option( $option );
			}

			if ( ! empty( $options ) ) {
				qode_optimizer_get_ajax_status( 'success', esc_html__( 'Success', 'qode-optimizer' ), $options );
			} else {
				qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Fail', 'qode-optimizer' ), $options );
			}
		}
	}

	/**
	 * Ajax - Adds ajax option description (Rewriting rules option)
	 */
	public static function handle_ajax_option_add_description() {

		if ( isset( $_POST ) && ! empty( $_POST ) ) {

			if (
				empty( $_POST['options']['qo_nonce'] ) ||
				! wp_verify_nonce( sanitize_key( $_POST['options']['qo_nonce'] ), 'qode_optimizer_framework_ajax_save_nonce' )
			) {
				if ( ! wp_doing_ajax() ) {
					wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
				}

				wp_die( wp_json_encode( array( 'error' => esc_html__( 'Access denied.', 'qode-optimizer' ) ) ) );
			}

			if ( ! empty( $_POST['options']['name'] ) ) {
				$option_name = sanitize_text_field( wp_unslash( $_POST['options']['name'] ) );

				if ( ! in_array( $option_name, static::get_ajax_options_list(), true ) ) {
					qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Unknown option', 'qode-optimizer' ) );
				}

				switch ( $option_name ) {
					case 'qodef_insert_rewriting_rules':
						$web_server     = Qode_Optimizer_Web_Server_Factory::create();
						$htaccess_rules = $web_server ? $web_server->get_htaccess_rules() : '';

						if ( ! empty( $htaccess_rules ) ) {

							$output = array(
								'title'       => esc_html( $web_server::WEB_SERVER_TYPE . ' web server' ),
								'description' => $htaccess_rules,
							);

							qode_optimizer_get_ajax_status( 'success', esc_html__( 'Success', 'qode-optimizer' ), $output );
						}

						break;
				}
			}

			qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Some error occurred', 'qode-optimizer' ) );
		}
	}

	/**
	 * Ajax - Does ajax option action (Rewriting rules option)
	 */
	public static function handle_ajax_option_action() {

		if ( isset( $_POST ) && ! empty( $_POST ) ) {

			if (
				empty( $_POST['options']['qo_nonce'] ) ||
				! wp_verify_nonce( sanitize_key( $_POST['options']['qo_nonce'] ), 'qode_optimizer_framework_ajax_save_nonce' )
			) {
				if ( ! wp_doing_ajax() ) {
					wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
				}

				wp_die( wp_json_encode( array( 'error' => esc_html__( 'Access denied.', 'qode-optimizer' ) ) ) );
			}

			if (
				! empty( $_POST['options']['name'] ) &&
				! empty( $_POST['options']['value'] )
			) {
				$option_name  = sanitize_text_field( wp_unslash( $_POST['options']['name'] ) );
				$option_value = sanitize_text_field( wp_unslash( $_POST['options']['value'] ) );

				if ( ! in_array( $option_name, static::get_ajax_options_list(), true ) ) {
					qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Unknown option', 'qode-optimizer' ) );
				}

				switch ( $option_name ) {
					case 'qodef_insert_rewriting_rules':
						$web_server = Qode_Optimizer_Web_Server_Factory::create();
						if (
							! $web_server ||
							! $web_server->check_htaccess_availability()
						) {
							qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Htaccess not available', 'qode-optimizer' ) );
						}

						if ( ! in_array( $option_value, array( 'yes', 'no' ), true ) ) {
							qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Unknown option value', 'qode-optimizer' ) );
						}

						$admin_options = get_option( QODE_OPTIMIZER_OPTIONS_NAME );

						$cloudflare_protection_exists = Qode_Optimizer_Web_Server::cloudflare_protection_exists();

						$current_rules = extract_from_markers(
							$web_server->get_htaccess_path(),
							'QODE OPTIMIZER'
						);

						// Current rules exist.
						if ( ! empty( $current_rules ) ) {

							if ( 'yes' === $option_value ) {
								// NO -> YES.

								if ( ! $cloudflare_protection_exists ) {
									// No Cloudflare protection.

									if ( insert_with_markers(
										$web_server->get_htaccess_path(),
										'QODE OPTIMIZER',
										$web_server->get_htaccess_rules()
									) ) {
										$admin_options['qodef_insert_rewriting_rules'] = $option_value;
										update_option( QODE_OPTIMIZER_OPTIONS_NAME, $admin_options );

										$message = esc_html__( 'Rewriting rules have been successfully inserted', 'qode-optimizer' );
										qode_optimizer_get_ajax_status( 'success', $message );
									} else {
										qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Failed to insert rules', 'qode-optimizer' ) );
									}
								} else {
									// Cloudflare protection is on.

									if ( insert_with_markers(
										$web_server->get_htaccess_path(),
										'QODE OPTIMIZER',
										''
									) ) {
										$message = esc_html__( 'Cloudflare protection detected... rewrite rules have been removed, to avoid any possible conflicts', 'qode-optimizer' );
										qode_optimizer_get_ajax_status( 'fail', $message );
									} else {
										qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Failed to insert rules', 'qode-optimizer' ) );
									}
								}
							} else {
								// YES -> NO.

								if ( insert_with_markers(
									$web_server->get_htaccess_path(),
									'QODE OPTIMIZER',
									''
								) ) {
									$admin_options['qodef_insert_rewriting_rules'] = $option_value;
									update_option( QODE_OPTIMIZER_OPTIONS_NAME, $admin_options );

									$message = esc_html__( 'Rewriting rules have been successfully removed', 'qode-optimizer' );
									qode_optimizer_get_ajax_status( 'success', $message );
								} else {
									qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Failed to remove rules', 'qode-optimizer' ) );
								}
							}
						} else {
							// No rules exist.

							if ( 'yes' === $option_value ) {
								// NO -> YES.

								if ( ! $cloudflare_protection_exists ) {
									// No Cloudflare protection.

									if ( insert_with_markers(
										$web_server->get_htaccess_path(),
										'QODE OPTIMIZER',
										$web_server->get_htaccess_rules()
									) ) {
										$admin_options['qodef_insert_rewriting_rules'] = $option_value;
										update_option( QODE_OPTIMIZER_OPTIONS_NAME, $admin_options );

										$message = esc_html__( 'Rewriting rules have been successfully inserted', 'qode-optimizer' );
										qode_optimizer_get_ajax_status( 'success', $message );
									} else {
										qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Failed to insert rules', 'qode-optimizer' ) );
									}
								} else {
									// Cloudflare protection is on.
									$message = esc_html__( 'Cloudflare protection detected... rewrite rules have been discarded, to avoid any possible conflicts', 'qode-optimizer' );
									qode_optimizer_get_ajax_status( 'fail', $message );
								}
							} else {
								// YES -> NO.
								$admin_options['qodef_insert_rewriting_rules'] = $option_value;
								update_option( QODE_OPTIMIZER_OPTIONS_NAME, $admin_options );

								$message = esc_html__( 'There were no rewrite rules to remove', 'qode-optimizer' );
								qode_optimizer_get_ajax_status( 'success', $message );
							}
						}

						break;
				}
			}

			qode_optimizer_get_ajax_status( 'fail', esc_html__( 'Some error occurred', 'qode-optimizer' ), array( 'error' => 'general' ) );
		}
	}

	/**
	 * Optimization Options
	 */

	/**
	 * Gets image max width option (Integer)
	 *
	 * @return int
	 */
	public static function get_image_max_width() {
		$image_max_width = qode_optimizer_get_post_value_through_levels( 'qodef_image_max_width' );

		return Qode_Optimizer_Utility::correct_integer( $image_max_width, 0, 1000000, 0 );
	}

	/**
	 * Gets image max height option (Integer)
	 *
	 * @return int
	 */
	public static function get_image_max_height() {
		$image_max_height = qode_optimizer_get_post_value_through_levels( 'qodef_image_max_height' );

		return Qode_Optimizer_Utility::correct_integer( $image_max_height, 0, 1000000, 0 );
	}

	/**
	 * Gets jpg compression method option (Select)
	 *
	 * @return string
	 */
	public static function get_jpg_compression_method() {
		$compression_method = qode_optimizer_get_post_value_through_levels( 'qodef_jpg_compression_method' );

		return Qode_Optimizer_Utility::correct_select(
			$compression_method,
			array( 'none', 'lossless-clt', 'lossy-clt', 'lossy-native' ),
			'lossy-native'
		);
	}

	/**
	 * Gets jpg compression quality option (Integer)
	 *
	 * @return int
	 */
	public static function get_jpg_compression_quality() {
		$compression_quality = qode_optimizer_get_post_value_through_levels( 'qodef_jpg_compression_quality' );

		return Qode_Optimizer_Utility::correct_integer( $compression_quality );
	}

	/**
	 * Gets png compression method option (Select)
	 *
	 * @return string
	 */
	public static function get_png_compression_method() {
		$compression_method = qode_optimizer_get_post_value_through_levels( 'qodef_png_compression_method' );

		return Qode_Optimizer_Utility::correct_select(
			$compression_method,
			array( 'none', 'lossless-clt', 'lossy-clt', 'lossy-native' ),
			'lossy-native'
		);
	}

	/**
	 * Gets png compression quality option (Integer)
	 *
	 * @return int
	 */
	public static function get_png_compression_quality() {
		$compression_quality = qode_optimizer_get_post_value_through_levels( 'qodef_png_compression_quality' );

		return Qode_Optimizer_Utility::correct_integer( $compression_quality );
	}

	/**
	 * Gets gif compression method option (Select)
	 *
	 * @return string
	 */
	public static function get_gif_compression_method() {
		$compression_method = qode_optimizer_get_post_value_through_levels( 'qodef_gif_compression_method' );

		return Qode_Optimizer_Utility::correct_select(
			$compression_method,
			array( 'none', 'lossy-clt', 'lossy-native' ),
			'lossy-native'
		);
	}

	/**
	 * Gets gif compression quality option (Integer)
	 *
	 * @return int
	 */
	public static function get_gif_compression_quality() {
		$compression_quality = qode_optimizer_get_post_value_through_levels( 'qodef_gif_compression_quality' );

		return Qode_Optimizer_Utility::correct_integer( $compression_quality );
	}

	/**
	 * Gets image metadata remove option (Yes/No)
	 *
	 * @return string
	 */
	public static function get_image_metadata_remove() {
		$image_metadata_remove = qode_optimizer_get_post_value_through_levels( 'qodef_image_metadata_remove' );

		return Qode_Optimizer_Utility::correct_yesno( $image_metadata_remove );
	}

	/**
	 * WebP Options
	 */

	/**
	 * Gets enable webp creation option (Yes/No)
	 *
	 * @return string
	 */
	public static function get_enable_webp_creation() {
		$enable_webp_creation = qode_optimizer_get_post_value_through_levels( 'qodef_enable_webp_creation' );

		return Qode_Optimizer_Utility::correct_yesno( $enable_webp_creation );
	}

	/**
	 * Gets webp conversion method option (Select)
	 *
	 * @return string
	 */
	public static function get_webp_conversion_method() {
		$webp_conversion_method = qode_optimizer_get_post_value_through_levels( 'qodef_webp_conversion_method' );

		return Qode_Optimizer_Utility::correct_select(
			$webp_conversion_method,
			array( 'native', 'tools' ),
			'native'
		);
	}

	/**
	 * Gets webp quality option (Integer)
	 *
	 * @return int
	 */
	public static function get_webp_quality() {
		$webp_quality = qode_optimizer_get_post_value_through_levels( 'qodef_webp_quality' );

		return Qode_Optimizer_Utility::correct_integer( $webp_quality );
	}

	/**
	 * Gets insert rewriting rules option (Yes/No)
	 *
	 * @return string
	 */
	public static function get_insert_rewriting_rules() {
		$insert_rewriting_rules = qode_optimizer_get_post_value_through_levels( 'qodef_insert_rewriting_rules' );

		return Qode_Optimizer_Utility::correct_yesno( $insert_rewriting_rules );
	}

	/**
	 * Gets picture webp rewriting option (Yes/No)
	 *
	 * @return string
	 */
	public static function get_picture_webp_rewriting() {
		$picture_webp_rewriting = qode_optimizer_get_post_value_through_levels( 'qodef_picture_webp_rewriting' );

		return Qode_Optimizer_Utility::correct_yesno( $picture_webp_rewriting );
	}

	/**
	 * Advanced Options
	 */

	/**
	 * Gets backup method option (Select)
	 *
	 * @return string
	 */
	public static function get_backup_method() {
		$backup_method = qode_optimizer_get_post_value_through_levels( 'qodef_backup_method' );

		return Qode_Optimizer_Utility::correct_select(
			$backup_method,
			array( 'local' ),
			'local'
		);
	}

	/**
	 * Gets optimize additional folders option (Textarea)
	 *
	 * @return array
	 */
	public static function get_optimize_additional_folders() {
		$optimize_additional_folders = qode_optimizer_get_post_value_through_levels( 'qodef_optimize_additional_folders' );
		$folders_array               = '' !== $optimize_additional_folders ? explode( PHP_EOL, $optimize_additional_folders ) : array();

		if ( ! empty( $folders_array ) ) {
			$root = realpath( qode_optimizer_get_home_path() );
			return array_filter(
				$folders_array,
				function ( $item ) use ( $root ) {
					$item = realpath( $item );

					if ( $item ) {
						return ! empty( $item ) && false !== strpos( $item, $root );
					}

					return false;
				}
			);
		}

		return array();
	}

	/**
	 * Gets optimize exclude images option (Textarea)
	 *
	 * @return array
	 */
	public static function get_optimize_exclude_images() {
		$optimize_exclude_images = qode_optimizer_get_post_value_through_levels( 'qodef_optimize_exclude_images' );
		$folders_array           = '' !== $optimize_exclude_images ? explode( PHP_EOL, $optimize_exclude_images ) : array();

		if ( ! empty( $folders_array ) ) {
			$root = realpath( qode_optimizer_get_home_path() );
			return array_filter(
				$folders_array,
				function ( $item ) use ( $root ) {
					$item = realpath( $item );

					if ( $item ) {
						return ! empty( $item ) && false !== strpos( $item, $root );
					}

					return false;
				}
			);
		}

		return array();
	}

	/**
	 * Gets enable system log option (Yes/No)
	 *
	 * @return string
	 */
	public static function get_enable_system_log() {
		$enable_system_log = qode_optimizer_get_post_value_through_levels( 'qodef_enable_system_log' );

		return Qode_Optimizer_Utility::correct_yesno( $enable_system_log );
	}
}
