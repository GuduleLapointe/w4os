/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./src/blocks/popular-places/editor.js":
/*!*********************************************!*\
  !*** ./src/blocks/popular-places/editor.js ***!
  \*********************************************/
/***/ (() => {

var ServerSideRender = wp.serverSideRender;
var registerBlockType = wp.blocks.registerBlockType;
var __ = wp.i18n.__;
var el = wp.element.createElement;
var TextControl = wp.components.TextControl;
var InspectorControls = wp.blockEditor.InspectorControls;
var PanelBody = wp.components.PanelBody;
var SelectControl = wp.components.SelectControl;
registerBlockType('w4os/popular-places', {
  title: __('OpenSimulator Popular Places', 'w4os'),
  icon: 'location',
  category: 'widgets',
  supports: {
    html: false
  },
  attributes: {
    title: {
      type: 'string',
      default: ''
    },
    level: {
      type: 'string',
      default: 'h3'
    },
    max: {
      type: 'number',
      default: 5
    }
  },
  edit: function (props) {
    var title = props.attributes.title;
    var level = props.attributes.level;
    var max = props.attributes.max || 0;
    var setAttributes = props.setAttributes;
    function onChangeTitle(newTitle) {
      // setAttributes({ title: newTitle });
      setAttributes({
        title: newTitle || undefined
      });
    }
    function onChangelevel(newLevel) {
      // const level = newLevel || 'h4';
      setAttributes({
        level: newLevel
      });
    }
    function onChangemax(newmax) {
      // Treat empty or less than zero value as 0
      var updatedmax = parseInt(newmax) < 0 ? 0 : parseInt(newmax);
      setAttributes({
        max: updatedmax
      });
    }
    return el('div', {
      className: props.className
    }, el(InspectorControls, null, el(PanelBody, {
      title: __('Block Settings', 'w4os'),
      initialOpen: true
    }, el(TextControl, {
      label: __('Title', 'w4os'),
      value: title,
      onChange: onChangeTitle
    }), el(SelectControl, {
      label: __('Title Level', 'w4os'),
      value: level,
      options: [{
        label: 'H1',
        value: 'h1'
      }, {
        label: 'H2',
        value: 'h2'
      }, {
        label: 'H3',
        value: 'h3'
      }, {
        label: 'H4',
        value: 'h4'
      }, {
        label: 'H5',
        value: 'h5'
      }, {
        label: 'H6',
        value: 'h6'
      }, {
        label: 'P',
        value: 'p'
      }],
      onChange: onChangelevel
    }), el(TextControl, {
      label: __('Max Results', 'w4os'),
      type: 'number',
      value: max.toString(),
      onChange: onChangemax
    }))), el('div', {
      className: 'block-content'
    }, el(ServerSideRender, {
      block: 'w4os/popular-places',
      attributes: props.attributes
      // LoadingResponsePlaceholder: function() {
      // 	return el(
      // 		'p',
      // 		{ className: 'loading-message' },
      // 		__('Building Popular Places block preview, please wait...', 'w4os'),
      // 	);
      // },
    })));
  },

  save: function () {
    // Empty save function as it's not used in this example
    return null;
  }
});

/***/ }),

/***/ "./src/blocks/popular-places/index.scss":
/*!**********************************************!*\
  !*** ./src/blocks/popular-places/index.scss ***!
  \**********************************************/
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
/*!********************************************!*\
  !*** ./src/blocks/popular-places/index.js ***!
  \********************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _editor_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./editor.js */ "./src/blocks/popular-places/editor.js");
/* harmony import */ var _editor_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_editor_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _index_scss__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./index.scss */ "./src/blocks/popular-places/index.scss");


})();

/******/ })()
;
//# sourceMappingURL=popular-places.js.map