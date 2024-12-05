<?php

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

define( 'QODE_OPTIMIZER_VERSION', '1.0.4' );
// Use the __DIR__ constant instead of calling dirname(__FILE__) (PHP >= 5.3).
define( 'QODE_OPTIMIZER_ABS_PATH', __DIR__ );
define( 'QODE_OPTIMIZER_REL_PATH', dirname( plugin_basename( __FILE__ ) ) );
define( 'QODE_OPTIMIZER_URL_PATH', plugin_dir_url( __FILE__ ) );
define( 'QODE_OPTIMIZER_ASSETS_PATH', QODE_OPTIMIZER_ABS_PATH . '/assets' );
define( 'QODE_OPTIMIZER_ASSETS_URL_PATH', QODE_OPTIMIZER_URL_PATH . 'assets' );
define( 'QODE_OPTIMIZER_INC_PATH', QODE_OPTIMIZER_ABS_PATH . '/inc' );
define( 'QODE_OPTIMIZER_INC_URL_PATH', QODE_OPTIMIZER_URL_PATH . 'inc' );
define( 'QODE_OPTIMIZER_ADMIN_PATH', QODE_OPTIMIZER_INC_PATH . '/admin' );
define( 'QODE_OPTIMIZER_ADMIN_URL_PATH', QODE_OPTIMIZER_INC_URL_PATH . '/admin' );
define( 'QODE_OPTIMIZER_CPT_PATH', QODE_OPTIMIZER_INC_PATH . '/post-types' );
define( 'QODE_OPTIMIZER_CPT_URL_PATH', QODE_OPTIMIZER_INC_URL_PATH . '/post-types' );
define( 'QODE_OPTIMIZER_SHORTCODES_PATH', QODE_OPTIMIZER_INC_PATH . '/shortcodes' );
define( 'QODE_OPTIMIZER_SHORTCODES_URL_PATH', QODE_OPTIMIZER_INC_URL_PATH . '/shortcodes' );
define( 'QODE_OPTIMIZER_PLUGINS_PATH', QODE_OPTIMIZER_INC_PATH . '/plugins' );
define( 'QODE_OPTIMIZER_PLUGINS_URL_PATH', QODE_OPTIMIZER_INC_URL_PATH . '/plugins' );
define( 'QODE_OPTIMIZER_HEADER_LAYOUTS_PATH', QODE_OPTIMIZER_INC_PATH . '/header/layouts' );
define( 'QODE_OPTIMIZER_HEADER_LAYOUTS_URL_PATH', QODE_OPTIMIZER_INC_URL_PATH . '/header/layouts' );
define( 'QODE_OPTIMIZER_HEADER_ASSETS_PATH', QODE_OPTIMIZER_INC_PATH . '/header/assets' );
define( 'QODE_OPTIMIZER_HEADER_ASSETS_URL_PATH', QODE_OPTIMIZER_INC_URL_PATH . '/header/assets' );
define( 'QODE_OPTIMIZER_MOBILE_HEADER_LAYOUTS_PATH', QODE_OPTIMIZER_INC_PATH . '/mobile-header/layouts' );
define( 'QODE_OPTIMIZER_MOBILE_HEADER_LAYOUTS_URL_PATH', QODE_OPTIMIZER_INC_URL_PATH . '/mobile-header/layouts' );

define( 'QODE_OPTIMIZER_CLASSES_FOLDER_PATH', QODE_OPTIMIZER_ABS_PATH . '/classes' );
define( 'QODE_OPTIMIZER_TESTS_FOLDER_PATH', QODE_OPTIMIZER_ABS_PATH . '/tests' );
define( 'QODE_OPTIMIZER_TOOLS_FOLDER_PATH', QODE_OPTIMIZER_ABS_PATH . '/tools' );
define( 'QODE_OPTIMIZER_DEMO_FOLDER_PATH', QODE_OPTIMIZER_ABS_PATH . '/demo' );
define( 'QODE_OPTIMIZER_LOGS_FOLDER_PATH', QODE_OPTIMIZER_ABS_PATH . '/logs' );
define( 'QODE_OPTIMIZER_SAMPLES_FOLDER_PATH', QODE_OPTIMIZER_ABS_PATH . '/samples' );
define( 'QODE_OPTIMIZER_VENDOR_FOLDER_PATH', QODE_OPTIMIZER_ABS_PATH . '/vendor' );

define( 'QODE_OPTIMIZER_MENU_NAME', 'qode_optimizer_menu' );
define( 'QODE_OPTIMIZER_OPTIONS_NAME', 'qode_optimizer_options' );
define( 'QODE_OPTIMIZER_GENERAL_MENU_NAME', 'qode_optimizer_general_menu' );
define( 'QODE_OPTIMIZER_MARKET_URL', 'https://qodeinteractive.com/products/plugins/qode-optimizer/' );

define( 'QODE_OPTIMIZER_STORE_URL', 'https://qodeinteractive.com' );
define( 'QODE_OPTIMIZER_ITEM_ID', 57699 );
define( 'QODE_OPTIMIZER_ITEM_NAME', 'Qode Optimizer' );
define( 'QODE_OPTIMIZER_ITEM_AUTHOR', 'Qode Interactive' );
