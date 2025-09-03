// Import directly from index to ensure coverage tracking
import {
  VerticalTabs,
  VerticalTab,
  VerticalTabPanel,
} from '../../../src/components/VerticalTabs/index.js';

describe('VerticalTabs index exports', () => {
  test('exports all components as functions with correct properties', () => {
    // Test all components are exported and are functions
    expect(typeof VerticalTabs).toBe('function');
    expect(typeof VerticalTab).toBe('function');
    expect(typeof VerticalTabPanel).toBe('function');

    // Test component identifiers
    expect(VerticalTabs.name).toBe('VerticalTabs');
    expect(VerticalTab.displayName).toBe('VerticalTab');
    expect(VerticalTabPanel.displayName).toBe('VerticalTabPanel');
  });
});
