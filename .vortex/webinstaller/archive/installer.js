// Vortex Web Installer - Complete Implementation
// This file contains all installer functionality organized into logical sections

// ============================================================================
// DATA MANAGER - Alpine.js data structure and state management
// ============================================================================

window.installerData = function () {
  return {
    // General Information
    siteName: '',
    siteMachineName: '',
    orgName: '',
    orgMachineName: '',
    publicDomain: '',

    // Repository
    repoProvider: 'github',
    githubToken: '',
    githubRepository: '',

    // Drupal
    drupalProfile: 'standard',
    customProfileName: '',
    modulePrefix: '',
    themeMachineName: '',

    // Services
    services: [],

    // Hosting
    hostingProvider: 'acquia',
    webRootDirectory: 'docroot',

    // Workflow
    provisionType: 'database_import',
    databaseSource: 'url',
    databaseContainerImage: '',

    // CI/CD
    ciProvider: 'github_actions',

    // Deployment
    deploymentType: 'container_registry',

    // Dependencies
    dependencyUpdates: 'renovatebot',

    // Database
    databaseName: 'drupal',
    databaseHost: 'mariadb',

    // Advanced
    debugMode: false,
    skipTests: false,

    // Helper methods
    updateMachineNames() {
      // Convert site name to machine name
      this.siteMachineName = this.toMachineName(this.siteName);

      // Convert org name to machine name
      this.orgMachineName = this.toMachineName(this.orgName);

      // Update module prefix
      this.modulePrefix = this.toAbbreviation(this.siteMachineName, 4);

      // Update domain
      if (this.siteMachineName) {
        this.publicDomain = this.toKebabCase(this.siteMachineName) + '.com';
      }

      // Update GitHub repo and container image
      this.updateGitHubRepo();
      this.updateContainerImage();

      // Validate updated fields
      setTimeout(() => {
        if (this.siteMachineName && window.validateField) {
          window.validateField('site-machine-name', this.siteMachineName);
        }
        if (this.publicDomain && window.validateField) {
          window.validateField('public-domain', this.publicDomain);
        }
      }, 100);
    },

    updateGitHubRepo() {
      if (this.orgMachineName && this.siteMachineName) {
        this.githubRepository =
          this.orgMachineName + '/' + this.siteMachineName;
      }
    },

    updateContainerImage() {
      if (this.orgMachineName && this.siteMachineName) {
        this.databaseContainerImage =
          this.orgMachineName + '/' + this.siteMachineName + '-data:latest';
      }
    },

    updateWebRoot() {
      switch (this.hostingProvider) {
        case 'acquia':
          this.webRootDirectory = 'docroot';
          break;
        case 'lagoon':
          this.webRootDirectory = 'web';
          break;
        case 'none':
          this.webRootDirectory = 'web';
          break;
        case 'other':
          // Keep current value or reset to empty for user input
          if (
            this.webRootDirectory === 'docroot' ||
            this.webRootDirectory === 'web'
          ) {
            this.webRootDirectory = '';
          }
          break;
      }
    },

    updateDatabaseSource() {
      if (this.provisionType === 'profile_install') {
        this.databaseSource = 'none';
      }
    },

    isDbSourceAvailable(source) {
      // Filter database sources based on hosting provider
      if (source === 'acquia' && this.hostingProvider === 'lagoon') {
        return false;
      }
      if (source === 'lagoon' && this.hostingProvider === 'acquia') {
        return false;
      }
      return true;
    },

    // Utility functions
    toMachineName(str) {
      return str
        .toLowerCase()
        .replace(/[^a-z0-9\s]/g, '')
        .replace(/\s+/g, '_')
        .replace(/_{2,}/g, '_')
        .replace(/^_|_$/g, '');
    },

    toKebabCase(str) {
      return str.toLowerCase().replace(/_/g, '-');
    },

    toAbbreviation(str, maxLength) {
      if (!str) {
        return '';
      }
      const parts = str.split('_');
      if (parts.length === 1) {
        return str.substring(0, maxLength);
      }
      return parts
        .map(part => part.charAt(0))
        .join('')
        .substring(0, maxLength);
    },
  };
};

// ============================================================================
// TAB MANAGER - Tab navigation and status management
// ============================================================================

let currentTab = 'general';

window.switchTab = function (tabName) {
  // Update tab buttons
  document
    .querySelectorAll('.tab-button')
    .forEach(btn => btn.classList.remove('active'));
  document
    .querySelector(`[onclick="switchTab('${tabName}')"]`)
    .classList.add('active');

  // Update tab panels
  document
    .querySelectorAll('.tab-panel')
    .forEach(panel => (panel.style.display = 'none'));
  document.getElementById(tabName + '-panel').style.display = 'block';

  currentTab = tabName;

  // Update navigation buttons after tab switch
  setTimeout(() => {
    if (window.updateNavigationButtons) {
      window.updateNavigationButtons();
    }
  }, 50);
};

// Get current active tab
window.getCurrentTab = function () {
  return currentTab;
};

// Check if tab is valid/complete
window.isTabValid = function (tabName) {
  const panel = document.getElementById(tabName + '-panel');
  if (!panel) {
    return false;
  }

  const requiredFields = panel.querySelectorAll(
    '[required], [data-validation*="required"]'
  );
  for (const field of requiredFields) {
    if (!field.value || field.classList.contains('invalid')) {
      return false;
    }
  }
  return true;
};

// Update tab status indicators
window.updateTabStatus = function (tabName) {
  const tabButton = document.querySelector(
    `[onclick="switchTab('${tabName}')"]`
  );
  if (!tabButton) {
    return;
  }

  let statusIndicator = tabButton.querySelector('.tab-status');
  if (!statusIndicator) {
    // Create status indicator if it doesn't exist
    statusIndicator = document.createElement('span');
    statusIndicator.className = 'tab-status';
    tabButton.appendChild(statusIndicator);
  }

  const isValid = window.isTabValid(tabName);

  if (isValid) {
    statusIndicator.classList.remove('invalid');
    statusIndicator.classList.add('valid');
    statusIndicator.innerHTML = '●'; // Green dot
  } else {
    statusIndicator.classList.remove('valid');
    statusIndicator.classList.add('invalid');
    statusIndicator.innerHTML = '●'; // Red dot
  }
};

// Update all tab statuses
window.updateAllTabStatuses = function () {
  const tabs = [
    'general',
    'repository',
    'drupal',
    'services',
    'hosting',
    'workflow',
    'cicd',
    'deployment',
    'dependencies',
    'database',
  ];
  tabs.forEach(tab => {
    if (document.getElementById(tab + '-panel')) {
      window.updateTabStatus(tab);
    }
  });
};

// ============================================================================
// VALIDATION SYSTEM - Field validation using Joi
// ============================================================================

window.validateField = function (fieldId, value) {
  const field = document.getElementById(fieldId);
  if (!field) {
    return true;
  }

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

  const validationRules = field.getAttribute('data-validation');

  if (!validationRules || !(window.Joi || window.joi)) {
    return true; // Return true if no validation rules or Joi not available
  }

  // Use joi (lowercase) if Joi (uppercase) is not available
  const Joi = window.Joi || window.joi;

  const schema = buildValidationSchema(validationRules, Joi);
  const result = schema.validate(value);

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
};

function buildValidationSchema(rulesString, Joi) {
  const rules = rulesString.split('|');
  let schema = Joi.string();

  rules.forEach(rule => {
    const [ruleName, ruleValue] = rule.split(':');

    switch (ruleName) {
      case 'required':
        schema = schema.required().messages({
          'string.empty': 'This field is required',
          'any.required': 'This field is required',
        });
        break;
      case 'min':
        schema = schema.min(parseInt(ruleValue)).messages({
          'string.min': `Must be at least ${ruleValue} characters`,
        });
        break;
      case 'max':
        schema = schema.max(parseInt(ruleValue)).messages({
          'string.max': `Must be no more than ${ruleValue} characters`,
        });
        break;
      case 'machine_name':
        schema = schema.pattern(/^[a-z0-9_]+$/).messages({
          'string.pattern.base':
            'No spaces allowed. Use only lowercase letters, numbers, and underscores.',
        });
        break;
      case 'domain':
        schema = schema
          .pattern(
            /^[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9](?:\.[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9])*$/
          )
          .messages({
            'string.pattern.base':
              'Must be a valid domain name (e.g., example.com)',
          });
        break;
      case 'email':
        schema = schema.email({ tlds: { allow: false } }).messages({
          'string.email': 'Must be a valid email address',
        });
        break;
      case 'url':
        schema = schema.uri().messages({
          'string.uri': 'Must be a valid URL',
        });
        break;
    }
  });

  return schema;
}

// Validate all fields function
window.validateAllFields = function () {
  const fields = document.querySelectorAll('[data-validation]');
  let allValid = true;

  fields.forEach(field => {
    const fieldId = field.id;
    const value = field.value;
    const isValid = validateField(fieldId, value);
    if (!isValid) {
      allValid = false;
    }
  });

  return allValid;
};

// Validate current tab fields
window.validateCurrentTabFields = function () {
  const currentTabPanel = document.getElementById(
    window.getCurrentTab() + '-panel'
  );
  if (!currentTabPanel) {
    return true;
  }

  const fields = currentTabPanel.querySelectorAll('[data-validation]');
  let allValid = true;

  fields.forEach(field => {
    const fieldId = field.id;
    const value = field.value;
    const isValid = validateField(fieldId, value);
    if (!isValid) {
      allValid = false;
    }
  });

  return allValid;
};

// ============================================================================
// UI UTILITIES - Navigation buttons and responsive functionality
// ============================================================================

// Navigation button control
window.updateNavigationButtons = function () {
  const nextButton = document.querySelector('.next-button');
  const prevButton = document.querySelector('.prev-button');

  if (nextButton) {
    const isCurrentTabValid = window.validateCurrentTabFields
      ? window.validateCurrentTabFields()
      : true;
    nextButton.disabled = !isCurrentTabValid;

    if (isCurrentTabValid) {
      nextButton.classList.remove('disabled');
    } else {
      nextButton.classList.add('disabled');
    }
  }

  if (prevButton) {
    const currentTab = window.getCurrentTab
      ? window.getCurrentTab()
      : 'general';
    prevButton.disabled = currentTab === 'general';

    if (currentTab === 'general') {
      prevButton.classList.add('disabled');
    } else {
      prevButton.classList.remove('disabled');
    }
  }
};

// Next/Previous navigation
window.goToNextTab = function () {
  const tabs = [
    'general',
    'repository',
    'drupal',
    'services',
    'hosting',
    'workflow',
    'cicd',
    'deployment',
    'dependencies',
    'database',
  ];
  const currentTab = window.getCurrentTab ? window.getCurrentTab() : 'general';
  const currentIndex = tabs.indexOf(currentTab);

  if (currentIndex < tabs.length - 1) {
    const nextTab = tabs[currentIndex + 1];
    if (document.getElementById(nextTab + '-panel')) {
      window.switchTab(nextTab);
    }
  }
};

window.goToPreviousTab = function () {
  const tabs = [
    'general',
    'repository',
    'drupal',
    'services',
    'hosting',
    'workflow',
    'cicd',
    'deployment',
    'dependencies',
    'database',
  ];
  const currentTab = window.getCurrentTab ? window.getCurrentTab() : 'general';
  const currentIndex = tabs.indexOf(currentTab);

  if (currentIndex > 0) {
    const prevTab = tabs[currentIndex - 1];
    if (document.getElementById(prevTab + '-panel')) {
      window.switchTab(prevTab);
    }
  }
};

// Dynamic scaling based on viewport height
function updateScaling() {
  const vh = window.innerHeight;
  const minHeight = 600;
  const maxHeight = 1200;
  const minScale = 0.7;
  const maxScale = 1.0;

  // Calculate scale factor based on viewport height
  const normalizedHeight = Math.max(minHeight, Math.min(maxHeight, vh));
  const scaleFactor =
    minScale +
    (maxScale - minScale) *
      ((normalizedHeight - minHeight) / (maxHeight - minHeight));

  // Apply scale factor to CSS custom property
  document.documentElement.style.setProperty('--scale-factor', scaleFactor);
}

// ============================================================================
// HELP SYSTEM - Help sidebar and content display
// ============================================================================

window.showHelp = function (buttonElement) {
  // Find the help content in the same form group
  const formGroup = buttonElement.closest('.form-group');
  const helpContent = formGroup.querySelector('.field-help-extended');

  if (helpContent) {
    updateHelpContentFromElement(helpContent);
  } else {
    updateHelpContentDefault();
  }

  // Show the help sidebar and form overlay
  const sidebar = document.getElementById('helpSidebar');
  const overlay = document.getElementById('formOverlay');

  sidebar.classList.add('active');
  overlay.classList.add('active');
};

window.closeHelpSidebar = function () {
  const sidebar = document.getElementById('helpSidebar');
  const overlay = document.getElementById('formOverlay');

  sidebar.classList.remove('active');
  overlay.classList.remove('active');
};

function updateHelpContentFromElement(helpElement) {
  const helpContentEl = document.getElementById('helpContent');
  const helpTitleEl = document.querySelector('.help-title');

  // Extract the title from the h4 element
  const titleElement = helpElement.querySelector('h4');
  const title = titleElement ? titleElement.textContent : 'Field Help';

  // Get all content except the h4 title
  const contentElements = Array.from(helpElement.children).filter(
    el => el.tagName !== 'H4'
  );
  const contentHtml = contentElements.map(el => el.outerHTML).join('');

  helpTitleEl.textContent = title;
  helpContentEl.innerHTML = `
        <div class="help-section">
            ${contentHtml}
        </div>
    `;
}

function updateHelpContentDefault() {
  const helpContentEl = document.getElementById('helpContent');
  const helpTitleEl = document.querySelector('.help-title');

  helpTitleEl.textContent = 'Field Help';
  helpContentEl.innerHTML = `
        <div class="help-placeholder">
            No help content available for this field.
        </div>
    `;
}

// ============================================================================
// MAIN INITIALIZATION
// ============================================================================

// Main installer initialization
document.addEventListener('DOMContentLoaded', function () {
  console.log('Vortex Web Installer initialized');

  // Initialize scaling
  updateScaling();
  window.addEventListener('resize', updateScaling);

  // Initialize all tab statuses
  setTimeout(() => {
    if (window.updateAllTabStatuses) {
      window.updateAllTabStatuses();
    }
    if (window.updateNavigationButtons) {
      window.updateNavigationButtons();
    }
  }, 100);

  // Set up global form change listeners
  setupGlobalFormListeners();
  setupValidationListeners();
  setupHelpSystemListeners();
  setupKeyboardNavigation();
});

// Global form change listeners
function setupGlobalFormListeners() {
  // Listen for any form changes to update tab statuses and navigation
  document.addEventListener('input', function (event) {
    if (event.target.matches('input, select, textarea')) {
      // Debounce updates to avoid excessive calls
      clearTimeout(window.globalUpdateTimeout);
      window.globalUpdateTimeout = setTimeout(() => {
        if (window.updateAllTabStatuses) {
          window.updateAllTabStatuses();
        }
        if (window.updateNavigationButtons) {
          window.updateNavigationButtons();
        }
      }, 150);
    }
  });

  // Listen for form changes to update derived fields
  document.addEventListener('change', function (event) {
    if (event.target.matches('input, select')) {
      // Trigger Alpine.js data updates if needed
      const alpineData = window.Alpine
        ? window.Alpine.store('installer')
        : null;
      if (alpineData && typeof alpineData.updateMachineNames === 'function') {
        alpineData.updateMachineNames();
      }
    }
  });
}

// Setup real-time validation
function setupValidationListeners() {
  // Add event listeners for real-time validation
  const validatableFields = document.querySelectorAll('[data-validation]');

  validatableFields.forEach(field => {
    // Validate on blur
    field.addEventListener('blur', function () {
      window.validateField(this.id, this.value);
    });

    // Validate on input for immediate feedback
    field.addEventListener('input', function () {
      // Debounce validation to avoid excessive calls
      clearTimeout(this.validationTimeout);
      this.validationTimeout = setTimeout(() => {
        window.validateField(this.id, this.value);
      }, 300);
    });
  });
}

// Setup help system event listeners
function setupHelpSystemListeners() {
  // Close sidebar on Escape key
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeHelpSidebar();
    }
  });

  // Close sidebar when clicking on the form overlay
  const overlay = document.getElementById('formOverlay');
  if (overlay) {
    overlay.addEventListener('click', function () {
      closeHelpSidebar();
    });
  }
}

// Setup keyboard navigation
function setupKeyboardNavigation() {
  document.addEventListener('keydown', function (event) {
    if (event.ctrlKey || event.metaKey) {
      switch (event.key) {
        case 'ArrowRight':
          event.preventDefault();
          window.goToNextTab();
          break;
        case 'ArrowLeft':
          event.preventDefault();
          window.goToPreviousTab();
          break;
      }
    }
  });
}
