/**
 * Typewriter Text Animation - Frontend JavaScript
 * 
 * Handles the typewriter animation effect for the Gutenberg block.
 * Automatically initializes when the DOM is ready and handles all typewriter blocks on the page.
 */

class TypewriterTextAnimation {
    constructor(element, config = {}) {
        this.element = element;
        this.textElement = element.querySelector('.typewriter-text');
        this.cursorElement = element.querySelector('.typewriter-cursor');
        
        // Get configuration from data attributes or use defaults
        this.config = {
            text: config.text || element.dataset.text || 'Sample text...',
            typingSpeed: parseInt(config.typingSpeed || element.dataset.typingSpeed) || 100,
            deleteSpeed: parseInt(config.deleteSpeed || element.dataset.deleteSpeed) || 50,
            pauseEnd: parseInt(config.pauseEnd || element.dataset.pauseEnd) || 2000,
            pauseStart: parseInt(config.pauseStart || element.dataset.pauseStart) || 1000,
            showCursor: config.showCursor !== false && element.dataset.showCursor !== 'false',
            cursorChar: config.cursorChar || element.dataset.cursorChar || '|',
            infiniteLoop: config.infiniteLoop !== false && element.dataset.infiniteLoop !== 'false',
            ...config
        };
        
        this.currentIndex = 0;
        this.isTyping = false;
        this.isDeleting = false;
        this.timeoutId = null;
        this.isVisible = false;
        
        // Set up intersection observer for performance
        this.setupIntersectionObserver();
        this.init();
    }
    
    init() {
        // Set up DOM elements
        if (this.textElement) {
            this.textElement.textContent = '';
        }
        
        if (this.cursorElement) {
            this.cursorElement.textContent = this.config.showCursor ? this.config.cursorChar : '';
            this.cursorElement.style.display = this.config.showCursor ? 'inline-block' : 'none';
        }
        
        // Mark container as active
        this.element.setAttribute('data-typewriter-active', 'true');
        
        // Respect reduced motion preference
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            this.showCompleteText();
            return;
        }
        
        // Start animation when visible
        if (this.isVisible) {
            this.startAnimation();
        }
    }
    
    setupIntersectionObserver() {
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    this.isVisible = entry.isIntersecting;
                    if (this.isVisible && !this.isTyping) {
                        this.startAnimation();
                    }
                });
            }, { threshold: 0.1 });
            
            observer.observe(this.element);
        } else {
            // Fallback for older browsers
            this.isVisible = true;
        }
    }
    
    startAnimation() {
        if (this.isTyping) return;
        
        this.isTyping = true;
        this.currentIndex = 0;
        this.typeCharacter();
    }
    
    typeCharacter() {
        if (!this.isVisible) {
            this.isTyping = false;
            return;
        }
        
        if (this.currentIndex < this.config.text.length) {
            // Typing forward
            const currentText = this.config.text.substring(0, this.currentIndex + 1);
            if (this.textElement) {
                this.textElement.textContent = currentText;
            }
            
            this.currentIndex++;
            this.timeoutId = setTimeout(() => this.typeCharacter(), this.config.typingSpeed);
        } else {
            // Finished typing
            if (this.config.infiniteLoop) {
                this.timeoutId = setTimeout(() => this.deleteCharacter(), this.config.pauseEnd);
            } else {
                this.finish();
            }
        }
    }
    
    deleteCharacter() {
        if (!this.isVisible) {
            this.isTyping = false;
            return;
        }
        
        if (this.currentIndex > 0) {
            // Deleting backward
            this.currentIndex--;
            const currentText = this.config.text.substring(0, this.currentIndex);
            if (this.textElement) {
                this.textElement.textContent = currentText;
            }
            
            this.timeoutId = setTimeout(() => this.deleteCharacter(), this.config.deleteSpeed);
        } else {
            // Finished deleting, pause then restart
            this.timeoutId = setTimeout(() => this.typeCharacter(), this.config.pauseStart);
        }
    }
    
    showCompleteText() {
        // For reduced motion users or fallback
        if (this.textElement) {
            this.textElement.textContent = this.config.text;
        }
        if (this.cursorElement && this.config.showCursor) {
            this.cursorElement.style.animation = 'none';
            this.cursorElement.style.opacity = '0.7';
        }
        this.isTyping = false;
    }
    
    finish() {
        this.isTyping = false;
        if (this.cursorElement && this.config.showCursor) {
            this.cursorElement.style.animation = 'none';
            this.cursorElement.style.opacity = '0.7';
        }
    }
    
    destroy() {
        if (this.timeoutId) {
            clearTimeout(this.timeoutId);
        }
        this.isTyping = false;
        this.element.removeAttribute('data-typewriter-active');
    }
    
    restart() {
        this.destroy();
        this.currentIndex = 0;
        this.init();
    }
    
    updateConfig(newConfig) {
        this.config = { ...this.config, ...newConfig };
        this.restart();
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeTypewriterBlocks();
});

// Also initialize on dynamic content load (for AJAX/SPA)
if (typeof window.addEventListener === 'function') {
    window.addEventListener('load', initializeTypewriterBlocks);
}

function initializeTypewriterBlocks() {
    const typewriterBlocks = document.querySelectorAll('.wp-block-advanced-svg-animator-typewriter-text .typewriter-text-container');
    
    typewriterBlocks.forEach(block => {
        // Skip if already initialized
        if (block.typewriterInstance) {
            return;
        }
        
        // Create and store instance
        block.typewriterInstance = new TypewriterTextAnimation(block);
    });
}

// Export for manual usage and testing
window.TypewriterTextAnimation = TypewriterTextAnimation;
window.initializeTypewriterBlocks = initializeTypewriterBlocks;
