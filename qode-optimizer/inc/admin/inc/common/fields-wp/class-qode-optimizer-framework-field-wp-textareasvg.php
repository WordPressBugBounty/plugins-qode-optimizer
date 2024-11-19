<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}
class Qode_Optimizer_Framework_Field_WP_TextareaSVG extends Qode_Optimizer_Framework_Field_WP_Type {

	public function render_field() {
		?>
		<textarea name="<?php echo esc_attr( $this->name ); ?>" id="<?php echo esc_attr( $this->params['id'] ); ?>" rows="5"><?php echo qode_optimizer_framework_wp_kses_html( 'svg', $this->params['value'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></textarea>
		<?php
	}
}
