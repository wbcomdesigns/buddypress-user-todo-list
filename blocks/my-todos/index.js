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
						{ title: __( 'Todo list settings', 'bp-user-to-do-list' ), initialOpen: true },
						el( RangeControl, {
							label: __( 'Items per page', 'bp-user-to-do-list' ),
							value: atts.perPage,
							min: 1,
							max: 50,
							onChange: function ( v ) {
								setAttributes( { perPage: v } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Default status filter', 'bp-user-to-do-list' ),
							value: atts.status,
							options: [
								{ label: __( 'All', 'bp-user-to-do-list' ), value: 'all' },
								{ label: __( 'Pending', 'bp-user-to-do-list' ), value: 'incomplete' },
								{ label: __( 'Completed', 'bp-user-to-do-list' ), value: 'complete' },
							],
							onChange: function ( v ) {
								setAttributes( { status: v } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Default priority filter', 'bp-user-to-do-list' ),
							value: atts.priority,
							options: [
								{ label: __( 'All', 'bp-user-to-do-list' ), value: 'all' },
								{ label: __( 'Critical', 'bp-user-to-do-list' ), value: 'critical' },
								{ label: __( 'High', 'bp-user-to-do-list' ), value: 'high' },
								{ label: __( 'Normal', 'bp-user-to-do-list' ), value: 'normal' },
							],
							onChange: function ( v ) {
								setAttributes( { priority: v } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Show quick-add box', 'bp-user-to-do-list' ),
							checked: atts.showAdd,
							onChange: function ( v ) {
								setAttributes( { showAdd: v } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Show stat cards', 'bp-user-to-do-list' ),
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
