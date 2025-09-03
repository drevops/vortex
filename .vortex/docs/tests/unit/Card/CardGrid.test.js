import React from 'react';
import { render, screen } from '@testing-library/react';
import CardGrid from '../../../src/components/Card/CardGrid';
import Card from '../../../src/components/Card/Card';

describe('CardGrid Component', () => {
  describe('Basic Rendering', () => {
    test('renders empty CardGrid', () => {
      const { container } = render(<CardGrid />);

      const gridElement = container.querySelector('.cards-grid');
      expect(gridElement).toBeInTheDocument();
      expect(gridElement.children).toHaveLength(0);
    });

    test('renders CardGrid with children', () => {
      render(
        <CardGrid>
          <div>Child 1</div>
          <div>Child 2</div>
          <div>Child 3</div>
        </CardGrid>
      );

      expect(screen.getByText('Child 1')).toBeInTheDocument();
      expect(screen.getByText('Child 2')).toBeInTheDocument();
      expect(screen.getByText('Child 3')).toBeInTheDocument();
    });

    test('applies custom className', () => {
      const { container } = render(
        <CardGrid className="custom-grid-class">
          <div>Test child</div>
        </CardGrid>
      );

      const gridElement = container.querySelector('.cards-grid');
      expect(gridElement).toHaveClass('cards-grid');
      expect(gridElement).toHaveClass('custom-grid-class');
    });

    test('handles multiple custom classes', () => {
      const { container } = render(
        <CardGrid className="class-one class-two class-three">
          <div>Test child</div>
        </CardGrid>
      );

      const gridElement = container.querySelector('.cards-grid');
      expect(gridElement).toHaveClass('cards-grid');
      expect(gridElement).toHaveClass('class-one');
      expect(gridElement).toHaveClass('class-two');
      expect(gridElement).toHaveClass('class-three');
    });
  });

  describe('Card Integration', () => {
    test('renders multiple Card components correctly', () => {
      render(
        <CardGrid>
          <Card icon="🚀" title="Card 1" description="First card description" />
          <Card
            icon="💡"
            title="Card 2"
            description="Second card description"
            link="/card-2"
          />
          <Card icon="🎯" title="Card 3" description="Third card description" />
        </CardGrid>
      );

      expect(screen.getByText('Card 1')).toBeInTheDocument();
      expect(screen.getByText('Card 2')).toBeInTheDocument();
      expect(screen.getByText('Card 3')).toBeInTheDocument();
      expect(screen.getByText('First card description')).toBeInTheDocument();
      expect(screen.getByText('Second card description')).toBeInTheDocument();
      expect(screen.getByText('Third card description')).toBeInTheDocument();

      // Check that linked card works
      const linkedCard = screen.getByRole('link');
      expect(linkedCard).toHaveAttribute('href', '/card-2');
    });

    test('handles mixed children types', () => {
      render(
        <CardGrid>
          <Card
            icon="🔧"
            title="Card Component"
            description="This is a Card component"
          />
          <div className="custom-card">Custom div card</div>
          <p>Regular paragraph</p>
          <Card
            icon="⚙️"
            title="Another Card"
            description="Another Card component"
            link="/another"
          />
        </CardGrid>
      );

      expect(screen.getByText('Card Component')).toBeInTheDocument();
      expect(screen.getByText('Custom div card')).toBeInTheDocument();
      expect(screen.getByText('Regular paragraph')).toBeInTheDocument();
      expect(screen.getByText('Another Card')).toBeInTheDocument();
    });

    test('maintains grid layout with different content sizes', () => {
      const { container } = render(
        <CardGrid>
          <Card icon="📝" title="Short" description="Short description" />
          <Card
            icon="📚"
            title="Very Long Title That Might Wrap To Multiple Lines"
            description="This is a much longer description that contains significantly more text content to test how the grid layout handles cards with varying amounts of content and different heights."
          />
          <Card
            icon="🎨"
            title="Medium Title"
            description="Medium length description that has more content than the short one but less than the very long one."
          />
        </CardGrid>
      );

      const gridElement = container.querySelector('.cards-grid');
      expect(gridElement).toBeInTheDocument();

      // All cards should be present
      expect(screen.getByText('Short')).toBeInTheDocument();
      expect(
        screen.getByText('Very Long Title That Might Wrap To Multiple Lines')
      ).toBeInTheDocument();
      expect(screen.getByText('Medium Title')).toBeInTheDocument();
    });
  });

  describe('CSS Grid Layout', () => {
    test('applies correct CSS class for grid layout', () => {
      const { container } = render(
        <CardGrid>
          <div>Grid item 1</div>
          <div>Grid item 2</div>
        </CardGrid>
      );

      const gridElement = container.querySelector('.cards-grid');
      expect(gridElement).toHaveClass('cards-grid');
    });

    test('maintains grid structure with many children', () => {
      const children = Array.from({ length: 12 }, (_, i) => (
        <Card
          key={i}
          icon="🎲"
          title={`Card ${i + 1}`}
          description={`Description for card ${i + 1}`}
        />
      ));

      render(<CardGrid>{children}</CardGrid>);

      // All 12 cards should be rendered
      for (let i = 1; i <= 12; i++) {
        expect(screen.getByText(`Card ${i}`)).toBeInTheDocument();
      }
    });
  });

  describe('Props Handling', () => {
    test('handles undefined className gracefully', () => {
      const { container } = render(
        <CardGrid className={undefined}>
          <div>Test child</div>
        </CardGrid>
      );

      const gridElement = container.querySelector('.cards-grid');
      expect(gridElement).toHaveClass('cards-grid');
      expect(gridElement.className.trim()).toBe('cards-grid');
    });

    test('handles null className gracefully', () => {
      const { container } = render(
        <CardGrid className={null}>
          <div>Test child</div>
        </CardGrid>
      );

      const gridElement = container.querySelector('.cards-grid');
      expect(gridElement).toHaveClass('cards-grid');
    });

    test('handles empty string className', () => {
      const { container } = render(
        <CardGrid className="">
          <div>Test child</div>
        </CardGrid>
      );

      const gridElement = container.querySelector('.cards-grid');
      expect(gridElement).toHaveClass('cards-grid');
      expect(gridElement.className).toBe('cards-grid ');
    });

    test('handles numeric and boolean className values', () => {
      const { container } = render(
        <CardGrid className={123}>
          <div>Test child</div>
        </CardGrid>
      );

      const gridElement = container.querySelector('.cards-grid');
      expect(gridElement).toHaveClass('cards-grid');
      expect(gridElement).toHaveClass('123');
    });
  });

  describe('Children Handling', () => {
    test('handles null children', () => {
      const { container } = render(<CardGrid>{null}</CardGrid>);

      const gridElement = container.querySelector('.cards-grid');
      expect(gridElement).toBeInTheDocument();
    });

    test('handles undefined children', () => {
      const { container } = render(<CardGrid>{undefined}</CardGrid>);

      const gridElement = container.querySelector('.cards-grid');
      expect(gridElement).toBeInTheDocument();
    });

    test('handles mixed valid and invalid children', () => {
      render(
        <CardGrid>
          {null}
          <Card icon="✅" title="Valid Card" description="This works" />
          {undefined}
          {false}
          <div>Valid div</div>
          {''}
        </CardGrid>
      );

      expect(screen.getByText('Valid Card')).toBeInTheDocument();
      expect(screen.getByText('Valid div')).toBeInTheDocument();
    });

    test('handles React fragments as children', () => {
      render(
        <CardGrid>
          <>
            <Card icon="🧩" title="Fragment Card 1" description="In fragment" />
            <Card
              icon="🔗"
              title="Fragment Card 2"
              description="Also in fragment"
            />
          </>
          <Card icon="🎯" title="Direct Card" description="Direct child" />
        </CardGrid>
      );

      expect(screen.getByText('Fragment Card 1')).toBeInTheDocument();
      expect(screen.getByText('Fragment Card 2')).toBeInTheDocument();
      expect(screen.getByText('Direct Card')).toBeInTheDocument();
    });

    test('handles array of children', () => {
      const cardArray = [
        <Card
          key="1"
          icon="1️⃣"
          title="Array Card 1"
          description="First array card"
        />,
        <Card
          key="2"
          icon="2️⃣"
          title="Array Card 2"
          description="Second array card"
        />,
        <Card
          key="3"
          icon="3️⃣"
          title="Array Card 3"
          description="Third array card"
        />,
      ];

      render(<CardGrid>{cardArray}</CardGrid>);

      expect(screen.getByText('Array Card 1')).toBeInTheDocument();
      expect(screen.getByText('Array Card 2')).toBeInTheDocument();
      expect(screen.getByText('Array Card 3')).toBeInTheDocument();
    });
  });

  describe('Component Integration', () => {
    test('works with conditional rendering', () => {
      const showCard2 = false;
      const showCard3 = true;

      render(
        <CardGrid>
          <Card icon="🎯" title="Always Visible" description="Always shown" />
          {showCard2 && (
            <Card icon="🔄" title="Conditional Card 2" description="Hidden" />
          )}
          {showCard3 && (
            <Card icon="✅" title="Conditional Card 3" description="Visible" />
          )}
        </CardGrid>
      );

      expect(screen.getByText('Always Visible')).toBeInTheDocument();
      expect(screen.queryByText('Conditional Card 2')).not.toBeInTheDocument();
      expect(screen.getByText('Conditional Card 3')).toBeInTheDocument();
    });

    test('handles dynamic children updates', () => {
      const { rerender } = render(
        <CardGrid>
          <Card icon="🔄" title="Initial Card" description="Initial state" />
        </CardGrid>
      );

      expect(screen.getByText('Initial Card')).toBeInTheDocument();

      rerender(
        <CardGrid>
          <Card icon="🆕" title="Updated Card" description="Updated state" />
          <Card icon="➕" title="Added Card" description="Newly added" />
        </CardGrid>
      );

      expect(screen.queryByText('Initial Card')).not.toBeInTheDocument();
      expect(screen.getByText('Updated Card')).toBeInTheDocument();
      expect(screen.getByText('Added Card')).toBeInTheDocument();
    });
  });

  describe('Accessibility', () => {
    test('maintains semantic structure', () => {
      render(
        <CardGrid>
          <Card
            icon="♿"
            title="Accessible Card 1"
            description="First accessible card"
          />
          <Card
            icon="🔍"
            title="Accessible Card 2"
            description="Second accessible card"
            link="/accessible-link"
          />
        </CardGrid>
      );

      // Should maintain heading structure
      const headings = screen.getAllByRole('heading', { level: 3 });
      expect(headings).toHaveLength(2);
      expect(headings[0]).toHaveTextContent('Accessible Card 1');
      expect(headings[1]).toHaveTextContent('Accessible Card 2');

      // Linked card should be accessible
      const link = screen.getByRole('link');
      expect(link).toHaveAttribute('href', '/accessible-link');
    });

    test('preserves keyboard navigation order', () => {
      render(
        <CardGrid>
          <Card title="Card 1" description="First" link="/link1" />
          <Card title="Card 2" description="Second" link="/link2" />
          <Card title="Card 3" description="Third" />
          <Card title="Card 4" description="Fourth" link="/link4" />
        </CardGrid>
      );

      const links = screen.getAllByRole('link');
      expect(links).toHaveLength(3);
      expect(links[0]).toHaveAttribute('href', '/link1');
      expect(links[1]).toHaveAttribute('href', '/link2');
      expect(links[2]).toHaveAttribute('href', '/link4');
    });
  });

  describe('CSS Module Integration', () => {
    test('uses correct kebab-case CSS class', () => {
      const { container } = render(
        <CardGrid>
          <div>Test content</div>
        </CardGrid>
      );

      const gridElement = container.querySelector('.cards-grid');
      expect(gridElement).toBeInTheDocument();
      expect(gridElement).toHaveClass('cards-grid');
    });

    test('CSS class name matches component expectation', () => {
      const { container } = render(
        <CardGrid className="test-class">
          <Card icon="🧪" title="Test Card" description="Test description" />
        </CardGrid>
      );

      const gridElement = container.querySelector('.cards-grid.test-class');
      expect(gridElement).toBeInTheDocument();
    });
  });

  describe('Edge Cases', () => {
    test('handles very large number of children', () => {
      const manyChildren = Array.from({ length: 100 }, (_, i) => (
        <div key={i}>Child {i}</div>
      ));

      render(<CardGrid>{manyChildren}</CardGrid>);

      // Check first, middle, and last children
      expect(screen.getByText('Child 0')).toBeInTheDocument();
      expect(screen.getByText('Child 50')).toBeInTheDocument();
      expect(screen.getByText('Child 99')).toBeInTheDocument();
    });

    test('handles complex nested structures', () => {
      render(
        <CardGrid>
          <Card
            icon="🏗️"
            title="Complex Card"
            description="Complex content with <strong>bold text</strong>, <em>italic text</em>, and <code>code snippets</code>"
            link="/complex"
          />
        </CardGrid>
      );

      expect(screen.getByText('Complex Card')).toBeInTheDocument();
      expect(screen.getByText('bold text')).toBeInTheDocument();
      expect(screen.getByText('italic text')).toBeInTheDocument();
      expect(screen.getByText('code snippets')).toBeInTheDocument();
    });

    test('handles empty and whitespace-only children', () => {
      render(
        <CardGrid>
          {''} {'\n'}
          <Card icon="✅" title="Valid Card" description="Real content" />
          {'   '}
        </CardGrid>
      );

      expect(screen.getByText('Valid Card')).toBeInTheDocument();
      expect(screen.getByText('Real content')).toBeInTheDocument();
    });
  });
});
