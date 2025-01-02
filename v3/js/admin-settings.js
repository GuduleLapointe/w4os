/**
 * Admin settings js
 * Scripts to fine tune settings pages forms
 */

document.addEventListener(
	'DOMContentLoaded',
	function () {
		// Toggle credential fields based on the value of "Use defaults" checkbox
		const defaultsCheckboxes = document.querySelectorAll( 'input.use-defaults' );
		defaultsCheckboxes.forEach(
			cb => {
            const container      = cb.closest( 'td' );
				function toggleFields() {
					container.querySelectorAll( '.w4os-credentials' ).forEach(
					div => {
						// Hide all credentials except the one containing the checkbox
						if ( ! div.classList.contains( 'credentials-use-defaults' )) {
							div.style.display = cb.checked ? 'none' : '';
						}
					}
					);
				}
				cb.addEventListener( 'change', toggleFields );
				toggleFields();
			}
		);

		const combineSelects            = document.querySelectorAll( '.select2-combined' );
		combineSelects.forEach(
			selectEl => {
				selectEl.addEventListener(
					'change',
					event => {
						const relatedId = selectEl.id.replace( '_dropdown', '' );
						const relatedEl = document.getElementById( relatedId );
						if (relatedEl) {
							relatedEl.value = event.target.value;
						}
					}
				);
			}
		);

		// Enable select2 for fields with class select2-field
		const select2Fields = document.querySelectorAll( '.select2-field' );
		select2Fields.forEach(
			selectEl => {
				jQuery( selectEl ).select2();
			}
		);

		document.querySelectorAll( '[data-modal-target]' ).forEach(
			trigger => {
				trigger.addEventListener(
					'click',
					event => {
						event.preventDefault();
						const dlg = document.getElementById( trigger.getAttribute( 'data-modal-target' ) );
						if (dlg) {
							dlg.showModal();
						}
					}
				);
			}
		);

		document.querySelectorAll( 'dialog.w4os-modal' ).forEach(
			dlg => {
				dlg.addEventListener(
					'click',
					evt => {
						if (evt.target === dlg) {
							closeModal();
						}
					}
				);
			}
		);
	}
);

function closeModal() {
	const dlg = document.querySelector( 'dialog[open]' );
	if (dlg) {
		dlg.close();
	}
}
