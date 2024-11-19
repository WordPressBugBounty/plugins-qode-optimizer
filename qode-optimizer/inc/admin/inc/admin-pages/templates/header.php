<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}
?>
<div class="qodef-custom-page-header">
	<img src="<?php echo esc_url( QODE_OPTIMIZER_ADMIN_URL_PATH . '/inc/admin-pages/assets/img/qode-logo.png' ); ?>" alt="<?php esc_attr_e( 'Qode Logo', 'qode-optimizer' ); ?>" width="40"/>
	<h1><?php echo esc_html( $title ); ?></h1>
</div>
