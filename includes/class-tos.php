<?php
/**
 * Add fields to registration forms
 *
 * @since      2.6.2
 * @package    GuduleLapointe/w4os
 * @subpackage w4os/includes
 */

class W4OS_Tos extends W4OS_Loader {
	public $tos_page_id;
	public $tos_link;
	private $tos_error;

	public function __construct() {

		$this->tos_page_id = get_option('w4os_tos_page_id');
		if($this->tos_page_id) {
			$this->tos_link = '<a href="' . get_permalink($this->tos_page_id) . '">' . get_the_title( $this->tos_page_id ). '</a>';
			$this->tos_agreement = sprintf(
				/* translators: %s: title and link to a page created by the user (gender- and number-neutral phrasing recommended) */
				__( 'I agree to the terms on page %s.', 'w4os' ),
				$this->tos_link,
			);
			$this->tos_error = '<strong>' . __('Error', 'w4os') . '</strong>: ' . sprintf(
				/* translators: %s: title and link to a page created by the user (gender- and number-neutral phrasing recommended) */
				__( 'You must agree to the terms on page %s.', 'w4os' ),
				$this->tos_link,
			);

			$this->actions = array(
				array(
					'hook'     => 'register_form',
					'callback' => 'tos_checkbox',
				),

				array(
					'hook'     => 'woocommerce_register_form',
					'callback' => 'wc_tos_checkbox',
				),
			);

			$this->filters = array(
				array(
					'hook'     => 'registration_errors',
					'callback' => 'tos_checkbox_validation',
				),

				array(
					'hook'     => 'woocommerce_registration_errors',
					'callback' => 'wc_tos_checkbox_validation',
				),
			);
		} else {
			$this->actions = array();
			$this->filters = array();
		}
	}

	function tos_checkbox() {
		echo '<p><label for="tos_confirm"><input type="checkbox" name="tos_confirm" id="tos_confirm" required> ' . $this->tos_agreement . '</label></p>';
	}

	function tos_checkbox_validation( $errors ) {
		if ( empty( $_POST['tos_confirm'] ) ) {
			$errors->add( 'tos_confirm_error', $this->tos_error );
		}
		return $errors;
	}

	// Add the checkbox to WooCommerce registration form
	function wc_tos_checkbox() {
		echo '<p class="form-row terms"><label for="tos_confirm" class="woocommerce-form__label woocommerce-form__label-for-checkbox"><input type="checkbox" class="woocommerce-form__input-checkbox" name="tos_confirm" id="tos_confirm" required> ' . $this->tos_agreement . '</label></p>';
	}

	// Validate the checkbox in WooCommerce registration
	function wc_tos_checkbox_validation( $errors ) {
		if ( empty( $_POST['tos_confirm'] ) ) {
			$errors->add( 'tos_confirm_error', $this->tos_error );
		}
		return $errors;
	}

}

$this->loaders[] = new W4OS_Tos();
