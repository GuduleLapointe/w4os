/**
 * Installation Wizard JavaScript
 * Handles form interactions and dynamic behavior
 */

// Wizard interaction functions

/**
 * Select choice option and handle sub-field validation
 */
function selectChoice(fieldId, value) {
    console.log('selectChoice called:', fieldId, value);
    
    const hiddenInput = document.getElementById(fieldId);
    if (hiddenInput) {
        hiddenInput.value = value;
    }
    
    // Update visual selection for the specific field
    const fieldContainer = document.querySelector(`[data-field="${fieldId}"]`);
    if (!fieldContainer) {
        console.warn('Field container not found for:', fieldId);
        return;
    }
    
    const choiceOptions = fieldContainer.querySelectorAll('.choice-option');
    choiceOptions.forEach(option => {
        option.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
        option.classList.add('border-secondary');
    });
    
    // Highlight selected option
    const selectedOption = fieldContainer.querySelector(`[data-value="${value}"]`);
    if (selectedOption) {
        selectedOption.classList.remove('border-secondary');
        selectedOption.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
    }
    
    // Hide all sub-fields for this field and disable their validation
    const allSubFields = fieldContainer.querySelectorAll('.choice-sub-fields');
    allSubFields.forEach(subField => {
        subField.classList.add('d-none');
        // Disable validation for hidden fields
        const inputs = subField.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.hasAttribute('required')) {
                input.dataset.originalRequired = 'true';
                input.removeAttribute('required');
            }
        });
    });
    
    // Show selected sub-fields and restore validation
    const selectedSubFields = fieldContainer.querySelector(`#${fieldId}_${value}_fields`);
    if (selectedSubFields) {
        selectedSubFields.classList.remove('d-none');
        // Restore validation for visible fields
        const inputs = selectedSubFields.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.dataset.originalRequired === 'true') {
                input.setAttribute('required', 'required');
            }
        });
    }
}

/**
 * Select method in accordion and handle validation
 */
function selectMethod(methodValue) {
    // Update radio button
    const radioButton = document.querySelector('input[value="' + methodValue + '"]');
    if (radioButton) {
        radioButton.checked = true;
    }
    
    // Hide all method bodies and disable their validation
    const allBodies = document.querySelectorAll('.method-body');
    allBodies.forEach(body => {
        body.classList.add('d-none');
        // Disable validation for hidden fields
        const inputs = body.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.hasAttribute('required')) {
                input.dataset.originalRequired = 'true';
                input.removeAttribute('required');
            }
        });
    });
    
    // Show selected method body and restore validation
    const selectedBody = document.getElementById(methodValue + '-body');
    if (selectedBody) {
        selectedBody.classList.remove('d-none');
        // Restore validation for visible fields
        const inputs = selectedBody.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.dataset.originalRequired === 'true') {
                input.setAttribute('required', 'required');
            }
        });
    }
    
    // Update header styling
    const allHeaders = document.querySelectorAll('.method-header');
    allHeaders.forEach(header => {
        header.classList.remove('bg-primary', 'bg-opacity-10', 'border-primary');
        header.classList.add('bg-light', 'border-secondary');
    });
    
    const selectedHeader = selectedBody ? selectedBody.previousElementSibling : null;
    if (selectedHeader) {
        selectedHeader.classList.remove('bg-light', 'border-secondary');
        selectedHeader.classList.add('bg-primary', 'bg-opacity-10', 'border-primary');
    }
}

/**
 * Toggle database credentials fields
 */
function toggleDbCredentials(fieldId) {
    const checkbox = document.getElementById(`${fieldId}_use_default`);
    const fieldsContainer = document.getElementById(`${fieldId}_fields`);
    
    if (checkbox && fieldsContainer) {
        if (checkbox.checked) {
            // Hide fields and disable validation
            fieldsContainer.style.display = 'none';
            const inputs = fieldsContainer.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                // Store original required state and remove required attribute
                if (input.hasAttribute('required')) {
                    input.dataset.originalRequired = 'true';
                    input.removeAttribute('required');
                }
                input.setAttribute('disabled', 'disabled');
            });
        } else {
            // Show fields and re-enable them
            fieldsContainer.style.display = 'block';
            const inputs = fieldsContainer.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.removeAttribute('disabled');
                // Restore original required state
                if (input.dataset.originalRequired === 'true') {
                    input.setAttribute('required', 'required');
                }
            });
        }
    }
}

/**
 * Handle mutual-exclusive fields - disable inactive fields
 */
function toggleMutualExclusive(element) {
    const fieldset = element.closest('.mutual-exclusive');
    if (!fieldset) return;
    
    const inputs = fieldset.querySelectorAll('input');
    const hasValue = element.value.trim() !== '';
    
    inputs.forEach(input => {
        if (input === element) {
            // Current input - enable if it has value
            if (hasValue) {
                input.removeAttribute('disabled');
            }
        } else {
            // Other inputs in same fieldset
            if (hasValue) {
                // Disable other fields
                input.setAttribute('disabled', 'disabled');
            } else {
                // Re-enable other fields if current input is empty
                input.removeAttribute('disabled');
            }
        }
    });
}

/**
 * Clear input field value and trigger mutual exclusive handling
 */
function clearInputField(fieldId) {
    const input = document.getElementById(fieldId);
    if (input) {
        input.value = '';
        toggleMutualExclusive(input);
    }
}

/**
 * Clear file input field
 */
function clearFileInput(fieldId) {
    const fileInput = document.getElementById(fieldId);
    if (fileInput) {
        fileInput.value = '';
        // Trigger change event to update any related UI
        fileInput.dispatchEvent(new Event('change'));
    }
}

/**
 * Update color text input when color picker changes
 */
function updateColorValue(fieldId) {
    const colorInput = document.getElementById(fieldId);
    const textInput = document.getElementById(fieldId + '_text');
    if (colorInput && textInput) {
        textInput.value = colorInput.value;
    }
}

/**
 * Update color picker when text input changes
 */
function updateColorPicker(fieldId) {
    const colorInput = document.getElementById(fieldId);
    const textInput = document.getElementById(fieldId + '_text');
    if (colorInput && textInput && /^#[0-9A-Fa-f]{6}$/.test(textInput.value)) {
        colorInput.value = textInput.value;
    }
}

/**
 * Add new field to multiple field container
 */
function addMultipleField(button) {
    const fieldItem = button.closest('.multiple-field-item');
    const container = fieldItem.closest('.multiple-fields-container');
    const fieldType = container.dataset.fieldType;
    const fieldId = container.dataset.fieldId;
    
    if (!container) return;
    
    const currentFields = container.querySelectorAll('.multiple-field-item');
    const newIndex = currentFields.length;
    
    // Clone the current field item
    const newFieldItem = fieldItem.cloneNode(true);
    newFieldItem.dataset.index = newIndex;
    
    // Update field names and IDs
    const inputs = newFieldItem.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        // Clear the value
        if (input.type === 'file') {
            input.value = '';
        } else if (input.type === 'color') {
            input.value = '#000000';
        } else {
            input.value = '';
        }
        
        // Update name and id
        const baseName = fieldId;
        const baseId = fieldId + '_' + newIndex;
        input.name = baseName + '[' + newIndex + ']';
        input.id = baseId + (input.id.includes('_text') ? '_text' : '');
        
        // Remove required attribute from non-first fields
        if (newIndex > 0) {
            input.removeAttribute('required');
        }
    });
    
    // Insert the new field
    container.insertBefore(newFieldItem, container.querySelector('.form-text') || container.lastElementChild);
    
    // Update button visibility
    updateMultipleFieldButtons(container);
    
    // Focus on the new field
    const newInput = newFieldItem.querySelector('input, textarea');
    if (newInput) {
        newInput.focus();
    }
}

/**
 * Remove field from multiple field container
 */
function removeMultipleField(button) {
    const fieldItem = button.closest('.multiple-field-item');
    const container = fieldItem.closest('.multiple-fields-container');
    
    if (!container) return;
    
    const currentFields = container.querySelectorAll('.multiple-field-item');
    
    // Don't remove if it's the only field - clear it instead
    if (currentFields.length <= 1) {
        const input = fieldItem.querySelector('input, textarea');
        if (input) {
            if (input.type === 'file') {
                input.value = '';
            } else if (input.type === 'color') {
                input.value = '#000000';
                const textInput = fieldItem.querySelector('input[type="text"]');
                if (textInput) textInput.value = '#000000';
            } else {
                input.value = '';
            }
        }
        return;
    }
    
    fieldItem.remove();
    
    // Re-index remaining fields
    const remainingFields = container.querySelectorAll('.multiple-field-item');
    const fieldId = container.dataset.fieldId;
    
    remainingFields.forEach((field, index) => {
        field.dataset.index = index;
        const inputs = field.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            const baseName = fieldId;
            const baseId = fieldId + '_' + index;
            input.name = baseName + '[' + index + ']';
            input.id = baseId + (input.id.includes('_text') ? '_text' : '');
            
            // First field should be required if originally required
            if (index === 0 && field.querySelector('[data-originally-required]')) {
                input.setAttribute('required', '');
            }
        });
    });
    
    // Update button visibility
    updateMultipleFieldButtons(container);
}

/**
 * Update add/remove button visibility for multiple fields
 */
function updateMultipleFieldButtons(container) {
    const fieldItems = container.querySelectorAll('.multiple-field-item');
    
    fieldItems.forEach((item, index) => {
        const removeBtn = item.querySelector('.multiple-field-remove');
        const addBtn = item.querySelector('.multiple-field-add');
        
        // Show/hide add button (only on last field)
        if (addBtn) {
            addBtn.style.display = (index === fieldItems.length - 1) ? 'block' : 'none';
        }
        
        // Remove button is always visible, but disabled if only one field
        if (removeBtn) {
            removeBtn.disabled = fieldItems.length <= 1;
        }
    });
}

/**
 * Add new field group to multiple groups container
 */
function addFieldGroup(button) {
    const groupItem = button.closest('.multiple-group-item');
    const container = groupItem.closest('.multiple-groups-container');
    const fieldId = container.dataset.fieldId;
    
    if (!container) return;
    
    const currentGroups = container.querySelectorAll('.multiple-group-item');
    const newIndex = currentGroups.length;
    
    // Clone the current group
    const newGroup = groupItem.cloneNode(true);
    newGroup.dataset.groupIndex = newIndex;
    
    // Update group title
    const legend = newGroup.querySelector('.field-group-legend');
    if (legend) {
        legend.textContent = 'Group ' + (newIndex + 1);
    }
    
    // Clear all input values and update names/IDs
    const inputs = newGroup.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        // Clear the value
        if (input.type === 'checkbox' || input.type === 'radio') {
            input.checked = false;
        } else if (input.type === 'file') {
            input.value = '';
        } else if (input.type === 'color') {
            input.value = '#000000';
        } else {
            input.value = '';
        }
        
        // Update name and id to reflect new group index
        const nameMatch = input.name.match(/^(.+)\[\d+\](\[.+\])$/);
        const idMatch = input.id.match(/^(.+)_\d+(_.*)?$/);
        
        if (nameMatch) {
            input.name = nameMatch[1] + '[' + newIndex + ']' + nameMatch[2];
        }
        if (idMatch) {
            input.id = idMatch[1] + '_' + newIndex + (idMatch[2] || '');
        }
    });
    
    // Insert the new group
    container.appendChild(newGroup);
    
    // Update button visibility
    updateFieldGroupButtons(container);
    
    // Focus on first input in new group
    const firstInput = newGroup.querySelector('input, textarea');
    if (firstInput) {
        firstInput.focus();
    }
}

/**
 * Remove field group from multiple groups container
 */
function removeFieldGroup(button) {
    const groupItem = button.closest('.multiple-group-item');
    const container = groupItem.closest('.multiple-groups-container');
    
    if (!container) return;
    
    const currentGroups = container.querySelectorAll('.multiple-group-item');
    
    // Don't remove if it's the only group - clear it instead
    if (currentGroups.length <= 1) {
        const inputs = groupItem.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            if (input.type === 'checkbox' || input.type === 'radio') {
                input.checked = false;
            } else if (input.type === 'file') {
                input.value = '';
            } else if (input.type === 'color') {
                input.value = '#000000';
            } else {
                input.value = '';
            }
        });
        return;
    }
    
    groupItem.remove();
    
    // Re-index remaining groups
    const remainingGroups = container.querySelectorAll('.multiple-group-item');
    const fieldId = container.dataset.fieldId;
    
    remainingGroups.forEach((group, index) => {
        group.dataset.groupIndex = index;
        
        // Update group title
        const legend = group.querySelector('.field-group-legend');
        if (legend) {
            legend.textContent = 'Group ' + (index + 1);
        }
        
        // Update all input names and IDs
        const inputs = group.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            const nameMatch = input.name.match(/^(.+)\[\d+\](\[.+\])$/);
            const idMatch = input.id.match(/^(.+)_\d+(_.*)?$/);
            
            if (nameMatch) {
                input.name = nameMatch[1] + '[' + index + ']' + nameMatch[2];
            }
            if (idMatch) {
                input.id = idMatch[1] + '_' + index + (idMatch[2] || '');
            }
        });
    });
    
    // Update button visibility
    updateFieldGroupButtons(container);
}

/**
 * Update add/remove button visibility for field groups
 */
function updateFieldGroupButtons(container) {
    const groupItems = container.querySelectorAll('.multiple-group-item');
    
    groupItems.forEach((item, index) => {
        const removeBtn = item.querySelector('.group-remove');
        const addBtn = item.querySelector('.group-add');
        
        // Show/hide add button (only on last group)
        if (addBtn) {
            addBtn.style.display = (index === groupItems.length - 1) ? 'block' : 'none';
        }
        
        // Remove button is always visible, but disabled if only one group
        if (removeBtn) {
            removeBtn.disabled = groupItems.length <= 1;
        }
    });
}

/**
 * Go back to previous step in multistep form
 */
function previousStep() {
    console.log('previousStep() called');
    // Get current form
    const form = document.querySelector('.helpers-form');
    if (!form) {
        console.error('No form found for previousStep');
        return;
    }
    
    // Create hidden input to indicate we want to go back
    const goBackInput = document.createElement('input');
    goBackInput.type = 'hidden';
    goBackInput.name = 'go_back';
    goBackInput.value = '1';
    form.appendChild(goBackInput);
    
    console.log('Submitting form with go_back=1');
    // Submit the form
    form.submit();
}

/**
 * Reset wizard - clear all data and go to first step
 */
function resetForm() {
    if (confirm('Are you sure you want to reset the form? All progress will be lost.')) {
        // Create a form to submit reset request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;
        
        // Add reset parameter
        const resetInput = document.createElement('input');
        resetInput.type = 'hidden';
        resetInput.name = 'reset_form';
        resetInput.value = '1';
        
        form.appendChild(resetInput);
        document.body.appendChild(form);
        form.submit();
    }
}

/**
 * Initialize form validation states on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize mutual-exclusive fields
    const mutualExclusiveFieldsets = document.querySelectorAll('.mutual-exclusive');
    mutualExclusiveFieldsets.forEach(fieldset => {
        const inputs = fieldset.querySelectorAll('input');
        inputs.forEach(input => {
            if (input.value.trim() !== '') {
                toggleMutualExclusive(input);
            }
        });
    });
    
    // Initialize choice fields (select-nested) - only manage required attribute for these
    const choiceFields = document.querySelectorAll('.choice-sub-fields');
    choiceFields.forEach(subField => {
        if (subField.classList.contains('d-none')) {
            // Disable validation for hidden choice sub-fields
            const inputs = subField.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (input.hasAttribute('required')) {
                    input.dataset.originalRequired = 'true';
                    input.removeAttribute('required');
                }
            });
        }
    });
    
    // Initialize accordion fields (select-accordion) - only manage required attribute for these
    const accordionBodies = document.querySelectorAll('.method-body');
    accordionBodies.forEach(body => {
        if (body.classList.contains('d-none')) {
            // Disable validation for hidden accordion fields
            const inputs = body.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (input.hasAttribute('required')) {
                    input.dataset.originalRequired = 'true';
                    input.removeAttribute('required');
                }
            });
        }
    });

    // Initialize database credentials checkboxes
    const dbCredentialCheckboxes = document.querySelectorAll('input[id$="_use_default"]');
    dbCredentialCheckboxes.forEach(checkbox => {
        if (checkbox.checked) {
            // Extract field ID from checkbox ID (remove _use_default suffix)
            const fieldId = checkbox.id.replace('_use_default', '');
            toggleDbCredentials(fieldId);
        }
    });

    // Set up initial state for checked radio buttons
    document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
        if (radio.name === 'config_method') {
            selectChoice('config_method', radio.value);
        } else if (radio.name === 'connection_method') {
            selectChoice('connection_method', radio.value);
        }
    });

    // Initialize button visibility for multiple fields
    document.querySelectorAll('.multiple-fields-container').forEach(updateMultipleFieldButtons);
    
    // Initialize button visibility for multiple groups
    document.querySelectorAll('.multiple-groups-container').forEach(updateFieldGroupButtons);
    
    // Initialize color field synchronization
    document.querySelectorAll('input[type="color"]').forEach(function(colorInput) {
        const textInput = document.getElementById(colorInput.id + '_text');
        if (textInput) {
            colorInput.addEventListener('input', function() {
                textInput.value = this.value;
            });
        }
    });
});
