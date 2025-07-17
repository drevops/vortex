# Test Case Expansion Plan for PromptManagerTest::dataProviderRunPrompts()

## Overview
This plan outlines the expansion of test cases for the `dataProviderRunPrompts()` method in `.vortex/installer/tests/Unit/PromptManagerTest.php`. Currently, the `Name` prompt type has comprehensive test coverage with 4 categories: prompt, invalid prompt, discovery, and invalid discovery. This plan extends the same pattern to all other prompt types.

## Test Pattern Structure
Each prompt type should have these 4 test case categories:
1. **Prompt** - Valid user input through prompt
2. **Invalid Prompt** - Invalid user input with error message
3. **Discovery** - Automatic discovery from existing files/environment
4. **Invalid Discovery** - Discovery scenarios that should fail or not match

## Current Status
✅ **Name** - Complete (6 test cases) - All 4 categories covered
✅ **MachineName** - Complete (5 test cases) - All 4 categories covered
✅ **Org** - Complete (4 test cases) - All 4 categories covered
✅ **OrgMachineName** - Complete (5 test cases) - All 4 categories covered
✅ **Domain** - Complete (8 test cases) - All 4 categories covered
✅ **CodeProvider** - Complete (5 test cases) - All 4 categories covered
✅ **Profile** - Complete (6 test cases) - All 4 categories covered
⚠️ **ModulePrefix** - Nearly complete (8 test cases) - Missing: invalid discovery
⚠️ **Theme** - Nearly complete (5 test cases) - Missing: invalid discovery
⚠️ **Services** - Partial (7 test cases) - Missing: prompt, invalid prompt
⚠️ **HostingProvider** - Partial (3 test cases) - Missing: prompt, invalid prompt, invalid discovery
⚠️ **Webroot** - Nearly complete (5 test cases) - Missing: invalid discovery
⚠️ **DeployType** - Minimal (1 test case) - Missing: prompt, invalid prompt, invalid discovery
⚠️ **ProvisionType** - Partial (2 test cases) - Missing: prompt, invalid prompt, invalid discovery
❌ **DatabaseDownloadSource** - No test cases - Missing: all 4 categories
⚠️ **DatabaseImage** - Nearly complete (4 test cases) - Missing: invalid discovery
⚠️ **CiProvider** - Partial (3 test cases) - Missing: prompt, invalid prompt, invalid discovery
⚠️ **DependencyUpdatesProvider** - Partial (4 test cases) - Missing: prompt, invalid prompt, invalid discovery
⚠️ **AssignAuthorPr** - Partial (3 test cases) - Missing: prompt, invalid prompt, invalid discovery
⚠️ **LabelMergeConflictsPr** - Partial (3 test cases) - Missing: prompt, invalid prompt, invalid discovery
⚠️ **PreserveDocsProject** - Partial (3 test cases) - Missing: prompt, invalid prompt, invalid discovery
⚠️ **PreserveDocsOnboarding** - Minimal (1 test case) - Missing: prompt, invalid prompt, invalid discovery
⚠️ **AiCodeInstructions** - Partial (3 test cases) - Missing: prompt, invalid prompt, invalid discovery
❌ **Internal** - No test cases - Missing: all 4 categories

## Missing Test Cases to Add

### Priority 1: Handlers Missing All 4 Categories

#### DatabaseDownloadSource
- **Prompt**: Test user selecting database download source through prompt
- **Invalid Prompt**: Test invalid database download source selection
- **Discovery**: Test automatic discovery from environment/files
- **Invalid Discovery**: Test discovery scenarios that should fail

#### Internal
- **Prompt**: Test internal prompts (if applicable)
- **Invalid Prompt**: Test invalid internal prompts
- **Discovery**: Test internal discovery
- **Invalid Discovery**: Test invalid internal discovery

### Priority 2: Handlers Missing 3 Categories

#### CodeProvider
- **Prompt**: Test user selecting GitHub/other through prompt
- **Invalid Prompt**: Test invalid code provider selection
- **Invalid Discovery**: Test scenarios where discovery fails

#### DeployType
- **Prompt**: Test user selecting deployment types through prompt
- **Invalid Prompt**: Test invalid deployment type selection
- **Invalid Discovery**: Test scenarios where discovery fails

#### ProvisionType
- **Prompt**: Test user selecting provision type through prompt
- **Invalid Prompt**: Test invalid provision type selection
- **Invalid Discovery**: Test scenarios where discovery fails

#### HostingProvider
- **Prompt**: Test user selecting hosting provider through prompt
- **Invalid Prompt**: Test invalid hosting provider selection
- **Invalid Discovery**: Test scenarios where discovery fails

#### CiProvider
- **Prompt**: Test user selecting CI provider through prompt
- **Invalid Prompt**: Test invalid CI provider selection
- **Invalid Discovery**: Test scenarios where discovery fails

#### DependencyUpdatesProvider
- **Prompt**: Test user selecting dependency updates provider through prompt
- **Invalid Prompt**: Test invalid dependency updates provider selection
- **Invalid Discovery**: Test scenarios where discovery fails

#### AssignAuthorPr
- **Prompt**: Test user selecting assign author PR through prompt
- **Invalid Prompt**: Test invalid assign author PR selection
- **Invalid Discovery**: Test scenarios where discovery fails

#### LabelMergeConflictsPr
- **Prompt**: Test user selecting label merge conflicts through prompt
- **Invalid Prompt**: Test invalid label merge conflicts selection
- **Invalid Discovery**: Test scenarios where discovery fails

#### PreserveDocsProject
- **Prompt**: Test user selecting preserve docs project through prompt
- **Invalid Prompt**: Test invalid preserve docs project selection
- **Invalid Discovery**: Test scenarios where discovery fails

#### PreserveDocsOnboarding
- **Prompt**: Test user selecting preserve docs onboarding through prompt
- **Invalid Prompt**: Test invalid preserve docs onboarding selection
- **Invalid Discovery**: Test scenarios where discovery fails

#### AiCodeInstructions
- **Prompt**: Test user selecting AI code instructions through prompt
- **Invalid Prompt**: Test invalid AI code instructions selection
- **Invalid Discovery**: Test scenarios where discovery fails

### Priority 3: Handlers Missing 2 Categories

#### MachineName
- **Prompt**: Test user providing valid machine name via prompt
- **Invalid Discovery**: Test discovery scenarios that should fail or not match

#### Org
- **Prompt**: Test user providing valid org name via prompt
- **Invalid Discovery**: Test discovery scenarios that should fail or not match

#### OrgMachineName
- **Prompt**: Test user providing valid org machine name via prompt
- **Invalid Discovery**: Test discovery scenarios that should fail or not match

#### Services
- **Prompt**: Test user selecting services via prompt
- **Invalid Prompt**: Test invalid service selection

### Priority 4: Handlers Missing 1 Category

#### Domain
- **Invalid Discovery**: Test discovery scenarios that should fail or not match

#### Profile
- **Invalid Discovery**: Test discovery scenarios that should fail or not match

#### ModulePrefix
- **Invalid Discovery**: Test discovery scenarios that should fail or not match

#### Theme
- **Invalid Discovery**: Test discovery scenarios that should fail or not match

#### Webroot
- **Invalid Discovery**: Test discovery scenarios that should fail or not match

#### DatabaseImage
- **Invalid Discovery**: Test discovery scenarios that should fail or not match

## Implementation Strategy

### Phase 1: Research Existing Handler Logic
For each prompt type, examine the corresponding handler class in `.vortex/installer/src/Prompts/Handlers/` to understand:
- Available options/values
- Validation rules
- Discovery mechanisms
- Error conditions

### Phase 2: Create Test Cases
For each missing test case:
1. **Prompt Tests**: Create test cases that simulate user input
2. **Invalid Prompt Tests**: Create test cases with invalid input and expected error messages
3. **Discovery Tests**: Create test cases that set up environment/files for automatic discovery
4. **Invalid Discovery Tests**: Create test cases where discovery should fail or not match

### Phase 3: Validation
- Ensure all test cases follow the existing naming convention
- Verify test cases use appropriate test data setup (stubbing methods)
- Confirm error messages match actual handler validation messages
- Test that discovery logic works with realistic file/environment scenarios

## Test Case Naming Convention
Follow the existing pattern:
- `'[handler name] - prompt'`
- `'[handler name] - prompt - invalid'`
- `'[handler name] - discovery'` (with descriptive suffix if multiple)
- `'[handler name] - discovery - invalid'` (with descriptive suffix if multiple)

## Expected Test Data Structure
Each test case should follow this structure:
```php
'test case name' => [
    [HandlerClass::id() => 'input_value'], // User input simulation
    $expected_output_or_error_message,      // Expected result or error message
    function (PromptManagerTest $test): void { // Optional setup function
        // Set up environment, files, etc.
    },
],
```

## Key Learnings from Implementation

### MachineName Handler Implementation
- **Individual field changes**: When only **MachineName** changes via prompt, only the machine_name and its derived fields (domain, module_prefix, theme) update, while name and org remain at defaults
- **Proper expected values**: For machine name prompt tests, must specify only the fields that actually change:
  ```php
  [
    MachineName::id() => 'prompted_project',
    Domain::id() => 'prompted-project.com',
    ModulePrefix::id() => 'pp',
    Theme::id() => 'prompted_project',
  ] + $expected_defaults
  ```
- **Order matters**: Must follow exact order: prompt → invalid prompt → discovery → invalid discovery
- **Naming convention**: Use descriptive suffixes for invalid discovery (e.g., "unmatched", "invalid") to match existing patterns

## Notes
- Some handlers may not support all 4 test categories (e.g., boolean handlers may not have invalid prompt tests)
- Discovery tests should use realistic file/environment scenarios
- Invalid discovery tests should verify that discovery doesn't match inappropriate scenarios
- All test cases should be thoroughly tested after implementation
- **Critical**: Each handler affects different derived fields - cannot reuse expected value arrays across handlers

## Success Criteria
- All prompt handlers have comprehensive test coverage
- Test cases follow consistent naming and structure conventions
- All tests pass when run with `composer test -- --filter=PromptManagerTest`
- Test cases accurately reflect real-world usage scenarios
