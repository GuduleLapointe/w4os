import "./settings-db-field.scss";

jQuery( document ).ready(
	function ($) {
		// Function to toggle subfield visibility based on "use_robot" checkbox
		function toggleSubfields($checkbox) {
			var $fieldset  = $checkbox.closest( '.w4osdb-field-group' );
			var $subfields = $fieldset.find( '.w4osdb-field:not(.db-field-use_default)' );

			if ($checkbox.prop( 'checked' )) {
				$subfields.hide();
			} else {
				$subfields.show();
			}
		}

		// Initial toggle when page loads
		$( '.w4osdb-field-group' ).each(
			function () {
				toggleSubfields( $( this ).find( '.db-field-use_default input[type="checkbox"]' ) );
			}
		);

		// Toggle subfields whenever "use_robot" checkbox changes within the same fieldset
		$( document ).on(
			'change',
			'.w4osdb-field-group .db-field-use_default input[type="checkbox"]',
			function () {
				toggleSubfields( $( this ) );
			}
		);
	}
);
