# Code Reorganization Migration Plan

## Background & Context

This migration addresses fundamental architectural limitations that have accumulated over 10 years of development. The w4os project originated as a merge of multiple independent OpenSimulator-related projects, each with their own structure and standards. While initially designed to maintain backwards compatibility and allow cross-updates with original projects, the reality is that these original projects have rarely been updated, and cross-pollination of improvements has not occurred.

The immediate catalyst for this restructuring was a user registration issue: OpenSimulator allows multiple avatars to share the same email address, but WordPress does not allow multiple users with the same email. The old architecture tightly coupled WordPress users with avatars, making this a complex problem to solve. However, addressing this revealed deeper structural issues that needed comprehensive resolution.

**Key challenges with the legacy codebase:**
- 10+ years of backwards compatibility layers creating technical debt
- Mixed architectural patterns from merged projects
- Tight coupling between WordPress users and OpenSim avatars
- Scattered configuration across multiple systems (PHP constants, WordPress options, INI files)
- Difficult maintenance and feature development due to structural complexity

**V3 represents a breaking change** that will:
- ✅ Decouple avatar management from WordPress user accounts
- ✅ Establish a clean, modern architecture for future development
- ✅ Provide unified configuration management through Engine Settings
- ✅ Enable easier creation of additional modules and features
- ✅ Significantly improve both user and administrator experience
- ✅ Allow the project to evolve sustainably for the next decade

**V3 nested modules structure**

w4os relies on several related projects, v3 release will involve changes in most of them. Therefore the development is made in parallel between all the projects to ensure ongoing compatibility and integration.

- **[w4os](http://github.com/GuduleLapointe/w4os/)**: the wordpress plugin providing a full web interface for OpenSimulator. _Depends on helpers and engine, included in w4os releases._
- **[OpenSim Helpers](https://github.com/magicoli/opensim-helpers)**: essentially the OpenSimulator helpers (API and tools queried directly by the viewer or the simulator, or querying directly the simulator), with additional general use UI elements (splash page, grid info block, registration form...). Can be used either as part of another project like w4os or as standalone complement to OpenSim servers. _Standalone or library, depends solely on engine, included in helpers releases._
- **[OpenSim Engine](https://github.com/magicoli/opensim-engine)**: the main engine shared between all implementations, provides data manipulation, transformation, validation, storage. It is only a library and doesn't do anything by itself, so it needs to be included in another project like **w4os** or **OpenSim Helpers**. _Library._

w4os, OpenSim Helpers and OpenSim Engine versioning is synchronized as their development is tightly linked, even though each of them has its own repository and can be installed independently.

┌─────────┐
│  w4os   │ (WordPress Plugin)
└────┬────┘
     │ depends on
┌────▼────┐
│ helpers │ (API + UI Elements)  
└────┬────┘
     │ depends on
┌────▼────┐
│ engine  │ (Core Library)
└─────────┘
(more detail in DEVELOPERS.md)

- **[OpenSim REST PHP](https://github.com/magicoli/opensim-rest-php)**: a small library providing tools to communicate with OpenSimulator instances and viewers with REST protocol. It also provides a command-line client. The library is included in OpenSim Engine (not the CLI), but the versioning is independent. It is designed to be light and as generic as possible, with few dependencies. _Library: included in OpenSim Engine releases; Binary executable: standalone._

**Purpose of this document:**

This document tracks the migration of code from the legacy v1/v2/v3 folder structure to the new engine/wordpress/helpers architecture.

## Current Structure (to be migrated from):
- v1/ - Legacy WordPress integration
- v2/ - Intermediate features
- v3/ - Latest features
- helpers/ - Direct API handlers

## New Structure (migrate to):
- engine/ - Core functionality (database, avatars, search, economy, grid)
    - minimal standalone web interface, independent from CMS
    - provides basic forms, info blocks and lists basic html that CMS can integrate and customize
- wordpress/ - WordPress-specific integration (admin pages, hooks, public features)
- helpers/ - Direct API endpoints (economy, search, profile helpers)

## Release Milestones

### v2.x Fixes
Legacy code bugs requiring immediate attention.
Will be fixed in 3.0 release, will be irrelevant starting from 3.1 release. As v3.0 release approaches and is assumed to be fully backwards compatible, there will be no more 2.x release.

### 🎯 v3.0 Foundation  
Foundation for v3 architecture. Drop-in replacement with full backward compatibility (functionalty and config files). Includes v2 fixes.

### 🎯 v3.1 Consolidation
Full modern architecture, all legacy code is removed and legacy config files are ignored or deleted.

### 🎯 v3.2 Advanced Features
New capabilities.

## v3.0 Foundation

### ✅ v3.0 Phase 1: Core Engine Foundation (COMPLETED)
- [x] **Engine Settings System** - Complete INI-based configuration management
  - [x] Engine_Settings class with .ini file support
  - [x] Credential encryption/decryption system
  - [x] Service-based credential storage
  - [x] OpenSim INI parsing integration
  - [x] parse_ini_file_decode() for JSON value handling
- [x] **Settings Migration Framework** - Transition from WordPress options to Engine Settings
  - [x] Constants migration (Helpers_Migration_2to3) with transform support
  - [x] WordPress options migration (W4OS_Migration_2to3)
  - [x] Database credential transforms (db_credentials) working correctly
  - [x] Migration validation and testing tools
  - [x] Settings validation page for comparing old vs new values

### ✅ v3.0 Phase 2: WordPress Integration (COMPLETED)
- [x] **New Settings Architecture**
  - [x] W4OS3_Settings class with tabbed interface
  - [x] Settings validation and test pages
  - [x] Migration test tools (settings-test.php, settings-validation.php)
- [x] **Core WordPress Classes**
  - [x] W4OS3 main class with WordPress integration
  - [x] W4OS3_Service for OpenSim service connections
  - [x] W4OS3_Model for avatar model management
  - [x] WordPress hooks and filters registration
- [x] **Admin Interface**
  - [x] Engine Settings test page (functional)
  - [x] Settings validation comparison page
  - [x] Migration testing interface with accurate constant counting
  - [x] Constants and WordPress options migration working

### 🔄 v3.0 Phase 3: Settings & Data Migration (COMPLETED)
- [x] **Migration Tools Built and Working**
  - [x] Constants migration with transform support
  - [x] WordPress options to INI migration
  - [x] Database credential handling with encryption
  - [x] Testing and validation framework
  - [x] Fixed db_credentials transform for individual constants (CURRENCY_DB_HOST, etc.)
  - [x] Precedence-based constant resolution working correctly
- [x] **Core Migration Features Working**
  - [x] Database credential transforms (db_credentials)
  - [x] Boolean and string transforms
  - [x] URI parsing and hostname extraction
  - [x] Precedence-based option resolution
  - [x] JSON encoding/decoding for complex values
- [x] **Migration Validation**
  - [x] Constants migration validated (46 mappings, 20 migrated)
  - [x] WordPress options migration validated
  - [x] Credential encryption/decryption working
- [x] **INI File Integration**
  - [x] OpenSim_Ini class integration for import_ini_file()
  - [x] Consistent file ordering and inclusion
  - [x] JSON value decoding in INI files

### 🔄 v3.0 Phase 4: INI Import Optimization (IN PROGRESS)
- [ ] **INI Import Strategy Alignment**
  - [ ] Review and align INI file importation with constants/WP import strategies
  - [ ] Implement consistent transform patterns for INI imports
  - [ ] Validate INI import against WordPress and constants migration
- [ ] **OpenSim Parameters Curation**
  - [ ] Identify core OpenSim parameters actually used by plugin and helpers
  - [ ] Create curated parameter set for plugin, helpers and standalone functionality
  - [ ] Filter out unnecessary OpenSim config to focus on essential parameters
  - [ ] Document parameter usage and dependencies

### 🔄 v3.0 Phase 5: Installation/Migration Wizard (IN PROGRESS)
- [x] **Multi-Step Wizard Framework**
  - [x] Installation_Wizard engine class with step management
  - [x] Session-based wizard state management with rollback capability
  - [x] Form validation and error handling
- [x] **Dual-Platform Wizard**
  - [x] Standalone helpers wizard page (install-wizard.php)
  - [x] WordPress plugin wizard integration
  - [x] Shared wizard logic between platforms using Engine classes
- [x] **Wizard Features**
  - [x] Multi-step installation guidance with progress tracking
  - [x] Configuration validation and testing at each step
  - [x] Progress tracking and rollback capability
  - [x] Bootstrap-based UI for helpers, WordPress admin UI for plugin
- [x] **Three Installation Modes**
  - [x] Console credentials (recommended): validate and import via console connection
  - [x] Full manual installation: database credentials and manual settings configuration
  - [x] Importing live grid INI files: direct import from existing OpenSim configuration
- [ ] **Additional Features**
  - [ ] Console-based settings import after credential validation
  - [ ] INI file parsing and import functionality
  - [ ] Advanced validation for complex configurations

### 🔄 v3.0 Release
  - [ ] All legacy features are working as initially
  - [ ] Settings conversion is tested and working
  - [ ] Settings conversion is optional: functionalty is perserved without conversion
  - [ ] Once converted, working perfectly after removing old config files
  - [ ] Can be used as a drop-in replacement for legacy 2.x

## v3.1 Consolidation

### 📋 v3.1 Phase 6: WordPress Admin Enhancement (PLANNED)
- [ ] **Admin Interface Refinement**
  - [ ] Enhanced WordPress admin settings pages
  - [ ] Improved user experience and workflow
  - [ ] Better validation and error handling
  - [ ] Streamlined configuration interface
- [ ] **User-Avatar Relationship System**
  - [ ] Complete WordPress user vs avatar links
  - [ ] Support for one or multiple avatars per user
  - [ ] Avatar management interface for users
  - [ ] User role integration with avatar permissions

### 📋 v3.1 Phase 7: Console & CLI Tools (PLANNED)
- [ ] **Basic Command-Line Console Client**
  - [ ] Alternative to screen bash tool when console connection enabled
  - [ ] Essential OpenSim console commands support
  - [ ] Integration with Engine Settings system
  - [ ] User-friendly CLI interface for grid management

### 📋 v3.1 Phase 8: Testing & Quality Assurance (PLANNED)
- [ ] **Unit Testing Framework**
  - [ ] Implement comprehensive unit tests
  - [ ] Migration validation tests
  - [ ] Engine Settings tests
  - [ ] Database and credential tests
  - [ ] Helpers functionalties tests
  - [ ] WordPres plugin functionalties tests
- [ ] **Live Installation Testing**
  - [ ] Full migration testing on staging environments
  - [ ] Performance validation
  - [ ] Compatibility verification with various OpenSim versions
- [ ] **Beta Distribution & Feedback**
  - [ ] Beta release to test users
  - [ ] Feedback collection and issue tracking
  - [ ] Documentation and user guides
  - [ ] Bug fixes and improvements based on feedback

### 🧹 v3.1 Phase 9: Legacy Cleanup (PENDING)
- [ ] **v1/v2/v3 Deprecation**
  - [x] Remove v3 beta feature toggles
  - [ ] Move remaining v1 and v2 methods and properties
  - [ ] Archive legacy code files
  - [ ] Update documentation
- [ ] **Code Organization**
  - [ ] Move remaining helper APIs
  - [ ] Consolidate duplicate functionality
  - [ ] Update file structure documentation

### 📋 v3.1 Release
  - [ ] Old config files can be safely deleted
  - [ ] No more legacy code is used
  - [ ] Old v1/, v2/, v3/ and all remaining legacy code are deleted
  - [ ] Legacy settings pages are replaced by new v3 settings pages
  - [ ] v3 is fully functional with new features

## 🚀 v3.2 Advanced Features

### 🆕 v3.2 Phase 10: Helpers Enhancement (FUTURE)
- [ ] **Standalone Helpers Admin Tools**
  - [ ] Helpers settings page/tools interface
  - [ ] Configuration management without WordPress
  - [ ] Lightweight admin interface for helpers
- [ ] **Enhanced Helper APIs**
  - [ ] Improved economy helper functionality
  - [ ] Advanced search helper features
  - [ ] Profile helper enhancements

### 🆕 v3.2 Phase 11: Advanced Grid Management (FUTURE)
- [ ] **Web-Based Grid Administration**
  - [ ] Add/enable/start/stop/backup/delete regions
  - [ ] User management (ban users, delete avatars)
  - [ ] Grid statistics and monitoring
  - [ ] Automated backup and maintenance tools
- [ ] **Advanced User Controls**
  - [ ] Enhanced avatar management interface
  - [ ] Region ownership and permissions
  - [ ] User activity monitoring and controls

### 🆕 v3.2 Phase 12: "v3" Viewer Features Completion (FUTURE)
- [ ] **Enhanced Web Search**
  - [ ] Complete web search interface
  - [ ] Advanced search filters and options
  - [ ] Search result improvements
- [ ] **Avatar & Grid Features**
  - [ ] Avatar stream functionality
  - [ ] Destination guide completion
  - [ ] Enhanced grid information display
- [ ] **Viewer Integration**
  - [ ] Improved viewer compatibility
  - [ ] Enhanced login experience
  - [ ] Better grid connectivity features

### 🆕 v3.2 Phase 13: Localization & Accessibility (FUTURE)
- [ ] **Multi-Language Support**
  - [ ] Internationalization (i18n) framework
  - [ ] Translation files for major languages
  - [ ] Localized admin interfaces
  - [ ] Multi-language user documentation
- [ ] **Accessibility Improvements**
  - [ ] WCAG compliance for admin interfaces
  - [ ] Screen reader compatibility
  - [ ] Keyboard navigation enhancements
  - [ ] Accessibility testing and validation

### 📋 v3.2 Release

No specific plan for v3.2 release yet, to be determined after v3.1 completion.

### 🧪 v3 Testing & Validation (ONGOING)
- [x] **Migration Testing Tools**
  - [x] Settings comparison validation
  - [x] Constants migration testing
  - [x] Database credential validation
- [x] **Test Pages Functional**
  - [x] Engine Settings test interface
  - [x] WordPress vs Engine Settings comparison
  - [x] Migration result validation
- [ ] **Production Testing**
  - [ ] Full migration testing on staging
  - [ ] Performance validation
  - [ ] Compatibility verification

## Current Status Summary:

**✅ COMPLETED:**
- Engine Settings foundation with INI support and JSON value handling
- Migration framework for constants and WordPress options working correctly
- Settings validation and testing tools with accurate constant counting
- Core WordPress integration classes
- Database credential encryption system with transform support
- Constants migration validated (46 mappings, working correctly)
- WordPress options to Engine Settings migration framework

**🔄 IN PROGRESS:**
- **Phase 4**: INI Import Optimization and OpenSim Parameters Curation
  - Reviewing INI file importation strategy alignment
  - Curating essential OpenSim parameters for plugin/helpers usage
  - Filtering out unnecessary OpenSim config for focused functionality

**📋 NEXT PRIORITIES:**
1. **Complete Phase 4**: INI Import Strategy & Parameter Curation
   - Align INI importation with constants/WP import patterns
   - Identify and document core OpenSim parameters actually used
   - Create curated parameter set for focused functionality
2. **Phase 5**: Multi-Step Installation/Migration Wizard
   - User-friendly wizard interface using Settings Tests principles
   - Dual-platform support (WordPress plugin + standalone helpers)
   - Step-by-step configuration with validation and rollback
   - 3 installation modes (console credentials, full manual, INI import)
3. **Phases 6-8**: WordPress Admin Enhancement, Console Tools, Testing & QA
4. **Phase 9**: Legacy code deprecation and cleanup

## Technical Notes:

### Migration Strategy:
- **Backward Compatible**: Old get_option() calls still work during transition
- **Validation Built-in**: Every migration step includes comparison validation
- **Encrypted Credentials**: Database and console credentials stored encrypted
- **Precedence Rules**: Multiple option sources handled with clear precedence

### Testing Approach:
- Side-by-side comparison of old vs new values
- Real-time migration testing with rollback capability
- Credential validation with actual connection testing
- Admin interface for monitoring migration progress

## Preservation Requirements:
- ✅ All current admin pages and menus (preserved in new structure)
- ✅ All existing functionality (maintained through compatibility layer)
- ✅ Current settings field types (enhanced with new types)
- ✅ Credential handling system (improved with encryption)
- ✅ All current features enabled by default (no functionality loss)
