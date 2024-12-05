<?php
/*
* Plugin Name: QODE Optimizer
* Plugin URI: https://qodeinteractive.com/qode-optimizer/
* Description: The QODE Optimizer plugin is developed to allow you to convert, compress and adjust file sizes for all the images found on your website.
* Author: Qode Interactive
* Author URI: https://qodeinteractive.com/
* Version: 1.0.4
* Requires at least: 6.3
* Requires PHP: 7.4
* License: GPLv3
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
* Text Domain: qode-optimizer
*/

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! class_exists( 'Qode_Optimizer' ) ) {
	class Qode_Optimizer {
		private static $instance;

		public function __construct() {

			define( 'QODE_OPTIMIZER_PLUGIN_BASE_FILE', plugin_basename( __FILE__ ) );

			$this->before_init();

			add_action( 'qode_optimizer_action_framework_load_dependent_plugins', array( $this, 'init' ) );
		}

		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function init_admin_features() {
			$db = new Qode_Optimizer_Db();
			$db->install_necessary_tables();

			$system_log = Qode_Optimizer_Log::get_instance();
		}

		public function before_init() {
			// Include required files.
			require_once __DIR__ . '/constants.php';
			require_once QODE_OPTIMIZER_ABS_PATH . '/helpers/helper.php';

			// Include framework file.
			require_once QODE_OPTIMIZER_ADMIN_PATH . '/class-qode-optimizer-framework.php';

			// Include all plugin classes.
			foreach ( glob( QODE_OPTIMIZER_CLASSES_FOLDER_PATH . '/*.php' ) as $require ) {
				require_once $require;
			}

			// Include additional plugin classes.
			do_action( 'qode_optimizer_action_additional_plugin_classes_loaded' );

			// Exif metadata modification, for jpeg images.
			if ( ! class_exists( '\lsolesen\pel\PelJpeg' ) ) {
				require_once( QODE_OPTIMIZER_ABS_PATH . '/vendor/autoload.php' );
			}
		}

		public function init() {
			$this->require_core();

			add_filter( 'qode_optimizer_filter_framework_register_admin_options', array( $this, 'create_core_options' ) );

			add_action( 'qode_optimizer_action_framework_before_options_init_' . QODE_OPTIMIZER_OPTIONS_NAME, array( $this, 'init_core_options' ) );

			add_action( 'qode_optimizer_action_framework_populate_meta_box', array( $this, 'init_core_meta_boxes' ) );

			// QO system params initialization.
			add_action( 'init', 'Qode_Optimizer_Support::init', 10 );

			// QO options initialization.
			add_action( 'qode_optimizer_action_framework_after_options_init_' . QODE_OPTIMIZER_OPTIONS_NAME, 'Qode_Optimizer_Options::init' );

			// QO additional options initialization.
			do_action( 'qode_optimizer_action_additional_plugin_options_initialized' );

			// Make plugin available for translation.
			add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ), 15 );

			// Add plugin's body classes.
			add_filter( 'body_class', array( $this, 'add_body_classes' ) );

			// Hook to include additional modules when plugin loaded.
			do_action( 'qode_optimizer_action_plugin_loaded' );

			// General plugin initialization.
			$general = new Qode_Optimizer_General();
			$general->init();

			// Admin Media initialization.
			$media = new Qode_Optimizer_Media();
			$media->init();

			// Bulk actions initialization.
			$bulk = new Qode_Optimizer_Bulk();
			$bulk->init();

			// Admin Utility initialization.
			$utility = new Qode_Optimizer_Utility();
			$utility->init();

			// Parser actions initialization.
			$parser = new Qode_Optimizer_Parser();
			$parser->init();

			// Init plugin's admin features.
			add_filter( 'admin_init', array( $this, 'init_admin_features' ) );
		}

		public function require_core() {

			// Hook to include additional files before modules inclusion.
			do_action( 'qode_optimizer_action_before_include_modules' );

			foreach ( glob( QODE_OPTIMIZER_INC_PATH . '/*/include.php' ) as $module ) {
				include_once $module;
			}

			// Hook to include additional files after modules inclusion.
			do_action( 'qode_optimizer_action_after_include_modules' );
		}

		public function create_core_options( $options ) {
			$qode_optimizer_options_admin = new Qode_Optimizer_Framework_Options_Admin(
				QODE_OPTIMIZER_MENU_NAME,
				QODE_OPTIMIZER_OPTIONS_NAME,
				array(
					'label' => esc_html__( 'Optimizer', 'qode-optimizer' ),
				)
			);

			$options[] = $qode_optimizer_options_admin;

			return $options;
		}

		public function init_core_options() {
			$qode_optimizer_framework = qode_optimizer_framework_get_framework_root();

			if ( ! empty( $qode_optimizer_framework ) ) {
				$page = $qode_optimizer_framework->add_options_page(
					array(
						'scope'       => QODE_OPTIMIZER_OPTIONS_NAME,
						'type'        => 'admin',
						'slug'        => 'optimization',
						'title'       => esc_html__( 'Optimization', 'qode-optimizer' ),
						'description' => esc_html__( 'Optimization Options', 'qode-optimizer' ),
						'icon'        => 'fa fa-cog',
					)
				);

				// Hook to include additional options after optimization options.
				do_action( 'qode_optimizer_action_optimization_options_init', $page );

				// Hook to include additional options after conversion options.
				do_action( 'qode_optimizer_action_conversion_options_init', $page );

				$page = $qode_optimizer_framework->add_options_page(
					array(
						'scope'       => QODE_OPTIMIZER_OPTIONS_NAME,
						'type'        => 'admin',
						'slug'        => 'webp',
						'title'       => esc_html__( 'WebP', 'qode-optimizer' ),
						'description' => esc_html__( 'WebP Options', 'qode-optimizer' ),
						'icon'        => 'fa fa-cog',
					)
				);

				// Hook to include additional options after webp options.
				do_action( 'qode_optimizer_action_webp_options_init', $page );

				$page = $qode_optimizer_framework->add_options_page(
					array(
						'scope'       => QODE_OPTIMIZER_OPTIONS_NAME,
						'type'        => 'admin',
						'slug'        => 'advanced',
						'title'       => esc_html__( 'Advanced', 'qode-optimizer' ),
						'description' => esc_html__( 'Advanced Options', 'qode-optimizer' ),
						'icon'        => 'fa fa-cog',
					)
				);

				// Hook to include additional options after advanced options.
				do_action( 'qode_optimizer_action_advanced_options_init', $page );
			}
		}

		public function init_core_meta_boxes() {
			do_action( 'qode_optimizer_action_default_meta_boxes_init' );
		}

		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'qode-optimizer', false, QODE_OPTIMIZER_REL_PATH . '/languages' );
		}

		public function add_body_classes( $classes ) {
			$classes[] = 'qode-optimizer-' . QODE_OPTIMIZER_VERSION;

			return $classes;
		}
	}

	Qode_Optimizer::get_instance();
}

if ( ! function_exists( 'qode_optimizer_activation_trigger' ) ) {
	/**
	 * Function that trigger hooks on plugin activation
	 */
	function qode_optimizer_activation_trigger() {

		// Hook to add additional code on plugin activation.
		do_action( 'qode_optimizer_action_on_activation' );
	}

	register_activation_hook( __FILE__, 'qode_optimizer_activation_trigger' );
}

if ( ! function_exists( 'qode_optimizer_deactivation_trigger' ) ) {
	/**
	 * Function that trigger hooks on plugin deactivation
	 */
	function qode_optimizer_deactivation_trigger() {

		// Hook to add additional code on plugin deactivation.
		do_action( 'qode_optimizer_action_on_deactivation' );
	}

	register_deactivation_hook( __FILE__, 'qode_optimizer_deactivation_trigger' );
}
