<?php
/**
 * Social Snap settings fields class.
 *
 * This class contains static functions that are common for the fields.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Fields {

	/**
	 * Build complete HTML output of a field.
	 *
	 * @since 1.0.0
	 * @param array $field
	 * @return string, HTML output of the field
	 */
	public static function build_field( $field ) {

		// Check if the field type is set
		if ( ! isset( $field['type'] ) ) {
			return;
		}

		$field_type       = $field['type'];
		$field_class      = 'SocialSnap_Field_' . $field_type;
		$field_class_file = SOCIALSNAP_PLUGIN_DIR . 'includes/admin/settings/fields/field_' . $field_type . '.php';

		do_action( 'socialsnap_settings_field_class' );

		// Load the class if it wasn't loaded yet
		if ( ! class_exists( $field_class ) && file_exists( $field_class_file ) ) {
			require_once $field_class_file;
		}

		$field_instance = new $field_class( $field );

		return $field_instance->render();
	}

	/**
	 * Get the field value from options or return default
	 *
	 * @since 1.0.0
	 */
	public static function saved_value( $options, $field ) {

		$saved_options = get_option( $options );

		if ( isset( $saved_options[ $field['id'] ] ) ) {
			return $saved_options[ $field['id'] ];
		} elseif ( isset( $field['default'] ) ) {
			return $field['default'];
		} else {
			return '';
		}
	}


	/**
	 * Returns data attributes if the field has dependency on other fields.
	 *
	 * @var
	 * @since 1.0.0
	 * @return boolean, true if the field is a subgroup
	 */
	public static function dependency_builder( $dependency, $echo = true ) {

		// No dependency
		if ( ! isset( $dependency ) || empty( $dependency ) ) {
			return false;
		}

		$output  = isset( $dependency['element'] ) ? ' data-dependency-mother="' . esc_attr( $dependency['element'] ) . '" ' : '';
		$output .= isset( $dependency['value'] ) ? ' data-dependency-value=\'' . esc_attr( wp_json_encode( $dependency['value'] ) ) . '\' ' : '';

		if ( $echo ) {
			echo $output; // phpcs:ignore
		} else {
			return $output;
		}
	}

}
