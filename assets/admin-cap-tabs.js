/**
 * Capability Settings - Vertical Tab Switcher (Vanilla JS)
 */
(function() {
    'use strict';

    function initCapabilityTabs() {
        // Find all vertical tab links in capability settings
        const tabLinks = document.querySelectorAll('.nav-pills a[data-toggle="tab"]');
        
        if (!tabLinks.length) {
            console.log('No capability tabs found');
            return;
        }

        console.log('Capability tabs initialized, found ' + tabLinks.length + ' tabs');

        tabLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Capability tab clicked:', this.getAttribute('href'));
                
                // Get target tab from href
                const targetId = this.getAttribute('href');
                if (!targetId || !targetId.startsWith('#')) return;
                
                const targetPane = document.querySelector(targetId);
                if (!targetPane) {
                    console.warn('Target pane not found:', targetId);
                    return;
                }

                // Find the parent container (only work within .tab-content inside capability settings)
                const container = this.closest('.row');
                if (!container) return;

                // Remove active class from all tabs within this container
                const allTabs = container.querySelectorAll('.nav-pills li');
                const allPanes = container.querySelectorAll('.tab-content .tab-pane');
                
                allTabs.forEach(function(tab) {
                    tab.classList.remove('active');
                });
                
                allPanes.forEach(function(pane) {
                    pane.classList.remove('active');
                });

                // Add active class to clicked tab and target pane
                this.parentElement.classList.add('active');
                targetPane.classList.add('active');
                
                console.log('Activated pane:', targetId);
            });
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCapabilityTabs);
    } else {
        initCapabilityTabs();
    }
})();
