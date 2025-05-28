document.addEventListener('DOMContentLoaded', function() {
    const filterFields = document.querySelectorAll('.filter-field');
    const filterButton = document.querySelector('input[name="filter_action"]');
    const resetButton = document.querySelector('.reset-filters'); // Updated selector to use class

    // Ensure buttons exist before proceeding
    if (!filterButton || !resetButton) {
        console.error('Filter or Reset button not found.');
        return;
    }

    // Store initial filter values
    const initialFilters = {};
    filterFields.forEach(field => {
        initialFilters[field.id] = field.value;
    });

    function checkFilters() {
        let filtersChanged = false;
        let anyFieldFilled = false;

        filterFields.forEach(field => {
            if (field.value !== initialFilters[field.id]) {
                filtersChanged = true;
            }
            if (field.value !== '') {
                anyFieldFilled = true;
            }
        });

        filterButton.disabled = !filtersChanged;
        resetButton.disabled = !anyFieldFilled;
    }

    filterFields.forEach(field => {
        field.addEventListener('change', checkFilters);
    });

    resetButton.addEventListener('click', function() {
        filterFields.forEach(field => {
            field.value = '';
        });
        checkFilters();

        // Form submission disabled, only clear filter fields
        // HTMLFormElement.prototype.submit.call(this.form);
    });

    checkFilters();
});
