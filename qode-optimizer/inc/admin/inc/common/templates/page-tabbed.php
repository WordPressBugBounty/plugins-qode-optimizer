<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}
?>
<div class="qodef-tab-wrapper <?php echo esc_attr( $class ); ?>">
	<ul class="qodef-tab-item-nav-wrapper">
		<?php
		foreach ( $this_object->get_children() as $child ) {
			$dependency      = method_exists( $child, 'get_dependency' ) ? $child->get_dependency() : array();
			$dependency_data = array();
			$item_class      = array();

			if ( ! empty( $dependency ) ) {
				$show     = array_key_exists( 'show', $dependency ) ? qode_optimizer_framework_return_dependency_options_array( $child->get_scope(), $child->get_type(), $dependency['show'], true ) : array();
				$hide     = array_key_exists( 'hide', $dependency ) ? qode_optimizer_framework_return_dependency_options_array( $child->get_scope(), $child->get_type(), $dependency['hide'] ) : array();
				$relation = array_key_exists( 'relation', $dependency ) ? $dependency['relation'] : 'and';

				$item_class[] = 'qodef-dependency-holder';
				$item_class[] = qode_optimizer_framework_return_dependency_classes( $show, $hide );

				$dependency_data = qode_optimizer_framework_return_dependency_data( $show, $hide, $relation );
			}
			?>
			<li class="qodef-tab-item-nav-item <?php echo esc_attr( implode( ' ', $item_class ) ); ?>" <?php qode_optimizer_inline_attrs( $dependency_data, true ); ?>>
				<a href="#qodef-tab-<?php echo esc_attr( sanitize_title( $child->get_title() ) ); ?>" rel="noopener noreferrer"><?php echo esc_html( $child->get_title() ); ?></a>
			</li>
		<?php } ?>
	</ul>
	<?php foreach ( $this_object->get_children() as $child ) { ?>
		<div class="qodef-tab-item-content " id="qodef-tab-<?php echo esc_attr( sanitize_title( $child->get_title() ) ); ?>">
			<div class="row">
				<?php $child->render(); ?>
			</div>
		</div>
	<?php } ?>
</div>
