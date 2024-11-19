<?php
/**
 * Implementation of nginx web server procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

require_once QODE_OPTIMIZER_CLASSES_FOLDER_PATH . '/class-qode-optimizer-web-server.php';

class Qode_Optimizer_Web_Server_Nginx extends Qode_Optimizer_Web_Server {

	/**
	 * Web server type
	 */
	const WEB_SERVER_TYPE = 'Nginx';

	/**
	 * Htaccess rules
	 *
	 * @var array $htaccess_rules
	 */
	protected $htaccess_rules = array();

	/**
	 * Alternative htaccess rules
	 *
	 * @var array $alternative_htaccess_rules
	 */
	protected $alternative_htaccess_rules = array();
}
