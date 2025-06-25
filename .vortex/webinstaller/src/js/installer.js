// Alpine.js data structure for installer
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

// Tab management
// eslint-disable-next-line no-unused-vars
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
};

// Help system functions
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

// Event listeners
document.addEventListener('DOMContentLoaded', function () {
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
});

// Validation functions
window.validateField = function (fieldId, value) {
  const field = document.getElementById(fieldId);
  const errorElement = document.getElementById(fieldId + '-error');
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
    return false;
  } else {
    // Show success
    field.classList.remove('invalid');
    field.classList.add('valid');
    errorElement.style.display = 'none';
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

// Update scaling on load and resize
window.addEventListener('load', updateScaling);
window.addEventListener('resize', updateScaling);
