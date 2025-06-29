import { defineConfig } from 'cypress';

export default defineConfig({
  e2e: {
    baseUrl: 'http://localhost:3000',
    setupNodeEvents(_on, _config) {
      // implement node event listeners here
    },
    specPattern: 'tests/e2e/**/*.cy.{js,jsx,ts,tsx}',
    supportFile: 'tests/support/e2e.js',
  },
  screenshotsFolder: '.logs/screenshots',
});
