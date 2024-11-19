<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! function_exists( 'qode_optimizer_framework_template_part' ) ) {
	/**
	 * Echo module template part.
	 *
	 * @param string $root path of root folder to start templating from
	 * @param string $module name of the module from inc folder
	 * @param string $template full path of the template to load
	 * @param string $slug
	 * @param array $params array of parameters to pass to template
	 */
	function qode_optimizer_framework_template_part( $root, $module, $template, $slug = '', $params = array() ) {
		$module_template_part = qode_optimizer_framework_get_template_part( $root, $module, $template, $slug, $params );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo qode_optimizer_framework_wp_kses_html( 'html', $module_template_part );
	}
}

if ( ! function_exists( 'qode_optimizer_framework_get_template_part' ) ) {
	/**
	 * Loads module template part.
	 *
	 * @param string $root path of root folder to start templating from
	 * @param string $module name of the module from inc folder
	 * @param string $template full path of the template to load
	 * @param string $slug
	 * @param array $params array of parameters to pass to template
	 *
	 * @return string - string containing html of template
	 */
	function qode_optimizer_framework_get_template_part( $root, $module, $template, $slug = '', $params = array() ) {
		$available_characters = '/[^A-Za-z0-9\_\-\/]/';

		if ( is_scalar( $module ) ) {
			$module = preg_replace( $available_characters, '', $module );
		} else {
			$module = '';
		}

		if ( is_scalar( $template ) ) {
			$template = preg_replace( $available_characters, '', $template );
		} else {
			$template = '';
		}

		$temp = $root . '/' . $module . '/' . $template;

		$template = qode_optimizer_framework_get_template_with_slug( $temp, $slug );

		return qode_optimizer_framework_execute_template_with_params( $template, $params );
	}
}

if ( ! function_exists( 'qode_optimizer_framework_get_template_with_slug' ) ) {
	/**
	 * Loads module template part.
	 *
	 * @param string $temp temp path to file that is being loaded
	 * @param string $slug slug that should be checked if exists
	 *
	 * @return string - string with template path
	 */
	function qode_optimizer_framework_get_template_with_slug( $temp, $slug ) {
		$template = '';

		if ( ! empty( $temp ) ) {
			if ( ! empty( $slug ) ) {
				$template = "{$temp}-{$slug}.php";

				if ( ! file_exists( $template ) ) {
					$template = $temp . '.php';
				}
			} else {
				$template = $temp . '.php';
			}
		}

		return $template;
	}
}

if ( ! function_exists( 'qode_optimizer_framework_execute_template_with_params' ) ) {
	/**
	 * Loads module template part.
	 *
	 * @param string $template path to template that is going to be included
	 * @param array $params params that are passed to template
	 *
	 * @return string - template html
	 */
	function qode_optimizer_framework_execute_template_with_params( $template, $params ) {
		if ( ! empty( $template ) && file_exists( $template ) ) {
			// Extract params so they could be used in template.
			if ( is_array( $params ) && count( $params ) ) {
				extract( $params, EXTR_SKIP ); // @codingStandardsIgnoreLine
			}

			ob_start();
			include( $template );
			$html = ob_get_clean();

			return $html;
		} else {
			return '';
		}
	}
}

if ( ! function_exists( 'qode_optimizer_framework_svg_icon' ) ) {
	/**
	 * Function that echo svg html icon
	 *
	 * @param string $name - icon name
	 * @param string $class_name - custom html tag class name
	 */
	function qode_optimizer_framework_svg_icon( $name, $class_name = '' ) {
		$svg_template_part = qode_optimizer_framework_get_svg_icon( $name, $class_name );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo qode_optimizer_framework_wp_kses_html( 'html', $svg_template_part );
	}
}

if ( ! function_exists( 'qode_optimizer_framework_get_svg_icon' ) ) {
	/**
	 * Returns svg html
	 *
	 * @param string $name - icon name
	 * @param string $class_name - custom html tag class name
	 *
	 * @return string - which contains html content
	 */
	function qode_optimizer_framework_get_svg_icon( $name, $class_name = '' ) {
		$html  = '';
		$class = isset( $class_name ) && ! empty( $class_name ) ? $class_name : '';

		switch ( $name ) {
			case 'expand':
				$html = '<svg class="' . esc_attr( $class ) . '" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="16.425" height="16.425" viewBox="0 0 16.425 16.425" xml:space="preserve"><path d="M102.059-849.728l-3.249-3.249.774-.774,1.94,1.94v-5.6H95.934l1.886,1.887-.779.773-3.194-3.194,3.2-3.2.774.774-1.9,1.9h5.595v-5.6l-1.886,1.886-.774-.774,3.194-3.194,3.194,3.194-.774.774-1.886-1.886v5.6h5.591l-1.886-1.887.779-.773,3.194,3.194-3.194,3.194-.774-.774,1.886-1.886h-5.595v5.591l1.936-1.94.779.779Z" transform="translate(-93.847 866.153)"/></svg>';
				break;
			case 'trash':
				$html = '<svg class="' . esc_attr( $class ) . '" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="14.593" height="16.426" viewBox="0 0 14.593 16.426" xml:space="preserve"><path d="M182.505-798.957a1.464,1.464,0,0,1-1.073-.448,1.465,1.465,0,0,1-.448-1.073v-11.94H180v-1.222h4.233v-1.742h6.126v1.742h4.233v1.222h-.983v11.94a1.465,1.465,0,0,1-.447,1.075,1.465,1.465,0,0,1-1.074.447Zm9.883-13.462H182.206v11.94a.286.286,0,0,0,.087.215.294.294,0,0,0,.212.084h9.584a.286.286,0,0,0,.206-.094.286.286,0,0,0,.094-.206ZM184.854-802.2h1.222v-8.22h-1.222Zm3.664,0h1.222v-8.22h-1.222Zm-6.312-10.222v0Z" transform="translate(-180.001 815.383)"/></svg>';
				break;
			case 'search':
				$html = '<svg class="' . esc_attr( $class ) . '" xmlns="http://www.w3.org/2000/svg" width="13.252" height="13.252" viewBox="0 0 13.252 13.252"><path d="M163.767-797.186l-3.68-3.68a4.547,4.547,0,0,1-1.425.817,4.763,4.763,0,0,1-1.619.289,4.572,4.572,0,0,1-3.36-1.374,4.567,4.567,0,0,1-1.375-3.358,4.577,4.577,0,0,1,1.374-3.361,4.564,4.564,0,0,1,3.358-1.377,4.576,4.576,0,0,1,3.361,1.375,4.568,4.568,0,0,1,1.377,3.36,4.668,4.668,0,0,1-.305,1.667,4.529,4.529,0,0,1-.8,1.378l3.68,3.68Zm-6.725-3.4a3.776,3.776,0,0,0,2.777-1.132,3.776,3.776,0,0,0,1.132-2.777,3.776,3.776,0,0,0-1.132-2.777,3.776,3.776,0,0,0-2.777-1.132,3.776,3.776,0,0,0-2.777,1.132,3.776,3.776,0,0,0-1.132,2.777,3.776,3.776,0,0,0,1.132,2.777A3.776,3.776,0,0,0,157.042-800.587Z" transform="translate(-151.807 809.731)"></path></svg>';
				break;
			case 'spinner':
				$html = '<svg class="' . esc_attr( $class ) . '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M304 48c0 26.51-21.49 48-48 48s-48-21.49-48-48 21.49-48 48-48 48 21.49 48 48zm-48 368c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.49-48-48-48zm208-208c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.49-48-48-48zM96 256c0-26.51-21.49-48-48-48S0 229.49 0 256s21.49 48 48 48 48-21.49 48-48zm12.922 99.078c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48c0-26.509-21.491-48-48-48zm294.156 0c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48c0-26.509-21.49-48-48-48zM108.922 60.922c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.491-48-48-48z"></path></svg>';
				break;
			case 'opener':
				$html = '<svg class="' . esc_attr( $class ) . '" xmlns="http://www.w3.org/2000/svg" width="25" height="16" viewBox="0 0 25 16"><g transform="translate(-24 -73)"><rect width="25" height="2" transform="translate(24 80)"/><rect width="25" height="2" transform="translate(24 73)"/><rect width="25" height="2" transform="translate(24 87)"/></g></svg>';
				break;
		}

		return $html;
	}
}

if ( ! function_exists( 'qode_optimizer_framework_wp_kses_html' ) ) {
	/**
	 * Function that does escape of specific html.
	 * It uses wp_kses function with predefined attributes array.
	 *
	 * @see wp_kses()
	 *
	 * @param string $type - type of html element
	 * @param string $content - string to escape
	 *
	 * @return string escaped output
	 */
	function qode_optimizer_framework_wp_kses_html( $type, $content ) {
		switch ( $type ) {
			case 'description':
				$atts = array(
					'code' => apply_filters(
						'qode_optimizer_filter_framework_wp_kses_description_atts',
						array()
					),
				);
				break;
			case 'img':
				$atts = array(
					'img' => apply_filters(
						'qode_optimizer_filter_framework_wp_kses_img_atts',
						array(
							'itemprop' => true,
							'id'       => true,
							'class'    => true,
							'width'    => true,
							'height'   => true,
							'src'      => true,
							'srcset'   => true,
							'sizes'    => true,
							'alt'      => true,
							'title'    => true,
						)
					),
				);
				break;
			case 'svg':
				$atts = apply_filters(
					'qode_optimizer_filter_framework_wp_kses_svg_atts',
					array(
						'svg'      => array(
							'xmlns'             => true,
							'version'           => true,
							'id'                => true,
							'class'             => true,
							'x'                 => true,
							'y'                 => true,
							'aria-hidden'       => true,
							'aria-labelledby'   => true,
							'role'              => true,
							'width'             => true,
							'height'            => true,
							'viewbox'           => true,
							'enable-background' => true,
							'focusable'         => true,
							'data-prefix'       => true,
							'data-icon'         => true,
						),
						'g'        => array(
							'stroke'       => true,
							'stroke-width' => true,
							'fill'         => true,
							'fill-opacity' => true,
							'transform'    => true,
						),
						'rect'     => array(
							'x'            => true,
							'y'            => true,
							'width'        => true,
							'height'       => true,
							'stroke'       => true,
							'stroke-width' => true,
							'fill'         => true,
							'fill-opacity' => true,
							'transform'    => true,
							'rx'           => true,
							'ry'           => true,
						),
						'title'    => array(
							'title' => true,
							'class' => true,
							'style' => true,
						),
						'path'     => array(
							'd'            => true,
							'stroke'       => true,
							'stroke-width' => true,
							'fill'         => true,
							'fill-opacity' => true,
							'transform'    => true,
							'pathlength'   => true,
						),
						'polygon'  => array(
							'points'    => true,
							'transform' => true,
						),
						'line'     => array(
							'x1'           => true,
							'x2'           => true,
							'y1'           => true,
							'y2'           => true,
							'stroke'       => true,
							'stroke-width' => true,
							'transform'    => true,
						),
						'polyline' => array(
							'points'    => true,
							'stroke'    => true,
							'fill'      => true,
							'transform' => true,
						),
						'circle'   => array(
							'cx'           => true,
							'cy'           => true,
							'r'            => true,
							'stroke'       => true,
							'stroke-width' => true,
							'fill'         => true,
							'fill-opacity' => true,
							'transform'    => true,
						),
						'ellipse'  => array(
							'class'        => true,
							'cx'           => true,
							'cy'           => true,
							'rx'           => true,
							'ry'           => true,
							'stroke'       => true,
							'stroke-width' => true,
							'fill'         => true,
							'fill-opacity' => true,
							'transform'    => true,
						),
						'text'     => array(
							'x'         => true,
							'y'         => true,
							'class'     => true,
							'style'     => true,
							'transform' => true,
						),
					)
				);
				break;
			case 'content':
				$atts = apply_filters(
					'qode_optimizer_filter_framework_wp_kses_content_atts',
					array(
						'div'  => array(
							'id'    => true,
							'class' => true,
							'style' => true,
						),
						'ul'   => array(
							'class' => true,
						),
						'li'   => array(
							'class' => true,
						),
						'br'   => true,
						'h1'   => array(
							'class' => true,
							'style' => true,
						),
						'h2'   => array(
							'class' => true,
							'style' => true,
						),
						'h3'   => array(
							'class' => true,
							'style' => true,
						),
						'h4'   => array(
							'class' => true,
							'style' => true,
						),
						'h5'   => array(
							'class' => true,
							'style' => true,
						),
						'h6'   => array(
							'class' => true,
							'style' => true,
						),
						'p'    => array(
							'id'    => true,
							'class' => true,
							'style' => true,
						),
						'a'    => array(
							'itemprop' => true,
							'id'       => true,
							'class'    => true,
							'href'     => true,
							'target'   => true,
							'style'    => true,
							'rel'      => true,
							'data-rel' => true,
						),
						'span' => array(
							'id'    => true,
							'class' => true,
							'style' => true,
						),
						'i'    => array(
							'class' => true,
						),
						'img'  => array(
							'itemprop' => true,
							'id'       => true,
							'class'    => true,
							'width'    => true,
							'height'   => true,
							'src'      => true,
							'srcset'   => true,
							'sizes'    => true,
							'alt'      => true,
							'title'    => true,
						),
					)
				);
				break;
			default:
				return apply_filters( 'qode_optimizer_framework_filter_wp_kses_custom', $content, $type );
		}

		return wp_kses( $content, $atts );
	}
}

if ( ! function_exists( 'qode_optimizer_framework_get_page_id' ) ) {
	/**
	 * Function that returns current page id
	 * Additional conditional is to check if current page is any wp archive page (archive, category, tag, date etc.) and returns -1
	 *
	 * @return int
	 */
	function qode_optimizer_framework_get_page_id() {
		$page_id = get_queried_object_id();

		if ( qode_optimizer_framework_is_wp_template() ) {
			$page_id = - 1;
		}

		return apply_filters( 'qode_optimizer_filter_framework_page_id', $page_id );
	}
}

if ( ! function_exists( 'qode_optimizer_framework_is_wp_template' ) ) {
	/**
	 * Function that checks if current page default wp page
	 *
	 * @return bool
	 */
	function qode_optimizer_framework_is_wp_template() {
		return is_archive() || is_search() || is_404() || ( is_front_page() && is_home() );
	}
}

if ( ! function_exists( 'qode_optimizer_framework_get_option_value' ) ) {
	/**
	 * Function that return option value
	 *
	 * @param array|string $scope - option key from database
	 * @param string $type - option type
	 * @param string $name - option key
	 * @param string $default_value
	 * @param int $post_id
	 *
	 * @return string|mixed
	 */
	function qode_optimizer_framework_get_option_value( $scope, $type, $name, $default_value = '', $post_id = null ) {

		if ( 'admin' === $type ) {
			if ( ! empty( $scope ) ) {
				if ( is_array( $scope ) ) {
					$scope = $scope[0];
				}
				$admin_options = qode_optimizer_framework_get_framework_root()->get_admin_option( $scope );
				$value         = $admin_options->get_option_value( $name );
			}
		} elseif ( 'meta-box' === $type ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			if ( empty( $post_id ) && isset( $_GET['post'] ) && ! empty( $_GET['post'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification
				$post_id = intval( $_GET['post'] );
			}
			if ( ! empty( $post_id ) ) {
				$value = get_post_meta( $post_id, $name, true );
			}
		} elseif ( 'attachment' === $type ) {
			if ( ! empty( $post_id ) ) {
				$value = get_post_meta( $post_id, $name, true );
			}
		} elseif ( 'taxonomy' === $type ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			if ( empty( $post_id ) && isset( $_GET['tag_ID'] ) && ! empty( $_GET['tag_ID'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification
				$post_id = intval( $_GET['tag_ID'] );
			}
			if ( ! empty( $post_id ) ) {
				$value = get_term_meta( $post_id, $name, true );
			}
		} elseif ( 'product-attribute' === $type ) {
			// phpcs:ignore WordPress.Security.NonceVerification
			$id = isset( $_GET['edit'] ) ? intval( $_GET['edit'] ) : 0;
			if ( ! empty( $id ) ) {
				$name  = $name . '-' . strval( $id );
				$value = get_option( $name );
			}
		}

		$value = isset( $value ) && ( '0' === $value || ! empty( $value ) ) ? $value : $default_value;

		return apply_filters( 'qode_optimizer_filter_framework_get_option_value', $value, $name );
	}
}

if ( ! function_exists( 'qode_optimizer_framework_get_post_value_through_levels' ) ) {
	/**
	 * Function that return post item option value
	 *
	 * @param array|string $scope - option key from database
	 * @param string $name - option key
	 * @param int $post_id
	 *
	 * @return string|array
	 */
	function qode_optimizer_framework_get_post_value_through_levels( $scope, $name, $post_id = 0 ) {
		$post_id = ! empty( $post_id ) ? intval( $post_id ) : qode_optimizer_framework_get_page_id();
		$value   = '';

		$option_value = qode_optimizer_framework_get_option_value( $scope, 'admin', $name );

		if ( '0' === $option_value || ! empty( $option_value ) ) {
			$value = $option_value;
		}

		if ( - 1 !== $post_id ) {
			$meta_value = qode_optimizer_framework_get_option_value( $scope, 'meta-box', $name, '', $post_id );

			if ( '0' === $meta_value || ! empty( $meta_value ) ) {
				$value = $meta_value;
			}
		}

		$value = apply_filters( 'qode_optimizer_filter_framework_value_through_levels_' . $name, $value );

		return $value;
	}
}

if ( ! function_exists( 'qode_optimizer_framework_clean_global_options' ) ) {
	/**
	 * Functions to clean and remove unnecessary fields from global options during save process.
	 *
	 * @param array $new_options - global options to save
	 * @param array $saved_options - db global options
	 *
	 * @return array
	 */
	function qode_optimizer_framework_clean_global_options( $new_options, $saved_options ) {

		if ( ! empty( $new_options ) ) {
			$predefined_global_options = array(
				'action',
				'options_name',
			);

			foreach ( $new_options as $new_option_key => $new_option_value ) {

				if ( ! in_array( $new_option_key, array_keys( $saved_options ), true ) && ! in_array( $new_option_key, $predefined_global_options, true ) ) {
					unset( $new_options[ $new_option_key ] );
				}
			}
		}

		return (array) $new_options;
	}
}

if ( ! function_exists( 'qode_optimizer_framework_map_deep_sanitize' ) ) {
	/**
	 * Maps a function to all non-iterable elements of an array or an object.
	 *
	 * This is similar to `array_walk_recursive()` but acts upon objects too.
	 *
	 * @param mixed $value The array, object, or scalar.
	 * @param callable $callback The function to map onto $value.
	 *
	 * @return mixed The value with the callback applied to all non-arrays and non-objects inside it.
	 */
	function qode_optimizer_framework_map_deep_sanitize( $value, $callback = 'sanitize_text_field' ) {
		$keys_array_sanitize = array(
			'_html'                  => 'wp_kses_post',
			'_email_default_content' => 'wp_kses_post',
		);

		if ( is_array( $value ) ) {
			foreach ( $value as $index => $item ) {
				foreach ( $keys_array_sanitize as $key => $callback_function ) {
					if ( str_contains( $index, $key ) ) {
						$value[ $index ] = qode_optimizer_framework_map_deep_sanitize( $item, $callback_function );
						// if sanitized exit this each and continue first one.
						continue 2;
					}
				}
				$value[ $index ] = qode_optimizer_framework_map_deep_sanitize( $item );
			}
		} elseif ( is_object( $value ) ) {
			$object_vars = get_object_vars( $value );
			foreach ( $object_vars as $property_name => $property_value ) {
				foreach ( $keys_array_sanitize as $key => $callback ) {
					if ( str_contains( $property_name, $key ) ) {
						$value->$property_name = qode_optimizer_framework_map_deep_sanitize( $property_value, $callback );
						// if sanitized exit this each and continue first one.
						continue 2;
					}
				}
				$value->$property_name = qode_optimizer_framework_map_deep_sanitize( $property_value );
			}
		} else {
			$value = call_user_func( $callback, $value );
		}

		return $value;
	}
}
