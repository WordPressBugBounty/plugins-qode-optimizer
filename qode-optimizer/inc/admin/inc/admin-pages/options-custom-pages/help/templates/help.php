<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}
?>
<div class="qodef-custom-help-page qodef-options-admin qodef-page-v4-optimizer">
	<?php qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH . '/inc', 'admin-pages/options-custom-pages/help', 'templates/parts/header', '' ); ?>
	<?php qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH . '/inc', 'admin-pages/options-custom-pages/help', 'templates/parts/knowledge', '' ); ?>
	<?php qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH . '/inc', 'admin-pages/options-custom-pages/help', 'templates/parts/boxes', '' ); ?>
	<?php qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH . '/inc', 'admin-pages/options-custom-pages/help', 'templates/parts/subscribe', '' ); ?>
	<?php qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH . '/inc', 'admin-pages/options-custom-pages/help', 'templates/parts/social', '' ); ?>
</div>
<?php qode_optimizer_framework_template_part( QODE_OPTIMIZER_ADMIN_PATH . '/inc', 'admin-pages/options-custom-pages/help', 'templates/parts/footer', '' ); ?>
