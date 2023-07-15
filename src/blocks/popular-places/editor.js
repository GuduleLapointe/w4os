var ServerSideRender  = wp.serverSideRender;
var registerBlockType = wp.blocks.registerBlockType;
var __                = wp.i18n.__;
var el                = wp.element.createElement;
var TextControl       = wp.components.TextControl;
var InspectorControls = wp.blockEditor.InspectorControls;
var PanelBody         = wp.components.PanelBody;
var SelectControl = wp.components.SelectControl;

registerBlockType(
	'w4os/popular-places',
	{
		title: __( 'OpenSimulator Popular Places', 'w4os' ),
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
			level: {
					type: 'string',
					default: 'h3',
			},
			max: {
				type: 'number',
				default: 5,
			},
		},
		edit: function(props) {
			var title         = props.attributes.title;
			var level         = props.attributes.level;
			var max           = props.attributes.max || 0;
			var setAttributes = props.setAttributes;

			function onChangeTitle(newTitle) {
				// setAttributes({ title: newTitle });
				setAttributes( { title: newTitle || undefined } );
			}

			function onChangelevel(newLevel) {
			    // const level = newLevel || 'h4';
			    setAttributes({ level: newLevel });
			}

			function onChangemax(newmax) {
				// Treat empty or less than zero value as 0
				var updatedmax = parseInt( newmax ) < 0 ? 0 : parseInt( newmax );
				setAttributes( { max: updatedmax } );
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
						el(
							TextControl,
							{
								label: __( 'Max Results', 'w4os' ),
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
						ServerSideRender,
						{
							block: 'w4os/popular-places',
							attributes: props.attributes,
							// LoadingResponsePlaceholder: function() {
							// 	return el(
							// 		'p',
							// 		{ className: 'loading-message' },
							// 		__('Building Popular Places block preview, please wait...', 'w4os'),
							// 	);
							// },
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
