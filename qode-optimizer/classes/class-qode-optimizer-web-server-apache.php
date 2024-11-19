<?php
/**
 * Implementation of apache web server procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

require_once QODE_OPTIMIZER_CLASSES_FOLDER_PATH . '/class-qode-optimizer-web-server.php';

class Qode_Optimizer_Web_Server_Apache extends Qode_Optimizer_Web_Server {

	/**
	 * Web server type
	 */
	const WEB_SERVER_TYPE = 'Apache';

	/**
	 * Htaccess rules
	 *
	 * @var array $htaccess_rules
	 */
	protected $htaccess_rules = array(
		'<IfModule mod_rewrite.c>',
		'RewriteEngine On',
		'RewriteCond %{HTTP_ACCEPT} image/webp',
		'RewriteCond %{REQUEST_FILENAME} (.*)\.(jpe?g|png|gif)$',
		'RewriteCond %{REQUEST_FILENAME}.webp -f',
		'RewriteCond %{QUERY_STRING} !type=original',
		'RewriteRule (.+)\.(jpe?g|png|gif)$ %{REQUEST_URI}.webp [T=image/webp,L]',
		'</IfModule>',
		'<IfModule mod_headers.c>',
		'<FilesMatch "\.(jpe?g|png|gif)$">',
		'Header append Vary Accept',
		'</FilesMatch>',
		'</IfModule>',
		'AddType image/webp .webp',
	);

	/**
	 * Alternative htaccess rules
	 *
	 * @var array $alternative_htaccess_rules
	 */
	protected $alternative_htaccess_rules = array();
}
