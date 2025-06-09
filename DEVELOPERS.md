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
