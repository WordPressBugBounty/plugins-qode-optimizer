<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

require_once QODE_OPTIMIZER_ADMIN_PATH . '/inc/admin-pages/class-qode-optimizer-admin-general-page.php';
require_once QODE_OPTIMIZER_ADMIN_PATH . '/inc/admin-pages/class-qode-optimizer-admin-sub-pages.php';
require_once QODE_OPTIMIZER_ADMIN_PATH . '/inc/admin-pages/class-qode-optimizer-admin-options-custom-page.php';
require_once QODE_OPTIMIZER_ADMIN_PATH . '/inc/admin-pages/class-qode-optimizer-admin-options-custom-page-handler.php';
require_once QODE_OPTIMIZER_ADMIN_PATH . '/inc/admin-pages/options-custom-pages/helper.php';

foreach ( glob( QODE_OPTIMIZER_ADMIN_PATH . '/inc/admin-pages/sub-pages/*/include.php' ) as $sub_page ) {
	require_once $sub_page;
}

foreach ( glob( QODE_OPTIMIZER_ADMIN_PATH . '/inc/admin-pages/options-custom-pages/*/include.php' ) as $custom_page ) {
	require_once $custom_page;
}
