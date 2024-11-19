<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Framework_Field_Color extends Qode_Optimizer_Framework_Field_Type {

	public function load_assets() {
		parent::load_assets();

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker-alpha' );
	}

	public function render_field() {
		$has_placeholder = $this->args['placeholder'] ?? '';
		?>
		<input type="text" data-alpha-enabled="true" name="<?php echo esc_attr( $this->name ); ?>" value="<?php echo esc_attr( $this->params['value'] ); ?>" placeholder="<?php echo esc_attr( esc_html( $has_placeholder ) ); ?>" class="qodef-field qodef-color-field"/>
		<?php
		if ( $has_placeholder ) {
			?>
			<span class="qodef-color-field-placeholder" style="background-color: <?php echo esc_attr( $has_placeholder ); ?>"></span>
			<?php
		}
	}
}
