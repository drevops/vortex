// Validation Rules - Named validation rules for each field
// Each rule can be individually tested

/**
 * Site name validation rule
 * Must be a human-readable name between 2-100 characters
 */
export function validateSiteName(value, Joi) {
  return Joi.string()
    .required()
    .min(2)
    .max(100)
    .trim()
    .messages({
      'string.empty': 'Site name is required',
      'any.required': 'Site name is required',
      'string.min': 'Site name must be at least 2 characters',
      'string.max': 'Site name must not exceed 100 characters'
    })
    .validate(value);
}

/**
 * Site machine name validation rule
 * Must be lowercase letters, numbers, and underscores only
 */
export function validateSiteMachineName(value, Joi) {
  return Joi.string()
    .required()
    .pattern(/^[a-z0-9_]+$/)
    .min(2)
    .max(50)
    .messages({
      'string.empty': 'Site machine name is required',
      'any.required': 'Site machine name is required',
      'string.pattern.base': 'No spaces allowed. Use only lowercase letters, numbers, and underscores',
      'string.min': 'Site machine name must be at least 2 characters',
      'string.max': 'Site machine name must not exceed 50 characters'
    })
    .validate(value);
}

/**
 * Organization name validation rule
 * Must be a human-readable organization name
 */
export function validateOrgName(value, Joi) {
  return Joi.string()
    .required()
    .min(2)
    .max(100)
    .trim()
    .messages({
      'string.empty': 'Organization name is required',
      'any.required': 'Organization name is required',
      'string.min': 'Organization name must be at least 2 characters',
      'string.max': 'Organization name must not exceed 100 characters'
    })
    .validate(value);
}

/**
 * Organization machine name validation rule
 * Must be lowercase letters, numbers, and underscores only
 */
export function validateOrgMachineName(value, Joi) {
  return Joi.string()
    .required()
    .pattern(/^[a-z0-9_]+$/)
    .min(2)
    .max(50)
    .messages({
      'string.empty': 'Organization machine name is required',
      'any.required': 'Organization machine name is required',
      'string.pattern.base': 'No spaces allowed. Use only lowercase letters, numbers, and underscores',
      'string.min': 'Organization machine name must be at least 2 characters',
      'string.max': 'Organization machine name must not exceed 50 characters'
    })
    .validate(value);
}

/**
 * Public domain validation rule
 * Must be a valid domain name format
 */
export function validatePublicDomain(value, Joi) {
  return Joi.string()
    .allow('')
    .pattern(/^[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9](?:\.[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9])*$/)
    .messages({
      'string.pattern.base': 'Must be a valid domain name (e.g., example.com)'
    })
    .validate(value);
}

/**
 * GitHub token validation rule
 * Must be a valid GitHub personal access token format
 */
export function validateGitHubToken(value, Joi) {
  return Joi.string()
    .allow('')
    .pattern(/^gh[ps]_[A-Za-z0-9_]{36,251}$/)
    .messages({
      'string.pattern.base': 'Must be a valid GitHub token (starts with ghp_ or ghs_)'
    })
    .validate(value);
}

/**
 * GitHub repository validation rule
 * Must be in format: username/repository
 */
export function validateGitHubRepository(value, Joi) {
  return Joi.string()
    .allow('')
    .pattern(/^[a-zA-Z0-9_.-]+\/[a-zA-Z0-9_.-]+$/)
    .messages({
      'string.pattern.base': 'Must be in format: username/repository'
    })
    .validate(value);
}

/**
 * Custom profile name validation rule
 * Machine name format for Drupal profiles
 */
export function validateCustomProfileName(value, Joi) {
  return Joi.string()
    .allow('')
    .pattern(/^[a-z0-9_]+$/)
    .min(2)
    .max(50)
    .messages({
      'string.pattern.base': 'Profile name must use only lowercase letters, numbers, and underscores',
      'string.min': 'Profile name must be at least 2 characters',
      'string.max': 'Profile name must not exceed 50 characters'
    })
    .validate(value);
}

/**
 * Module prefix validation rule
 * Short abbreviation for module names
 */
export function validateModulePrefix(value, Joi) {
  return Joi.string()
    .allow('')
    .pattern(/^[a-z0-9_]+$/)
    .min(2)
    .max(10)
    .messages({
      'string.pattern.base': 'Module prefix must use only lowercase letters, numbers, and underscores',
      'string.min': 'Module prefix must be at least 2 characters',
      'string.max': 'Module prefix must not exceed 10 characters'
    })
    .validate(value);
}

/**
 * Theme machine name validation rule
 * Machine name format for Drupal themes
 */
export function validateThemeMachineName(value, Joi) {
  return Joi.string()
    .allow('')
    .pattern(/^[a-z0-9_]+$/)
    .min(2)
    .max(50)
    .messages({
      'string.pattern.base': 'Theme name must use only lowercase letters, numbers, and underscores',
      'string.min': 'Theme name must be at least 2 characters',
      'string.max': 'Theme name must not exceed 50 characters'
    })
    .validate(value);
}

/**
 * Web root directory validation rule
 * Valid directory name for web root
 */
export function validateWebRootDirectory(value, Joi) {
  return Joi.string()
    .required()
    .pattern(/^[a-zA-Z0-9_-]+$/)
    .min(1)
    .max(50)
    .messages({
      'string.empty': 'Web root directory is required',
      'any.required': 'Web root directory is required',
      'string.pattern.base': 'Directory name must use only letters, numbers, underscores, and hyphens',
      'string.min': 'Directory name must be at least 1 character',
      'string.max': 'Directory name must not exceed 50 characters'
    })
    .validate(value);
}

/**
 * Database container image validation rule
 * Docker image format validation
 */
export function validateDatabaseContainerImage(value, Joi) {
  return Joi.string()
    .allow('')
    .pattern(/^[a-z0-9._/-]+:[a-z0-9._-]+$/i)
    .messages({
      'string.pattern.base': 'Must be a valid Docker image format (e.g., org/image:tag)'
    })
    .validate(value);
}

/**
 * Database name validation rule
 * Valid database name format
 */
export function validateDatabaseName(value, Joi) {
  return Joi.string()
    .required()
    .pattern(/^[a-zA-Z0-9_]+$/)
    .min(1)
    .max(64)
    .messages({
      'string.empty': 'Database name is required',
      'any.required': 'Database name is required',
      'string.pattern.base': 'Database name must use only letters, numbers, and underscores',
      'string.min': 'Database name must be at least 1 character',
      'string.max': 'Database name must not exceed 64 characters'
    })
    .validate(value);
}

/**
 * Database host validation rule
 * Valid hostname or service name
 */
export function validateDatabaseHost(value, Joi) {
  return Joi.string()
    .required()
    .pattern(/^[a-zA-Z0-9._-]+$/)
    .min(1)
    .max(255)
    .messages({
      'string.empty': 'Database host is required',
      'any.required': 'Database host is required',
      'string.pattern.base': 'Host must be a valid hostname or service name',
      'string.min': 'Host must be at least 1 character',
      'string.max': 'Host must not exceed 255 characters'
    })
    .validate(value);
}

// Field mapping - maps field IDs to their validation functions
export const VALIDATION_RULES = {
  'site-name': validateSiteName,
  'site-machine-name': validateSiteMachineName,
  'org-name': validateOrgName,
  'org-machine-name': validateOrgMachineName,
  'public-domain': validatePublicDomain,
  'github-token': validateGitHubToken,
  'github-repository': validateGitHubRepository,
  'custom-profile-name': validateCustomProfileName,
  'module-prefix': validateModulePrefix,
  'theme-machine-name': validateThemeMachineName,
  'web-root-directory': validateWebRootDirectory,
  'database-container-image': validateDatabaseContainerImage,
  'database-name': validateDatabaseName,
  'database-host': validateDatabaseHost
};

/**
 * Get validation function for a field
 * @param {string} fieldId - The field ID
 * @returns {Function|null} - The validation function or null if not found
 */
export function getValidationRule(fieldId) {
  return VALIDATION_RULES[fieldId] || null;
}

/**
 * Get all available validation rule names
 * @returns {string[]} - Array of field IDs that have validation rules
 */
export function getValidationRuleNames() {
  return Object.keys(VALIDATION_RULES);
}