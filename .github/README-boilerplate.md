# .github Boilerplate

This repository contains GitHub configuration files for automatic releases and funding that can be added to any project using git subtree.

## Files included:

- **workflows/release.yml** - GitHub Actions workflow for automatic releases
- **FUNDING.yml** - GitHub funding configuration template
- **FUNDING-speculoos.yml** - Speculoos-specific funding configuration

## Usage with git subtree:

Add to a new project:
```bash
git subtree add --prefix=.github git@git.magiiic.com:magic/github-autorelease.git main --squash
git subtree pull --prefix=.github git@git.magiiic.com:magic/github-autorelease.git main --squash
```

## After adding to your project:

1. Choose the appropriate FUNDING.yml (copy one of FUNDING-*.yml and adjust if needed)
2. Remove unused FUNDING files
3. Commit and push to GitHub
4. Create tags in format `v1.0.0` to trigger automatic releases

## Features:

- Automatic release creation from git tags
- ZIP asset generation with proper exclusions
- Honors .gitignore and .distignore files
- Uses tag message as release notes
- Supports prerelease detection (tags with -beta, -rc, etc.)

## Requirements:

- Repository must have appropriate .gitignore and .distignore files
- Tags must follow `v*.*.*` pattern (e.g., v1.0.0, v1.2.3-beta)
