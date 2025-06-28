/**
 * Frontend JavaScript for SVG Animator block
 * Handles scroll-triggered animations, hover triggers, element targeting, and interactive features
 */

(function() {
	'use strict';

	/**
	 * Initialize SVG animations on page load
	 */
	function initSVGAnimations() {
		const svgBlocks = document.querySelectorAll('.svg-animator-block');
		
		if (svgBlocks.length === 0) {
			return;
		}

		// Check if user prefers reduced motion
		const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		
		if (prefersReducedMotion) {
			// Remove all animations if user prefers reduced motion
			svgBlocks.forEach(block => {
				const animatedElements = block.querySelectorAll('[class*="asa-animation-"]');
				animatedElements.forEach(element => {
					element.style.animation = 'none';
				});
			});
			return;
		}

		// Process each SVG block
		svgBlocks.forEach(block => {
			const svg = block.querySelector('svg[data-asa-animation]');
			if (svg) {
				setupSVGAnimation(svg, block);
			}
		});
	}

	/**
	 * Set up SVG animation based on data attributes and trigger
	 */
	function setupSVGAnimation(svg, block) {
		const animationType = svg.getAttribute('data-asa-animation');
		const trigger = svg.getAttribute('data-asa-trigger') || 'onLoad';
		const targetSelector = svg.getAttribute('data-asa-target-selector');
		const duration = svg.getAttribute('data-asa-duration') || '1';
		const delay = svg.getAttribute('data-asa-delay') || '0';
		const iteration = svg.getAttribute('data-asa-iteration') || '1';
		const timing = svg.getAttribute('data-asa-timing') || 'ease';
		const scrollOffset = parseInt(svg.getAttribute('data-asa-scroll-offset')) || 100;
		const scrollThreshold = parseFloat(svg.getAttribute('data-asa-scroll-threshold')) || 0.1;
		const hoverTarget = svg.getAttribute('data-asa-hover-target') || 'svg';

		// Parse advanced settings
		let drawSettings = {};
		let advancedSettings = {};
		
		try {
			const drawSettingsData = svg.getAttribute('data-asa-draw-settings');
			if (drawSettingsData) {
				drawSettings = JSON.parse(drawSettingsData);
			}
			
			const advancedSettingsData = svg.getAttribute('data-asa-advanced-settings');
			if (advancedSettingsData) {
				advancedSettings = JSON.parse(advancedSettingsData);
			}
		} catch (error) {
			console.warn('ASA: Failed to parse settings', error);
		}

		// Set CSS custom properties for animation
		block.style.setProperty('--asa-duration', duration + 's');
		block.style.setProperty('--asa-delay', delay + 's');
		block.style.setProperty('--asa-iteration', iteration);
		block.style.setProperty('--asa-timing', timing);

		// Prepare SVG for animation (especially for draw animations)
		prepareSVGForAnimation(svg, animationType, drawSettings, targetSelector);

		// Set up trigger-specific behavior
		switch (trigger) {
			case 'onLoad':
				// Trigger animation immediately
				setTimeout(() => {
					triggerAnimation(svg, block, animationType, targetSelector);
				}, parseFloat(delay) * 1000);
				break;

			case 'onScroll':
				setupScrollTrigger(svg, block, animationType, targetSelector, scrollOffset, scrollThreshold);
				break;

			case 'onHover':
				setupHoverTrigger(svg, block, animationType, targetSelector, hoverTarget);
				break;

			case 'onClick':
				setupClickTrigger(svg, block, animationType, targetSelector);
				break;

			default:
				// Fallback to onLoad
				setTimeout(() => {
					triggerAnimation(svg, block, animationType, targetSelector);
				}, parseFloat(delay) * 1000);
				break;
		}
	}

	/**
	 * Prepare SVG for animation (handle draw animations and element targeting)
	 */
	function prepareSVGForAnimation(svg, animationType, drawSettings, targetSelector) {
		// Special handling for draw SVG animations
		if (animationType === 'drawSVGPaths') {
			prepareDrawSVGAnimation(svg, drawSettings, targetSelector);
		}

		// Handle initial state for different animation types
		const elementsToAnimate = getTargetElements(svg, targetSelector);
		
		elementsToAnimate.forEach(element => {
			// Set initial state based on animation type
			switch (animationType) {
				case 'fadeIn':
					element.style.opacity = '0';
					break;
				case 'slideUp':
					element.style.transform = 'translateY(50px)';
					element.style.opacity = '0';
					break;
				case 'slideDown':
					element.style.transform = 'translateY(-50px)';
					element.style.opacity = '0';
					break;
				case 'slideLeft':
					element.style.transform = 'translateX(50px)';
					element.style.opacity = '0';
					break;
				case 'slideRight':
					element.style.transform = 'translateX(-50px)';
					element.style.opacity = '0';
					break;
				case 'scale':
					element.style.transform = 'scale(0)';
					element.style.opacity = '0';
					break;
				case 'rotate':
					element.style.transform = 'rotate(0deg)';
					element.style.opacity = '0';
					break;
				case 'bounce':
					element.style.opacity = '0';
					break;
				case 'drawSVGPaths':
					// Already handled by prepareDrawSVGAnimation
					break;
			}
		});
	}

	/**
	 * Prepare SVG for draw animation
	 */
	function prepareDrawSVGAnimation(svg, drawSettings, targetSelector) {
		const paths = targetSelector ? 
			svg.querySelectorAll(targetSelector) : 
			svg.querySelectorAll('path, line, circle, ellipse, rect, polyline, polygon');

		paths.forEach((path, index) => {
			let pathLength;
			
			if (path.getTotalLength) {
				pathLength = path.getTotalLength();
			} else {
				// Fallback for elements without getTotalLength()
				pathLength = estimatePathLength(path);
			}

			// Set stroke properties
			path.style.stroke = drawSettings.strokeColor || '#333333';
			path.style.strokeWidth = drawSettings.strokeWidth || '2';
			path.style.fill = 'none';
			
			// Set up dash array for draw effect
			path.style.strokeDasharray = pathLength;
			path.style.strokeDashoffset = drawSettings.drawDirection === 'reverse' ? -pathLength : pathLength;
			
			// Add stagger delay if specified
			if (drawSettings.simultaneousPaths === false && index > 0) {
				path.style.animationDelay = `${index * 0.2}s`;
			}
		});
	}

	/**
	 * Estimate path length for elements without getTotalLength()
	 */
	function estimatePathLength(element) {
		const bbox = element.getBoundingClientRect();
		// Simple estimation based on bounding box
		return (bbox.width + bbox.height) * 2;
	}

	/**
	 * Get target elements based on selector
	 */
	function getTargetElements(svg, targetSelector) {
		if (targetSelector) {
			const elements = svg.querySelectorAll(targetSelector);
			return elements.length > 0 ? Array.from(elements) : [svg];
		}
		return [svg];
	}

	/**
	 * Set up scroll trigger for animation
	 */
	function setupScrollTrigger(svg, block, animationType, targetSelector, offset, threshold) {
		// Initially hide elements
		const elementsToAnimate = getTargetElements(svg, targetSelector);
		elementsToAnimate.forEach(element => {
			element.classList.add('asa-scroll-hidden');
		});

		// Set up intersection observer
		const observer = new IntersectionObserver((entries) => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					triggerAnimation(svg, block, animationType, targetSelector);
					observer.unobserve(entry.target);
				}
			});
		}, {
			threshold: threshold,
			rootMargin: `0px 0px -${offset}px 0px`
		});

		observer.observe(block);
	}

	/**
	 * Set up hover trigger for animation
	 */
	function setupHoverTrigger(svg, block, animationType, targetSelector, hoverTarget) {
		let triggerElement;
		
		switch (hoverTarget) {
			case 'block':
				triggerElement = block;
				break;
			case 'svg':
				triggerElement = svg;
				break;
			case 'custom':
				// For custom, use the targetSelector as hover target
				triggerElement = targetSelector ? block.querySelector(targetSelector) : svg;
				break;
			default:
				triggerElement = svg;
		}

		if (triggerElement) {
			triggerElement.addEventListener('mouseenter', () => {
				triggerAnimation(svg, block, animationType, targetSelector);
			});

			// Optional: Reset on mouse leave for repeatable animations
			triggerElement.addEventListener('mouseleave', () => {
				const elementsToAnimate = getTargetElements(svg, targetSelector);
				elementsToAnimate.forEach(element => {
					element.classList.remove(`asa-animation-${animationType}`);
					// Reset to initial state
					setTimeout(() => {
						prepareSVGForAnimation(svg, animationType, {}, targetSelector);
					}, 50);
				});
			});
		}
	}

	/**
	 * Set up click trigger for animation
	 */
	function setupClickTrigger(svg, block, animationType, targetSelector) {
		svg.style.cursor = 'pointer';
		
		svg.addEventListener('click', () => {
			triggerAnimation(svg, block, animationType, targetSelector);
		});
	}

	/**
	 * Trigger the animation
	 */
	function triggerAnimation(svg, block, animationType, targetSelector) {
		const elementsToAnimate = getTargetElements(svg, targetSelector);
		
		elementsToAnimate.forEach((element, index) => {
			// Remove hidden class if present
			element.classList.remove('asa-scroll-hidden');
			
			// Add animation class
			element.classList.add(`asa-animation-${animationType}`);
			
			// For draw animations, animate the stroke-dashoffset
			if (animationType === 'drawSVGPaths') {
				element.style.strokeDashoffset = '0';
			}
		});
	}

	/**
	 * Restart all animations in the page
	 */
	function restartAnimations() {
		const svgBlocks = document.querySelectorAll('.svg-animator-block');
		
		svgBlocks.forEach(block => {
			const animatedElements = block.querySelectorAll('[class*="asa-animation-"]');
			animatedElements.forEach(element => {
				// Remove animation classes
				const classes = Array.from(element.classList);
				classes.forEach(className => {
					if (className.startsWith('asa-animation-')) {
						element.classList.remove(className);
					}
				});
			});
		});
		
		// Re-initialize after a brief delay
		setTimeout(initSVGAnimations, 100);
	}

	/**
	 * Pause/resume animations
	 */
	function toggleAnimations(pause = false) {
		const animatedElements = document.querySelectorAll('[class*="asa-animation-"]');
		
		animatedElements.forEach(element => {
			element.style.animationPlayState = pause ? 'paused' : 'running';
		});
	}

	/**
	 * Expose public API
	 */
	window.ASVGAnimator = {
		restart: restartAnimations,
		pause: () => toggleAnimations(true),
		resume: () => toggleAnimations(false),
		init: initSVGAnimations
	};

	/**
	 * Initialize when DOM is ready
	 */
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initSVGAnimations);
	} else {
		initSVGAnimations();
	}

	/**
	 * Re-initialize animations when new content is loaded dynamically
	 */
	document.addEventListener('DOMNodeInserted', function(event) {
		if (event.target.classList && event.target.classList.contains('svg-animator-block')) {
			setTimeout(initSVGAnimations, 100);
		}
	});

	/**
	 * Handle window resize for responsive animations
	 */
	let resizeTimeout;
	window.addEventListener('resize', function() {
		clearTimeout(resizeTimeout);
		resizeTimeout = setTimeout(() => {
			// Recalculate animations if needed
			initSVGAnimations();
		}, 250);
	});

})();
