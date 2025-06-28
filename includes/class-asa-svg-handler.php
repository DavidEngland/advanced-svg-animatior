<?php
/**
 * SVG Handler class for Advanced SVG Animator
 * 
 * @package AdvancedSVGAnimator
 * @subpackage Includes
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SVG Handler class
 */
class ASA_SVG_Handler extends ASA_Component {
    
    /**
     * Component name
     * @var string
     */
    protected $name = 'svg_handler';
    
    /**
     * Setup WordPress hooks
     */
    protected function setup_hooks() {
        add_filter('wp_handle_upload_prefilter', array($this, 'handle_svg_upload'), 10, 1);
        add_filter('wp_generate_attachment_metadata', array($this, 'generate_svg_metadata'), 10, 2);
        add_filter('wp_get_attachment_image_src', array($this, 'fix_svg_image_src'), 10, 4);
        add_action('wp_ajax_asa_get_svg_elements', array($this, 'ajax_get_svg_elements'));
        add_action('wp_ajax_asa_optimize_svg', array($this, 'ajax_optimize_svg'));
    }
    
    /**
     * Handle SVG file upload
     * 
     * @param array $file Upload file data
     * @return array Modified file data
     */
    public function handle_svg_upload($file) {
        if (!isset($file['type']) || $file['type'] !== 'image/svg+xml') {
            return $file;
        }
        
        // Additional SVG-specific validation
        if (!$this->validate_svg_file($file['tmp_name'])) {
            $file['error'] = __('Invalid SVG file format.', 'advanced-svg-animator');
            return $file;
        }
        
        // Optimize SVG if enabled
        if (asa_get_option('auto_optimize_svg', false)) {
            $this->optimize_svg_file($file['tmp_name']);
        }
        
        return $file;
    }
    
    /**
     * Generate metadata for SVG attachments
     * 
     * @param array $metadata Attachment metadata
     * @param int $attachment_id Attachment ID
     * @return array Modified metadata
     */
    public function generate_svg_metadata($metadata, $attachment_id) {
        $file_path = get_attached_file($attachment_id);
        
        if (!$file_path || !$this->is_svg_file($file_path)) {
            return $metadata;
        }
        
        // Get SVG dimensions
        $dimensions = asa_get_svg_dimensions($file_path);
        if ($dimensions) {
            $metadata['width'] = $dimensions['width'];
            $metadata['height'] = $dimensions['height'];
        }
        
        // Extract SVG elements for animation
        $metadata['svg_elements'] = $this->extract_svg_elements($file_path);
        
        // Get SVG optimization potential
        $metadata['optimization_potential'] = $this->analyze_svg_optimization($file_path);
        
        return $metadata;
    }
    
    /**
     * Fix SVG image src for proper display
     * 
     * @param array|false $image Image data or false
     * @param int $attachment_id Attachment ID
     * @param string|array $size Image size
     * @param bool $icon Whether image is an icon
     * @return array|false Modified image data
     */
    public function fix_svg_image_src($image, $attachment_id, $size, $icon) {
        if (!$image) {
            return $image;
        }
        
        $file_path = get_attached_file($attachment_id);
        
        if (!$this->is_svg_file($file_path)) {
            return $image;
        }
        
        // Get SVG dimensions
        $dimensions = asa_get_svg_dimensions($file_path);
        
        if ($dimensions && $dimensions['width'] && $dimensions['height']) {
            $image[1] = $dimensions['width'];
            $image[2] = $dimensions['height'];
        }
        
        return $image;
    }
    
    /**
     * AJAX handler to get SVG elements
     */
    public function ajax_get_svg_elements() {
        check_ajax_referer('asa_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error(__('Insufficient permissions.', 'advanced-svg-animator'));
        }
        
        $attachment_id = intval($_POST['attachment_id'] ?? 0);
        
        if (!$attachment_id) {
            wp_send_json_error(__('Invalid attachment ID.', 'advanced-svg-animator'));
        }
        
        $file_path = get_attached_file($attachment_id);
        
        if (!$this->is_svg_file($file_path)) {
            wp_send_json_error(__('File is not a valid SVG.', 'advanced-svg-animator'));
        }
        
        $elements = $this->extract_svg_elements($file_path);
        
        wp_send_json_success(array(
            'elements' => $elements,
            'count' => count($elements)
        ));
    }
    
    /**
     * AJAX handler to optimize SVG
     */
    public function ajax_optimize_svg() {
        check_ajax_referer('asa_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error(__('Insufficient permissions.', 'advanced-svg-animator'));
        }
        
        $attachment_id = intval($_POST['attachment_id'] ?? 0);
        
        if (!$attachment_id) {
            wp_send_json_error(__('Invalid attachment ID.', 'advanced-svg-animator'));
        }
        
        $file_path = get_attached_file($attachment_id);
        
        if (!$this->is_svg_file($file_path)) {
            wp_send_json_error(__('File is not a valid SVG.', 'advanced-svg-animator'));
        }
        
        $original_size = filesize($file_path);
        $optimized = $this->optimize_svg_file($file_path);
        
        if (!$optimized) {
            wp_send_json_error(__('Failed to optimize SVG.', 'advanced-svg-animator'));
        }
        
        $new_size = filesize($file_path);
        $savings = $original_size - $new_size;
        $percentage = $original_size > 0 ? round(($savings / $original_size) * 100, 2) : 0;
        
        wp_send_json_success(array(
            'original_size' => size_format($original_size),
            'new_size' => size_format($new_size),
            'savings' => size_format($savings),
            'percentage' => $percentage
        ));
    }
    
    /**
     * Validate SVG file
     * 
     * @param string $file_path Path to SVG file
     * @return bool True if valid SVG
     */
    private function validate_svg_file($file_path) {
        return asa_is_valid_svg($file_path);
    }
    
    /**
     * Check if file is SVG
     * 
     * @param string $file_path File path
     * @return bool True if SVG file
     */
    private function is_svg_file($file_path) {
        return pathinfo($file_path, PATHINFO_EXTENSION) === 'svg';
    }
    
    /**
     * Extract animatable elements from SVG
     * 
     * @param string $file_path Path to SVG file
     * @return array Array of SVG elements
     */
    private function extract_svg_elements($file_path) {
        if (!file_exists($file_path)) {
            return array();
        }
        
        $content = file_get_contents($file_path);
        if (!$content) {
            return array();
        }
        
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        
        if (!$dom->loadXML($content)) {
            return array();
        }
        
        $elements = array();
        $animatable_tags = array('path', 'circle', 'rect', 'ellipse', 'line', 'polygon', 'polyline', 'text', 'g');
        
        foreach ($animatable_tags as $tag) {
            $nodes = $dom->getElementsByTagName($tag);
            foreach ($nodes as $node) {
                $element = array(
                    'tag' => $tag,
                    'id' => $node->getAttribute('id') ?: '',
                    'class' => $node->getAttribute('class') ?: '',
                    'xpath' => $this->get_element_xpath($node)
                );
                
                // Add tag-specific attributes
                switch ($tag) {
                    case 'path':
                        $element['d'] = $node->getAttribute('d');
                        break;
                    case 'circle':
                        $element['cx'] = $node->getAttribute('cx');
                        $element['cy'] = $node->getAttribute('cy');
                        $element['r'] = $node->getAttribute('r');
                        break;
                    case 'rect':
                        $element['x'] = $node->getAttribute('x');
                        $element['y'] = $node->getAttribute('y');
                        $element['width'] = $node->getAttribute('width');
                        $element['height'] = $node->getAttribute('height');
                        break;
                }
                
                $elements[] = $element;
            }
        }
        
        return $elements;
    }
    
    /**
     * Get XPath for DOM element
     * 
     * @param DOMElement $element DOM element
     * @return string XPath string
     */
    private function get_element_xpath($element) {
        $xpath = '';
        $node = $element;
        
        while ($node && $node->nodeType === XML_ELEMENT_NODE) {
            $position = 1;
            $siblings = $node->parentNode ? $node->parentNode->childNodes : array();
            
            foreach ($siblings as $sibling) {
                if ($sibling->nodeType === XML_ELEMENT_NODE && $sibling->tagName === $node->tagName) {
                    if ($sibling === $node) {
                        break;
                    }
                    $position++;
                }
            }
            
            $xpath = '/' . $node->tagName . '[' . $position . ']' . $xpath;
            $node = $node->parentNode;
        }
        
        return $xpath;
    }
    
    /**
     * Analyze SVG optimization potential
     * 
     * @param string $file_path Path to SVG file
     * @return array Optimization analysis
     */
    private function analyze_svg_optimization($file_path) {
        if (!file_exists($file_path)) {
            return array();
        }
        
        $content = file_get_contents($file_path);
        if (!$content) {
            return array();
        }
        
        $analysis = array(
            'file_size' => filesize($file_path),
            'can_optimize' => false,
            'issues' => array()
        );
        
        // Check for common optimization opportunities
        if (strpos($content, '<?xml') !== false) {
            $analysis['issues'][] = 'XML declaration can be removed for web use';
            $analysis['can_optimize'] = true;
        }
        
        if (strpos($content, '<!-- ') !== false) {
            $analysis['issues'][] = 'Comments can be removed';
            $analysis['can_optimize'] = true;
        }
        
        if (preg_match('/\s{2,}/', $content)) {
            $analysis['issues'][] = 'Excessive whitespace can be minified';
            $analysis['can_optimize'] = true;
        }
        
        if (strpos($content, 'stroke="none"') !== false || strpos($content, 'fill="none"') !== false) {
            $analysis['issues'][] = 'Unnecessary attributes can be removed';
            $analysis['can_optimize'] = true;
        }
        
        return $analysis;
    }
    
    /**
     * Optimize SVG file
     * 
     * @param string $file_path Path to SVG file
     * @return bool True on success, false on failure
     */
    private function optimize_svg_file($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }
        
        $content = file_get_contents($file_path);
        if (!$content) {
            return false;
        }
        
        // Basic optimization: remove comments and excessive whitespace
        $content = preg_replace('/<!--.*?-->/s', '', $content);
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);
        
        // Remove XML declaration for web use
        $content = preg_replace('/<\?xml[^>]*\?>/', '', $content);
        
        // Write optimized content back
        $result = file_put_contents($file_path, $content);
        
        return $result !== false;
    }
}
