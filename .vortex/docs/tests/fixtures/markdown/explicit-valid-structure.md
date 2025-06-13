import { VerticalTabsExplicit, Tab, TabPanel } from '@site/src/components/VerticalTabs';

# Explicit Tab Structure - Valid Examples

<VerticalTabsExplicit>
  <Tab icon="💧" title="Drupal Foundation" description="Core Drupal platform" badge="Platform">
    <TabPanel>
      
      Modern Drupal 11 with pre-configured settings, scaffolds, and admin modules for production-ready development.

      ### ✅ Drupal 11 Foundation
      **Modern composer-based architecture with PHP 8+ compatibility**

      <details>
      <summary>View Implementation Details</summary>
      Built on the latest Drupal 11 with industry-standard composer dependency management and modern PHP features.
      </details>

      ### ✅ Configuration Management
      **Professional-grade configuration workflow**

      Export and import configuration changes between environments with confidence.

      :::tip Pro Tip
      Use configuration splits to manage environment-specific settings.
      :::

    </TabPanel>
  </Tab>

  <Tab icon="🐳" title="Docker Services" description="Container stack" badge="Infrastructure">
    <TabPanel>
      
      Complete containerized development environment with all dependencies included.

      ### ✅ Docker Compose Setup
      **Full development stack in containers**

      <details>
      <summary>Service Details</summary>
      
      **Services included:**
      - PHP 8.3 with Xdebug
      - MySQL 8.0 with persistent data
      - Nginx with SSL support
      - Redis for caching
      - MailHog for email testing

      ```yaml
      services:
        web:
          image: nginx:alpine
          ports:
            - "80:80"
            - "443:443"
        
        php:
          build: .
          volumes:
            - .:/var/www/html
      ```

      </details>

      ### ✅ Development Tools
      **Pre-configured development environment**

      All tools configured and ready:
      - Xdebug for debugging
      - Drush for Drupal CLI
      - Composer for dependencies
      - Quality tools (PHPCS, PHPStan)

    </TabPanel>
  </Tab>

  <Tab icon="🔧" title="Testing Framework" description="Comprehensive testing tools" badge="Quality">
    <TabPanel>
      
      Professional testing toolkit with multiple testing layers.

      ### ✅ Unit Testing
      **PHPUnit for isolated component testing**

      ```php
      class UserServiceTest extends TestCase 
      {
          public function testCreateUser(): void
          {
              $service = new UserService();
              $user = $service->create(['email' => 'test@example.com']);
              
              $this->assertInstanceOf(User::class, $user);
              $this->assertEquals('test@example.com', $user->getEmail());
          }
      }
      ```

      ### ✅ Behavioral Testing
      **Behat for user story validation**

      <details>
      <summary>Behat Example</summary>

      ```gherkin
      Feature: User Registration
        As a visitor
        I want to register an account
        So that I can access member features

        Scenario: Successful registration
          Given I am on the registration page
          When I fill in "Email" with "user@example.com"
          And I fill in "Password" with "secure123"
          And I click "Register"
          Then I should see "Registration successful"
      ```

      </details>

      ### ✅ Integration Testing
      **Full application testing with database**

      - Database fixtures and cleanup
      - API endpoint testing
      - Form submission testing
      - Email delivery testing

    </TabPanel>
  </Tab>

  <Tab icon="🚀" title="Deployment Pipeline" description="CI/CD and automation" badge="DevOps">
    <TabPanel>
      
      Automated deployment pipeline with multiple environment support.

      ### ✅ Continuous Integration
      **GitHub Actions workflow**

      <details>
      <summary>CI Pipeline Steps</summary>

      ```yaml
      name: CI
      on: [push, pull_request]
      
      jobs:
        test:
          runs-on: ubuntu-latest
          steps:
            - uses: actions/checkout@v4
            - name: Setup PHP
              uses: shivammathur/setup-php@v2
              with:
                php-version: 8.3
            - name: Install dependencies
              run: composer install
            - name: Run tests
              run: |
                vendor/bin/phpunit
                vendor/bin/behat
            - name: Code quality
              run: |
                vendor/bin/phpcs
                vendor/bin/phpstan
      ```

      </details>

      ### ✅ Deployment Automation
      **Multi-environment deployment**

      :::warning Environment Management
      Always test deployments in staging before production.
      :::

      **Supported environments:**
      - Development (automatic on feature branches)
      - Staging (automatic on develop branch)
      - Production (manual approval required)

      **Deployment features:**
      - Zero-downtime deployments
      - Automatic rollback on failure
      - Database migration handling
      - Cache warming
      - Health checks

    </TabPanel>
  </Tab>
</VerticalTabsExplicit>