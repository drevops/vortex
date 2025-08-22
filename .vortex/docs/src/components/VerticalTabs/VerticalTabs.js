import React, { useState, useEffect, Children, isValidElement } from 'react';
import './VerticalTabs.css';

// Helper function to check if a child is a VerticalTab component
const isVerticalTab = child => {
  if (!isValidElement(child)) {
    return false;
  }

  // Check by component type name
  if (child.type && child.type.name === 'VerticalTab') {
    return true;
  }

  // Check by displayName (for MDX)
  if (child.type && child.type.displayName === 'VerticalTab') {
    return true;
  }

  // Check by data attribute as fallback
  if (child.props && child.props['data-component'] === 'VerticalTab') {
    return true;
  }

  return false;
};

// Helper function to check if a child is a VerticalTabPanel component
const isVerticalTabPanel = child => {
  if (!isValidElement(child)) {
    return false;
  }

  // Check by component type name
  if (child.type && child.type.name === 'VerticalTabPanel') {
    return true;
  }

  // Check by displayName (for MDX)
  if (child.type && child.type.displayName === 'VerticalTabPanel') {
    return true;
  }

  // Check by data attribute as fallback
  if (child.props && child.props['data-component'] === 'VerticalTabPanel') {
    return true;
  }

  return false;
};

// Helper function to extract text from React elements
const extractText = element => {
  if (typeof element === 'string') {
    return element;
  }
  if (typeof element === 'number') {
    return element.toString();
  }
  if (Array.isArray(element)) {
    return element.map(extractText).join('');
  }
  if (
    React.isValidElement(element) &&
    element.props &&
    element.props.children
  ) {
    return extractText(element.props.children);
  }
  return '';
};

// Helper function to create a URL-friendly slug from tab title
const createSlug = title => {
  return title
    .toLowerCase()
    .replace(/[^\w\s-]/g, '') // Remove special characters except spaces and hyphens
    .replace(/\s+/g, '-') // Replace spaces with hyphens
    .replace(/-+/g, '-') // Replace multiple hyphens with single hyphen
    .trim('-'); // Remove leading/trailing hyphens
};

const VerticalTabs = ({ children }) => {
  const [activeTab, setActiveTab] = useState(0);

  // Parse children to find VerticalTab and VerticalTabPanel components
  const childrenArray = Children.toArray(children);
  const tabElements = childrenArray.filter(isVerticalTab);
  const panelElements = childrenArray.filter(isVerticalTabPanel);

  // Match tabs with panels by order
  const tabs = tabElements.map((tabElement, index) => {
    const panelElement = panelElements[index] || null;

    // Extract tab content (should be the title/description text)
    const tabContent = extractText(tabElement.props.children);

    // Parse tab content - format: "💧 Drupal Foundation | Core platform"
    const parts = tabContent.split(' | ');
    const titlePart = parts[0] || '';
    const shortDesc = parts[1] || '';

    // Extract icon and title from first part
    const iconMatch = titlePart.match(/^(\S+)\s+(.+)$/);
    const icon = iconMatch ? iconMatch[1] : '📄';
    const title = iconMatch ? iconMatch[2] : titlePart || 'Untitled Tab';

    return {
      icon,
      title,
      shortDesc,
      slug: createSlug(title),
      content: panelElement ? panelElement.props.children : null,
    };
  });

  // Effect to handle hash changes and set active tab from URL hash
  useEffect(() => {
    const handleHashChange = () => {
      const hash = window.location.hash.slice(1); // Remove the # symbol
      if (hash) {
        const tabIndex = tabs.findIndex(tab => tab.slug === hash);
        if (tabIndex !== -1) {
          setActiveTab(tabIndex);
        }
      }
    };

    // Set initial active tab from hash on mount
    handleHashChange();

    // Listen for hash changes
    window.addEventListener('hashchange', handleHashChange);

    return () => {
      window.removeEventListener('hashchange', handleHashChange);
    };
  }, [tabs]);

  // Function to handle tab click and update URL hash
  const handleTabClick = index => {
    setActiveTab(index);
    const slug = tabs[index]?.slug;
    if (slug) {
      // Update URL hash without triggering page scroll
      const newUrl = `${window.location.pathname}${window.location.search}#${slug}`;
      window.history.replaceState(null, '', newUrl);
    }
  };

  if (tabs.length === 0) {
    return (
      <div>
        <p>No tabs found. Use VerticalTab and VerticalTabPanel components:</p>
        <pre>{`<VerticalTabs>
  <VerticalTab>💧 Title | Description</VerticalTab>
  <VerticalTabPanel>Content here</VerticalTabPanel>
</VerticalTabs>`}</pre>
      </div>
    );
  }

  return (
    <div className="vertical-features">
      <div className="vertical-layout">
        <div className="sidebar">
          <div className="sidebar-header">
            <h3>Feature Categories</h3>
          </div>
          <div className="tab-list" role="tablist">
            {tabs.map((tab, index) => (
              <div
                key={index}
                className={`tab-item ${activeTab === index ? 'active' : ''}`}
                onClick={() => handleTabClick(index)}
              >
                <div className="tab-content">
                  <h4>
                    <span className="tab-icon">{tab.icon}</span>
                    {tab.title}
                  </h4>
                  <p>{tab.shortDesc}</p>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="content-area">
          {tabs[activeTab] && (
            <div className="content-panel">
              <div className="content-header">
                <h2>
                  {tabs[activeTab].icon} {tabs[activeTab].title}
                </h2>
                {tabs[activeTab].shortDesc && (
                  <p className="content-subtitle">
                    {tabs[activeTab].shortDesc}
                  </p>
                )}
              </div>

              <div className="content-body">{tabs[activeTab].content}</div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default VerticalTabs;
