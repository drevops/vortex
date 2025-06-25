// Validation System - Field validation using named validation rules
import { getValidationRule, getValidationRuleNames } from './validation-rules.js';

export function validateField(fieldId, value) {
  const field = document.getElementById(fieldId);
  if (!field) {return true;}
  
  let errorElement = document.getElementById(fieldId + '-error');
  
  // Create error element if it doesn't exist
  if (!errorElement) {
    errorElement = document.createElement('div');
    errorElement.id = fieldId + '-error';
    errorElement.className = 'validation-error';
    errorElement.style.display = 'none';
    
    // Insert error element after the field
    field.parentNode.insertBefore(errorElement, field.nextSibling);
  }
  
  // Check if Joi is available
  if (!(window.Joi || window.joi)) {
    return true; // Return true if Joi not available
  }

  // Use joi (lowercase) if Joi (uppercase) is not available
  const Joi = window.Joi || window.joi;

  // Get the named validation rule for this field
  const validationRule = getValidationRule(fieldId);
  
  if (!validationRule) {
    // No validation rule defined for this field
    field.classList.remove('invalid', 'valid');
    errorElement.style.display = 'none';
    return true;
  }

  // Execute the named validation rule
  const result = validationRule(value, Joi);

  if (result.error) {
    // Show error
    field.classList.remove('valid');
    field.classList.add('invalid');
    errorElement.textContent = result.error.details[0].message;
    errorElement.style.display = 'block';
    
    // Update tab status
    const panel = field.closest('.tab-panel');
    if (panel && window.updateTabStatus) {
      const tabName = panel.id.replace('-panel', '');
      window.updateTabStatus(tabName);
    }
    
    return false;
  } else {
    // Show success
    field.classList.remove('invalid');
    field.classList.add('valid');
    errorElement.style.display = 'none';
    
    // Update tab status
    const panel = field.closest('.tab-panel');
    if (panel && window.updateTabStatus) {
      const tabName = panel.id.replace('-panel', '');
      window.updateTabStatus(tabName);
    }
    
    return true;
  }
}

// Validate all fields function
export function validateAllFields() {
  const fieldIds = getValidationRuleNames();
  let allValid = true;

  fieldIds.forEach(fieldId => {
    const field = document.getElementById(fieldId);
    if (field) {
      const value = field.value;
      const isValid = validateField(fieldId, value);
      if (!isValid) {
        allValid = false;
      }
    }
  });

  return allValid;
}

// Validate current tab fields
export function validateCurrentTabFields() {
  const currentTabPanel = document.getElementById(window.getCurrentTab() + '-panel');
  if (!currentTabPanel) {return true;}

  // Get all input/select/textarea fields in current tab
  const fields = currentTabPanel.querySelectorAll('input, select, textarea');
  let allValid = true;

  fields.forEach(field => {
    if (field.id) {
      const fieldId = field.id;
      const value = field.value;
      const isValid = validateField(fieldId, value);
      if (!isValid) {
        allValid = false;
      }
    }
  });

  return allValid;
}

// Setup real-time validation
export function setupValidationListeners() {
  const fieldIds = getValidationRuleNames();
  
  fieldIds.forEach(fieldId => {
    const field = document.getElementById(fieldId);
    if (field) {
      // Validate on blur
      field.addEventListener('blur', function () {
        validateField(this.id, this.value);
      });
      
      // Validate on input for immediate feedback
      field.addEventListener('input', function () {
        // Debounce validation to avoid excessive calls
        clearTimeout(this.validationTimeout);
        this.validationTimeout = setTimeout(() => {
          validateField(this.id, this.value);
        }, 300);
      });
    }
  });
}