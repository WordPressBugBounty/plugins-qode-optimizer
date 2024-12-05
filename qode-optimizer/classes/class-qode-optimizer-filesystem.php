<?php
/**
 * Implementation of file support procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Filesystem {

	/**
	 * Filesystem instance ( WP_Filesystem_Direct )
	 */
	protected WP_Filesystem_Direct $wpfilesystem; // phpcs:ignore PHPCompatibility.Classes.NewTypedProperties.Found

	/**
	 * Qode_Optimizer_Filesystem constructor
	 */
	public function __construct() {
		// WP filesystem initialization.
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

		// Permission constants definition.
		if ( ! defined( 'FS_CHMOD_DIR' ) ) {
			define( 'FS_CHMOD_DIR', ( fileperms( ABSPATH ) & 0777 | 0755 ) );
		}

		if ( ! defined( 'FS_CHMOD_FILE' ) ) {
			define( 'FS_CHMOD_FILE', ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 ) );
		}

		// Our own filesystem instance definition.
		$this->wpfilesystem = new WP_Filesystem_Direct( '' );
	}

	/**
	 * Get filesystem instance
	 */
	public function get_wpfilesystem() {
		return $this->wpfilesystem;
	}

	/**
	 * Absolute path to url conversion
	 *
	 * @param string $file File path
	 *
	 * @return string
	 */
	public function path_to_url( $file ) {
		if (
			! empty( $file ) &&
			! empty( $_SERVER['DOCUMENT_ROOT'] )
		) {
			$folder = substr(
				str_replace( '\\', '/', realpath( dirname( $file ) ) ),
				strlen(
					str_replace( '\\', '/', realpath( sanitize_url( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) ) )
				)
			);
			return $folder . '/' . basename( $file );
		}
	}

	/**
	 * A wrapper for PHP's parse_url, prepending assumed scheme for network path
	 * URLs. PHP versions 5.4.6 and earlier do not correctly parse without scheme.
	 *
	 * @param string  $url The URL to parse.
	 * @param integer $component Retrieve specific URL component.
	 * @return bool|mixed Result of parse_url.
	 */
	public function parse_url( $url, $component = -1 ) {
		if (
			! is_string( $url ) || empty( $url ) ||
			! is_int( $component ) || $component < -1 || $component > 7
		) {
			return false;
		}

		// Double forward slash relative urls.
		if ( 0 === strpos( $url, '//' ) ) {
			$url = ( is_ssl() ? 'https:' : 'http:' ) . $url;
		}

		// No protocol and no starting forward slash urls.
		if (
			false === strpos( $url, 'http' ) &&
			'/' !== substr( $url, 0, 1 )
		) {
			$url = ( is_ssl() ? 'https://' : 'http://' ) . $url;
		}

		// Replace encoded ampersands.
		$url = str_replace( '&#038;', '&', $url );

		return wp_parse_url( $url, $component );
	}

	/**
	 * Url to absolute path conversion
	 *
	 * @param string $url Url
	 *
	 * @return string
	 */
	public function url_to_path( $url ) {
		$path = '';

		$url_info = $this->parse_url( $url );

		if (
			false !== $url_info &&
			is_array( $url_info ) &&
			Qode_Optimizer_Utility::multiple_array_keys_exist( array( 'scheme', 'host', 'path' ), $url_info )
		) {
			$path = str_replace( $url_info['scheme'] . '://' . $url_info['host'] . '/', ABSPATH, $url );
		}

		return $this->is_file( $path ) ? $path : false;
	}

	/**
	 * File extension based mime-type information, very quick
	 *
	 * @param string $file File path
	 *
	 * @return string|bool
	 */
	public function get_quick_mime_type( $file ) {
		$fileextension = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
		switch ( $fileextension ) {
			case 'jpg':
			case 'jpeg':
			case 'jpe':
				return 'image/jpeg';
			case 'png':
				return 'image/png';
			case 'gif':
				return 'image/gif';
			case 'webp':
				return 'image/webp';
			case 'pdf':
				return 'application/pdf';
			case 'svg':
				return 'image/svg+xml';
			default:
				return false;
		}
	}

	/**
	 * File content reading based mime-type information
	 *
	 * @param string $file File path
	 *
	 * @return bool|string
	 */
	public function get_mime_type( $file ) {
		$file = realpath( $file );
		if ( ! $this->is_file( $file ) ) {
			return false;
		}

		$file_contents = $this->get_wpfilesystem()->get_contents( $file );
		if ( $file_contents ) {
			// Read first 12 bytes, which equates to 24 hex characters.
			$handle = bin2hex( substr( $file_contents, 0, 12 ) );
			if (
				0 === strpos( $handle, '52494646' ) &&
				16 === strpos( $handle, '57454250' )
			) {
				return 'image/webp';
			}
			if ( 'ffd8ff' === substr( $handle, 0, 6 ) ) {
				return 'image/jpeg';
			}
			if ( '89504e470d0a1a0a' === substr( $handle, 0, 16 ) ) {
				return 'image/png';
			}
			if (
				'474946383761' === substr( $handle, 0, 12 ) ||
				'474946383961' === substr( $handle, 0, 12 )
			) {
				return 'image/gif';
			}
			if ( '25504446' === substr( $handle, 0, 8 ) ) {
				return 'application/pdf';
			}
			if ( preg_match( '/<svg/', substr( $file_contents, 0, 4096 ) ) ) {
				return 'image/svg+xml';
			}
		}

		return false;
	}

	/**
	 * File exist check
	 *
	 * @param string $file File path
	 * @param bool $local Is file locally accessed
	 *
	 * @return bool
	 */
	public function is_file( $file, $local = true ) {
		if ( true === $local ) {
			$protocols = array( '://' );
			foreach ( $protocols as $protocol ) {
				if ( false !== strpos( $file, $protocol ) ) {
					return false;
				}
			}
		}

		$file = realpath( $file );

		return $this->get_wpfilesystem()->is_file( $file );
	}

	/**
	 * Is file readable check
	 *
	 * @param string $file File path
	 *
	 * @return bool
	 */
	public function is_readable( $file ) {
		return $this->get_wpfilesystem()->is_readable( $file );
	}

	/**
	 * Is file/directory writable check
	 *
	 * @param string $path File/directory path
	 *
	 * @return bool
	 */
	public function is_writable( $path ) {
		return $this->get_wpfilesystem()->is_writable( $path );
	}

	/**
	 * File size check
	 *
	 * @param string $file File path
	 *
	 * @return bool
	 */
	public function filesize( $file ) {
		$file = realpath( $file );

		clearstatcache();

		if ( $this->is_file( $file ) ) {
			return $this->get_wpfilesystem()->size( $file );
		}

		return 0;
	}

	/**
	 * File copying
	 *
	 * @param string $source_path Original file path
	 * @param string $destination_path Destination file path
	 *
	 * @return bool
	 */
	public function copy_file( $source_path, $destination_path ) {
		return $this->get_wpfilesystem()->copy( $source_path, $destination_path, true );
	}

	/**
	 * File renaming/moving
	 *
	 * @param string $source_path Original file path
	 * @param string $destination_path Destination file path
	 *
	 * @return bool
	 */
	public function rename_file( $source_path, $destination_path ) {
		return $this->get_wpfilesystem()->move( $source_path, $destination_path, true );
	}

	/**
	 * Delete file
	 *
	 * @param string $file File path
	 * @param array $directories Directory path array
	 *
	 * @return bool
	 */
	public function delete_file( $file, $directories = array() ) {
		$file = realpath( $file );

		if (
			! is_array( $directories ) ||
			empty( $directories )
		) {
			$upload_directory  = wp_get_upload_dir();
			$local_directories = Qode_Optimizer_Options::get_option( 'optimize_additional_folders' );
			if ( ! is_array( $local_directories ) ) {
				$local_directories = array();
			}
			$demo_directory = array( QODE_OPTIMIZER_DEMO_FOLDER_PATH );
			$directories    = array_merge( array( $upload_directory['basedir'] ), $local_directories, $demo_directory );
		}

		foreach ( $directories as $directory ) {
			$directory = realpath( $directory );

			if ( false !== strpos( $file, $directory ) ) {
				return wp_delete_file_from_directory( $file, $directory );
			}
		}

		return false;
	}

	/**
	 * Reads entire file into a string.
	 *
	 * @since 2.5.0
	 *
	 * @param string $file Name of the file to read.
	 * @return string|false Read data on success, false on failure.
	 */
	public function get_contents( $file ) {
		return $this->get_wpfilesystem()->get_contents( $file );
	}

	/**
	 * Writes a string to a file.
	 *
	 * @since 2.5.0
	 *
	 * @param string    $file     Remote path to the file where to write the data.
	 * @param string    $contents The data to write.
	 * @param int|false $mode     Optional. The file permissions as octal number, usually 0644.
	 *                            Default false.
	 * @return bool True on success, false on failure.
	 */
	public function put_contents( $file, $contents, $mode = false ) {
		return $this->get_wpfilesystem()->put_contents( $file, $contents, $mode );
	}

	/**
	 * Sets the access and modification times of a file.
	 *
	 * Note: If $file doesn't exist, it will be created.
	 *
	 * @since 2.5.0
	 *
	 * @param string $file  Path to file.
	 * @param int    $time  Optional. Modified time to set for file.
	 *                      Default 0.
	 * @param int    $atime Optional. Access time to set for file.
	 *                      Default 0.
	 * @return bool True on success, false on failure.
	 */
	public function touch( $file, $time = 0, $atime = 0 ) {
		return $this->get_wpfilesystem()->touch( $file, $time, $atime );
	}

	/**
	 * Changes filesystem permissions.
	 *
	 * @since 2.5.0
	 *
	 * @param string    $file      Path to the file.
	 * @param int|false $mode      Optional. The permissions as octal number, usually 0644 for files,
	 *                             0755 for directories. Default false.
	 * @param bool      $recursive Optional. If set to true, changes file permissions recursively.
	 *                             Default false.
	 * @return bool True on success, false on failure.
	 */
	public function chmod( $file, $mode = false, $recursive = false ) {
		return $this->get_wpfilesystem()->chmod( $file, $mode, $recursive );
	}

	/**
	 * File size savings report
	 *
	 * @param int $initial_size Image original size
	 * @param int $new_size New image size
	 *
	 * @return string Saving percentage
	 */
	public function readable_filesize_savings( $initial_size, $new_size ) {
		if ( $new_size >= $initial_size ) {
			$messages = __( 'No savings', 'qode-optimizer' );
		} else {
			// Calculate how much space was saved.
			$savings     = intval( $initial_size ) - intval( $new_size );
			$savings_str = $this->readable_size_format( $savings );
			// Determine the percentage savings.
			$percent = number_format_i18n( 100 - ( 100 * ( $new_size / $initial_size ) ), 1 ) . '%';
			// Use the percentage and the savings size to output a nice message to the user.
			$messages = sprintf(
			/* translators: 1: Size of savings in bytes, kb, mb 2: Percentage savings */
				__( 'Reduced by %1$s (%2$s)', 'qode-optimizer' ),
				$percent,
				$savings_str
			);
		}

		return $messages;
	}

	/**
	 * Wrapper around size_format to remove the decimal from sizes in bytes
	 *
	 * @param int $size A filesize in bytes.
	 * @param int $precision Number of places after the decimal separator
	 *
	 * @return string Human-readable filesize
	 */
	public function readable_size_format( $size, $precision = 1 ) {
		// Convert it to human readable format.
		$size_str = size_format( $size, $precision );

		// Remove spaces and extra decimals when measurement is in bytes.
		return preg_replace( '/\.0+ B ?/', ' B', $size_str );
	}

	/**
	 * Scans a directory to obtain all file paths, fulfilling mime-type criteria
	 *
	 * @param string $directory Directory path, must be inside a root directory
	 * @param array $allowed_mime_type Allowed mime types to scan for
	 *
	 * @return array|bool
	 */
	public function scan_directory( $directory = '', $allowed_mime_type = Qode_Optimizer_Image::ALLOWED_MIME_TYPES['optimizable'] ) {
		$output_files = array();

		$directory = realpath( $directory );
		$root      = realpath( qode_optimizer_get_home_path() );
		if (
			empty( $directory ) ||
			false === strpos( $directory, $root )
		) {
			return false;
		}

		if (
			! is_array( $allowed_mime_type ) ||
			empty( $allowed_mime_type )
		) {
			$allowed_mime_type = Qode_Optimizer_Image::ALLOWED_MIME_TYPES['optimizable'];
		}

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $directory ),
			RecursiveIteratorIterator::CHILD_FIRST,
			RecursiveIteratorIterator::CATCH_GET_CHILD
		);

		foreach ( $files as $file ) {
			$file_path = $file instanceof SplFileInfo && $file->isFile() ? $file->getPathname() : '';

			if ( ! empty( $file_path ) ) {
				// Eliminate files with filenames starting with .(dot).
				if ( preg_match( '/(\/|\\\\)\./', $file_path ) ) {
					continue;
				}

				$file_mime_type = $this->get_quick_mime_type( $file_path );
				if ( in_array( $file_mime_type, $allowed_mime_type, true ) ) {
					$output_files[] = $file_path;
				}
			}
		}

		return $output_files;
	}
}
