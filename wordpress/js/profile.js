document.addEventListener('DOMContentLoaded', function() {
    console.log('Profile JS loaded');
    // Initialize all tabbed interfaces
    const tabContainers = document.querySelectorAll('[data-tabs]');
    
    tabContainers.forEach(container => {
        console.log('container', container);

        const tabs = container.querySelectorAll('.profile-tab');
        const contents = container.parentElement.querySelectorAll('[data-tabs-content] .tab-section');

        tabs.forEach(tab => {
            tab.addEventListener('click', function(event) {
                console.log('clicked ' + this.getAttribute('data-tab'));
                event.preventDefault();
                
                // Remove active class from all tabs
                tabs.forEach(t => t.classList.remove('active'));
                
                // Hide all content sections
                contents.forEach(content => content.style.display = 'none');
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Show corresponding content
                const target = this.getAttribute('data-tab');
                const contentToShow = container.parentElement.querySelector(`#tab-${target}`);
                if (contentToShow) {
                    contentToShow.style.display = 'block';
                }
            });
        });

        // Show default active tab content
        const activeTab = container.querySelector('.profile-tab.active');
        if (activeTab) {
            const target = activeTab.getAttribute('data-tab');
            const contentToShow = container.parentElement.querySelector(`#tab-${target}`);
            if (contentToShow) {
                contentToShow.style.display = 'block';
            }
        }
    });
});
