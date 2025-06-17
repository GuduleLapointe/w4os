# W4OS Project Architecture Rules

This WordPress plugin includes the OpenSim Helpers library, which
iself includes the OpenSim Engine library. Each library could be used as standalone
or included in a totally unrelated project. Therefore neither helpers or enginer 
could ever rely on a class, method or function defined in the project including them.

w4os/ # WordPress plugin
└── helpers/ # Viewer helpers, API, minimal web interface
    ├── bootstrap.php
    └── engine/ # Pure business logic, configuration management, no direct output
        └── boostrap.php

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   W4OS Plugin   │    │     Helpers     │    │     Engine      │
│  (true Web UI)  │────│      (API)      │────│   (Pure Data)   │
│                 │    │                 │    │                 │
│ • WP integration│    │ • HTTP handling │    │ • Data process  │
│ • Admin pages   │    │ • HTML output   │    │ • Database ops  │
│ • WP hooks      │    │ • Form process  │    │ • OpenSim logic │
│ • User mgmt     │    │ • API endpoints │    │ • Config mgmt   │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## Core Principles
- **engine/** and **helpers/** are framework-agnostic standalone components
- **wordpress/** contains WordPress-specific integration code
- No WordPress knowledge in engine/helpers (no wp functions, no w4os naming)

## Data Flow Rules
- Use generic session variables (`wizard_data`, not `w4os_wizard_data`)
- Engine components receive clean data contracts, not framework-specific structures
- Calling applications handle their own internal logic (migration vs update vs new)

## Naming Conventions
- Generic components: `Engine_Settings`, `Installation_Wizard`
- WordPress components: `W4OS_*`, `w4os_*`
- Session data: generic names that work with any calling framework

## Separation of Concerns
- **Engine**: Pure business logic, configuration management
- **Helpers**: Standalone web interface for engine
- **WordPress**: Integration layer, handles WP-specific features

## Data Contracts
- Pass only essential data between layers
- Use arrays with `values`, `return_url`, `timestamp`
- No framework metadata in generic components

Always apply these rules when suggesting code changes.


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
- helpers/engine/ - Core functionality (database, avatars, search, economy, grid)
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
