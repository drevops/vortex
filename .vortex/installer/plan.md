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
**Goal**: Use handler properties directly in existing Laravel form() structure

#### 3.1 Simplified PromptManager - Just Use Handler Properties
```php
class PromptManager {
    // ... existing properties and constructor ...
    
    public function prompt(): void {
        // Same Laravel form() as before, just get properties from handlers
        $responses = form()
            ->intro('General information')
            
            ->add(fn($r, $pr, $n): string => text(
                label: $this->label($this->handlers[Name::id()]->getLabel()),
                hint: $this->handlers[Name::id()]->getHint(),
                placeholder: $this->handlers[Name::id()]->getPlaceholder(),
                required: $this->handlers[Name::id()]->getRequired(),
                default: $this->default($n, $this->handlers[Name::id()]->getDefault()),
                transform: $this->handlers[Name::id()]->getTransform(),
                validate: $this->handlers[Name::id()]->getValidate(),
            ), Name::id())
            
            ->add(fn($r, $pr, $n): string => text(
                label: $this->label($this->handlers[MachineName::id()]->getLabel()),
                hint: $this->handlers[MachineName::id()]->getHint(),
                placeholder: $this->handlers[MachineName::id()]->getPlaceholder(),
                required: $this->handlers[MachineName::id()]->getRequired(),
                default: $this->default($n, $this->handlers[MachineName::id()]->getDefault()),
                transform: $this->handlers[MachineName::id()]->getTransform(),
                validate: $this->handlers[MachineName::id()]->getValidate(),
            ), MachineName::id())
            
            // ... more prompts using handler properties
            
            ->add(fn($r, $pr, $n): array => multiselect(
                label: $this->label($this->handlers[Services::id()]->getLabel()),
                hint: $this->handlers[Services::id()]->getHint(),
                options: $this->handlers[Services::id()]->getOptions(),
                default: $this->default($n, $this->handlers[Services::id()]->getDefault()),
            ), Services::id())
            
            ->addIf(
                fn($r): bool => $this->handlers[GithubToken::id()]->isConditional() && 
                               $this->handlers[GithubToken::id()]->getCondition()($r),
                fn($r, $pr, $n): string => password(
                    label: $this->label($this->handlers[GithubToken::id()]->getLabel()),
                    hint: $this->handlers[GithubToken::id()]->getHint(),
                    placeholder: $this->handlers[GithubToken::id()]->getPlaceholder(),
                    transform: $this->handlers[GithubToken::id()]->getTransform(),
                    validate: $this->handlers[GithubToken::id()]->getValidate(),
                ), GithubToken::id()
            )
            
            ->submit();
            
        $this->responses = $responses;
        $this->processResponses();
    }
}
```

#### 3.2 Even Cleaner - Helper Method
```php
class PromptManager {
    public function prompt(): void {
        $responses = form()
            ->intro('General information')
            
            ->add(fn($r, $pr, $n) => $this->textPrompt(Name::id(), $n), Name::id())
            ->add(fn($r, $pr, $n) => $this->textPrompt(MachineName::id(), $n), MachineName::id())
            ->add(fn($r, $pr, $n) => $this->textPrompt(Org::id(), $n), Org::id())
            ->add(fn($r, $pr, $n) => $this->textPrompt(Domain::id(), $n), Domain::id())
            
            ->intro('Code repository')
            
            ->add(fn($r, $pr, $n) => $this->selectPrompt(CodeProvider::id(), $n), CodeProvider::id())
            
            ->addIf(
                fn($r): bool => $this->handlers[GithubToken::id()]->getCondition()($r),
                fn($r, $pr, $n) => $this->passwordPrompt(GithubToken::id(), $n),
                GithubToken::id()
            )
            
            ->intro('Services')
            
            ->add(fn($r, $pr, $n) => $this->multiselectPrompt(Services::id(), $n), Services::id())
            
            ->submit();
            
        $this->responses = $responses;
        $this->processResponses();
    }
    
    private function textPrompt(string $handlerId, string $n): string {
        $handler = $this->handlers[$handlerId];
        return text(
            label: $this->label($handler->getLabel()),
            hint: $handler->getHint(),
            placeholder: $handler->getPlaceholder(),
            required: $handler->getRequired(),
            default: $this->default($n, $handler->getDefault()),
            transform: $handler->getTransform(),
            validate: $handler->getValidate(),
        );
    }
    
    private function selectPrompt(string $handlerId, string $n): mixed {
        $handler = $this->handlers[$handlerId];
        return select(
            label: $this->label($handler->getLabel()),
            hint: $handler->getHint(),
            options: $handler->getOptions(),
            required: $handler->getRequired(),
            default: $this->default($n, $handler->getDefault()),
        );
    }
    
    private function passwordPrompt(string $handlerId, string $n): string {
        $handler = $this->handlers[$handlerId];
        return password(
            label: $this->label($handler->getLabel()),
            hint: $handler->getHint(),
            placeholder: $handler->getPlaceholder(),
            transform: $handler->getTransform(),
            validate: $handler->getValidate(),
        );
    }
    
    private function multiselectPrompt(string $handlerId, string $n): array {
        $handler = $this->handlers[$handlerId];
        return multiselect(
            label: $this->label($handler->getLabel()),
            hint: $handler->getHint(),
            options: $handler->getOptions(),
            default: $this->default($n, $handler->getDefault()),
        );
    }
}
```

### Phase 4: Future Extensibility (Optional)
**Goal**: Make it easy to add different UI providers later

If you want to support different UI providers in the future, you can simply:

#### 4.1 Create Alternative PromptManager
```php
class SymfonyConsolePromptManager extends PromptManager {
    public function prompt(): void {
        // Use Symfony Console instead of Laravel Prompts
        // But still get properties from handlers the same way
        $responses = [];
        
        $responses[Name::id()] = $this->symfonyTextPrompt(Name::id());
        $responses[Services::id()] = $this->symfonyMultiSelectPrompt(Services::id());
        // etc.
        
        $this->responses = $responses;
        $this->processResponses();
    }
    
    private function symfonyTextPrompt(string $handlerId): string {
        $handler = $this->handlers[$handlerId];
        $question = new Question($handler->getLabel(), $handler->getDefault());
        $question->setValidator($handler->getValidate());
        // etc.
        return $this->questionHelper->ask($this->input, $this->output, $question);
    }
}
```

#### 4.2 Special Handler Cases
Some handlers may need special handling:

```php
// Handler that shows note instead of input based on state
class GithubToken extends AbstractHandler {
    public function getLabel(): string {
        if (!empty($this->discover())) {
            return 'GitHub access token is already set in the environment.';
        }
        return '🔑 GitHub access token (optional)';
    }
    
    public function shouldShowAsNote(): bool {
        return !empty($this->discover());
    }
}

// In PromptManager:
->addIf(
    fn($r): bool => $this->handlers[GithubToken::id()]->getCondition()($r),
    function ($r, $pr, $n) {
        $handler = $this->handlers[GithubToken::id()];
        if ($handler->shouldShowAsNote()) {
            Tui::ok($handler->getLabel());
            return $handler->getDefault();
        }
        return $this->passwordPrompt(GithubToken::id(), $n);
    },
    GithubToken::id()
)
```

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
1. **Refactor prompt() Method**: Replace inline configuration with handler property calls
2. **Add Helper Methods**: Create textPrompt(), selectPrompt(), multiselectPrompt() etc.
3. **Maintain Existing Structure**: Keep same Laravel form() flow, just get values from handlers
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