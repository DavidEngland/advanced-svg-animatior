<?php
/**
 * Typewriter Text Block - Server Side Rendering
 * 
 * @param array $attributes Block attributes
 * @param string $content Block content
 * @param WP_Block $block Block instance
 * @return string Rendered block content
 */
function render_typewriter_text_block( $attributes, $content, $block ) {
    // Default attributes
    $defaults = array(
        'text' => 'Type your message here...',
        'typingSpeed' => 100,
        'deleteSpeed' => 50,
        'pauseEnd' => 2000,
        'pauseStart' => 1000,
        'showCursor' => true,
        'cursorChar' => '|',
        'infiniteLoop' => true,
        'fontSize' => '',
        'textColor' => '',
        'backgroundColor' => '',
        'fontFamily' => 'monospace'
    );
    
    $attributes = wp_parse_args( $attributes, $defaults );
    
    // Sanitize attributes
    $text = wp_kses_post( $attributes['text'] );
    $typing_speed = absint( $attributes['typingSpeed'] );
    $delete_speed = absint( $attributes['deleteSpeed'] );
    $pause_end = absint( $attributes['pauseEnd'] );
    $pause_start = absint( $attributes['pauseStart'] );
    $show_cursor = (bool) $attributes['showCursor'];
    $cursor_char = esc_html( substr( $attributes['cursorChar'], 0, 3 ) ); // Limit cursor to 3 chars
    $infinite_loop = (bool) $attributes['infiniteLoop'];
    $font_family = esc_attr( $attributes['fontFamily'] );
    
    // Build CSS classes
    $wrapper_classes = array( 'wp-block-advanced-svg-animator-typewriter-text' );
    
    if ( ! empty( $attributes['textColor'] ) ) {
        $wrapper_classes[] = 'has-text-color';
    }
    
    if ( ! empty( $attributes['backgroundColor'] ) ) {
        $wrapper_classes[] = 'has-background';
    }
    
    if ( ! empty( $attributes['fontSize'] ) ) {
        $wrapper_classes[] = 'has-' . esc_attr( $attributes['fontSize'] ) . '-font-size';
    }
    
    // Build inline styles
    $wrapper_styles = array();
    
    if ( ! empty( $attributes['textColor'] ) ) {
        $wrapper_styles[] = 'color: ' . esc_attr( $attributes['textColor'] );
    }
    
    if ( ! empty( $attributes['backgroundColor'] ) ) {
        $wrapper_styles[] = 'background-color: ' . esc_attr( $attributes['backgroundColor'] );
    }
    
    if ( ! empty( $font_family ) ) {
        $wrapper_styles[] = 'font-family: ' . $font_family;
    }
    
    // Build data attributes for JavaScript
    $data_attributes = array(
        'data-text' => esc_attr( $text ),
        'data-typing-speed' => $typing_speed,
        'data-delete-speed' => $delete_speed,
        'data-pause-end' => $pause_end,
        'data-pause-start' => $pause_start,
        'data-show-cursor' => $show_cursor ? 'true' : 'false',
        'data-cursor-char' => esc_attr( $cursor_char ),
        'data-infinite-loop' => $infinite_loop ? 'true' : 'false'
    );
    
    // Generate unique ID for this instance
    static $instance_count = 0;
    $instance_count++;
    $unique_id = 'typewriter-' . $instance_count;
    
    // Build the HTML
    ob_start();
    ?>
    <div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" 
         <?php if ( ! empty( $wrapper_styles ) ) : ?>
         style="<?php echo esc_attr( implode( '; ', $wrapper_styles ) ); ?>"
         <?php endif; ?>>
        <div class="typewriter-text-container" 
             id="<?php echo esc_attr( $unique_id ); ?>"
             <?php foreach ( $data_attributes as $key => $value ) : ?>
             <?php echo esc_attr( $key ); ?>="<?php echo esc_attr( $value ); ?>"
             <?php endforeach; ?>>
            <span class="typewriter-text" aria-live="polite"></span><?php if ( $show_cursor ) : ?><span class="typewriter-cursor" aria-hidden="true"></span><?php endif; ?>
        </div>
    </div>
    <?php
    
    return ob_get_clean();
}

// Register the render callback
add_action( 'init', function() {
    if ( function_exists( 'register_block_type' ) ) {
        // This will be called from the main plugin file
        return;
    }
});
?>
