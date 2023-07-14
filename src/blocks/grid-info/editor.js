var ServerSideRender  = wp.serverSideRender;
var registerBlockType = wp.blocks.registerBlockType;
var __                = wp.i18n.__;
var el                = wp.element.createElement;
var TextControl       = wp.components.TextControl;
var InspectorControls = wp.blockEditor.InspectorControls;
var PanelBody         = wp.components.PanelBody;
var SelectControl = wp.components.SelectControl;

registerBlockType(
	'w4os/grid-info',
	{
		title: __( 'Grid Info', 'w4os' ),
		icon: 'user',
		category: 'widgets',
		supports: {
			// html: true,
			html: false,
		},
		attributes: {
			title: {
				type: 'string',
				default: '',
			},
			level: {
					type: 'string',
					default: 'h3',
			},
		},

		edit: function(props) {
			var title         = props.attributes.title;
			var level         = props.attributes.level;
			var setAttributes = props.setAttributes;

			function onChangeTitle(newTitle) {
				// setAttributes({ title: newTitle });
				setAttributes( { title: newTitle || undefined } );
			}

			function onChangelevel(newLevel) {
			    // const level = newLevel || 'h4';
			    setAttributes({ level: newLevel });
			}

			return el(
				'div',
				{ className: props.className },
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Block Settings', 'w4os' ), initialOpen: true },
						el(
							TextControl,
							{
								label: __( 'Title', 'w4os' ),
								value: title,
								onChange: onChangeTitle,
							}
						),
						el(
						    SelectControl,
						    {
						        label: __('Title Level', 'w4os'),
						        value: level,
						        options: [
						            { label: 'H1', value: 'h1' },
						            { label: 'H2', value: 'h2' },
						            { label: 'H3', value: 'h3' },
						            { label: 'H4', value: 'h4' },
						            { label: 'H5', value: 'h5' },
						            { label: 'H6', value: 'h6' },
						            { label: 'P', value: 'p' },
						        ],
						        onChange: onChangelevel,
						    }
						),
					)
				),
				el(
					'div',
					{ className: 'block-content' },
					el(
						ServerSideRender,
						{
							block: 'w4os/grid-info',
							attributes: props.attributes,
						}
					)
				)
			);
		},
		save: function() {
			// Empty save function as it's not used in this example
			return null;
		},
	}
);
