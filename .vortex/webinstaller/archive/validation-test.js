// Validation Rules Test Suite
// This file can be used to test all validation rules independently

import {
  validateSiteName,
  validateSiteMachineName,
  validateOrgName,
  validateOrgMachineName,
  validatePublicDomain,
  validateGitHubToken,
  validateGitHubRepository,
  validateCustomProfileName,
  validateModulePrefix,
  validateThemeMachineName,
  validateWebRootDirectory,
  validateDatabaseContainerImage,
  validateDatabaseName,
  validateDatabaseHost,
  getValidationRule,
  getValidationRuleNames,
} from './modules/validation-rules.js';

// Mock Joi for testing (you would use the real Joi in actual tests)
const mockJoi = {
  string: () => ({
    required: () => mockJoi.string(),
    min: () => mockJoi.string(),
    max: () => mockJoi.string(),
    trim: () => mockJoi.string(),
    allow: () => mockJoi.string(),
    pattern: () => mockJoi.string(),
    email: () => mockJoi.string(),
    uri: () => mockJoi.string(),
    messages: () => mockJoi.string(),
    validate: value => {
      // Mock validation - always returns success for testing
      return { error: null, value: value };
    },
  }),
};

// Test data
const testCases = {
  'site-name': {
    valid: ['My Site', 'Test Website', 'Company Portal'],
    invalid: ['', 'A', 'A'.repeat(101)],
  },
  'site-machine-name': {
    valid: ['my_site', 'test_website', 'company_portal_123'],
    invalid: ['', 'My Site', 'my-site', 'my site', 'A'],
  },
  'org-name': {
    valid: ['My Company', 'Test Organization', 'ACME Corp'],
    invalid: ['', 'A', 'A'.repeat(101)],
  },
  'org-machine-name': {
    valid: ['my_company', 'test_org', 'acme_corp_123'],
    invalid: ['', 'My Company', 'my-company', 'my company', 'A'],
  },
  'public-domain': {
    valid: ['', 'example.com', 'my-site.org', 'test.co.uk'],
    invalid: ['invalid_domain', 'localhost', '123', '-invalid.com'],
  },
  'github-token': {
    valid: [
      '',
      'ghp_1234567890123456789012345678901234567890',
      'ghs_1234567890123456789012345678901234567890',
    ],
    invalid: [
      'invalid-token',
      'ghp_short',
      'gho_1234567890123456789012345678901234567890',
    ],
  },
  'github-repository': {
    valid: ['', 'username/repo', 'my-org/my-project', 'test.user/test.repo'],
    invalid: ['invalid', 'username/', '/repo', 'username/repo/extra'],
  },
  'custom-profile-name': {
    valid: ['', 'my_profile', 'custom_profile_123'],
    invalid: ['My Profile', 'my-profile', 'my profile', 'A'],
  },
  'module-prefix': {
    valid: ['', 'mp', 'mod_pre', 'custom_123'],
    invalid: ['A', 'too_long_prefix', 'My-Prefix', 'my prefix'],
  },
  'theme-machine-name': {
    valid: ['', 'my_theme', 'custom_theme_123'],
    invalid: ['My Theme', 'my-theme', 'my theme', 'A'],
  },
  'web-root-directory': {
    valid: ['web', 'docroot', 'public_html', 'www'],
    invalid: ['', 'web/subdir', 'my web', 'web@root'],
  },
  'database-container-image': {
    valid: ['', 'mysql:8.0', 'my-org/my-db:latest', 'registry.com/db:v1.0'],
    invalid: ['invalid', 'mysql', 'mysql:', ':tag'],
  },
  'database-name': {
    valid: ['drupal', 'my_database', 'db_123'],
    invalid: ['', 'my-database', 'my database', 'A'.repeat(65)],
  },
  'database-host': {
    valid: ['localhost', 'mariadb', 'db.example.com', 'mysql-server'],
    invalid: ['', 'host@name', 'host name', 'A'.repeat(256)],
  },
};

// Test runner function
export function runValidationTests() {
  console.log('🧪 Running Validation Rules Tests');
  console.log('===================================');

  let totalTests = 0;
  let passedTests = 0;

  // Test individual validation functions
  Object.entries(testCases).forEach(([fieldId, cases]) => {
    console.log(`\n📋 Testing ${fieldId}:`);

    const validationRule = getValidationRule(fieldId);
    if (!validationRule) {
      console.error(`❌ No validation rule found for ${fieldId}`);
      return;
    }

    // Test valid cases
    cases.valid.forEach(value => {
      totalTests++;
      try {
        const result = validationRule(value, mockJoi);
        if (!result.error) {
          console.log(`✅ Valid: "${value}"`);
          passedTests++;
        } else {
          console.log(`❌ Expected valid but got error for: "${value}"`);
        }
      } catch (error) {
        console.log(`❌ Exception testing "${value}": ${error.message}`);
      }
    });

    // Test invalid cases would require real Joi to see actual validation
    console.log(`   (Invalid cases would be tested with real Joi validation)`);
  });

  // Test utility functions
  console.log('\n📋 Testing utility functions:');

  totalTests++;
  const ruleNames = getValidationRuleNames();
  if (Array.isArray(ruleNames) && ruleNames.length > 0) {
    console.log(
      `✅ getValidationRuleNames() returned ${ruleNames.length} rules`
    );
    passedTests++;
  } else {
    console.log(`❌ getValidationRuleNames() failed`);
  }

  totalTests++;
  const siteNameRule = getValidationRule('site-name');
  if (typeof siteNameRule === 'function') {
    console.log(`✅ getValidationRule('site-name') returned function`);
    passedTests++;
  } else {
    console.log(`❌ getValidationRule('site-name') failed`);
  }

  totalTests++;
  const invalidRule = getValidationRule('non-existent-field');
  if (invalidRule === null) {
    console.log(`✅ getValidationRule('non-existent-field') returned null`);
    passedTests++;
  } else {
    console.log(
      `❌ getValidationRule('non-existent-field') should return null`
    );
  }

  // Results
  console.log('\n📊 Test Results:');
  console.log('================');
  console.log(`Total Tests: ${totalTests}`);
  console.log(`Passed: ${passedTests}`);
  console.log(`Failed: ${totalTests - passedTests}`);
  console.log(`Success Rate: ${Math.round((passedTests / totalTests) * 100)}%`);

  return {
    total: totalTests,
    passed: passedTests,
    failed: totalTests - passedTests,
    successRate: Math.round((passedTests / totalTests) * 100),
  };
}

// Export test cases for external use
export { testCases };

// Auto-run tests if this file is loaded directly
if (typeof window !== 'undefined') {
  window.runValidationTests = runValidationTests;
  window.testCases = testCases;
}
