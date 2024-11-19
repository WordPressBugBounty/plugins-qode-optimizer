<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}
?>
<a
	class="qodef-btn qodef-with-icon qodef-btn-underlined qodef-action-button <?php echo esc_attr( $class ); ?>"
	data-plugin="<?php echo esc_attr( $plugin_key ); ?>"
	href="<?php echo esc_url( $plugin_url ); ?>"
	target="<?php echo esc_attr( $plugin_url_target ); ?>">
	<span class="qodef-btn-text"><?php echo esc_html( $label ); ?></span>
	<span class="qodef-btn-icon">
		<svg xmlns="http://www.w3.org/2000/svg" width="15.675" height="15.675" viewBox="0 0 15.675 15.675">
			<path d="M7.917,9.5,6.809,8.353,9.619,5.542H0V3.959H9.619L6.809,1.148,7.917,0l4.75,4.75Z" transform="translate(0 8.957) rotate(-45)"/>
		</svg>
	</span>
</a>
