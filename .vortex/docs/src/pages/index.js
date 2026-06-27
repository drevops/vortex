import React, { useEffect, useRef, useState } from 'react';
import Layout from '@theme/Layout';
import useBaseUrl from '@docusaurus/useBaseUrl';
import AsciinemaPlayer from '@site/src/components/AsciinemaPlayer';

const INSTALL_COMMAND =
  'curl -SsL https://www.vortextemplate.com/install > installer.php && php installer.php';

function copyToClipboard(text) {
  if (
    typeof navigator !== 'undefined' &&
    navigator.clipboard &&
    navigator.clipboard.writeText
  ) {
    return navigator.clipboard
      .writeText(text)
      .then(() => true)
      .catch(() => fallbackCopy(text));
  }

  return Promise.resolve(fallbackCopy(text));
}

function fallbackCopy(text) {
  if (typeof document === 'undefined') {
    return false;
  }

  const ta = document.createElement('textarea');
  ta.value = text;
  ta.setAttribute('readonly', '');
  ta.style.position = 'absolute';
  ta.style.left = '-9999px';
  document.body.appendChild(ta);
  ta.select();

  let ok = false;

  try {
    ok = document.execCommand('copy');
  } catch (e) {
    ok = false;
  }

  document.body.removeChild(ta);

  return ok;
}

function useCopied() {
  const [copied, setCopied] = useState(false);
  const timer = useRef(null);

  useEffect(
    () => () => {
      if (timer.current) {
        clearTimeout(timer.current);
      }
    },
    []
  );

  const trigger = text => {
    copyToClipboard(text).then(ok => {
      if (!ok) {
        return;
      }

      setCopied(true);

      if (timer.current) {
        clearTimeout(timer.current);
      }

      timer.current = setTimeout(() => setCopied(false), 1600);
    });
  };

  return [copied, trigger];
}

function CopyButton({ command }) {
  const [copied, trigger] = useCopied();

  return (
    <button
      className={copied ? 'copy-btn copied' : 'copy-btn'}
      type="button"
      onClick={() => trigger(command)}
      aria-label="Copy install command"
    >
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLinejoin="round"
      >
        <rect x="9" y="9" width="11" height="11" rx="2" />
        <path d="M5 15V5a2 2 0 0 1 2-2h10" />
      </svg>
      <span className="ct">{copied ? 'Copied!' : 'Copy'}</span>
    </button>
  );
}

function CopyMini({ command, label }) {
  const [copied, trigger] = useCopied();

  return (
    <button
      className={copied ? 'copy-mini copied' : 'copy-mini'}
      type="button"
      onClick={() => trigger(command)}
      aria-label={label}
    >
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLinejoin="round"
      >
        <rect x="9" y="9" width="11" height="11" rx="2" />
        <path d="M5 15V5a2 2 0 0 1 2-2h10" />
      </svg>
    </button>
  );
}

function InstallSnippet() {
  return (
    <div className="snippet">
      <div className="snippet-code">
        <span className="pr">$</span> <span className="cm">curl</span>{' '}
        <span className="fl">-SsL</span> https://www.vortextemplate.com/install{' '}
        <span className="op">&gt;</span> installer.php{' '}
        <span className="op">&amp;&amp;</span> <span className="cm">php</span>{' '}
        installer.php
      </div>
      <CopyButton command={INSTALL_COMMAND} />
    </div>
  );
}

function useReveals() {
  useEffect(() => {
    const targets = document.querySelectorAll(
      '.vtx-home .reveal, .vtx-home .stagger'
    );
    const reduce = window.matchMedia(
      '(prefers-reduced-motion: reduce)'
    ).matches;

    if (reduce || !('IntersectionObserver' in window)) {
      targets.forEach(t => t.classList.add('in'));

      return undefined;
    }

    const io = new IntersectionObserver(
      entries => {
        entries.forEach(en => {
          if (en.isIntersecting) {
            en.target.classList.add('in');
            io.unobserve(en.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: '0px 0px -8% 0px' }
    );

    targets.forEach(t => io.observe(t));

    return () => io.disconnect();
  }, []);
}

export default function Home() {
  const installerVideo = useBaseUrl('/img/installer.svg');
  const installerCast = useBaseUrl('/img/installer.json');
  const [playerOpen, setPlayerOpen] = useState(false);
  const triggerRef = useRef(null);
  const closeRef = useRef(null);
  const modalRef = useRef(null);

  useReveals();

  useEffect(() => {
    if (!playerOpen) {
      return undefined;
    }

    const onKey = e => {
      if (e.key === 'Escape') {
        setPlayerOpen(false);

        return;
      }

      if (e.key === 'Tab' && modalRef.current) {
        const focusables = modalRef.current.querySelectorAll(
          'a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );

        if (focusables.length === 0) {
          return;
        }

        const first = focusables[0];
        const last = focusables[focusables.length - 1];

        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    };

    const trigger = triggerRef.current;

    document.addEventListener('keydown', onKey);
    const prevOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    if (closeRef.current) {
      closeRef.current.focus();
    }

    return () => {
      document.removeEventListener('keydown', onKey);
      document.body.style.overflow = prevOverflow;

      if (trigger) {
        trigger.focus();
      }
    };
  }, [playerOpen]);

  const closeOnBackdrop = e => {
    if (e.target === e.currentTarget) {
      setPlayerOpen(false);
    }
  };

  return (
    <Layout
      title="Ship Drupal projects on solid ground."
      description="Vortex is a production-ready Drupal project template: containerized local development, automated testing, CI/CD, and hosting integrations - pre-configured and continuously tested."
    >
      <div className="vtx-home">
        <main>
          {/* HERO */}
          <section className="hero">
            <div className="wrap">
              <div className="hero-grid">
                <div className="hero-text reveal">
                  <span className="hero-badge">
                    <span className="pulse" /> Production-grade Drupal, since
                    2017
                  </span>
                  <h1>
                    Ship Drupal projects on{' '}
                    <span className="grad">solid ground.</span>
                  </h1>
                  <p className="hero-sub">
                    Vortex is a Drupal project template that hands your team a
                    complete, production-ready foundation - containerized local
                    development, automated testing, CI/CD pipelines, and hosting
                    integrations - all pre-configured and continuously tested.
                  </p>
                  <div className="hero-actions">
                    <a className="btn btn-primary" href="/docs/installation">
                      Get started
                      <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2.2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      >
                        <path d="M5 12h14M13 6l6 6-6 6" />
                      </svg>
                    </a>
                    <a className="btn btn-ghost" href="/docs">
                      Read the docs
                    </a>
                  </div>
                </div>

                <div className="hero-media reveal">
                  <figure className="media-frame">
                    <figcaption className="media-bar">
                      <span className="media-dots">
                        <span className="r" />
                        <span className="y" />
                        <span className="g" />
                      </span>
                      <span className="media-cap">vortex installer</span>
                      <span className="media-live">
                        <span className="d" /> demo
                      </span>
                    </figcaption>
                    <button
                      type="button"
                      className="media-screen media-play"
                      onClick={() => setPlayerOpen(true)}
                      ref={triggerRef}
                      aria-label="Play the installer demo with playback controls"
                    >
                      <img
                        src={installerVideo}
                        alt="Animated demo of the Vortex installer scaffolding a new project from a single command"
                        loading="lazy"
                        decoding="async"
                        width="1280"
                        height="705"
                      />
                      <span className="media-play-overlay" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                          <path d="M8 5v14l11-7z" />
                        </svg>
                      </span>
                    </button>
                  </figure>
                  <p className="media-foot">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth="2"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="m9 18 6-6-6-6" />
                    </svg>{' '}
                    The installer scaffolds your whole project - structure,
                    tooling, CI, and hosting - in one run.
                  </p>
                </div>
              </div>

              <div className="hero-cmd reveal">
                <span className="hero-cmd-label">Install in one command</span>
                <InstallSnippet />
                <p className="snippet-note">
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                  >
                    <path d="M20 6 9 17l-5-5" />
                  </svg>{' '}
                  One command. PHP 8.3+. No global installs.
                </p>
              </div>
            </div>
          </section>

          {/* TRUST */}
          <section className="trust">
            <div className="wrap">
              <div className="trust-grid stagger">
                <div className="trust-cell">
                  <div className="trust-num">2017</div>
                  <div className="trust-label">In production since</div>
                </div>
                <div className="trust-cell">
                  <div className="trust-num">Monthly</div>
                  <div className="trust-label">Releases shipped</div>
                </div>
                <div className="trust-cell">
                  <div className="trust-num">130+</div>
                  <div className="trust-label">Scenarios tested per change</div>
                </div>
                <div className="trust-cell">
                  <div className="trust-num">GPL-3.0</div>
                  <div className="trust-label">Free &amp; open source</div>
                </div>
              </div>
            </div>
          </section>

          {/* TICKER */}
          <div className="ticker" aria-hidden="true">
            <div className="ticker-mask">
              <div className="ticker-row r1">
                <span className="chip">
                  <span className="cd" />
                  PHPUnit
                </span>
                <span className="chip">
                  <span className="cd" />
                  Behat
                </span>
                <span className="chip">
                  <span className="cd" />
                  PHPStan
                </span>
                <span className="chip">
                  <span className="cd" />
                  Rector
                </span>
                <span className="chip">
                  <span className="cd" />
                  ESLint
                </span>
                <span className="chip">
                  <span className="cd" />
                  Twig CS Fixer
                </span>
                <span className="chip">
                  <span className="cd" />
                  PHPCS
                </span>
                <span className="chip">
                  <span className="cd" />
                  Docker
                </span>
                <span className="chip">
                  <span className="cd" />
                  Ahoy
                </span>
                <span className="chip">
                  <span className="cd" />
                  Nginx
                </span>
                <span className="chip">
                  <span className="cd" />
                  PHP 8.3
                </span>
                <span className="chip">
                  <span className="cd" />
                  MariaDB
                </span>
                <span className="chip">
                  <span className="cd" />
                  Redis
                </span>
                <span className="chip">
                  <span className="cd" />
                  PHPUnit
                </span>
                <span className="chip">
                  <span className="cd" />
                  Behat
                </span>
                <span className="chip">
                  <span className="cd" />
                  PHPStan
                </span>
                <span className="chip">
                  <span className="cd" />
                  Rector
                </span>
                <span className="chip">
                  <span className="cd" />
                  ESLint
                </span>
                <span className="chip">
                  <span className="cd" />
                  Twig CS Fixer
                </span>
                <span className="chip">
                  <span className="cd" />
                  PHPCS
                </span>
                <span className="chip">
                  <span className="cd" />
                  Docker
                </span>
                <span className="chip">
                  <span className="cd" />
                  Ahoy
                </span>
                <span className="chip">
                  <span className="cd" />
                  Nginx
                </span>
                <span className="chip">
                  <span className="cd" />
                  PHP 8.3
                </span>
                <span className="chip">
                  <span className="cd" />
                  MariaDB
                </span>
                <span className="chip">
                  <span className="cd" />
                  Redis
                </span>
              </div>
              <div className="ticker-row r2">
                <span className="chip">
                  <span className="cd" />
                  Solr
                </span>
                <span className="chip">
                  <span className="cd" />
                  ClamAV
                </span>
                <span className="chip">
                  <span className="cd" />
                  Xdebug
                </span>
                <span className="chip">
                  <span className="cd" />
                  Composer
                </span>
                <span className="chip">
                  <span className="cd" />
                  Drush
                </span>
                <span className="chip">
                  <span className="cd" />
                  Acquia
                </span>
                <span className="chip">
                  <span className="cd" />
                  Lagoon
                </span>
                <span className="chip">
                  <span className="cd" />
                  CircleCI
                </span>
                <span className="chip">
                  <span className="cd" />
                  GitHub Actions
                </span>
                <span className="chip">
                  <span className="cd" />
                  Renovate
                </span>
                <span className="chip">
                  <span className="cd" />
                  Diffy
                </span>
                <span className="chip">
                  <span className="cd" />
                  Codecov
                </span>
                <span className="chip">
                  <span className="cd" />
                  Solr
                </span>
                <span className="chip">
                  <span className="cd" />
                  ClamAV
                </span>
                <span className="chip">
                  <span className="cd" />
                  Xdebug
                </span>
                <span className="chip">
                  <span className="cd" />
                  Composer
                </span>
                <span className="chip">
                  <span className="cd" />
                  Drush
                </span>
                <span className="chip">
                  <span className="cd" />
                  Acquia
                </span>
                <span className="chip">
                  <span className="cd" />
                  Lagoon
                </span>
                <span className="chip">
                  <span className="cd" />
                  CircleCI
                </span>
                <span className="chip">
                  <span className="cd" />
                  GitHub Actions
                </span>
                <span className="chip">
                  <span className="cd" />
                  Renovate
                </span>
                <span className="chip">
                  <span className="cd" />
                  Diffy
                </span>
                <span className="chip">
                  <span className="cd" />
                  Codecov
                </span>
              </div>
            </div>
          </div>

          {/* FEATURES */}
          <section className="section-pad">
            <div className="wrap">
              <div className="section-head reveal">
                <span className="eyebrow">What&apos;s inside</span>
                <h2 className="section-title">
                  Everything a Drupal team needs, pre-wired
                </h2>
                <p className="section-lead">
                  A complete foundation for building and deploying Drupal sites
                  - so your team focuses on features, not infrastructure.
                </p>
              </div>
              <div className="feature-grid stagger">
                <article className="fcard">
                  <span className="ic ic-cyan">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="M12 3s6.5 6 6.5 10.5a6.5 6.5 0 0 1-13 0C5.5 9 12 3 12 3z" />
                      <path d="M9.5 13.5a2.6 2.6 0 0 0 2.5 2.5" />
                    </svg>
                  </span>
                  <h3>Drupal foundation</h3>
                  <p>
                    Modern Drupal 11 on a Composer project with tuned settings,
                    module and theme scaffolds, and admin modules ready to go.
                  </p>
                </article>
                <article className="fcard">
                  <span className="ic ic-teal">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="M12 2.5 20.5 7v10L12 21.5 3.5 17V7L12 2.5z" />
                      <path d="M3.7 7.2 12 12l8.3-4.8M12 12v9.3" />
                    </svg>
                  </span>
                  <h3>Local development</h3>
                  <p>
                    A containerized Docker Compose stack - Nginx, PHP, MariaDB,
                    Redis, Solr, ClamAV - driven by simple Ahoy commands.
                  </p>
                </article>
                <article className="fcard">
                  <span className="ic ic-blue">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="M21 12a9 9 0 1 1-2.64-6.36" />
                      <path d="M21 4v5h-5" />
                      <path d="m9 12 2 2 4-4" />
                    </svg>
                  </span>
                  <h3>Continuous integration</h3>
                  <p>
                    Identical GitHub Actions and CircleCI pipelines: download
                    the database, build and test a real site, then deploy.
                  </p>
                </article>
                <article className="fcard">
                  <span className="ic ic-cyan">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="M7 18a4.5 4.5 0 0 1-.5-8.97 6 6 0 0 1 11.5 1.3A3.8 3.8 0 0 1 17.5 18H7z" />
                    </svg>
                  </span>
                  <h3>Hosting integrations</h3>
                  <p>
                    First-class Acquia and Lagoon support with database and file
                    syncing, deployments, and preview environments.
                  </p>
                </article>
                <article className="fcard">
                  <span className="ic ic-teal">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="M12 3 5 6v5.5c0 4.2 3 7 7 9 4-2 7-4.8 7-9V6l-7-3z" />
                      <path d="m9 12 2 2 4-4.2" />
                    </svg>
                  </span>
                  <h3>Quality &amp; testing</h3>
                  <p>
                    PHPCS, PHPStan, Rector, ESLint, PHPUnit, and Behat -
                    pre-configured and wired straight into CI.
                  </p>
                </article>
                <article className="fcard">
                  <span className="ic ic-blue">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="m12 3 1.9 4.7L18.5 9l-4.6 1.3L12 15l-1.9-4.7L5.5 9l4.6-1.3L12 3z" />
                      <path d="M19 14.5l.7 1.8 1.8.7-1.8.7-.7 1.8-.7-1.8-1.8-.7 1.8-.7.7-1.8z" />
                    </svg>
                  </span>
                  <h3>AI-ready</h3>
                  <p>
                    Agent-agnostic AGENTS.md and CLAUDE.md give coding agents
                    the context to contribute from the very first session.
                  </p>
                </article>
                <article className="fcard">
                  <span className="ic ic-cyan">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="M13 2 4.5 13.2H11l-1 8.8L19.5 10.8H13l0-8.8z" />
                    </svg>
                  </span>
                  <h3>Automations</h3>
                  <p>
                    Renovate dependency updates, deploy notifications for Slack,
                    Jira, New Relic and email, plus workflow tooling.
                  </p>
                </article>
                <article className="fcard">
                  <span className="ic ic-teal">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="M5 4.5A2 2 0 0 1 7 3h11a1 1 0 0 1 1 1v15a1 1 0 0 1-1 1H7a2 2 0 0 0-2 2V4.5z" />
                      <path d="M5 19.5A2 2 0 0 1 7 18h12" />
                      <path d="M9 7.5h6M9 11h6" />
                    </svg>
                  </span>
                  <h3>Documentation</h3>
                  <p>
                    Centralized docs at vortextemplate.com, plus a scaffold for
                    your own project-specific documentation.
                  </p>
                </article>
              </div>
            </div>
          </section>

          {/* PILLARS */}
          <section className="section-pad" style={{ paddingTop: 0 }}>
            <div className="wrap">
              <div className="section-head center reveal">
                <span className="eyebrow">How it fits together</span>
                <h2 className="section-title">
                  Template + Documentation + Installer
                </h2>
                <p className="section-lead">
                  Three parts that keep every Vortex project consistent - and
                  easy to keep up to date.
                </p>
              </div>
              <div className="pillars-grid stagger">
                <article className="pillar">
                  <span className="ic ic-blue">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="M12 3 21 8l-9 5-9-5 9-5z" />
                      <path d="m3 13 9 5 9-5" />
                      <path d="m3 17.5 9 5 9-5" />
                    </svg>
                  </span>
                  <h3>Template</h3>
                  <p>
                    A pre-configured Drupal project - structure, tools, Docker,
                    CI, and hosting - ready to build on.
                  </p>
                </article>
                <article className="pillar">
                  <span className="ic ic-teal">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="M12 6c-2-1.6-5.3-1.6-7.5 0V18c2.2-1.6 5.5-1.6 7.5 0 2-1.6 5.3-1.6 7.5 0V6c-2.2-1.6-5.5-1.6-7.5 0z" />
                      <path d="M12 6v12" />
                    </svg>
                  </span>
                  <h3>Documentation</h3>
                  <p>
                    Centralized, always-current guidance for every
                    Vortex-onboarded project and team.
                  </p>
                </article>
                <article className="pillar">
                  <span className="ic ic-cyan">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="M4.5 19.5 13.5 10.5" />
                      <path d="M17 3.4l1 2.4 2.4 1-2.4 1-1 2.4-1-2.4-2.4-1 2.4-1 1-2.4z" />
                      <path d="M7 6l.5 1.2 1.2.5-1.2.5L7 9.4l-.5-1.2L5.3 7.7l1.2-.5L7 6z" />
                    </svg>
                  </span>
                  <h3>Installer</h3>
                  <p>
                    A standalone CLI that scaffolds your project and updates it
                    to the latest Vortex version.
                  </p>
                </article>
              </div>
            </div>
          </section>

          {/* WHY */}
          <section className="section-pad" style={{ paddingTop: 0 }}>
            <div className="wrap">
              <div className="section-head reveal">
                <span className="eyebrow">Built to last</span>
                <h2 className="section-title">Why teams choose Vortex</h2>
              </div>
              <div className="why-grid stagger">
                <article className="why-card">
                  <span className="ic ic-cyan">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="M12 2.5c2.7 1.7 4.3 4.6 4.3 8 0 2.2-.6 4.2-1.7 6H9.4c-1.1-1.8-1.7-3.8-1.7-6 0-3.4 1.6-6.3 4.3-8z" />
                      <circle cx="12" cy="9.5" r="1.7" />
                      <path d="M9.4 16.5c-1.6.7-2.6 2.2-2.9 4.2 2-.3 3.5-1.3 4.2-2.9M14.6 16.5c1.6.7 2.6 2.2 2.9 4.2-2-.3-3.5-1.3-4.2-2.9" />
                    </svg>
                  </span>
                  <h3>Production-ready from day one</h3>
                  <p>
                    Skip weeks of setup. Start from a battle-tested foundation
                    refined across hundreds of real projects.
                  </p>
                </article>
                <article className="why-card">
                  <span className="ic ic-teal">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <circle cx="9" cy="8" r="3.2" />
                      <path d="M3.5 20a5.5 5.5 0 0 1 11 0" />
                      <path d="M16 5.2a3.2 3.2 0 0 1 0 5.6M20.5 20a5.5 5.5 0 0 0-3.8-5.2" />
                    </svg>
                  </span>
                  <h3>Built for teams</h3>
                  <p>
                    The same tools, commands, and workflows everywhere, so
                    anyone can move between projects without missing a beat.
                  </p>
                </article>
                <article className="why-card">
                  <span className="ic ic-blue">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="M21 12a9 9 0 1 1-2.64-6.36" />
                      <path d="M21 4v5h-5" />
                      <path d="m8.5 12 2.5 2.5L16 9.5" />
                    </svg>
                  </span>
                  <h3>Continuously tested</h3>
                  <p>
                    Every change is verified across many scenarios, making
                    upgrades safe, boring, and predictable.
                  </p>
                </article>
              </div>
            </div>
          </section>

          {/* QUICK START */}
          <section className="section-pad" style={{ paddingTop: 0 }}>
            <div className="wrap">
              <div className="section-head reveal">
                <span className="eyebrow">Get going</span>
                <h2 className="section-title">Up and running in three steps</h2>
              </div>
              <div className="steps stagger">
                <article className="step">
                  <div className="step-num">1</div>
                  <h3>Run the installer</h3>
                  <p>
                    Pull and run the installer with a single command - no global
                    tooling required.
                  </p>
                  <div className="step-code">
                    <code>
                      <span className="pr">$</span> {INSTALL_COMMAND}
                    </code>
                    <CopyMini
                      command={INSTALL_COMMAND}
                      label="Copy install command"
                    />
                  </div>
                </article>
                <article className="step">
                  <div className="step-num">2</div>
                  <h3>Answer a few prompts</h3>
                  <p>
                    Name your site and choose the integrations you need -
                    hosting, CI, services, and more.
                  </p>
                </article>
                <article className="step">
                  <div className="step-num">3</div>
                  <h3>Build and go</h3>
                  <p>
                    Spin up the whole stack. Your fully provisioned site is
                    ready to develop.
                  </p>
                  <div className="step-code">
                    <code>
                      <span className="pr">$</span> ahoy build
                    </code>
                    <CopyMini command="ahoy build" label="Copy build command" />
                  </div>
                </article>
              </div>
            </div>
          </section>

          {/* DOCS GATEWAY */}
          <section className="section-pad" style={{ paddingTop: 0 }}>
            <div className="wrap">
              <div className="section-head reveal">
                <span className="eyebrow">Keep exploring</span>
                <h2 className="section-title">Jump into the docs</h2>
              </div>
              <div className="docs-grid stagger">
                <a className="doc-card" href="/docs">
                  <span className="ic ic-cyan">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <circle cx="12" cy="12" r="9" />
                      <path d="M12 11v5M12 8h.01" />
                    </svg>
                  </span>
                  <div className="dc-body">
                    <div className="dc-top">
                      <h3>Introduction</h3>
                      <span className="arrow">
                        <svg
                          viewBox="0 0 24 24"
                          fill="none"
                          stroke="currentColor"
                          strokeWidth="2"
                          strokeLinecap="round"
                          strokeLinejoin="round"
                        >
                          <path d="M7 17 17 7M9 7h8v8" />
                        </svg>
                      </span>
                    </div>
                    <p>What Vortex is and why it exists.</p>
                  </div>
                </a>
                <a className="doc-card" href="/docs/features">
                  <span className="ic ic-teal">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <rect x="3.5" y="3.5" width="7" height="7" rx="1.5" />
                      <rect x="13.5" y="3.5" width="7" height="7" rx="1.5" />
                      <rect x="3.5" y="13.5" width="7" height="7" rx="1.5" />
                      <rect x="13.5" y="13.5" width="7" height="7" rx="1.5" />
                    </svg>
                  </span>
                  <div className="dc-body">
                    <div className="dc-top">
                      <h3>Features</h3>
                      <span className="arrow">
                        <svg
                          viewBox="0 0 24 24"
                          fill="none"
                          stroke="currentColor"
                          strokeWidth="2"
                          strokeLinecap="round"
                          strokeLinejoin="round"
                        >
                          <path d="M7 17 17 7M9 7h8v8" />
                        </svg>
                      </span>
                    </div>
                    <p>Everything included, in detail.</p>
                  </div>
                </a>
                <a className="doc-card" href="/docs/installation">
                  <span className="ic ic-blue">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="M12 3v11m0 0-4-4m4 4 4-4" />
                      <path d="M4 16v2.5A2.5 2.5 0 0 0 6.5 21h11a2.5 2.5 0 0 0 2.5-2.5V16" />
                    </svg>
                  </span>
                  <div className="dc-body">
                    <div className="dc-top">
                      <h3>Installation</h3>
                      <span className="arrow">
                        <svg
                          viewBox="0 0 24 24"
                          fill="none"
                          stroke="currentColor"
                          strokeWidth="2"
                          strokeLinecap="round"
                          strokeLinejoin="round"
                        >
                          <path d="M7 17 17 7M9 7h8v8" />
                        </svg>
                      </span>
                    </div>
                    <p>Install or update your project.</p>
                  </div>
                </a>
                <a className="doc-card" href="/docs/development">
                  <span className="ic ic-cyan">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="m8 8-4 4 4 4M16 8l4 4-4 4M13.5 6l-3 12" />
                    </svg>
                  </span>
                  <div className="dc-body">
                    <div className="dc-top">
                      <h3>Development</h3>
                      <span className="arrow">
                        <svg
                          viewBox="0 0 24 24"
                          fill="none"
                          stroke="currentColor"
                          strokeWidth="2"
                          strokeLinecap="round"
                          strokeLinejoin="round"
                        >
                          <path d="M7 17 17 7M9 7h8v8" />
                        </svg>
                      </span>
                    </div>
                    <p>Day-to-day workflows and tooling.</p>
                  </div>
                </a>
                <a className="doc-card" href="/docs/architecture">
                  <span className="ic ic-teal">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <rect x="3.5" y="3.5" width="7" height="7" rx="1.5" />
                      <rect x="14" y="3.5" width="6.5" height="4.5" rx="1.5" />
                      <rect x="14" y="11" width="6.5" height="9.5" rx="1.5" />
                      <rect x="3.5" y="13.5" width="7" height="7" rx="1.5" />
                    </svg>
                  </span>
                  <div className="dc-body">
                    <div className="dc-top">
                      <h3>Architecture</h3>
                      <span className="arrow">
                        <svg
                          viewBox="0 0 24 24"
                          fill="none"
                          stroke="currentColor"
                          strokeWidth="2"
                          strokeLinecap="round"
                          strokeLinejoin="round"
                        >
                          <path d="M7 17 17 7M9 7h8v8" />
                        </svg>
                      </span>
                    </div>
                    <p>How the pieces fit together.</p>
                  </div>
                </a>
                <a className="doc-card" href="/docs/support">
                  <span className="ic ic-blue">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <circle cx="12" cy="12" r="9" />
                      <circle cx="12" cy="12" r="3.4" />
                      <path d="m5.6 5.6 3.2 3.2M15.2 15.2l3.2 3.2M18.4 5.6l-3.2 3.2M8.8 15.2l-3.2 3.2" />
                    </svg>
                  </span>
                  <div className="dc-body">
                    <div className="dc-top">
                      <h3>Support</h3>
                      <span className="arrow">
                        <svg
                          viewBox="0 0 24 24"
                          fill="none"
                          stroke="currentColor"
                          strokeWidth="2"
                          strokeLinecap="round"
                          strokeLinejoin="round"
                        >
                          <path d="M7 17 17 7M9 7h8v8" />
                        </svg>
                      </span>
                    </div>
                    <p>Get help from the community.</p>
                  </div>
                </a>
              </div>
            </div>
          </section>

          {/* CTA */}
          <section className="section-pad cta" style={{ paddingTop: 0 }}>
            <div className="wrap">
              <div className="cta-inner reveal">
                <h2>Start your next Drupal project on solid ground.</h2>
                <p>
                  Free, open source, and trusted in production across
                  government, healthcare, education, and finance.
                </p>
                <InstallSnippet />
                <div className="cta-actions">
                  <a className="btn btn-primary" href="/docs/installation">
                    Get started
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth="2.2"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                    >
                      <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                  </a>
                  <a
                    className="btn btn-ghost"
                    href="https://github.com/drevops/vortex"
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    <svg viewBox="0 0 24 24" fill="currentColor">
                      <path d="M12 2C6.48 2 2 6.58 2 12.25c0 4.53 2.87 8.37 6.84 9.73.5.1.68-.22.68-.49l-.01-1.9c-2.78.62-3.37-1.2-3.37-1.2-.46-1.18-1.11-1.49-1.11-1.49-.91-.64.07-.62.07-.62 1 .07 1.53 1.06 1.53 1.06.89 1.56 2.34 1.11 2.91.85.09-.66.35-1.11.63-1.37-2.22-.26-4.55-1.14-4.55-5.07 0-1.12.39-2.03 1.03-2.75-.1-.26-.45-1.3.1-2.71 0 0 .84-.28 2.75 1.05a9.4 9.4 0 0 1 5 0c1.91-1.33 2.75-1.05 2.75-1.05.55 1.41.2 2.45.1 2.71.64.72 1.03 1.63 1.03 2.75 0 3.94-2.34 4.81-4.57 5.06.36.32.68.94.68 1.9l-.01 2.82c0 .27.18.59.69.49A10.26 10.26 0 0 0 22 12.25C22 6.58 17.52 2 12 2z" />
                    </svg>
                    Star on GitHub
                  </a>
                </div>
              </div>
            </div>
          </section>
        </main>

        {playerOpen && (
          <div
            className="vtx-modal"
            role="dialog"
            aria-modal="true"
            aria-label="Vortex installer demo"
            onClick={closeOnBackdrop}
          >
            <div className="vtx-modal-panel" ref={modalRef}>
              <div className="vtx-modal-bar">
                <span className="media-dots">
                  <span className="r" />
                  <span className="y" />
                  <span className="g" />
                </span>
                <span className="vtx-modal-title">
                  vortex installer - full demo
                </span>
                <button
                  type="button"
                  className="vtx-modal-close"
                  onClick={() => setPlayerOpen(false)}
                  ref={closeRef}
                  aria-label="Close demo"
                >
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                  >
                    <path d="M18 6 6 18M6 6l12 12" />
                  </svg>
                </button>
              </div>
              <div className="vtx-modal-body">
                <AsciinemaPlayer
                  src={installerCast}
                  poster="npt:0:1"
                  autoPlay
                  loop={false}
                  controls
                  preload
                  className="vtx-cast"
                />
              </div>
            </div>
          </div>
        )}
      </div>
    </Layout>
  );
}
