/**
 * Vanilla JavaScript Tab Navigation
 * Handles tab switching in admin settings without jQuery
 */
(function() {
    'use strict';
    
    function initTabs() {
        var tabLinks = document.querySelectorAll('.mm-tab-link');
        
        if (tabLinks.length === 0) {
            return; // No tabs on this page
        }
        
        tabLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                var target = this.getAttribute('data-target') || ('#' + this.getAttribute('data-tab'));
                var url = this.getAttribute('href');
                
                // Update URL query (?tab=...)
                if (window.history && window.history.replaceState) {
                    window.history.replaceState({}, '', url);
                }
                
                // Toggle active class on tabs
                var navTabs = document.querySelectorAll('.nav-tabs li');
                navTabs.forEach(function(tab) {
                    tab.classList.remove('active');
                });
                
                var parentLi = this.closest('li');
                if (parentLi) {
                    parentLi.classList.add('active');
                }
                
                // Reset inline styles and switch panes
                var tabPanes = document.querySelectorAll('.tab-pane');
                tabPanes.forEach(function(pane) {
                    pane.removeAttribute('style');
                    pane.classList.remove('active');
                });
                
                // Activate target pane
                var targetPane = document.querySelector(target);
                if (targetPane) {
                    targetPane.classList.add('active');
                }
            });
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTabs);
    } else {
        initTabs();
    }
})();
