/**
 * Modern Modal - Modernized replacement for jquery.leanModal
 * Pure Vanilla JavaScript with optional jQuery compatibility layer
 * 
 * @version 2.0.0
 * @license MIT
 */

(function(window) {
    'use strict';

    /**
     * Modern Modal Class
     */
    class ModernModal {
        constructor(trigger, options = {}) {
            this.trigger = typeof trigger === 'string' ? document.querySelector(trigger) : trigger;
            
            this.options = {
                top: options.top || 100,
                overlay: options.overlay !== undefined ? options.overlay : 0.5,
                closeButton: options.closeButton || null,
                width: options.width || 'auto',
                maxWidth: options.maxWidth || '600px',
                onOpen: options.onOpen || null,
                onClose: options.onClose || null
            };

            this.modal = null;
            this.overlay = null;
            this.isOpen = false;

            this.init();
        }

        init() {
            if (!this.trigger) {
                console.warn('ModernModal: Trigger element not found');
                return;
            }

            // Create overlay if it doesn't exist
            this.overlay = document.getElementById('mm_modal_overlay');
            if (!this.overlay) {
                this.overlay = document.createElement('div');
                this.overlay.id = 'mm_modal_overlay';
                this.overlay.className = 'mm-modal-overlay';
                document.body.appendChild(this.overlay);
            }

            // Get modal target from href or data-target
            const modalTarget = this.trigger.getAttribute('href') || this.trigger.getAttribute('data-target');
            if (modalTarget) {
                this.modal = document.querySelector(modalTarget);
            }

            if (!this.modal) {
                console.warn('ModernModal: Modal target not found');
                return;
            }

            // Setup event listeners
            this.setupEventListeners();
        }

        setupEventListeners() {
            // Trigger click
            this.trigger.addEventListener('click', (e) => {
                e.preventDefault();
                this.open();
            });

            // Overlay click to close
            this.overlay.addEventListener('click', (e) => {
                if (e.target === this.overlay) {
                    this.close();
                }
            });

            // Close button
            if (this.options.closeButton) {
                const closeBtn = typeof this.options.closeButton === 'string' 
                    ? this.modal.querySelector(this.options.closeButton)
                    : this.options.closeButton;
                
                if (closeBtn) {
                    closeBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.close();
                    });
                }
            }

            // ESC key to close
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });
        }

        open() {
            if (this.isOpen) return;

            this.isOpen = true;

            // Show overlay
            this.overlay.style.display = 'block';
            this.overlay.style.opacity = '0';
            
            // Fade in overlay
            setTimeout(() => {
                this.overlay.style.transition = 'opacity 200ms';
                this.overlay.style.opacity = this.options.overlay;
            }, 10);

            // Calculate modal position
            const modalHeight = this.modal.offsetHeight;
            const modalWidth = this.options.width === 'auto' ? this.modal.offsetWidth : this.options.width;
            
            // Position modal
            this.modal.style.display = 'block';
            this.modal.style.position = 'fixed';
            this.modal.style.zIndex = '11000';
            this.modal.style.left = '50%';
            this.modal.style.top = this.options.top + 'px';
            this.modal.style.marginLeft = -(modalWidth / 2) + 'px';
            
            if (this.options.width !== 'auto') {
                this.modal.style.width = this.options.width + 'px';
            }
            
            if (this.options.maxWidth) {
                this.modal.style.maxWidth = this.options.maxWidth;
            }

            // Prevent body scroll
            document.body.style.overflow = 'hidden';

            // Callback
            if (typeof this.options.onOpen === 'function') {
                this.options.onOpen(this);
            }
        }

        close() {
            if (!this.isOpen) return;

            this.isOpen = false;

            // Fade out overlay
            this.overlay.style.transition = 'opacity 200ms';
            this.overlay.style.opacity = '0';

            setTimeout(() => {
                this.overlay.style.display = 'none';
                this.modal.style.display = 'none';
                
                // Restore body scroll
                document.body.style.overflow = '';
            }, 200);

            // Callback
            if (typeof this.options.onClose === 'function') {
                this.options.onClose(this);
            }
        }

        destroy() {
            // Remove event listeners would go here
            // For simplicity, we'll keep them
            this.close();
        }
    }

    // jQuery compatibility layer
    if (window.jQuery) {
        jQuery.fn.modernModal = function(options) {
            return this.each(function() {
                const modal = new ModernModal(this, options);
                // Store instance on element
                jQuery(this).data('modernModal', modal);
            });
        };
    }

    // Expose to global scope
    window.ModernModal = ModernModal;

})(window);

/**
 * Legacy leanModal compatibility
 * This provides backward compatibility with old leanModal API
 */
if (window.jQuery) {
    jQuery.fn.leanModal = function(options) {
        console.warn('leanModal is deprecated. Please migrate to modernModal.');
        return this.modernModal(options);
    };
}
