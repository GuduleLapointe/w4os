# w4os Development Roadmap

**Project Details**: https://github.com/users/GuduleLapointe/projects/2

*Last updated: Wed Jun 18 04:21:46 CEST 2025*

## v2.x Fixes (1/4 issues)

### Additional Issues

- [ ] Cannot  confirm setup if port is not 3306 #88
- [x] Registration and Avatar Models Not Working After Switching Robust #92
- [x] SearchURL use https if available #109 (fixed in 52bdd83 and d7b7fcb)
- [x] Registration ending to empty page #114 (fixed in ba190fd)
- [x] Forbidden character allowed in avatar registration #112 (Fixed in 037431eb and ac6652a0)

### Enhancements

- [ ] Port W4OS_Search logic to search_url() function (check w4os_provide_search value to choose between internal/external helpers)

## v3.0 Foundation (0/9 issues)

### ⬜ v3.0 Phase 1: Core Engine Foundation #94

**Engine Settings System** - Complete INI-based configuration management
- [x] Engine_Settings class with .ini file support
- [x] Credential encryption/decryption system
- [x] Service-based credential storage
- [x] OpenSim INI parsing integration
- [x] parse_ini_file_decode() for JSON value handling

**Settings Migration Framework** - Transition from WordPress options to Engine Settings
- [x] Constants migration (Helpers_Migration_2to3) with transform support
- [x] WordPress options migration (W4OS_Migration_2to3)
- [x] Database credential transforms (db_credentials) working correctly
- [x] Migration validation and testing tools
- [x] Settings validation page for comparing old vs new values


### ⬜ v3.0 Phase 2: WordPress Integration #95

**New Settings Architecture**
- [x] W4OS3_Settings class with tabbed interface
- [x] Settings validation and test pages
- [x] Migration test tools (settings-test.php, settings-validation.php)

**Core WordPress Classes**
- [x] W4OS3 main class with WordPress integration
- [x] W4OS3_Service for OpenSim service connections
- [x] W4OS3_Model for avatar model management
- [x] WordPress hooks and filters registration

**Admin Interface**
- [x] Engine Settings test page (functional)
- [x] Settings validation comparison page
- [x] Migration testing interface with accurate constant counting
- [x] Constants and WordPress options migration working


### ⬜ v3.0 Phase 3: Settings & Data Migration #96

**Migration Tools Built and Working**
- [x] Constants migration with transform support
- [x] WordPress options to INI migration
- [x] Database credential handling with encryption
- [x] Testing and validation framework
- [x] Fixed db_credentials transform for individual constants (CURRENCY_DB_HOST, etc.)
- [x] Precedence-based constant resolution working correctly

**Core Migration Features Working**
- [x] Database credential transforms (db_credentials)
- [x] Boolean and string transforms
- [x] URI parsing and hostname extraction
- [x] Precedence-based option resolution
- [x] JSON encoding/decoding for complex values

**Migration Validation**
- [x] Constants migration validated (46 mappings, 20 migrated)
- [x] WordPress options migration validated
- [x] Credential encryption/decryption working

**INI File Integration**
- [x] OpenSim_Ini class integration for import_ini_file()
- [x] Consistent file ordering and inclusion
- [x] JSON value decoding in INI files


### ⬜ v3.0 Phase 4: INI file handling (import, save and documentation) #97

**INI Import Strategy Alignment**
- [ ] Review and align INI file importation with constants/WP import strategies
- [ ] Implement consistent transform patterns for INI imports
- [ ] Validate INI import against WordPress and constants migration

**OpenSim Parameters Curation**
- [ ] Identify core OpenSim parameters actually used by plugin and helpers
- [ ] Create curated parameter set for plugin, helpers and standalone functionality
- [ ] Filter out unnecessary OpenSim config to focus on essential parameters
- [ ] Document parameter usage and dependencies

**Common issues fixes**
- [ ] https://github.com/GuduleLapointe/w4os/issues/109
- [ ] https://github.com/GuduleLapointe/opensim-helpers/issues/3
- [ ] https://github.com/GuduleLapointe/opensim-engine/issues/1

- [ ] SearchURL use https if available #109
- [ ] Combine search API and WebUI GuduleLapointe/opensim-helpers#3
- [ ] OpenSim 0.9.3 Support GuduleLapointe/opensim-engine#1

### ⬜ v3.0 Phase 5: Installation/Migration Wizard #98

**Multi-Step Wizard Framework**
- [x] Installation_Wizard engine class with step management
- [x] Session-based wizard state management with rollback capability
- [x] Form validation and error handling

**Dual-Platform Wizard**
- [x] Standalone helpers wizard page (install-wizard.php)
- [x] WordPress plugin wizard integration
- [x] Shared wizard logic between platforms using Engine classes

**Wizard Features**
- [x] Multi-step installation guidance with progress tracking
- [x] Configuration validation and testing at each step
- [x] Progress tracking and rollback capability
- [x] Bootstrap-based UI for helpers, WordPress admin UI for plugin

**Three Installation Modes**
- [x] Console credentials (recommended): validate and import via console connection
- [x] Full manual installation: database credentials and manual settings configuration
- [x] Importing live grid INI files: direct import from existing OpenSim configuration

**Wizard steps**
- [x] Wizard step 1: Initial configuration (detected existing config or new install)
- [ ] #110
- [ ] Wizard step 3: Grid Info (name, login uri...)
- [ ] Wizard step 4: Helpers
- [ ] Wizard step 5: Economy/Currency
- [ ] Wizard step 6: Validation

- [ ] Wizard step 2: Grid connection (console or db) #110

### ⬜ v3.0 Release #99

- [ ] All legacy features are working as initially
- [ ] Settings conversion is tested and working
- [ ] Settings conversion is optional: functionalty is perserved without conversion
- [ ] Once converted, working perfectly after removing old config files
- [ ] Can be used as a drop-in replacement for legacy 2.x

- [ ] Cannot  confirm setup if port is not 3306 #88
- [x] Registration and Avatar Models Not Working After Switching Robust #92
- [ ] LLCLIENTVIEW exception when using places search with no result GuduleLapointe/opensim-helpers#1

### Additional Issues

- [ ] check databases when settings are updated #51

## v3.1 Consolidation (0/4 issues)

### ⬜ v3.1 Phase 6: WordPress Admin Enhancement #100

**Admin Interface Refinement**
- [ ] Enhanced WordPress admin settings pages
- [ ] Improved user experience and workflow
- [ ] Better validation and error handling
- [ ] Streamlined configuration interface

**User-Avatar Relationship System**
- [ ] Complete WordPress user vs avatar links
- [ ] Support for one or multiple avatars per user
- [ ] Avatar management interface for users
- [ ] User role integration with avatar permissions
- [ ] #48 

- [ ] Automatic login after mail verification #48

### ⬜ v3.1 Phase 7: Console & CLI Tools #101

**Basic Command-Line Console Client**
- [ ] `opensim-cli` universal CLI executable 
- [ ] Compile script for portability
- [ ] Integration with Engine Settings system
- [ ] User-friendly CLI interface for grid management
- [ ] Alternative to screen bash tool when console connection enabled
- [ ] Essential OpenSim console commands support

**Port legacy bash script commands** from `dev/opensim/debian/opensim`
- [ ] `opensim-cli status`
- [ ] `opensim-cli <start|stop [now]|restart [now]> [instance1] [instance2] [...]`
- [ ] `opensim-cli console <args>`
- [ ] `opensim-cli ban <"Avatar Name"|UUID>`


### ⬜ v3.1 Phase 8: Testing & Quality Assurance #102

**Unit Testing Framework**
- [ ] Implement comprehensive unit tests
- [ ] Migration validation tests
- [ ] Engine Settings tests
- [ ] Database and credential tests
- [ ] Helpers functionalties tests
- [ ] WordPres plugin functionalties tests

**Live Installation Testing**
- [ ] Full migration testing on staging environments
- [ ] Performance validation
- [ ] Compatibility verification with various OpenSim versions

**Beta Distribution & Feedback**
- [ ] Beta release to test users
- [ ] Feedback collection and issue tracking
- [ ] Documentation and user guides
- [ ] Bug fixes and improvements based on feedback


### ⬜ v3.1 Phase 9: Legacy Cleanup #103

**v1/v2/v3 Deprecation**
- [x] Remove v3 beta feature toggles
- [ ] Move remaining v1 and v2 methods and properties
- [ ] Archive legacy code files
- [ ] Update documentation

**Code Organization**
- [ ] Move remaining helper APIs
- [ ] Consolidate duplicate functionality
- [ ] Update file structure documentation


## v3.2 Advanced Features (0/11 issues)

### ⬜ v3.2 Phase 10: Helpers Enhancement #105

**Standalone Helpers Admin Tools**
- [ ] Helpers settings page/tools interface
- [ ] Configuration management without WordPress
- [ ] Lightweight admin interface for helpers

**Enhanced Helper APIs**
- [ ] Improved economy helper functionality
- [ ] Advanced search helper features
- [ ] Profile helper enhancements
- [ ] #86 

**New helpers**
- [ ] #54 

- [ ] add grid map shortcode and block #54
- [ ] PHP8 and XML-RPC #86

### ⬜ v3.2 Phase 11: Advanced Grid Management #106

**Web-Based Grid Administration**
- [ ] Add/enable/start/stop/backup/delete regions
- [ ] User management (ban users, delete avatars)
- [ ] Grid statistics and monitoring
- [ ] Automated backup and maintenance tools
- [ ] #56 
- [ ] #61 

**Advanced User Controls**
- [ ] Enhanced avatar management interface
- [ ] Region ownership and permissions
- [ ] User activity monitoring and controls
- [ ] #60 
- [ ] #46 

- [ ] New feature: transaction history tab in profile #46
- [ ] admin notification when server is down #56
- [ ] Add feature to ban users #60
- [ ] Add feature: scan for unusual building activity #61

### ⬜ v3.2 Phase 12: Advanced Viewer Features #107

**Enhanced Web Search**
- [ ] Complete web search interface
- [ ] Advanced search filters and options
- [ ] Search result improvements

**Avatar & Grid Features**
- [ ] Avatar stream functionality (in progress)
- [ ] Destination guide completion (in progress)
- [ ] Enhanced grid information display

**Viewer Integration**
- [ ] Improved viewer compatibility
- [ ] Enhanced login experience
- [ ] Better grid connectivity features


### ⬜ v3.2 Phase 13: Localization & Accessibility #108

**Multi-Language Support**
- [x] Internationalization (i18n) framework
- [ ] Translation files for major languages
- [ ] Localized admin interfaces
- [ ] Multi-language user documentation

**Accessibility Improvements**
- [ ] WCAG compliance for admin interfaces
- [ ] Screen reader compatibility
- [ ] Keyboard navigation enhancements
- [ ] Accessibility testing and validation


### Additional Issues

- [ ] Automatic login after mail verification #48

