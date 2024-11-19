<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Framework_Field_Select extends Qode_Optimizer_Framework_Field_Type {

	public function __construct( $params ) {
		$select_class = 'qodef-select2';
		if ( isset( $params['args'] ) && isset( $params['args']['select2'] ) && empty( $params['args']['select2'] ) ) {
			$select_class = '';
		}
		$params['select_class'] = $select_class;

		$params['multiple'] = '';
		if ( isset( $params['args'] ) && isset( $params['args']['multiple'] ) && $params['args']['multiple'] ) {
			$params['multiple'] = 'multiple';
		}

		parent::__construct( $params );
	}

	public function render_field() {
		$field_name = ! empty( $this->params['multiple'] ) ? $this->name . '[]' : $this->name;
		?>
		<select class="<?php echo esc_attr( $this->params['select_class'] ); ?> qodef-field" name="<?php echo esc_attr( $field_name ); ?>" data-option-name="<?php echo esc_attr( $this->name ); ?>" data-option-type="selectbox" <?php echo esc_attr( $this->params['multiple'] ); ?>>
			<?php
			foreach ( $this->options as $key => $label ) {
				if ( '-1' === $key ) {
					$key = '';
				}
				?>
				<option
					<?php
					if ( ( is_array( $this->params['value'] ) && in_array( strval( $key ), $this->params['value'], true ) ) || strval( $key ) === $this->params['value'] ) {
						echo " selected='selected'";
					}
					?>
						value="<?php echo esc_attr( $key ); ?>">
					<?php echo esc_html( $label ); ?>
				</option>
			<?php } ?>
		</select>
		<?php
	}
}
