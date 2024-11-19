<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! function_exists( 'qode_optimizer_get_list_of_other_plugins' ) ) {
	/**
	 * Function that return list of QODE plugins
	 */
	function qode_optimizer_get_list_of_other_plugins() {

		$current_plugin = basename( plugin_dir_path( QODE_OPTIMIZER_PLUGIN_BASE_FILE ) );

		$plugins         = array();
		$transient_name  = 'qode_optimizer_qode_products' . str_replace( '.', '_', QODE_OPTIMIZER_VERSION );
		$transient_value = get_transient( $transient_name );

		if ( false !== $transient_value ) {
			$plugins = $transient_value;
		} else {

			$url = 'https://export.qodethemes.com/qode-plugins/qode-list-of-plugins.txt';

			$response = wp_remote_get( $url );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code = wp_remote_retrieve_response_code( $response );

			if ( 200 === $code ) {
				$body         = wp_remote_retrieve_body( $response );
				$body_decoded = json_decode( $body, true );

				if ( ! empty( $body_decoded ) || is_array( $body_decoded ) ) {

					if ( isset( $body_decoded[ $current_plugin ] ) ) {
						unset( $body_decoded[ $current_plugin ] );
					}

					set_transient( $transient_name, $body_decoded, WEEK_IN_SECONDS );

					return $body_decoded;
				}
			}
		}

		return $plugins;
	}
}

if ( ! function_exists( 'qode_optimizer_get_plugin_by_slug_from_others_plugins' ) ) {
	/**
	 * Function that return list of QODE plugins
	 */
	function qode_optimizer_get_plugin_by_slug_from_others_plugins( $slug ) {
		$plugin  = array();
		$plugins = qode_optimizer_get_list_of_other_plugins();

		if ( isset( $plugins[ $slug ] ) ) {
			$plugin = $plugins[ $slug ];
		}

		return $plugin;
	}
}

if ( ! function_exists( 'qode_optimizer_plugin_get_plugin_link' ) ) {

	function qode_optimizer_plugin_get_plugin_link( $plugin_key, $plugin ) {

		$status = qode_optimizer_plugin_status( $plugin );

		switch ( $status ) :
			case 'installed':
			case 'installed_pro':
				$plugin_url = add_query_arg(
					array(
						'plugin_status' => 'inactive',
						's'             => esc_attr( $plugin_key ),
					),
					admin_url( 'plugins.php' )
				);

				$params = array(
					'class'      => 'qodef-install-plugin',
					'label'      => esc_html__( 'Activate', 'qode-optimizer' ),
					'plugin_url' => $plugin_url,
				);
				break;
			case 'activated':
				$params = array(
					'class'             => 'qodef-buy-plugin',
					'label'             => esc_html__( 'Upgrade', 'qode-optimizer' ),
					'plugin_url'        => isset( $plugin['upgrade_url'] ) ? $plugin['upgrade_url'] : '',
					'plugin_url_target' => '_blank',
				);
				break;
			case 'activated_pro':
				$plugin_url = add_query_arg(
					array(
						'plugin_status' => 'inactive',
						's'             => esc_attr( $plugin_key ),
					),
					admin_url( 'plugin-install.php' )
				);

				$params = array(
					'class'      => 'qodef-installed-plugin',
					'label'      => esc_html__( 'Activated', 'qode-optimizer' ),
					'plugin_url' => $plugin_url,
				);
				break;
			default:
				$plugin_url = add_query_arg(
					array(
						's'    => $plugin_key,
						'tab'  => 'search',
						'type' => 'term',
					),
					admin_url( 'plugin-install.php' )
				);

				$params = array(
					'class'      => 'qodef-install-plugin',
					'label'      => esc_html__( 'Get Free Version', 'qode-optimizer' ),
					'plugin_url' => $plugin_url,
				);
				break;
		endswitch;

		$params['plugin_key']        = $plugin_key;
		$params['plugin_url_target'] = isset( $params['plugin_url_target'] ) ? $params['plugin_url_target'] : '_self';

		return qode_optimizer_framework_get_template_part( QODE_OPTIMIZER_ADMIN_PATH . '/inc', 'admin-pages/options-custom-pages/qode-products', 'templates/parts/plugin-link', '', $params );
	}
}

if ( ! function_exists( 'qode_optimizer_is_specific_plugin_installed' ) ) {
	function qode_optimizer_is_specific_plugin_installed( $plugin ) {
		$plugins = get_plugins();

		if ( isset( $plugins[ $plugin ] ) ) {
			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'qode_optimizer_plugin_status' ) ) {
	function qode_optimizer_plugin_status( $plugin ) {

		$status       = '';
		$is_installed = qode_optimizer_is_specific_plugin_installed( $plugin['slug'] );

		if ( $is_installed ) {

			$status = 'installed';

			$is_activated = is_plugin_active( $plugin['slug'] );

			if ( $is_activated ) {
				$status           = 'activated';
				$is_installed_pro = qode_optimizer_is_specific_plugin_installed( $plugin['premium_slug'] );

				if ( $is_installed_pro ) {
					$status           = 'installed_pro';
					$is_activated_pro = is_plugin_active( $plugin['premium_slug'] );

					if ( $is_activated_pro ) {
						$status = 'activated_pro';
					}
				}
			}
		}

		return $status;
	}
}
