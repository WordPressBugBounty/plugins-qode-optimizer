<?php

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

spl_autoload_register(
	function ( $class_name ) {
		$file_name = QODE_OPTIMIZER_CLASSES_FOLDER_PATH . '/class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

		if ( file_exists( $file_name ) ) {
			require_once( $file_name );
		} else {
			foreach ( apply_filters( 'qode_optimizer_filter_additional_plugin_classes_folders', array() ) as $class_folder ) {
				$file_name = $class_folder . '/class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

				if ( file_exists( $file_name ) ) {
					require_once( $file_name );
				}
			}
		}
	}
);

if ( ! function_exists( 'qode_optimizer_is_installed' ) ) {
	/**
	 * Function check is some plugin is installed
	 *
	 * @param string $plugin name
	 *
	 * @return bool
	 */
	function qode_optimizer_is_installed( $plugin ) {
		switch ( $plugin ) :
			case 'optimizer-premium':
				return defined( 'QODE_OPTIMIZER_PREMIUM_VERSION' );
			case 'gutenberg-page':
				$current_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : array();

				return method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor();
			case 'gutenberg-editor':
				return class_exists( 'WP_Block_Type' );
			case 'wpbakery':
				return class_exists( 'WPBakeryVisualComposerAbstract' );
			case 'elementor':
				return defined( 'ELEMENTOR_VERSION' );
			case 'revolution-slider':
				return class_exists( 'RevSliderFront' );
			case 'woocommerce':
				return class_exists( 'WooCommerce' );
			case 'contact_form_7':
				return defined( 'WPCF7_VERSION' );
			case 'wp_forms':
				return defined( 'WPFORMS_VERSION' );
			case 'wpml':
				return defined( 'ICL_SITEPRESS_VERSION' );
			default:
				return apply_filters( 'qode_optimizer_filter_is_plugin_installed', false, $plugin );

		endswitch;
	}
}

if ( ! function_exists( 'qode_optimizer_sanitize_module_template_part' ) ) {
	/**
	 * Sanitize module template part.
	 *
	 * @param string $template temp path to file that is being loaded
	 *
	 * @return string - string with template path
	 */
	function qode_optimizer_sanitize_module_template_part( $template ) {
		$available_characters = '/[^A-Za-z0-9\_\-\/]/';

		if ( ! empty( $template ) && is_scalar( $template ) ) {
			$template = preg_replace( $available_characters, '', $template );
		} else {
			$template = '';
		}

		return $template;
	}
}

if ( ! function_exists( 'qode_optimizer_get_template_with_slug' ) ) {
	/**
	 * Loads module template part.
	 *
	 * @param string $temp temp path to file that is being loaded
	 * @param string $slug slug that should be checked if exists
	 *
	 * @return string - string with template path
	 */
	function qode_optimizer_get_template_with_slug( $temp, $slug ) {
		$template = '';

		if ( ! empty( $temp ) ) {
			$slug = qode_optimizer_sanitize_module_template_part( $slug );

			if ( ! empty( $slug ) ) {
				$template = "$temp-$slug.php";

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

if ( ! function_exists( 'qode_optimizer_get_template_part' ) ) {
	/**
	 * Loads module template part.
	 *
	 * @param string $module name of the module from inc folder
	 * @param string $template full path of the template to load
	 * @param string $slug
	 * @param array $params array of parameters to pass to template
	 *
	 * @return string - string containing html of template
	 */
	function qode_optimizer_get_template_part( $module, $template, $slug = '', $params = array() ) {
		$module   = qode_optimizer_sanitize_module_template_part( $module );
		$template = qode_optimizer_sanitize_module_template_part( $template );

		$temp = QODE_OPTIMIZER_INC_PATH . '/' . $module . '/' . $template;

		$template = qode_optimizer_get_template_with_slug( $temp, $slug );

		if ( ! empty( $template ) && file_exists( $template ) ) {
			// Extract params so they could be used in template.
			if ( is_array( $params ) && count( $params ) ) {
				// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
				extract( $params, EXTR_SKIP ); // @codingStandardsIgnoreLine
			}

			ob_start();

			// nosemgrep audit.php.lang.security.file.inclusion-arg.
			include qode_optimizer_get_template_with_slug( $temp, $slug );

			$html = ob_get_clean();

			return $html;
		} else {
			return '';
		}
	}
}

if ( ! function_exists( 'qode_optimizer_template_part' ) ) {
	/**
	 * Echo module template part.
	 *
	 * @param string $module name of the module from inc folder
	 * @param string $template full path of the template to load
	 * @param string $slug
	 * @param array $params array of parameters to pass to template
	 */
	function qode_optimizer_template_part( $module, $template, $slug = '', $params = array() ) {
		$module_template = qode_optimizer_get_template_part( $module, $template, $slug, $params );

		echo do_shortcode( $module_template );
	}
}

if ( ! function_exists( 'qode_optimizer_get_option_value' ) ) {
	/**
	 * Function that returns option value using framework function but providing its own scope
	 *
	 * @param string $type option type
	 * @param string $name name of option
	 * @param string $default_value option default value
	 * @param int $post_id id of
	 *
	 * @return string value of option
	 */
	function qode_optimizer_get_option_value( $type, $name, $default_value = '', $post_id = null ) {
		$scope = QODE_OPTIMIZER_OPTIONS_NAME;

		return qode_optimizer_framework_get_option_value( $scope, $type, $name, $default_value, $post_id );
	}
}

if ( ! function_exists( 'qode_optimizer_get_post_value_through_levels' ) ) {
	/**
	 * Function that returns meta value if exists, otherwise global value using framework function but providing its own scope
	 *
	 * @param string $name name of option
	 * @param int $post_id id of
	 *
	 * @return string|array value of option
	 */
	function qode_optimizer_get_post_value_through_levels( $name, $post_id = null ) {
		$scope = QODE_OPTIMIZER_OPTIONS_NAME;

		return qode_optimizer_framework_get_post_value_through_levels( $scope, $name, $post_id );
	}
}

if ( ! function_exists( 'qode_optimizer_svg_icon' ) ) {
	/**
	 * Function that print svg html icon
	 *
	 * @param string $name - icon name
	 * @param string $class_name - custom html tag class name
	 */
	function qode_optimizer_svg_icon( $name, $class_name = '' ) {
		$svg_template_part = qode_optimizer_get_svg_icon( $name, $class_name );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo qode_optimizer_framework_wp_kses_html( 'html', $svg_template_part );
	}
}

if ( ! function_exists( 'qode_optimizer_get_svg_icon' ) ) {
	/**
	 * Returns svg html
	 *
	 * @param string $name - icon name
	 * @param string $class_name - custom html tag class name
	 *
	 * @return string
	 */
	function qode_optimizer_get_svg_icon( $name, $class_name = '' ) {
		$html  = '';
		$class = isset( $class_name ) && ! empty( $class_name ) ? 'class=' . esc_attr( $class_name ) : '';

		switch ( $name ) {
			case 'expand':
				$html = '<svg ' . esc_attr( $class ) . ' xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="92px" height="92px" viewBox="0 0 92 92" xml:space="preserve"><path d="M90,6l0,20c0,2.2-1.8,4-4,4l0,0c-2.2,0-4-1.8-4-4V15.7L58.8,38.9c-0.8,0.8-1.8,1.2-2.8,1.2c-1,0-2-0.4-2.8-1.2c-1.6-1.6-1.6-4.1,0-5.7L76.3,10H66c-2.2,0-4-1.8-4-4c0-2.2,1.8-4,4-4h20c1.1,0,2.1,0.4,2.8,1.2C89.6,3.9,90,4.9,90,6z M86,62c-2.2,0-4,1.8-4,4v10.3L59.2,53.7c-1.6-1.6-4.2-1.6-5.8,0c-1.6,1.6-1.6,4.1-0.1,5.7L75.9,82H65.6c0,0,0,0,0,0c-2.2,0-4,1.8-4,4s1.8,4,4,4l20,0l0,0c1.1,0,2.3-0.4,3-1.2c0.8-0.8,1.4-1.8,1.4-2.8V66C90,63.8,88.2,62,86,62zM32.8,53.5L10,76.3V66c0-2.2-1.8-4-4-4h0c-2.2,0-4,1.8-4,4l0,20c0,1.1,0.4,2.1,1.2,2.8C4,89.6,5,90,6.1,90h20c2.2,0,4-1.8,4-4c0-2.2-1.8-4-4-4H15.7l22.8-22.8c1.6-1.6,1.5-4.1,0-5.7C37,51.9,34.4,51.9,32.8,53.5z M15.7,10.4l10.3,0h0c2.2,0,4-1.8,4-4s-1.8-4-4-4l-20,0h0c-1.1,0-2.1,0.4-2.8,1.2C2.4,4.3,2,5.3,2,6.4l0,20c0,2.2,1.8,4,4,4c2.2,0,4-1.8,4-4V16l23.1,23.1c0.8,0.8,1.8,1.2,2.8,1.2c1,0,2-0.4,2.8-1.2c1.6-1.6,1.6-4.1,0-5.7L15.7,10.4z"/></svg>';
				break;
			case 'remove':
				$html = '<svg ' . esc_attr( $class ) . ' xmlns="http://www.w3.org/2000/svg" width="12.25" height="14" viewBox="0 0 12.25 14"><path d="M12.25,2.625v.4375a.4374.4374,0,0,1-.4375.4375H11.375v9.1875A1.3128,1.3128,0,0,1,10.0625,14H2.1875A1.3128,1.3128,0,0,1,.875,12.6875V3.5H.4375A.4374.4374,0,0,1,0,3.0625V2.625a.4374.4374,0,0,1,.4375-.4375H2.6909l.93-1.55A1.4556,1.4556,0,0,1,4.7466,0H7.5039A1.4556,1.4556,0,0,1,8.6294.6372l.93,1.55h2.2534A.4374.4374,0,0,1,12.25,2.625ZM10.0625,3.5H2.1875v9.1875h7.875Zm-6.125,7.5469V5.1406a.3282.3282,0,0,1,.3281-.3281h.6563a.3282.3282,0,0,1,.3281.3281v5.9063a.3282.3282,0,0,1-.3281.3281H4.2656A.3282.3282,0,0,1,3.9375,11.0469Zm.2842-8.8594H8.0283l-.4775-.7954a.1818.1818,0,0,0-.1406-.08H4.8394a.1818.1818,0,0,0-.1406.08ZM7,11.0469V5.1406a.3282.3282,0,0,1,.3281-.3281h.6563a.3282.3282,0,0,1,.3281.3281v5.9063a.3282.3282,0,0,1-.3281.3281H7.3281A.3282.3282,0,0,1,7,11.0469Z"/></svg>';
				break;
			case 'close':
				$html = '<svg ' . esc_attr( $class ) . ' xmlns="http://www.w3.org/2000/svg" width="18.1213" height="18.1213" viewBox="0 0 18.1213 18.1213" stroke-miterlimit="10" stroke-width="2"><line x1="1.0607" y1="1.0607" x2="17.0607" y2="17.0607"/><line x1="17.0607" y1="1.0607" x2="1.0607" y2="17.0607"/></svg>';
				break;
			case 'add':
				$html = '<svg ' . esc_attr( $class ) . ' xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14"><rect y="6" width="14" height="2"/><rect y="6" width="14" height="2" transform="translate(0 14) rotate(-90)"/></svg>';
				break;
			case 'check':
				$html = '<svg ' . esc_attr( $class ) . ' xmlns="http://www.w3.org/2000/svg" width="16.818" height="12.783" viewBox="0 0 16.818 12.783"><path d="M1,7l4.987,4L15,1" transform="translate(0.406 0.412)" stroke-linecap="round" stroke-miterlimit="10" stroke-width="2"/></svg>';
				break;
			case 'spinner':
				$html = '<svg ' . esc_attr( $class ) . ' xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M304 48c0 26.51-21.49 48-48 48s-48-21.49-48-48 21.49-48 48-48 48 21.49 48 48zm-48 368c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.49-48-48-48zm208-208c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.49-48-48-48zM96 256c0-26.51-21.49-48-48-48S0 229.49 0 256s21.49 48 48 48 48-21.49 48-48zm12.922 99.078c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48c0-26.509-21.491-48-48-48zm294.156 0c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48c0-26.509-21.49-48-48-48zM108.922 60.922c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.491-48-48-48z"></path></svg>';
				break;
			case 'share':
				$html = '<svg ' . esc_attr( $class ) . ' xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>';
				break;
		}

		return apply_filters( 'qode_optimizer_filter_svg_icon', $html, $name, $class_name );
	}
}

if ( ! function_exists( 'qode_optimizer_class_attribute' ) ) {
	/**
	 * Function that echoes class attribute
	 *
	 * @param string|array $value - value of class attribute
	 *
	 * @see qode_optimizer_get_class_attribute()
	 */
	function qode_optimizer_class_attribute( $value ) {
		echo wp_kses_post( qode_optimizer_get_class_attribute( $value ) );
	}
}

if ( ! function_exists( 'qode_optimizer_get_class_attribute' ) ) {
	/**
	 * Function that returns generated class attribute
	 *
	 * @param string|array $value - value of class attribute
	 *
	 * @return string generated class attribute
	 *
	 * @see qode_optimizer_get_inline_attr()
	 */
	function qode_optimizer_get_class_attribute( $value ) {
		return qode_optimizer_get_inline_attr( $value, 'class', ' ' );
	}
}

if ( ! function_exists( 'qode_optimizer_inline_style' ) ) {
	/**
	 * Function that echoes generated style attribute
	 *
	 * @param string|array $value - attribute value
	 *
	 * @see qode_optimizer_get_inline_style()
	 */
	function qode_optimizer_inline_style( $value ) {
		$inline_style_part = qode_optimizer_get_inline_style( $value );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo qode_optimizer_framework_wp_kses_html( 'attributes', $inline_style_part );
	}
}

if ( ! function_exists( 'qode_optimizer_get_inline_style' ) ) {
	/**
	 * Function that generates style attribute and returns generated string
	 *
	 * @param string|array $value - value of style attribute
	 *
	 * @return string generated style attribute
	 *
	 * @see qode_optimizer_get_inline_style()
	 */
	function qode_optimizer_get_inline_style( $value ) {
		return qode_optimizer_get_inline_attr( $value, 'style', ';' );
	}
}

if ( ! function_exists( 'qode_optimizer_inline_attrs' ) ) {
	/**
	 * Echo multiple inline attributes
	 *
	 * @param array $attrs
	 * @param bool $allow_zero_values
	 */
	function qode_optimizer_inline_attrs( $attrs, $allow_zero_values = false ) {
		$inline_attrs_part = qode_optimizer_get_inline_attrs( $attrs, $allow_zero_values );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo qode_optimizer_framework_wp_kses_html( 'attributes', $inline_attrs_part );
	}
}

if ( ! function_exists( 'qode_optimizer_get_inline_attrs' ) ) {
	/**
	 * Generate multiple inline attributes
	 *
	 * @param array $attrs
	 * @param bool $allow_zero_values
	 *
	 * @return string
	 */
	function qode_optimizer_get_inline_attrs( $attrs, $allow_zero_values = false ) {
		$output = '';
		if ( is_array( $attrs ) && count( $attrs ) ) {
			if ( $allow_zero_values ) {
				foreach ( $attrs as $attr => $value ) {
					$output .= ' ' . qode_optimizer_get_inline_attr( $value, $attr, '', true );
				}
			} else {
				foreach ( $attrs as $attr => $value ) {
					$output .= ' ' . qode_optimizer_get_inline_attr( $value, $attr );
				}
			}
		}

		$output = ltrim( $output );

		return $output;
	}
}

if ( ! function_exists( 'qode_optimizer_get_inline_attr' ) ) {
	/**
	 * Function that generates html attribute
	 *
	 * @param string|array $value value of html attribute
	 * @param string $attr - name of html attribute to generate
	 * @param string $glue - glue with which to implode $attr. Used only when $attr is arrayed
	 * @param bool $allow_zero_values - allow data to have zero value
	 *
	 * @return string generated html attribute
	 */
	function qode_optimizer_get_inline_attr( $value, $attr, $glue = '', $allow_zero_values = false ) {
		if ( $allow_zero_values ) {
			if ( '' !== $value ) {

				if ( is_array( $value ) && count( $value ) ) {
					$properties = implode( $glue, $value );
				} else {
					$properties = $value;
				}

				return $attr . '="' . esc_attr( $properties ) . '"';
			}
		} else {
			if ( ! empty( $value ) ) {

				if ( is_array( $value ) && count( $value ) ) {
					$properties = implode( $glue, $value );
				} elseif ( '' !== $value ) {
					$properties = $value;
				} else {
					return '';
				}

				return $attr . '="' . esc_attr( $properties ) . '"';
			}
		}

		return '';
	}
}

if ( ! function_exists( 'qode_optimizer_get_ajax_status' ) ) {
	/**
	 * Function that return status from ajax functions
	 *
	 * @param string $status - success or error
	 * @param string $message - ajax message value
	 * @param string|array $data - returned value
	 * @param string $redirect - url address
	 */
	function qode_optimizer_get_ajax_status( $status, $message, $data = null, $redirect = '' ) {
		$response = array(
			'status'   => esc_attr( $status ),
			'message'  => wp_kses_post( $message ),
			'data'     => $data,
			'redirect' => ! empty( $redirect ) ? esc_url( $redirect ) : '',
		);

		$output = wp_json_encode( $response );

		exit( $output ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'qode_optimizer_get_admin_options_map_position' ) ) {
	/**
	 * Function that set dashboard admin options map position
	 *
	 * @param string $map
	 *
	 * @return int
	 */
	function qode_optimizer_get_admin_options_map_position( $map ) {
		$position = 10;

		switch ( $map ) {
			case 'general':
				$position = 1;
				break;
			case 'optimization':
				$position = 2;
				break;
			case 'conversion':
				$position = 3;
				break;
			case 'webp':
				$position = 4;
				break;
			case 'advanced':
				$position = 5;
				break;
		}

		return apply_filters( 'qode_optimizer_filter_admin_options_map_position', $position, $map );
	}
}

if ( ! function_exists( 'qode_optimizer_get_home_path' ) ) {
	/**
	 * Returns home path of the project
	 *
	 * @return string
	 */
	function qode_optimizer_get_home_path() {
		if ( function_exists( 'get_home_path' ) ) {
			return get_home_path();
		} elseif ( defined( 'ABSPATH' ) ) {
			return ABSPATH;
		} else {
			return '';
		}
	}
}
