# Vortex Installer Refactoring Plan: Prompt Property Decoupling

## Executive Summary

This plan outlines the refactoring of the Vortex installer's prompt system to decouple prompt property logic from terminal UI implementation. The goal is to move prompt properties (placeholder, hint, default, transform, validate) from the centralized `PromptManager` into individual handlers as values or callbacks, while keeping the prompt type determination and UI logic in the PromptManager.

## Current Architecture Analysis

### Current Issues
1. **Tight Coupling**: `PromptManager::prompt()` contains hardcoded Laravel Prompts configuration
2. **Mixed Responsibilities**: PromptManager handles both UI orchestration and prompt property definitions
3. **Inflexible Progress Tracking**: Manual counter with hardcoded total (21 responses)
4. **No Abstraction**: Direct Laravel Prompts dependency throughout
5. **Complex Method**: 400+ line `prompt()` method with nested closures

### Current Structure
- **PromptManager**: 56 lines of handlers + 400+ lines of prompt definitions
- **HandlerInterface**: Basic contract (id, discover, process, setResponses, setWebroot)
- **AbstractHandler**: Base implementation with response management
- **25 Handler Classes**: Implement discover() and process() logic

## Refactoring Strategy

### Phase 1: Interface Extension
**Goal**: Extend handler contracts to provide prompt properties as values/callbacks

#### 1.1 Enhance HandlerInterface
```php
interface HandlerInterface {
    // Existing methods...
    public static function id(): string;
    public function discover(): null|string|bool|array;
    public function process(): void;
    public function setResponses(array $responses): static;
    public function setWebroot(string $webroot): static;
    
    // New prompt property methods - handlers provide values/callbacks
    public function getLabel(): string;
    public function getHint(): ?string;
    public function getPlaceholder(): ?string;
    public function getDefault(): mixed;
    public function getTransform(): ?callable;
    public function getValidate(): ?callable;
    public function getRequired(): bool;
    
    // Optional methods for specific prompt types (return null if not applicable)
    public function getOptions(): ?array;        // For select/multiselect
    public function getIntro(): ?string;         // For section grouping
    
    // Conditional logic
    public function isConditional(): bool;
    public function getCondition(): ?callable;
}
```

#### 1.2 Enhance AbstractHandler
```php
abstract class AbstractHandler implements HandlerInterface {
    // Existing properties and methods...
    
    // Default implementations - handlers override as needed
    public function getHint(): ?string {
        return null;
    }
    
    public function getPlaceholder(): ?string {
        return null;
    }
    
    public function getDefault(): mixed {
        return $this->discover(); // Use discovery as default
    }
    
    public function getTransform(): ?callable {
        return null;
    }
    
    public function getValidate(): ?callable {
        return null;
    }
    
    public function getRequired(): bool {
        return false;
    }
    
    public function getOptions(): ?array {
        return null; // Only relevant for select/multiselect
    }
    
    public function getIntro(): ?string {
        return null;
    }
    
    public function isConditional(): bool {
        return false;
    }
    
    public function getCondition(): ?callable {
        return null;
    }
    
    // Abstract method that must be implemented
    abstract public function getLabel(): string;
}
```

### Phase 2: Handler Implementation
**Goal**: Update handlers to provide property values/callbacks

#### 2.1 Simple Handler Example (Name.php)
```php
class Name extends AbstractHandler {
    public function getLabel(): string {
        return '🏷️ Site name';
    }
    
    public function getHint(): ?string {
        return 'We will use this name in the project and in the documentation.';
    }
    
    public function getPlaceholder(): ?string {
        return 'E.g. My Site';
    }
    
    public function getRequired(): bool {
        return true;
    }
    
    public function getTransform(): ?callable {
        return fn(string $v): string => trim($v);
    }
    
    public function getValidate(): ?callable {
        return fn($v): ?string => Converter::label($v) !== $v ? 
            'Please enter a valid project name.' : null;
    }
    
    // discover() and process() remain unchanged
}
```

#### 2.2 Multi-Select Handler Example (Services.php)
```php
class Services extends AbstractHandler {
    public function getLabel(): string {
        return '🔌 Services';
    }
    
    public function getHint(): ?string {
        return 'Select the services you want to use in the project.';
    }
    
    public function getOptions(): ?array {
        return [
            self::CLAMAV => '🦠 ClamAV',
            self::SOLR => '🔍 Solr',
            self::VALKEY => '🗃️ Valkey',
        ];
    }
    
    public function getDefault(): mixed {
        return $this->discover() ?? [self::CLAMAV, self::SOLR, self::VALKEY];
    }
    
    // discover() and process() remain unchanged
}
```

#### 2.3 Conditional Handler Example (GithubToken.php)
```php
class GithubToken extends AbstractHandler {
    public function isConditional(): bool {
        return true;
    }
    
    public function getCondition(): callable {
        return fn(array $responses): bool => 
            $responses[CodeProvider::id()] === CodeProvider::GITHUB;
    }
    
    public function getLabel(): string {
        return '🔑 GitHub access token (optional)';
    }
    
    public function getHint(): ?string {
        return 'Create a new token with "repo" scopes at https://github.com/settings/tokens/new';
    }
    
    public function getPlaceholder(): ?string {
        return 'E.g. ghp_1234567890';
    }
    
    public function getTransform(): ?callable {
        return fn(string $v): string => trim($v);
    }
    
    public function getValidate(): ?callable {
        return fn($v): ?string => !empty($v) && !str_starts_with($v, 'ghp_') ? 
            'Please enter a valid token starting with "ghp_"' : null;
    }
    
    // discover() and process() remain unchanged
}
```

### Phase 3: Refactor PromptManager
**Goal**: Use handler properties with args() helper function in existing Laravel form() structure

#### 3.1 Final PromptManager Implementation
```php
class PromptManager {
    public function prompt(): void {
        $responses = form()
            ->intro('General information')
            
            ->add(fn($r, $pr, $n): string => text(...$this->args(Name::class, $n)), Name::id())
            ->add(fn($r, $pr, $n): string => text(...$this->args(MachineName::class, $n)), MachineName::id())
            ->add(fn($r, $pr, $n): string => text(...$this->args(Org::class, $n)), Org::id())
            ->add(fn($r, $pr, $n): string => text(...$this->args(OrgMachineName::class, $n)), OrgMachineName::id())
            ->add(fn($r, $pr, $n): string => text(...$this->args(Domain::class, $n)), Domain::id())
            
            ->intro('Code repository')
            
            ->add(fn($r, $pr, $n): mixed => select(...$this->args(CodeProvider::class, $n)), CodeProvider::id())
            
            ->addIf(
                fn($r): bool => $this->handlers[GithubToken::id()]->getCondition()($r),
                fn($r, $pr, $n): string => password(...$this->args(GithubToken::class, $n)),
                GithubToken::id()
            )
            
            ->add(fn($r, $pr, $n): string => text(...$this->args(GithubRepo::class, $n)), GithubRepo::id())
            
            ->intro('Services')
            
            ->add(fn($r, $pr, $n): array => multiselect(...$this->args(Services::class, $n)), Services::id())
            
            ->intro('Hosting')
            
            ->add(fn($r, $pr, $n): mixed => select(...$this->args(HostingProvider::class, $n)), HostingProvider::id())
            
            // Continue with all other prompts...
            
            ->submit();
            
        $this->responses = $responses;
        $this->processResponses();
    }
    
    /**
     * Helper function that converts handler properties to Laravel prompt arguments
     */
    private function args(string $handlerClass, string $n): array {
        $handler = $this->handlers[$handlerClass::id()];
        
        return array_filter([
            'label' => $this->label($handler->getLabel()),
            'hint' => $handler->getHint(),
            'placeholder' => $handler->getPlaceholder(),
            'required' => $handler->getRequired(),
            'default' => $this->default($n, $handler->getDefault()),
            'transform' => $handler->getTransform(),
            'validate' => $handler->getValidate(),
            'options' => $handler->getOptions(), // For select/multiselect (ignored by text/password)
        ], fn($value) => $value !== null);
    }
}
```

#### 3.2 Key Benefits of This Approach
- **Clean Syntax**: `text(...$this->args(Name::class, $n))` is readable and concise
- **Explicit UI Choice**: PromptManager explicitly chooses `text` vs `select` vs `multiselect`
- **No Boilerplate**: The `args()` helper eliminates repetitive property mapping
- **Handler Agnostic**: Handlers just provide properties, no UI knowledge
- **Laravel Native**: Uses pure Laravel prompt functions with magical argument population
- **Minimal Magic**: Only one helper function, everything else is explicit


## Migration Strategy

### Phase 1: Interface Extension (Week 1)
1. **Extend HandlerInterface**: Add prompt property methods
2. **Enhance AbstractHandler**: Add default implementations for all new methods
3. **Maintain Backward Compatibility**: Existing handlers work without changes initially
4. **Update Base Tests**: Ensure interface changes don't break existing tests

### Phase 2: Handler Migration (Week 2-3)
1. **Simple Text Handlers**: Name, Domain, MachineName, Org, OrgMachineName, ModulePrefix
2. **Select Handlers**: CodeProvider, HostingProvider, ProvisionType, DeployType
3. **Multi-Select Handlers**: Services, CiProvider
4. **Special Cases**: GithubToken (conditional/note), Profile (dynamic), Theme (conditional)
5. **Update Handler Tests**: Verify each handler's property methods work correctly

### Phase 3: PromptManager Refactoring (Week 3-4)
1. **Create args() Helper Method**: Single function to convert handler properties to Laravel prompt arguments
2. **Refactor prompt() Method**: Replace inline configuration with `text(...$this->args(Handler::class, $n))`
3. **Maintain Existing Structure**: Keep same Laravel form() flow, just use args() helper
4. **Handle Conditionals**: Use handler isConditional() and getCondition() methods
5. **Maintain Progress Logic**: Ensure (1/21) style progress indicators still work

### Phase 4: Testing & Validation (Week 4-5)
1. **Unit Tests**: Test individual handler property methods
2. **Integration Tests**: Test full prompt flow with new architecture
3. **Fixture Updates**: Use `UPDATE_FIXTURES=1` to regenerate any changed outputs
4. **Manual Testing**: Run installer with various configurations to ensure behavior is identical

### Phase 5: Cleanup (Week 5)
1. **Remove Dead Code**: Clean up old inline prompt definitions
2. **Documentation**: Update code comments and any architectural docs
3. **Performance Check**: Ensure no performance regressions

## Testing Strategy

### Unit Tests
- Test each handler's `getPromptConfig()` method
- Test conditional logic in handlers
- Test PromptProvider implementations
- Test progress calculation logic

### Integration Tests
- Test full prompt flow with various configurations
- Test conditional prompt chains
- Test section grouping
- Test validation flows

### Fixture Updates
- Update installer fixtures to reflect new prompt behavior
- Use `UPDATE_FIXTURES=1` process for baseline updates
- Verify all scenarios still work correctly

## Backward Compatibility

### Deprecated Methods
- Keep current `label()` method in PromptManager for gradual migration
- Maintain existing handler interface during transition
- Preserve existing test interfaces

### Migration Helpers
```php
// Temporary helper to convert old closures to new config
class PromptMigrationHelper {
    public static function convertClosureToConfig(callable $closure): PromptConfig {
        // Helper to ease migration of complex prompts
    }
}
```

## Benefits of This Approach

### 1. **Simple & Clean**
- **Handlers**: Just provide values/callbacks for prompt properties
- **PromptManager**: Explicitly builds UI using Laravel form() with handler values
- **No Abstractions**: No form builders, interfaces, or mappings to maintain

### 2. **Handler Simplicity**
- Handlers don't know about UI widgets - they just provide data
- Each handler method returns simple values or callbacks
- Clear responsibility: provide properties like getLabel(), getHint(), getValidate()

### 3. **PromptManager Explicitness**
- PromptManager explicitly decides text vs select vs multiselect for each handler
- All UI flow is visible in the prompt() method
- Easy to see what prompts are used and in what order

### 4. **Future Flexibility**
- Easy to create alternative PromptManager classes for different UI libraries
- Handler property methods work with any UI implementation
- No complex abstractions to maintain

### 5. **Readability & Maintainability**
- Handler property methods are self-documenting
- PromptManager shows explicit UI structure
- Simple to understand and modify

## Risk Mitigation

### Technical Risks
1. **Complex Migration**: Phased approach with thorough testing
2. **Test Breakage**: Comprehensive fixture updates
3. **Performance Impact**: Minimal - mainly structural changes

### Compatibility Risks
1. **Handler API Changes**: Gradual migration with backward compatibility
2. **Prompt Behavior Changes**: Extensive testing of edge cases
3. **External Dependencies**: Maintain Laravel Prompts as default

## Success Criteria

### Functional Requirements
- [ ] All existing prompts work identically
- [ ] Progress tracking works correctly
- [ ] Conditional prompts function properly
- [ ] Validation messages appear correctly
- [ ] All installer tests pass

### Non-Functional Requirements
- [ ] Code is more maintainable and testable
- [ ] Easy to add new prompt types
- [ ] Simple to implement alternative UI providers
- [ ] Performance is not degraded
- [ ] Documentation is updated

## Conclusion

This refactoring will significantly improve the maintainability and flexibility of the Vortex installer while preserving all existing functionality. The phased approach ensures minimal risk while delivering maximum benefit for future development.