( function( wp ) {
	/**
	 * Registers a new block provided a unique name and an object defining its behavior.
	 *
	 * @see https://wordpress.org/gutenberg/handbook/designers-developers/developers/block-api/#registering-a-block
	 */
	var registerBlockType = wp.blocks.registerBlockType;
	/**
	 * Returns a new element of given type. Element is an abstraction layer atop React.
	 *
	 * @see https://wordpress.org/gutenberg/handbook/designers-developers/developers/packages/packages-element/
	 */
	var el = wp.element.createElement;
	/**
	 * Retrieves the translation of text.
	 *
	 * @see https://wordpress.org/gutenberg/handbook/designers-developers/developers/packages/packages-i18n/
	 */
	var __ = wp.i18n.__;

	/**
	 * Every block starts by registering a new block type definition.
	 *
	 * @see https://wordpress.org/gutenberg/handbook/designers-developers/developers/block-api/#registering-a-block
	 */
	registerBlockType(
		'w4os/w4os-gridprofile-block',
		{
			/**
			 * This is the display title for your block, which can be translated with `i18n` functions.
			 * The block inserter will show this name.
			 */
			title: __( 'Grid profile', 'w4os' ),

			/**
			 * Blocks are grouped into categories to help users browse and discover them.
			 * The categories provided by core are `common`, `embed`, `formatting`, `layout` and `widgets`.
			 */
			category: 'widgets',

			/**
			 * Optional block extended support features.
			 */
			supports: {
				// Removes support for an HTML mode.
				html: true,
			},

			/**
			 * The edit function describes the structure of your block in the context of the editor.
			 * This represents what the editor will render when the block is used.
			 *
			 * @see https://wordpress.org/gutenberg/handbook/designers-developers/developers/block-api/block-edit-save/#edit
			 *
			 * @param {Object} [props] Properties passed from the editor.
			 * @return {Element}       Element to render.
			 */
			edit: function ( props ) {
				// var blockProps = useBlockProps();
				return el(
					'div',
					props,
					el(
						ServerSideRender,
						{
							block: 'w4os/w4os-gridprofile-block',
							attributes: props.attributes,
							}
					)
				);
			},

			/**
			 * The save function defines the way in which the different attributes should be combined
			 * into the final markup, which is then serialized by Gutenberg into `post_content`.
			 *
			 * @see https://wordpress.org/gutenberg/handbook/designers-developers/developers/block-api/block-edit-save/#save
			 *
			 * @return {Element}       Element to render.
			 */
			save: function() {
				return el(
					'div',
					{},
					__( 'Grid profile', 'w4os' )
				);
			}
			//
			// edit: ( { attributes, setAttributes } ) => {
			// const blockProps = useBlockProps();
			// const updateFieldValue = ( val ) => {
			// setAttributes( { content: val } );
			// }
			// return (
			// <div { ...blockProps }>
			// <TextControl
			// label='My Text Field'
			// value={ attributes.content }
			// onChange={ updateFieldValue }
			// >
			// </p>
			// );
			// },
			//
			// save: ( { attributes } ) => {
			// const blockProps = useBlockProps.save();
			//
			// return <div { ...blockProps }> { attributes.content } </div>;
			// },

		}
	);
} )(
	window.wp
);
