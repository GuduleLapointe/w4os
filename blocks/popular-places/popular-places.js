/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!********************************************!*\
  !*** ./src/blocks/popular-places/index.js ***!
  \********************************************/
var ServerSideRender = wp.serverSideRender;
(function (wp) {
  var registerBlockType = wp.blocks.registerBlockType;
  var __ = wp.i18n.__;
  const {
    createElement
  } = wp.element;
  const {
    InspectorControls
  } = wp.blockEditor;
  const {
    PanelBody,
    TextControl
  } = wp.components;
  var el = wp.element.createElement;
  registerBlockType('w4os/popular-places', {
    title: __('Popular Places', 'w4os'),
    icon: 'location',
    category: 'widgets',
    supports: {
      html: false
    },
    edit: function (props) {
      const {
        attributes,
        setAttributes
      } = props;
      return el('div', props, el(TextControl, {
        label: __('Title', 'w4os'),
        value: attributes.title,
        onChange: newTitle => setAttributes({
          title: newTitle
        })
      }), el(ServerSideRender, {
        block: 'w4os/popular-places',
        attributes: props.attributes
      }));
    },
    save: function (props) {
      return el('div', props, __('Popular Places', 'w4os'));
    }
  });
})(window.wp);
/******/ })()
;
//# sourceMappingURL=popular-places.js.map