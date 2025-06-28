/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Save component for SVG Animator block
 */
export default function Save({ attributes }) {
	const {
		svgId,
		svgUrl,
		svgContent,
		animationType,
		duration,
		delay,
		iterationCount,
		timingFunction,
		customCSSClass,
		width,
		height,
		align,
		targetSelector,
		animationTrigger,
		scrollOffset,
		scrollThreshold,
		hoverTarget,
		drawSVGSettings,
		advancedSettings,
	} = attributes;

	if (!svgContent || !svgId) {
		return null;
	}

	const blockProps = useBlockProps.save({
		className: `svg-animator-block align${align} ${customCSSClass}`,
		'data-svg-id': svgId,
		'data-svg-url': svgUrl,
		'data-animation-type': animationType,
		'data-animation-trigger': animationTrigger,
		'data-target-selector': targetSelector,
		'data-duration': duration,
		'data-delay': delay,
		'data-iteration-count': iterationCount,
		'data-timing-function': timingFunction,
		'data-scroll-offset': scrollOffset,
		'data-scroll-threshold': scrollThreshold,
		'data-hover-target': hoverTarget,
		'data-draw-svg-settings': JSON.stringify(drawSVGSettings),
		'data-advanced-settings': JSON.stringify(advancedSettings),
		style: {
			'--animation-duration': `${duration}s`,
			'--animation-delay': `${delay}s`,
			'--animation-iteration-count': iterationCount,
			'--animation-timing-function': timingFunction,
		},
	});

	/**
	 * Process SVG content for frontend rendering
	 */
	const processSVGContent = () => {
		try {
			// Parse SVG content
			const parser = new DOMParser();
			const svgDoc = parser.parseFromString(svgContent, 'image/svg+xml');
			const svgElement = svgDoc.querySelector('svg');

			if (svgElement) {
				// Add animation class if needed
				if (animationType !== 'none') {
					// Apply animation class based on target selector or to the SVG itself
					if (targetSelector) {
						// Add data attribute for frontend JS to handle targeting
						svgElement.setAttribute('data-asa-target-selector', targetSelector);
					}
					svgElement.classList.add(`asa-animation-${animationType}`);
				}

				// Set dimensions
				if (width && width !== 'auto') {
					svgElement.setAttribute('width', width);
				}
				if (height && height !== 'auto') {
					svgElement.setAttribute('height', height);
				}

				// Add data attributes for frontend JavaScript
				svgElement.setAttribute('data-asa-animation', animationType);
				svgElement.setAttribute('data-asa-trigger', animationTrigger);
				svgElement.setAttribute('data-asa-target-selector', targetSelector || '');
				svgElement.setAttribute('data-asa-duration', duration);
				svgElement.setAttribute('data-asa-delay', delay);
				svgElement.setAttribute('data-asa-iteration', iterationCount);
				svgElement.setAttribute('data-asa-timing', timingFunction);
				svgElement.setAttribute('data-asa-scroll-offset', scrollOffset);
				svgElement.setAttribute('data-asa-scroll-threshold', scrollThreshold);
				svgElement.setAttribute('data-asa-hover-target', hoverTarget);
				
				// Add advanced settings as JSON
				if (drawSVGSettings && Object.keys(drawSVGSettings).length > 0) {
					svgElement.setAttribute('data-asa-draw-settings', JSON.stringify(drawSVGSettings));
				}
				if (advancedSettings && Object.keys(advancedSettings).length > 0) {
					svgElement.setAttribute('data-asa-advanced-settings', JSON.stringify(advancedSettings));
				}

				return svgElement.outerHTML;
			}
		} catch (error) {
			console.error('Error processing SVG content:', error);
		}

		return svgContent;
	};

	return (
		<div {...blockProps}>
			<div 
				className="svg-animator-container"
				dangerouslySetInnerHTML={{ __html: processSVGContent() }}
			/>
		</div>
	);
}
