<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

class Qode_Optimizer_Framework_Field_File extends Qode_Optimizer_Framework_Field_Type {

	public function render_field() {
		$has_image = ! empty( $this->params['value'] );
		?>
		<div class="qodef-image-uploader" data-file="yes" data-allowed-type='<?php echo esc_attr( $this->args['allowed_type'] ); ?>'>
			<div class="qodef-image-thumb <?php echo ! $has_image ? 'qodef-hide' : ''; ?>">
				<?php
				if ( '' !== $this->params['value'] ) {
					$image_src = wp_get_attachment_image_src( $this->params['value'], 'full', true );
					?>
					<img class="qodef-file-image" src="<?php echo esc_url( $image_src[0] ); ?>" alt="<?php esc_attr_e( 'File Thumbnail', 'qode-optimizer' ); ?>"/>
					<div class="qodef-file-name"><?php echo esc_html( basename( get_attached_file( $this->params['value'] ) ) ); ?></div>
				<?php } ?>
			</div>
			<div class="qodef-image-meta-fields qodef-hide">
				<input type="hidden" class="qodef-field qodef-image-upload-id" name="<?php echo esc_attr( $this->name ); ?>" value="<?php echo esc_attr( $this->params['value'] ); ?>"/>
			</div>
			<a class="qodef-image-upload-btn" href="javascript:void(0)" data-frame-title="<?php esc_attr_e( 'Select File', 'qode-optimizer' ); ?>" data-frame-button-text="<?php esc_attr_e( 'Select File', 'qode-optimizer' ); ?>"><?php esc_html_e( 'Upload', 'qode-optimizer' ); ?></a>
			<a href="javascript: void(0)" class="qodef-image-remove-btn qodef-hide"><?php esc_html_e( 'Remove', 'qode-optimizer' ); ?></a>
		</div>
		<?php
	}
}
