<?php

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

$log_enabled = 'yes' === Qode_Optimizer_Options::get_option( 'enable_system_log' );

if ( $log_enabled ) {
	if (
		! empty( $_REQUEST['qo_nonce'] ) &&
		wp_verify_nonce( sanitize_key( $_REQUEST['qo_nonce'] ), 'qo-nonce' ) &&
		! empty( $_REQUEST['deleted_log'] ) &&
		'success' === $_REQUEST['deleted_log']
	) {
		$log_note = esc_html__( 'System log was successfully deleted', 'qode-optimizer' );
	}

	$qo_nonce = wp_create_nonce( 'qo-nonce' );

	$action_params = array(
		'open'     => array(
			'action' => 'qode_optimizer_open_system_log',
			'label'  => esc_html__( 'Open Log', 'qode-optimizer' ),
		),
		'download' => array(
			'action' => 'qode_optimizer_download_system_log',
			'label'  => esc_html__( 'Download Log', 'qode-optimizer' ),
		),
		'delete'   => array(
			'action' => 'qode_optimizer_delete_system_log',
			'label'  => esc_html__( 'Delete Log', 'qode-optimizer' ),
		),
	);

	foreach ( $action_params as &$params ) {
		$params['href'] = add_query_arg(
			array(
				'action'   => $params['action'],
				'qo_nonce' => $qo_nonce,
			),
			admin_url( 'admin.php' )
		);
	}
} else {
	$log_note = esc_html__( 'System log is not enabled', 'qode-optimizer' );
}
?>
<div class="qodef-log-description">
	<p><?php esc_html_e( 'Here you’ll be able to view and analyze the steps that the system takes during all types of image optimization processes. The system log page lets you track processes that affect specific images, see the results of these processes, and helps you fine-tune your image optimization experience.', 'qode-optimizer' ); ?></p>
	<p><?php esc_html_e( 'Note that it is advisable to keep the system log turned on during analysis period only, as leaving it on all the time may have an impact on performance, cause slowdowns, and affect memory consumption negatively', 'qode-optimizer' ); ?></p>
</div>

<?php if ( ! empty( $log_note ) ) { ?>
	<div class="qodef-log-notice">
		<p><?php echo esc_html( $log_note ); ?></p>
	</div>
<?php } ?>

<?php if ( $log_enabled ) { ?>
	<div class="qodef-log-content">
		<?php if ( ! $log_enabled ) { ?>
			<p><?php esc_html_e( 'System log is not enabled', 'qode-optimizer' ); ?></p>
		<?php } else { ?>
			<div class="qodef-log-actions qodef-main">
				<?php foreach ( array( 'open', 'download' ) as $slug ) { ?>
					<a class="qodef-btn qodef-btn-solid" href="<?php echo esc_url( $action_params[ $slug ]['href'] ); ?>" target="_blank"><?php echo esc_html( $action_params[ $slug ]['label'] ); ?></a>
				<?php } ?>
			</div>
			<div class="qodef-log-actions">
				<?php foreach ( array( 'delete' ) as $slug ) { ?>
					<a class="qodef-btn qodef-btn-solid" href="<?php echo esc_url( $action_params[ $slug ]['href'] ); ?>" target="_self"><?php echo esc_html( $action_params[ $slug ]['label'] ); ?></a>
				<?php } ?>
			</div>
		<?php } ?>
	</div>
<?php } ?>
