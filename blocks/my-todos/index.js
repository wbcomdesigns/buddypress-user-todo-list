/**
 * Editor registration for the bptodo/my-todos block (buildless).
 *
 * Uses the global wp.* packages and ServerSideRender for the editor preview, so no
 * build step is required. The block is dynamic (server-rendered on the front end).
 *
 * @package bp-user-todo-list
 * @since   4.0.0
 */
( function ( blocks, element, blockEditor, components, serverSideRender, i18n ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var RangeControl = components.RangeControl;
	var SelectControl = components.SelectControl;
	var ToggleControl = components.ToggleControl;
	var ServerSideRender = serverSideRender;

	blocks.registerBlockType( 'bptodo/my-todos', {
		edit: function ( props ) {
			var atts = props.attributes;
			var setAttributes = props.setAttributes;

			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Todo list settings', 'wb-todo' ), initialOpen: true },
						el( RangeControl, {
							label: __( 'Items per page', 'wb-todo' ),
							value: atts.perPage,
							min: 1,
							max: 50,
							onChange: function ( v ) {
								setAttributes( { perPage: v } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Default status filter', 'wb-todo' ),
							value: atts.status,
							options: [
								{ label: __( 'All', 'wb-todo' ), value: 'all' },
								{ label: __( 'Pending', 'wb-todo' ), value: 'incomplete' },
								{ label: __( 'Completed', 'wb-todo' ), value: 'complete' },
							],
							onChange: function ( v ) {
								setAttributes( { status: v } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Default priority filter', 'wb-todo' ),
							value: atts.priority,
							options: [
								{ label: __( 'All', 'wb-todo' ), value: 'all' },
								{ label: __( 'Critical', 'wb-todo' ), value: 'critical' },
								{ label: __( 'High', 'wb-todo' ), value: 'high' },
								{ label: __( 'Normal', 'wb-todo' ), value: 'normal' },
							],
							onChange: function ( v ) {
								setAttributes( { priority: v } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Show quick-add box', 'wb-todo' ),
							checked: atts.showAdd,
							onChange: function ( v ) {
								setAttributes( { showAdd: v } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Show stat cards', 'wb-todo' ),
							checked: atts.showStats,
							onChange: function ( v ) {
								setAttributes( { showStats: v } );
							},
						} )
					)
				),
				el( ServerSideRender, {
					block: 'bptodo/my-todos',
					attributes: atts,
				} )
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
