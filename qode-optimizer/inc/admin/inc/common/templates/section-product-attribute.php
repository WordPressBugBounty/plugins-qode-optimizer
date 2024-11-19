<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

global $pagenow;

$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : '';
$base   = '';
if ( $screen && is_object( $screen ) && isset( $screen->base ) ) {
	$base = $screen->base;
}

$section_title = $this_object->get_title();

if ( empty( $section_title ) ) {
	return;
}

if ( 'term.php' === $pagenow || ( 'edit.php' === $pagenow && 'product_page_product_attributes' === $base ) ) {
	?>
	<tr class="qodef-form-field">
		<td colspan="2">
			<div class="qodef-options-heading">
				<div class="qodef-options-heading-icon">
					<img src="<?php echo esc_url( QODE_OPTIMIZER_ADMIN_URL_PATH . '/inc/common/modules/admin/assets/img/admin-logo-icon.png' ); ?>" alt="<?php echo esc_attr( $section_title ); ?>" />
				</div>
				<div class="qodef-options-heading-content">
					<h3 class="qodef-title qodef-section-title"><?php echo esc_html( $section_title ); ?></h3>
				</div>
			</div>
		</td>
	</tr>
<?php } else { ?>
	<div class="qodef-options-heading">
		<div class="qodef-options-heading-icon">
			<img src="<?php echo esc_url( QODE_OPTIMIZER_ADMIN_URL_PATH . '/inc/common/modules/admin/assets/img/admin-logo-icon.png' ); ?>" alt="<?php echo esc_attr( $section_title ); ?>" />
		</div>
		<div class="qodef-options-heading-content">
			<h3 class="qodef-title qodef-section-title"><?php echo esc_html( $section_title ); ?></h3>
		</div>
	</div>
<?php } ?>
