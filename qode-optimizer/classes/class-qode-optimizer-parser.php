<?php
/**
 * Implementation of parser procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Parser {

	/**
	 * Request URI.
	 *
	 * @var string $request_uri
	 */
	public $request_uri = '';

	/**
	 * Process page
	 *
	 * @var bool $process_page
	 */
	public $process_page = true;

	/**
	 * Register (once) actions and filters for Picture WebP.
	 */
	public function __construct() {
		$this->request_uri  = add_query_arg( null, null );
		$this->process_page = $this->should_process_page( true, $this->request_uri );
	}

	/**
	 * Initialization
	 */
	public function init() {
		if ( $this->process_page ) {
			// Load front-end parser for WebP.
			add_action( 'init', array( $this, 'activate' ), 99 );
			add_filter( 'qode_optimizer_modify_page_output', array( $this, 'modify_page_output' ), 10 );
		}
	}

	/**
	 * Setup page parsing classes after theme functions.php is loaded and plugins have run init routines
	 */
	public function activate() {
		$activate_buffer = false;
		// If WebP Rewriting is enabled.
		if ( 'yes' === Qode_Optimizer_Options::get_option( 'picture_webp_rewriting' ) ) {
			$activate_buffer = true;
		}
		if ( $activate_buffer ) {
			// Start an output buffer before any output starts.
			add_action( 'template_redirect', array( $this, 'activate_buffer' ), 0 );
		}
	}

	/**
	 * Starts an output buffer and registers the callback function to do WebP replacement
	 */
	public function activate_buffer() {
		ob_start( array( $this, 'activate_buffer_callback' ) );
	}

	/**
	 * Run the page through any registered EWWW IO filters.
	 *
	 * @param string $buffer The full HTML page generated since the output buffer was started.
	 * @return string The altered buffer containing the full page with WebP images inserted.
	 */
	public function activate_buffer_callback( $buffer ) {
		return apply_filters( 'qode_optimizer_modify_page_output', $buffer );
	}

	/**
	 * Search for img elements and rewrite them with noscript elements for WebP replacement.
	 *
	 * Any img elements or elements that may be used in place of img elements by JS are checked to see
	 * if WebP derivatives exist. The element is then wrapped within a noscript element for fallback,
	 * and noscript element receives a copy of the attributes from the img along with webp replacement
	 * values for those attributes.
	 *
	 * @param string $buffer The full HTML page generated since the output buffer was started.
	 * @return string The altered buffer containing the full page with WebP images inserted.
	 */
	public function modify_page_output( $buffer ) {
		if (
			empty( $buffer ) ||
			preg_match( '/^<\?xml/', $buffer ) ||
			strpos( $buffer, 'amp-boilerplate' )
		) {
			// picture WebP disabled.
			return $buffer;
		}
		if ( $this->is_json( $buffer ) ) {
			return $buffer;
		}

		if ( ! $this->process_page ) {
			// picture WebP should not process page.
			return $buffer;
		}

		$images = $this->get_images_from_html( preg_replace( '/<(picture|noscript).*?\/\1>/s', '', $buffer ), false );
		if ( ! empty( $images[0] ) && Qode_Optimizer_Utility::is_iterable( $images[0] ) ) {
			foreach ( $images[0] as $index => $image ) {
				if ( ! $this->validate_img_tag( $image ) ) {
					continue;
				}
				$file = $images['img_url'][ $index ];
				// parsing an image: $file.
				if ( $this->validate_image_url( $file ) ) {
					// If a CDN path match was found, or .webp image existence is confirmed.
					// found a webp image or forced path.
					$srcset      = $this->get_attribute( $image, 'srcset' );
					$srcset_webp = '';
					if ( $srcset ) {
						$srcset_webp = $this->srcset_replace( $srcset );
					}
					$sizes_attr = '';
					if ( empty( $srcset_webp ) ) {
						$srcset_webp = $this->generate_url( $file );
					} else {
						$sizes = $this->get_attribute( $image, 'sizes' );
						if ( $sizes ) {
							$sizes_attr = "sizes='$sizes'";
						}
					}
					if ( empty( $srcset_webp ) || $srcset_webp === $file ) {
						continue;
					}
					$pic_img = $image;
					$this->set_attribute( $pic_img, 'data-eio', 'p', true );
					$picture_tag = "<picture><source srcset=\"$srcset_webp\" $sizes_attr type=\"image/webp\">$pic_img</picture>";
					// going to swap $image with $picture_tag.
					$buffer = str_replace( $image, $picture_tag, $buffer );
				}
			}
		}
		// Images listed as picture/source elements.
		$pictures = $this->get_picture_tags_from_html( $buffer );
		if ( Qode_Optimizer_Utility::is_iterable( $pictures ) ) {
			foreach ( $pictures as $index => $picture ) {
				if ( strpos( $picture, 'image/webp' ) ) {
					continue;
				}
				$sources = $this->get_elements_from_html( $picture, 'source' );
				if ( Qode_Optimizer_Utility::is_iterable( $sources ) ) {
					foreach ( $sources as $source ) {
						$srcset_attr_name = 'srcset';
						if ( false !== strpos( $source, 'base64,R0lGOD' ) && false !== strpos( $source, 'data-srcset=' ) ) {
							$srcset_attr_name = 'data-srcset';
						} elseif ( ! $this->get_attribute( $source, $srcset_attr_name ) && false !== strpos( $source, 'data-srcset=' ) ) {
							$srcset_attr_name = 'data-srcset';
						}
						$srcset = $this->get_attribute( $source, $srcset_attr_name );
						if ( $srcset ) {
							$srcset_webp = $this->srcset_replace( $srcset );
							if ( $srcset_webp ) {
								$source_webp = str_replace( $srcset, $srcset_webp, $source );
								$this->set_attribute( $source_webp, 'type', 'image/webp' );
								$picture = str_replace( $source, $source_webp . $source, $picture );
							}
						}
					}
					if ( $picture !== $pictures[ $index ] ) {
						$buffer = str_replace( $pictures[ $index ], $picture, $buffer );
					}
				}
			}
		}
		return $buffer;
	}

	/**
	 * Checks to see if the current page being output is an AMP page.
	 *
	 * @return bool True for an AMP endpoint, false otherwise.
	 */
	public function is_amp() {
		// Just return false if we can't properly check yet.
		if ( ! did_action( 'parse_request' ) ) {
			return false;
		}
		if ( ! did_action( 'wp' ) ) {
			return false;
		}
		global $wp_query;
		if ( ! isset( $wp_query ) || ! ( $wp_query instanceof WP_Query ) ) {
			return false;
		}

		if ( function_exists( 'amp_is_request' ) && amp_is_request() ) {
			return true;
		}
		if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
			return true;
		}
		if ( function_exists( 'ampforwp_is_amp_endpoint' ) && ampforwp_is_amp_endpoint() ) {
			return true;
		}
		return false;
	}

	/**
	 * Checks to see if the current buffer/output is a JSON-encoded string.
	 *
	 * Specifically, we are looking for JSON objects/strings, not just ANY JSON value.
	 * Thus, the check is rather "loose", only looking for {} or [] at the start/end.
	 *
	 * @param string $buffer The content to check for JSON.
	 * @return bool True for JSON, false for everything else.
	 */
	public function is_json( $buffer ) {
		if (
			(
				'{' === substr( $buffer, 0, 1 ) &&
				'}' === substr( $buffer, -1 )
			) ||
			(
				'[' === substr( $buffer, 0, 1 ) &&
				']' === substr( $buffer, -1 )
			)
		) {
			return true;
		}

		return false;
	}

	/**
	 * Check if pages should be processed, especially for things like page builders.
	 *
	 * @since 6.2.2
	 *
	 * @param boolean $should_process Whether <picture> WebP should process the page.
	 * @param string  $uri The URI of the page (no domain or scheme included).
	 * @return boolean True to process the page, false to skip.
	 */
	public function should_process_page( $should_process = true, $uri = '' ) {
		// Don't foul up the admin side of things, unless a plugin needs to.
		if (
			is_admin() &&
			/**
			 * Provide plugins a way of running <picture> WebP for images in the WordPress Admin, usually for admin-ajax.php.
			 *
			 * @param bool false Allow <picture> WebP to run on the Dashboard. Defaults to false.
			 */
			false === apply_filters( 'eio_allow_admin_picture_webp', false )
		) {
			return false;
		}
		if ( empty( $uri ) ) {
			$uri = $this->request_uri;
		}
		if ( false !== strpos( $uri, 'bricks=run' ) ) {
			return false;
		}
		if ( false !== strpos( $uri, '?brizy-edit' ) ) {
			return false;
		}
		if ( false !== strpos( $uri, '&builder=true' ) ) {
			return false;
		}
		if ( false !== strpos( $uri, 'cornerstone=' ) || false !== strpos( $uri, 'cornerstone-endpoint' ) ) {
			return false;
		}
		if ( false !== strpos( $uri, 'ct_builder=' ) ) {
			return false;
		}
		if ( false !== strpos( $uri, 'ct_render_shortcode=' ) || false !== strpos( $uri, 'action=oxy_render' ) ) {
			return false;
		}
		if ( did_action( 'cornerstone_boot_app' ) || did_action( 'cs_before_preview_frame' ) ) {
			return false;
		}
		if ( false !== strpos( $uri, 'elementor-preview=' ) ) {
			return false;
		}
		if ( false !== strpos( $uri, 'et_fb=' ) ) {
			return false;
		}
		if ( false !== strpos( $uri, 'fb-edit=' ) ) {
			return false;
		}
		if ( false !== strpos( $uri, '?fl_builder' ) ) {
			return false;
		}
		if ( false !== strpos( $uri, 'is-editor-iframe=' ) ) {
			return false;
		}
		if ( '/print/' === substr( $uri, -7 ) ) {
			return false;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}
		if ( false !== strpos( $uri, 'tatsu=' ) ) {
			return false;
		}
		if ( false !== strpos( $uri, 'tve=true' ) ) {
			return false;
		}
		if ( ! empty( $_POST['action'] ) && 'tatsu_get_concepts' === sanitize_text_field( wp_unslash( $_POST['action'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return false;
		}
		if ( is_customize_preview() ) {
			return false;
		}
		global $wp_query;
		if ( ! isset( $wp_query ) || ! ( $wp_query instanceof WP_Query ) ) {
			return $should_process;
		}
		if ( $this->is_amp() ) {
			return false;
		}
		if ( is_feed() ) {
			return false;
		}
		if ( is_preview() ) {
			return false;
		}
		if ( wp_script_is( 'twentytwenty-twentytwenty', 'enqueued' ) ) {
			return false;
		}
		return $should_process;
	}

	/**
	 * Match all images and any relevant <a> tags in a block of HTML.
	 *
	 * The hyperlinks param implies that the src attribute is required, but not the other way around.
	 *
	 * @param string $content Some HTML.
	 * @param bool   $hyperlinks Default true. Should we include encasing hyperlinks in our search.
	 * @param bool   $src_required Default true. Should we look only for images with src attributes.
	 * @return array An array of $images matches, where $images[0] is
	 *         an array of full matches, and the link_url, img_tag,
	 *         and img_url keys are arrays of those matches.
	 */
	public function get_images_from_html( $content, $hyperlinks = true, $src_required = true ) {
		$images          = array();
		$unquoted_images = array();

		$unquoted_pattern = '';
		$search_pattern   = '#(?P<img_tag><img\s[^\\\\>]*?>)#is';
		if ( $hyperlinks ) {
			$search_pattern   = '#(?:<div[^>]*?\s+?class\s*=\s*["\'](?P<div_class>[\w\s-]+?)["\'][^>]*?>\s*(?:<span[^>]*?></span>)?)?(?:<figure[^>]*?\s+?class\s*=\s*["\'](?P<figure_class>[\w\s-]+?)["\'][^>]*?>\s*)?(?:<a[^>]*?\s+?href\s*=\s*["\'](?P<link_url>[^\s]+?)["\'][^>]*?>\s*)?(?P<img_tag><img[^>]*?\s+?src\s*=\s*("|\')(?P<img_url>(?!\5)[^\\\\]+?)\5[^>]*?>){1}(?:\s*</a>)?#is';
			$unquoted_pattern = '#(?:<div[^>]*?\s+?class\s*=\s*(?P<div_class>[\w-]+?)[^>]*?>\s*(?:<span[^>]*?></span>)?)?(?:<figure[^>]*?\s+?class\s*=\s*(?P<figure_class>[\w-]+?)[^>]*?>\s*)?(?:<a[^>]*?\s+?href\s*=\s*(?P<link_url>[^"\'\\\\<>][^\s<>]+)[^>]*?>\s*)?(?P<img_tag><img[^>]*?\s+?src\s*=\s*(?P<img_url>[^"\'\\\\<>][^\s\\\\<>]+)(?:\s[^>]*?)?>){1}(?:\s*</a>)?#is';
		} elseif ( $src_required ) {
			$search_pattern   = '#(?P<img_tag><img[^>]*?\s+?src\s*=\s*("|\')(?P<img_url>(?!\2)[^\\\\]+?)\2[^>]*?>)#is';
			$unquoted_pattern = '#(?P<img_tag><img[^>]*?\s+?src\s*=\s*(?P<img_url>[^"\'\\\\<>][^\s\\\\<>]+)(?:\s[^>]*?)?>)#is';
		}
		if ( preg_match_all( $search_pattern, $content, $images ) ) {
			foreach ( $images as $key => $unused ) {
				// Simplify the output as much as possible.
				if ( is_numeric( $key ) && $key > 0 ) {
					unset( $images[ $key ] );
				}
			}
		}
		$images = array_filter( $images );
		if ( $unquoted_pattern && preg_match_all( $unquoted_pattern, $content, $unquoted_images ) ) {
			foreach ( $unquoted_images as $key => $unused ) {
				// Simplify the output as much as possible.
				if ( is_numeric( $key ) && $key > 0 ) {
					unset( $unquoted_images[ $key ] );
				}
			}
		}
		$unquoted_images = array_filter( $unquoted_images );
		if ( ! empty( $images ) && ! empty( $unquoted_images ) ) {
			$images = array_merge_recursive( $images, $unquoted_images );
			if ( ! empty( $images[0] ) && ! empty( $images[1] ) ) {
				$images[0] = array_merge( $images[0], $images[1] );
				unset( $images[1] );
			}
		} elseif ( empty( $images ) && ! empty( $unquoted_images ) ) {
			$images = $unquoted_images;
		}
		return $images;
	}

	/**
	 * Checks if the img tag is allowed to be rewritten.
	 *
	 * @param string $image The img tag.
	 * @return bool False if it flags a filter or exclusion, true otherwise.
	 */
	public function validate_img_tag( $image ) {
		// Skip inline data URIs.
		if ( false !== strpos( $image, 'data:image' ) ) {
			return false;
		}
		// Ignore 0-size Pinterest schema images.
		if ( strpos( $image, 'data-pin-description=' ) && strpos( $image, 'width="0" height="0"' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks if the path is a valid WebP image, on-disk or forced.
	 *
	 * @param string $image The image URL.
	 * @return bool True if the file exists or matches a forced path, false otherwise.
	 */
	public function validate_image_url( $image ) {
		// Cleanup the image from encoded HTML characters.
		$image = str_replace( '&#038;', '&', $image );
		$image = str_replace( '#038;', '&', $image );

		$filesystem = new Qode_Optimizer_Filesystem();

		$extension  = '';
		$image_path = $filesystem->parse_url( $image, PHP_URL_PATH );
		if ( ! is_null( $image_path ) && $image_path ) {
			$extension = strtolower( pathinfo( $image_path, PATHINFO_EXTENSION ) );
		}
		if ( $extension && 'svg' === $extension ) {
			return false;
		}
		if ( $extension && 'webp' === $extension ) {
			return false;
		}
		if ( apply_filters( 'qode_optimizer_filter_skip_webp_rewrite', false, $image ) ) {
			return false;
		}

		$path = $filesystem->url_to_path( $image );
		if ( ! empty( $path ) ) {
			$webp_path = $path . '.webp';
			return $filesystem->is_file( $webp_path );
		}

		return false;
	}

	/**
	 * Get an attribute from an HTML element.
	 *
	 * @param string $element The HTML element to parse.
	 * @param string $name The name of the attribute to search for.
	 * @return string The value of the attribute, or an empty string if not found.
	 */
	public function get_attribute( $element, $name ) {
		// Don't forget, back references cannot be used in character classes.
		if ( preg_match( '#\s' . $name . '\s*=\s*("|\')((?!\1).+?)\1#is', $element, $attr_matches ) ) {
			if ( ! empty( $attr_matches[2] ) ) {
				return $attr_matches[2];
			}
		}
		// If there were not any matches with quotes, look for unquoted attributes, no spaces or quotes allowed.
		if ( preg_match( '#\s' . $name . '\s*=\s*([^"\'][^\s>]+)#is', $element, $attr_matches ) ) {
			if ( ! empty( $attr_matches[1] ) ) {
				return $attr_matches[1];
			}
		}
		return '';
	}

	/**
	 * Set an attribute on an HTML element.
	 *
	 * @param string $element The HTML element to modify. Passed by reference.
	 * @param string $name The name of the attribute to set.
	 * @param string $value The value of the attribute to set.
	 * @param bool   $replace Default false. True to replace, false to append.
	 */
	public function set_attribute( &$element, $name, $value, $replace = false ) {
		if ( 'class' === $name ) {
			$element = preg_replace( "#\s$name\s+([^=])#", ' $1', $element );
		}
		// Remove empty attributes first.
		$element = preg_replace( "#\s$name=\"\"#", ' ', $element );
		// Remove/escape double-quotes with the encoded version, so that we can safely enclose the value in double-quotes.
		$value = str_replace( '"', '&#34;', $value );
		$value = trim( $value );
		if ( $replace ) {
			// Don't forget, back references cannot be used in character classes.
			$new_element = preg_replace( '#\s' . $name . '\s*=\s*("|\')(?!\1).*?\1#is', ' ' . $name . '="' . $value . '"', $element );
			if ( strpos( $new_element, "$name=" ) && $new_element !== $element ) {
				$element = $new_element;
				return;
			}
			// Purge un-quoted attribute patterns, so the new value can be inserted further down.
			$new_element = preg_replace( '#\s' . $name . '\s*=\s*[^"\'][^\s>]+#is', ' ', $element );
			// But if we couldn't purge the attribute, then bail out.
			if ( preg_match( '#\s' . $name . '\s*=\s*#', $new_element ) && $new_element === $element ) {
				return;
			}
			$element = $new_element;
		}
		$closing = ' />';
		if ( false === strpos( $element, '/>' ) ) {
			$closing = '>';
		}
		// This should always be true, since we escape double-quotes above.
		if ( false === strpos( $value, '"' ) ) {
			$element = rtrim( $element, $closing ) . " $name=\"$value\"$closing";
			return;
		}
		// If we get here, something is kind of weird, since double-quotes were supposed to be escaped.
		$element = rtrim( $element, $closing ) . " $name='$value'$closing";
	}

	/**
	 * Replaces images within a srcset attribute with their .webp derivatives.
	 *
	 * @param string $srcset A valid srcset attribute from an img element.
	 * @return bool|string False if no changes were made, or the new srcset if any WebP images replaced the originals.
	 */
	public function srcset_replace( $srcset ) {
		$srcset_urls = explode( ' ', $srcset );
		$found_webp  = false;
		if ( Qode_Optimizer_Utility::is_iterable( $srcset_urls ) && count( $srcset_urls ) > 1 ) {
			foreach ( $srcset_urls as $srcurl ) {
				if ( is_numeric( substr( $srcurl, 0, 1 ) ) ) {
					continue;
				}
				$trailing = ' ';
				if ( ',' === substr( $srcurl, -1 ) ) {
					$trailing = ',';
					$srcurl   = rtrim( $srcurl, ',' );
				}
				// looking for $srcurl from srcset.
				if ( $this->validate_image_url( $srcurl ) ) {
					$srcset = str_replace( $srcurl . $trailing, $this->generate_url( $srcurl ) . $trailing, $srcset );
					// replaced $srcurl in srcset.
					$found_webp = true;
				}
			}
		} elseif ( $this->validate_image_url( $srcset ) ) {
			return $this->generate_url( $srcset );
		}
		if ( $found_webp ) {
			return $srcset;
		} else {
			return false;
		}
	}

	/**
	 * Generate a WebP URL by appending .webp to the filename.
	 *
	 * @param string $url The image url.
	 * @return string The WebP version of the image url.
	 */
	public function generate_url( $url ) {
		$path_parts = explode( '?', $url );
		return $path_parts[0] . '.webp' . ( ! empty( $path_parts[1] ) && 'is-pending-load=1' !== $path_parts[1] ? '?' . $path_parts[1] : '' );
	}

	/**
	 * Match all sources wrapped in <picture> tags in a block of HTML.
	 *
	 * @param string $content Some HTML.
	 * @return array An array of $pictures matches, containing full elements with ending tags.
	 */
	public function get_picture_tags_from_html( $content ) {
		$pictures = array();
		if ( preg_match_all( '#(?:<picture[^>]*?>\s*)(?:<source[^>]*?>)+(?:.*?</picture>)?#is', $content, $pictures ) ) {
			return $pictures[0];
		}
		return array();
	}

	/**
	 * Match all elements by tag name in a block of HTML. Does not retrieve contents or closing tags.
	 *
	 * @param string $content Some HTML.
	 * @param string $tag_name The name of the elements to retrieve.
	 * @return array An array of $elements.
	 */
	public function get_elements_from_html( $content, $tag_name ) {
		if ( ! ctype_alpha( $tag_name ) ) {
			return array();
		}
		if ( preg_match_all( '#<' . $tag_name . '\s[^\\\\>]+?>#is', $content, $elements ) ) {
			return $elements[0];
		}
		return array();
	}
}
