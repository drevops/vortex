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

### Phase 3: Prompt Provider Abstraction
**Goal**: Create abstraction layer for different terminal UI providers

#### 3.1 Create PromptProviderInterface
```php
interface PromptProviderInterface {
    public function form(): FormBuilder;
}

interface FormBuilder {
    public function intro(string $message): self;
    public function add(callable $callback, string $name): self;
    public function addIf(callable $condition, callable $callback, ?string $name = null): self;
    public function submit(): array;
}
```

#### 3.2 Implement Laravel Prompts Provider
```php
class LaravelPromptsProvider implements PromptProviderInterface {
    public function form(): FormBuilder {
        return new LaravelFormBuilder();
    }
}

class LaravelFormBuilder implements FormBuilder {
    private $form;
    
    public function __construct() {
        $this->form = \Laravel\Prompts\form();
    }
    
    public function intro(string $message): self {
        $this->form->intro($message);
        return $this;
    }
    
    public function add(callable $callback, string $name): self {
        $this->form->add($callback, $name);
        return $this;
    }
    
    public function addIf(callable $condition, callable $callback, ?string $name = null): self {
        $this->form->addIf($condition, $callback, $name);
        return $this;
    }
    
    public function submit(): array {
        return $this->form->submit();
    }
}
```

### Phase 4: Refactor PromptManager
**Goal**: Use handler properties to configure prompts, while PromptManager determines prompt type

#### 4.1 Prompt Type Mapping
```php
class PromptManager {
    // Map handlers to their prompt types - PromptManager decides the UI widget
    private array $promptTypes = [
        Name::id() => 'text',
        MachineName::id() => 'text',
        Org::id() => 'text',
        OrgMachineName::id() => 'text',
        Domain::id() => 'text',
        CodeProvider::id() => 'select',
        GithubToken::id() => 'password',
        GithubRepo::id() => 'text',
        Services::id() => 'multiselect',
        HostingProvider::id() => 'select',
        // ... etc
    ];
    
    // Section groupings - PromptManager controls UI flow
    private array $sections = [
        'General information' => [Name::id(), MachineName::id(), Org::id(), OrgMachineName::id(), Domain::id()],
        'Code repository' => [CodeProvider::id(), GithubToken::id(), GithubRepo::id()],
        'Services' => [Services::id()],
        'Hosting' => [HostingProvider::id()],
        // ... etc
    ];
}
```

#### 4.2 Simplified PromptManager
```php
class PromptManager {
    private array $handlers = [];
    private PromptProviderInterface $promptProvider;
    
    public function __construct(
        private Config $config,
        ?PromptProviderInterface $promptProvider = null
    ) {
        $this->promptProvider = $promptProvider ?? new LaravelPromptsProvider();
        $this->initHandlers();
    }
    
    public function prompt(): void {
        $form = $this->promptProvider->form();
        $this->addPromptsToForm($form);
        $this->responses = $form->submit();
        $this->processResponses();
    }
    
    private function addPromptsToForm(FormBuilder $form): void {
        $promptNumber = 0;
        $totalPrompts = $this->calculateTotalPrompts();
        
        foreach ($this->sections as $sectionTitle => $handlerIds) {
            $form->intro($sectionTitle);
            
            foreach ($handlerIds as $handlerId) {
                $handler = $this->handlers[$handlerId];
                
                if ($handler->isConditional()) {
                    $form->addIf(
                        $handler->getCondition(),
                        fn($responses) => $this->createPrompt($handler, ++$promptNumber, $totalPrompts),
                        $handlerId
                    );
                } else {
                    $form->add(
                        fn($responses) => $this->createPrompt($handler, ++$promptNumber, $totalPrompts),
                        $handlerId
                    );
                }
            }
        }
    }
    
    private function createPrompt(HandlerInterface $handler, int $current, int $total): mixed {
        $promptType = $this->promptTypes[$handler::id()];
        $label = $this->addProgressToLabel($handler->getLabel(), $current, $total);
        
        return match ($promptType) {
            'text' => \Laravel\Prompts\text(
                label: $label,
                hint: $handler->getHint(),
                placeholder: $handler->getPlaceholder(),
                required: $handler->getRequired(),
                default: $handler->getDefault(),
                transform: $handler->getTransform(),
                validate: $handler->getValidate(),
            ),
            'password' => \Laravel\Prompts\password(
                label: $label,
                hint: $handler->getHint(),
                placeholder: $handler->getPlaceholder(),
                required: $handler->getRequired(),
                transform: $handler->getTransform(),
                validate: $handler->getValidate(),
            ),
            'select' => \Laravel\Prompts\select(
                label: $label,
                hint: $handler->getHint(),
                options: $handler->getOptions(),
                required: $handler->getRequired(),
                default: $handler->getDefault(),
            ),
            'multiselect' => \Laravel\Prompts\multiselect(
                label: $label,
                hint: $handler->getHint(),
                options: $handler->getOptions(),
                default: $handler->getDefault(),
            ),
            'confirm' => \Laravel\Prompts\confirm(
                label: $label,
                hint: $handler->getHint(),
                default: $handler->getDefault(),
            ),
        };
    }
    
    private function addProgressToLabel(string $label, int $current, int $total): string {
        return sprintf('%s (%d/%d)', $label, $current, $total);
    }
}
```

### Phase 5: Enhanced Features
**Goal**: Add advanced features leveraging the new architecture

#### 5.1 Dynamic Progress Calculation
- Calculate total prompts based on enabled handlers and conditions
- Handle conditional prompts in progress counting
- Support for nested/grouped prompts

#### 5.2 Alternative UI Provider Example
```php
class SymfonyConsoleProvider implements PromptProviderInterface {
    public function form(): FormBuilder {
        return new SymfonyFormBuilder($this->input, $this->output);
    }
}

// Easy to swap providers
$promptManager = new PromptManager($config, new SymfonyConsoleProvider());
```

#### 5.3 Special Handler Considerations
Some handlers may need special handling patterns:

```php
// Handler with dynamic properties based on state
class GithubToken extends AbstractHandler {
    public function getLabel(): string {
        if (!empty($this->discover())) {
            return 'GitHub access token is already set in the environment.';
        }
        return '🔑 GitHub access token (optional)';
    }
    
    public function isNote(): bool {
        return !empty($this->discover()); // Show as note instead of input
    }
}

// PromptManager handles this:
private function createPrompt(HandlerInterface $handler, int $current, int $total): mixed {
    // Check if handler wants to show as note
    if (method_exists($handler, 'isNote') && $handler->isNote()) {
        Tui::ok($handler->getLabel());
        return $handler->getDefault();
    }
    
    // Regular prompt handling...
}
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
1. **Create Provider Abstraction**: PromptProviderInterface and LaravelPromptsProvider
2. **Add Prompt Type Mapping**: Define which handlers use which prompt types
3. **Create Section Configuration**: Group handlers into logical sections
4. **Refactor prompt() Method**: Use handler properties instead of inline configuration
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

### 1. **Clean Separation of Concerns**
- **Handlers**: Provide prompt properties (label, hint, validation, etc.) as values/callbacks
- **PromptManager**: Determines UI widget type (text, select, multiselect) and orchestrates flow
- **UI Providers**: Handle terminal interaction (Laravel Prompts, Symfony Console, etc.)

### 2. **Handler Simplicity**
- Handlers don't know about UI widgets - they just provide data
- Each handler method returns simple values or callbacks
- Clear, focused responsibility: provide properties, not define UI

### 3. **PromptManager Control**
- PromptManager decides which widget to use for each handler
- Centralized prompt type mapping makes UI changes easy
- Section grouping and progress logic stay in one place

### 4. **UI Provider Flexibility**
- Easy to implement different terminal libraries
- Swap providers without changing handler logic
- Future-proof for new UI frameworks

### 5. **Readability**
- Handler property methods are self-documenting
- Clear what each handler provides (label, validation, etc.)
- No complex UI configuration mixed with business logic

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