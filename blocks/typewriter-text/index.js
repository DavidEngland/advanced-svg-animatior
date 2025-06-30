import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { 
    useBlockProps, 
    RichText, 
    InspectorControls, 
    PanelColorSettings,
    FontSizePicker,
    withFontSizes 
} from '@wordpress/block-editor';
import { 
    PanelBody, 
    RangeControl, 
    ToggleControl, 
    TextControl,
    SelectControl 
} from '@wordpress/components';
import { compose } from '@wordpress/compose';

import './editor.scss';

const TypewriterTextEdit = ( props ) => {
    const { 
        attributes, 
        setAttributes, 
        fontSize,
        setFontSize
    } = props;
    
    const {
        text,
        typingSpeed,
        deleteSpeed,
        pauseEnd,
        pauseStart,
        showCursor,
        cursorChar,
        infiniteLoop,
        textColor,
        backgroundColor,
        fontFamily
    } = attributes;

    const blockProps = useBlockProps( {
        className: 'typewriter-text-block',
        style: {
            fontSize: fontSize?.size,
            color: textColor,
            backgroundColor: backgroundColor,
            fontFamily: fontFamily
        }
    } );

    const fontFamilyOptions = [
        { label: 'Monospace (Default)', value: 'monospace' },
        { label: 'Courier New', value: '"Courier New", monospace' },
        { label: 'Monaco', value: 'Monaco, monospace' },
        { label: 'Consolas', value: 'Consolas, monospace' },
        { label: 'System Font', value: 'system-ui' },
        { label: 'Sans Serif', value: 'sans-serif' },
        { label: 'Serif', value: 'serif' }
    ];

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Typewriter Settings', 'advanced-svg-animator' ) }>
                    <RangeControl
                        label={ __( 'Typing Speed (ms)', 'advanced-svg-animator' ) }
                        value={ typingSpeed }
                        onChange={ ( value ) => setAttributes( { typingSpeed: value } ) }
                        min={ 50 }
                        max={ 500 }
                        step={ 10 }
                        help={ __( 'Milliseconds between each character', 'advanced-svg-animator' ) }
                    />
                    <RangeControl
                        label={ __( 'Delete Speed (ms)', 'advanced-svg-animator' ) }
                        value={ deleteSpeed }
                        onChange={ ( value ) => setAttributes( { deleteSpeed: value } ) }
                        min={ 25 }
                        max={ 250 }
                        step={ 5 }
                        help={ __( 'Speed when deleting characters', 'advanced-svg-animator' ) }
                    />
                    <RangeControl
                        label={ __( 'Pause at End (ms)', 'advanced-svg-animator' ) }
                        value={ pauseEnd }
                        onChange={ ( value ) => setAttributes( { pauseEnd: value } ) }
                        min={ 500 }
                        max={ 5000 }
                        step={ 100 }
                        help={ __( 'Pause after typing before deleting', 'advanced-svg-animator' ) }
                    />
                    <RangeControl
                        label={ __( 'Pause at Start (ms)', 'advanced-svg-animator' ) }
                        value={ pauseStart }
                        onChange={ ( value ) => setAttributes( { pauseStart: value } ) }
                        min={ 200 }
                        max={ 3000 }
                        step={ 100 }
                        help={ __( 'Pause before starting to type again', 'advanced-svg-animator' ) }
                    />
                    <ToggleControl
                        label={ __( 'Show Cursor', 'advanced-svg-animator' ) }
                        checked={ showCursor }
                        onChange={ ( value ) => setAttributes( { showCursor: value } ) }
                    />
                    { showCursor && (
                        <TextControl
                            label={ __( 'Cursor Character', 'advanced-svg-animator' ) }
                            value={ cursorChar }
                            onChange={ ( value ) => setAttributes( { cursorChar: value } ) }
                            help={ __( 'Character to use as the cursor', 'advanced-svg-animator' ) }
                        />
                    ) }
                    <ToggleControl
                        label={ __( 'Infinite Loop', 'advanced-svg-animator' ) }
                        checked={ infiniteLoop }
                        onChange={ ( value ) => setAttributes( { infiniteLoop: value } ) }
                        help={ __( 'Continuously type and delete text', 'advanced-svg-animator' ) }
                    />
                </PanelBody>
                
                <PanelBody title={ __( 'Typography', 'advanced-svg-animator' ) }>
                    <FontSizePicker
                        value={ fontSize?.size }
                        onChange={ setFontSize }
                    />
                    <SelectControl
                        label={ __( 'Font Family', 'advanced-svg-animator' ) }
                        value={ fontFamily }
                        options={ fontFamilyOptions }
                        onChange={ ( value ) => setAttributes( { fontFamily: value } ) }
                    />
                </PanelBody>
                
                <PanelColorSettings
                    title={ __( 'Color Settings', 'advanced-svg-animator' ) }
                    colorSettings={ [
                        {
                            value: textColor,
                            onChange: ( value ) => setAttributes( { textColor: value } ),
                            label: __( 'Text Color', 'advanced-svg-animator' )
                        },
                        {
                            value: backgroundColor,
                            onChange: ( value ) => setAttributes( { backgroundColor: value } ),
                            label: __( 'Background Color', 'advanced-svg-animator' )
                        }
                    ] }
                />
            </InspectorControls>

            <div { ...blockProps }>
                <div className="typewriter-preview">
                    <RichText
                        tagName="div"
                        value={ text }
                        onChange={ ( value ) => setAttributes( { text: value } ) }
                        placeholder={ __( 'Enter your typewriter text...', 'advanced-svg-animator' ) }
                        allowedFormats={ [] }
                        className="typewriter-text-input"
                    />
                    <div className="typewriter-settings-preview">
                        <small>
                            { __( 'Speed:', 'advanced-svg-animator' ) } { typingSpeed }ms | 
                            { __( ' Loop:', 'advanced-svg-animator' ) } { infiniteLoop ? __( 'Yes', 'advanced-svg-animator' ) : __( 'No', 'advanced-svg-animator' ) }
                            { showCursor && ` | ${ __( 'Cursor:', 'advanced-svg-animator' ) } "${ cursorChar }"` }
                        </small>
                    </div>
                </div>
            </div>
        </>
    );
};

const TypewriterTextBlock = compose( [
    withFontSizes( 'fontSize' )
] )( TypewriterTextEdit );

registerBlockType( 'advanced-svg-animator/typewriter-text', {
    edit: TypewriterTextBlock,
} );
