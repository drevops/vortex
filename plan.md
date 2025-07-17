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
✅ **Name** - Complete (4 test cases)
✅ **MachineName** - Complete (3 test cases) - missing invalid discovery
✅ **Org** - Complete (3 test cases) - missing invalid discovery  
✅ **OrgMachineName** - Complete (3 test cases) - missing invalid discovery
✅ **Domain** - Complete (7 test cases) - has additional variations
✅ **CodeProvider** - Partial (2 test cases) - missing prompt and invalid prompt
✅ **Profile** - Complete (4 test cases)
✅ **ModulePrefix** - Complete (6 test cases) - has additional variations
✅ **Theme** - Complete (4 test cases)
✅ **Services** - Complete (7 test cases) - has additional variations
✅ **HostingProvider** - Complete (4 test cases) - has additional variations
✅ **Webroot** - Complete (4 test cases) - has additional variations
✅ **DeployType** - Partial (1 test case) - missing prompt, invalid prompt, invalid discovery
✅ **ProvisionType** - Partial (2 test cases) - missing prompt and invalid prompt
✅ **DatabaseDownloadSource** - Missing all test cases
✅ **DatabaseImage** - Complete (4 test cases)
✅ **CiProvider** - Complete (3 test cases) - missing invalid prompt
✅ **DependencyUpdatesProvider** - Complete (4 test cases)
✅ **AssignAuthorPr** - Complete (3 test cases) - missing invalid prompt
✅ **LabelMergeConflictsPr** - Complete (3 test cases) - missing invalid prompt
✅ **PreserveDocsProject** - Complete (3 test cases) - missing invalid prompt
✅ **PreserveDocsOnboarding** - Partial (1 test case) - missing prompt, invalid prompt, invalid discovery
✅ **AiCodeInstructions** - Complete (3 test cases) - missing invalid prompt

## Missing Test Cases to Add

### 1. CodeProvider
- **Prompt**: Test user selecting GitHub/other through prompt
- **Invalid Prompt**: Test invalid selection (if applicable)

### 2. DeployType  
- **Prompt**: Test user selecting deployment types through prompt
- **Invalid Prompt**: Test invalid deployment type selection
- **Invalid Discovery**: Test scenarios where discovery fails

### 3. ProvisionType
- **Prompt**: Test user selecting provision type through prompt  
- **Invalid Prompt**: Test invalid provision type selection

### 4. DatabaseDownloadSource
- **Prompt**: Test user selecting database download source through prompt
- **Invalid Prompt**: Test invalid database download source selection  
- **Discovery**: Test automatic discovery from environment/files
- **Invalid Discovery**: Test discovery scenarios that should fail

### 5. CiProvider
- **Invalid Prompt**: Test invalid CI provider selection (if applicable)

### 6. PreserveDocsOnboarding
- **Prompt**: Test user selecting preserve docs onboarding through prompt
- **Invalid Prompt**: Test invalid selection (if applicable)
- **Invalid Discovery**: Test discovery scenarios that should fail

### 7. AiCodeInstructions
- **Invalid Prompt**: Test invalid AI code instructions selection (if applicable)

### 8. AssignAuthorPr
- **Invalid Prompt**: Test invalid assign author PR selection (if applicable)

### 9. LabelMergeConflictsPr  
- **Invalid Prompt**: Test invalid label merge conflicts PR selection (if applicable)

### 10. PreserveDocsProject
- **Invalid Prompt**: Test invalid preserve docs project selection (if applicable)

### 11. MachineName
- **Invalid Discovery**: Test discovery scenarios that should fail or not match

### 12. Org
- **Invalid Discovery**: Test discovery scenarios that should fail or not match

### 13. OrgMachineName
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

## Priority Order
1. **High Priority**: Handlers missing all 4 test categories
2. **Medium Priority**: Handlers missing 2-3 test categories  
3. **Low Priority**: Handlers missing only 1 test category (usually invalid prompt/discovery)

## Notes
- Some handlers may not support all 4 test categories (e.g., boolean handlers may not have invalid prompt tests)
- Discovery tests should use realistic file/environment scenarios
- Invalid discovery tests should verify that discovery doesn't match inappropriate scenarios
- All test cases should be thoroughly tested after implementation

## Success Criteria
- All prompt handlers have comprehensive test coverage
- Test cases follow consistent naming and structure conventions
- All tests pass when run with `composer test`
- Code coverage remains high or improves
- Test cases accurately reflect real-world usage scenarios