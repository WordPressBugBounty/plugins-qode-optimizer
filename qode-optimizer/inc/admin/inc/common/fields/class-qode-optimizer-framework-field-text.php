<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Framework_Field_Text extends Qode_Optimizer_Framework_Field_Type {

	public function render_field() {
		?>
		<?php if ( ! empty( $this->args['custom_class'] ) ) : ?>
			<div <?php qode_optimizer_class_attribute( $this->args['custom_class'] ); ?>>
		<?php endif; ?>
		<?php if ( $this->params['suffix'] || $this->params['prefix'] ) : ?>
		<div class="input-group">
	<?php endif; ?>
		<?php if ( $this->params['prefix'] ) : ?>
			<div class="input-group-addon input-prefix">
				<?php echo esc_html( $this->params['prefix'] ); ?>
			</div>
		<?php endif; ?>
		<input type="text" <?php qode_optimizer_inline_attrs( $this->data_attrs ); ?> class="qodef-field qodef-input" name="<?php echo esc_attr( $this->name ); ?>" value="<?php echo esc_attr( esc_html( $this->params['value'] ) ); ?>" placeholder="<?php echo isset( $this->args['placeholder'] ) ? esc_attr( esc_html( $this->args['placeholder'] ) ) : ''; ?>"
				<?php
				if ( isset( $this->args['readonly'] ) ) {
					echo ' readonly';
				}
				?>
		/>
		<?php if ( $this->params['suffix'] ) : ?>
			<div class="input-group-addon input-suffix">
				<?php echo esc_html( $this->params['suffix'] ); ?>
			</div>
		<?php endif; ?>
		<?php if ( $this->params['suffix'] || $this->params['prefix'] ) : ?>
		</div>
	<?php endif; ?>
		<?php if ( ! empty( $this->args['custom_class'] ) ) : ?>
			</div>
			<?php
		endif;
	}
}
