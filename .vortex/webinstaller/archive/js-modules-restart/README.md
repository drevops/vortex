# Vortex Web Installer - JavaScript Architecture

## Modular Structure

The installer JavaScript has been split into logical modules for better maintainability and organization:

### Module Files

#### `modules/data-manager.js`
- **Purpose**: Alpine.js data structure and state management
- **Exports**: `installerData()` function
- **Features**:
  - Form data structure definition
  - Auto-generation of machine names, domains, and derived fields
  - Hosting provider logic (web root directory updates)
  - Database source filtering
  - String utility functions (toMachineName, toKebabCase, toAbbreviation)

#### `modules/tab-manager.js`
- **Purpose**: Tab navigation and status management
- **Exports**: `switchTab`, `getCurrentTab`, `isTabValid`, `updateTabStatus`, `updateAllTabStatuses`
- **Features**:
  - Tab switching functionality
  - Tab completion status tracking
  - Visual status indicators (green/red dots)
  - Tab validation based on required fields

#### `modules/validation.js`
- **Purpose**: Field validation using Joi schema validation
- **Exports**: `validateField`, `validateAllFields`, `validateCurrentTabFields`, `setupValidationListeners`
- **Features**:
  - Real-time field validation
  - Custom validation rules (machine_name, domain, email, url)
  - Error message display
  - Validation event listeners setup
  - Integration with tab status updates

#### `modules/ui-utilities.js`
- **Purpose**: Navigation buttons and responsive functionality
- **Exports**: `updateNavigationButtons`, `goToNextTab`, `goToPreviousTab`, `updateScaling`
- **Features**:
  - Next/Previous button state management
  - Keyboard navigation (Ctrl+Arrow keys)
  - Dynamic viewport scaling for responsiveness
  - Button enable/disable logic based on validation

#### `modules/help-system.js`
- **Purpose**: Help sidebar and content display
- **Exports**: `showHelp`, `closeHelpSidebar`, `setupHelpSystemListeners`
- **Features**:
  - Help content extraction from DOM
  - Sidebar show/hide functionality
  - Escape key and overlay click handling
  - Dynamic help content generation

### Main Files

#### `installer-new.js` (Modular Version)
- **Purpose**: Main coordination file that imports and initializes all modules
- **Features**:
  - Imports all module functions
  - Exports functions to global window object for HTML compatibility
  - Coordinates module initialization
  - Sets up global event listeners
  - Handles keyboard navigation setup

#### `installer.js` (Original Monolithic Version)
- **Purpose**: Original single-file implementation
- **Status**: Preserved for comparison and fallback
- **Note**: All functionality identical to modular version

## Build Configuration

### Package.json Scripts

- `npm run build` - Builds modular version (default)
- `npm run build:js:new` - Explicitly builds modular version
- `npm run build:js:old` - Builds original monolithic version
- `npm run dev` - Development mode with modular version
- `npm run watch:js` - Watch mode for modular version

### Build Process

1. **esbuild** bundles the modular structure into a single minified file
2. ES6 modules are resolved and tree-shaken
3. Output file: `dist/scripts.js` (9.2kb minified)

## Usage

### HTML Integration
No changes required in HTML - all functions are exported to `window` object:
```html
<button onclick="switchTab('general')">General</button>
<button onclick="showHelp(this)">?</button>
```

### Alpine.js Integration
```html
<div x-data="installerData()">
  <!-- Form fields with automatic data binding -->
</div>
```

## Benefits of Modular Structure

1. **Maintainability**: Each module has a single responsibility
2. **Testability**: Individual modules can be tested in isolation
3. **Reusability**: Modules can be reused in other projects
4. **Developer Experience**: Easier to navigate and understand codebase
5. **Performance**: Tree-shaking removes unused code
6. **Scalability**: Easy to add new modules or extend existing ones

## Migration Notes

- All existing functionality preserved
- No breaking changes in public API
- HTML templates remain unchanged
- Build output size reduced from 9.5kb to 9.2kb
- Development workflow improved with better code organization