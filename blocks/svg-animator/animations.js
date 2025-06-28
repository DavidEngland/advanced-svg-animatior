/**
 * Animation definitions and helper functions for the SVG Animator block
 */

/**
 * Available animation types with their CSS properties
 */
export const ANIMATION_TYPES = [
	{
		label: 'Fade In',
		value: 'fadeIn',
		keyframes: `
			@keyframes asa-fadeIn {
				from { opacity: 0; }
				to { opacity: 1; }
			}
		`,
	},
	{
		label: 'Slide Up',
		value: 'slideUp',
		keyframes: `
			@keyframes asa-slideUp {
				from { transform: translateY(50px); opacity: 0; }
				to { transform: translateY(0); opacity: 1; }
			}
		`,
	},
	{
		label: 'Slide Down',
		value: 'slideDown',
		keyframes: `
			@keyframes asa-slideDown {
				from { transform: translateY(-50px); opacity: 0; }
				to { transform: translateY(0); opacity: 1; }
			}
		`,
	},
	{
		label: 'Slide Left',
		value: 'slideLeft',
		keyframes: `
			@keyframes asa-slideLeft {
				from { transform: translateX(50px); opacity: 0; }
				to { transform: translateX(0); opacity: 1; }
			}
		`,
	},
	{
		label: 'Slide Right',
		value: 'slideRight',
		keyframes: `
			@keyframes asa-slideRight {
				from { transform: translateX(-50px); opacity: 0; }
				to { transform: translateX(0); opacity: 1; }
			}
		`,
	},
	{
		label: 'Scale Up',
		value: 'scale',
		keyframes: `
			@keyframes asa-scale {
				from { transform: scale(0); opacity: 0; }
				to { transform: scale(1); opacity: 1; }
			}
		`,
	},
	{
		label: 'Rotate',
		value: 'rotate',
		keyframes: `
			@keyframes asa-rotate {
				from { transform: rotate(0deg); opacity: 0; }
				to { transform: rotate(360deg); opacity: 1; }
			}
		`,
	},
	{
		label: 'Bounce',
		value: 'bounce',
		keyframes: `
			@keyframes asa-bounce {
				0%, 20%, 53%, 80%, 100% {
					animation-timing-function: cubic-bezier(0.215, 0.610, 0.355, 1.000);
					transform: translate3d(0,0,0);
				}
				40%, 43% {
					animation-timing-function: cubic-bezier(0.755, 0.050, 0.855, 0.060);
					transform: translate3d(0, -30px, 0);
				}
				70% {
					animation-timing-function: cubic-bezier(0.755, 0.050, 0.855, 0.060);
					transform: translate3d(0, -15px, 0);
				}
				90% {
					transform: translate3d(0, -4px, 0);
				}
			}
		`,
	},
	{
		label: 'Draw Line (Basic)',
		value: 'drawLine',
		keyframes: `
			@keyframes asa-drawLine {
				from {
					stroke-dashoffset: 1000;
				}
				to {
					stroke-dashoffset: 0;
				}
			}
		`,
		special: 'svg-path',
	},
	{
		label: 'Draw SVG Paths (Advanced)',
		value: 'drawSVGPaths',
		keyframes: `
			@keyframes asa-drawSVGPaths {
				from {
					stroke-dashoffset: var(--path-length, 1000);
				}
				to {
					stroke-dashoffset: 0;
				}
			}
		`,
		special: 'svg-paths-calculated',
		requiresJS: true,
		description: 'Calculates actual path lengths for precise drawing animation'
	},
	{
		label: 'Morph Path',
		value: 'morphPath',
		keyframes: `
			@keyframes asa-morphPath {
				0% {
					d: var(--path-from, path('M0,0 L100,100'));
				}
				100% {
					d: var(--path-to, path('M0,0 L200,50'));
				}
			}
		`,
		special: 'svg-morph',
		requiresJS: true,
		experimental: true,
		description: 'Morphs between different path shapes (experimental)'
	},
];

/**
 * Available timing functions
 */
export const TIMING_FUNCTIONS = [
	{ label: 'Ease', value: 'ease' },
	{ label: 'Linear', value: 'linear' },
	{ label: 'Ease In', value: 'ease-in' },
	{ label: 'Ease Out', value: 'ease-out' },
	{ label: 'Ease In Out', value: 'ease-in-out' },
	{ label: 'Cubic Bezier (Bounce)', value: 'cubic-bezier(0.68, -0.55, 0.265, 1.55)' },
	{ label: 'Cubic Bezier (Back)', value: 'cubic-bezier(0.175, 0.885, 0.32, 1.275)' },
];

/**
 * Generate CSS for animations
 */
export function getAnimationCSS(animationType, duration = 1, delay = 0, iterationCount = 1, timingFunction = 'ease') {
	const animation = ANIMATION_TYPES.find(type => type.value === animationType);
	
	if (!animation) {
		return '';
	}

	const animationName = `asa-${animationType}`;
	
	let css = animation.keyframes + '\n';
	
	css += `
		.asa-animation-${animationType} {
			animation-name: ${animationName};
			animation-duration: ${duration}s;
			animation-delay: ${delay}s;
			animation-iteration-count: ${iterationCount};
			animation-timing-function: ${timingFunction};
			animation-fill-mode: both;
		}
	`;

	// Special handling for SVG path animations
	if (animation.special === 'svg-path') {
		css += `
			.asa-animation-${animationType} path {
				stroke-dasharray: 1000;
				stroke-dashoffset: 1000;
				animation-name: ${animationName};
				animation-duration: ${duration}s;
				animation-delay: ${delay}s;
				animation-iteration-count: ${iterationCount};
				animation-timing-function: ${timingFunction};
				animation-fill-mode: both;
			}
		`;
	}

	return css;
}

/**
 * Generate all animation CSS for inclusion in the frontend
 */
export function getAllAnimationCSS() {
	let allCSS = '';
	
	ANIMATION_TYPES.forEach(animation => {
		allCSS += animation.keyframes + '\n';
		
		const animationName = `asa-${animation.value}`;
		
		allCSS += `
			.asa-animation-${animation.value} {
				animation-name: ${animationName};
				animation-duration: var(--asa-duration, 1s);
				animation-delay: var(--asa-delay, 0s);
				animation-iteration-count: var(--asa-iteration, 1);
				animation-timing-function: var(--asa-timing, ease);
				animation-fill-mode: both;
			}
		`;

		// Special handling for SVG path animations
		if (animation.special === 'svg-path') {
			allCSS += `
				.asa-animation-${animation.value} path {
					stroke-dasharray: 1000;
					stroke-dashoffset: 1000;
					animation-name: ${animationName};
					animation-duration: var(--asa-duration, 1s);
					animation-delay: var(--asa-delay, 0s);
					animation-iteration-count: var(--asa-iteration, 1);
					animation-timing-function: var(--asa-timing, ease);
					animation-fill-mode: both;
				}
			`;
		}
	});

	return allCSS;
}

/**
 * Default animation settings
 */
export const DEFAULT_ANIMATION_SETTINGS = {
	duration: 1,
	delay: 0,
	iterationCount: '1',
	timingFunction: 'ease',
	fillMode: 'both',
};

/**
 * Animation trigger types
 */
export const TRIGGER_TYPES = [
	{ label: 'On Load', value: 'onLoad' },
	{ label: 'On Scroll', value: 'onScroll' },
	{ label: 'On Hover', value: 'onHover' },
	{ label: 'On Click', value: 'onClick' },
];

/**
 * SVG element preparation helper
 */
export function prepareSVGForAnimation(svgElement, animationType, settings = {}) {
	if (!svgElement) return;

	switch (animationType) {
		case 'drawSVGPaths':
			prepareSVGPaths(svgElement, settings);
			break;
		case 'drawLine':
			prepareBasicSVGPaths(svgElement, settings);
			break;
		case 'morphPath':
			prepareMorphPaths(svgElement, settings);
			break;
	}
}

/**
 * Prepare SVG paths for advanced drawing animation
 */
function prepareSVGPaths(svgElement, settings) {
	const { targetSelector = 'path', drawSVGSettings = {} } = settings;
	const paths = targetSelector === 'path' 
		? svgElement.querySelectorAll('path')
		: svgElement.querySelectorAll(targetSelector);

	paths.forEach((path, index) => {
		// Calculate actual path length
		const pathLength = path.getTotalLength();
		
		// Set stroke properties if not present
		if (!path.getAttribute('stroke') && drawSVGSettings.strokeColor) {
			path.setAttribute('stroke', drawSVGSettings.strokeColor);
		}
		if (!path.getAttribute('stroke-width') && drawSVGSettings.strokeWidth) {
			path.setAttribute('stroke-width', drawSVGSettings.strokeWidth);
		}
		
		// Set up dash array and offset
		path.style.strokeDasharray = pathLength;
		path.style.strokeDashoffset = pathLength;
		path.style.setProperty('--path-length', pathLength);
		
		// Add stagger delay if enabled
		if (settings.advancedSettings?.staggerDelay && index > 0) {
			path.style.animationDelay = `${settings.delay + (index * settings.advancedSettings.staggerDelay)}s`;
		}
	});
}

/**
 * Prepare SVG paths for basic drawing animation
 */
function prepareBasicSVGPaths(svgElement, settings) {
	const paths = svgElement.querySelectorAll('path');
	
	paths.forEach(path => {
		path.style.strokeDasharray = '1000';
		path.style.strokeDashoffset = '1000';
	});
}

/**
 * Prepare paths for morphing animation (experimental)
 */
function prepareMorphPaths(svgElement, settings) {
	const { targetSelector = 'path' } = settings;
	const paths = svgElement.querySelectorAll(targetSelector);
	
	paths.forEach(path => {
		const currentPath = path.getAttribute('d');
		path.style.setProperty('--path-from', `path('${currentPath}')`);
		
		// For now, create a simple morph target
		// In a real implementation, this would come from user settings
		const morphedPath = createMorphTarget(currentPath);
		path.style.setProperty('--path-to', `path('${morphedPath}')`);
	});
}

/**
 * Create a simple morph target (placeholder implementation)
 */
function createMorphTarget(originalPath) {
	// This is a simplified example - real morphing requires complex path interpolation
	return originalPath.replace(/(\d+)/g, (match) => {
		const num = parseInt(match);
		return Math.max(0, num + (Math.random() - 0.5) * 50).toString();
	});
}

/**
 * Apply target selector to SVG element
 */
export function applyTargetSelector(svgElement, animationClass, targetSelector) {
	if (!targetSelector || targetSelector === '') {
		// Apply to entire SVG
		svgElement.classList.add(animationClass);
		return;
	}
	
	try {
		// Apply to specific elements within SVG
		const targetElements = svgElement.querySelectorAll(targetSelector);
		if (targetElements.length > 0) {
			targetElements.forEach(element => {
				element.classList.add(animationClass);
			});
		} else {
			// Fallback to entire SVG if selector doesn't match anything
			console.warn(`Target selector "${targetSelector}" didn't match any elements. Applying to entire SVG.`);
			svgElement.classList.add(animationClass);
		}
	} catch (error) {
		console.error(`Invalid target selector "${targetSelector}":`, error);
		// Fallback to entire SVG
		svgElement.classList.add(animationClass);
	}
}

/**
 * Get animation trigger setup
 */
export function getAnimationTriggerSetup(trigger, element, callback, options = {}) {
	switch (trigger) {
		case 'onLoad':
			// Immediate execution
			setTimeout(callback, options.delay || 0);
			break;
			
		case 'onScroll':
			return setupScrollTrigger(element, callback, options);
			
		case 'onHover':
			return setupHoverTrigger(element, callback, options);
			
		case 'onClick':
			return setupClickTrigger(element, callback, options);
			
		default:
			console.warn(`Unknown trigger type: ${trigger}`);
			setTimeout(callback, options.delay || 0);
	}
}

/**
 * Setup scroll-based animation trigger
 */
function setupScrollTrigger(element, callback, options) {
	const { scrollThreshold = 0.1, scrollOffset = 100 } = options;
	
	const observer = new IntersectionObserver((entries) => {
		entries.forEach(entry => {
			if (entry.isIntersecting && entry.intersectionRatio >= scrollThreshold) {
				callback();
				observer.unobserve(element); // Trigger only once
			}
		});
	}, {
		threshold: scrollThreshold,
		rootMargin: `0px 0px -${scrollOffset}px 0px`
	});
	
	observer.observe(element);
	
	return () => observer.disconnect();
}

/**
 * Setup hover-based animation trigger
 */
function setupHoverTrigger(element, callback, options) {
	const { hoverTarget = 'svg' } = options;
	let targetElement = element;
	
	if (hoverTarget === 'block') {
		targetElement = element.closest('.svg-animator-block') || element;
	} else if (hoverTarget === 'custom' && options.customHoverTarget) {
		targetElement = document.querySelector(options.customHoverTarget) || element;
	}
	
	const handleMouseEnter = () => {
		callback();
	};
	
	targetElement.addEventListener('mouseenter', handleMouseEnter);
	
	return () => targetElement.removeEventListener('mouseenter', handleMouseEnter);
}

/**
 * Setup click-based animation trigger
 */
function setupClickTrigger(element, callback, options) {
	const handleClick = () => {
		callback();
	};
	
	element.addEventListener('click', handleClick);
	element.style.cursor = 'pointer';
	
	return () => {
		element.removeEventListener('click', handleClick);
		element.style.cursor = '';
	};
}
