# Vortex Installer: Screen-Based UI Design

## Overview

This document proposes a refactored approach to the Vortex installer UI, moving from the current submenu-based system to a **screen-based system** where each screen can contain multiple related inputs. This allows users to configure related settings together and see their relationships more clearly.

## Current vs. Proposed Approach

### Current Menu System
- Main menu with sections
- Each section opens a submenu
- Each submenu item collects one input
- Users navigate back and forth between individual prompts

### Proposed Screen System
- Main menu with screens
- Each screen shows multiple related inputs on one page
- Users can see and edit all related settings together
- Progress through screens in logical order with ability to return

## Screen Designs

Based on the analysis of current prompts, here are the proposed screens:

---

## Screen 1: Project Information
**Purpose**: Collect basic project identity and metadata

```
┌─ 🚀 Vortex Installer: Project Information ──────────────────────┐
│                                                                  │
│  Configure your project's basic information:                    │
│                                                                  │
│  🏷️  Site Name: ________________________________                │
│      (Human-readable project name)                              │
│                                                                  │
│  🏷️  Site Machine Name: ________________________                │
│      (Lowercase, underscores only)                              │
│      Auto-generated from site name ↑                           │
│                                                                  │
│  🏢  Organization Name: ____________________________            │
│      (Your company/organization)                                │
│                                                                  │
│  🏢  Organization Machine Name: ____________________            │
│      (Lowercase, underscores only)                              │
│      Auto-generated from organization ↑                        │
│                                                                  │
│  🌐  Public Domain: ________________________________            │
│      (e.g., example.com)                                        │
│      Auto-generated from machine name ↑                        │
│                                                                  │
│  ┌─ Navigation ─────────────────────────────────────────────┐   │
│  │  [Continue to Code Repository →]  [← Back to Main]      │   │
│  └──────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────┘
```

**Inputs on this screen**:
- Site Name (text)
- Site Machine Name (text, auto-generated)
- Organization Name (text)
- Organization Machine Name (text, auto-generated)
- Public Domain (text, auto-generated)

**Dependencies shown**:
- Real-time updates of derived fields as user types
- Clear indication of auto-generated vs manual values

---

## Screen 2: Code Repository
**Purpose**: Configure version control and GitHub integration

```
┌─ 🚀 Vortex Installer: Code Repository ──────────────────────────┐
│                                                                  │
│  Configure your code repository settings:                       │
│                                                                  │
│  🗄️  Repository Provider:                                       │
│      ○ GitHub                                                   │
│      ○ Other                                                    │
│                                                                  │
│  ┌─ GitHub Settings (shown when GitHub selected) ─────────────┐ │
│  │                                                            │ │
│  │  🔑  GitHub Access Token (optional): ___________________  │ │
│  │      (Leave empty to skip GitHub integration)             │ │
│  │                                                            │ │
│  │  🏷️  GitHub Repository: ____________________________      │ │
│  │      (org/repo format, e.g., myorg/myproject)             │ │
│  │      Auto-generated: myorg/myproject ↑                    │ │
│  │      [Only shown if token provided]                       │ │
│  │                                                            │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  ┌─ Navigation ─────────────────────────────────────────────┐   │
│  │  [← Previous]  [Continue to Drupal Config →]  [Skip]    │   │
│  └──────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────┘
```

**Inputs on this screen**:
- Repository Provider (single selection)
- GitHub Token (password, conditional)
- GitHub Repository (text, conditional)

**Conditional logic**:
- GitHub settings section only appears when GitHub is selected
- Repository field only appears when token is provided
- Auto-generation uses previous screen's organization + machine name

---

## Screen 3: Drupal Configuration
**Purpose**: Configure Drupal-specific settings

```
┌─ 🚀 Vortex Installer: Drupal Configuration ─────────────────────┐
│                                                                  │
│  Configure Drupal-specific settings:                            │
│                                                                  │
│  🧾  Installation Profile:                                      │
│      ○ Standard (Recommended)                                   │
│      ○ Minimal                                                  │
│      ○ Demo Umami                                               │
│      ○ Custom → [Custom Profile Name: _______________]          │
│                                                                  │
│  🧩  Module Prefix: ___________________                         │
│      (Prefix for custom modules)                                │
│      Auto-generated from machine name ↑                        │
│                                                                  │
│  🎨  Theme Machine Name (optional): ____________________       │
│      (Leave empty for no custom theme)                          │
│      Auto-generated from machine name ↑                        │
│                                                                  │
│  ┌─ Navigation ─────────────────────────────────────────────┐   │
│  │  [← Previous]  [Continue to Services →]  [Skip]         │   │
│  └──────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────┘
```

**Inputs on this screen**:
- Installation Profile (single selection with conditional text input)
- Module Prefix (text, auto-generated)
- Theme Machine Name (text, optional, auto-generated)

**Dynamic elements**:
- Custom profile name field appears when "Custom" is selected
- Auto-generated defaults can be overridden

---

## Screen 4: Services & Infrastructure
**Purpose**: Configure services, hosting, and deployment

```
┌─ 🚀 Vortex Installer: Services & Infrastructure ────────────────┐
│                                                                  │
│  Configure services and hosting:                                │
│                                                                  │
│  🔌  Additional Services (select all that apply):               │
│      ☑ 🦠 ClamAV (Virus scanning)                              │
│      ☑ 🔍 Solr (Search engine)                                 │
│      ☑ 🗃️ Valkey (Redis-compatible caching)                    │
│                                                                  │
│  ☁️  Hosting Provider:                                          │
│      ○ Acquia Cloud                                             │
│      ○ Lagoon                                                   │
│      ○ Other                                                    │
│      ○ None                                                     │
│                                                                  │
│  📁  Web Root Directory: _______________                        │
│      Auto-set based on hosting provider ↑                      │
│      (Acquia: docroot, Lagoon: web, Other: custom)             │
│                                                                  │
│  🚚  Deployment Types (select all that apply):                 │
│      ☐ 📦 Code artifact                                        │
│      ☐ 🌊 Lagoon webhook                                       │
│      ☐ 🐳 Container image                                      │
│      ☐ 🌐 Custom webhook                                       │
│      [Options filtered based on hosting provider ↑]            │
│                                                                  │
│  ┌─ Navigation ─────────────────────────────────────────────┐   │
│  │  [← Previous]  [Continue to Workflow →]  [Skip]         │   │
│  └──────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────┘
```

**Inputs on this screen**:
- Services (multi-selection)
- Hosting Provider (single selection)
- Web Root Directory (text, auto-set)
- Deployment Types (multi-selection, filtered)

**Complex dependencies**:
- Web root auto-sets based on hosting provider
- Deployment options filtered by hosting provider
- Real-time updates as selections change

---

## Screen 5: Workflow & Database
**Purpose**: Configure deployment workflow and database settings

```
┌─ 🚀 Vortex Installer: Workflow & Database ──────────────────────┐
│                                                                  │
│  Configure deployment workflow and database:                    │
│                                                                  │
│  🦋  Provision Type:                                            │
│      ○ Database (Import existing database)                      │
│      ○ Profile (Install fresh from profile)                     │
│                                                                  │
│  ┌─ Database Settings (shown when Database selected) ──────────┐ │
│  │                                                             │ │
│  │  📡  Database Download Source:                             │ │
│  │      ○ URL download                                        │ │
│  │      ○ FTP download                                        │ │
│  │      ○ Acquia Cloud                                        │ │
│  │      ○ Lagoon                                              │ │
│  │      ○ Container Registry                                  │ │
│  │      ○ None (manual)                                       │ │
│  │      [Options filtered by hosting provider ↑]             │ │
│  │                                                             │ │
│  │  🏷️  Database Container Image: _______________________     │ │
│  │      (Only shown for Container Registry)                   │ │
│  │      Auto-generated: myorg/myproject-db ↑                  │ │
│  │                                                             │ │
│  └─────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  ┌─ Navigation ─────────────────────────────────────────────┐   │
│  │  [← Previous]  [Continue to CI/CD →]  [Skip]            │   │
│  └──────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────┘
```

**Inputs on this screen**:
- Provision Type (single selection)
- Database Download Source (single selection, conditional)
- Database Container Image (text, conditional)

**Conditional sections**:
- Database settings section only appears when "Database" provision type selected
- Container image field only appears for "Container Registry" source
- Options filtered by hosting provider from previous screen

---

## Screen 6: CI/CD & Automation
**Purpose**: Configure continuous integration and automation

```
┌─ 🚀 Vortex Installer: CI/CD & Automation ───────────────────────┐
│                                                                  │
│  Configure continuous integration and automation:               │
│                                                                  │
│  🔄  Continuous Integration Provider:                           │
│      ○ None                                                     │
│      ○ GitHub Actions                                           │
│      ○ CircleCI                                                 │
│      [GitHub Actions only available if GitHub repo selected ↑] │
│                                                                  │
│  ⬆️  Dependency Updates Provider:                               │
│      ○ 🤖 + 🔄 Renovate self-hosted in CI                      │
│      ○ 🤖 Renovate GitHub app                                   │
│      ○ 🚫 None                                                  │
│                                                                  │
│  👤  Auto-assign PR Author?                                     │
│      ○ Yes (Recommended)                                        │
│      ○ No                                                       │
│                                                                  │
│  🎫  Auto-add CONFLICT Label to PRs?                            │
│      ○ Yes (Recommended)                                        │
│      ○ No                                                       │
│                                                                  │
│  ┌─ Navigation ─────────────────────────────────────────────┐   │
│  │  [← Previous]  [Continue to Documentation →]  [Skip]    │   │
│  └──────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────┘
```

**Inputs on this screen**:
- CI Provider (single selection, filtered)
- Dependency Updates Provider (single selection)
- Auto-assign PR Author (confirmation)
- Auto-add CONFLICT Label (confirmation)

**Dependencies**:
- GitHub Actions only available if GitHub was selected in Screen 2
- All settings relate to automation and can be configured together

---

## Screen 7: Documentation & AI
**Purpose**: Configure documentation and AI assistant

```
┌─ 🚀 Vortex Installer: Documentation & AI ───────────────────────┐
│                                                                  │
│  Configure documentation and AI assistant:                      │
│                                                                  │
│  📚  Preserve Project Documentation?                            │
│      ○ Yes (Recommended)                                        │
│      ○ No                                                       │
│                                                                  │
│  📋  Preserve Onboarding Checklist?                             │
│      ○ Yes (Recommended)                                        │
│      ○ No                                                       │
│                                                                  │
│  🤖  AI Code Assistant Instructions:                            │
│      ○ Anthropic Claude                                         │
│      ○ None                                                     │
│                                                                  │
│  ┌─ AI Assistant Preview (shown when Claude selected) ──────────┐ │
│  │                                                              │ │
│  │  When enabled, this will add a CLAUDE.md file with:         │ │
│  │  • Drupal development guidelines                            │ │
│  │  • Project-specific coding standards                        │ │
│  │  • Testing and deployment workflows                         │ │
│  │  • Integration with Ahoy commands                           │ │
│  │                                                              │ │
│  └──────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  ┌─ Navigation ─────────────────────────────────────────────┐   │
│  │  [← Previous]  [Continue to Review →]  [Skip]           │   │
│  └──────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────┘
```

**Inputs on this screen**:
- Preserve Project Documentation (confirmation)
- Preserve Onboarding Checklist (confirmation)
- AI Code Assistant Instructions (single selection)

**Informational elements**:
- Preview section shows what will be included when Claude is selected
- All final configuration options grouped together

---

## Screen 8: Review & Install
**Purpose**: Final review and installation execution

```
┌─ 🚀 Vortex Installer: Review & Install ─────────────────────────┐
│                                                                  │
│  Review your configuration:                                     │
│                                                                  │
│  ── Project Information ──                                      │
│    Site Name: My Awesome Project                                │
│    Machine Name: my_awesome_project                             │
│    Organization: My Organization                                │
│    Domain: my-awesome-project.com                               │
│                                                                  │
│  ── Code Repository ──                                          │
│    Provider: GitHub                                             │
│    Repository: myorg/my-awesome-project                         │
│    Token: *** (provided)                                        │
│                                                                  │
│  ── Drupal Configuration ──                                     │
│    Profile: Standard                                            │
│    Module Prefix: map                                           │
│    Theme: my_awesome_project_theme                              │
│                                                                  │
│  ── Services & Infrastructure ──                                │
│    Services: ClamAV, Solr, Valkey                              │
│    Hosting: Lagoon                                              │
│    Web Root: web                                                │
│    Deployment: Lagoon webhook                                   │
│                                                                  │
│  [Continue scrolling for full configuration...]                 │
│                                                                  │
│  ┌─ Actions ──────────────────────────────────────────────────┐ │
│  │  [💾 Export Config]  [✅ Validate]  [🔧 Edit Settings]    │ │
│  │                                                             │ │
│  │  [🚀 START INSTALLATION]  [❌ Cancel]                      │ │
│  └─────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────┘
```

**Features on this screen**:
- Complete configuration summary organized by section
- Scrollable review of all settings
- Export configuration option
- Validation option
- Edit settings (return to previous screens)
- Final installation execution

---

## Key Benefits of Screen-Based Approach

### 1. **Contextual Input Collection**
- Related inputs grouped together logically
- Users can see relationships between settings
- Auto-generation and dependencies visible in real-time

### 2. **Reduced Navigation**
- Fewer clicks to configure related settings
- Natural flow from basic to advanced configuration
- Clear progress indication through screens

### 3. **Better User Experience**
- See all related options at once
- Understand impact of choices immediately
- Less mental overhead switching between contexts

### 4. **Improved Validation**
- Validate related fields together
- Show conflicts and dependencies clearly
- Provide immediate feedback on screen

### 5. **Flexible Navigation**
- Move forward through screens naturally
- Jump back to any previous screen easily
- Skip optional sections entirely
- Return to edit from final review

## Implementation Notes

### Screen State Management
- Each screen maintains its own state
- Values propagate to dependent fields across screens
- Navigation preserves all collected data
- Auto-save progress between screens

### Conditional Logic
- Sections appear/disappear based on selections
- Options filtered by previous choices
- Real-time updates within screens
- Dependencies resolved immediately

### Navigation Patterns
- Consistent navigation controls on every screen
- Progress indicator showing current position
- Ability to skip optional screens
- Return to main menu at any time

### Error Handling
- Per-field validation with inline errors
- Screen-level validation before proceeding
- Clear error messages with suggested fixes
- Prevent navigation if critical errors exist

This screen-based approach provides a much more intuitive and efficient way for users to configure their Vortex installation while maintaining all the flexibility and power of the current system.