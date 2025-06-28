/**
 * WordPress dependencies
 */
const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';

/**
 * Register the SVG Animator block
 */
registerBlockType('advanced-svg-animator/svg-animator', {
	title: __('SVG Animator', 'advanced-svg-animator'),
	description: __('Insert and animate SVG files with predefined CSS animations and custom controls.', 'advanced-svg-animator'),
	category: 'media',
	icon: 'format-image',
	keywords: ['svg', 'animation', 'media', 'graphics'],
	supports: {
		html: false,
		align: ['left', 'center', 'right', 'wide', 'full'],
		spacing: {
			margin: true,
			padding: true
		},
		color: {
			background: true,
			text: false
		}
	},
	attributes: {
		svgId: {
			type: 'number',
			default: 0
		},
		svgUrl: {
			type: 'string',
			default: ''
		},
		svgContent: {
			type: 'string',
			default: ''
		},
		animationType: {
			type: 'string',
			default: 'none'
		},
		duration: {
			type: 'number',
			default: 1
		},
		delay: {
			type: 'number',
			default: 0
		},
		iterationCount: {
			type: 'string',
			default: '1'
		},
		timingFunction: {
			type: 'string',
			default: 'ease'
		},
		customCSSClass: {
			type: 'string',
			default: ''
		},
		width: {
			type: 'string',
			default: '300px'
		},
		height: {
			type: 'string',
			default: 'auto'
		},
		align: {
			type: 'string',
			default: 'center'
		},
		animationTrigger: {
			type: 'string',
			default: 'onLoad'
		}
	},
	edit,
	save,
});
