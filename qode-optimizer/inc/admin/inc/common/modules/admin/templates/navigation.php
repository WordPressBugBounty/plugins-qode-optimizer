<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}
?>
<div class="qodef-tabs-navigation-wrapper">
	<div class="qodef-tabs-navigation-wrapper-inner">
		<nav class="navbar navbar-expand-md navbar-dark bg-dark">
			<div class="collapse navbar-collapse" id="navbar-collapse">
				<ul class="navbar-nav mr-auto">
					<?php
					foreach ( $pages as $page_object ) {
						$page_slug       = $page_object->get_slug();
						$page_title      = $page_object->get_title();
						$section_slug    = empty( $page_slug ) ? $options_name : $options_name . '_' . $page_slug;
						$is_custom_page  = method_exists( $page_object, 'get_layout' ) && 'custom' === $page_object->get_layout();
						$dependency      = $page_object->get_dependency() ?? array();
						$dependency_data = array();

						$item_class   = array();
						$item_class[] = 'qodef-' . esc_attr( $page_slug );

						if ( ! empty( $dependency ) ) {
							$show     = array_key_exists( 'show', $dependency ) ? qode_optimizer_framework_return_dependency_options_array( $options_name, 'admin', $dependency['show'], true ) : array();
							$hide     = array_key_exists( 'hide', $dependency ) ? qode_optimizer_framework_return_dependency_options_array( $options_name, 'admin', $dependency['hide'] ) : array();
							$relation = array_key_exists( 'relation', $dependency ) ? $dependency['relation'] : 'and';

							$item_class[] = 'qodef-dependency-holder';
							$item_class[] = qode_optimizer_framework_return_dependency_classes( $show, $hide );

							$dependency_data = qode_optimizer_framework_return_dependency_data( $show, $hide, $relation );
						}

						$dependency_data['data-options-url'] = esc_url_raw(
							add_query_arg(
								array(
									'page' => QODE_OPTIMIZER_MENU_NAME,
								),
								admin_url( 'admin.php' )
							)
						);

						if ( $is_custom_page ) {
							$item_class[] = 'qodef-layout-custom';
						}
						?>
						<li class="nav-item <?php echo esc_attr( implode( ' ', $item_class ) ); ?>" <?php qode_optimizer_inline_attrs( $dependency_data, true ); ?>>
							<span class="nav-link" data-section="<?php echo esc_attr( $section_slug ); ?>">
									<?php if ( $page_object->get_icon() !== '' && $use_icons ) { ?>
										<i class="<?php echo esc_attr( $page_object->get_icon() ); ?> qodef-tooltip qodef-inline-tooltip left" data-placement="top" data-toggle="tooltip" title="<?php echo esc_attr( $page_title ); ?>"></i>
									<?php } ?>
								<span><?php echo esc_html( $page_title ); ?></span>
							</span>
						</li>
					<?php } ?>
				</ul>
			</div>
		</nav>
		<?php do_action( 'qode_optimizer_action_framework_before_custom_nav' ); ?>
		<?php
		$custom_nav = apply_filters( 'qode_optimizer_filter_framework_custom_nav', array() );

		if ( ! empty( $custom_nav ) && count( $custom_nav ) > 0 ) :
			?>
			<nav class="custom-navbar">
				<ul>
					<?php foreach ( $custom_nav as $nav_item ) : ?>
						<li class="nav-item <?php echo esc_attr( $nav_item['class'] ); ?>">
							<a href="<?php echo esc_url( $nav_item['url'] ); ?>">
								<?php
								if ( isset( $nav_item['icon'] ) ) {
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									echo qode_optimizer_framework_wp_kses_html( 'svg', $nav_item['icon'] );
								}
								?>
								<span><?php echo esc_html( $nav_item['name'] ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>
	</div>
</div>
