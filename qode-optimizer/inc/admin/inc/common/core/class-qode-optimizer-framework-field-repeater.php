<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}
class Qode_Optimizer_Framework_Field_Repeater implements Qode_Optimizer_Framework_Tree_Interface, Qode_Optimizer_Framework_Child_Interface {

	private $name;
	private $title;
	private $description;
	private $button_text;
	private $layout;
	private $args;

	private $scope;
	private $type;
	private $value;
	private $row_number;
	private $children;
	private $params;

	public function __construct( $params ) {
		$this->name        = isset( $params['name'] ) ? $params['name'] : '';
		$this->title       = isset( $params['title'] ) ? $params['title'] : '';
		$this->description = isset( $params['description'] ) ? $params['description'] : '';
		$this->button_text = isset( $params['button_text'] ) ? $params['button_text'] : esc_html__( '+ Add New Item', 'qode-optimizer' );
		$this->layout      = isset( $params['layout'] ) ? $params['layout'] : 'normal';
		$this->args        = isset( $params['args'] ) ? $params['args'] : array();

		$this->scope      = isset( $params['scope'] ) ? $params['scope'] : '';
		$this->type       = isset( $params['type'] ) ? $params['type'] : '';
		$this->value      = isset( $params['value'] ) ? $params['value'] : array();
		$this->row_number = isset( $params['num_of_rows'] ) ? $params['num_of_rows'] : 1;
		$this->children   = isset( $params['children'] ) ? $params['children'] : array();
		$this->params     = isset( $params['params'] ) ? $params['params'] : array();

		$sortable_class = 'sortable';
		foreach ( $this->children as $child ) {
			if ( 'textareahtml' === $child->params['type'] ) {
				$sortable_class = 'not-sortable';
				break;
			}
		}
		$params['sortable_class'] = $sortable_class;

		$wrapper_classes   = array();
		$wrapper_classes[] = 'qodef-repeater-wrapper col-12';
		$wrapper_classes[] = 'qodef-repeater-' . $this->layout;
		$wrapper_classes[] = 'qodef-repeater-name-' . $this->name;

		if ( isset( $this->args['custom_class'] ) && '' !== $this->args['custom_class'] ) {
			$wrapper_classes[] = $this->args['custom_class'];
		}

		$params['wrapper_classes'] = $wrapper_classes;

		$wrapper_data                       = array();
		$wrapper_data['data-repeater-name'] = $this->name;
		$params['wrapper_data']             = $wrapper_data;

		$this->params = isset( $params ) ? $params : array();
	}

	public function get_name() {
		return $this->name;
	}

	public function get_children() {
		return $this->children;
	}

	public function has_children() {
		return count( $this->children ) > 0;
	}

	public function get_child( $key ) {
		return $this->children[ $key ];
	}

	public function add_child( Qode_Optimizer_Framework_Child_Interface $field ) {
		$key                    = $field->get_name();
		$this->children[ $key ] = $field;
	}

	public function add_repeater_inner_element( $params ) {
		if ( isset( $params['name'] ) && ! empty( $params['name'] ) ) {
			$params['type'] = $this->type;
			$field          = new Qode_Optimizer_Framework_Field_Repeater_Inner( $params );
			$this->add_child( $field );

			return $field;
		}

		return false;
	}

	public function add_field_element( $params ) {

		if ( isset( $params['name'] ) && ! empty( $params['name'] ) ) {
			$params['type']          = $this->type;
			$params['default_value'] = isset( $params['default_value'] ) ? $params['default_value'] : '';
			$field                   = new Qode_Optimizer_Framework_Field_Mapper( $params );
			$this->add_child( $field );

			return $field;
		}

		return false;
	}

	public function render() {
		$this->value = qode_optimizer_framework_get_option_value( $this->scope, $this->type, $this->name ); ?>

		<div <?php qode_optimizer_class_attribute( $this->params['wrapper_classes'] ); ?> <?php qode_optimizer_inline_attrs( $this->params['wrapper_data'] ); ?>>
			<div class="qodef-repeater-wrapper-inner">
				<div class="row">
					<?php if ( '' !== $this->title ) { ?>
						<h5 class="qodef-title qodef-repeater-title col-12"><?php echo esc_attr( $this->title ); ?></h5>
					<?php } ?>
					<?php if ( '' !== $this->description ) { ?>
						<p class="qodef-description qodef-repeater-description col-12"><?php echo esc_attr( $this->description ); ?></p>
					<?php } ?>
					<div class="qodef-repeater-wrapper-main col-12 <?php echo esc_attr( $this->params['sortable_class'] ); ?>" data-template="<?php echo esc_attr( str_replace( '_', '-', $this->name ) ); ?>">
						<input type="hidden" name="<?php echo esc_attr( $this->name ); ?>" value="" />
						<?php
						if ( ! empty( $this->value ) && count( $this->value ) > 0 ) {
							$counter = 0;
							foreach ( $this->value as $row ) {
								?>
								<div class="qodef-repeater-fields-holder clearfix" data-index="<?php echo esc_attr( $counter ); ?>">
									<div class="qodef-repeater-fields">
										<?php
										foreach ( $this->children as $child ) {
											if ( isset( $row[ $child->params['name'] ] ) ) {
												if ( '' === $row[ $child->params['name'] ] && isset( $child->params['default_value'] ) && '' !== $child->params['default_value'] ) {
													$repeater_field_value = $child->params['default_value'];
												} else {
													$repeater_field_value = $row[ $child->params['name'] ];
												}
											} else {
												$repeater_field_value = '';
											}

											$repeater = array(
												'name'  => $this->name,
												'index' => $counter,
												'value' => $repeater_field_value,
											);

											$child->params['repeater'] = $repeater;
											$child->render();
										}
										?>
									</div>
									<div class="qodef-repeater-actions-holder">
										<div class="qodef-repeater-sort">
											<?php qode_optimizer_framework_svg_icon( 'expand' ); ?>
										</div>
										<div class="qodef-repeater-remove">
											<a class="qodef-clone-remove" href="#" rel="noopener noreferrer"><?php qode_optimizer_framework_svg_icon( 'trash' ); ?></a>
										</div>
									</div>
								</div>
								<?php
								++$counter;
							}
						}
						?>
						<script type="text/html" class="qodef-repeater-template" id="tmpl-qodef-repeater-template-<?php echo esc_attr( str_replace( '_', '-', $this->name ) ); ?>">
							<div class="qodef-repeater-fields-holder <?php echo esc_attr( $this->params['sortable_class'] ); ?> clearfix" data-index="{{{ data.rowIndex }}}">
								<div class="qodef-repeater-fields">
									<?php
									foreach ( $this->children as $child ) {
										$repeate_template_field_value = ( isset( $child->params['default_value'] ) && '' !== $child->params['default_value'] ) ? $child->params['default_value'] : '';
										$repeater                     = array(
											'name'  => $this->name,
											'index' => '{{{ data.rowIndex }}}',
											'value' => $repeate_template_field_value,
										);
										$child->params['repeater']    = $repeater;

										$child->render();
									}
									?>
								</div>
								<div class="qodef-repeater-actions-holder">
									<div class="qodef-repeater-sort">
										<?php qode_optimizer_framework_svg_icon( 'expand' ); ?>
									</div>
									<div class="qodef-repeater-remove">
										<a class="qodef-clone-remove" href="#" rel="noopener noreferrer"><?php qode_optimizer_framework_svg_icon( 'trash' ); ?></a>
									</div>
								</div>
							</div>
						</script>
						<?php
						foreach ( $this->children as $child ) {
							if ( $child instanceof Qode_Optimizer_Framework_Field_Repeater_Inner ) {
								$repeate_template_field_value = ( isset( $child->params['default_value'] ) && '' !== $child->params['default_value'] ) ? $child->params['default_value'] : array();

								$repeater = array(
									'name'  => $this->name,
									'index' => '{{{ data.rowIndex }}}',
									'value' => $repeate_template_field_value,
								);

								$child->params['repeater'] = $repeater;
								$child->renderScript();
							}
						}
						?>
					</div>
					<div class="col-12">
						<div class="qodef-repeater-add">
							<a class="qodef-clone qodef-btn qodef-btn-solid" data-count="<?php echo esc_attr( $this->row_number ); ?>" href="#" rel="noopener noreferrer"><?php echo esc_html( $this->button_text ); ?></a>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
