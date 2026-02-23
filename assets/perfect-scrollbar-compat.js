/**
 * Perfect Scrollbar Compatibility Layer
 * Provides jQuery wrapper for Perfect Scrollbar 1.5.5
 * Maintains backward compatibility with 0.5.3 jQuery API
 * 
 * @version 1.0.0
 * @author PSOURCE
 */
(function($, PerfectScrollbar) {
    'use strict';

    if (!$ || typeof PerfectScrollbar === 'undefined') {
        console.error('Perfect Scrollbar Compatibility: jQuery and PerfectScrollbar required');
        return;
    }

    // Store instances per element
    const instances = new WeakMap();

    /**
     * jQuery wrapper for Perfect Scrollbar
     * @param {string|object} action - 'update', 'destroy', or options object
     * @returns {jQuery}
     */
    $.fn.perfectScrollbar = function(action) {
        return this.each(function() {
            const element = this;
            const $element = $(element);

            // Handle string commands
            if (typeof action === 'string') {
                const instance = instances.get(element);

                switch (action) {
                    case 'update':
                        if (instance) {
                            instance.update();
                        }
                        break;

                    case 'destroy':
                        if (instance) {
                            instance.destroy();
                            instances.delete(element);
                        }
                        break;

                    default:
                        console.warn(`Perfect Scrollbar: Unknown command "${action}"`);
                }
                return;
            }

            // Initialize or reinitialize
            const options = action || {};

            // Map old option names to new ones (if needed)
            const mappedOptions = {
                ...options,
                // suppressScrollX is still valid in 1.5.5
                // suppressScrollY is still valid in 1.5.5
            };

            // Destroy existing instance
            const existingInstance = instances.get(element);
            if (existingInstance) {
                existingInstance.destroy();
            }

            // Create new instance
            try {
                const psInstance = new PerfectScrollbar(element, mappedOptions);
                instances.set(element, psInstance);

                // Store instance reference on element (for debugging)
                $element.data('perfect-scrollbar-instance', psInstance);
            } catch (error) {
                console.error('Perfect Scrollbar init failed:', error);
            }
        });
    };

    // Expose global helper (optional)
    if (window) {
        window.MMPerfectScrollbar = {
            getInstance: function(element) {
                const el = element instanceof $ ? element[0] : element;
                return instances.get(el);
            },
            destroyAll: function() {
                // Not possible with WeakMap, but instances auto-cleanup
                console.info('Perfect Scrollbar: Instances will auto-cleanup when elements are removed');
            }
        };
    }

})(jQuery, PerfectScrollbar);
