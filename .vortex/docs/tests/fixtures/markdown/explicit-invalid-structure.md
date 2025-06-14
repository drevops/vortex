import { VerticalTabsExplicit, Tab, TabPanel } from '@site/src/components/VerticalTabs';

# Explicit Tab Structure - Invalid Examples

## Missing Tab Components
<VerticalTabsExplicit>
  <!-- No Tab components, should show error -->
</VerticalTabsExplicit>

## Empty VerticalTabsExplicit
<VerticalTabsExplicit>
</VerticalTabsExplicit>

## Tab Without TabPanel
<VerticalTabsExplicit>
  <Tab icon="💧" title="Missing TabPanel" description="No TabPanel wrapper" badge="Error">
    Direct content without TabPanel wrapper - should still work but not ideal.
  </Tab>
</VerticalTabsExplicit>

## Tab With Missing Required Props
<VerticalTabsExplicit>
  <Tab description="Missing title and icon" badge="Incomplete">
    <TabPanel>
      Content for tab with missing required props.
    </TabPanel>
  </Tab>
</VerticalTabsExplicit>

## Tab With Empty Props
<VerticalTabsExplicit>
  <Tab icon="" title="" description="" badge="">
    <TabPanel>
      Content for tab with empty props.
    </TabPanel>
  </Tab>
</VerticalTabsExplicit>

## Mixed Valid and Invalid Tabs
<VerticalTabsExplicit>
  <Tab icon="✅" title="Valid Tab" description="This one is correct" badge="Good">
    <TabPanel>
      Valid content here.
    </TabPanel>
  </Tab>
  
  <!-- Invalid: Missing title -->
  <Tab icon="❌" description="Missing title" badge="Bad">
    <TabPanel>
      Invalid content - missing title.
    </TabPanel>
  </Tab>
  
  <Tab title="Another Valid" description="This one works too" badge="Good">
    <TabPanel>
      Another valid tab (missing icon should use default).
    </TabPanel>
  </Tab>
  
  <!-- Invalid: No props at all -->
  <Tab>
    <TabPanel>
      Tab with no metadata props.
    </TabPanel>
  </Tab>
</VerticalTabsExplicit>

## Nested Complex Invalid Structure
<VerticalTabsExplicit>
  <Tab icon="🔧" title="Complex Invalid" description="Complex structure issues" badge="Complex">
    <TabPanel>
      <Tab icon="🚫" title="Nested Tab" description="This shouldn't work" badge="Nested">
        <TabPanel>
          Nested tab inside another tab - should be ignored.
        </TabPanel>
      </Tab>
      
      Regular content that should work fine.
      
      <VerticalTabsExplicit>
        <Tab icon="🚫" title="Nested Tabs Component" description="This shouldn't work" badge="Nested">
          <TabPanel>
            Nested VerticalTabsExplicit component - should be treated as regular content.
          </TabPanel>
        </Tab>
      </VerticalTabsExplicit>
    </TabPanel>
  </Tab>
</VerticalTabsExplicit>