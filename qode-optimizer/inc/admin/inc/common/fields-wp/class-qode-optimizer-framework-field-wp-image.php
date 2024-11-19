<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}
class Qode_Optimizer_Framework_Field_WP_Image extends Qode_Optimizer_Framework_Field_WP_Type {

	public function render_field() {
		$hide_class = empty( $this->params['value'] ) ? 'qodef-hide' : '';
		?>
		<div class="qodef-image-uploader" data-multiple="<?php echo esc_attr( $this->multiple ); ?>">
			<div class="qodef-image-thumb <?php echo esc_attr( $hide_class ); ?>">
				<?php if ( 'yes' === $this->multiple ) { ?>
					<ul class="clearfix">
						<?php
						if ( '' !== $this->params['value'] ) {
							$images_array = explode( ',', $this->params['value'] );
							foreach ( $images_array as $image_id ) :
								$image_src = wp_get_attachment_image_src( $image_id );
								if ( is_array( $image_src ) ) {
									echo '<li ><img src="' . esc_url( $image_src[0] ) . '" alt="' . esc_attr__( 'Image Thumbnail', 'qode-optimizer' ) . '" /></li>';
								}
							endforeach;
						}
						?>
					</ul>
					<?php
				} else {
					if ( '' !== $this->params['value'] ) {
						$image_src = wp_get_attachment_image_src( $this->params['value'] );
						?>
						<img class="qodef-single-image" src="<?php echo esc_url( $image_src[0] ); ?>" alt="<?php esc_attr_e( 'Image Thumbnail', 'qode-optimizer' ); ?>"/>
						<?php
					}
				}
				?>
			</div>
			<div class="qodef-image-meta-fields qodef-hide">
				<input type="hidden" name="<?php echo esc_attr( $this->name ); ?>" id="<?php echo esc_attr( $this->params['id'] ); ?>" value="<?php echo esc_attr( $this->params['value'] ); ?>" class="qodef-image-upload-id"/>
			</div>
			<a class="qodef-image-upload-btn" href="javascript:void(0)" data-frame-title="<?php esc_attr_e( 'Select Image', 'qode-optimizer' ); ?>" data-frame-button-text="<?php esc_attr_e( 'Select Image', 'qode-optimizer' ); ?>"><?php esc_html_e( 'Upload', 'qode-optimizer' ); ?></a>
			<a href="javascript: void(0)" class="qodef-image-remove-btn qodef-hide"><?php esc_html_e( 'Remove', 'qode-optimizer' ); ?></a>
		</div>
		<?php
	}
}
