/**
 * Admin settings js
 * Scripts to fine tune settings pages forms
 */

document.addEventListener('DOMContentLoaded', function() {
    // Toggle credential fields based on the value of "Use defaults" checkbox
    const defaultsCheckboxes = document.querySelectorAll('input.use-defaults');
    defaultsCheckboxes.forEach(cb => {
        const container = cb.closest('td');
        function toggleFields() {
            container.querySelectorAll('.w4os-credentials').forEach(div => {
                // Hide all credentials except the one containing the checkbox
                if (!div.classList.contains('credentials-use-defaults')) {
                    div.style.display = cb.checked ? 'none' : '';
                }
            });
        }
        cb.addEventListener('change', toggleFields);
        toggleFields();
    });
});
