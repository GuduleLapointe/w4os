function valueChanged() {
  // show internal or external assets server uri according to provide checkbox
  document.getElementById("w4os_internal_asset_server_uri").parentNode.parentNode.style.display = document.getElementById('w4os_provide_asset_server').checked ? "table-row" : "none";
  document.getElementById("w4os_external_asset_server_uri").parentNode.parentNode.style.display = document.getElementById('w4os_provide_asset_server').checked ? "none" : "table-row";

  // show internal offline helper uri according to provide checkbox
  // document.getElementById("w4os_offline_helper_uri").parentNode.parentNode.style.display = document.getElementById('w4os_provide_offline_messages').checked ? "table-row" : "none";

  // show internal economy helper uri according to provide checkbox
  document.getElementById("w4os_economy_helper_uri").parentNode.parentNode.style.display = document.getElementById('w4os_provide_economy_helpers').checked ? "table-row" : "none";
  document.getElementById("w4os_economy_use_robust_db").parentNode.parentNode.style.display = document.getElementById('w4os_provide_economy_helpers').checked ? "table-row" : "none";
  document.getElementById("w4os_economy_db_host").parentNode.parentNode.style.display = document.getElementById('w4os_provide_economy_helpers').checked &! document.getElementById('w4os_economy_use_robust_db').checked ? "table-row" : "none";
  document.getElementById("w4os_economy_db_database").parentNode.parentNode.style.display = document.getElementById('w4os_provide_economy_helpers').checked &! document.getElementById('w4os_economy_use_robust_db').checked ? "table-row" : "none";
  document.getElementById("w4os_economy_db_user").parentNode.parentNode.style.display = document.getElementById('w4os_provide_economy_helpers').checked &! document.getElementById('w4os_economy_use_robust_db').checked ? "table-row" : "none";
  document.getElementById("w4os_economy_db_pass").parentNode.parentNode.style.display = document.getElementById('w4os_provide_economy_helpers').checked &! document.getElementById('w4os_economy_use_robust_db').checked ? "table-row" : "none";
}
// force check on load
valueChanged();
w4os_economy_db_host
// /*
//  * Try to autofill grid info when login uri is updated.
//  * Abandoned for now, requires a workaround for CORS cross-origin limitation
//  */
// autoFillLink = document.getElementById('fillFromGrid-link');
// autoFillLink.addEventListener("click", function(e) {
//   e.preventDefault();
//
//   loginURI = document.getElementById("w4os_login_uri").value;
//   if(loginURI == '') {
//     loginURI = 'http://localhost:8002';
//   }
//   var get_grid_info = new XMLHttpRequest();
//   get_grid_info.open("GET", loginURI + '/get_grid_info', true);
//   get_grid_info.onreadystatechange = function () {
//     if (get_grid_info.readyState == 4 && get_grid_info.status == 200)
//     {
//       var grid_info = get_grid_info.responseXML;
//       var gridName = grid_info.evaluate('//gridinfo/gridname/text()', doc, null, 0, null).iterateNext();
//       document.getElementById("w4os_grid_name").setAttribute('value',gridName);
//       var loginURI = grid_info.evaluate('//gridinfo/login/text()', doc, null, 0, null).iterateNext();
//       document.getElementById("w4os_login_uri").setAttribute('value',loginURI);
//     }
//   };
//   get_grid_info.send(null);
//
// }, false);
