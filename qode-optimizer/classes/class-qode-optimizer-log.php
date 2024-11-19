<?php
/**
 * Implementation of log procedures
 *
 * @package Qode
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Log {

	/**
	 * Current log instance
	 *
	 * @var Qode_Optimizer_Log $instance
	 */
	private static Qode_Optimizer_Log $instance; // phpcs:ignore PHPCompatibility.Classes.NewTypedProperties.Found

	/**
	 * Current log actions
	 *
	 * @var array $current_log
	 */
	private $current_log = array();

	/**
	 * Log file path
	 *
	 * @var string $log_file
	 */
	private $log_file = QODE_OPTIMIZER_LOGS_FOLDER_PATH . DIRECTORY_SEPARATOR . 'log.txt';

	/**
	 * Gets current log instance
	 *
	 * @return Qode_Optimizer_Log
	 */
	public static function get_instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Initialization
	 */
	public function init() {
		add_action( 'admin_action_qode_optimizer_open_system_log', array( $this, 'open_system_log' ) );
		add_action( 'admin_action_qode_optimizer_download_system_log', array( $this, 'download_system_log' ) );
		add_action( 'admin_action_qode_optimizer_delete_system_log', array( $this, 'delete_system_log' ) );
	}

	/**
	 * Qode_Optimizer_Log constructor
	 */
	public function __construct() {
		$filesystem = new Qode_Optimizer_Filesystem();

		if ( ! $filesystem->is_file( $this->log_file ) ) {
			// alternative for PHP native touch function.
			$filesystem->touch( $this->log_file );
		}

		// alternative for PHP native chmod function.
		$filesystem->chmod( $this->log_file, 0644 );

		$this->init();
	}

	/**
	 * Adds logs actions
	 *
	 * @param mixed $log_entry
	 * @param bool $heading
	 */
	public function add_log( $log_entry, $heading = false ) {
		if ( 'yes' === Qode_Optimizer_Options::get_option( 'enable_system_log' ) ) {
			if ( is_array( $log_entry ) ) {
				$log_entry = implode( ', ', $log_entry );
			} else {
				$log_entry = (string) $log_entry;
			}

			if ( ! is_bool( $heading ) ) {
				$heading = false;
			}

			$data = '';
			if ( ! $heading ) {
				$data .= gmdate( 'Y-m-d H:i:s' ) . ' -> ';
			}

			$data .= esc_html( $log_entry ) . PHP_EOL;

			$this->current_log[] = $data;
		}
	}

	/**
	 * Writes logs actions in the log file
	 *
	 * @return bool
	 */
	public function write_log() {
		if (
			'yes' === Qode_Optimizer_Options::get_option( 'enable_system_log' ) &&
			// WP_Filesystem methods don't have the alternative for PHP native file_put_contents FLAGS param, which is essential in our use case.
			file_put_contents( $this->log_file, implode( '', $this->current_log ), FILE_APPEND ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		) {
			$this->current_log = array();

			return true;
		}

		return false;
	}

	/**
	 * Sets elapsed time checkpoint
	 *
	 * @param array $elapsed_time_params
	 *
	 * @return array
	 */
	public function set_elapsed_time_checkpoint( $elapsed_time_params ) {
		if (
			is_array( $elapsed_time_params ) &&
			Qode_Optimizer_Utility::multiple_array_keys_exist(
				array(
					'current',
					'local',
					'total',
				),
				$elapsed_time_params
			)
		) {
			$current_time                   = microtime( true );
			$elapsed_time_params['local']   = $current_time - $elapsed_time_params['current'];
			$elapsed_time_params['total']  += $elapsed_time_params['local'];
			$elapsed_time_params['current'] = $current_time;

			$this->add_log( 'Elapsed time for this session: ' . number_format( $elapsed_time_params['local'], 4 ) . 's', true );

			$elapsed_time_params['local'] = 0.0;
		} else {
			$current_time        = microtime( true );
			$elapsed_time_params = array(
				'current' => $current_time,
				'local'   => 0.0,
				'total'   => 0.0,
			);
		}

		return $elapsed_time_params;
	}

	/**
	 * Opens log
	 */
	public function open_system_log() {
		if ( current_user_can( 'activate_plugins' ) ) {
			$filesystem = new Qode_Optimizer_Filesystem();

			if ( $filesystem->is_file( $this->log_file ) ) {
				ob_end_clean();
				header( 'Content-Type: text/plain;charset=UTF-8' );
				// WP_Filesystem methods don't have the alternative for PHP native readfile function.
				readfile( $this->log_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
				exit;
			}

			wp_die( esc_html__( 'The system Log is empty.', 'qode-optimizer' ) );
		}

		wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
	}

	/**
	 * Downloads log
	 */
	public function download_system_log() {
		if ( current_user_can( 'activate_plugins' ) ) {
			$filesystem = new Qode_Optimizer_Filesystem();

			if ( $filesystem->is_file( $this->log_file ) ) {
				ob_end_clean();
				header( 'Content-Description: File Transfer' );
				header( 'Content-Type: text/plain;charset=UTF-8' );
				header( 'Content-Disposition: attachment; filename=qode_optimizer_system_log_on_' . gmdate( 'Y_m_d__H_i_s' ) . '.txt' );
				header( 'Expires: 0' );
				header( 'Cache-Control: must-revalidate' );
				header( 'Pragma: public' );
				header( 'Content-Length: ' . filesize( $this->log_file ) );
				// WP_Filesystem methods don't have the alternative for PHP native readfile function.
				readfile( $this->log_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
				exit;
			}

			wp_die( esc_html__( 'The system Log is empty.', 'qode-optimizer' ) );
		}

		wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
	}

	/**
	 * Deletes log
	 */
	public function delete_system_log() {
		if ( current_user_can( 'activate_plugins' ) ) {

			$filesystem = new Qode_Optimizer_Filesystem();

			if (
				$filesystem->is_file( $this->log_file ) &&
				$filesystem->is_writable( $this->log_file )
			) {
				wp_delete_file( $this->log_file );
			}

			$redirect_url = add_query_arg(
				array(
					'page'        => 'qode_optimizer_log',
					'deleted_log' => 'success',
					'qo_nonce'    => wp_create_nonce( 'qo-nonce' ),
				),
				admin_url( 'admin.php' )
			);

			wp_safe_redirect( $redirect_url );

			exit;
		}

		wp_die( esc_html__( 'Access denied.', 'qode-optimizer' ) );
	}
}
