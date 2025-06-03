# Code Reorganization Migration Plan

This document tracks the migration of code from v1/v2/v3 folders to the new engine/wordpress/helpers structure.

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

## Migration Status:

### ✅ Phase 1: Core Engine Foundation (COMPLETED)
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

### ✅ Phase 2: WordPress Integration (COMPLETED)
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

### 🔄 Phase 3: Settings & Data Migration (MOSTLY COMPLETED)
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

### 🔄 Phase 4: INI Import Optimization (IN PROGRESS)
- [ ] **INI Import Strategy Alignment**
  - [ ] Review and align INI file importation with constants/WP import strategies
  - [ ] Implement consistent transform patterns for INI imports
  - [ ] Validate INI import against WordPress and constants migration
- [ ] **OpenSim Parameters Curation**
  - [ ] Identify core OpenSim parameters actually used by plugin and helpers
  - [ ] Create curated parameter set for plugin, helpers and standalone functionality
  - [ ] Filter out unnecessary OpenSim config to focus on essential parameters
  - [ ] Document parameter usage and dependencies

### 📋 Phase 5: Installation/Migration Wizard (PLANNED)
- [ ] **Multi-Step Wizard Framework**
  - [ ] Design user-friendly installation/upgrade wizard interface
  - [ ] Implement step-by-step configuration process
  - [ ] Create validation at each step (using principles established during settings tests)
- [ ] **Dual-Platform Wizard**
  - [ ] WordPress plugin wizard integration
  - [ ] Standalone helpers wizard page
  - [ ] Shared wizard logic between platforms
- [ ] **Wizard Features**
  - [ ] First-time installation guidance
  - [ ] Upgrade migration assistance
  - [ ] Configuration validation and testing
  - [ ] Progress tracking and rollback capability
- [ ] **Three Installation Modes**
  - [ ] Console credentials (recommended): all other settings imported via console
  - [ ] Full manual installation: require db credentials and setting up all settings
  - [ ] Importing live grid INI files: direct import from existing OpenSim configuration

### 📋 Phase 6: WordPress Admin Enhancement (PLANNED)
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

### 📋 Phase 7: Console & CLI Tools (PLANNED)
- [ ] **Basic Command-Line Console Client**
  - [ ] Alternative to screen bash tool when console connection enabled
  - [ ] Essential OpenSim console commands support
  - [ ] Integration with Engine Settings system
  - [ ] User-friendly CLI interface for grid management

### 📋 Phase 8: Testing & Quality Assurance (PLANNED)
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

### 🧹 Phase 9: Legacy Cleanup (PENDING)
- [ ] **v1/v2/v3 Deprecation**
  - [x] Remove v3 beta feature toggles
  - [ ] Move remaining v1 and v2 methods and properties
  - [ ] Archive legacy code files
  - [ ] Update documentation
- [ ] **Code Organization**
  - [ ] Move remaining helper APIs
  - [ ] Consolidate duplicate functionality
  - [ ] Update file structure documentation

## 🚀 Post-Migration Feature Development (FUTURE)

### 🆕 Phase 10: Helpers Enhancement (FUTURE)
- [ ] **Standalone Helpers Admin Tools**
  - [ ] Helpers settings page/tools interface
  - [ ] Configuration management without WordPress
  - [ ] Lightweight admin interface for helpers
- [ ] **Enhanced Helper APIs**
  - [ ] Improved economy helper functionality
  - [ ] Advanced search helper features
  - [ ] Profile helper enhancements

### 🆕 Phase 11: Advanced Grid Management (FUTURE)
- [ ] **Web-Based Grid Administration**
  - [ ] Add/enable/start/stop/backup/delete regions
  - [ ] User management (ban users, delete avatars)
  - [ ] Grid statistics and monitoring
  - [ ] Automated backup and maintenance tools
- [ ] **Advanced User Controls**
  - [ ] Enhanced avatar management interface
  - [ ] Region ownership and permissions
  - [ ] User activity monitoring and controls

### 🆕 Phase 12: "v3" Viewer Features Completion (FUTURE)
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

### 🆕 Phase 13: Localization & Accessibility (FUTURE)
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

### 🧪 Phase 5: Testing & Validation (ONGOING)
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
