/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	BlockControls,
	AlignmentControl,
} from '@wordpress/block-editor';
import {
	PanelBody,
	Button,
	SelectControl,
	RangeControl,
	TextControl,
	ToggleControl,
	Notice,
	Placeholder,
	Spinner,
	ToolbarGroup,
	ToolbarButton,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { media, replace } from '@wordpress/icons';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { 
	getAnimationCSS, 
	ANIMATION_TYPES, 
	TIMING_FUNCTIONS, 
	TRIGGER_TYPES,
	prepareSVGForAnimation,
	applyTargetSelector,
	getAnimationTriggerSetup
} from './animations';

/**
 * Edit component for SVG Animator block
 */
export default function Edit({ attributes, setAttributes, clientId }) {
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

	const [isLoading, setIsLoading] = useState(false);
	const [error, setError] = useState(null);
	const [animationKey, setAnimationKey] = useState(0); // Force re-render for preview
	const [cleanupTrigger, setCleanupTrigger] = useState(null);

	const blockProps = useBlockProps({
		className: `svg-animator-block align${align} ${customCSSClass}`,
		style: {
			'--animation-duration': `${duration}s`,
			'--animation-delay': `${delay}s`,
			'--animation-iteration-count': iterationCount,
			'--animation-timing-function': timingFunction,
		},
	});

	/**
	 * Fetch SVG content from WordPress media
	 */
	const fetchSVGContent = async (attachmentId) => {
		setIsLoading(true);
		setError(null);
		
		try {
			// Use our custom REST endpoint to get SVG content
			const response = await apiFetch({
				path: `/advanced-svg-animator/v1/svg-content/${attachmentId}`,
			});

			if (response && response.content) {
				// Validate it's actually SVG content
				if (response.content.includes('<svg')) {
					setAttributes({
						svgUrl: response.url,
						svgContent: response.content,
					});
				} else {
					setError(__('Selected file is not a valid SVG.', 'advanced-svg-animator'));
				}
			}
		} catch (err) {
			console.error('SVG fetch error:', err);
			setError(__('Failed to load SVG content. Please ensure you have permission to access SVG files.', 'advanced-svg-animator'));
		} finally {
			setIsLoading(false);
		}
	};

	/**
	 * Handle media selection
	 */
	const onSelectMedia = (media) => {
		if (media && media.id) {
			setAttributes({
				svgId: media.id,
				svgUrl: media.url,
			});
			fetchSVGContent(media.id);
		}
	};

	/**
	 * Handle media removal
	 */
	const onRemoveMedia = () => {
		setAttributes({
			svgId: 0,
			svgUrl: '',
			svgContent: '',
		});
		setError(null);
	};

	/**
	 * Preview animation (for editor)
	 */
	const previewAnimation = () => {
		if (animationType === 'none') return;
		
		// Force re-render to restart animation
		setAnimationKey(prev => prev + 1);
		
		// Clean up previous trigger
		if (cleanupTrigger) {
			cleanupTrigger();
		}
		
		// Set up new trigger for preview
		setTimeout(() => {
			const container = document.querySelector(`[data-block-id="${clientId}"] svg`);
			if (container && animationTrigger !== 'onLoad') {
				const cleanup = getAnimationTriggerSetup(
					animationTrigger,
					container,
					() => {
						// Animation callback for preview
						console.log(`Preview: ${animationType} animation triggered by ${animationTrigger}`);
					},
					{
						scrollThreshold,
						scrollOffset,
						hoverTarget,
						delay: delay * 1000,
					}
				);
				setCleanupTrigger(() => cleanup);
			}
		}, 100);
	};

	/**
	 * Effect to handle animation changes
	 */
	useEffect(() => {
		if (svgContent && animationType !== 'none') {
			previewAnimation();
		}
		
		return () => {
			if (cleanupTrigger) {
				cleanupTrigger();
			}
		};
	}, [animationType, targetSelector, animationTrigger, duration, delay, iterationCount, timingFunction]);

	/**
	 * Get current animation type details
	 */
	const getCurrentAnimationType = () => {
		return ANIMATION_TYPES.find(type => type.value === animationType);
	};

	/**
	 * Render SVG content with animation
	 */
	const renderSVG = () => {
		if (!svgContent) return null;

		// Parse SVG content and add animation classes
		const parser = new DOMParser();
		const svgDoc = parser.parseFromString(svgContent, 'image/svg+xml');
		const svgElement = svgDoc.querySelector('svg');

		if (svgElement) {
			// Set dimensions
			if (width && width !== 'auto') {
				svgElement.setAttribute('width', width);
			}
			if (height && height !== 'auto') {
				svgElement.setAttribute('height', height);
			}

			// Add unique ID for this block instance
			svgElement.setAttribute('data-block-id', clientId);
			svgElement.setAttribute('data-animation-key', animationKey);

			// Prepare SVG for specific animation types
			if (animationType !== 'none') {
				prepareSVGForAnimation(svgElement, animationType, {
					targetSelector,
					drawSVGSettings,
					advancedSettings,
					duration,
					delay,
				});

				// Apply animation class to target elements
				const animationClass = `asa-animation-${animationType}`;
				applyTargetSelector(svgElement, animationClass, targetSelector);

				// Add trigger-specific classes for styling
				svgElement.setAttribute('data-trigger', animationTrigger);
			}

			return (
				<div 
					className="svg-animator-container"
					dangerouslySetInnerHTML={{ __html: svgElement.outerHTML }}
					key={animationKey} // Force re-render when animation changes
				/>
			);
		}

		return null;
	};

	/**
	 * Render media placeholder
	 */
	const renderPlaceholder = () => (
		<Placeholder
			icon={media}
			label={__('SVG Animator', 'advanced-svg-animator')}
			instructions={__(
				'Select an SVG file from your media library to get started.',
				'advanced-svg-animator'
			)}
		>
			<MediaUploadCheck>
				<MediaUpload
					onSelect={onSelectMedia}
					allowedTypes={['image/svg+xml']}
					value={svgId}
					render={({ open }) => (
						<Button variant="primary" onClick={open}>
							{__('Select SVG', 'advanced-svg-animator')}
						</Button>
					)}
				/>
			</MediaUploadCheck>
		</Placeholder>
	);

	return (
		<div {...blockProps}>
			<BlockControls>
				<AlignmentControl
					value={align}
					onChange={(newAlign) => setAttributes({ align: newAlign })}
				/>
				{svgId > 0 && (
					<ToolbarGroup>
						<MediaUploadCheck>
							<MediaUpload
								onSelect={onSelectMedia}
								allowedTypes={['image/svg+xml']}
								value={svgId}
								render={({ open }) => (
									<ToolbarButton
										icon={replace}
										label={__('Replace SVG', 'advanced-svg-animator')}
										onClick={open}
									/>
								)}
							/>
						</MediaUploadCheck>
						<ToolbarButton
							icon="no"
							label={__('Remove SVG', 'advanced-svg-animator')}
							onClick={onRemoveMedia}
						/>
					</ToolbarGroup>
				)}
			</BlockControls>

			<InspectorControls>
				<PanelBody title={__('SVG Settings', 'advanced-svg-animator')} initialOpen={true}>
					{svgId > 0 ? (
						<>
							<div style={{ marginBottom: '16px' }}>
								<strong>{__('Current SVG:', 'advanced-svg-animator')}</strong>
								<br />
								<small>{svgUrl}</small>
							</div>
							<MediaUploadCheck>
								<MediaUpload
									onSelect={onSelectMedia}
									allowedTypes={['image/svg+xml']}
									value={svgId}
									render={({ open }) => (
										<Button variant="secondary" onClick={open}>
											{__('Change SVG', 'advanced-svg-animator')}
										</Button>
									)}
								/>
							</MediaUploadCheck>
							<Button
								variant="link"
								isDestructive
								onClick={onRemoveMedia}
								style={{ marginLeft: '8px' }}
							>
								{__('Remove', 'advanced-svg-animator')}
							</Button>
						</>
					) : (
						<MediaUploadCheck>
							<MediaUpload
								onSelect={onSelectMedia}
								allowedTypes={['image/svg+xml']}
								value={svgId}
								render={({ open }) => (
									<Button variant="primary" onClick={open}>
										{__('Select SVG', 'advanced-svg-animator')}
									</Button>
								)}
							/>
						</MediaUploadCheck>
					)}

					<TextControl
						label={__('Width', 'advanced-svg-animator')}
						value={width}
						onChange={(value) => setAttributes({ width: value })}
						placeholder="300px"
						help={__('Set width (e.g., 300px, 50%, auto)', 'advanced-svg-animator')}
					/>

					<TextControl
						label={__('Height', 'advanced-svg-animator')}
						value={height}
						onChange={(value) => setAttributes({ height: value })}
						placeholder="auto"
						help={__('Set height (e.g., 200px, auto)', 'advanced-svg-animator')}
					/>
				</PanelBody>

				<PanelBody title={__('Animation Settings', 'advanced-svg-animator')} initialOpen={true}>
					<SelectControl
						label={__('Animation Type', 'advanced-svg-animator')}
						value={animationType}
						options={[
							{ label: __('None', 'advanced-svg-animator'), value: 'none' },
							...ANIMATION_TYPES.map(type => ({
								label: type.label + (type.experimental ? ' (Experimental)' : ''),
								value: type.value,
							})),
						]}
						onChange={(value) => setAttributes({ animationType: value })}
						help={getCurrentAnimationType()?.description || __('Choose a predefined animation effect', 'advanced-svg-animator')}
					/>

					{animationType !== 'none' && (
						<>
							<SelectControl
								label={__('Animation Trigger', 'advanced-svg-animator')}
								value={animationTrigger}
								options={TRIGGER_TYPES}
								onChange={(value) => setAttributes({ animationTrigger: value })}
								help={__('When should the animation start?', 'advanced-svg-animator')}
							/>

							<TextControl
								label={__('Target Selector (Optional)', 'advanced-svg-animator')}
								value={targetSelector}
								onChange={(value) => setAttributes({ targetSelector: value })}
								placeholder="e.g., #my-path, .my-group, path"
								help={__('CSS selector to animate specific SVG elements. Leave empty to animate entire SVG.', 'advanced-svg-animator')}
							/>

							<RangeControl
								label={__('Duration (seconds)', 'advanced-svg-animator')}
								value={duration}
								onChange={(value) => setAttributes({ duration: value })}
								min={0.1}
								max={10}
								step={0.1}
								help={__('How long the animation takes to complete', 'advanced-svg-animator')}
							/>

							<RangeControl
								label={__('Delay (seconds)', 'advanced-svg-animator')}
								value={delay}
								onChange={(value) => setAttributes({ delay: value })}
								min={0}
								max={5}
								step={0.1}
								help={__('Delay before animation starts', 'advanced-svg-animator')}
							/>

							<TextControl
								label={__('Iteration Count', 'advanced-svg-animator')}
								value={iterationCount}
								onChange={(value) => setAttributes({ iterationCount: value })}
								placeholder="1"
								help={__('Number of times to repeat (use "infinite" for endless loop)', 'advanced-svg-animator')}
							/>

							<SelectControl
								label={__('Timing Function', 'advanced-svg-animator')}
								value={timingFunction}
								options={TIMING_FUNCTIONS}
								onChange={(value) => setAttributes({ timingFunction: value })}
								help={__('Controls the speed curve of the animation', 'advanced-svg-animator')}
							/>

							{/* Trigger-specific settings */}
							{animationTrigger === 'onScroll' && (
								<>
									<RangeControl
										label={__('Scroll Threshold', 'advanced-svg-animator')}
										value={scrollThreshold}
										onChange={(value) => setAttributes({ scrollThreshold: value })}
										min={0.1}
										max={1.0}
										step={0.1}
										help={__('Percentage of element visible to trigger animation', 'advanced-svg-animator')}
									/>
									<RangeControl
										label={__('Scroll Offset (px)', 'advanced-svg-animator')}
										value={scrollOffset}
										onChange={(value) => setAttributes({ scrollOffset: value })}
										min={0}
										max={500}
										step={10}
										help={__('Pixels from viewport edge to trigger', 'advanced-svg-animator')}
									/>
								</>
							)}

							{animationTrigger === 'onHover' && (
								<SelectControl
									label={__('Hover Target', 'advanced-svg-animator')}
									value={hoverTarget}
									options={[
										{ label: __('SVG Element', 'advanced-svg-animator'), value: 'svg' },
										{ label: __('Entire Block', 'advanced-svg-animator'), value: 'block' },
										{ label: __('Custom Selector', 'advanced-svg-animator'), value: 'custom' },
									]}
									onChange={(value) => setAttributes({ hoverTarget: value })}
									help={__('What element triggers the hover animation', 'advanced-svg-animator')}
								/>
							)}
						</>
					)}

					<TextControl
						label={__('Custom CSS Class', 'advanced-svg-animator')}
						value={customCSSClass}
						onChange={(value) => setAttributes({ customCSSClass: value })}
						placeholder="my-custom-animation"
						help={__('Add custom CSS class for advanced animations', 'advanced-svg-animator')}
					/>

					{animationType !== 'none' && (
						<div style={{ marginTop: '16px', padding: '12px', backgroundColor: '#f0f6fc', border: '1px solid #c3c4c7', borderRadius: '4px' }}>
							<Button 
								variant="secondary" 
								onClick={previewAnimation}
								style={{ marginBottom: '8px' }}
							>
								{__('Preview Animation', 'advanced-svg-animator')}
							</Button>
							<div style={{ fontSize: '12px', color: '#666' }}>
								<strong>{__('Current Setup:', 'advanced-svg-animator')}</strong><br />
								{getCurrentAnimationType()?.label} • {animationTrigger} • {duration}s
								{targetSelector && <><br /><strong>{__('Target:', 'advanced-svg-animator')}</strong> {targetSelector}</>}
							</div>
						</div>
					)}
				</PanelBody>

				{/* Advanced SVG Drawing Settings */}
				{(animationType === 'drawSVGPaths' || animationType === 'drawLine') && (
					<PanelBody title={__('SVG Drawing Settings', 'advanced-svg-animator')} initialOpen={false}>
						<Notice status="info" isDismissible={false}>
							<strong>{__('SVG Preparation Tips:', 'advanced-svg-animator')}</strong>
							<ul style={{ marginTop: '8px', paddingLeft: '20px' }}>
								<li>{__('Ensure your SVG paths have stroke and stroke-width attributes', 'advanced-svg-animator')}</li>
								<li>{__('Remove fill or set fill="none" for best drawing effect', 'advanced-svg-animator')}</li>
								<li>{__('Use targetSelector to animate specific paths only', 'advanced-svg-animator')}</li>
							</ul>
						</Notice>

						<TextControl
							label={__('Stroke Color', 'advanced-svg-animator')}
							value={drawSVGSettings?.strokeColor || '#333333'}
							onChange={(value) => setAttributes({ 
								drawSVGSettings: { ...drawSVGSettings, strokeColor: value }
							})}
							help={__('Color for SVG strokes (if not set in SVG)', 'advanced-svg-animator')}
						/>

						<RangeControl
							label={__('Stroke Width', 'advanced-svg-animator')}
							value={drawSVGSettings?.strokeWidth || 2}
							onChange={(value) => setAttributes({ 
								drawSVGSettings: { ...drawSVGSettings, strokeWidth: value }
							})}
							min={0.5}
							max={10}
							step={0.5}
							help={__('Width of SVG strokes (if not set in SVG)', 'advanced-svg-animator')}
						/>

						{animationType === 'drawSVGPaths' && (
							<>
								<SelectControl
									label={__('Draw Direction', 'advanced-svg-animator')}
									value={drawSVGSettings?.drawDirection || 'forward'}
									options={[
										{ label: __('Forward', 'advanced-svg-animator'), value: 'forward' },
										{ label: __('Reverse', 'advanced-svg-animator'), value: 'reverse' },
									]}
									onChange={(value) => setAttributes({ 
										drawSVGSettings: { ...drawSVGSettings, drawDirection: value }
									})}
								/>

								<ToggleControl
									label={__('Animate All Paths Simultaneously', 'advanced-svg-animator')}
									checked={drawSVGSettings?.simultaneousPaths || false}
									onChange={(value) => setAttributes({ 
										drawSVGSettings: { ...drawSVGSettings, simultaneousPaths: value }
									})}
									help={__('If disabled, paths will animate with stagger delay', 'advanced-svg-animator')}
								/>

								{!drawSVGSettings?.simultaneousPaths && (
									<RangeControl
										label={__('Stagger Delay (seconds)', 'advanced-svg-animator')}
										value={advancedSettings?.staggerDelay || 0}
										onChange={(value) => setAttributes({ 
											advancedSettings: { ...advancedSettings, staggerDelay: value }
										})}
										min={0}
										max={2}
										step={0.1}
										help={__('Delay between each path animation', 'advanced-svg-animator')}
									/>
								)}
							</>
						)}
					</PanelBody>
				)}
			</InspectorControls>

			{/* Block Content */}
			{error && (
				<Notice status="error" isDismissible={false}>
					{error}
				</Notice>
			)}

			{isLoading && (
				<div style={{ textAlign: 'center', padding: '20px' }}>
					<Spinner />
					<p>{__('Loading SVG...', 'advanced-svg-animator')}</p>
				</div>
			)}

			{!svgId || svgId === 0 ? (
				renderPlaceholder()
			) : svgContent ? (
				<div className="svg-animator-preview">
					{renderSVG()}
					{animationType !== 'none' && (
						<div className="animation-info">
							<small>
								{__('Animation:', 'advanced-svg-animator')} {getCurrentAnimationType()?.label || animationType}<br />
								{__('Trigger:', 'advanced-svg-animator')} {animationTrigger} • 
								{__('Duration:', 'advanced-svg-animator')} {duration}s • 
								{__('Iterations:', 'advanced-svg-animator')} {iterationCount}
								{targetSelector && (
									<><br />{__('Target:', 'advanced-svg-animator')} <code>{targetSelector}</code></>
								)}
							</small>
						</div>
					)}
				</div>
			) : (
				!isLoading && (
					<Notice status="warning" isDismissible={false}>
						{__('Failed to load SVG content. Please try selecting a different file.', 'advanced-svg-animator')}
					</Notice>
				)
			)}
		</div>
	);
}
