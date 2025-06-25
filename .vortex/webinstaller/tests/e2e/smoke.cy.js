describe('Smoke tests', () => {
  it('should check browser console and scripts loading', () => {
    cy.visit('/');

    cy.get('h1').should(
      'contain',
      'Vortex Installer',
      'Page should load with correct title'
    );

    cy.wait(1000);

    cy.window().should(
      'satisfy',
      win => {
        return !!win.Alpine && typeof win.Alpine === 'object';
      },
      'Alpine.js framework should be loaded on window object'
    );

    cy.window().should(
      'satisfy',
      win => {
        return !!(win.joi || win.Joi);
      },
      'Joi validation library should be loaded (either as joi or Joi)'
    );

    cy.window().should(
      'satisfy',
      win => {
        return !!win.installerData;
      },
      'Installer data should be available on window object'
    );

    cy.window().should(
      'satisfy',
      win => {
        return !!win.validateField && typeof win.validateField === 'function';
      },
      'validateField function should be available on window object'
    );

    cy.get('#site-name').clear().type('Test Site');
    cy.wait(500);
    cy.get('#site-machine-name').should(
      'have.value',
      'test_site',
      'Machine name should be auto-generated proving Alpine.js is working'
    );

    cy.get('#site-machine-name').clear().type('test with spaces');
    cy.wait(500);
    cy.get('#site-machine-name').should(
      'have.class',
      'invalid',
      'Machine name should show invalid state when containing spaces, proving validation is working'
    );
  });
});
