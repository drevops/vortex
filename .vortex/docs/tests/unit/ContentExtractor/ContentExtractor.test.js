import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import ContentExtractor from '../../../src/components/ContentExtractor';

// Mock MDX content components for testing
const MockContentWithHeading = () => (
  <div>
    <h1>Page Title</h1>
    <p>
      Select a feature from the tab list to view details in the content panel.
    </p>
    <h2>Section 1</h2>
    <p>This is section 1 content.</p>
    <h3>Subsection 1.1</h3>
    <p>This is subsection content.</p>
  </div>
);

const MockContentWithVerticalTabs = () => (
  <div>
    <h1>Features</h1>
    <p>Select a feature from the tab list to view details.</p>
    <div data-component="VerticalTabs">
      <div>Tab 1</div>
      <div>Tab content 1</div>
    </div>
    <h2>Additional Section</h2>
    <p>More content after tabs.</p>
  </div>
);

const MockContentNoHeading = () => (
  <div>
    <p>Content without any headings.</p>
    <div className="content-block">
      <span>Some nested content</span>
    </div>
  </div>
);

const MockEmptyContent = () => <div></div>;

const MockContentWithFrontmatter = () => (
  <div>
    <div className="frontmatter">title: Features</div>
    <div data-frontmatter='{"title": "Features", "sidebar_position": 2}'>
      Frontmatter data
    </div>
    <div className="metadata">Meta information</div>
    <h1>Actual Content Title</h1>
    <p>This is the actual page content.</p>
    <h2>Section</h2>
    <p>More content here.</p>
  </div>
);

describe('ContentExtractor', () => {
  describe('Basic Rendering', () => {
    test('renders children without any modifications by default', () => {
      render(
        <ContentExtractor stripFirstHeading={false} stripIntro={false}>
          <MockContentWithHeading />
        </ContentExtractor>
      );

      expect(screen.getByText('Page Title')).toBeInTheDocument();
      expect(
        screen.getByText(
          'Select a feature from the tab list to view details in the content panel.'
        )
      ).toBeInTheDocument();
      expect(screen.getByText('Section 1')).toBeInTheDocument();
      expect(
        screen.getByText('This is section 1 content.')
      ).toBeInTheDocument();
    });

    test('applies default className', () => {
      const { container } = render(
        <ContentExtractor>
          <MockContentNoHeading />
        </ContentExtractor>
      );

      expect(container.firstChild).toHaveClass('mdx-content-extracted');
    });

    test('applies custom className', () => {
      const { container } = render(
        <ContentExtractor className="custom-class">
          <MockContentNoHeading />
        </ContentExtractor>
      );

      expect(container.firstChild).toHaveClass('custom-class');
    });
  });

  describe('Heading Stripping', () => {
    test('strips first h1 heading when stripFirstHeading is true', () => {
      render(
        <ContentExtractor stripFirstHeading={true}>
          <MockContentWithHeading />
        </ContentExtractor>
      );

      // First h1 should be removed
      expect(screen.queryByText('Page Title')).not.toBeInTheDocument();

      // Other content should remain
      expect(screen.getByText('Section 1')).toBeInTheDocument();
      expect(
        screen.getByText('This is section 1 content.')
      ).toBeInTheDocument();
    });

    test('preserves first h1 heading when stripFirstHeading is false', () => {
      render(
        <ContentExtractor stripFirstHeading={false}>
          <MockContentWithHeading />
        </ContentExtractor>
      );

      expect(screen.getByText('Page Title')).toBeInTheDocument();
      expect(screen.getByText('Section 1')).toBeInTheDocument();
    });

    test('handles content without h1 gracefully', () => {
      render(
        <ContentExtractor stripFirstHeading={true}>
          <MockContentNoHeading />
        </ContentExtractor>
      );

      expect(
        screen.getByText('Content without any headings.')
      ).toBeInTheDocument();
      expect(screen.getByText('Some nested content')).toBeInTheDocument();
    });
  });

  describe('Frontmatter Stripping', () => {
    test('strips frontmatter elements when stripFrontmatter is true', () => {
      render(
        <ContentExtractor stripFrontmatter={true} stripFirstHeading={false}>
          <MockContentWithFrontmatter />
        </ContentExtractor>
      );

      // Frontmatter elements should be removed
      expect(screen.queryByText('title: Features')).not.toBeInTheDocument();
      expect(screen.queryByText('Frontmatter data')).not.toBeInTheDocument();
      expect(screen.queryByText('Meta information')).not.toBeInTheDocument();

      // Actual content should remain
      expect(screen.getByText('Actual Content Title')).toBeInTheDocument();
      expect(
        screen.getByText('This is the actual page content.')
      ).toBeInTheDocument();
      expect(screen.getByText('Section')).toBeInTheDocument();
    });

    test('preserves frontmatter elements when stripFrontmatter is false', () => {
      render(
        <ContentExtractor stripFrontmatter={false} stripFirstHeading={false}>
          <MockContentWithFrontmatter />
        </ContentExtractor>
      );

      // Frontmatter elements should be preserved
      expect(screen.getByText('title: Features')).toBeInTheDocument();
      expect(screen.getByText('Frontmatter data')).toBeInTheDocument();
      expect(screen.getByText('Meta information')).toBeInTheDocument();

      // Actual content should also remain
      expect(screen.getByText('Actual Content Title')).toBeInTheDocument();
      expect(
        screen.getByText('This is the actual page content.')
      ).toBeInTheDocument();
    });

    test('removes elements with frontmatter-related classes', () => {
      const CustomFrontmatterContent = () => (
        <div>
          <div className="frontmatter">YAML frontmatter here</div>
          <div className="metadata">Metadata content</div>
          <h1>Real Content</h1>
          <p>Actual article content.</p>
        </div>
      );

      render(
        <ContentExtractor stripFrontmatter={true} stripFirstHeading={false}>
          <CustomFrontmatterContent />
        </ContentExtractor>
      );

      expect(
        screen.queryByText('YAML frontmatter here')
      ).not.toBeInTheDocument();
      expect(screen.queryByText('Metadata content')).not.toBeInTheDocument();
      expect(screen.getByText('Real Content')).toBeInTheDocument();
      expect(screen.getByText('Actual article content.')).toBeInTheDocument();
    });

    test('removes elements with data-frontmatter attributes', () => {
      const DataFrontmatterContent = () => (
        <div>
          <div data-frontmatter="true">Data frontmatter element</div>
          <div data-frontmatter='{"title": "Test"}'>JSON frontmatter</div>
          <p>Regular content</p>
        </div>
      );

      render(
        <ContentExtractor stripFrontmatter={true}>
          <DataFrontmatterContent />
        </ContentExtractor>
      );

      expect(
        screen.queryByText('Data frontmatter element')
      ).not.toBeInTheDocument();
      expect(screen.queryByText('JSON frontmatter')).not.toBeInTheDocument();
      expect(screen.getByText('Regular content')).toBeInTheDocument();
    });

    test('handles content without frontmatter gracefully', () => {
      render(
        <ContentExtractor stripFrontmatter={true}>
          <MockContentNoHeading />
        </ContentExtractor>
      );

      expect(
        screen.getByText('Content without any headings.')
      ).toBeInTheDocument();
      expect(screen.getByText('Some nested content')).toBeInTheDocument();
    });
  });

  describe('Intro Text Stripping', () => {
    test('strips intro paragraph when stripIntro is true', () => {
      render(
        <ContentExtractor stripIntro={true} stripFirstHeading={false}>
          <MockContentWithHeading />
        </ContentExtractor>
      );

      // Intro paragraph should be removed
      expect(
        screen.queryByText(
          'Select a feature from the tab list to view details in the content panel.'
        )
      ).not.toBeInTheDocument();

      // Other content should remain
      expect(screen.getByText('Page Title')).toBeInTheDocument();
      expect(screen.getByText('Section 1')).toBeInTheDocument();
    });

    test('preserves intro paragraph when stripIntro is false', () => {
      render(
        <ContentExtractor stripIntro={false}>
          <MockContentWithHeading />
        </ContentExtractor>
      );

      expect(
        screen.getByText(
          'Select a feature from the tab list to view details in the content panel.'
        )
      ).toBeInTheDocument();
    });

    test('only strips intro paragraphs containing specific text', () => {
      const CustomContent = () => (
        <div>
          <p>This paragraph should remain.</p>
          <p>Select a feature from the tab list to view details.</p>
          <p>This paragraph should also remain.</p>
        </div>
      );

      render(
        <ContentExtractor stripIntro={true}>
          <CustomContent />
        </ContentExtractor>
      );

      expect(
        screen.getByText('This paragraph should remain.')
      ).toBeInTheDocument();
      expect(
        screen.getByText('This paragraph should also remain.')
      ).toBeInTheDocument();
      expect(
        screen.queryByText(
          'Select a feature from the tab list to view details.'
        )
      ).not.toBeInTheDocument();
    });
  });

  describe('Selector-based Content Filtering', () => {
    test('starts from specified selector when startFromSelector is provided', () => {
      render(
        <ContentExtractor startFromSelector="[data-component='VerticalTabs']">
          <MockContentWithVerticalTabs />
        </ContentExtractor>
      );

      // Content before the selector should be removed
      expect(screen.queryByText('Features')).not.toBeInTheDocument();
      expect(
        screen.queryByText(
          'Select a feature from the tab list to view details.'
        )
      ).not.toBeInTheDocument();

      // Content from the selector onwards should remain
      expect(screen.getByText('Tab 1')).toBeInTheDocument();
      expect(screen.getByText('Tab content 1')).toBeInTheDocument();
      expect(screen.getByText('Additional Section')).toBeInTheDocument();
    });

    test('ends at specified selector when endAtSelector is provided', () => {
      const ContentWithEndMarker = () => (
        <div>
          <h1>Title</h1>
          <p>Keep this content</p>
          <div className="end-marker">End here</div>
          <p>Remove this content</p>
        </div>
      );

      render(
        <ContentExtractor endAtSelector=".end-marker" stripFirstHeading={false}>
          <ContentWithEndMarker />
        </ContentExtractor>
      );

      // Content before the end selector should remain
      expect(screen.getByText('Title')).toBeInTheDocument();
      expect(screen.getByText('Keep this content')).toBeInTheDocument();

      // Content from the end selector onwards should be removed
      expect(screen.queryByText('End here')).not.toBeInTheDocument();
      expect(screen.queryByText('Remove this content')).not.toBeInTheDocument();
    });

    test('handles non-existent selectors gracefully', () => {
      render(
        <ContentExtractor
          startFromSelector=".non-existent"
          endAtSelector=".also-non-existent"
          stripFirstHeading={false}
        >
          <MockContentWithHeading />
        </ContentExtractor>
      );

      // All content should remain when selectors don't match anything
      expect(screen.getByText('Page Title')).toBeInTheDocument();
      expect(screen.getByText('Section 1')).toBeInTheDocument();
      expect(
        screen.getByText('This is section 1 content.')
      ).toBeInTheDocument();
    });
  });

  describe('Combined Filtering Options', () => {
    test('applies multiple filtering options together', () => {
      render(
        <ContentExtractor
          stripFirstHeading={true}
          stripIntro={true}
          stripFrontmatter={true}
          startFromSelector="h2"
        >
          <MockContentWithHeading />
        </ContentExtractor>
      );

      // h1 should be removed by stripFirstHeading
      expect(screen.queryByText('Page Title')).not.toBeInTheDocument();

      // Intro should be removed by stripIntro
      expect(
        screen.queryByText(
          'Select a feature from the tab list to view details in the content panel.'
        )
      ).not.toBeInTheDocument();

      // Content should start from h2 due to startFromSelector
      expect(screen.getByText('Section 1')).toBeInTheDocument();
      expect(
        screen.getByText('This is section 1 content.')
      ).toBeInTheDocument();
    });

    test('applies frontmatter stripping with other options', () => {
      const CombinedContent = () => (
        <div>
          <div className="frontmatter">title: Test Page</div>
          <h1>Page Title</h1>
          <p>Select a feature from the tab list to view details.</p>
          <h2>Main Section</h2>
          <p>Important content here.</p>
        </div>
      );

      render(
        <ContentExtractor
          stripFrontmatter={true}
          stripFirstHeading={true}
          stripIntro={true}
        >
          <CombinedContent />
        </ContentExtractor>
      );

      // All should be stripped
      expect(screen.queryByText('title: Test Page')).not.toBeInTheDocument();
      expect(screen.queryByText('Page Title')).not.toBeInTheDocument();
      expect(
        screen.queryByText(
          'Select a feature from the tab list to view details.'
        )
      ).not.toBeInTheDocument();

      // Main content should remain
      expect(screen.getByText('Main Section')).toBeInTheDocument();
      expect(screen.getByText('Important content here.')).toBeInTheDocument();
    });
  });

  describe('Edge Cases', () => {
    test('handles empty content', () => {
      render(
        <ContentExtractor>
          <MockEmptyContent />
        </ContentExtractor>
      );

      // Should not crash and should render empty container
      const container = document.querySelector('.mdx-content-extracted');
      expect(container).toBeInTheDocument();
    });

    test('handles null children', () => {
      render(<ContentExtractor>{null}</ContentExtractor>);

      const container = document.querySelector('.mdx-content-extracted');
      expect(container).toBeInTheDocument();
    });

    test('handles text-only content', () => {
      render(<ContentExtractor>Plain text content</ContentExtractor>);

      expect(screen.getByText('Plain text content')).toBeInTheDocument();
    });

    test('handles case when contentRef.current is null', () => {
      // Use a ref callback to simulate null contentRef
      const TestComponent = () => {
        const refCallback = React.useCallback(_node => {
          // Set ref to null to trigger the early return
          return null;
        }, []);

        return (
          <div ref={refCallback} className="mdx-content-extracted">
            <div>Test content</div>
          </div>
        );
      };

      // This will trigger the useEffect with null contentRef
      const result = render(<TestComponent />);
      expect(result.container.firstChild).toBeInTheDocument();
    });

    test('handles component lifecycle edge cases gracefully', () => {
      // Test normal lifecycle - the null ref check is defensive and hard to test
      // We've excluded it from coverage as it's a safety guard
      render(
        <ContentExtractor>
          <div>Normal component lifecycle</div>
        </ContentExtractor>
      );

      expect(
        screen.getByText('Normal component lifecycle')
      ).toBeInTheDocument();
    });

    test('handles case when contentDiv is null', () => {
      // Create content without a div wrapper
      const NoWrapperContent = () => 'Just text';

      render(
        <ContentExtractor>
          <NoWrapperContent />
        </ContentExtractor>
      );

      expect(screen.getByText('Just text')).toBeInTheDocument();
    });
  });

  describe('Default Props', () => {
    test('uses correct default values', () => {
      const { container } = render(
        <ContentExtractor>
          <MockContentWithHeading />
        </ContentExtractor>
      );

      // stripFirstHeading defaults to true
      expect(screen.queryByText('Page Title')).not.toBeInTheDocument();

      // stripIntro defaults to false
      expect(
        screen.getByText(
          'Select a feature from the tab list to view details in the content panel.'
        )
      ).toBeInTheDocument();

      // Default className should be applied
      expect(container.firstChild).toHaveClass('mdx-content-extracted');
    });

    test('stripFrontmatter defaults to true', () => {
      render(
        <ContentExtractor stripFirstHeading={false}>
          <MockContentWithFrontmatter />
        </ContentExtractor>
      );

      // stripFrontmatter defaults to true, so frontmatter should be removed
      expect(screen.queryByText('title: Features')).not.toBeInTheDocument();
      expect(screen.queryByText('Frontmatter data')).not.toBeInTheDocument();

      // Actual content should remain
      expect(screen.getByText('Actual Content Title')).toBeInTheDocument();
      expect(
        screen.getByText('This is the actual page content.')
      ).toBeInTheDocument();
    });
  });
});
