<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Framework_Field_Textareahtml extends Qode_Optimizer_Framework_Field_Type {

	public function render_field() {
		?>
		<textarea class="form-control qodef-field qodef--field-html" name="<?php echo esc_attr( $this->name ); ?>" rows="10"
		<?php
		if ( isset( $this->args['readonly'] ) ) {
			echo ' readonly';
		}
		?>
		><?php echo wp_kses_post( $this->params['value'] ); ?></textarea>
		<?php
	}
}
