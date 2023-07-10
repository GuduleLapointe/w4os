jQuery(document).ready(function($) {
  // Function to toggle subfield visibility based on "use_robot" checkbox
  function toggleSubfields($checkbox) {
    var $fieldset = $checkbox.closest('.rwmb-field.rwmb-w4osdb_field_type-wrapper');
    var $subfields = $fieldset.find('.rwmb-input > .w4osdb-field:not(.db-field-use_default)');

    if ($checkbox.prop('checked')) {
      $subfields.hide();
    } else {
      $subfields.show();
    }
  }

  // Initial toggle when page loads
  $('.rwmb-field.rwmb-w4osdb_field_type-wrapper').each(function() {
    toggleSubfields($(this).find('.db-field-use_default input[type="checkbox"]'));
  });

  // Toggle subfields whenever "use_robot" checkbox changes within the same fieldset
  $(document).on('change', '.rwmb-field.rwmb-w4osdb_field_type-wrapper .db-field-use_default input[type="checkbox"]', function() {
    toggleSubfields($(this));
  });
});


function valueChanged() {

	// show internal or external assets server uri according to provide checkbox
	document.getElementById( "w4os_internal_asset_server_uri" ).parentNode.parentNode.style.display = document.getElementById( 'w4os_provide_asset_server' ).checked ? "table-row" : "none";
	document.getElementById( "w4os_external_asset_server_uri" ).parentNode.parentNode.style.display = document.getElementById( 'w4os_provide_asset_server' ).checked ? "none" : "table-row";

	// show internal offline helper uri according to provide checkbox
	document.getElementById( "w4os_offline_helper_uri" ).parentNode.parentNode.style.display = document.getElementById( 'w4os_provide_offline_messages' ).checked ? "table-row" : "none";

	// show internal economy helper uri according to provide checkbox
	document.getElementById( "w4os_economy_helper_uri" ).parentNode.parentNode.style.display    = document.getElementById( 'w4os_provide_economy_helpers' ).checked ? "table-row" : "none";
	document.getElementById( "w4os_economy_use_default_db" ).parentNode.parentNode.style.display = document.getElementById( 'w4os_provide_economy_helpers' ).checked ? "table-row" : "none";
	document.getElementById( "w4os_economy_db_host" ).parentNode.parentNode.style.display       = document.getElementById( 'w4os_provide_economy_helpers' ).checked & ! document.getElementById( 'w4os_economy_use_default_db' ).checked ? "table-row" : "none";
	document.getElementById( "w4os_economy_db_database" ).parentNode.parentNode.style.display   = document.getElementById( 'w4os_provide_economy_helpers' ).checked & ! document.getElementById( 'w4os_economy_use_default_db' ).checked ? "table-row" : "none";
	document.getElementById( "w4os_economy_db_user" ).parentNode.parentNode.style.display       = document.getElementById( 'w4os_provide_economy_helpers' ).checked & ! document.getElementById( 'w4os_economy_use_default_db' ).checked ? "table-row" : "none";
	document.getElementById( "w4os_economy_db_pass" ).parentNode.parentNode.style.display       = document.getElementById( 'w4os_provide_economy_helpers' ).checked & ! document.getElementById( 'w4os_economy_use_default_db' ).checked ? "table-row" : "none";
	document.getElementById( "w4os_podex_redirect_url" ).parentNode.parentNode.style.display    = document.getElementById( 'w4os_provide_economy_helpers' ).checked && document.getElementById( 'w4os_currency_provider_podex' ).checked ? "table-row" : "none";
	document.getElementById( "w4os_podex_error_message" ).parentNode.parentNode.style.display   = document.getElementById( 'w4os_provide_economy_helpers' ).checked && document.getElementById( 'w4os_currency_provider_podex' ).checked ? "table-row" : "none";
	document.getElementById( "w4os_currency_provider_" ).parentNode.parentNode.style.display    = document.getElementById( 'w4os_provide_economy_helpers' ).checked ? "table-row" : "none";

	document.getElementById( "w4os_money_script_access_key" ).parentNode.parentNode.style.display = document.getElementById( 'w4os_provide_economy_helpers' ).checked && document.getElementById( 'w4os_currency_provider_' ).checked ? "table-row" : "none";
	document.getElementById( "w4os_currency_rate" ).parentNode.parentNode.style.display           = document.getElementById( 'w4os_provide_economy_helpers' ).checked & ! document.getElementById( 'w4os_currency_provider_gloebit' ).checked ? "table-row" : "none";

	document.getElementById( "w4os_search_use_default_db" ).parentNode.parentNode.style.display = document.getElementById( 'w4os_provide_search' ).checked ? "table-row" : "none";
	document.getElementById( "w4os_search_db_host" ).parentNode.parentNode.style.display       = document.getElementById( 'w4os_provide_search' ).checked & ! document.getElementById( 'w4os_search_use_default_db' ).checked ? "table-row" : "none";
	document.getElementById( "w4os_search_db_database" ).parentNode.parentNode.style.display   = document.getElementById( 'w4os_provide_search' ).checked & ! document.getElementById( 'w4os_search_use_default_db' ).checked ? "table-row" : "none";
	document.getElementById( "w4os_search_db_user" ).parentNode.parentNode.style.display       = document.getElementById( 'w4os_provide_search' ).checked & ! document.getElementById( 'w4os_search_use_default_db' ).checked ? "table-row" : "none";
	document.getElementById( "w4os_search_db_pass" ).parentNode.parentNode.style.display       = document.getElementById( 'w4os_provide_search' ).checked & ! document.getElementById( 'w4os_search_use_default_db' ).checked ? "table-row" : "none";

	document.getElementById( "w4os_hypevents_url" ).parentNode.parentNode.style.display = document.getElementById( 'w4os_provide_search' ).checked ? "table-row" : "none";

	// document.getElementById("w4os_search_url").readonly = document.getElementById('w4os_provide_search').checked;
	// document.getElementById("w4os_search_url").setAttribute("readonly", document.getElementById('w4os_provide_search').checked);
	// document.getElementById("w4os_search_url").disabled = document.getElementById('w4os_provide_search').checked;

	if (document.getElementById( 'w4os_provide_search' ).checked) {
		document.getElementById( "w4os_search_url" ).setAttribute( "readonly", "readonly" );
		document.getElementById( "w4os_search_register" ).setAttribute( "readonly", "readonly" );
	} else {
		document.getElementById( "w4os_search_url" ).removeAttribute( "readonly" );
		document.getElementById( "w4os_search_register" ).removeAttribute( "readonly" );
	}
}
// force check on load
window.onload = function() {
	// Code that depends on external scripts being loaded goes here...
	valueChanged(); // Call the function after all scripts have loaded
};

// /*
// * Try to autofill grid info when login uri is updated.
// * Abandoned for now, requires a workaround for CORS cross-origin limitation
// */
// autoFillLink = document.getElementById('fillFromGrid-link');
// autoFillLink.addEventListener("click", function(e) {
// e.preventDefault();
//
// loginURI = document.getElementById("w4os_login_uri").value;
// if(loginURI == '') {
// loginURI = 'http://localhost:8002';
// }
// var get_grid_info = new XMLHttpRequest();
// get_grid_info.open("GET", loginURI + '/get_grid_info', true);
// get_grid_info.onreadystatechange = function () {
// if (get_grid_info.readyState == 4 && get_grid_info.status == 200)
// {
// var grid_info = get_grid_info.responseXML;
// var gridName = grid_info.evaluate('//gridinfo/gridname/text()', doc, null, 0, null).iterateNext();
// document.getElementById("w4os_grid_name").setAttribute('value',gridName);
// var loginURI = grid_info.evaluate('//gridinfo/login/text()', doc, null, 0, null).iterateNext();
// document.getElementById("w4os_login_uri").setAttribute('value',loginURI);
// }
// };
// get_grid_info.send(null);
//
// }, false);
