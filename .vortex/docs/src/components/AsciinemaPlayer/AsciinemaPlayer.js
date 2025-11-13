import React, { useEffect, useRef } from 'react';

const AsciinemaPlayer = ({
  src,
  poster,
  startAt,
  autoPlay = false,
  loop = false,
  preload = true,
  controls = true,
  theme = 'asciinema',
  terminalLineHeight = 1.0,
  terminalFontFamily = 'Consolas, "Courier New", Courier, "Liberation Mono", monospace',
  ...props
}) => {
  const containerRef = useRef(null);

  useEffect(() => {
    const loadAsciinemaPlayer = async () => {
      if (typeof window === 'undefined') {
        return;
      }

      try {
        if (!document.querySelector('link[href*="asciinema-player.css"]')) {
          const link = document.createElement('link');
          link.rel = 'stylesheet';
          link.href =
            'https://cdn.jsdelivr.net/npm/asciinema-player@3/dist/bundle/asciinema-player.css';
          document.head.appendChild(link);
        }

        if (!window.AsciinemaPlayer) {
          const script = document.createElement('script');
          script.src =
            'https://cdn.jsdelivr.net/npm/asciinema-player@3/dist/bundle/asciinema-player.min.js';
          script.onload = () => {
            if (containerRef.current && window.AsciinemaPlayer) {
              const options = {
                autoPlay,
                loop,
                preload,
                controls,
                theme,
                terminalLineHeight,
                terminalFontFamily,
              };

              if (poster) {
                options.poster = poster;
              }

              if (startAt !== undefined) {
                options.startAt = startAt;
              }

              window.AsciinemaPlayer.create(src, containerRef.current, options);
            }
          };
          document.head.appendChild(script);
        } else {
          if (containerRef.current && window.AsciinemaPlayer) {
            const options = {
              autoPlay,
              loop,
              preload,
              controls,
              theme,
              terminalLineHeight,
              terminalFontFamily,
            };

            if (poster) {
              options.poster = poster;
            }

            if (startAt !== undefined) {
              options.startAt = startAt;
            }

            window.AsciinemaPlayer.create(src, containerRef.current, options);
          }
        }
      } catch (error) {
        console.error('Failed to load Asciinema player:', error);
      }
    };

    loadAsciinemaPlayer();
  }, [
    src,
    poster,
    startAt,
    autoPlay,
    loop,
    preload,
    controls,
    theme,
    terminalLineHeight,
    terminalFontFamily,
  ]);

  return <div ref={containerRef} {...props} />;
};

export default AsciinemaPlayer;
