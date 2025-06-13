import React, { useState, Children, isValidElement } from 'react';
import './VerticalTabs.css';

// Helper function to check if a child is a VerticalTab component
const isVerticalTab = child => {
  if (!isValidElement(child)) return false;

  // Check by component type name
  if (child.type && child.type.name === 'VerticalTab') return true;

  // Check by displayName (for MDX)
  if (child.type && child.type.displayName === 'VerticalTab') return true;

  // Check by data attribute as fallback
  if (child.props && child.props['data-component'] === 'VerticalTab')
    return true;

  return false;
};

// Helper function to check if a child is a VerticalTabPanel component
const isVerticalTabPanel = child => {
  if (!isValidElement(child)) return false;

  // Check by component type name
  if (child.type && child.type.name === 'VerticalTabPanel') return true;

  // Check by displayName (for MDX)
  if (child.type && child.type.displayName === 'VerticalTabPanel') return true;

  // Check by data attribute as fallback
  if (child.props && child.props['data-component'] === 'VerticalTabPanel')
    return true;

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
      content: panelElement ? panelElement.props.children : null,
    };
  });

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
          <div className="tab-list">
            {tabs.map((tab, index) => (
              <div
                key={index}
                className={`tab-item ${activeTab === index ? 'active' : ''}`}
                onClick={() => setActiveTab(index)}
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
                <h1>
                  {tabs[activeTab].icon} {tabs[activeTab].title}
                </h1>
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
