/**
 * Tom-Select Compatibility Layer
 * Provides jQuery wrapper for Tom-Select 2.3.1
 * Maintains backward compatibility with Selectize.js API
 * 
 * @version 1.0.0
 * @author PSOURCE
 */
(function($, TomSelect) {
    'use strict';

    if (!$ || typeof TomSelect === 'undefined') {
        console.error('Tom-Select Compatibility: jQuery and TomSelect required');
        return;
    }

    // Store instances per element
    const instances = new WeakMap();

    /**
     * jQuery wrapper for Tom-Select (Selectize.js compatible API)
     * @param {object} options - Configuration options
     * @returns {jQuery}
     */
    $.fn.selectize = function(options) {
        return this.each(function() {
            const element = this;
            const $element = $(element);

            // Destroy existing instance
            const existingInstance = instances.get(element);
            if (existingInstance) {
                existingInstance.destroy();
                instances.delete(element);
            }

            // Map Selectize options to Tom-Select
            const mappedOptions = mapSelectizeToTomSelect(options || {});

            // Create new Tom-Select instance
            try {
                const tsInstance = new TomSelect(element, mappedOptions);
                instances.set(element, tsInstance);

                // Store instance reference (Selectize compatibility)
                $element.data('selectize', tsInstance);
                $element.data('tom-select-instance', tsInstance);

                // Expose instance on element.selectize (Selectize compatibility)
                if (!element.selectize) {
                    element.selectize = tsInstance;
                }
            } catch (error) {
                console.error('Tom-Select init failed:', error);
            }
        });
    };

    /**
     * Map Selectize.js options to Tom-Select options
     * @param {object} selectizeOptions
     * @returns {object} Tom-Select options
     */
    function mapSelectizeToTomSelect(selectizeOptions) {
        const tomSelectOptions = { ...selectizeOptions };

        // Most options are compatible, but some need mapping:

        // plugins: Selectize uses string array, Tom-Select uses object
        if (selectizeOptions.plugins && Array.isArray(selectizeOptions.plugins)) {
            const pluginsObj = {};
            selectizeOptions.plugins.forEach(plugin => {
                pluginsObj[plugin] = {};
            });
            tomSelectOptions.plugins = pluginsObj;
        }

        // render: Both use similar structure, no mapping needed

        // load: Signature is compatible
        // Selectize: load(query, callback)
        // Tom-Select: load(query, callback)
        // ✓ Compatible

        // onChange: Selectize uses onChange, Tom-Select uses onChange
        // ✓ Compatible

        // onItemAdd/onItemRemove: Compatible
        // ✓ Compatible

        // maxItems: Compatible
        // ✓ Compatible

        // delimiter: Compatible  
        // ✓ Compatible

        // create: Selectize uses true/false/function, Tom-Select uses same
        // ✓ Compatible

        // Specific Tom-Select enhancements (optional)
        if (!tomSelectOptions.plugins) {
            tomSelectOptions.plugins = {};
        }
        
        // Enable useful Tom-Select plugins by default
        if (!tomSelectOptions.plugins.remove_button && tomSelectOptions.maxItems !== 1) {
            // Add remove button for multi-select
            tomSelectOptions.plugins.remove_button = {
                title: 'Entfernen'
            };
        }

        return tomSelectOptions;
    }

    /**
     * Get Tom-Select instance from element
     * @param {jQuery|HTMLElement} element
     * @returns {TomSelect|null}
     */
    $.fn.getSelectizeInstance = function() {
        if (this.length === 0) return null;
        const element = this[0];
        return instances.get(element) || null;
    };

    // Expose global helper
    if (window) {
        window.MMTomSelect = {
            getInstance: function(element) {
                const el = element instanceof $ ? element[0] : element;
                return instances.get(el);
            },
            destroyAll: function() {
                console.info('Tom-Select: Use element.selectize.destroy() for specific instances');
            }
        };
    }

})(jQuery, TomSelect);
