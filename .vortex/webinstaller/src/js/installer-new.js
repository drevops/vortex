// Vortex Web Installer - Data Attribute-Driven Implementation
// Main coordination file that imports and initializes all modules

import { FormInteractions } from './modules/form-interactions.js';
import { 
  switchTab, 
  getCurrentTab, 
  isTabValid, 
  updateTabStatus, 
  updateAllTabStatuses 
} from './modules/tab-manager.js';
import { 
  validateField, 
  validateAllFields, 
  validateCurrentTabFields, 
  setupValidationListeners 
} from './modules/validation.js';
import { 
  updateNavigationButtons, 
  goToNextTab, 
  goToPreviousTab, 
  updateScaling 
} from './modules/ui-utilities.js';
import { 
  showHelp, 
  closeHelpSidebar, 
  setupHelpSystemListeners 
} from './modules/help-system.js';

// ============================================================================
// GLOBAL EXPORTS - Make functions available to the global window object
// ============================================================================

// Initialize form interactions
let formInteractions;

// Export tab manager functions
window.switchTab = switchTab;
window.getCurrentTab = getCurrentTab;
window.isTabValid = isTabValid;
window.updateTabStatus = updateTabStatus;
window.updateAllTabStatuses = updateAllTabStatuses;

// Export validation functions
window.validateField = validateField;
window.validateAllFields = validateAllFields;
window.validateCurrentTabFields = validateCurrentTabFields;

// Export UI utilities
window.updateNavigationButtons = updateNavigationButtons;
window.goToNextTab = goToNextTab;
window.goToPreviousTab = goToPreviousTab;

// Export help system functions
window.showHelp = showHelp;
window.closeHelpSidebar = closeHelpSidebar;

// ============================================================================
// MAIN INITIALIZATION
// ============================================================================

// Main installer initialization
document.addEventListener('DOMContentLoaded', function () {
  console.log('Vortex Web Installer initialized (data attribute-driven)');
  
  // Initialize form interactions
  formInteractions = new FormInteractions();
  
  // Initialize scaling
  updateScaling();
  window.addEventListener('resize', updateScaling);
  
  // Initialize all tab statuses
  setTimeout(() => {
    updateAllTabStatuses();
    updateNavigationButtons();
  }, 100);
  
  // Set up global form change listeners
  setupGlobalFormListeners();
  setupValidationListeners();
  setupHelpSystemListeners();
  setupKeyboardNavigation();
});

// ============================================================================
// EVENT LISTENERS AND SETUP
// ============================================================================

// Global form change listeners
function setupGlobalFormListeners() {
  // Listen for any form changes to update tab statuses and navigation
  document.addEventListener('input', function (event) {
    if (event.target.matches('input, select, textarea')) {
      // Debounce updates to avoid excessive calls
      clearTimeout(window.globalUpdateTimeout);
      window.globalUpdateTimeout = setTimeout(() => {
        updateAllTabStatuses();
        updateNavigationButtons();
      }, 150);
    }
  });
  
  // Listen for form changes 
  document.addEventListener('change', function (event) {
    if (event.target.matches('input, select, textarea')) {
      // Update tab statuses and navigation when form data changes
      setTimeout(() => {
        updateAllTabStatuses();
        updateNavigationButtons();
      }, 50);
    }
  });
}

// Setup keyboard navigation
function setupKeyboardNavigation() {
  document.addEventListener('keydown', function (event) {
    if (event.ctrlKey || event.metaKey) {
      switch (event.key) {
        case 'ArrowRight':
          event.preventDefault();
          goToNextTab();
          break;
        case 'ArrowLeft':
          event.preventDefault();
          goToPreviousTab();
          break;
      }
    }
  });
}