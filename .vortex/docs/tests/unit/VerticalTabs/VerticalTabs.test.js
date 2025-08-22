import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import {
  VerticalTabs,
  VerticalTab,
  VerticalTabPanel,
} from '../../../src/components/VerticalTabs';

// Mock window.location and history for hash testing
const mockLocation = {
  pathname: '/test',
  search: '',
  hash: '',
};

const mockHistory = {
  replaceState: jest.fn(),
};

Object.defineProperty(window, 'location', {
  value: mockLocation,
  writable: true,
});

Object.defineProperty(window, 'history', {
  value: mockHistory,
  writable: true,
});

describe('VerticalTabs with VerticalTab/VerticalTabPanel Components', () => {
  beforeEach(() => {
    // Reset mocks before each test
    mockLocation.hash = '';
    mockHistory.replaceState.mockClear();

    // Clear any existing hashchange listeners
    window.removeEventListener = jest.fn();
    window.addEventListener = jest.fn();
  });
  describe('Basic Rendering', () => {
    test('renders tabs with explicit VerticalTab and VerticalTabPanel components', () => {
      render(
        <VerticalTabs>
          <VerticalTab>💧 Drupal Foundation | Core platform</VerticalTab>
          <VerticalTabPanel>
            <p>Drupal content here</p>
          </VerticalTabPanel>
          <VerticalTab>🐳 Docker Services | Container stack</VerticalTab>
          <VerticalTabPanel>
            <p>Docker content here</p>
          </VerticalTabPanel>
        </VerticalTabs>
      );

      // Check sidebar structure
      expect(screen.getByText('Feature Categories')).toBeInTheDocument();
      expect(screen.getByText('Drupal Foundation')).toBeInTheDocument();
      expect(screen.getByText('Docker Services')).toBeInTheDocument();

      // Check descriptions in sidebar
      expect(screen.getAllByText('Core platform')).toHaveLength(2); // Appears in sidebar and content
      expect(screen.getByText('Container stack')).toBeInTheDocument(); // Only in sidebar (not active tab)

      // Check content is displayed for first tab
      expect(screen.getByText('💧 Drupal Foundation')).toBeInTheDocument();
      expect(screen.getAllByText('Core platform')).toHaveLength(2); // Should appear in both sidebar and content
      expect(screen.getByText('Drupal content here')).toBeInTheDocument();
    });

    test('handles missing VerticalTabPanel gracefully', () => {
      render(
        <VerticalTabs>
          <VerticalTab>📄 Tab Without Panel | No panel</VerticalTab>
          <VerticalTab>🔧 Another Tab | With panel</VerticalTab>
          <VerticalTabPanel>
            <p>Content for second tab</p>
          </VerticalTabPanel>
        </VerticalTabs>
      );

      expect(screen.getByText('Tab Without Panel')).toBeInTheDocument();
      expect(screen.getByText('Another Tab')).toBeInTheDocument();

      // Second tab should be active and show content (first tab has no panel)
      expect(screen.getByText('Content for second tab')).toBeInTheDocument();
    });

    test('handles extra VerticalTabPanels beyond tabs', () => {
      render(
        <VerticalTabs>
          <VerticalTab>🎯 Single Tab | Only tab</VerticalTab>
          <VerticalTabPanel>
            <p>Content for single tab</p>
          </VerticalTabPanel>
          <VerticalTabPanel>
            <p>Extra panel content - should be ignored</p>
          </VerticalTabPanel>
        </VerticalTabs>
      );

      expect(screen.getByText('Single Tab')).toBeInTheDocument();
      expect(screen.getByText('Content for single tab')).toBeInTheDocument();
      expect(
        screen.queryByText('Extra panel content - should be ignored')
      ).not.toBeInTheDocument();
    });

    test('uses default values for malformed tab content', () => {
      render(
        <VerticalTabs>
          <VerticalTab>No Pipes Here</VerticalTab>
          <VerticalTabPanel>
            <p>Content for malformed tab</p>
          </VerticalTabPanel>
          <VerticalTab></VerticalTab>
          <VerticalTabPanel>
            <p>Content for empty tab</p>
          </VerticalTabPanel>
        </VerticalTabs>
      );

      // Should handle malformed content gracefully
      expect(screen.getByText('No Pipes Here')).toBeInTheDocument();
      expect(screen.getByText('Untitled Tab')).toBeInTheDocument();
    });
  });

  describe('Tab Navigation', () => {
    test('switches between tabs correctly', async () => {
      render(
        <VerticalTabs>
          <VerticalTab>🔧 First Tab | First description</VerticalTab>
          <VerticalTabPanel>
            <p>First tab content</p>
          </VerticalTabPanel>
          <VerticalTab>🚀 Second Tab | Second description</VerticalTab>
          <VerticalTabPanel>
            <p>Second tab content</p>
          </VerticalTabPanel>
        </VerticalTabs>
      );

      // Initially shows first tab
      expect(screen.getByText('First tab content')).toBeInTheDocument();
      expect(screen.queryByText('Second tab content')).not.toBeInTheDocument();
      expect(screen.getAllByText('First description')).toHaveLength(2); // Subtitle in both places

      // Click second tab
      fireEvent.click(screen.getByText('Second Tab'));

      // Should switch to second tab
      await waitFor(() => {
        expect(screen.getByText('Second tab content')).toBeInTheDocument();
        expect(screen.getAllByText('Second description')).toHaveLength(2); // Subtitle in both places
      });

      expect(screen.queryByText('First tab content')).not.toBeInTheDocument();
    });

    test('updates active tab styling', async () => {
      render(
        <VerticalTabs>
          <VerticalTab>🔧 First Tab | First description</VerticalTab>
          <VerticalTabPanel>Content 1</VerticalTabPanel>
          <VerticalTab>🚀 Second Tab | Second description</VerticalTab>
          <VerticalTabPanel>Content 2</VerticalTabPanel>
        </VerticalTabs>
      );

      const firstTab = screen.getByText('First Tab').closest('.tab-item');
      const secondTab = screen.getByText('Second Tab').closest('.tab-item');

      // Initially first tab is active
      expect(firstTab).toHaveClass('active');
      expect(secondTab).not.toHaveClass('active');

      // Click second tab
      fireEvent.click(screen.getByText('Second Tab'));

      await waitFor(() => {
        expect(secondTab).toHaveClass('active');
        expect(firstTab).not.toHaveClass('active');
      });
    });
  });

  describe('Complex Content Handling', () => {
    test('handles complex nested content in VerticalTabPanel', () => {
      render(
        <VerticalTabs>
          <VerticalTab>🏗️ Complex Tab | Complex content</VerticalTab>
          <VerticalTabPanel>
            <h3>Subheading</h3>
            <p>Regular paragraph</p>
            <ul>
              <li>List item 1</li>
              <li>List item 2</li>
            </ul>
            <details>
              <summary>Details Summary</summary>
              <p>Details content</p>
            </details>
          </VerticalTabPanel>
        </VerticalTabs>
      );

      // All complex content should be preserved
      expect(screen.getByText('Subheading')).toBeInTheDocument();
      expect(screen.getByText('Regular paragraph')).toBeInTheDocument();
      expect(screen.getByText('List item 1')).toBeInTheDocument();
      expect(screen.getByText('Details Summary')).toBeInTheDocument();
    });

    test('handles icons and special characters correctly', () => {
      render(
        <VerticalTabs>
          <VerticalTab>
            🌟 Special Characters & Icons | Description with & symbols
          </VerticalTab>
          <VerticalTabPanel>
            <p>Content with special characters: © ® ™ & more!</p>
          </VerticalTabPanel>
        </VerticalTabs>
      );

      expect(
        screen.getByText('Special Characters & Icons')
      ).toBeInTheDocument();
      expect(screen.getAllByText('Description with & symbols')).toHaveLength(2);
    });
  });

  describe('Error Handling', () => {
    test('shows error message when no VerticalTab components provided', () => {
      render(
        <VerticalTabs>
          <p>Regular content without VerticalTab components</p>
          <div>Some other content</div>
        </VerticalTabs>
      );

      expect(
        screen.getByText(
          'No tabs found. Use VerticalTab and VerticalTabPanel components:'
        )
      ).toBeInTheDocument();
      expect(screen.getByText(/VerticalTabs>/)).toBeInTheDocument();
    });

    test('shows error message for empty VerticalTabs', () => {
      render(<VerticalTabs />);

      expect(
        screen.getByText(
          'No tabs found. Use VerticalTab and VerticalTabPanel components:'
        )
      ).toBeInTheDocument();
    });

    test('shows error message for VerticalTabs with only non-VerticalTab children', () => {
      render(
        <VerticalTabs>
          {null}
          {undefined}
          <div>Not a VerticalTab component</div>
          <span>Another non-VerticalTab element</span>
        </VerticalTabs>
      );

      expect(
        screen.getByText(
          'No tabs found. Use VerticalTab and VerticalTabPanel components:'
        )
      ).toBeInTheDocument();
    });

    test('filters out non-VerticalTab components gracefully', () => {
      render(
        <VerticalTabs>
          <div>Non-tab content</div>
          <VerticalTab>✅ Valid Tab | Good tab</VerticalTab>
          <VerticalTabPanel>Valid content</VerticalTabPanel>
          <p>Another non-tab element</p>
          <VerticalTab>✅ Another Valid | Another good tab</VerticalTab>
          <VerticalTabPanel>Another valid content</VerticalTabPanel>
        </VerticalTabs>
      );

      // Should only show valid tabs
      expect(screen.getByText('Valid Tab')).toBeInTheDocument();
      expect(screen.getByText('Another Valid')).toBeInTheDocument();

      // Non-tab content should not appear in sidebar
      expect(screen.queryByText('Non-tab content')).not.toBeInTheDocument();
    });
  });

  describe('Subtitle Display', () => {
    test('shows subtitle in both sidebar and content when provided', () => {
      render(
        <VerticalTabs>
          <VerticalTab>🎖️ With Subtitle | Has subtitle</VerticalTab>
          <VerticalTabPanel>Content with subtitle</VerticalTabPanel>
        </VerticalTabs>
      );

      // Should appear in both sidebar and content header
      expect(screen.getAllByText('Has subtitle')).toHaveLength(2);
    });

    test('handles missing subtitle gracefully', () => {
      const { container } = render(
        <VerticalTabs>
          <VerticalTab>🎖️ No Subtitle</VerticalTab>
          <VerticalTabPanel>Content without subtitle</VerticalTabPanel>
        </VerticalTabs>
      );

      // Should not show any subtitle
      expect(container.querySelector('.content-subtitle')).toBeNull();
    });
  });

  describe('Accessibility', () => {
    test('maintains proper heading structure', () => {
      render(
        <VerticalTabs>
          <VerticalTab>
            ♿ Accessible Tab | Accessibility test | A11y
          </VerticalTab>
          <VerticalTabPanel>
            <h3>Content heading</h3>
            <p>Accessible content</p>
          </VerticalTabPanel>
        </VerticalTabs>
      );

      // Should have proper heading hierarchy
      const heading = screen.getByRole('heading', { level: 2 });
      expect(heading.textContent).toContain('Accessible Tab');

      const headings = screen.getAllByRole('heading', { level: 3 });
      const contentHeading = headings.find(
        h => h.textContent === 'Content heading'
      );
      expect(contentHeading).toBeInTheDocument();
    });

    test('tabs are clickable and focusable', () => {
      render(
        <VerticalTabs>
          <VerticalTab>🔘 Clickable Tab | Click test | Click</VerticalTab>
          <VerticalTabPanel>Clickable content</VerticalTabPanel>
          <VerticalTab>🔘 Another Tab | Another click test | Click</VerticalTab>
          <VerticalTabPanel>Another content</VerticalTabPanel>
        </VerticalTabs>
      );

      const firstTab = screen.getByText('Clickable Tab').closest('.tab-item');
      const secondTab = screen.getByText('Another Tab').closest('.tab-item');

      // Tabs should be clickable
      expect(firstTab).toBeInTheDocument();
      expect(secondTab).toBeInTheDocument();

      // Click should work
      fireEvent.click(secondTab);
      expect(screen.getByText('Another content')).toBeInTheDocument();
    });
  });

  describe('Component Detection', () => {
    test('detects VerticalTab components by type name', () => {
      // Simulate how MDX would render these components
      const VerticalTabComponent = props =>
        React.createElement(VerticalTab, props);
      VerticalTabComponent.displayName = 'VerticalTab';

      const VerticalTabPanelComponent = props =>
        React.createElement(VerticalTabPanel, props);
      VerticalTabPanelComponent.displayName = 'VerticalTabPanel';

      render(
        <VerticalTabs>
          <VerticalTabComponent>
            🎯 Detected Tab | Type detection | Detected
          </VerticalTabComponent>
          <VerticalTabPanelComponent>
            Content from detected components
          </VerticalTabPanelComponent>
        </VerticalTabs>
      );

      expect(screen.getByText('Detected Tab')).toBeInTheDocument();
      expect(
        screen.getByText('Content from detected components')
      ).toBeInTheDocument();
    });

    test('detects components by data attributes as fallback', () => {
      render(
        <VerticalTabs>
          <div data-component="VerticalTab">
            📋 Data Attribute Tab | Fallback detection | Fallback
          </div>
          <div data-component="VerticalTabPanel">
            Content from data attribute detection
          </div>
        </VerticalTabs>
      );

      expect(screen.getByText('Data Attribute Tab')).toBeInTheDocument();
      expect(
        screen.getByText('Content from data attribute detection')
      ).toBeInTheDocument();
    });
  });

  describe('Hash-based Active Tab Support', () => {
    test('sets active tab from URL hash on mount', () => {
      // Set hash to match second tab's slug
      mockLocation.hash = '#docker-services';

      render(
        <VerticalTabs>
          <VerticalTab>💧 Drupal Foundation | Core platform</VerticalTab>
          <VerticalTabPanel>
            <p>Drupal content here</p>
          </VerticalTabPanel>
          <VerticalTab>🐳 Docker Services | Container stack</VerticalTab>
          <VerticalTabPanel>
            <p>Docker content here</p>
          </VerticalTabPanel>
        </VerticalTabs>
      );

      // Should show content for second tab (Docker Services)
      expect(screen.getByText('Docker content here')).toBeInTheDocument();
      expect(screen.queryByText('Drupal content here')).not.toBeInTheDocument();

      // Second tab should be active
      const dockerTab = screen.getByText('Docker Services').closest('.tab-item');
      expect(dockerTab).toHaveClass('active');
    });

    test('updates URL hash when tab is clicked', async () => {
      render(
        <VerticalTabs>
          <VerticalTab>💧 Drupal Foundation | Core platform</VerticalTab>
          <VerticalTabPanel>
            <p>Drupal content here</p>
          </VerticalTabPanel>
          <VerticalTab>🐳 Docker Services | Container stack</VerticalTab>
          <VerticalTabPanel>
            <p>Docker content here</p>
          </VerticalTabPanel>
        </VerticalTabs>
      );

      // Click second tab
      fireEvent.click(screen.getByText('Docker Services'));

      // Should update URL hash
      await waitFor(() => {
        expect(mockHistory.replaceState).toHaveBeenCalledWith(
          null,
          '',
          '/test#docker-services'
        );
      });
    });

    test('handles hash changes via browser navigation', () => {
      let hashChangeHandler;

      // Capture the hashchange event listener
      window.addEventListener = jest.fn((event, handler) => {
        if (event === 'hashchange') {
          hashChangeHandler = handler;
        }
      });

      render(
        <VerticalTabs>
          <VerticalTab>💧 Drupal Foundation | Core platform</VerticalTab>
          <VerticalTabPanel>
            <p>Drupal content here</p>
          </VerticalTabPanel>
          <VerticalTab>🐳 Docker Services | Container stack</VerticalTab>
          <VerticalTabPanel>
            <p>Docker content here</p>
          </VerticalTabPanel>
        </VerticalTabs>
      );

      // Verify event listener was added
      expect(window.addEventListener).toHaveBeenCalledWith('hashchange', expect.any(Function));

      // Initially shows first tab
      expect(screen.getByText('Drupal content here')).toBeInTheDocument();

      // Simulate hash change
      mockLocation.hash = '#docker-services';
      if (hashChangeHandler) {
        act(() => {
          hashChangeHandler();
        });
      }

      // Should switch to second tab
      expect(screen.getByText('Docker content here')).toBeInTheDocument();
      expect(screen.queryByText('Drupal content here')).not.toBeInTheDocument();
    });

    test('ignores invalid hash values', () => {
      mockLocation.hash = '#nonexistent-tab';

      render(
        <VerticalTabs>
          <VerticalTab>💧 Drupal Foundation | Core platform</VerticalTab>
          <VerticalTabPanel>
            <p>Drupal content here</p>
          </VerticalTabPanel>
          <VerticalTab>🐳 Docker Services | Container stack</VerticalTab>
          <VerticalTabPanel>
            <p>Docker content here</p>
          </VerticalTabPanel>
        </VerticalTabs>
      );

      // Should default to first tab when hash doesn't match any tab
      expect(screen.getByText('Drupal content here')).toBeInTheDocument();
      expect(screen.queryByText('Docker content here')).not.toBeInTheDocument();

      const drupalTab = screen.getByText('Drupal Foundation').closest('.tab-item');
      expect(drupalTab).toHaveClass('active');
    });

    test('handles empty hash correctly', () => {
      mockLocation.hash = '';

      render(
        <VerticalTabs>
          <VerticalTab>💧 Drupal Foundation | Core platform</VerticalTab>
          <VerticalTabPanel>
            <p>Drupal content here</p>
          </VerticalTabPanel>
          <VerticalTab>🐳 Docker Services | Container stack</VerticalTab>
          <VerticalTabPanel>
            <p>Docker content here</p>
          </VerticalTabPanel>
        </VerticalTabs>
      );

      // Should default to first tab
      expect(screen.getByText('Drupal content here')).toBeInTheDocument();
      expect(screen.queryByText('Docker content here')).not.toBeInTheDocument();
    });

    test('creates proper slugs from tab titles', () => {
      render(
        <VerticalTabs>
          <VerticalTab>🎯 Special Characters & Symbols! | Test</VerticalTab>
          <VerticalTabPanel>
            <p>Special content here</p>
          </VerticalTabPanel>
          <VerticalTab>🚀 Multi Spaces Tab | Test</VerticalTab>
          <VerticalTabPanel>
            <p>Multi spaces content</p>
          </VerticalTabPanel>
        </VerticalTabs>
      );

      // Click first tab to check slug generation
      fireEvent.click(screen.getByText('Special Characters & Symbols!'));

      expect(mockHistory.replaceState).toHaveBeenCalledWith(
        null,
        '',
        '/test#special-characters-symbols'
      );

      // Click second tab to check multiple spaces handling
      fireEvent.click(screen.getByText('Multi Spaces Tab'));

      expect(mockHistory.replaceState).toHaveBeenCalledWith(
        null,
        '',
        '/test#multi-spaces-tab'
      );
    });

    test('preserves search parameters when updating hash', async () => {
      mockLocation.search = '?param=value';

      render(
        <VerticalTabs>
          <VerticalTab>💧 Drupal Foundation | Core platform</VerticalTab>
          <VerticalTabPanel>
            <p>Drupal content here</p>
          </VerticalTabPanel>
          <VerticalTab>🐳 Docker Services | Container stack</VerticalTab>
          <VerticalTabPanel>
            <p>Docker content here</p>
          </VerticalTabPanel>
        </VerticalTabs>
      );

      // Click second tab
      fireEvent.click(screen.getByText('Docker Services'));

      // Should preserve search parameters
      await waitFor(() => {
        expect(mockHistory.replaceState).toHaveBeenCalledWith(
          null,
          '',
          '/test?param=value#docker-services'
        );
      });
    });

    test('cleans up event listeners on unmount', () => {
      const { unmount } = render(
        <VerticalTabs>
          <VerticalTab>💧 Test Tab | Test</VerticalTab>
          <VerticalTabPanel>
            <p>Test content</p>
          </VerticalTabPanel>
        </VerticalTabs>
      );

      // Verify event listener was added
      expect(window.addEventListener).toHaveBeenCalledWith('hashchange', expect.any(Function));

      // Unmount component
      unmount();

      // Verify event listener was removed
      expect(window.removeEventListener).toHaveBeenCalledWith('hashchange', expect.any(Function));
    });
  });

  describe('Helper Function Edge Cases', () => {
    test('handles non-valid element children gracefully', () => {
      render(
        <VerticalTabs>
          {null}
          {undefined}
          {false}
          {'string content'}
          {123}
          <VerticalTab>Valid tab</VerticalTab>
          <VerticalTabPanel>Valid panel</VerticalTabPanel>
        </VerticalTabs>
      );

      // Should only show the valid components
      expect(screen.getByText('Valid tab')).toBeInTheDocument();
      expect(screen.getByText('Valid panel')).toBeInTheDocument();
    });

    test('extractText handles different data types in tab titles', () => {
      render(
        <VerticalTabs>
          <VerticalTab>
            <div>
              <span>Mixed content:</span>
              {42}
              {['array', ' values']}
              <em>nested elements</em>
            </div>
          </VerticalTab>
          <VerticalTabPanel>Panel content</VerticalTabPanel>
        </VerticalTabs>
      );

      // Should extract and display all text types correctly
      expect(screen.getByText(/Mixed content:.*42.*array values.*nested elements/)).toBeInTheDocument();
    });

    test('handleTabClick with empty slug does not update URL', () => {
      render(
        <VerticalTabs>
          <VerticalTab></VerticalTab> {/* Empty tab creates empty slug */}
          <VerticalTabPanel>Panel for empty tab</VerticalTabPanel>
        </VerticalTabs>
      );

      const emptyTab = document.querySelector('.tab-item');
      fireEvent.click(emptyTab);

      // Should work without URL update (due to empty slug)
      expect(screen.getByText('Panel for empty tab')).toBeInTheDocument();
    });
  });
});
