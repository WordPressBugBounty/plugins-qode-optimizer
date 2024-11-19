<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}
?>
<div class="qodef-section-wrapper qodef-welcome-section col-12 <?php echo esc_attr( $class ); ?>" <?php qode_optimizer_inline_attrs( $dependency_data, true ); ?>>
	<div class="qodef-section-wrapper-inner">
		<div class="row">
			<?php
			$section_title       = $this_object->get_title();
			$section_description = $this_object->get_description();
			$section_icon        = $this_object->get_icon();

			if ( ! empty( $section_icon ) ) {
				?>
				<div class="qodef-welcome-icon">
					<img src="<?php echo esc_url( $section_icon ); ?>" alt="<?php echo esc_attr( $section_title ); ?>" />
				</div>
				<?php
			}
			?>
			<div class="qodef-welcome-content">
				<?php if ( ! empty( $section_title ) ) { ?>
					<h1 class="qodef-title qodef-section-title"><?php echo esc_html( $section_title ); ?></h1>
				<?php } ?>
				<?php if ( ! empty( $section_description ) ) { ?>
					<p class="qodef-description qodef-section-description"><?php echo wp_kses_post( $section_description ); ?></p>
				<?php } ?>
			</div>
		</div>
	</div>
</div>
