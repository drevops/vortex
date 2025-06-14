/**
 * VerticalTab component - defines a tab header/title
 *
 * This is a marker component that gets parsed by VerticalTabs.
 * The content is used as the tab title and metadata.
 */
const VerticalTab = ({ children }) => {
  // This is a marker component - the actual rendering is handled by VerticalTabs
  return children;
};

// Set displayName for production build compatibility
VerticalTab.displayName = 'VerticalTab';

export default VerticalTab;
