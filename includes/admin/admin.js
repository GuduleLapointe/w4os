/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./src/admin/settings-copyable-fields.js":
/*!***********************************************!*\
  !*** ./src/admin/settings-copyable-fields.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _settings_copyable_fields_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./settings-copyable-fields.scss */ "./src/admin/settings-copyable-fields.scss");

jQuery(document).ready(function ($) {
  // Find all input fields inside elements with class 'copyable'
  $('.copyable .rwmb-input input').each(function () {
    var inputField = $(this);
    var copyIcon = $('<span>', {
      class: 'dashicons dashicons-admin-page copy-icon',
      click: copyText
    });
    inputField.addClass('form-control').before(copyIcon);
  });
  function copyText() {
    var inputField = $(this).next('.form-control');
    inputField.select();
    document.execCommand('copy');
  }
});

/***/ }),

/***/ "./src/admin/settings.js":
/*!*******************************!*\
  !*** ./src/admin/settings.js ***!
  \*******************************/
/***/ (() => {

jQuery(document).ready(function ($) {
  var provideSearch = $('#w4os_provide_search');
  var searchURLField = $('#w4os_search_url');
  var registerURLField = $('#w4os_search_register');
  var helpersInternal = provideSearch.data('helpers-internal');
  var helpersExternal = provideSearch.data('helpers-external');

  // Get the current values of searchURLField and registerURLField
  var searchURLValue = searchURLField.val();
  if (searchURLValue === '') {
    searchURLValue = helpersExternal + 'query.php';
  }
  var registerURLValue = registerURLField.val();
  if (registerURLValue === '') {
    registerURLValue = helpersExternal + 'register.php';
  }
  provideSearch.on('change', function () {
    var isProvideChecked = provideSearch.prop('checked');
    var readonly = false;
    if (isProvideChecked) {
      searchURLField.prop('readonly', true).val(helpersInternal + 'query.php');
      registerURLField.prop('readonly', true).val(helpersInternal + 'register.php');
    } else {
      searchURLField.prop('readonly', false).val(searchURLValue);
      registerURLField.prop('readonly', false).val(registerURLValue);
    }
  }).trigger('change'); // Trigger the change event on page load
});

jQuery(document).ready(function ($) {
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
  $('.rwmb-field.rwmb-w4osdb_field_type-wrapper').each(function () {
    toggleSubfields($(this).find('.db-field-use_default input[type="checkbox"]'));
  });

  // Toggle subfields whenever "use_robot" checkbox changes within the same fieldset
  $(document).on('change', '.rwmb-field.rwmb-w4osdb_field_type-wrapper .db-field-use_default input[type="checkbox"]', function () {
    toggleSubfields($(this));
  });
});
function valueChanged() {
  // show internal or external assets server uri according to provide checkbox
  document.getElementById("w4os_internal_asset_server_uri").parentNode.parentNode.style.display = document.getElementById('w4os_provide_asset_server').checked ? "table-row" : "none";
  document.getElementById("w4os_external_asset_server_uri").parentNode.parentNode.style.display = document.getElementById('w4os_provide_asset_server').checked ? "none" : "table-row";

  // show internal offline helper uri according to provide checkbox
  document.getElementById("w4os_offline_helper_uri").parentNode.parentNode.style.display = document.getElementById('w4os_provide_offline_messages').checked ? "table-row" : "none";

  // show internal economy helper uri according to provide checkbox
  document.getElementById("w4os_economy_helper_uri").parentNode.parentNode.style.display = document.getElementById('w4os_provide_economy_helpers').checked ? "table-row" : "none";
  document.getElementById("w4os_economy_use_default_db").parentNode.parentNode.style.display = document.getElementById('w4os_provide_economy_helpers').checked ? "table-row" : "none";
  document.getElementById("w4os_economy_db_host").parentNode.parentNode.style.display = document.getElementById('w4os_provide_economy_helpers').checked & !document.getElementById('w4os_economy_use_default_db').checked ? "table-row" : "none";
  document.getElementById("w4os_economy_db_database").parentNode.parentNode.style.display = document.getElementById('w4os_provide_economy_helpers').checked & !document.getElementById('w4os_economy_use_default_db').checked ? "table-row" : "none";
  document.getElementById("w4os_economy_db_user").parentNode.parentNode.style.display = document.getElementById('w4os_provide_economy_helpers').checked & !document.getElementById('w4os_economy_use_default_db').checked ? "table-row" : "none";
  document.getElementById("w4os_economy_db_pass").parentNode.parentNode.style.display = document.getElementById('w4os_provide_economy_helpers').checked & !document.getElementById('w4os_economy_use_default_db').checked ? "table-row" : "none";
  document.getElementById("w4os_podex_redirect_url").parentNode.parentNode.style.display = document.getElementById('w4os_provide_economy_helpers').checked && document.getElementById('w4os_currency_provider_podex').checked ? "table-row" : "none";
  document.getElementById("w4os_podex_error_message").parentNode.parentNode.style.display = document.getElementById('w4os_provide_economy_helpers').checked && document.getElementById('w4os_currency_provider_podex').checked ? "table-row" : "none";
  document.getElementById("w4os_currency_provider_").parentNode.parentNode.style.display = document.getElementById('w4os_provide_economy_helpers').checked ? "table-row" : "none";
  document.getElementById("w4os_money_script_access_key").parentNode.parentNode.style.display = document.getElementById('w4os_provide_economy_helpers').checked && document.getElementById('w4os_currency_provider_').checked ? "table-row" : "none";
  document.getElementById("w4os_currency_rate").parentNode.parentNode.style.display = document.getElementById('w4os_provide_economy_helpers').checked & !document.getElementById('w4os_currency_provider_gloebit').checked ? "table-row" : "none";
  document.getElementById("w4os_search_use_default_db").parentNode.parentNode.style.display = document.getElementById('w4os_provide_search').checked ? "table-row" : "none";
  document.getElementById("w4os_search_db_host").parentNode.parentNode.style.display = document.getElementById('w4os_provide_search').checked & !document.getElementById('w4os_search_use_default_db').checked ? "table-row" : "none";
  document.getElementById("w4os_search_db_database").parentNode.parentNode.style.display = document.getElementById('w4os_provide_search').checked & !document.getElementById('w4os_search_use_default_db').checked ? "table-row" : "none";
  document.getElementById("w4os_search_db_user").parentNode.parentNode.style.display = document.getElementById('w4os_provide_search').checked & !document.getElementById('w4os_search_use_default_db').checked ? "table-row" : "none";
  document.getElementById("w4os_search_db_pass").parentNode.parentNode.style.display = document.getElementById('w4os_provide_search').checked & !document.getElementById('w4os_search_use_default_db').checked ? "table-row" : "none";
  document.getElementById("w4os_hypevents_url").parentNode.parentNode.style.display = document.getElementById('w4os_provide_search').checked ? "table-row" : "none";

  // document.getElementById("w4os_search_url").readonly = document.getElementById('w4os_provide_search').checked;
  // document.getElementById("w4os_search_url").setAttribute("readonly", document.getElementById('w4os_provide_search').checked);
  // document.getElementById("w4os_search_url").disabled = document.getElementById('w4os_provide_search').checked;

  if (document.getElementById('w4os_provide_search').checked) {
    document.getElementById("w4os_search_url").setAttribute("readonly", "readonly");
    document.getElementById("w4os_search_register").setAttribute("readonly", "readonly");
  } else {
    document.getElementById("w4os_search_url").removeAttribute("readonly");
    document.getElementById("w4os_search_register").removeAttribute("readonly");
  }
}
// force check on load
window.onload = function () {
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

/***/ }),

/***/ "./src/admin/admin.scss":
/*!******************************!*\
  !*** ./src/admin/admin.scss ***!
  \******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/admin/models.scss":
/*!*******************************!*\
  !*** ./src/admin/models.scss ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/admin/settings-copyable-fields.scss":
/*!*************************************************!*\
  !*** ./src/admin/settings-copyable-fields.scss ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/admin/settings.scss":
/*!*********************************!*\
  !*** ./src/admin/settings.scss ***!
  \*********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
(() => {
"use strict";
/*!****************************!*\
  !*** ./src/admin/index.js ***!
  \****************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _settings_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./settings.scss */ "./src/admin/settings.scss");
/* harmony import */ var _settings_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./settings.js */ "./src/admin/settings.js");
/* harmony import */ var _settings_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_settings_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _settings_copyable_fields_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./settings-copyable-fields.js */ "./src/admin/settings-copyable-fields.js");
/* harmony import */ var _admin_scss__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./admin.scss */ "./src/admin/admin.scss");
/* harmony import */ var _models_scss__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./models.scss */ "./src/admin/models.scss");





})();

/******/ })()
;
//# sourceMappingURL=admin.js.map