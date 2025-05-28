console.log( 'usermenu-classic-menu.js file loaded' );
document.addEventListener('DOMContentLoaded', function() {
    console.log( 'dom content triggered' );
    const addButton = document.querySelector('.add-avatar-links');
    const selectAllLink = document.querySelector('.avatar-menu-select-all-avatar-links');

    // Custom Select All functionality
    const selectAllCheckbox = document.getElementById('avatar-menu-select-all');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            console.log( 'select all checkbox changed' );
            const checkboxes = document.querySelectorAll('.menu-item-checkbox');
            checkboxes.forEach(function(checkbox) {
                console.log( 'enabling checkbox' );
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
    }

    // Optional: Handle removal of menu items
    const menuItemsContainer = document.querySelector('.menu-items');
    if (menuItemsContainer) {
        // ...existing code...
    }
});
