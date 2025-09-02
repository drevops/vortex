import React from 'react';
import { render, screen } from '@testing-library/react';
import AsciinemaPlayer from '../../../src/components/AsciinemaPlayer/AsciinemaPlayer';

describe('AsciinemaPlayer Component', () => {
  describe('Basic Rendering', () => {
    test('renders container div', () => {
      const { container } = render(
        <AsciinemaPlayer src="/fixtures/test-cast.json" />
      );

      expect(container.firstChild).toBeInTheDocument();
      expect(container.firstChild.tagName).toBe('DIV');
    });

    test('renders without src prop', () => {
      const { container } = render(<AsciinemaPlayer />);

      expect(container.firstChild).toBeInTheDocument();
      expect(container.firstChild.tagName).toBe('DIV');
    });

    test('renders with all props', () => {
      const { container } = render(
        <AsciinemaPlayer
          src="/fixtures/test-cast.json"
          poster="npt:0:01"
          startAt={30}
          autoPlay={true}
          loop={true}
          preload={true}
          controls={true}
          theme="monokai"
        />
      );

      expect(container.firstChild).toBeInTheDocument();
    });
  });

  describe('Container Props', () => {
    test('passes through className prop', () => {
      render(
        <AsciinemaPlayer
          src="/fixtures/test-cast.json"
          className="custom-player"
          data-testid="player"
        />
      );

      const element = screen.getByTestId('player');
      expect(element).toHaveClass('custom-player');
    });

    test('passes through id prop', () => {
      render(
        <AsciinemaPlayer
          src="/fixtures/test-cast.json"
          id="my-player"
          data-testid="player"
        />
      );

      const element = screen.getByTestId('player');
      expect(element).toHaveAttribute('id', 'my-player');
    });

    test('passes through style prop', () => {
      render(
        <AsciinemaPlayer
          src="/fixtures/test-cast.json"
          style={{ width: '100%', height: '400px' }}
          data-testid="player"
        />
      );

      const element = screen.getByTestId('player');
      expect(element).toHaveStyle('width: 100%');
      expect(element).toHaveStyle('height: 400px');
    });

    test('passes through data attributes', () => {
      render(
        <AsciinemaPlayer
          src="/fixtures/test-cast.json"
          data-testid="player"
          data-custom="value"
        />
      );

      const element = screen.getByTestId('player');
      expect(element).toHaveAttribute('data-custom', 'value');
    });
  });

  describe('Prop Variants - Boolean Props', () => {
    test('renders with autoPlay true', () => {
      const { container } = render(
        <AsciinemaPlayer src="/fixtures/test-cast.json" autoPlay={true} />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders with autoPlay false', () => {
      const { container } = render(
        <AsciinemaPlayer src="/fixtures/test-cast.json" autoPlay={false} />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders with loop true', () => {
      const { container } = render(
        <AsciinemaPlayer src="/fixtures/test-cast.json" loop={true} />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders with loop false', () => {
      const { container } = render(
        <AsciinemaPlayer src="/fixtures/test-cast.json" loop={false} />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders with controls true', () => {
      const { container } = render(
        <AsciinemaPlayer src="/fixtures/test-cast.json" controls={true} />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders with controls false', () => {
      const { container } = render(
        <AsciinemaPlayer src="/fixtures/test-cast.json" controls={false} />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders with preload true', () => {
      const { container } = render(
        <AsciinemaPlayer src="/fixtures/test-cast.json" preload={true} />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders with preload false', () => {
      const { container } = render(
        <AsciinemaPlayer src="/fixtures/test-cast.json" preload={false} />
      );

      expect(container.firstChild).toBeInTheDocument();
    });
  });

  describe('Prop Variants - Theme Options', () => {
    test('renders with asciinema theme', () => {
      const { container } = render(
        <AsciinemaPlayer src="/fixtures/test-cast.json" theme="asciinema" />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders with monokai theme', () => {
      const { container } = render(
        <AsciinemaPlayer src="/fixtures/test-cast.json" theme="monokai" />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders with tango theme', () => {
      const { container } = render(
        <AsciinemaPlayer src="/fixtures/test-cast.json" theme="tango" />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders with solarized-dark theme', () => {
      const { container } = render(
        <AsciinemaPlayer
          src="/fixtures/test-cast.json"
          theme="solarized-dark"
        />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders with solarized-light theme', () => {
      const { container } = render(
        <AsciinemaPlayer
          src="/fixtures/test-cast.json"
          theme="solarized-light"
        />
      );

      expect(container.firstChild).toBeInTheDocument();
    });
  });

  describe('Prop Variants - Poster Options', () => {
    test('renders with npt poster format', () => {
      const { container } = render(
        <AsciinemaPlayer src="/fixtures/test-cast.json" poster="npt:0:01" />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders with different npt times', () => {
      const posterTimes = ['npt:0:01', 'npt:0:30', 'npt:1:23', 'npt:2:45'];

      posterTimes.forEach(poster => {
        const { container } = render(
          <AsciinemaPlayer src="/fixtures/test-cast.json" poster={poster} />
        );

        expect(container.firstChild).toBeInTheDocument();
      });
    });

    test('renders with numeric poster values', () => {
      const numericPosters = ['1', '30', '90', '120'];

      numericPosters.forEach(poster => {
        const { container } = render(
          <AsciinemaPlayer src="/fixtures/test-cast.json" poster={poster} />
        );

        expect(container.firstChild).toBeInTheDocument();
      });
    });
  });

  describe('Prop Variants - StartAt Options', () => {
    test('renders with startAt as number', () => {
      const { container } = render(
        <AsciinemaPlayer src="/fixtures/test-cast.json" startAt={30} />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders with different startAt values', () => {
      const startAtValues = [0, 15, 30, 60, 120];

      startAtValues.forEach(startAt => {
        const { container } = render(
          <AsciinemaPlayer src="/fixtures/test-cast.json" startAt={startAt} />
        );

        expect(container.firstChild).toBeInTheDocument();
      });
    });
  });

  describe('Prop Combinations', () => {
    test('renders with all boolean props true', () => {
      const { container } = render(
        <AsciinemaPlayer
          src="/fixtures/test-cast.json"
          autoPlay={true}
          loop={true}
          preload={true}
          controls={true}
        />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders with all boolean props false', () => {
      const { container } = render(
        <AsciinemaPlayer
          src="/fixtures/test-cast.json"
          autoPlay={false}
          loop={false}
          preload={false}
          controls={false}
        />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders with mixed boolean props', () => {
      const { container } = render(
        <AsciinemaPlayer
          src="/fixtures/test-cast.json"
          autoPlay={true}
          loop={false}
          preload={true}
          controls={false}
        />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders with full configuration', () => {
      const { container } = render(
        <AsciinemaPlayer
          src="/fixtures/test-cast.json"
          poster="npt:1:30"
          startAt={15}
          autoPlay={true}
          loop={true}
          preload={true}
          controls={true}
          theme="monokai"
          className="full-config-player"
          id="full-player"
        />
      );

      expect(container.firstChild).toBeInTheDocument();
      expect(container.firstChild).toHaveClass('full-config-player');
      expect(container.firstChild).toHaveAttribute('id', 'full-player');
    });

    test('renders with minimal configuration', () => {
      const { container } = render(
        <AsciinemaPlayer
          src="/fixtures/test-cast.json"
          autoPlay={false}
          loop={false}
          preload={false}
          controls={false}
        />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders with production-like configuration', () => {
      const { container } = render(
        <AsciinemaPlayer
          src="/img/installer.json"
          poster="npt:0:01"
          autoPlay={false}
          loop={false}
          preload={true}
          controls={true}
          theme="asciinema"
        />
      );

      expect(container.firstChild).toBeInTheDocument();
    });
  });

  describe('Edge Cases', () => {
    test('renders with null props', () => {
      const { container } = render(
        <AsciinemaPlayer
          src={null}
          poster={null}
          theme={null}
          className={null}
        />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders with undefined props', () => {
      const { container } = render(
        <AsciinemaPlayer
          src={undefined}
          poster={undefined}
          startAt={undefined}
          theme={undefined}
        />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders with empty string props', () => {
      const { container } = render(
        <AsciinemaPlayer src="" poster="" theme="" className="" />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('handles re-rendering with prop changes', () => {
      const { container, rerender } = render(
        <AsciinemaPlayer
          src="/fixtures/test-cast.json"
          theme="asciinema"
          autoPlay={false}
        />
      );

      expect(container.firstChild).toBeInTheDocument();

      rerender(
        <AsciinemaPlayer
          src="/fixtures/test-cast.json"
          theme="monokai"
          autoPlay={true}
          poster="npt:0:30"
        />
      );

      expect(container.firstChild).toBeInTheDocument();
    });

    test('renders multiple instances', () => {
      render(
        <div>
          <AsciinemaPlayer
            src="/fixtures/test-cast.json"
            data-testid="player-1"
          />
          <AsciinemaPlayer
            src="/fixtures/test-cast.json"
            theme="monokai"
            data-testid="player-2"
          />
        </div>
      );

      expect(screen.getByTestId('player-1')).toBeInTheDocument();
      expect(screen.getByTestId('player-2')).toBeInTheDocument();
    });
  });

  describe('Library Integration', () => {
    beforeEach(() => {
      // Clean up window/document for each test if they exist
      if (typeof window !== 'undefined' && window.AsciinemaPlayer) {
        delete window.AsciinemaPlayer;
      }
      if (typeof document !== 'undefined') {
        document.head.innerHTML = '';
      }
      jest.clearAllMocks();
    });

    test('loads CSS and JS assets', async () => {
      render(<AsciinemaPlayer src="/fixtures/test-cast.json" />);

      // Wait for useEffect
      await new Promise(resolve => setTimeout(resolve, 100));

      // Should add CSS link
      const cssLink = document.head.querySelector(
        'link[href*="asciinema-player.css"]'
      );
      expect(cssLink).toBeInTheDocument();
      expect(cssLink.rel).toBe('stylesheet');

      // Should add JS script
      const jsScript = document.head.querySelector(
        'script[src*="asciinema-player.min.js"]'
      );
      expect(jsScript).toBeInTheDocument();
    });

    test('does not duplicate CSS links', async () => {
      // First render
      render(
        <AsciinemaPlayer
          src="/fixtures/test-cast.json"
          data-testid="player-1"
        />
      );
      await new Promise(resolve => setTimeout(resolve, 50));

      // Second render
      render(
        <AsciinemaPlayer
          src="/fixtures/test-cast.json"
          data-testid="player-2"
        />
      );
      await new Promise(resolve => setTimeout(resolve, 50));

      // Should only have one CSS link
      const cssLinks = document.head.querySelectorAll(
        'link[href*="asciinema-player.css"]'
      );
      expect(cssLinks).toHaveLength(1);
    });

    test('creates player when library loads via script onload', async () => {
      const mockCreate = jest.fn();
      const { container } = render(
        <AsciinemaPlayer src="/fixtures/test-cast.json" poster="npt:0:01" />
      );

      await new Promise(resolve => setTimeout(resolve, 50));

      // Simulate library not existing initially
      expect(window.AsciinemaPlayer).toBeUndefined();

      // Find the script and simulate its onload
      const script = document.head.querySelector(
        'script[src*="asciinema-player.min.js"]'
      );
      expect(script).toBeInTheDocument();

      // Mock the library being available after script loads
      window.AsciinemaPlayer = { create: mockCreate };

      // Trigger the onload handler
      if (script.onload) {
        script.onload();
      }

      expect(mockCreate).toHaveBeenCalledWith(
        '/fixtures/test-cast.json',
        container.firstChild,
        expect.objectContaining({
          poster: 'npt:0:01',
          autoPlay: false,
          loop: false,
          preload: true,
          controls: true,
          theme: 'asciinema',
        })
      );
    });

    test('creates player when library already exists', async () => {
      const mockCreate = jest.fn();

      // Pre-load the library
      window.AsciinemaPlayer = { create: mockCreate };

      const { container } = render(
        <AsciinemaPlayer
          src="/fixtures/test-cast.json"
          poster="npt:0:30"
          startAt={45}
          autoPlay={true}
          theme="monokai"
        />
      );

      await new Promise(resolve => setTimeout(resolve, 50));

      expect(mockCreate).toHaveBeenCalledWith(
        '/fixtures/test-cast.json',
        container.firstChild,
        expect.objectContaining({
          poster: 'npt:0:30',
          startAt: 45,
          autoPlay: true,
          loop: false,
          preload: true,
          controls: true,
          theme: 'monokai',
        })
      );
    });

    test('handles poster and startAt options correctly', async () => {
      const mockCreate = jest.fn();
      window.AsciinemaPlayer = { create: mockCreate };

      // With poster but no startAt
      const { rerender, container } = render(
        <AsciinemaPlayer src="/fixtures/test-cast.json" poster="npt:1:00" />
      );

      await new Promise(resolve => setTimeout(resolve, 50));

      expect(mockCreate).toHaveBeenCalledWith(
        '/fixtures/test-cast.json',
        container.firstChild,
        expect.objectContaining({
          poster: 'npt:1:00',
        })
      );
      expect(mockCreate).toHaveBeenCalledWith(
        '/fixtures/test-cast.json',
        container.firstChild,
        expect.not.objectContaining({
          startAt: expect.anything(),
        })
      );

      jest.clearAllMocks();

      // With startAt but no poster
      rerender(<AsciinemaPlayer src="/fixtures/test-cast.json" startAt={30} />);

      await new Promise(resolve => setTimeout(resolve, 50));

      expect(mockCreate).toHaveBeenCalledWith(
        '/fixtures/test-cast.json',
        container.firstChild,
        expect.objectContaining({
          startAt: 30,
        })
      );
      expect(mockCreate).toHaveBeenCalledWith(
        '/fixtures/test-cast.json',
        container.firstChild,
        expect.not.objectContaining({
          poster: expect.anything(),
        })
      );
    });

    test('handles errors during library loading', async () => {
      const consoleSpy = jest.spyOn(console, 'error').mockImplementation();

      // Force an error by making querySelector throw
      const originalQuerySelector = document.querySelector;
      document.querySelector = jest.fn().mockImplementation(() => {
        throw new Error('Mock error');
      });

      const { container } = render(
        <AsciinemaPlayer src="/fixtures/test-cast.json" />
      );

      await new Promise(resolve => setTimeout(resolve, 50));

      // Should still render the container
      expect(container.firstChild).toBeInTheDocument();

      // Should log the error
      expect(consoleSpy).toHaveBeenCalledWith(
        'Failed to load Asciinema player:',
        expect.any(Error)
      );

      // Restore
      document.querySelector = originalQuerySelector;
      consoleSpy.mockRestore();
    });

    test('covers startAt assignment in script onload callback', async () => {
      const mockCreate = jest.fn();
      const { container } = render(
        <AsciinemaPlayer src="/fixtures/test-cast.json" startAt={60} />
      );

      await new Promise(resolve => setTimeout(resolve, 50));

      // Find the script and simulate its onload (library not yet available)
      const script = document.head.querySelector(
        'script[src*="asciinema-player.min.js"]'
      );
      expect(script).toBeInTheDocument();

      // Mock the library being available after script loads
      window.AsciinemaPlayer = { create: mockCreate };

      // Trigger the onload handler to cover the startAt assignment on line 50
      if (script.onload) {
        script.onload();
      }

      expect(mockCreate).toHaveBeenCalledWith(
        '/fixtures/test-cast.json',
        container.firstChild,
        expect.objectContaining({
          startAt: 60,
        })
      );
    });
  });
});
