/**
 * Avatar Name Validation and Auto-conversion
 * 
 * Provides real-time validation and conversion of avatar names
 * - Converts accented characters to ASCII equivalents
 * - Validates pattern in real-time
 * - Shows helpful feedback to users
 */

document.addEventListener('DOMContentLoaded', function() {
    // Accent conversion map
    const accentMap = {
        'à': 'a', 'á': 'a', 'â': 'a', 'ã': 'a', 'ä': 'a', 'å': 'a', 'æ': 'ae',
        'ç': 'c', 'è': 'e', 'é': 'e', 'ê': 'e', 'ë': 'e', 'ì': 'i', 'í': 'i',
        'î': 'i', 'ï': 'i', 'ñ': 'n', 'ò': 'o', 'ó': 'o', 'ô': 'o', 'õ': 'o',
        'ö': 'o', 'ø': 'o', 'ù': 'u', 'ú': 'u', 'û': 'u', 'ü': 'u', 'ý': 'y',
        'ÿ': 'y', 'À': 'A', 'Á': 'A', 'Â': 'A', 'Ã': 'A', 'Ä': 'A', 'Å': 'A',
        'Æ': 'AE', 'Ç': 'C', 'È': 'E', 'É': 'E', 'Ê': 'E', 'Ë': 'E', 'Ì': 'I',
        'Í': 'I', 'Î': 'I', 'Ï': 'I', 'Ñ': 'N', 'Ò': 'O', 'Ó': 'O', 'Ô': 'O',
        'Õ': 'O', 'Ö': 'O', 'Ø': 'O', 'Ù': 'U', 'Ú': 'U', 'Û': 'U', 'Ü': 'U',
        'Ý': 'Y'
    };

    /**
     * Convert accented characters to ASCII
     */
    function removeAccents(str) {
        return str.replace(/[àáâãäåæçèéêëìíîïñòóôõöøùúûüýÿÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝ]/g, function(match) {
            return accentMap[match] || match;
        });
    }

    /**
     * Validate and clean avatar name part (firstname or lastname)
     * This function must follow EXACTLY the same logic as PHP w4os_validate_name_part()
     * 
     * @param string value The name part to validate
     * @return string The cleaned/validated name part
     */
    function validateNamePart(value) {
        const original = value;
        
        // Remove accents
        let cleaned = removeAccents(value);
        
        // Remove invalid characters (keep only letters and numbers)
        cleaned = cleaned.replace(/[^A-Za-z0-9]/g, '');
        
        // Ensure first character is a letter
        if (cleaned.length > 0 && /[0-9]/.test(cleaned.charAt(0))) {
            cleaned = cleaned.replace(/^[0-9]+/, '');
        }
        
        // Fix capitalization only if all caps or all lowercase
        if (cleaned.length > 0) {
            if (cleaned === cleaned.toUpperCase()) {
                // All uppercase -> fix to proper case
                cleaned = cleaned.charAt(0).toUpperCase() + cleaned.slice(1).toLowerCase();
            } else if (cleaned === cleaned.toLowerCase()) {
                // All lowercase -> capitalize first letter
                cleaned = cleaned.charAt(0).toUpperCase() + cleaned.slice(1);
            }
            // Otherwise leave mixed case as-is (DeVito, McAfee, etc.)
        }
        
        return cleaned;
    }

    /**
     * Show validation feedback
     */
    function showFeedback(input, message, isValid) {
        // Remove existing feedback
        const existingFeedback = input.parentNode.querySelector('.w4os-name-feedback');
        if (existingFeedback) {
            existingFeedback.remove();
        }

        if (message) {
            const feedback = document.createElement('p');
            feedback.className = 'w4os-name-feedback';
            feedback.style.fontSize = '12px';
            feedback.style.margin = '5px 0 0 0';
            feedback.style.color = isValid ? '#46b450' : '#dc3232';
            feedback.textContent = message;
            input.parentNode.appendChild(feedback);
        }
    }

    /**
     * Validate name pattern
     */
    function validatePattern(value) {
        const pattern = /^[A-Za-z][A-Za-z0-9]*$/;
        return pattern.test(value);
    }

    // Find name input fields
    const nameInputs = [
        document.getElementById('w4os_firstname'),
        document.getElementById('w4os_lastname'),
        document.getElementById('avatar_name'), // v3 full name field
        // Also check for any input with name containing firstname/lastname
        ...document.querySelectorAll('input[name*="firstname"]'),
        ...document.querySelectorAll('input[name*="lastname"]'),
        ...document.querySelectorAll('input[name*="first_name"]'),
        ...document.querySelectorAll('input[name*="last_name"]')
    ].filter(input => input); // Remove null values

    nameInputs.forEach(function(input) {
        if (!input) return;

        // Handle full name field (avatar_name) differently
        if (input.id === 'avatar_name' || input.name === 'avatar_name') {
            input.addEventListener('input', function(e) {
                let value = e.target.value;
                let original = value;
                
                // Convert accents
                value = removeAccents(value);
                
                // Basic cleanup - remove invalid chars except space
                value = value.replace(/[^A-Za-z0-9 ]/g, '');
                
                // Ensure proper format: "Firstname Lastname"
                let parts = value.split(/\s+/).filter(part => part.length > 0);
                if (parts.length > 2) {
                    parts = parts.slice(0, 2); // Keep only first two parts
                }
                
                // Validate each part
                parts = parts.map(part => {
                    // Ensure starts with letter
                    if (part.length > 0 && /[0-9]/.test(part.charAt(0))) {
                        part = part.substring(1);
                    }
                    // Capitalize first letter
                    if (part.length > 0) {
                        part = part.charAt(0).toUpperCase() + part.slice(1);
                    }
                    return part;
                }).filter(part => part.length > 0);
                
                value = parts.join(' ');
                
                // Update field if changed
                if (value !== original) {
                    e.target.value = value;
                }
                
                // Validate full pattern
                const fullPattern = /^[A-Za-z][A-Za-z0-9]* [A-Za-z][A-Za-z0-9]*$/;
                if (value.length === 0) {
                    showFeedback(input, '', true);
                } else if (fullPattern.test(value)) {
                    showFeedback(input, '✓ Valid avatar name format', true);
                } else if (parts.length === 1) {
                    showFeedback(input, 'Please add a last name', false);
                } else {
                    showFeedback(input, 'Names must start with a letter and contain only letters and numbers', false);
                }
            });
        } else {
            // Handle individual name fields (firstname/lastname)
            input.addEventListener('input', function(e) {
                let value = e.target.value;
                let original = value;
                
                // Auto-convert and validate
                value = validateNamePart(value);
                
                // Update field if it was changed
                if (value !== original) {
                    e.target.value = value;
                    if (original.length > 0) {
                        showFeedback(input, 'Converted to valid characters', true);
                    }
                }
                
                // Validate
                if (validatePattern(value)) {
                    showFeedback(input, null, true);
                } else {
                    showFeedback(input, 'Names must start with a letter and contain only letters and numbers', false);
                }
            });
        }

        // Also validate on blur
        input.addEventListener('blur', function(e) {
            if (e.target.value.length > 0) {
                // Trigger input event to revalidate
                e.target.dispatchEvent(new Event('input'));
            }
        });
    });

    // Add CSS styles for feedback
    const style = document.createElement('style');
    style.textContent = `
        .w4os-name-feedback {
            font-size: 12px !important;
            margin: 5px 0 0 0 !important;
            padding: 0 !important;
        }
        input:invalid {
            border-color: #dc3232;
            box-shadow: 0 0 2px rgba(220, 50, 50, 0.3);
        }
        input:valid {
            border-color: #46b450;
        }
    `;
    document.head.appendChild(style);
});
