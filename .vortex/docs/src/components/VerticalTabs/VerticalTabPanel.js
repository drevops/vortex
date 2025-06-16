/**
 * VerticalTabPanel component - defines tab content/panel
 *
 * This is a marker component that gets parsed by VerticalTabs.
 * The content is used as the panel content for the corresponding tab.
 */
const VerticalTabPanel = ({ children }) => {
  // This is a marker component - the actual rendering is handled by VerticalTabs
  return children;
};

// Set displayName for production build compatibility
VerticalTabPanel.displayName = 'VerticalTabPanel';

export default VerticalTabPanel;
