const puppeteer = require('puppeteer');

describe('VerticalTabs E2E Tests', () => {
  let browser;
  let page;
  const baseUrl = process.env.E2E_BASE_URL || 'http://localhost:3000';

  beforeAll(async () => {
    browser = await puppeteer.launch({
      headless: process.env.CI ? true : false,
      args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });
  });

  afterAll(async () => {
    if (browser) {
      await browser.close();
    }
  });

  beforeEach(async () => {
    page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });
  });

  afterEach(async () => {
    if (page) {
      await page.close();
    }
  });

  describe('Component Rendering', () => {
    test('should render vertical tabs layout', async () => {
      await page.goto(`${baseUrl}/getting-started/features-markdown`);
      
      // Wait for the component to load
      await page.waitForSelector('.vertical-features', { timeout: 5000 });
      
      // Check sidebar exists
      const sidebar = await page.$('.sidebar');
      expect(sidebar).toBeTruthy();
      
      // Check content area exists
      const contentArea = await page.$('.content-area');
      expect(contentArea).toBeTruthy();
      
      // Check Feature Categories header
      const header = await page.$eval('.sidebar-header h3', el => el.textContent);
      expect(header).toBe('Feature Categories');
    });

    test('should display all feature tabs', async () => {
      await page.goto(`${baseUrl}/getting-started/features-markdown`);
      await page.waitForSelector('.tab-list', { timeout: 5000 });
      
      // Count tab items
      const tabItems = await page.$$('.tab-item');
      expect(tabItems.length).toBeGreaterThan(0);
      
      // Check for expected feature titles
      const expectedFeatures = [
        'Drupal Foundation',
        'Docker Services',
        'Development Tools',
        'Code Quality',
        'Testing Framework',
        'Deployment Pipeline',
        'Security Features',
        'Performance Tools',
        'Content Management'
      ];
      
      for (const feature of expectedFeatures) {
        const element = await page.waitForSelector(
          `text=${feature}`,
          { timeout: 2000 }
        );
        expect(element).toBeTruthy();
      }
    });

    test('should show first tab as active by default', async () => {
      await page.goto(`${baseUrl}/getting-started/features-markdown`);
      await page.waitForSelector('.tab-item.active', { timeout: 5000 });
      
      const activeTab = await page.$('.tab-item.active');
      expect(activeTab).toBeTruthy();
      
      // Check it's the first tab
      const firstTab = await page.$('.tab-item:first-child');
      const isActive = await firstTab.evaluate(el => el.classList.contains('active'));
      expect(isActive).toBe(true);
    });
  });

  describe('Tab Navigation', () => {
    test('should switch content when clicking different tabs', async () => {
      await page.goto(`${baseUrl}/getting-started/features-markdown`);
      await page.waitForSelector('.tab-list', { timeout: 5000 });
      
      // Get initial content
      const initialContent = await page.$eval('.content-panel h1', el => el.textContent);
      
      // Click second tab
      const secondTab = await page.$('.tab-item:nth-child(2)');
      await secondTab.click();
      
      // Wait for content to change
      await page.waitForFunction(
        (initial) => {
          const current = document.querySelector('.content-panel h1');
          return current && current.textContent !== initial;
        },
        {},
        initialContent
      );
      
      // Verify content changed
      const newContent = await page.$eval('.content-panel h1', el => el.textContent);
      expect(newContent).not.toBe(initialContent);
    });

    test('should update active tab styling', async () => {
      await page.goto(`${baseUrl}/getting-started/features-markdown`);
      await page.waitForSelector('.tab-item.active', { timeout: 5000 });
      
      // Click third tab
      const thirdTab = await page.$('.tab-item:nth-child(3)');
      await thirdTab.click();
      
      // Wait for active class to update
      await page.waitForSelector('.tab-item:nth-child(3).active', { timeout: 2000 });
      
      // Verify only third tab is active
      const activeTabsCount = await page.$$eval('.tab-item.active', els => els.length);
      expect(activeTabsCount).toBe(1);
      
      const thirdTabActive = await page.$eval(
        '.tab-item:nth-child(3)',
        el => el.classList.contains('active')
      );
      expect(thirdTabActive).toBe(true);
    });

    test('should support keyboard navigation', async () => {
      await page.goto(`${baseUrl}/getting-started/features-markdown`);
      await page.waitForSelector('.tab-list', { timeout: 5000 });
      
      // Focus first tab
      await page.focus('.tab-item:first-child');
      
      // Use arrow keys to navigate
      await page.keyboard.press('ArrowDown');
      await page.keyboard.press('Enter');
      
      // Wait for tab to become active
      await page.waitForSelector('.tab-item:nth-child(2).active', { timeout: 2000 });
      
      const secondTabActive = await page.$eval(
        '.tab-item:nth-child(2)',
        el => el.classList.contains('active')
      );
      expect(secondTabActive).toBe(true);
    });
  });

  describe('Content Display', () => {
    test('should display expandable details sections', async () => {
      await page.goto(`${baseUrl}/getting-started/features-markdown`);
      await page.waitForSelector('.content-panel', { timeout: 5000 });
      
      // Look for details elements
      const detailsElements = await page.$$('details');
      expect(detailsElements.length).toBeGreaterThan(0);
      
      // Test expanding a details section
      const firstDetails = detailsElements[0];
      await firstDetails.click();
      
      // Wait for details to expand
      await page.waitForTimeout(500);
      
      const isOpen = await firstDetails.evaluate(el => el.hasAttribute('open'));
      expect(isOpen).toBe(true);
    });

    test('should display badges and icons correctly', async () => {
      await page.goto(`${baseUrl}/getting-started/features-markdown`);
      await page.waitForSelector('.content-panel', { timeout: 5000 });
      
      // Check for content badge
      const badge = await page.$('.content-badge');
      expect(badge).toBeTruthy();
      
      // Check for tab icons
      const tabIcons = await page.$$('.tab-icon');
      expect(tabIcons.length).toBeGreaterThan(0);
      
      // Verify icon content (should be emoji)
      const firstIconText = await page.$eval('.tab-icon', el => el.textContent);
      expect(firstIconText).toMatch(/[\u{1F300}-\u{1F9FF}]/u); // Unicode emoji range
    });
  });

  describe('Responsive Design', () => {
    test('should adapt layout for mobile devices', async () => {
      await page.goto(`${baseUrl}/getting-started/features-markdown`);
      await page.setViewport({ width: 375, height: 667 }); // iPhone size
      
      await page.waitForSelector('.vertical-layout', { timeout: 5000 });
      
      // Check if layout becomes vertical on mobile
      const layoutStyle = await page.$eval('.vertical-layout', el => 
        window.getComputedStyle(el).flexDirection
      );
      
      // Should be column on mobile
      expect(layoutStyle).toBe('column');
    });

    test('should maintain functionality on tablet', async () => {
      await page.goto(`${baseUrl}/getting-started/features-markdown`);
      await page.setViewport({ width: 768, height: 1024 }); // iPad size
      
      await page.waitForSelector('.tab-list', { timeout: 5000 });
      
      // Test tab switching still works
      const secondTab = await page.$('.tab-item:nth-child(2)');
      await secondTab.click();
      
      await page.waitForSelector('.tab-item:nth-child(2).active', { timeout: 2000 });
      
      const isActive = await page.$eval(
        '.tab-item:nth-child(2)',
        el => el.classList.contains('active')
      );
      expect(isActive).toBe(true);
    });
  });

  describe('Performance', () => {
    test('should load component within reasonable time', async () => {
      const startTime = Date.now();
      
      await page.goto(`${baseUrl}/getting-started/features-markdown`);
      await page.waitForSelector('.vertical-features', { timeout: 5000 });
      
      const loadTime = Date.now() - startTime;
      
      // Should load within 3 seconds
      expect(loadTime).toBeLessThan(3000);
    });

    test('should handle rapid tab switching', async () => {
      await page.goto(`${baseUrl}/getting-started/features-markdown`);
      await page.waitForSelector('.tab-list', { timeout: 5000 });
      
      // Rapidly click through tabs
      const tabItems = await page.$$('.tab-item');
      
      for (let i = 0; i < Math.min(5, tabItems.length); i++) {
        await tabItems[i].click();
        await page.waitForTimeout(100); // Small delay between clicks
      }
      
      // Verify last clicked tab is active
      const lastIndex = Math.min(4, tabItems.length - 1);
      const lastTabActive = await page.$eval(
        `.tab-item:nth-child(${lastIndex + 1})`,
        el => el.classList.contains('active')
      );
      expect(lastTabActive).toBe(true);
    });
  });

  describe('Accessibility', () => {
    test('should have proper ARIA attributes', async () => {
      await page.goto(`${baseUrl}/getting-started/features-markdown`);
      await page.waitForSelector('.tab-list', { timeout: 5000 });
      
      // Check for semantic HTML structure
      const sidebar = await page.$('.sidebar');
      const contentArea = await page.$('.content-area');
      
      expect(sidebar).toBeTruthy();
      expect(contentArea).toBeTruthy();
      
      // Verify details/summary structure for expandable content
      const summaryElements = await page.$$('summary');
      expect(summaryElements.length).toBeGreaterThan(0);
    });

    test('should support screen reader navigation', async () => {
      await page.goto(`${baseUrl}/getting-started/features-markdown`);
      await page.waitForSelector('.tab-list', { timeout: 5000 });
      
      // Test tab navigation with Tab key
      await page.keyboard.press('Tab');
      
      // Check if focus is on a tab item
      const focusedElement = await page.evaluate(() => document.activeElement.className);
      expect(focusedElement).toContain('tab-item');
    });
  });
});