<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}
class Qode_Optimizer_Framework_Field_Attachment_Select extends Qode_Optimizer_Framework_Field_Attachment_Type {

	public function render() {
		$html = '<select name="' . $this->name . '">';
		foreach ( $this->options as $key => $label ) {
			$selected = $this->params['value'] === $key ? ' selected="selected"' : '';
			$html    .= '<option' . esc_attr( $selected ) . ' value="' . esc_attr( $key ) . '">';
			$html    .= esc_html( $label );
			$html    .= '</option>';
		}
		$html .= '</select>';

		$this->form_fields['html'] = $html;
	}
}
