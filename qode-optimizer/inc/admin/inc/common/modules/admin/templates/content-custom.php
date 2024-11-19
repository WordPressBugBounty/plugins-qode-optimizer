<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}
?>
<div class="qodef-tabs-content">
	<?php
	foreach ( $pages as $custom_page ) {

		if ( 'custom' !== $custom_page->get_layout() ) {
			continue;
		}

		$page_slug    = $custom_page->get_slug();
		$section_slug = empty( $page_slug ) ? $options_name : $options_name . '_' . $page_slug;
		?>
		<div class="tab-content qodef-hide-pane" data-section="<?php echo esc_attr( $section_slug ); ?>">
			<div class="tab-pane">
				<div class="qodef-tab-content">
					<?php $custom_page->render(); ?>
				</div>
			</div>
		</div>
	<?php } ?>
</div>
