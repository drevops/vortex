import React from 'react';
import useBrokenLinks from '@docusaurus/useBrokenLinks';

// The Docusaurus broken-anchor checker collects anchors from headings and
// links only, so a bare '<a id>' target is reported as broken.
function Anchor({ id }) {
  useBrokenLinks().collectAnchor(id);

  return <a id={id} />;
}

export default Anchor;
