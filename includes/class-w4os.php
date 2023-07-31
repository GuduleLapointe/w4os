<?php
/**
 * Define main methods and constants for the plugin
 *
 * @package    GuduleLapointe/w4os
 * @link       https://github.com/magicoli/w4os
 * @since      2.5.1
 */

class W4OS {

	public static function get_original_post_id( $post_id = null ) {
		if ( empty( $post_id ) ) {
			$post_id = get_the_id();
		}

		// Check for WPML
		if ( function_exists( 'icl_object_id' ) ) {
			// Get the original post ID using WPML's icl_object_id function
			$default_language = apply_filters( 'wpml_default_language', null );
			$original_post_id = icl_object_id( $post_id, 'post', false, $default_language );

			// If the original post ID is different, return it; otherwise, return the current post ID
			return ( $original_post_id !== $post_id ) ? $original_post_id : $post_id;
		}

		// Check for Polylang
		if ( function_exists( 'pll_get_post' ) ) {
			global $polylang;
			$languages = $polylang->model->get_languages_list();

			$default_language = $polylang->default_lang;
			$original_post_id = $post_id;

			if ( isset( $languages[ $default_language ] ) && $languages[ $default_language ]['slug'] !== get_locale() ) {
				// Get the Polylang translation relationships
				$translations = $polylang->model->post->get_translations( $post_id );

				// Check if the original post ID exists in the translations
				if ( isset( $translations[ $default_language ] ) ) {
					$original_post_id = $translations[ $default_language ];
				}
			}

			// Return the original post ID
			return $original_post_id;
		}

		// If no translation plugin is active or supported, return the current post ID
		return $post_id;
	}

	public static function sprintf_safe( $format, ...$args ) {
		try {
			// Attempt to format the string using sprintf
			$result = sprintf( $format, ...$args );

			// Restore the previous error handler
			restore_error_handler();

			return $result;
		} catch ( Throwable $e ) {
			// Log an error or handle the situation gracefully
			error_log( "Error W4OS::sprintf_safe( $format, " . join( ', ', $args ) . '): ' . $e->getMessage() );

			// Fallback: Return the format string with placeholders intact
			restore_error_handler();

			return $format;
		}
	}

}
