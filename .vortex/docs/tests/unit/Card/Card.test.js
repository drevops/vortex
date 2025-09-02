import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import Card from '../../../src/components/Card/Card';

describe('Card Component', () => {
  describe('Basic Rendering', () => {
    test('renders card with all props', () => {
      render(
        <Card
          icon="🚀"
          title="Test Card"
          description="This is a test card description"
          link="/test-link"
          className="custom-class"
        />
      );

      expect(screen.getByText('🚀')).toBeInTheDocument();
      expect(screen.getByText('Test Card')).toBeInTheDocument();
      expect(
        screen.getByText('This is a test card description')
      ).toBeInTheDocument();

      const linkElement = screen.getByRole('link');
      expect(linkElement).toHaveAttribute('href', '/test-link');
      expect(linkElement).toHaveClass('custom-class');
    });

    test('renders card without link as div', () => {
      render(
        <Card
          icon="💡"
          title="Non-linked Card"
          description="This card has no link"
        />
      );

      expect(screen.getByText('💡')).toBeInTheDocument();
      expect(screen.getByText('Non-linked Card')).toBeInTheDocument();
      expect(screen.getByText('This card has no link')).toBeInTheDocument();

      // Should not be a link
      expect(screen.queryByRole('link')).not.toBeInTheDocument();

      // Should be a div with card class
      const cardElement = screen.getByText('Non-linked Card').closest('div');
      expect(cardElement).toBeInTheDocument();
    });

    test('renders card without icon', () => {
      render(<Card title="No Icon Card" description="This card has no icon" />);

      expect(screen.getByText('No Icon Card')).toBeInTheDocument();
      expect(screen.getByText('This card has no icon')).toBeInTheDocument();

      // Icon span should not be present
      const cardElement = screen.getByText('No Icon Card').closest('div');
      const iconElement = cardElement.querySelector('.card-icon');
      expect(iconElement).not.toBeInTheDocument();
    });

    test('handles empty icon gracefully', () => {
      render(
        <Card
          icon=""
          title="Empty Icon Card"
          description="This card has empty icon"
        />
      );

      expect(screen.getByText('Empty Icon Card')).toBeInTheDocument();

      // Icon span should not be present when icon is empty
      const cardElement = screen.getByText('Empty Icon Card').closest('div');
      const iconElement = cardElement.querySelector('.card-icon');
      expect(iconElement).not.toBeInTheDocument();
    });

    test('applies correct CSS classes', () => {
      const { container } = render(
        <Card
          icon="🎯"
          title="CSS Test Card"
          description="Testing CSS classes"
          className="additional-class"
        />
      );

      const cardElement = container.querySelector('.card');
      expect(cardElement).toHaveClass('card');
      expect(cardElement).toHaveClass('additional-class');

      const iconElement = container.querySelector('.card-icon');
      expect(iconElement).toBeInTheDocument();

      const titleElement = container.querySelector('.card-title');
      expect(titleElement).toBeInTheDocument();

      const descriptionElement = container.querySelector('.card-description');
      expect(descriptionElement).toBeInTheDocument();
    });
  });

  describe('Link Behavior', () => {
    test('creates anchor element when link prop is provided', () => {
      render(
        <Card
          icon="🔗"
          title="Linked Card"
          description="This card is linked"
          link="/external-link"
        />
      );

      const linkElement = screen.getByRole('link');
      expect(linkElement).toHaveAttribute('href', '/external-link');
      expect(linkElement.tagName).toBe('A');
    });

    test('handles various link formats', () => {
      const { rerender } = render(
        <Card
          title="External Link"
          description="External link test"
          link="https://example.com"
        />
      );

      expect(screen.getByRole('link')).toHaveAttribute(
        'href',
        'https://example.com'
      );

      rerender(
        <Card
          title="Internal Link"
          description="Internal link test"
          link="/internal/path"
        />
      );

      expect(screen.getByRole('link')).toHaveAttribute(
        'href',
        '/internal/path'
      );

      rerender(
        <Card title="Hash Link" description="Hash link test" link="#section" />
      );

      expect(screen.getByRole('link')).toHaveAttribute('href', '#section');
    });

    test('applies card-link class to linked cards', () => {
      const { container } = render(
        <Card
          title="Link Class Test"
          description="Testing link class"
          link="/test"
        />
      );

      const linkElement = container.querySelector('.card-link');
      expect(linkElement).toBeInTheDocument();
      expect(linkElement.tagName).toBe('A');
    });
  });

  describe('Content Handling', () => {
    test('handles special characters in content', () => {
      render(
        <Card
          icon="🌟"
          title="Special Characters & Symbols!"
          description="Content with special chars: © ® ™ & more <em>HTML</em>"
        />
      );

      expect(
        screen.getByText('Special Characters & Symbols!')
      ).toBeInTheDocument();
      expect(
        screen.getByText('Content with special chars: © ® ™ & more', {
          exact: false,
        })
      ).toBeInTheDocument();
      expect(screen.getByText('HTML')).toBeInTheDocument();
    });

    test('handles very long content', () => {
      const longTitle =
        'This is a very long title that might wrap to multiple lines and test how the card handles overflow content';
      const longDescription =
        'This is an extremely long description that contains a lot of text and should test how the card component handles very lengthy content that might need to wrap to multiple lines within the card layout structure.';

      render(
        <Card icon="📝" title={longTitle} description={longDescription} />
      );

      expect(screen.getByText(longTitle)).toBeInTheDocument();
      expect(screen.getByText(longDescription)).toBeInTheDocument();
    });

    test('handles numeric content', () => {
      render(<Card icon="🔢" title={42} description="Numeric title test" />);

      expect(screen.getByText('42')).toBeInTheDocument();
      expect(screen.getByText('Numeric title test')).toBeInTheDocument();
    });
  });

  describe('Props Validation', () => {
    test('handles missing required props gracefully', () => {
      // Should not crash without title or description
      render(<Card />);

      const cardElement = document.querySelector('.card');
      expect(cardElement).toBeInTheDocument();
    });

    test('handles undefined props', () => {
      render(
        <Card
          icon={undefined}
          title={undefined}
          description={undefined}
          link={undefined}
          variant={undefined}
          className={undefined}
        />
      );

      const cardElement = document.querySelector('.card');
      expect(cardElement).toBeInTheDocument();
    });

    test('handles null props', () => {
      render(
        <Card
          icon={null}
          title={null}
          description={null}
          link={null}
          variant={null}
          className={null}
        />
      );

      const cardElement = document.querySelector('.card');
      expect(cardElement).toBeInTheDocument();
    });
  });

  describe('Accessibility', () => {
    test('maintains proper heading structure', () => {
      render(
        <Card
          icon="♿"
          title="Accessible Card"
          description="Accessibility test card"
        />
      );

      const heading = screen.getByRole('heading', { level: 3 });
      expect(heading).toHaveTextContent('Accessible Card');
    });

    test('linked cards are keyboard accessible', () => {
      render(
        <Card
          title="Keyboard Test"
          description="Keyboard accessibility test"
          link="/keyboard-test"
        />
      );

      const linkElement = screen.getByRole('link');
      expect(linkElement).toHaveAttribute('href', '/keyboard-test');

      // Should be focusable
      linkElement.focus();
      expect(document.activeElement).toBe(linkElement);
    });

    test('non-linked cards are not focusable', () => {
      render(
        <Card
          title="Non-focusable Test"
          description="Non-focusable card test"
        />
      );

      const cardElement = screen.getByText('Non-focusable Test').closest('div');
      expect(cardElement).not.toHaveAttribute('tabindex');
    });

    test('provides meaningful content structure', () => {
      render(
        <Card
          icon="🎯"
          title="Structure Test"
          description="Testing content structure"
          link="/structure"
        />
      );

      const linkElement = screen.getByRole('link');
      const heading = screen.getByRole('heading', { level: 3 });

      // Heading should be inside the link
      expect(linkElement).toContainElement(heading);
      expect(linkElement).toHaveTextContent('Structure Test');
      expect(linkElement).toHaveTextContent('Testing content structure');
    });
  });

  describe('CSS Module Classes', () => {
    test('uses kebab-case CSS classes correctly', () => {
      const { container } = render(
        <Card
          icon="🎨"
          title="CSS Classes Test"
          description="Testing CSS module classes"
          link="/css-test"
        />
      );

      // Check that kebab-case classes are applied
      expect(container.querySelector('.card')).toBeInTheDocument();
      expect(container.querySelector('.card-icon')).toBeInTheDocument();
      expect(container.querySelector('.card-title')).toBeInTheDocument();
      expect(container.querySelector('.card-description')).toBeInTheDocument();
      expect(container.querySelector('.card-link')).toBeInTheDocument();
    });
  });

  describe('Event Handling', () => {
    test('link cards can be clicked', () => {
      render(
        <Card
          title="Clickable Card"
          description="This card should be clickable"
          link="/click-test"
        />
      );

      const linkElement = screen.getByRole('link');

      // Should be able to trigger click events
      fireEvent.click(linkElement);

      // Link should still have correct href after click
      expect(linkElement).toHaveAttribute('href', '/click-test');
    });

    test('non-linked cards do not interfere with click events', () => {
      render(
        <Card
          title="Non-clickable Card"
          description="This card is not clickable"
        />
      );

      const cardElement = screen.getByText('Non-clickable Card').closest('div');

      // Should not have click handlers
      expect(cardElement.onclick).toBeFalsy();
    });
  });

  describe('Edge Cases', () => {
    test('handles React fragments and complex children in props', () => {
      render(
        <Card
          icon={<span>🧩</span>}
          title={
            <>
              Fragment <em>Title</em>
            </>
          }
          description="Complex <strong>description</strong> with <code>code elements</code>"
        />
      );

      expect(screen.getByText('🧩')).toBeInTheDocument();
      expect(screen.getByText(/Fragment/)).toBeInTheDocument();
      expect(screen.getByText('Title')).toBeInTheDocument();
      expect(screen.getByText('Complex', { exact: false })).toBeInTheDocument();
      expect(screen.getByText('description')).toBeInTheDocument();
      expect(screen.getByText('code elements')).toBeInTheDocument();
    });

    test('handles empty string values', () => {
      render(<Card icon="" title="" description="" link="" className="" />);

      const cardElement = document.querySelector('.card');
      expect(cardElement).toBeInTheDocument();
    });

    test('maintains component integrity with changing props', () => {
      const { rerender } = render(
        <Card
          icon="🔄"
          title="Initial Title"
          description="Initial description"
        />
      );

      expect(screen.getByText('Initial Title')).toBeInTheDocument();

      rerender(
        <Card
          icon="🆕"
          title="Updated Title"
          description="Updated description"
          link="/new-link"
        />
      );

      expect(screen.getByText('Updated Title')).toBeInTheDocument();
      expect(screen.getByText('Updated description')).toBeInTheDocument();
      expect(screen.getByRole('link')).toHaveAttribute('href', '/new-link');
      expect(screen.queryByText('Initial Title')).not.toBeInTheDocument();
    });
  });
});
