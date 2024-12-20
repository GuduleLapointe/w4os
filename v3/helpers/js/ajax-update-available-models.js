jQuery( document ).ready(
	function ($) {
		var previousMatch = $( 'input[name="w4os-avatars[models][match]"]:checked' ).val();
		var previousName  = $( '#name' ).val();
		var previousUuids = $( '#uuids' ).val();
		var reloadCount   = 0;
		var initialUpdate = true;

		// Function to update the available models content
		function updateAvailableModelsContent() {
			// Function to check if the field values have changed
			var currentMatch = $( 'input[name="w4os-avatars[models][match]"]:checked' ).val();
			var currentName  = $( '#name' ).val();
			var currentUuids = $( '#uuids' ).val();

			function hasFieldsChanged() {

				// Compare the current values with the previous values
				if (currentMatch !== previousMatch || currentName !== previousName || currentUuids.join( ',' ) !== previousUuids.join( ',' )) {
						previousMatch = currentMatch;
						previousName  = currentName;
						previousUuids = currentUuids;

						return true; // Fields have changed
				}

				return false; // Fields have not changed
			}

			if (hasFieldsChanged()) {
				var loadingMessage = w4osSettings.loadingMessage;
				$( '#w4os-models-preview-container' ).text( loadingMessage );

				var data = {
					action: w4osSettings.updateAction,
					nonce: w4osSettings.nonce,
					preview_match: currentMatch,
					preview_name: currentName,
					preview_uuids: currentUuids
				};

				// Perform the AJAX request
				$.post(
					w4osSettings.ajaxUrl,
					data,
					function (response) {
							$( '#w4os-models-preview-container' ).html( response );
					}
				);
			}
		}

		// Function to update the enabled/disabled state of fields based on 'match' value
		function updateFieldStates() {
			var currentMatch = $( 'input[name="w4os-avatars[models][match]"]:checked' ).val();

			if (currentMatch === 'uuid') {
				$( '#name' ).closest( 'tr' ).hide();
				$( '#uuids' ).closest( 'tr' ).show();
			} else {
				$( '#uuids' ).closest( 'tr' ).hide();
				$( '#name' ).closest( 'tr' ).show();
			}
		}

		// Call the function on initial load
		updateFieldStates();

		// Trigger the update function when the 'match' field value changes
		$( document ).on(
			'change',
			'input[name="w4os-avatars[models][match]"]',
			function () {
				updateFieldStates();
				updateAvailableModelsContent();
			}
		);

		// Trigger the update function when the 'name' field value changes
		$( document ).on(
			'input',
			'#name',
			function () {
				updateAvailableModelsContent();
			}
		);

		// Trigger the update function when the 'uuids' field value changes
		$( document ).on(
			'change',
			'#uuids',
			function () {
				updateAvailableModelsContent();
			}
		);
	}
);
