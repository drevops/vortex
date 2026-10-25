import React from 'react';
import { render } from '@testing-library/react';
import Anchor from '../../../src/components/Anchor';

const mockCollectAnchor = jest.fn();

jest.mock(
  '@docusaurus/useBrokenLinks',
  () => () => ({ collectAnchor: mockCollectAnchor }),
  { virtual: true }
);

describe('Anchor Component', () => {
  beforeEach(() => {
    mockCollectAnchor.mockClear();
  });

  test('renders a link target with the given id', () => {
    const { container } = render(<Anchor id="vortex_debug" />);

    const anchor = container.querySelector('a');
    expect(anchor).toBeInTheDocument();
    expect(anchor).toHaveAttribute('id', 'vortex_debug');
    expect(anchor).not.toHaveAttribute('href');
  });

  test('registers the id with the broken-anchor checker', () => {
    render(<Anchor id="vortex_debug" />);

    expect(mockCollectAnchor).toHaveBeenCalledWith('vortex_debug');
  });
});
