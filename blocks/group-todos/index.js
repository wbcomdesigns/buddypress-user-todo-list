/**
 * Editor registration for bptodo/group-todos (buildless).
 *
 * @package bp-user-todo-list
 * @since   4.0.0
 */
( function ( blocks, element, blockEditor, components, serverSideRender, i18n ) {
	'use strict';
	var el = element.createElement;
	var __ = i18n.__;

	blocks.registerBlockType( 'bptodo/group-todos', {
		edit: function ( props ) {
			var atts = props.attributes;
			var set = props.setAttributes;
			return el(
				'div',
				blockEditor.useBlockProps(),
				el(
					blockEditor.InspectorControls,
					{},
					el(
						components.PanelBody,
						{ title: __( 'Group todos settings', 'wb-todo' ), initialOpen: true },
						el( components.TextControl, {
							label: __( 'Group ID (0 = current group)', 'wb-todo' ),
							type: 'number',
							value: atts.groupId,
							onChange: function ( v ) {
								set( { groupId: parseInt( v, 10 ) || 0 } );
							},
						} ),
						el( components.RangeControl, {
							label: __( 'Items per page', 'wb-todo' ),
							value: atts.perPage,
							min: 1,
							max: 50,
							onChange: function ( v ) {
								set( { perPage: v } );
							},
						} ),
						el( components.ToggleControl, {
							label: __( 'Show add box', 'wb-todo' ),
							checked: atts.showAdd,
							onChange: function ( v ) {
								set( { showAdd: v } );
							},
						} )
					)
				),
				el( serverSideRender, { block: 'bptodo/group-todos', attributes: atts } )
			);
		},
		save: function () {
			return null;
		},
	} );
} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.serverSideRender,
	window.wp.i18n
);
