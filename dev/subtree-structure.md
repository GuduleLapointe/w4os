# Subtree Structure and Commands

## Current Subtree Structure

### 2.x Branch (current master):
- **helpers/** → `https://github.com/GuduleLapointe/opensim-helpers` master branch
  - Simple helpers structure for 2.x compatibility

### 3.x Branch:
- **helpers/** → `https://github.com/GuduleLapointe/opensim-helpers` 3.x branch
  - **helpers/engine/** → `https://github.com/GuduleLapointe/opensim-engine` 3.x branch  
  - **helpers/engine/opensim-rest/** → `https://github.com/GuduleLapointe/opensim-rest-php` 3.x branch

## Subtree Commands

### 2.x Branch Commands:
```bash
# Pull/Push helpers
git subtree pull --prefix=helpers https://github.com/GuduleLapointe/opensim-helpers master --squash
git subtree push --prefix=helpers https://github.com/GuduleLapointe/opensim-helpers master
```

### 3.x Branch Commands:
```bash
# Pull/Push main helpers (includes nested structure)
git subtree pull --prefix=helpers https://github.com/GuduleLapointe/opensim-helpers 3.x --squash
git subtree push --prefix=helpers https://github.com/GuduleLapointe/opensim-helpers 3.x

# Pull/Push nested engine (within helpers)
git subtree pull --prefix=helpers/engine https://github.com/GuduleLapointe/opensim-engine 3.x --squash
git subtree push --prefix=helpers/engine https://github.com/GuduleLapointe/opensim-engine 3.x

# Pull/Push deeply nested opensim-rest (within engine)
git subtree pull --prefix=helpers/engine/opensim-rest https://github.com/GuduleLapointe/opensim-rest-php 3.x --squash
git subtree push --prefix=helpers/engine/opensim-rest https://github.com/GuduleLapointe/opensim-rest-php 3.x
```

## Important Notes

**`--squash` flag usage:**
- ✅ **Always use `--squash` on PULL** - keeps your history clean by condensing upstream changes
- ❌ **Never use `--squash` on PUSH** - not supported and will cause errors

## Conversion Cleanup (if needed)

If you encounter "fatal: no submodule mapping found in .gitmodules" errors after conversion:

```bash
# Remove any remaining submodule references from Git index
git rm --cached helpers

# Remove orphaned submodule metadata
rm -rf .git/modules/opensim-helpers

# Commit the cleanup
git add .
git commit -m "Complete submodule to subtree conversion cleanup"

# Verify clean status
git submodule status
```

## Benefits Achieved

✅ **No checkout conflicts** - Historical commits work seamlessly  
✅ **Branch-specific dependencies** - 2.x uses simple helpers, 3.x uses complex nested structure  
✅ **Multi-level push/pull** - Can update any level of nesting independently (3.x only)  
✅ **Simplified workflow** - No submodule update commands needed  
✅ **Complete history** - All dependencies embedded in main repo  

## URL Correction Note

The helpers repository has moved from `https://github.com/magicoli/opensim-helpers.git` to `https://github.com/GuduleLapointe/opensim-helpers`. All future subtree operations should use the new URL.
