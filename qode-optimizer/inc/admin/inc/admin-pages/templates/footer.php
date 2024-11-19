<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}
?>
<div class="qodef-admin-footer">
	<div class="qodef-admin-footer-text">
		<p>
			<?php
			// translators: %s - current date.
			echo wp_kses_post( sprintf( esc_html__( 'Copyright &copy; %d Qode Interactive, All rights reserved', 'qode-optimizer' ), date_i18n( 'Y' ) ) );
			?>
		</p>
	</div>
</div>
