/**
 * Adnet Ad Slot block — editor UI (pre-compiled, no JSX build step required).
 *
 * Registers the block in the Gutenberg editor using wp.blocks / wp.element globals.
 * The frontend is handled by render.php (server-side render).
 *
 * @package Rootz_AI_Discovery
 * @license GPL-2.0-or-later
 */
( function ( blocks, element, blockEditor, components, i18n ) {
	'use strict';

	var el             = element.createElement;
	var __             = i18n.__;
	var useBlockProps  = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody      = components.PanelBody;
	var TextControl    = components.TextControl;

	blocks.registerBlockType( 'rootz-ai-discovery/adnet-slot', {

		edit: function ( props ) {
			var attributes    = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps    = useBlockProps( {
				className: 'rootz-adnet-slot-editor',
				style: {
					background:    '#f0f6fc',
					border:        '2px dashed #0073aa',
					borderRadius:  '4px',
					padding:       '16px',
					textAlign:     'center',
					fontFamily:    'monospace',
					fontSize:      '13px',
					color:         '#1d2327',
				},
			} );

			return [
				el(
					InspectorControls,
					{ key: 'inspector' },
					el(
						PanelBody,
						{
							title: __( 'Adnet Settings', 'rootz-ai-discovery' ),
							initialOpen: true,
						},
						el( TextControl, {
							label: __( 'Campaign ID', 'rootz-ai-discovery' ),
							value: attributes.campaignId,
							onChange: function ( val ) {
								setAttributes( { campaignId: val } );
							},
							help: __( 'The Adnet campaign ID from your GeistM publisher dashboard.', 'rootz-ai-discovery' ),
						} ),
						el( TextControl, {
							label: __( 'Placeholder Text', 'rootz-ai-discovery' ),
							value: attributes.placeholderText,
							onChange: function ( val ) {
								setAttributes( { placeholderText: val } );
							},
							help: __( 'Screen-reader label for this ad slot.', 'rootz-ai-discovery' ),
						} )
					)
				),
				el(
					'div',
					Object.assign( {}, blockProps, { key: 'block' } ),
					el( 'span', { style: { fontSize: '18px', marginRight: '8px' } }, '📢' ),
					__( 'Adnet Ad Slot', 'rootz-ai-discovery' ),
					attributes.campaignId
						? el( 'span', { style: { color: '#0073aa', marginLeft: '8px' } }, '— ' + attributes.campaignId )
						: el( 'span', { style: { color: '#cc1818', marginLeft: '8px' } }, __( '(no campaign ID set)', 'rootz-ai-discovery' ) )
				),
			];
		},

		// save() returns null — render.php handles the frontend output.
		save: function () {
			return null;
		},
	} );

} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.i18n
);
