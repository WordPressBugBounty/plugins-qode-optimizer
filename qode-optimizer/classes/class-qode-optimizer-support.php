<?php
/**
 * Implementation of support procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Support {

	/**
	 * Minimal PHP version required
	 */
	const MIN_PHP_VERSION_REQUIRED = '5.5.0';

	/**
	 * Supported tools list
	 */
	const TOOLS_SUPPORTED = array(
		'convert',
		'cwebp',
		'gif2webp',
		'gifsicle',
		'jpegoptim',
		'jpegtran',
		'optipng',
		'pngout',
		'pngquant',
	);

	/**
	 * System parameters
	 *
	 * @var array $system_params
	 */
	private static $system_params = array();

	/**
	 * Get all system params
	 *
	 * @return array
	 */
	public static function get_system_params() {
		if ( ! is_array( static::$system_params ) ) {
			return array();
		}

		return static::$system_params;
	}

	/**
	 * Get specific system param
	 *
	 * @param string $system_param
	 *
	 * @return mixed
	 */
	public static function get_system_param( $system_param ) {
		if (
			! is_string( $system_param ) ||
			'' === $system_param
		) {
			return null;
		}

		$all_system_params = static::get_system_params();
		if ( ! array_key_exists( $system_param, $all_system_params ) ) {
			return null;
		}

		return $all_system_params[ $system_param ];
	}

	/**
	 * Set specific system param
	 *
	 * @param string $system_param
	 * @param mixed $system_param_value
	 */
	public static function set_system_param( $system_param, $system_param_value ) {
		if (
			is_string( $system_param ) &&
			array_key_exists( $system_param, static::$system_params )
		) {
			static::$system_params[ $system_param ] = $system_param_value;
		}
	}

	/**
	 * Initialization
	 *
	 * @throws GmagickException
	 * @throws ImagickException
	 */
	public static function init() {
		static::$system_params = array();

		static::$system_params['operating_system']       = static::get_operating_system();
		static::$system_params['architecture']           = static::get_architecture();
		static::$system_params['php_min_version_exists'] = static::php_min_version_exists();
		static::$system_params['php_version']            = static::get_php_version();
		static::$system_params['gmagick_support_exists'] = static::gmagick_support_exists();
		static::$system_params['imagick_support_exists'] = static::imagick_support_exists();
		static::$system_params['gd_support_exists']      = static::gd_support_exists();
		static::$system_params['imagick_supports_webp']  = static::imagick_supports_webp();
		static::$system_params['gd_supports_webp']       = static::gd_supports_webp();
		static::$system_params['cl_tool_paths']          = static::get_cl_tool_paths();

		static::$system_params['tool_tests'] = static::make_tool_tests();
	}

	/**
	 * Get operating system info
	 *
	 * @return string
	 */
	public static function get_operating_system() {
		return PHP_OS;
	}

	/**
	 * Get architecture info
	 *
	 * @return string
	 */
	public static function get_architecture() {
		return function_exists( 'php_uname' ) ? php_uname( 'm' ) : '';
	}

	/**
	 * PHP min version exists
	 *
	 * @return bool
	 */
	public static function php_min_version_exists() {
		if ( version_compare( PHP_VERSION, static::MIN_PHP_VERSION_REQUIRED ) >= 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * PHP version
	 *
	 * @return string|bool
	 */
	public static function get_php_version() {
		return phpversion();
	}

	/**
	 * GMagick PNG/JPG support check
	 *
	 * @return bool
	 * @throws GmagickException On error
	 */
	public static function gmagick_support_exists() {
		if (
			extension_loaded( 'gmagick' ) &&
			class_exists( 'Gmagick' )
		) {
			$gmagick = new Gmagick();
			$formats = $gmagick->queryFormats();

			if (
				in_array( 'PNG', $formats, true ) &&
				in_array( 'JPG', $formats, true )
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * IMagick PNG/JPG support check
	 *
	 * @return bool
	 * @throws ImagickException Throws ImagickException on error
	 **/
	public static function imagick_support_exists() {
		if (
			extension_loaded( 'imagick' ) &&
			class_exists( 'Imagick' )
		) {
			$imagick = new Imagick();
			$formats = $imagick->queryFormats();

			if (
				in_array( 'PNG', $formats, true ) &&
				in_array( 'JPG', $formats, true )
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * GD extension support check
	 *
	 * @return array|bool returns version and capabilities of the installed GD library, false if GD library is not installed
	 */
	public static function gd_support_exists() {
		if ( extension_loaded( 'gd' ) ) {
			return gd_info();
		}

		return false;
	}

	/**
	 * IMagick WebP support check
	 *
	 * @return bool
	 * @throws ImagickException Throws ImagickException on error.
	 */
	public static function imagick_supports_webp() {
		if ( static::get_system_param( 'imagick_support_exists' ) ) {
			$imagick = new Imagick();
			$formats = $imagick->queryFormats();

			if ( in_array( 'WEBP', $formats, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * GD WebP support check
	 *
	 * @return bool
	 */
	public static function gd_supports_webp() {
		$gd_support = static::get_system_param( 'gd_support_exists' );

		if (
			$gd_support &&
			function_exists( 'imagewebp' ) &&
			function_exists( 'imagepalettetotruecolor' ) &&
			function_exists( 'imageistruecolor' ) &&
			function_exists( 'imagealphablending' ) &&
			function_exists( 'imagesavealpha' )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Tool support check
	 *
	 * @param array $tools List of tools
	 *
	 * @return bool
	 */
	public static function tool_support_exists( $tools = array() ) {
		$filesystem = new Qode_Optimizer_Filesystem();
		if (
			is_array( $tools ) && ! empty( $tools ) &&
			Qode_Optimizer_Utility::multiple_array_values_exist( $tools, static::TOOLS_SUPPORTED ) &&
			function_exists( 'exec' )
		) {
			foreach ( $tools as $tool ) {
				if ( ! $filesystem->is_file( QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . $tool . '.exe', 'general' ) ) {
					return false;
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * Get cl tool paths
	 *
	 * @return array
	 */
	public static function get_cl_tool_paths() {
		$cl_tool_paths = array();

		switch ( static::get_system_param( 'operating_system' ) ) {
			case 'Darwin':
				$cl_tool_paths['convert'] = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'convert-mac';
				$cl_tool_paths['cwebp']   = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'cwebp-mac';

				if ( in_array( static::get_system_param( 'architecture' ), array( 'aarch64', 'arm64' ), true ) ) {
					// arm64.
					$cl_tool_paths['gif2webp'] = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'gif2webp-mac-arm64';
				} else {
					// x86_64.
					$cl_tool_paths['gif2webp'] = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'gif2webp-mac';
				}

				$cl_tool_paths['gifsicle'] = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'gifsicle-mac';

				// This needs to be tested.
				$cl_tool_paths['jpegoptim'] = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'jpegoptim-mac';

				$cl_tool_paths['jpegtran'] = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'jpegtran-mac';
				$cl_tool_paths['optipng']  = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'optipng-mac';
				$cl_tool_paths['pngout']   = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'pngout-mac';
				$cl_tool_paths['pngquant'] = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'pngquant-mac';
				break;
			case 'FreeBSD':
			case 'Linux':
			case 'SunOS':
				$cl_tool_paths['convert'] = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'convert-linux';
				$cl_tool_paths['cwebp']   = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'cwebp-linux';

				if ( in_array( static::get_system_param( 'architecture' ), array( 'aarch64', 'arm64' ), true ) ) {
					// aarch64.
					$cl_tool_paths['gif2webp'] = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'gif2webp-linux-aarch64';
				} else {
					// x86_64.
					$cl_tool_paths['gif2webp'] = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'gif2webp-linux';
				}

				$cl_tool_paths['gifsicle']  = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'gifsicle-linux';
				$cl_tool_paths['jpegoptim'] = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'jpegoptim-linux';
				$cl_tool_paths['jpegtran']  = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'jpegtran-linux';
				$cl_tool_paths['optipng']   = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'optipng-linux';

				if ( in_array( static::get_system_param( 'architecture' ), array( 'aarch64', 'arm64' ), true ) ) {
					// aarch64.
					$cl_tool_paths['pngout'] = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'pngout-linux-aarch64';
				} elseif ( 'armv7' === static::get_system_param( 'architecture' ) ) {
					// armv7.
					$cl_tool_paths['pngout'] = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'pngout-linux-armv7';
				} elseif ( 'amd64' === static::get_system_param( 'architecture' ) ) {
					// amd64.
					$cl_tool_paths['pngout'] = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'pngout-linux-amd64';
				} else {
					// x86_64.
					$cl_tool_paths['pngout'] = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'pngout-static';
				}

				$cl_tool_paths['pngquant'] = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'pngquant-linux';
				break;
			case 'WINNT':
				$cl_tool_paths['convert']   = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'convert.exe';
				$cl_tool_paths['cwebp']     = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'cwebp.exe';
				$cl_tool_paths['gif2webp']  = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'gif2webp.exe';
				$cl_tool_paths['gifsicle']  = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'gifsicle.exe';
				$cl_tool_paths['jpegoptim'] = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . '';
				$cl_tool_paths['jpegtran']  = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'jpegtran.exe';
				$cl_tool_paths['optipng']   = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'optipng.exe';
				$cl_tool_paths['pngout']    = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'pngout.exe';
				$cl_tool_paths['pngquant']  = QODE_OPTIMIZER_TOOLS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'pngquant.exe';
				break;
			default:
				break;
		}

		$filesystem = new Qode_Optimizer_Filesystem();

		foreach ( $cl_tool_paths as $path ) {
			if ( $filesystem->is_file( $path ) ) {
				$filesystem->chmod( $path, 0755 );
			}
		}

		return $cl_tool_paths;
	}

	/**
	 * Make tool tests
	 *
	 * @param array $tools Array of tools for testing, all supported tools by default
	 *
	 * @return array
	 */
	public static function make_tool_tests( $tools = self::TOOLS_SUPPORTED ) {
		$tests = array();

		if ( ! is_array( $tools ) ) {
			$tools = array();
		}

		foreach ( $tools as $tool ) {
			if ( in_array( $tool, static::TOOLS_SUPPORTED, true ) ) {
				$tests[ $tool ] = array(
					'installed'    => false,
					'executable'   => false,
					'working_type' => false,
					'working_path' => false,
					'is_working'   => false,
				);

				if ( function_exists( 'exec' ) ) {

					$installed_tool_path  = static::get_possible_installed_tool_paths( $tool );
					$executable_tool_path = array_key_exists( $tool, static::get_system_param( 'cl_tool_paths' ) ) ?
						static::get_system_param( 'cl_tool_paths' )[ $tool ] :
						'';

					switch ( $tool ) {
						case 'convert':
							if ( static::get_system_param( 'imagick_support_exists' ) ) {
								foreach (
									array(
										'executable' => $executable_tool_path,
										'installed'  => $installed_tool_path,
									) as $tool_type => $tool_paths
								) {
									if ( ! is_array( $tool_paths ) ) {
										$tool_paths = array( $tool_paths );
									}
									foreach ( $tool_paths as $tool_path ) {
										// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
										exec( $tool_path . ' -version 2>&1', $output, $return_var );
										if (
											is_array( $output ) &&
											! empty( $output ) &&
											0 === $return_var
										) {
											static::get_tool_test_params( $tool, $tool_type, $tool_path, $tests );
										}
										unset( $output );

										if ( $tests[ $tool ][ $tool_type ] ) {
											break;
										}
									}
								}
							}

							break;

						case 'gif2webp':
							foreach (
								array(
									'executable' => $executable_tool_path,
									'installed'  => $installed_tool_path,
								) as $tool_type => $tool_paths
							) {
								if ( ! is_array( $tool_paths ) ) {
									$tool_paths = array( $tool_paths );
								}
								foreach ( $tool_paths as $tool_path ) {
									// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
									exec( $tool_path . ' -version 2>&1', $output, $return_var );
									if (
										is_array( $output ) &&
										! empty( $output ) &&
										0 === $return_var
									) {
										static::get_tool_test_params( $tool, $tool_type, $tool_path, $tests );
									}
									unset( $output );

									if ( $tests[ $tool ][ $tool_type ] ) {
										break;
									}
								}
							}

							break;

						case 'cwebp':
							foreach (
								array(
									'executable' => $executable_tool_path,
									'installed'  => $installed_tool_path,
								) as $tool_type => $tool_paths
							) {
								if ( ! is_array( $tool_paths ) ) {
									$tool_paths = array( $tool_paths );
								}
								foreach ( $tool_paths as $tool_path ) {
									// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
									exec( $tool_path . ' -version 2>&1', $output );
									if (
										is_array( $output ) &&
										! empty( $output ) &&
										preg_match( '/\d\.\d\.\d/', $output[0] )
									) {
										static::get_tool_test_params( $tool, $tool_type, $tool_path, $tests );
									}
									unset( $output );

									if ( $tests[ $tool ][ $tool_type ] ) {
										break;
									}
								}
							}

							break;

						case 'gifsicle':
							foreach (
								array(
									'executable' => $executable_tool_path,
									'installed'  => $installed_tool_path,
								) as $tool_type => $tool_paths
							) {
								if ( ! is_array( $tool_paths ) ) {
									$tool_paths = array( $tool_paths );
								}
								foreach ( $tool_paths as $tool_path ) {
									// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
									exec( $tool_path . ' --version 2>&1', $output );
									if (
										is_array( $output ) &&
										! empty( $output ) &&
										0 === strpos( $output[0], 'LCDF Gifsicle' )
									) {
										static::get_tool_test_params( $tool, $tool_type, $tool_path, $tests );
									}
									unset( $output );

									if ( $tests[ $tool ][ $tool_type ] ) {
										break;
									}
								}
							}

							break;

						case 'jpegoptim':
							foreach (
								array(
									'executable' => $executable_tool_path,
									'installed'  => $installed_tool_path,
								) as $tool_type => $tool_paths
							) {
								if ( ! is_array( $tool_paths ) ) {
									$tool_paths = array( $tool_paths );
								}
								foreach ( $tool_paths as $tool_path ) {
									// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
									exec( $tool_path . ' --version 2>&1', $output );
									if (
										is_array( $output ) &&
										! empty( $output ) &&
										0 === strpos( $output[0], 'jpegoptim' )
									) {
										static::get_tool_test_params( $tool, $tool_type, $tool_path, $tests );
									}
									unset( $output );

									if ( $tests[ $tool ][ $tool_type ] ) {
										break;
									}
								}
							}

							break;

						case 'jpegtran':
							foreach (
								array(
									'executable' => $executable_tool_path,
									'installed'  => $installed_tool_path,
								) as $tool_type => $tool_paths
							) {
								if ( ! is_array( $tool_paths ) ) {
									$tool_paths = array( $tool_paths );
								}
								foreach ( $tool_paths as $tool_path ) {
									// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
									exec( $tool_path . ' -v ' . QODE_OPTIMIZER_SAMPLES_FOLDER_PATH . DIRECTORY_SEPARATOR . 'sample.jpg 2>&1', $output );
									if ( is_array( $output ) ) {
										foreach ( $output as $item ) {
											if ( preg_match( '/Independent JPEG Group/', $item ) ) {
												static::get_tool_test_params( $tool, $tool_type, $tool_path, $tests );
												break;
											}
										}
									}
									unset( $output );

									if ( $tests[ $tool ][ $tool_type ] ) {
										break;
									}
								}
							}

							break;

						case 'optipng':
							foreach (
								array(
									'executable' => $executable_tool_path,
									'installed'  => $installed_tool_path,
								) as $tool_type => $tool_paths
							) {
								if ( ! is_array( $tool_paths ) ) {
									$tool_paths = array( $tool_paths );
								}
								foreach ( $tool_paths as $tool_path ) {
									// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
									exec( $tool_path . ' -v 2>&1', $output );
									if (
										is_array( $output ) &&
										! empty( $output ) &&
										0 === strpos( $output[0], 'OptiPNG' )
									) {
										static::get_tool_test_params( $tool, $tool_type, $tool_path, $tests );
									}
									unset( $output );

									if ( $tests[ $tool ][ $tool_type ] ) {
										break;
									}
								}
							}

							break;

						case 'pngout':
							foreach (
								array(
									'executable' => $executable_tool_path,
									'installed'  => $installed_tool_path,
								) as $tool_type => $tool_paths
							) {
								if ( ! is_array( $tool_paths ) ) {
									$tool_paths = array( $tool_paths );
								}
								foreach ( $tool_paths as $tool_path ) {
									// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
									exec( $tool_path . ' 2>&1', $output );
									if (
										is_array( $output ) &&
										! empty( $output ) &&
										0 === strpos( $output[0], 'PNGOUT' )
									) {
										static::get_tool_test_params( $tool, $tool_type, $tool_path, $tests );
									}
									unset( $output );

									if ( $tests[ $tool ][ $tool_type ] ) {
										break;
									}
								}
							}

							break;

						case 'pngquant':
							foreach (
								array(
									'executable' => $executable_tool_path,
									'installed'  => $installed_tool_path,
								) as $tool_type => $tool_paths
							) {
								if ( ! is_array( $tool_paths ) ) {
									$tool_paths = array( $tool_paths );
								}
								foreach ( $tool_paths as $tool_path ) {
									// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
									exec( $tool_path . ' -V 2>&1', $output );
									if (
										is_array( $output ) &&
										! empty( $output ) &&
										substr( $output[0], 0, 3 ) >= 2.0
									) {
										static::get_tool_test_params( $tool, $tool_type, $tool_path, $tests );
									}
									unset( $output );

									if ( $tests[ $tool ][ $tool_type ] ) {
										break;
									}
								}
							}

							break;
					}
				}
			}
		}

		return $tests;
	}

	/**
	 * Gets tool test params
	 *
	 * @param string $tool
	 * @param string $tool_type
	 * @param string $tool_path
	 * @param array $tests Global tool test params
	 */
	private static function get_tool_test_params( $tool, $tool_type, $tool_path, &$tests ) {
		if (
			in_array( $tool, static::TOOLS_SUPPORTED, true ) &&
			in_array( $tool_type, array( 'executable', 'installed' ), true )
		) {
			$tests[ $tool ][ $tool_type ] = true;
			if (
				false === $tests[ $tool ]['working_type'] &&
				false === $tests[ $tool ]['working_path'] &&
				false === $tests[ $tool ]['is_working']
			) {
				$tests[ $tool ]['working_type'] = $tool_type;
				$tests[ $tool ]['working_path'] = $tool_path;
				$tests[ $tool ]['is_working']   = true;
			}
		}
	}

	/**
	 * Get tool test
	 *
	 * @param string $tool
	 *
	 * @return bool|array
	 */
	public static function get_tool_test( $tool ) {
		if (
			! is_string( $tool ) ||
			! in_array( $tool, static::TOOLS_SUPPORTED, true ) ||
			! array_key_exists( $tool, static::get_system_param( 'tool_tests' ) )
		) {
			return false;
		}

		return static::get_system_param( 'tool_tests' )[ $tool ];
	}

	/**
	 * Get possible paths for tool which is already installed
	 *
	 * @param string $tool
	 *
	 * @return array
	 */
	public static function get_possible_installed_tool_paths( $tool ) {
		if (
			! is_string( $tool ) ||
			! in_array( $tool, static::TOOLS_SUPPORTED, true )
		) {
			return array();
		}

		if ( 'WINNT' === static::get_system_param( 'operating_system' ) ) {
			return array( $tool );
		} else {
			return array(
				$tool,
				'/usr/bin/' . $tool,
				'/usr/local/bin/' . $tool,
				'/usr/gnu/bin/' . $tool,
				'/usr/syno/bin/' . $tool,
			);
		}
	}

	/**
	 * Is tool working
	 *
	 * @param string $tool
	 *
	 * @return bool
	 */
	public static function is_tool_working( $tool ) {
		if (
			! is_string( $tool ) ||
			! in_array( $tool, static::TOOLS_SUPPORTED, true )
		) {
			return false;
		}

		$tool_test = static::get_tool_test( $tool );

		if ( $tool_test ) {
			return $tool_test['is_working'];
		}

		return false;
	}

	/**
	 * Get tool working path
	 *
	 * @param string $tool
	 *
	 * @return string
	 */
	public static function get_tool_working_path( $tool ) {
		if (
			! is_string( $tool ) ||
			! in_array( $tool, static::TOOLS_SUPPORTED, true )
		) {
			return '';
		}

		$tool_test = static::get_tool_test( $tool );

		if (
			$tool_test &&
			$tool_test['is_working']
		) {
			return $tool_test['working_path'];
		}

		return '';
	}
}
