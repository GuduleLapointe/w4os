jQuery( function( $ ) {
	const $section = $( '#mbup-confirm-section' );

	function clearContent(){
		$section.empty();
	}
	function addContent( res ){
		$section.html( '<em>' + res.data + '</em>' );
	}

	function adminSendComfirmEmail() {
		$.ajax( {
			url: window.ajaxurl,
			type: 'POST',
			cache: false,
			data: {
				action: 'send_confirmation_email',
				_wpnonce: MBUP_ADMIN.nonce,
				uid: $(this).data( 'uid' ),
				email: $(this).data( 'email' ),
				username: $(this).data( 'username' ),
			}
		} ).done( function( response ) {
			clearContent();
			addContent( response );
		} );
	}
	$section.on( 'click', '.mbup-confirm-btn', adminSendComfirmEmail );
} );