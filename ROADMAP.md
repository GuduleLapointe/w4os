# w4os Development Roadmap

*This file is auto-generated from GitHub milestones and issues. Do not edit manually.*
*To update: run `dev/scripts/update-milestones.sh`*

## Overview

- **v2.x Fixes**: 0/2 issues (0% complete)
- **v3.0 Foundation**: 0/7 issues (0% complete)
- **v3.1 Consolidation**: 0/5 issues (0% complete)
- **v3.2 Advanced Features**: 0/11 issues (0% complete)

## Milestone: v2.x Fixes (v2.x)

### Additional Issues

- [ ] #88 Cannot  confirm setup if port is not 3306 (bug )
- [ ] #92 Registration and Avatar Models Not Working After Switching Robust (bug )

## Milestone: v3.0 Foundation (v3.0)

### v3.0 Phase 1: Core Engine Foundation

{"body":"**Engine Settings System** - Complete INI-based configuration management\n- [x] Engine_Settings class with .ini file support\n- [x] Credential encryption/decryption system\n- [x] Service-based credential storage\n- [x] OpenSim INI parsing integration\n- [x] parse_ini_file_decode() for JSON value handling\n\n**Settings Migration Framework** - Transition from WordPress options to Engine Settings\n- [x] Constants migration (Helpers_Migration_2to3) with transform support\n- [x] WordPress options migration (W4OS_Migration_2to3)\n- [x] Database credential transforms (db_credentials) working correctly\n- [x] Migration validation and testing tools\n- [x] Settings validation page for comparing old vs new values\n","labels":[{"id":"MDU6TGFiZWwyMTQ4NjY4ODYz","name":"enhancement","description":"New feature or request","color":"a2eeef"}],"number":94,"state":"OPEN","title":"v3.0 Phase 1: Core Engine Foundation"}


### v3.0 Phase 2: WordPress Integration

{"body":"- [x] **New Settings Architecture**\n  - [x] W4OS3_Settings class with tabbed interface\n  - [x] Settings validation and test pages\n  - [x] Migration test tools (settings-test.php, settings-validation.php)\n\n- [x] **Core WordPress Classes**\n  - [x] W4OS3 main class with WordPress integration\n  - [x] W4OS3_Service for OpenSim service connections\n  - [x] W4OS3_Model for avatar model management\n  - [x] WordPress hooks and filters registration\n\n- [x] **Admin Interface**\n  - [x] Engine Settings test page (functional)\n  - [x] Settings validation comparison page\n  - [x] Migration testing interface with accurate constant counting\n  - [x] Constants and WordPress options migration working","labels":[{"id":"MDU6TGFiZWwyMTQ4NjY4ODYz","name":"enhancement","description":"New feature or request","color":"a2eeef"}],"number":95,"state":"OPEN","title":"v3.0 Phase 2: WordPress Integration"}


### v3.0 Phase 3: Settings & Data Migration

{"body":"- [x] **Migration Tools Built and Working**\n  - [x] Constants migration with transform support\n  - [x] WordPress options to INI migration\n  - [x] Database credential handling with encryption\n  - [x] Testing and validation framework\n  - [x] Fixed db_credentials transform for individual constants (CURRENCY_DB_HOST, etc.)\n  - [x] Precedence-based constant resolution working correctly\n\n- [x] **Core Migration Features Working**\n  - [x] Database credential transforms (db_credentials)\n  - [x] Boolean and string transforms\n  - [x] URI parsing and hostname extraction\n  - [x] Precedence-based option resolution\n  - [x] JSON encoding/decoding for complex values\n\n- [x] **Migration Validation**\n  - [x] Constants migration validated (46 mappings, 20 migrated)\n  - [x] WordPress options migration validated\n  - [x] Credential encryption/decryption working\n\n- [x] **INI File Integration**\n  - [x] OpenSim_Ini class integration for import_ini_file()\n  - [x] Consistent file ordering and inclusion\n  - [x] JSON value decoding in INI files","labels":[{"id":"MDU6TGFiZWwyMTQ4NjY4ODYz","name":"enhancement","description":"New feature or request","color":"a2eeef"}],"number":96,"state":"OPEN","title":"v3.0 Phase 3: Settings & Data Migration"}


### v3.0 Phase 4: INI Import Optimization

{"body":"- [ ] **INI Import Strategy Alignment**\n  - [ ] Review and align INI file importation with constants/WP import strategies\n  - [ ] Implement consistent transform patterns for INI imports\n  - [ ] Validate INI import against WordPress and constants migration\n- [ ] **OpenSim Parameters Curation**\n  - [ ] Identify core OpenSim parameters actually used by plugin and helpers\n  - [ ] Create curated parameter set for plugin, helpers and standalone functionality\n  - [ ] Filter out unnecessary OpenSim config to focus on essential parameters\n  - [ ] Document parameter usage and dependencies\n","labels":[{"id":"MDU6TGFiZWwyMTQ4NjY4ODYz","name":"enhancement","description":"New feature or request","color":"a2eeef"}],"number":97,"state":"OPEN","title":"v3.0 Phase 4: INI Import Optimization"}


### v3.0 Phase 5: Installation/Migration Wizard

{"body":"- [x] **Multi-Step Wizard Framework**\n  - [x] Installation_Wizard engine class with step management\n  - [x] Session-based wizard state management with rollback capability\n  - [x] Form validation and error handling\n\n- [x] **Dual-Platform Wizard**\n  - [x] Standalone helpers wizard page (install-wizard.php)\n  - [x] WordPress plugin wizard integration\n  - [x] Shared wizard logic between platforms using Engine classes\n\n- [x] **Wizard Features**\n  - [x] Multi-step installation guidance with progress tracking\n  - [x] Configuration validation and testing at each step\n  - [x] Progress tracking and rollback capability\n  - [x] Bootstrap-based UI for helpers, WordPress admin UI for plugin\n\n- [x] **Three Installation Modes**\n  - [x] Console credentials (recommended): validate and import via console connection\n  - [x] Full manual installation: database credentials and manual settings configuration\n  - [x] Importing live grid INI files: direct import from existing OpenSim configuration\n\n- [ ] **Additional Features**\n  - [ ] Console-based settings import after credential validation\n  - [ ] INI file parsing and import functionality\n  - [ ] Advanced validation for complex configurations","labels":[{"id":"MDU6TGFiZWwyMTQ4NjY4ODYz","name":"enhancement","description":"New feature or request","color":"a2eeef"}],"number":98,"state":"OPEN","title":"v3.0 Phase 5: Installation/Migration Wizard"}


### v3.0 Release

{"body":"- [ ] All legacy features are working as initially\n- [ ] Settings conversion is tested and working\n- [ ] Settings conversion is optional: functionalty is perserved without conversion\n- [ ] Once converted, working perfectly after removing old config files\n- [ ] Can be used as a drop-in replacement for legacy 2.x\n","labels":[{"id":"MDU6TGFiZWwyMTQ4NjY4ODYz","name":"enhancement","description":"New feature or request","color":"a2eeef"}],"number":99,"state":"OPEN","title":"v3.0 Release"}


### Additional Issues

- [ ] #51 check databases when settings are updated (enhancement )

## Milestone: v3.1 Consolidation (v3.1)

### v3.1 Phase 6: WordPress Admin Enhancement

{"body":"**Admin Interface Refinement**\n- [ ] Enhanced WordPress admin settings pages\n- [ ] Improved user experience and workflow\n- [ ] Better validation and error handling\n- [ ] Streamlined configuration interface\n\n**User-Avatar Relationship System**\n- [ ] Complete WordPress user vs avatar links\n- [ ] Support for one or multiple avatars per user\n- [ ] Avatar management interface for users\n- [ ] User role integration with avatar permissions\n- [ ] #48 \n","labels":[{"id":"MDU6TGFiZWwyMTQ4NjY4ODYz","name":"enhancement","description":"New feature or request","color":"a2eeef"}],"number":100,"state":"OPEN","title":"v3.1 Phase 6: WordPress Admin Enhancement"}


### v3.1 Phase 7: Console & CLI Tools

{"body":"**Basic Command-Line Console Client**\n- [ ] Alternative to screen bash tool when console connection enabled\n- [ ] Essential OpenSim console commands support\n- [ ] Integration with Engine Settings system\n- [ ] User-friendly CLI interface for grid management\n","labels":[{"id":"MDU6TGFiZWwyMTQ4NjY4ODYz","name":"enhancement","description":"New feature or request","color":"a2eeef"}],"number":101,"state":"OPEN","title":"v3.1 Phase 7: Console & CLI Tools"}


### v3.1 Phase 8: Testing & Quality Assurance

{"body":"**Unit Testing Framework**\n- [ ] Implement comprehensive unit tests\n- [ ] Migration validation tests\n- [ ] Engine Settings tests\n- [ ] Database and credential tests\n- [ ] Helpers functionalties tests\n- [ ] WordPres plugin functionalties tests\n\n**Live Installation Testing**\n- [ ] Full migration testing on staging environments\n- [ ] Performance validation\n- [ ] Compatibility verification with various OpenSim versions\n\n**Beta Distribution & Feedback**\n- [ ] Beta release to test users\n- [ ] Feedback collection and issue tracking\n- [ ] Documentation and user guides\n- [ ] Bug fixes and improvements based on feedback\n","labels":[{"id":"MDU6TGFiZWwyMTQ4NjY4ODYz","name":"enhancement","description":"New feature or request","color":"a2eeef"}],"number":102,"state":"OPEN","title":"v3.1 Phase 8: Testing & Quality Assurance"}


### v3.1 Phase 9: Legacy Cleanup

{"body":"**v1/v2/v3 Deprecation**\n- [x] Remove v3 beta feature toggles\n- [ ] Move remaining v1 and v2 methods and properties\n- [ ] Archive legacy code files\n- [ ] Update documentation\n\n**Code Organization**\n- [ ] Move remaining helper APIs\n- [ ] Consolidate duplicate functionality\n- [ ] Update file structure documentation\n","labels":[{"id":"MDU6TGFiZWwyMTQ4NjY4ODYz","name":"enhancement","description":"New feature or request","color":"a2eeef"}],"number":103,"state":"OPEN","title":"v3.1 Phase 9: Legacy Cleanup"}


### Additional Issues

- [ ] #93 No way to delete an avatar from simulator (enhancement )

## Milestone: v3.2 Advanced Features (v3.2)

### v3.2 Phase 10: Helpers Enhancement

{"body":"**Standalone Helpers Admin Tools**\n- [ ] Helpers settings page/tools interface\n- [ ] Configuration management without WordPress\n- [ ] Lightweight admin interface for helpers\n\n**Enhanced Helper APIs**\n- [ ] Improved economy helper functionality\n- [ ] Advanced search helper features\n- [ ] Profile helper enhancements\n- [ ] #86 \n\n**New helpers**\n- [ ] #54 \n","labels":[{"id":"MDU6TGFiZWwyMTQ4NjY4ODYz","name":"enhancement","description":"New feature or request","color":"a2eeef"}],"number":105,"state":"OPEN","title":"v3.2 Phase 10: Helpers Enhancement"}

- [ ] #54 add grid map shortcode and block
- [ ] #86 PHP8 and XML-RPC

### v3.2 Phase 11: Advanced Grid Management

{"body":"**Web-Based Grid Administration**\n- [ ] Add/enable/start/stop/backup/delete regions\n- [ ] User management (ban users, delete avatars)\n- [ ] Grid statistics and monitoring\n- [ ] Automated backup and maintenance tools\n- [ ] #56 \n- [ ] #61 \n\n**Advanced User Controls**\n- [ ] Enhanced avatar management interface\n- [ ] Region ownership and permissions\n- [ ] User activity monitoring and controls\n- [ ] #60 \n- [ ] #46 ","labels":[{"id":"MDU6TGFiZWwyMTQ4NjY4ODYz","name":"enhancement","description":"New feature or request","color":"a2eeef"}],"number":106,"state":"OPEN","title":"v3.2 Phase 11: Advanced Grid Management"}

- [ ] #46 New feature: transaction history tab in profile
- [ ] #56 admin notification when server is down
- [ ] #60 Add feature to ban users
- [ ] #61 Add feature: scan for unusual building activity

### v3.2 Phase 12: Advanced Viewer Features

{"body":"**Enhanced Web Search**\n- [ ] Complete web search interface\n- [ ] Advanced search filters and options\n- [ ] Search result improvements\n\n**Avatar & Grid Features**\n- [ ] Avatar stream functionality (in progress)\n- [ ] Destination guide completion (in progress)\n- [ ] Enhanced grid information display\n\n**Viewer Integration**\n- [ ] Improved viewer compatibility\n- [ ] Enhanced login experience\n- [ ] Better grid connectivity features\n","labels":[{"id":"MDU6TGFiZWwyMTQ4NjY4ODYz","name":"enhancement","description":"New feature or request","color":"a2eeef"}],"number":107,"state":"OPEN","title":"v3.2 Phase 12: Advanced Viewer Features"}


### v3.2 Phase 13: Localization & Accessibility

{"body":"**Multi-Language Support**\n- [x] Internationalization (i18n) framework\n- [ ] Translation files for major languages\n- [ ] Localized admin interfaces\n- [ ] Multi-language user documentation\n\n**Accessibility Improvements**\n- [ ] WCAG compliance for admin interfaces\n- [ ] Screen reader compatibility\n- [ ] Keyboard navigation enhancements\n- [ ] Accessibility testing and validation\n","labels":[{"id":"MDU6TGFiZWwyMTQ4NjY4ODYz","name":"enhancement","description":"New feature or request","color":"a2eeef"}],"number":108,"state":"OPEN","title":"v3.2 Phase 13: Localization & Accessibility"}


### Additional Issues

- [ ] #48 Automatic login after mail verification (enhancement )

---

## Links

- 📊 [GitHub Project](https://github.com/GuduleLapointe/w4os/projects) - Live progress tracking
- 🐛 [GitHub Issues](https://github.com/GuduleLapointe/w4os/issues) - Bug reports and feature requests

*Last updated: mar. 17 juin 2025 09:32:36 CEST*
*Generated from GitHub milestones and issues*
