<?php
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! function_exists( 'qode_optimizer_framework_return_dependency_options_array' ) ) {
	/**
	 * Function that return option dependency values
	 *
	 * @param array|string $scope - option key from database
	 * @param string $type - option name
	 * @param array $dependency_values
	 * @param bool $initial
	 * @param bool $repeater
	 *
	 * @return array
	 */
	function qode_optimizer_framework_return_dependency_options_array( $scope, $type, $dependency_values = array(), $initial = false, $repeater = false ) {
		$return_array   = array();
		$options_values = array();
		$data_values    = array();

		if ( ! empty( $dependency_values ) ) {
			foreach ( $dependency_values as $key => $dependency_params ) {
				$values        = $dependency_params['values'];
				$default_value = $dependency_params['default_value'];

				if ( ! is_array( $values ) ) {
					$values = array( $values );
				}

				if ( ! empty( $values ) ) {
					$data_values[ $key ] = implode( ',', $values );

					if ( $repeater ) {
						$rep_key = explode( '[', str_replace( ']', '', $key ) );

						if ( ! empty( $rep_key ) && count( $rep_key ) > 2 ) {
							$rep_main_option = $rep_key[0];
							$rep_key_index   = $rep_key[1];
							$rep_main_key    = $rep_key[2];

							$rep_option = qode_optimizer_framework_get_option_value( $scope, $type, $rep_main_option );

							if ( count( $rep_key ) === 5 ) {
								$rep_key_inner_index = $rep_key[3];
								$rep_main_inner_key  = $rep_key[4];

								if ( isset( $rep_option[ $rep_key_index ][ $rep_main_key ] ) ) {
									if ( isset( $rep_option[ $rep_key_index ][ $rep_main_key ][ $rep_key_inner_index ][ $rep_main_inner_key ] ) ) {
										$options_values[] = in_array( $rep_option[ $rep_key_index ][ $rep_main_key ][ $rep_key_inner_index ][ $rep_main_inner_key ], $values, true );
									} else {
										$options_values[] = in_array( $default_value, $values, true );
									}
								} else {
									$options_values[] = in_array( $default_value, $values, true );
								}
							} else {

								if ( isset( $rep_option[ $rep_key_index ][ $rep_main_key ] ) ) {
									$options_values[] = in_array( $rep_option[ $rep_key_index ][ $rep_main_key ], $values, true );
								} else {
									$options_values[] = in_array( $default_value, $values, true );
								}
							}
						}
					} else {
						$saved_value = qode_optimizer_framework_get_option_value( $scope, $type, $key );

						// Improved normal options dependency to work for checkboxes ($saved_value or $default_value is array).
						if ( ! empty( $saved_value ) ) {

							if ( is_array( $saved_value ) ) {
								$options_values[] = in_array( $data_values[ $key ], $saved_value, true );
							} else {
								$options_values[] = in_array( $saved_value, $values, true );
							}
						} else {

							if ( is_array( $default_value ) ) {
								$options_values[] = in_array( $data_values[ $key ], $default_value, true );
							} else {
								$options_values[] = in_array( $default_value, $values, true );
							}
						}
					}
				}
			}

			$hide_item = false;

			if ( count( array_unique( $options_values ) ) === 1 ) {
				if ( $initial && false === $options_values[0] ) {
					$hide_item = true;
				} elseif ( ! $initial && true === $options_values[0] ) {
					$hide_item = true;
				}
			}

			$return_array = array(
				'data_values'    => $data_values,
				'hide_container' => $hide_item,
			);
		}

		return $return_array;
	}
}

if ( ! function_exists( 'qode_optimizer_framework_return_widget_dependency_options_array' ) ) {
	/**
	 * Function that return widget options dependency values
	 *
	 * @param array $instance - widget options
	 * @param array $dependency_values
	 * @param bool $initial
	 *
	 * @return array
	 */
	function qode_optimizer_framework_return_widget_dependency_options_array( $instance, $dependency_values = array(), $initial = false ) {
		$return_array   = array();
		$options_values = array();
		$data_values    = array();

		if ( ! empty( $dependency_values ) ) {
			foreach ( $dependency_values as $key => $dependency_params ) {
				$values        = $dependency_params['values'];
				$default_value = isset( $dependency_params['default_value'] ) ? $dependency_params['default_value'] : '';
				$option_name   = $dependency_params['option_name'];

				if ( ! empty( $instance ) ) {
					if ( is_array( $values ) ) {
						$data_values[ $key ] = implode( ',', $values );
						if ( in_array( $instance[ $option_name ], $values, true ) ) {
							$options_values[] = true;
						} else {
							$options_values[] = false;
						}
					} else {
						$data_values[ $key ] = $values;

						if ( isset( $instance[ $option_name ] ) ) {
							if ( $instance[ $option_name ] === $values ) {
								$options_values[] = true;
							} else {
								$options_values[] = false;
							}
						} else {
							if ( $default_value === $values ) {
								$options_values[] = true;
							} else {
								$options_values[] = false;
							}
						}
					}
				} else {
					if ( is_array( $values ) ) {
						$data_values[ $key ] = implode( ',', $values );
						if ( in_array( $default_value, $values, true ) ) {
							$options_values[] = true;
						} else {
							$options_values[] = false;
						}
					} else {
						$data_values[ $key ] = $values;
						if ( $default_value === $values ) {
							$options_values[] = true;
						} else {
							$options_values[] = false;
						}
					}
				}
			}

			$hide_item = false;

			if ( count( array_unique( $options_values ) ) === 1 ) {
				if ( $initial && false === $options_values[0] ) {
					$hide_item = true;
				} elseif ( ! $initial && true === $options_values[0] ) {
					$hide_item = true;
				}
			}

			$return_array = array(
				'data_values'    => $data_values,
				'hide_container' => $hide_item,
			);
		}

		return $return_array;
	}
}

if ( ! function_exists( 'qode_optimizer_framework_return_dependency_classes' ) ) {
	/**
	 * Function that return dependency option class name
	 *
	 * @param array $show
	 * @param array $hide
	 *
	 * @return string
	 */
	function qode_optimizer_framework_return_dependency_classes( $show = array(), $hide = array() ) {
		$hide_container = true;

		if ( ! empty( $show ) ) {
			$hide_container = $show['hide_container'];
		}

		if ( ! empty( $hide ) ) {
			$hide_container = $hide['hide_container'];
		}

		if ( $hide_container ) {
			return 'qodef-hide-dependency-holder';
		}

		return '';
	}
}

if ( ! function_exists( 'qode_optimizer_framework_return_dependency_data' ) ) {
	/**
	 * Function that return dependency option datas
	 *
	 * @param array $show
	 * @param array $hide
	 * @param string $relation
	 *
	 * @return array
	 */
	function qode_optimizer_framework_return_dependency_data( $show = array(), $hide = array(), $relation = 'and' ) {
		$dependency_data  = array();
		$show_data_values = '';
		$hide_data_values = '';

		if ( ! empty( $show ) ) {
			$show_data_values = $show['data_values'];
		}

		if ( ! empty( $hide ) ) {
			$hide_data_values = $hide['data_values'];
		}

		$dependency_data['data-show']     = ! empty( $show_data_values ) ? wp_json_encode( $show_data_values ) : '';
		$dependency_data['data-hide']     = ! empty( $hide_data_values ) ? wp_json_encode( $hide_data_values ) : '';
		$dependency_data['data-relation'] = ! empty( $relation ) ? strtolower( $relation ) : 'and';

		return $dependency_data;
	}
}
