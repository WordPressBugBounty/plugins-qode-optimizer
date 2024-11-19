<?php
/**
 * Implementation of output procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Output {

	/**
	 * Params
	 *
	 * @var array $params
	 */
	public $params;

	/**
	 * Messages
	 *
	 * @var array $messages
	 */
	public $messages;

	/**
	 * Qode_Optimizer_Output constructor
	 */
	public function __construct() {
		$this->messages = array();
		$this->params   = array();
	}

	/**
	 * Set param
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set_param( $key, $value ) {
		$this->params[ $key ] = $value;
	}

	/**
	 * Get param
	 *
	 * @param string $key
	 *
	 * @@return  mixed|bool
	 */
	public function get_param( $key ) {
		return isset( $this->params[ $key ] ) ? $this->params[ $key ] : false;
	}

	/**
	 * Add new message
	 *
	 * @param string $message
	 */
	public function add_message( $message ) {
		$this->messages[] = $message;
	}

	/**
	 * Display string messages
	 *
	 * @return string
	 */
	public function show_messages_as_string() {
		$output = '';

		foreach ( $this->messages as $message ) {
			$output .= $message . ' ';
		}

		return $output;
	}

	/**
	 * Display HTML messages
	 *
	 * @return string
	 */
	public function show_messages_as_html() {
		$output = '';

		foreach ( $this->messages as $message ) {
			$output .= '<span>' . $message . '</span>';
		}

		return $output;
	}
}
