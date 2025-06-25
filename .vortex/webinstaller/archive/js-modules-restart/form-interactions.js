// Form Interactions - Simple data attribute-driven interactions
// No models, just direct DOM interactions based on data attributes

export class FormInteractions {
  constructor() {
    this.init();
  }

  init() {
    this.setupAutoGeneration();
    this.setupConditionalFields();
    this.setupFieldRelationships();
  }

  // Handle auto-generation based on data-auto-generate attributes
  setupAutoGeneration() {
    const autoFields = document.querySelectorAll('[data-auto-generate]');

    autoFields.forEach(field => {
      // Mark field as auto-generated
      field.classList.add('auto-generated');

      // Add readonly if specified
      if (field.hasAttribute('data-readonly')) {
        field.readOnly = true;
        field.classList.add('readonly');
      }
    });

    // Setup listeners for fields that trigger auto-generation
    document.addEventListener('input', event => {
      const field = event.target;
      if (field.hasAttribute('data-triggers')) {
        this.handleAutoGeneration(field);
      }
    });
  }

  handleAutoGeneration(sourceField) {
    const triggers = sourceField.getAttribute('data-triggers').split(',');
    const sourceValue = sourceField.value;

    triggers.forEach(trigger => {
      switch (trigger.trim()) {
        case 'machine-name':
          this.generateMachineName(sourceValue);
          break;
        case 'org-machine-name':
          this.generateOrgMachineName();
          break;
        case 'domain':
          this.generateDomain();
          break;
        case 'github-repo':
          this.generateGitHubRepo();
          break;
        case 'container-image':
          this.generateContainerImage();
          break;
        case 'module-prefix':
          this.generateModulePrefix();
          break;
      }
    });
  }

  generateMachineName(siteName) {
    const machineNameField = document.getElementById('site-machine-name');
    if (machineNameField) {
      const machineName = this.toMachineName(siteName);
      machineNameField.value = machineName;

      // Trigger validation
      if (window.validateField) {
        window.validateField('site-machine-name', machineName);
      }

      // Trigger other dependent generations
      this.generateModulePrefix();
      this.generateDomain();
    }
  }

  generateOrgMachineName() {
    const orgNameField = document.getElementById('org-name');
    const orgMachineNameField = document.getElementById('org-machine-name');

    if (orgNameField && orgMachineNameField) {
      const orgMachineName = this.toMachineName(orgNameField.value);
      orgMachineNameField.value = orgMachineName;

      // Trigger validation
      if (window.validateField) {
        window.validateField('org-machine-name', orgMachineName);
      }

      // Trigger dependent generations
      this.generateGitHubRepo();
      this.generateContainerImage();
    }
  }

  generateModulePrefix() {
    const siteMachineNameField = document.getElementById('site-machine-name');
    const modulePrefixField = document.getElementById('module-prefix');

    if (siteMachineNameField && modulePrefixField) {
      const prefix = this.toAbbreviation(siteMachineNameField.value, 4);
      modulePrefixField.value = prefix;

      // Trigger validation
      if (window.validateField) {
        window.validateField('module-prefix', prefix);
      }
    }
  }

  generateDomain() {
    const siteMachineNameField = document.getElementById('site-machine-name');
    const domainField = document.getElementById('public-domain');

    if (siteMachineNameField && domainField && siteMachineNameField.value) {
      const domain = this.toKebabCase(siteMachineNameField.value) + '.com';
      domainField.value = domain;

      // Trigger validation
      if (window.validateField) {
        window.validateField('public-domain', domain);
      }
    }
  }

  generateGitHubRepo() {
    const orgMachineNameField = document.getElementById('org-machine-name');
    const siteMachineNameField = document.getElementById('site-machine-name');
    const repoField = document.getElementById('github-repository');

    if (orgMachineNameField && siteMachineNameField && repoField) {
      const orgName = orgMachineNameField.value;
      const siteName = siteMachineNameField.value;

      if (orgName && siteName) {
        const repo = `${orgName}/${siteName}`;
        repoField.value = repo;

        // Trigger validation
        if (window.validateField) {
          window.validateField('github-repository', repo);
        }
      }
    }
  }

  generateContainerImage() {
    const orgMachineNameField = document.getElementById('org-machine-name');
    const siteMachineNameField = document.getElementById('site-machine-name');
    const imageField = document.getElementById('database-container-image');

    if (orgMachineNameField && siteMachineNameField && imageField) {
      const orgName = orgMachineNameField.value;
      const siteName = siteMachineNameField.value;

      if (orgName && siteName) {
        const image = `${orgName}/${siteName}-data:latest`;
        imageField.value = image;

        // Trigger validation
        if (window.validateField) {
          window.validateField('database-container-image', image);
        }
      }
    }
  }

  // Setup conditional field visibility based on data-conditional attributes
  setupConditionalFields() {
    const conditionalFields = document.querySelectorAll('[data-conditional]');

    conditionalFields.forEach(field => {
      const condition = field.getAttribute('data-conditional');
      this.updateConditionalField(field, condition);
    });

    // Listen for changes that might affect conditional fields
    document.addEventListener('change', event => {
      const changedField = event.target;
      const fieldId = changedField.id;

      // Check all conditional fields to see if they depend on this field
      conditionalFields.forEach(field => {
        const condition = field.getAttribute('data-conditional');
        if (condition.includes(fieldId)) {
          this.updateConditionalField(field, condition);
        }
      });
    });
  }

  updateConditionalField(field, condition) {
    // Parse condition like "hostingProvider=acquia" or "field-id=value"
    const [sourceFieldId, expectedValue] = condition.split('=');
    const sourceField = document.getElementById(sourceFieldId);

    if (sourceField) {
      const currentValue =
        sourceField.value ||
        (sourceField.type === 'checkbox' ? sourceField.checked : '');
      const shouldShow = currentValue.toString() === expectedValue;

      if (shouldShow) {
        field.style.display = '';
        field.classList.add('active');
      } else {
        field.style.display = 'none';
        field.classList.remove('active');
      }
    }
  }

  // Setup field relationships based on data-updates attributes
  setupFieldRelationships() {
    // Handle hosting provider changes affecting web root
    const hostingFields = document.querySelectorAll(
      '[data-updates*="web-root"]'
    );
    hostingFields.forEach(field => {
      field.addEventListener('change', () => this.updateWebRoot(field.value));
    });

    // Handle provision type changes affecting database source
    const provisionFields = document.querySelectorAll(
      '[data-updates*="database-source"]'
    );
    provisionFields.forEach(field => {
      field.addEventListener('change', () =>
        this.updateDatabaseSource(field.value)
      );
    });
  }

  updateWebRoot(hostingProvider) {
    const webRootField = document.getElementById('web-root-directory');
    if (!webRootField) {
      return;
    }

    let webRoot;
    switch (hostingProvider) {
      case 'acquia':
        webRoot = 'docroot';
        break;
      case 'lagoon':
        webRoot = 'web';
        break;
      case 'none':
        webRoot = 'web';
        break;
      case 'other':
        // Don't change if user has already customized it
        if (webRootField.value === 'docroot' || webRootField.value === 'web') {
          webRoot = '';
        } else {
          return; // Keep current value
        }
        break;
      default:
        return;
    }

    webRootField.value = webRoot;

    // Trigger validation
    if (window.validateField) {
      window.validateField('web-root-directory', webRoot);
    }
  }

  updateDatabaseSource(provisionType) {
    const dbSourceField = document.getElementById('database-source');
    if (!dbSourceField) {
      return;
    }

    if (provisionType === 'profile_install') {
      dbSourceField.value = 'none';

      // Trigger validation
      if (window.validateField) {
        window.validateField('database-source', 'none');
      }
    }
  }

  // Utility functions
  toMachineName(str) {
    return str
      .toLowerCase()
      .replace(/[^a-z0-9\s]/g, '')
      .replace(/\s+/g, '_')
      .replace(/_{2,}/g, '_')
      .replace(/^_|_$/g, '');
  }

  toKebabCase(str) {
    return str.toLowerCase().replace(/_/g, '-');
  }

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
  }
}
