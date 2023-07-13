var ServerSideRender = wp.serverSideRender;
var registerBlockType = wp.blocks.registerBlockType;
var __ = wp.i18n.__;
var el = wp.element.createElement;
var TextControl = wp.components.TextControl;
var InspectorControls = wp.blockEditor.InspectorControls;
var PanelBody = wp.components.PanelBody;

registerBlockType('w4os/popular-places', {
  title: __('Popular Places', 'w4os'),
  icon: 'location',
  category: 'widgets',
  supports: {
    html: false,
  },
  attributes: {
    title: {
      type: 'string',
      default: '',
    },
    max: {
      type: 'number',
      default: 5,
    },
  },
  edit: function(props) {
    var title = props.attributes.title;
    var max = props.attributes.max || 0;
    var setAttributes = props.setAttributes;

    function onChangeTitle(newTitle) {
      // setAttributes({ title: newTitle });
      setAttributes({ title: newTitle || undefined });
    }

    function onChangemax(newmax) {
      // Treat empty or less than zero value as 0
      var updatedmax = parseInt(newmax) < 0 ? 0 : parseInt(newmax);
      setAttributes({ max: updatedmax });
    }

    return el(
      'div',
      { className: props.className },
      el(
        InspectorControls,
        null,
        el(
          PanelBody,
          { title: __('Block Settings', 'w4os'), initialOpen: true },
          el(
            TextControl,
            {
              label: __('Max Results', 'w4os'),
              type: 'number',
              value: max.toString(),
              onChange: onChangemax,
            }
          )
        )
      ),
      el(
        'div',
        { className: 'block-content' },
        el(
          TextControl,
          {
            label: __('Title', 'w4os'),
            value: title,
            onChange: onChangeTitle,
          }
        ),
        el(ServerSideRender, {
          block: 'w4os/popular-places',
          attributes: {
            title: title,
            max: max,
          },
        })
      )
    );
  },
  save: function() {
    // Empty save function as it's not used in this example
    return null;
  },
});
