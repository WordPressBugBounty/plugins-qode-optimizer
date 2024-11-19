<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}
?>
<div class="qodef-knowledge-section">
	<div class="qodef-section-left">
		<h2><?php esc_html_e( 'Knowledge Base', 'qode-optimizer' ); ?></h2>
		<p><?php esc_html_e( 'Find the answers to all types of WordPress-related questions by browsing our Knowledge Base articles.', 'qode-optimizer' ); ?></p>
		<a href="https://helpcenter.qodeinteractive.com/?utm_source=dash&utm_medium=qodeoptimizer&utm_campaign=help" class="qodef-btn qodef-with-icon qodef-btn-underlined">
			<span class="qodef-btn-text"><?php esc_html_e( 'View More', 'qode-optimizer' ); ?></span>
			<span class="qodef-btn-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="15.675" height="15.675" viewBox="0 0 15.675 15.675">
					<path d="M7.917,9.5,6.809,8.353,9.619,5.542H0V3.959H9.619L6.809,1.148,7.917,0l4.75,4.75Z" transform="translate(0 8.957) rotate(-45)"/>
				</svg>
			</span>
		</a>
	</div>
	<div class="qodef-section-right">
		<img src="<?php echo esc_url( QODE_OPTIMIZER_ADMIN_URL_PATH . '/inc/admin-pages/options-custom-pages/help/assets/img/knowledge-base.png' ); ?>" alt="<?php esc_attr_e( 'Knowledge Base', 'qode-optimizer' ); ?>"/>
	</div>
</div>
