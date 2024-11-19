<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Framework_Root {
	private static $instance;
	private $admin_options;
	private $meta_options;
	private $attachment_options;

	private function __construct() {
		do_action( 'qode_optimizer_action_framework_before_framework_root_init' );

		add_action( 'after_setup_theme', array( $this, 'load_admin_pages' ), 5 );
		add_action( 'after_setup_theme', array( $this, 'load_options_files' ), 5 );
		add_action( 'after_setup_theme', array( $this, 'load_admin_notice_files' ), 5 );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_assets' ) );

		do_action( 'qode_optimizer_action_framework_after_framework_root_init' );
	}

	/**
	 * Instance of module class
	 *
	 * @return Qode_Optimizer_Framework_Root
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_admin_pages() {
		require_once QODE_OPTIMIZER_ADMIN_PATH . '/inc/admin-pages/include.php';
	}

	public function load_options_files() {
		require_once QODE_OPTIMIZER_ADMIN_PATH . '/inc/common/include.php';
		require_once QODE_OPTIMIZER_ADMIN_PATH . '/inc/fonts/include.php';

		$this->admin_options   = array();
		$admin_options_classes = apply_filters( 'qode_optimizer_filter_framework_register_admin_options', $this->admin_options );

		if ( ! empty( $admin_options_classes ) ) {
			foreach ( $admin_options_classes as $class ) {
				$this->set_admin_option( $class );
			}
		}

		$this->meta_options       = new Qode_Optimizer_Framework_Options_Meta();
		$this->attachment_options = new Qode_Optimizer_Framework_Options_Attachment();
	}

	public function load_admin_notice_files() {
		require_once QODE_OPTIMIZER_ADMIN_PATH . '/inc/admin-notice/include.php';
	}

	public function admin_enqueue_assets( $hook ) {

		if ( 'upload.php' === $hook ) {
			wp_enqueue_script( 'qode-optimizer-admin-media', QODE_OPTIMIZER_ADMIN_URL_PATH . '/inc/admin-media/assets/js/admin-media.min.js', array( 'jquery' ), QODE_OPTIMIZER_VERSION, true );
			wp_enqueue_style( 'qode-optimizer-admin-media', QODE_OPTIMIZER_ADMIN_URL_PATH . '/inc/admin-media/assets/css/admin-media.min.css', array(), QODE_OPTIMIZER_VERSION );
		}
	}

	public function get_admin_options() {
		return $this->admin_options;
	}

	public function set_admin_option( Qode_Optimizer_Framework_Options_Admin $options ) {
		$key                         = $options->get_options_name();
		$this->admin_options[ $key ] = $options;

		return $this->admin_options[ $key ];
	}

	public function get_admin_option( $key ) {
		if ( is_array( $key ) ) {
			$key = $key[0];
		}

		return $this->admin_options[ $key ];
	}

	public function get_meta_options() {
		return $this->meta_options;
	}

	public function get_attachment_options() {
		return $this->attachment_options;
	}

	public function add_options_page( $params ) {
		$page = false;
		if ( isset( $params['type'] ) && ! empty( $params['type'] ) ) {
			if ( 'admin' === $params['type'] ) {
				$scope = isset( $params['scope'] ) ? $params['scope'] : '';
				if ( ! empty( $scope ) ) {
					$page = new Qode_Optimizer_Framework_Page_Admin( $params );
					$this->get_admin_option( $scope )->add_option_page( $page );
				}
			} elseif ( 'meta' === $params['type'] ) {
				$page = new Qode_Optimizer_Framework_Page_Meta( $params );
				$this->get_meta_options()->add_option_page( $page );
			} elseif ( 'attachment' === $params['type'] ) {
				$page = new Qode_Optimizer_Framework_Page_Attachment( $params );
				$this->get_attachment_options()->add_option_page( $page );
			}
		}

		return $page;
	}
}

if ( ! function_exists( 'qode_optimizer_framework_get_framework_root' ) ) {
	/**
	 * Main instance of Framework Root.
	 *
	 * Returns the main instance of Qode_Optimizer_Framework_Root to prevent the need to use globals.
	 *
	 * @return Qode_Optimizer_Framework_Root
	 * @since  1.0
	 */
	function qode_optimizer_framework_get_framework_root() {
		return Qode_Optimizer_Framework_Root::get_instance();
	}
}
