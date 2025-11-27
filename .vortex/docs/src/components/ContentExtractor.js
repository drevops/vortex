import React from 'react';

/**
 * Extracts and renders specific content from MDX files without page metadata
 *
 * This component allows you to reuse content from documentation pages
 * in other parts of your site without including titles, frontmatter, etc.
 *
 * Usage:
 *
 * // Create a content extractor for your features page
 * import FeaturesPageContent from '../../content/features.mdx';
 *
 * <ContentExtractor>
 *   <FeaturesPageContent />
 * </ContentExtractor>
 *
 * // Or with filtering options
 * <ContentExtractor
 *   stripFirstHeading={true}
 *   stripFrontmatter={true}
 *   stripIntro={true}
 *   startFromSelector="[data-component='VerticalTabs']"
 *   className="features-content"
 * >
 *   <FeaturesPageContent />
 * </ContentExtractor>
 */
const ContentExtractor = ({
  children,
  stripFirstHeading = true,
  stripIntro = false,
  stripFrontmatter = true,
  startFromSelector = null,
  endAtSelector = null,
  className = 'mdx-content-extracted',
}) => {
  const contentRef = React.useRef(null);

  React.useEffect(() => {
    /* istanbul ignore next */
    if (!contentRef.current) {
      return;
    }

    const container = contentRef.current;

    // Get the first child div that contains the actual content
    const contentDiv = container.firstElementChild;
    if (!contentDiv) {
      return;
    }

    // Clone the container to avoid modifying during iteration
    const elementsToRemove = [];

    // Handle startFromSelector first (affects what we work with)
    let workingElements = Array.from(contentDiv.children);
    if (startFromSelector) {
      const startElement = contentDiv.querySelector(startFromSelector);
      if (startElement) {
        const startIndex = workingElements.indexOf(startElement);
        if (startIndex > 0) {
          // Remove everything before the start element
          for (let i = 0; i < startIndex; i++) {
            elementsToRemove.push(workingElements[i]);
          }
          // Update working elements to only include from start onwards
          workingElements = workingElements.slice(startIndex);
        }
      }
    }

    // Handle endAtSelector
    if (endAtSelector) {
      const endElement = contentDiv.querySelector(endAtSelector);
      if (endElement && workingElements.includes(endElement)) {
        const endIndex = workingElements.indexOf(endElement);
        if (endIndex >= 0) {
          // Remove from end element onwards
          for (let i = endIndex; i < workingElements.length; i++) {
            elementsToRemove.push(workingElements[i]);
          }
        }
      }
    }

    // Remove frontmatter elements if requested
    if (stripFrontmatter) {
      const frontmatterElements = contentDiv.querySelectorAll(
        '[data-frontmatter], .frontmatter, .metadata'
      );
      frontmatterElements.forEach(element => {
        elementsToRemove.push(element);
      });
    }

    // Remove first h1 if requested
    if (stripFirstHeading) {
      const firstH1 = contentDiv.querySelector('h1');
      if (firstH1) {
        elementsToRemove.push(firstH1);
      }
    }

    // Remove intro paragraph if requested
    if (stripIntro) {
      const paragraphs = contentDiv.querySelectorAll('p');
      paragraphs.forEach(p => {
        if (p.textContent && p.textContent.includes('Select a feature')) {
          elementsToRemove.push(p);
        }
      });
    }

    // Remove all marked elements
    elementsToRemove.forEach(element => {
      if (element && element.parentNode) {
        element.parentNode.removeChild(element);
      }
    });
  }, [
    stripFirstHeading,
    stripIntro,
    stripFrontmatter,
    startFromSelector,
    endAtSelector,
  ]);

  return (
    <div ref={contentRef} className={className}>
      {children}
    </div>
  );
};

export default ContentExtractor;
