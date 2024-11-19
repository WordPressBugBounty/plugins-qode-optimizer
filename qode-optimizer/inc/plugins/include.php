<?php

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

foreach ( glob( QODE_OPTIMIZER_PLUGINS_PATH . '/*/include.php' ) as $module ) {
	include_once $module;
}
