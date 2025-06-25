// Data Manager - Alpine.js data structure and state management
export function installerData() {
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
}