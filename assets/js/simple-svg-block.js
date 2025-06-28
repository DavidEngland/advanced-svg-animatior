/**
 * Simple SVG Animator Block
 * A working WordPress block that allows users to select SVG files and add animations
 */

(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, MediaUpload, MediaUploadCheck } = wp.blockEditor;
    const { PanelBody, Button, SelectControl, RangeControl, Notice } = wp.components;
    const { useState, useEffect } = wp.element;
    const { __ } = wp.i18n;

    // Animation options
    const ANIMATION_OPTIONS = [
        { label: __('None'), value: 'none' },
        { label: __('Fade In'), value: 'fadeIn' },
        { label: __('Scale Up'), value: 'scaleUp' },
        { label: __('Rotate'), value: 'rotate' },
        { label: __('Bounce'), value: 'bounce' },
        { label: __('Slide In Left'), value: 'slideInLeft' },
        { label: __('Slide In Right'), value: 'slideInRight' },
        { label: __('Pulse'), value: 'pulse' }
    ];

    const TIMING_OPTIONS = [
        { label: __('Ease'), value: 'ease' },
        { label: __('Linear'), value: 'linear' },
        { label: __('Ease In'), value: 'ease-in' },
        { label: __('Ease Out'), value: 'ease-out' },
        { label: __('Ease In Out'), value: 'ease-in-out' }
    ];

    registerBlockType('advanced-svg-animator/svg-animator', {
        title: __('SVG Animator'),
        description: __('Add animated SVG files from your media library'),
        category: 'media',
        icon: 'format-image',
        attributes: {
            svgId: {
                type: 'number',
                default: 0
            },
            svgUrl: {
                type: 'string',
                default: ''
            },
            animationType: {
                type: 'string',
                default: 'none'
            },
            duration: {
                type: 'number',
                default: 2
            },
            delay: {
                type: 'number',
                default: 0
            },
            iterations: {
                type: 'string',
                default: '1'
            },
            timing: {
                type: 'string',
                default: 'ease'
            },
            width: {
                type: 'number',
                default: 200
            }
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { svgId, svgUrl, animationType, duration, delay, iterations, timing, width } = attributes;

            // Media upload handler
            const onSelectSVG = function(media) {
                setAttributes({
                    svgId: media.id,
                    svgUrl: media.url
                });
            };

            // Remove SVG handler
            const removeSVG = function() {
                setAttributes({
                    svgId: 0,
                    svgUrl: ''
                });
            };

            // Generate animation CSS
            const getAnimationCSS = function() {
                if (animationType === 'none') return '';
                
                let animationName = '';
                switch(animationType) {
                    case 'fadeIn':
                        animationName = 'asa-fadeIn';
                        break;
                    case 'scaleUp':
                        animationName = 'asa-scaleUp';
                        break;
                    case 'rotate':
                        animationName = 'asa-rotate';
                        break;
                    case 'bounce':
                        animationName = 'asa-bounce';
                        break;
                    case 'slideInLeft':
                        animationName = 'asa-slideInLeft';
                        break;
                    case 'slideInRight':
                        animationName = 'asa-slideInRight';
                        break;
                    case 'pulse':
                        animationName = 'asa-pulse';
                        break;
                }
                
                return `animation: ${animationName} ${duration}s ${timing} ${delay}s ${iterations} both;`;
            };

            return wp.element.createElement('div', {
                className: 'svg-animator-block'
            }, [
                // Inspector Controls
                wp.element.createElement(InspectorControls, { key: 'inspector' }, [
                    wp.element.createElement(PanelBody, {
                        title: __('SVG Settings'),
                        initialOpen: true,
                        key: 'svg-settings'
                    }, [
                        svgId > 0 && wp.element.createElement('div', {
                            key: 'current-svg',
                            style: { marginBottom: '16px' }
                        }, [
                            wp.element.createElement('strong', {}, __('Current SVG: ')),
                            wp.element.createElement('br'),
                            wp.element.createElement('small', {}, svgUrl)
                        ]),
                        
                        wp.element.createElement(MediaUploadCheck, { key: 'media-check' },
                            wp.element.createElement(MediaUpload, {
                                onSelect: onSelectSVG,
                                allowedTypes: ['image/svg+xml'],
                                value: svgId,
                                render: function({ open }) {
                                    return wp.element.createElement(Button, {
                                        onClick: open,
                                        variant: svgId > 0 ? 'secondary' : 'primary'
                                    }, svgId > 0 ? __('Replace SVG') : __('Select SVG'));
                                }
                            })
                        ),
                        
                        svgId > 0 && wp.element.createElement(Button, {
                            onClick: removeSVG,
                            variant: 'tertiary',
                            isDestructive: true,
                            key: 'remove-svg',
                            style: { marginTop: '8px' }
                        }, __('Remove SVG')),

                        wp.element.createElement(RangeControl, {
                            label: __('Width (px)'),
                            value: width,
                            onChange: function(value) { setAttributes({ width: value }); },
                            min: 50,
                            max: 800,
                            key: 'width-control'
                        })
                    ]),
                    
                    wp.element.createElement(PanelBody, {
                        title: __('Animation Settings'),
                        initialOpen: true,
                        key: 'animation-settings'
                    }, [
                        wp.element.createElement(SelectControl, {
                            label: __('Animation Type'),
                            value: animationType,
                            options: ANIMATION_OPTIONS,
                            onChange: function(value) { setAttributes({ animationType: value }); },
                            key: 'animation-type'
                        }),
                        
                        animationType !== 'none' && wp.element.createElement(RangeControl, {
                            label: __('Duration (seconds)'),
                            value: duration,
                            onChange: function(value) { setAttributes({ duration: value }); },
                            min: 0.1,
                            max: 10,
                            step: 0.1,
                            key: 'duration'
                        }),
                        
                        animationType !== 'none' && wp.element.createElement(RangeControl, {
                            label: __('Delay (seconds)'),
                            value: delay,
                            onChange: function(value) { setAttributes({ delay: value }); },
                            min: 0,
                            max: 5,
                            step: 0.1,
                            key: 'delay'
                        }),
                        
                        animationType !== 'none' && wp.element.createElement(SelectControl, {
                            label: __('Timing Function'),
                            value: timing,
                            options: TIMING_OPTIONS,
                            onChange: function(value) { setAttributes({ timing: value }); },
                            key: 'timing'
                        }),
                        
                        animationType !== 'none' && wp.element.createElement('div', {
                            key: 'iterations-wrapper'
                        }, [
                            wp.element.createElement('label', {
                                style: { display: 'block', marginBottom: '8px' },
                                key: 'iterations-label'
                            }, __('Iterations')),
                            wp.element.createElement('input', {
                                type: 'text',
                                value: iterations,
                                onChange: function(e) { setAttributes({ iterations: e.target.value }); },
                                placeholder: '1 or infinite',
                                style: { width: '100%', padding: '6px 8px' },
                                key: 'iterations-input'
                            }),
                            wp.element.createElement('small', {
                                style: { display: 'block', marginTop: '4px', color: '#757575' },
                                key: 'iterations-help'
                            }, __('Enter number of times to repeat, or "infinite"'))
                        ])
                    ])
                ]),
                
                // Block Content
                svgId === 0 ? 
                    // Placeholder when no SVG selected
                    wp.element.createElement('div', {
                        className: 'svg-animator-placeholder',
                        style: {
                            border: '2px dashed #ccc',
                            padding: '40px',
                            textAlign: 'center',
                            backgroundColor: '#f9f9f9'
                        },
                        key: 'placeholder'
                    }, [
                        wp.element.createElement('p', {
                            style: { margin: '0 0 16px 0', fontSize: '16px' },
                            key: 'placeholder-text'
                        }, __('SVG Animator Block')),
                        wp.element.createElement(MediaUploadCheck, { key: 'placeholder-media-check' },
                            wp.element.createElement(MediaUpload, {
                                onSelect: onSelectSVG,
                                allowedTypes: ['image/svg+xml'],
                                value: svgId,
                                render: function({ open }) {
                                    return wp.element.createElement(Button, {
                                        onClick: open,
                                        variant: 'primary'
                                    }, __('Select SVG File'));
                                }
                            })
                        )
                    ])
                    :
                    // SVG Display when selected
                    wp.element.createElement('div', {
                        className: 'svg-animator-preview',
                        style: {
                            textAlign: 'center',
                            padding: '20px'
                        },
                        key: 'preview'
                    }, [
                        wp.element.createElement('img', {
                            src: svgUrl,
                            style: {
                                width: width + 'px',
                                height: 'auto',
                                maxWidth: '100%',
                                ...( animationType !== 'none' ? { 
                                    animation: `asa-${animationType} ${duration}s ${timing} ${delay}s ${iterations} both`
                                } : {})
                            },
                            key: 'svg-image'
                        }),
                        wp.element.createElement('p', {
                            style: { 
                                marginTop: '10px', 
                                fontSize: '14px', 
                                color: '#666' 
                            },
                            key: 'preview-text'
                        }, animationType !== 'none' ? __('Animation: ') + animationType : __('No animation'))
                    ])
            ]);
        },

        save: function(props) {
            const { attributes } = props;
            const { svgId, svgUrl, animationType, duration, delay, iterations, timing, width } = attributes;

            if (!svgUrl) {
                return null;
            }

            const animationStyle = animationType !== 'none' ? 
                `animation: asa-${animationType} ${duration}s ${timing} ${delay}s ${iterations} both;` : '';

            return wp.element.createElement('div', {
                className: 'svg-animator-block-frontend'
            }, 
                wp.element.createElement('img', {
                    src: svgUrl,
                    style: `width: ${width}px; height: auto; max-width: 100%; ${animationStyle}`,
                    className: 'svg-animator-image'
                })
            );
        }
    });

})(window.wp);
