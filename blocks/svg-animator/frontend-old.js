/**
 * Frontend JavaScript for SVG Animator block
 * Handles scroll-triggered animations and interactive features
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

		// Set up intersection observer for scroll-triggered animations
		const observer = new IntersectionObserver((entries) => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					triggerAnimation(entry.target);
				}
			});
		}, {
			threshold: 0.1,
			rootMargin: '0px 0px -50px 0px'
		});

		svgBlocks.forEach(block => {
			const svg = block.querySelector('svg[data-asa-animation]');
			if (svg) {
				setupSVGAnimation(svg, block);
				
				// Observe block for scroll animations
				observer.observe(block);
			}
		});
	}

	/**
	 * Set up SVG animation based on data attributes
	 */
	function setupSVGAnimation(svg, block) {
		const animationType = svg.getAttribute('data-asa-animation');
		const duration = svg.getAttribute('data-asa-duration') || '1';
		const delay = svg.getAttribute('data-asa-delay') || '0';
		const iteration = svg.getAttribute('data-asa-iteration') || '1';
		const timing = svg.getAttribute('data-asa-timing') || 'ease';

		// Set CSS custom properties for animation
		block.style.setProperty('--asa-duration', duration + 's');
		block.style.setProperty('--asa-delay', delay + 's');
		block.style.setProperty('--asa-iteration', iteration);
		block.style.setProperty('--asa-timing', timing);

		// Special handling for draw line animation
		if (animationType === 'drawLine') {
			setupDrawLineAnimation(svg);
		}

		// Initially hide animated elements until triggered
		if (animationType !== 'none') {
			svg.style.opacity = '0';
			svg.style.transform = getInitialTransform(animationType);
		}
	}

	/**
	 * Get initial transform for animation type
	 */
	function getInitialTransform(animationType) {
		switch (animationType) {
			case 'slideUp':
				return 'translateY(50px)';
			case 'slideDown':
				return 'translateY(-50px)';
			case 'slideLeft':
				return 'translateX(50px)';
			case 'slideRight':
				return 'translateX(-50px)';
			case 'scale':
				return 'scale(0)';
			case 'rotate':
				return 'rotate(0deg)';
			default:
				return 'none';
		}
	}

	/**
	 * Set up draw line animation for SVG paths
	 */
	function setupDrawLineAnimation(svg) {
		const paths = svg.querySelectorAll('path');
		
		paths.forEach(path => {
			const pathLength = path.getTotalLength();
			path.style.strokeDasharray = pathLength;
			path.style.strokeDashoffset = pathLength;
		});
	}

	/**
	 * Trigger animation for a block
	 */
	function triggerAnimation(block) {
		const svg = block.querySelector('svg[data-asa-animation]');
		if (!svg) return;

		const animationType = svg.getAttribute('data-asa-animation');
		
		if (animationType === 'none') return;

		// Reset styles and add animation class
		svg.style.opacity = '';
		svg.style.transform = '';
		
		// Add animation class
		svg.classList.add(`asa-animation-${animationType}`);

		// Handle animation end event
		svg.addEventListener('animationend', () => {
			handleAnimationEnd(svg, animationType);
		}, { once: true });
	}

	/**
	 * Handle animation end
	 */
	function handleAnimationEnd(svg, animationType) {
		// For finite animations, ensure final state is maintained
		const iteration = svg.getAttribute('data-asa-iteration');
		
		if (iteration === '1' || iteration === 'finite') {
			svg.style.opacity = '1';
			svg.style.transform = getFinalTransform(animationType);
		}
	}

	/**
	 * Get final transform for animation type
	 */
	function getFinalTransform(animationType) {
		switch (animationType) {
			case 'slideUp':
			case 'slideDown':
			case 'slideLeft':
			case 'slideRight':
				return 'translate(0, 0)';
			case 'scale':
				return 'scale(1)';
			case 'rotate':
				return 'rotate(360deg)';
			default:
				return 'none';
		}
	}

	/**
	 * Restart animations when requested
	 */
	function restartAnimations() {
		const svgBlocks = document.querySelectorAll('.svg-animator-block');
		
		svgBlocks.forEach(block => {
			const svg = block.querySelector('svg[data-asa-animation]');
			if (svg) {
				// Remove animation class
				svg.className = svg.className.replace(/asa-animation-\w+/g, '');
				
				// Reset styles
				const animationType = svg.getAttribute('data-asa-animation');
				svg.style.opacity = '0';
				svg.style.transform = getInitialTransform(animationType);
				
				// Re-trigger after a brief delay
				setTimeout(() => {
					triggerAnimation(block);
				}, 100);
			}
		});
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
