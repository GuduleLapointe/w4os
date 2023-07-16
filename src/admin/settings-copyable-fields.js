import "./settings-copyable-fields.scss";

jQuery( document ).ready(
	function($) {
		// Find all input fields inside elements with class 'copyable'
		$( '.copyable .rwmb-input input' ).each(
			function() {
				var inputField = $( this );
				var copyIcon   = $( '<span>', { class: 'dashicons dashicons-admin-page copy-icon', click: copyText } );

				inputField.addClass( 'form-control' ).before( copyIcon );
			}
		);

		function copyText() {
			var inputField = $( this ).next( '.form-control' );
			inputField.select();
			document.execCommand( 'copy' );
		}
	}
);
