import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { Card, CardGrid } from '../../../src/components/Card';

describe('Card and CardGrid Integration', () => {
  describe('Export Verification', () => {
    test('exports Card and CardGrid components correctly', () => {
      expect(Card).toBeDefined();
      expect(CardGrid).toBeDefined();
      expect(typeof Card).toBe('function');
      expect(typeof CardGrid).toBe('function');
    });
  });

  describe('Component Integration', () => {
    test('renders CardGrid with multiple Card components', () => {
      render(
        <CardGrid>
          <Card
            icon="🚀"
            title="Feature 1"
            description="First feature description"
            link="/feature-1"
          />
          <Card
            icon="💡"
            title="Feature 2"
            description="Second feature description"
            link="/feature-2"
          />
          <Card
            icon="🎯"
            title="Feature 3"
            description="Third feature description"
          />
        </CardGrid>
      );

      // All cards should be rendered
      expect(screen.getByText('Feature 1')).toBeInTheDocument();
      expect(screen.getByText('Feature 2')).toBeInTheDocument();
      expect(screen.getByText('Feature 3')).toBeInTheDocument();

      // Check descriptions
      expect(screen.getByText('First feature description')).toBeInTheDocument();
      expect(
        screen.getByText('Second feature description')
      ).toBeInTheDocument();
      expect(screen.getByText('Third feature description')).toBeInTheDocument();

      // Check linked cards
      const links = screen.getAllByRole('link');
      expect(links).toHaveLength(2);
      expect(links[0]).toHaveAttribute('href', '/feature-1');
      expect(links[1]).toHaveAttribute('href', '/feature-2');
    });

    test('maintains grid layout with different card configurations', () => {
      const { container } = render(
        <CardGrid className="test-grid">
          <Card
            icon="🔧"
            title="Tool Card"
            description="A card representing tools"
            link="/tools"
          />
          <Card title="No Icon Card" description="This card has no icon" />
          <Card
            icon="🎨"
            title="Design Card"
            description="A card about design with a very long description that might wrap to multiple lines and test the layout"
            link="/design"
          />
        </CardGrid>
      );

      // Check grid structure
      const gridElement = container.querySelector('.cards-grid.test-grid');
      expect(gridElement).toBeInTheDocument();

      // Check all cards are rendered correctly
      expect(screen.getByText('Tool Card')).toBeInTheDocument();
      expect(screen.getByText('No Icon Card')).toBeInTheDocument();
      expect(screen.getByText('Design Card')).toBeInTheDocument();

      // Check icons
      expect(screen.getByText('🔧')).toBeInTheDocument();
      expect(screen.getByText('🎨')).toBeInTheDocument();

      // Check links
      const toolLink = screen.getByText('Tool Card').closest('a');
      const designLink = screen.getByText('Design Card').closest('a');
      expect(toolLink).toHaveAttribute('href', '/tools');
      expect(designLink).toHaveAttribute('href', '/design');

      // No icon card should not be a link
      const noIconCard = screen.getByText('No Icon Card').closest('div');
      expect(noIconCard.tagName).toBe('DIV');
    });
  });

  describe('Real-world Use Cases', () => {
    test('renders feature cards as used in homepage', () => {
      render(
        <CardGrid>
          <Card
            icon="💧"
            title="Drupal"
            description="Pre-configured Composer project with modern Drupal 11, optimized settings, module & theme scaffolds, and admin modules."
            link="/features#drupal-foundation"
          />
          <Card
            icon="☁️"
            title="Hosting"
            description="Integrations with cloud hosting providers including Acquia and Lagoon with database syncing, deployment workflows, and preview environments."
            link="/features#hosting-integrations"
          />
          <Card
            icon="🏗️"
            title="Continuous Integration"
            description="Continuous integration pipelines with multi-provider CI/CD using GitHub Actions and CircleCI, containerized environments, and automated testing."
            link="/features#continuous-integration"
          />
        </CardGrid>
      );

      // Check all feature cards are present
      expect(screen.getByText('Drupal')).toBeInTheDocument();
      expect(screen.getByText('Hosting')).toBeInTheDocument();
      expect(screen.getByText('Continuous Integration')).toBeInTheDocument();

      // Check all are linked
      const links = screen.getAllByRole('link');
      expect(links).toHaveLength(3);
      expect(links[0]).toHaveAttribute('href', '/features#drupal-foundation');
      expect(links[1]).toHaveAttribute(
        'href',
        '/features#hosting-integrations'
      );
      expect(links[2]).toHaveAttribute(
        'href',
        '/features#continuous-integration'
      );
    });

    test('renders benefit cards as used in homepage', () => {
      render(
        <CardGrid>
          <Card
            icon="🚀"
            title="Production-Ready from Day One"
            description="Eliminate weeks of setup time. Get a battle-tested foundation that's been refined through real-world usage across education, government, commercial, and healthcare sectors."
            variant="benefit"
          />
          <Card
            icon="🎯"
            title="Built for Teams"
            description="Consistent developer experience across every project. Same tools, commands, and workflows so team members can jump between projects without missing a beat."
            variant="benefit"
          />
          <Card
            icon="🔄"
            title="Continuously Tested"
            description="Automatically tested across different scenarios and environments to ensure seamless upgrades and reliable compatibility."
            variant="benefit"
          />
        </CardGrid>
      );

      // Check all benefit cards are present
      expect(
        screen.getByText('Production-Ready from Day One')
      ).toBeInTheDocument();
      expect(screen.getByText('Built for Teams')).toBeInTheDocument();
      expect(screen.getByText('Continuously Tested')).toBeInTheDocument();

      // These cards should not be linked (no href attributes)
      expect(screen.queryByRole('link')).not.toBeInTheDocument();
    });
  });

  describe('Interactive Behavior', () => {
    test('linked cards in grid are clickable', () => {
      render(
        <CardGrid>
          <Card
            icon="🔗"
            title="Clickable Card 1"
            description="First clickable card"
            link="/click-1"
          />
          <Card
            icon="🎯"
            title="Non-clickable Card"
            description="This card is not linked"
          />
          <Card
            icon="🚀"
            title="Clickable Card 2"
            description="Second clickable card"
            link="/click-2"
          />
        </CardGrid>
      );

      const links = screen.getAllByRole('link');
      expect(links).toHaveLength(2);

      // Test clicking first card
      fireEvent.click(links[0]);
      expect(links[0]).toHaveAttribute('href', '/click-1');

      // Test clicking second card
      fireEvent.click(links[1]);
      expect(links[1]).toHaveAttribute('href', '/click-2');

      // Non-clickable card should not be a link
      const nonClickableCard = screen
        .getByText('Non-clickable Card')
        .closest('div');
      expect(nonClickableCard.tagName).toBe('DIV');
    });
  });

  describe('Responsive Layout Behavior', () => {
    test('grid maintains structure with varying content lengths', () => {
      render(
        <CardGrid>
          <Card icon="📝" title="Short" description="Brief" />
          <Card
            icon="📚"
            title="This is a Much Longer Title That Will Definitely Wrap to Multiple Lines in Most Responsive Layouts"
            description="This card has an extremely long description that contains a significant amount of text content to test how the grid layout handles cards with varying amounts of content. It should maintain proper spacing and alignment even when some cards are much taller than others due to content overflow and text wrapping behavior in different viewport sizes."
          />
          <Card
            icon="🎯"
            title="Medium Length Title"
            description="A moderately sized description that falls between the short and very long examples."
          />
          <Card
            icon="⚡"
            title="Another Card"
            description="Standard description length for testing purposes."
          />
        </CardGrid>
      );

      // All cards should be present regardless of content length
      expect(screen.getByText('Short')).toBeInTheDocument();
      expect(
        screen.getByText(
          'This is a Much Longer Title That Will Definitely Wrap to Multiple Lines in Most Responsive Layouts'
        )
      ).toBeInTheDocument();
      expect(screen.getByText('Medium Length Title')).toBeInTheDocument();
      expect(screen.getByText('Another Card')).toBeInTheDocument();
    });
  });

  describe('Accessibility Integration', () => {
    test('maintains accessibility across card grid', () => {
      render(
        <CardGrid>
          <Card
            icon="♿"
            title="Accessible Card 1"
            description="First accessible card with link"
            link="/accessible-1"
          />
          <Card
            icon="🔍"
            title="Accessible Card 2"
            description="Second accessible card without link"
          />
          <Card
            icon="🎯"
            title="Accessible Card 3"
            description="Third accessible card with link"
            link="/accessible-3"
          />
        </CardGrid>
      );

      // Check heading structure is maintained
      const headings = screen.getAllByRole('heading', { level: 3 });
      expect(headings).toHaveLength(3);
      expect(headings[0]).toHaveTextContent('Accessible Card 1');
      expect(headings[1]).toHaveTextContent('Accessible Card 2');
      expect(headings[2]).toHaveTextContent('Accessible Card 3');

      // Check linked cards are accessible
      const links = screen.getAllByRole('link');
      expect(links).toHaveLength(2);
      expect(links[0]).toHaveAttribute('href', '/accessible-1');
      expect(links[1]).toHaveAttribute('href', '/accessible-3');

      // Test keyboard navigation
      links[0].focus();
      expect(document.activeElement).toBe(links[0]);
    });
  });

  describe('Error Handling and Edge Cases', () => {
    test('handles mixed valid and invalid children gracefully', () => {
      render(
        <CardGrid>
          {null}
          <Card
            icon="✅"
            title="Valid Card 1"
            description="This card should render"
          />
          {undefined}
          <div>Non-card content</div>
          <Card
            icon="🎯"
            title="Valid Card 2"
            description="This card should also render"
            link="/valid-2"
          />
          {false}
          {''}
        </CardGrid>
      );

      // Valid cards should render
      expect(screen.getByText('Valid Card 1')).toBeInTheDocument();
      expect(screen.getByText('Valid Card 2')).toBeInTheDocument();
      expect(screen.getByText('Non-card content')).toBeInTheDocument();

      // Check link functionality
      const link = screen.getByRole('link');
      expect(link).toHaveAttribute('href', '/valid-2');
    });

    test('handles dynamic updates correctly', () => {
      const { rerender } = render(
        <CardGrid>
          <Card icon="🔄" title="Initial Card" description="Initial state" />
        </CardGrid>
      );

      expect(screen.getByText('Initial Card')).toBeInTheDocument();

      // Update with new cards
      rerender(
        <CardGrid>
          <Card
            icon="🆕"
            title="Updated Card 1"
            description="New state"
            link="/updated-1"
          />
          <Card
            icon="➕"
            title="Updated Card 2"
            description="Additional card"
            link="/updated-2"
          />
        </CardGrid>
      );

      // Old card should be gone
      expect(screen.queryByText('Initial Card')).not.toBeInTheDocument();

      // New cards should be present
      expect(screen.getByText('Updated Card 1')).toBeInTheDocument();
      expect(screen.getByText('Updated Card 2')).toBeInTheDocument();

      // Check links work
      const links = screen.getAllByRole('link');
      expect(links).toHaveLength(2);
      expect(links[0]).toHaveAttribute('href', '/updated-1');
      expect(links[1]).toHaveAttribute('href', '/updated-2');
    });
  });

  describe('CSS Integration', () => {
    test('applies CSS classes correctly in integrated layout', () => {
      const { container } = render(
        <CardGrid className="feature-grid">
          <Card
            icon="🎨"
            title="Styled Card 1"
            description="Testing CSS integration"
            className="custom-card-class"
          />
          <Card
            icon="🖌️"
            title="Styled Card 2"
            description="Another styled card"
            link="/styled-2"
            className="another-card-class"
          />
        </CardGrid>
      );

      // Check grid classes
      const gridElement = container.querySelector('.cards-grid.feature-grid');
      expect(gridElement).toBeInTheDocument();

      // Check card classes
      const card1 = container.querySelector('.card.custom-card-class');
      const card2 = container.querySelector(
        '.card.another-card-class.card-link'
      );
      expect(card1).toBeInTheDocument();
      expect(card2).toBeInTheDocument();

      // Check kebab-case classes are applied
      expect(container.querySelector('.card-icon')).toBeInTheDocument();
      expect(container.querySelector('.card-title')).toBeInTheDocument();
      expect(container.querySelector('.card-description')).toBeInTheDocument();
      expect(container.querySelector('.card-link')).toBeInTheDocument();
    });
  });
});
