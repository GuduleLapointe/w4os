var ServerSideRender = wp.serverSideRender;

(function(wp) {
  var registerBlockType = wp.blocks.registerBlockType;
  var __ = wp.i18n.__;
  var el = wp.element.createElement;
  var TextControl = wp.components.TextControl;

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
				default: __('Popular Places'),
      },
    },
    edit: function(props) {
      var title = props.attributes.title;
      var setAttributes = props.setAttributes;

      function onChangeTitle(newTitle) {
        setAttributes({ title: newTitle });
      }

      return el(
        'div',
        { className: props.className },
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
          },
        })
      );
    },
    save: function() {
      // Empty save function as it's not used in this example
      return null;
    },
  });
})(window.wp);
