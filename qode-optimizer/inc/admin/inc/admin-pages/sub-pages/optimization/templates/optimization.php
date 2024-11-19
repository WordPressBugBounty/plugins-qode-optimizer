<?php

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

$option_array = array(
	'media'   => array(
		'none'        => array(
			'ids'   => array(),
			'count' => 0,
			'attr'  => array(),
		),
		'selected'    => array(
			'ids'   => array(),
			'count' => 0,
			'attr'  => array(),
		),
		'unoptimized' => array(
			'ids'   => array(),
			'count' => 0,
			'attr'  => array(),
		),
		'all'         => array(
			'ids'   => array(),
			'count' => 0,
			'attr'  => array(),
		),
	),
	'folders' => array(
		'none'        => array(
			'paths' => array(),
			'count' => 0,
			'attr'  => array(),
		),
		'unoptimized' => array(
			'paths' => array(),
			'count' => 0,
			'attr'  => array(),
		),
		'all'         => array(
			'paths' => array(),
			'count' => 0,
			'attr'  => array(),
		),
	),
);

$all_exclude_images = Qode_Optimizer_Options::get_option( 'optimize_exclude_images' );
if ( ! is_array( $all_exclude_images ) ) {
	$all_exclude_images = array();
}

/**
 * Media images section
 */

// All images from Media.
$all_media = Qode_Optimizer_Media::get_all();

$all_ids_array         = array();
$all_exclude_ids_array = array();
foreach ( $all_media as $media ) {
	if ( ! in_array( realpath( $media['path'] ), $all_exclude_images, true ) ) {
		$all_ids_array[] = intval( $media['id'] );
	} else {
		$all_exclude_ids_array[] = intval( $media['id'] );
	}
}

$option_array['media']['all']['ids']   = $all_ids_array;
$option_array['media']['all']['count'] = count( $option_array['media']['all']['ids'] );

// Unoptimized images from Media.
global $wpdb;

$qo_db = new Qode_Optimizer_Db();

$qo_db->init_charset();

$modified_ids_array = array();

/**
 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
 */
$modifications_query   = 'SELECT DISTINCT attachment_id FROM ' . $qo_db->get_modifications_table() . ' WHERE attachment_id > 0';
$modifications_results = $wpdb->get_results( $modifications_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
if ( ! empty( $modifications_results ) ) {
	foreach ( $modifications_results as $result ) {
		$modified_ids_array[] = $result['attachment_id'];
	}
}

$modified_ids_array = array_map( 'intval', $modified_ids_array );

$option_array['media']['unoptimized']['ids']   = array_diff( $all_ids_array, $modified_ids_array );
$option_array['media']['unoptimized']['count'] = count( $option_array['media']['unoptimized']['ids'] );

// Selected images from Media.
$selected_ids_array = array();
if (
	! empty( $_REQUEST['qo_referer'] ) &&
	in_array( $_REQUEST['qo_referer'], array( 'media_library' ), true ) &&
	! empty( $_REQUEST['qo_nonce'] ) &&
	wp_verify_nonce( sanitize_key( $_REQUEST['qo_nonce'] ), 'qo-nonce' ) &&
	! empty( $_REQUEST['ids'] )
) {
	$selected_ids_array = explode( ',', sanitize_text_field( wp_unslash( $_REQUEST['ids'] ) ) );
	if ( ! is_array( $selected_ids_array ) ) {
		$selected_ids_array = array();
	}

	$selected_ids_array = array_filter(
		array_map( 'intval', $selected_ids_array ),
		function ( $item_id ) use ( $all_exclude_ids_array ) {
			return ! in_array( $item_id, $all_exclude_ids_array, true );
		}
	);
}

$option_array['media']['selected']['ids']   = array_intersect( $selected_ids_array, $all_ids_array );
$option_array['media']['selected']['count'] = count( $option_array['media']['selected']['ids'] );

if ( empty( $selected_ids_array ) ) {
	$option_array['media']['none']['attr'][] = 'checked';
	$default_media_option                    = 'none';
} else {
	$option_array['media']['selected']['attr'][] = 'checked';
	$default_media_option                        = 'selected';
}

/**
 * Additional folders section
 */

$filesystem = new Qode_Optimizer_Filesystem();

$backup_paths_array      = array();
$backup_ids_for_deletion = array();

/**
 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
 */
$backup_query   = 'SELECT * FROM ' . $qo_db->get_backup_table() . ' WHERE media_size = "folders"';
$backup_results = $wpdb->get_results( $backup_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
if ( ! empty( $backup_results ) ) {
	foreach ( $backup_results as $result ) {
		$current_file = maybe_unserialize( $result['backup_paths'] )['folders'];
		if ( $filesystem->is_file( $current_file ) ) {
			$backup_paths_array[] = realpath( $current_file );
		} else {
			$backup_ids_for_deletion[] = $result['id'];
		}
	}
}

if ( ! empty( $backup_ids_for_deletion ) ) {
	Qode_Optimizer_Db::delete_records_from_backup_table( $backup_ids_for_deletion );
}

$all_files          = array();
$additional_folders = Qode_Optimizer_Options::get_option( 'optimize_additional_folders' );
if ( ! empty( $additional_folders ) ) {
	foreach ( $additional_folders as $folder ) {
		// Add only new file paths.
		$all_files = array_merge( $all_files, array_diff( $filesystem->scan_directory( $folder ), $all_files ) );
	}

	$all_files = array_diff( $all_files, $all_exclude_images );
}

$option_array['folders']['all']['paths'] = array_diff( $all_files, $backup_paths_array );
$option_array['folders']['all']['count'] = count( $option_array['folders']['all']['paths'] );

if ( 0 === $option_array['folders']['all']['count'] ) {
	$option_array['folders']['all']['attr'][] = 'disabled';
}

$modified_paths_array      = array();
$modified_ids_for_deletion = array();

/**
 * Ignoring WordPress.DB.PreparedSQL.NotPrepared checks for the following reasons:
 * - table name strings cannot be reliably prepared because we need to use %i placeholder, which is supported only in more recent WP versions
 * - it's a well known issue apparently, an error gets falsely fired (false positive) for interpolated table names
 */
$modifications_query   = 'SELECT * FROM ' . $qo_db->get_modifications_table() . ' WHERE media_size = "folders"';
$modifications_results = $wpdb->get_results( $modifications_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
if ( ! empty( $modifications_results ) ) {
	foreach ( $modifications_results as $result ) {
		if (
			$filesystem->is_file( $result['current_path'] ) &&
			$filesystem->filesize( $result['current_path'] ) === (int) $result['current_size'] &&
			(
				(
					$result['current_path'] !== $result['previous_path'] &&
					1 === (int) $result['is_converted']
				) ||
				1 === (int) $result['is_optimized']
			)
		) {
			$modified_paths_array[] = realpath( $result['current_path'] );
		} else {
			$modified_ids_for_deletion[] = $result['id'];
		}
	}
}

if ( ! empty( $modified_ids_for_deletion ) ) {
	Qode_Optimizer_Db::delete_records_from_modifications_table( $modified_ids_for_deletion );
}

$option_array['folders']['unoptimized']['paths'] = array_diff( $all_files, $backup_paths_array, $modified_paths_array );
$option_array['folders']['unoptimized']['count'] = count( $option_array['folders']['unoptimized']['paths'] );

if ( 0 === $option_array['folders']['unoptimized']['count'] ) {
	$option_array['folders']['unoptimized']['attr'][] = 'disabled';
}

$option_array['folders']['none']['attr'][] = 'checked';
$default_folders_option                    = 'none';

$form_attr = wp_json_encode( $option_array );
$qo_nonce  = wp_create_nonce( 'qo-nonce' );
?>
<div class="qodef-bulk-content">
	<div id="qodef-bulk-forms">
		<form id="qodef-bulk-start" class="qodef-bulk-form" method="post" action="?page=optimization" <?php qode_optimizer_inline_attrs( array( 'data-params' => $form_attr ) ); ?> data-qo-nonce="<?php echo esc_html( $qo_nonce ); ?>">
			<div class="qodef-bulk-header-holder">
				<h2><?php esc_html_e( 'Optimization Options', 'qode-optimizer' ); ?></h2>
				<p><?php esc_html_e( 'Choose the options for the process of image optimization', 'qode-optimizer' ); ?></p>
			</div>
			<div class="qodef-bulk-form-content-holder">
				<h4 class="qodef-bulk-form-content-header"><?php esc_html_e( 'Media Library', 'qode-optimizer' ); ?></h4>
				<div class="qodef-bulk-form-content">
					<div class="qodef-bulk-radio-selection qodef-radio-group-holder">
						<div class="qodef-bulk-radio-option qodef-inline">
							<input id="qodef-bulk-option-none" class="qodef-field" <?php echo esc_attr( implode( ' ', $option_array['media']['none']['attr'] ) ); ?> name="bulk_option[]" type="radio" value="none" />
							<label for="qodef-bulk-option-none">
								<span class="qodef-label-view"></span>
								<span class="qodef-label-text"><?php esc_html_e( 'None', 'qode-optimizer' ); ?></span>
							</label>
						</div>
						<?php if ( $option_array['media']['selected']['count'] > 0 ) { ?>
							<div class="qodef-bulk-radio-option qodef-inline">
								<input id="qodef-bulk-option-selected" class="qodef-field" <?php echo esc_attr( implode( ' ', $option_array['media']['selected']['attr'] ) ); ?> name="bulk_option[]" type="radio" value="selected" />
								<label for="qodef-bulk-option-selected">
									<span class="qodef-label-view"></span>
									<span class="qodef-label-text"><?php esc_html_e( 'Selected images only', 'qode-optimizer' ); ?> (<?php echo esc_attr( $option_array['media']['selected']['count'] ); ?>)</span>
								</label>
							</div>
						<?php } ?>
						<?php if ( $option_array['media']['unoptimized']['count'] > 0 ) { ?>
							<div class="qodef-bulk-radio-option qodef-inline">
								<input id="qodef-bulk-option-unoptimized" class="qodef-field" <?php echo esc_attr( implode( ' ', $option_array['media']['unoptimized']['attr'] ) ); ?> name="bulk_option[]" type="radio" value="unoptimized" />
								<label for="qodef-bulk-option-unoptimized">
									<span class="qodef-label-view"></span>
									<span class="qodef-label-text"><?php esc_html_e( 'All unoptimized images', 'qode-optimizer' ); ?> (<?php echo esc_attr( $option_array['media']['unoptimized']['count'] ); ?>)</span>
								</label>
							</div>
						<?php } ?>
						<?php if ( $option_array['media']['all']['count'] > 0 ) { ?>
							<div class="qodef-bulk-radio-option qodef-inline">
								<input id="qodef-bulk-option-all" class="qodef-field" <?php echo esc_attr( implode( ' ', $option_array['media']['all']['attr'] ) ); ?> name="bulk_option[]" type="radio" value="all" />
								<label for="qodef-bulk-option-all">
									<span class="qodef-label-view"></span>
									<span class="qodef-label-text"><?php esc_html_e( 'All images', 'qode-optimizer' ); ?> (<?php echo esc_attr( $option_array['media']['all']['count'] ); ?>)</span>
								</label>
							</div>
						<?php } else { ?>
							<div class="qodef-note"><?php esc_html_e( 'There are no images in Media Library right now. Please, go and upload some first.', 'qode-optimizer' ); ?></div>
						<?php } ?>
					</div>
				</div>

				<?php
				$holder_inner_classes   = array( 'qodef-bulk-form-content-holder-inner' );
				$holder_inner_classes[] = $option_array['folders']['all']['count'] > 0 ? 'qodef-enabled' : 'qodef-disabled';
				?>

				<div <?php qode_optimizer_class_attribute( $holder_inner_classes ); ?>>
					<h4 class="qodef-bulk-form-content-header"><?php esc_html_e( 'Additional Folders', 'qode-optimizer' ); ?></h4>
					<div class="qodef-bulk-form-content">
						<div class="qodef-bulk-radio-selection qodef-radio-group-holder">
							<div class="qodef-bulk-radio-option qodef-inline">
								<input id="qodef-bulk-folders-option-none" <?php echo esc_attr( implode( ' ', $option_array['folders']['none']['attr'] ) ); ?> name="bulk_folders_option[]" type="radio" value="none" />
								<label for="qodef-bulk-folders-option-none">
									<span class="qodef-label-view"></span>
									<span class="qodef-label-text"><?php esc_html_e( 'None', 'qode-optimizer' ); ?></span>
								</label>
							</div>
							<?php if ( $option_array['folders']['unoptimized']['count'] > 0 ) { ?>
								<div class="qodef-bulk-radio-option qodef-inline">
									<input id="qodef-bulk-folders-option-unoptimized" <?php echo esc_attr( implode( ' ', $option_array['folders']['unoptimized']['attr'] ) ); ?> name="bulk_folders_option[]" type="radio" value="unoptimized" />
									<label for="qodef-bulk-folders-option-unoptimized">
										<span class="qodef-label-view"></span>
										<span class="qodef-label-text"><?php esc_html_e( 'All unoptimized images', 'qode-optimizer' ); ?> (<?php echo esc_attr( $option_array['folders']['unoptimized']['count'] ); ?>)</span>
									</label>
								</div>
							<?php } ?>
							<?php if ( $option_array['folders']['all']['count'] > 0 ) { ?>
								<div class="qodef-bulk-radio-option">
									<input id="qodef-bulk-folders-option-all" <?php echo esc_attr( implode( ' ', $option_array['folders']['all']['attr'] ) ); ?> name="bulk_folders_option[]" type="radio" value="all" />
									<label for="qodef-bulk-folders-option-all">
										<span class="qodef-label-view"></span>
										<span class="qodef-label-text"><?php esc_html_e( 'All images', 'qode-optimizer' ); ?> (<?php echo esc_attr( $option_array['folders']['all']['count'] ); ?>)</span>
									</label>
								</div>
							<?php } else { ?>
								<div class="qodef-note"><?php esc_html_e( 'There are no images in additional folders right now.', 'qode-optimizer' ); ?></div>
							<?php } ?>
						</div>
					</div>
				</div>

				<div class="qodef-bulk-form-content qodef-last">
					<div class="qodef-bulk-checkbox-selection qodef-checkbox-group-holder">
						<div class="qodef-bulk-checkbox-option qodef-inline">
							<input id="qodef-bulk-force-optimization" name="bulk_force_optimization" type="checkbox" value="force" />
							<label for="qodef-bulk-force-optimization">
								<span class="qodef-label-view"></span>
								<span class="qodef-label-text"><?php esc_html_e( 'Force Optimization (Images that are already optimized will be skipped by default. Check this option if you want to optimize them again)', 'qode-optimizer' ); ?></span>
							</label>
						</div>
					</div>
				</div>
			</div>

			<div class="qodef-bulk-form-action">
				<input id="qodef-bulk-optimization" <?php echo esc_html( 0 === $option_array['media'][ $default_media_option ]['count'] && 0 === $option_array['folders'][ $default_folders_option ]['count'] ? 'disabled' : '' ); ?> name="bulk_optimization" type="submit" class="qodef-btn qodef-btn-solid button-primary action" value="<?php esc_attr_e( 'Start Optimization', 'qode-optimizer' ); ?>" />
			</div>
		</form>
	</div>
	<div id="qodef-bulk-loading">
		<span class="qodef-spinner-loading qodef-hidden">
			<?php qode_optimizer_framework_svg_icon( 'spinner' ); ?>
			<span class="qodef-action-label"><?php esc_html_e( 'Processing, please wait...', 'qode-optimizer' ); ?></span>
		</span>
		<span class="qodef-message qodef-hidden"><?php esc_html_e( 'Done!', 'qode-optimizer' ); ?></span>
	</div>
	<div id="qodef-bulk-progressbar" class="qodef-hidden qodef-optimize" data-max="<?php echo esc_attr( $option_array['media'][ $default_media_option ]['count'] + $option_array['folders'][ $default_folders_option ]['count'] ); ?>"></div>
	<div id="qodef-bulk-counter" class="qodef-hidden"><span class="qodef-current">0</span><?php esc_html_e( ' of ', 'qode-optimizer' ); ?><span class="qodef-max"><?php echo esc_html( $option_array['media'][ $default_media_option ]['count'] + $option_array['folders'][ $default_folders_option ]['count'] ); ?></span></div>
	<div id="qodef-bulk-results" class="qodef-hidden"></div>
</div>
