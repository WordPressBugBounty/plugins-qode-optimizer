<?php
/**
 * Implementation of web server procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

abstract class Qode_Optimizer_Web_Server {

	/**
	 * Web server type
	 */
	const WEB_SERVER_TYPE = false;

	/**
	 * Get htaccess rules
	 *
	 * @return array|bool
	 */
	public function get_htaccess_rules() {
		return $this->htaccess_rules;
	}

	/**
	 * Set htaccess rules
	 *
	 * @param array $rules
	 */
	public function set_htaccess_rules( $rules ) {
		if ( is_array( $rules ) ) {
			$this->htaccess_rules = $rules;
		}
	}

	/**
	 * Get alternative htaccess rules
	 *
	 * @return array|bool
	 */
	public function get_alternative_htaccess_rules() {
		return $this->alternative_htaccess_rules;
	}

	/**
	 * Set alternative htaccess rules
	 *
	 * @param array $rules
	 */
	public function set_alternative_htaccess_rules( $rules ) {
		if ( is_array( $rules ) ) {
			$this->alternative_htaccess_rules = $rules;
		}
	}

	/**
	 * Checks for htaccess availability
	 *
	 * @return bool
	 */
	public function check_htaccess_availability() {
		$htaccess_file = $this->get_htaccess_path();

		$filesystem = new Qode_Optimizer_Filesystem();

		if (
			$filesystem->is_file( $htaccess_file ) &&
			$filesystem->is_writable( $htaccess_file )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Get .htaccess path
	 *
	 * @return string .htaccess file path
	 */
	public function get_htaccess_path() {
		$htaccess_folder = qode_optimizer_get_home_path();
		$htaccess_file   = $htaccess_folder . '.htaccess';

		$home     = get_option( 'home' );
		$site_url = get_option( 'siteurl' );

		// Site Url is sub-domain of WP.
		if ( $home !== $site_url ) {
			$url_base       = rtrim( $htaccess_folder, '/' );
			$url_difference = ltrim( str_replace( $home, '', $site_url ), '/' );

			$subdomain_htaccess_folder = trailingslashit( $url_base . '/' . $url_difference );
			$subdomain_htaccess_file   = $subdomain_htaccess_folder . '.htaccess';

			$filesystem = new Qode_Optimizer_Filesystem();

			if ( $filesystem->is_file( $subdomain_htaccess_file ) ) {
				return $subdomain_htaccess_file;
			}
		}

		return $htaccess_file;
	}

	/**
	 * Check if a given ip is in a network
	 * https://gist.github.com/tott/7684443
	 *
	 * @param string $ip IP to check in IPV4 format eg. 127.0.0.1
	 * @param string $range IP/CIDR netmask eg. 127.0.0.0/24, also 127.0.0.1 is accepted and /32 assumed
	 *
	 * @return boolean true if the ip is in this range / false if not.
	 */
	public static function ip_in_range( $ip, $range ) {
		if ( false === strpos( $range, '/' ) ) {
			$range .= '/32';
		}

		// $range is in IP/CIDR format eg 127.0.0.1/24
		list( $range, $netmask ) = explode( '/', $range, 2 );

		$range_decimal    = ip2long( $range );
		$ip_decimal       = ip2long( $ip );
		$wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
		$netmask_decimal  = ~$wildcard_decimal;

		return ( ( $ip_decimal & $netmask_decimal ) === ( $range_decimal & $netmask_decimal ) );
	}

	/**
	 * Cloudflare protection
	 *
	 * @return bool
	 */
	public static function cloudflare_protection_exists() {
		$cloudflare_ip_ranges = array(
			'173.245.48.0/20',
			'103.21.244.0/22',
			'103.22.200.0/22',
			'103.31.4.0/22',
			'141.101.64.0/18',
			'108.162.192.0/18',
			'190.93.240.0/20',
			'188.114.96.0/20',
			'197.234.240.0/22',
			'198.41.128.0/17',
			'162.158.0.0/15',
			'104.16.0.0/13',
			'104.24.0.0/14',
			'172.64.0.0/13',
			'131.0.72.0/22',
		);

		foreach (
			array(
				'HTTP_CF_IPCOUNTRY',
				'HTTP_CF_RAY',
				'HTTP_CF_VISITOR',
				'HTTP_CF_CONNECTING_IP',
				'HTTP_CF_REQUEST_ID',
			) as $global
		) {
			if ( ! empty( $_SERVER[ $global ] ) ) {
				return true;
			}
		}

		if (
			isset( $_SERVER['HTTP_CDN_LOOP'] ) &&
			'cloudflare' === $_SERVER['HTTP_CDN_LOOP']
		) {
			return true;
		}

		$filesystem = new Qode_Optimizer_Filesystem();

		$host_name = $filesystem->parse_url( get_site_url(), PHP_URL_HOST );
		$host_ip   = gethostbyname( $host_name );

		foreach ( $cloudflare_ip_ranges as $ip_range ) {
			if ( static::ip_in_range( $host_ip, $ip_range ) ) {
				return true;
			}
		}

		return false;
	}
}
