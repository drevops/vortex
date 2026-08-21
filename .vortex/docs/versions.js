// Documentation version resolution.
//
// 'VORTEX_CURRENT_MAJOR' names the major served as the default version at the
// bare '/docs'; the other major is served at '/docs/v{other}'. Bumping it
// promotes a new major. 'VORTEX_DOCS_COMBINED' opts into serving both majors
// from one build - unset, the site is a single unversioned set and neither
// export contributes anything.

const combined = process.env.VORTEX_DOCS_COMBINED === '1';
const currentMajor = process.env.VORTEX_CURRENT_MAJOR || '1';
const otherMajor = currentMajor === '1' ? '2' : '1';
const currentVersion = `${currentMajor}.x`;

/** Version options for the docs preset. */
export const docsVersions = combined ? {
  lastVersion: currentVersion,
  versions: {
    [currentVersion]: {
      label: `v${currentMajor}`,
    },
    current: {
      label: `v${otherMajor}`,
      path: `v${otherMajor}`,
      banner: Number(otherMajor) > Number(currentMajor) ? 'unreleased' : 'unmaintained',
    },
  },
} : {};

/** Navbar items for the version switcher. */
export const versionNavbarItems = combined ? [{type: 'docsVersionDropdown', position: 'right'}] : [];

/** Redirects from the current major's explicit path to the default it is served at. */
export const versionRedirects = combined ? [{from: `/docs/v${currentMajor}`, to: '/docs'}] : [];
