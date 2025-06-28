<?php
/**
 * Base class for Advanced SVG Animator components
 * 
 * @package AdvancedSVGAnimator
 * @subpackage Includes
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base component class
 */
abstract class ASA_Component {
    
    /**
     * Component name
     * @var string
     */
    protected $name = '';
    
    /**
     * Component version
     * @var string
     */
    protected $version = '1.0.0';
    
    /**
     * Whether component is enabled
     * @var bool
     */
    protected $enabled = true;
    
    /**
     * Component dependencies
     * @var array
     */
    protected $dependencies = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize component
     */
    protected function init() {
        if (!$this->enabled) {
            return;
        }
        
        if (!$this->check_dependencies()) {
            $this->enabled = false;
            return;
        }
        
        $this->setup_hooks();
    }
    
    /**
     * Setup WordPress hooks
     */
    abstract protected function setup_hooks();
    
    /**
     * Check if dependencies are met
     * 
     * @return bool True if dependencies are met
     */
    protected function check_dependencies() {
        foreach ($this->dependencies as $dependency) {
            if (!class_exists($dependency) && !function_exists($dependency)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get component name
     * 
     * @return string Component name
     */
    public function get_name() {
        return $this->name;
    }
    
    /**
     * Get component version
     * 
     * @return string Component version
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Check if component is enabled
     * 
     * @return bool True if enabled
     */
    public function is_enabled() {
        return $this->enabled;
    }
    
    /**
     * Enable component
     */
    public function enable() {
        $this->enabled = true;
        $this->init();
    }
    
    /**
     * Disable component
     */
    public function disable() {
        $this->enabled = false;
    }
}
