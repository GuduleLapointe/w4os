# Code Reorganization Migration Plan

This document tracks the migration of code from v1/v2/v3 folders to the new engine/wordpress/helpers structure.

## Current Structure (to be migrated from):
- v1/ - Legacy WordPress integration
- v2/ - Intermediate features
- v3/ - Latest features
- helpers/ - Direct API handlers

## New Structure (migrate to):
- engine/ - Core functionality (database, avatars, search, economy, grid)
- wordpress/ - WordPress-specific integration (admin pages, hooks, public features)
- helpers/ - Direct API endpoints (economy, search, profile helpers)

## Migration Tasks:

### Phase 1: Core Engine (engine/)
- [ ] Move database connection logic from v*/includes/database.php
- [ ] Move avatar functions from v*/includes/avatar.php
- [ ] Move search functionality from v*/includes/search.php
- [ ] Move economy logic from v*/includes/economy.php
- [ ] Move grid management from v*/includes/grid.php

### Phase 2: WordPress Integration (wordpress/)
- [ ] Move admin pages from v1/admin/
- [ ] Move settings pages from v*/admin/settings/
- [ ] Move WordPress hooks from v*/init.php
- [ ] Move public shortcodes from v*/public/
- [ ] Move WordPress-specific functions

### Phase 3: Helper APIs (helpers/)
- [ ] Move economy helper from existing helper files
- [ ] Move search helper from existing helper files
- [ ] Move profile helper from existing helper files
- [ ] Update API routing logic

### Phase 4: Settings & Credentials
- [ ] Preserve existing admin menu structure
- [ ] Preserve existing settings field types (especially credentials field)
- [ ] Ensure all current features remain enabled
- [ ] Remove v3 beta feature toggles

## Preservation Requirements:
- All current admin pages and menus
- All existing functionality
- Current settings field types
- Credential handling system
- All current features enabled by default