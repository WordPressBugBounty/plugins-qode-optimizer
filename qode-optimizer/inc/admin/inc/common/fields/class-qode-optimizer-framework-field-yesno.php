<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Framework_Field_Yesno extends Qode_Optimizer_Framework_Field_Type {

	public function render_field() {
		?>
		<div class="qodef-yesno qodef-field" data-option-name="<?php echo esc_attr( $this->name ); ?>" data-option-type="radiogroup">
			<input class="qodef-field" type="radio" id="<?php echo esc_attr( $this->name ); ?>-yes" name="<?php echo esc_attr( $this->name ); ?>" value="yes" <?php echo esc_attr( $this->params['value'] ) === 'yes' ? 'checked' : ''; ?>/>
			<label for="<?php echo esc_attr( $this->name ); ?>-yes"><?php esc_html_e( 'Yes', 'qode-optimizer' ); ?></label>
			<input class="qodef-field" type="radio" id="<?php echo esc_attr( $this->name ); ?>-no" name="<?php echo esc_attr( $this->name ); ?>" value="no" <?php echo esc_attr( $this->params['value'] ) === 'no' ? 'checked' : ''; ?> />
			<label for="<?php echo esc_attr( $this->name ); ?>-no"><?php esc_html_e( 'No', 'qode-optimizer' ); ?></label>
		</div>
		<?php
	}
}
