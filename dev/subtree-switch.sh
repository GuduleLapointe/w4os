#!/bin/bash

# Script to convert nested submodules to subtrees
# This script checks for unpushed changes before each conversion

set -e  # Exit on any error

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to check if a directory has unpushed changes
check_unpushed_changes() {
    local dir="$1"
    local repo_name="$2"
    
    echo -e "${YELLOW}Checking $repo_name for unpushed changes...${NC}"
    
    cd "$dir"
    
    # Check if we're in a git repository (handle both .git directory and .git file for submodules)
    if [ ! -d ".git" ] && [ ! -f ".git" ]; then
        echo -e "${RED}Error: $dir is not a git repository${NC}"
        return 1
    fi
    
    # Check for uncommitted changes
    if [ -n "$(git status --porcelain)" ]; then
        echo -e "${RED}Error: $repo_name has uncommitted changes${NC}"
        git status --short
        return 1
    fi
    
    # Check for unpushed commits
    local branch=$(git rev-parse --abbrev-ref HEAD)
    local unpushed=$(git rev-list --count @{upstream}..HEAD 2>/dev/null || echo "unknown")
    
    if [ "$unpushed" = "unknown" ]; then
        echo -e "${YELLOW}Warning: Cannot determine upstream for $repo_name (branch: $branch)${NC}"
        echo "Please ensure this branch is pushed manually"
        read -p "Continue anyway? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            return 1
        fi
    elif [ "$unpushed" -gt 0 ]; then
        echo -e "${RED}Error: $repo_name has $unpushed unpushed commits on branch $branch${NC}"
        git log --oneline @{upstream}..HEAD
        return 1
    else
        echo -e "${GREEN}✓ $repo_name is clean and up to date${NC}"
    fi
    
    cd - > /dev/null
    return 0
}

# Function to convert a submodule to subtree
convert_submodule_to_subtree() {
    local submodule_path="$1"
    local repo_url="$2"
    local branch="${3:-master}"
    local parent_dir=$(dirname "$submodule_path")
    local submodule_name=$(basename "$submodule_path")
    
    echo -e "${YELLOW}Converting $submodule_path to subtree...${NC}"
    
    cd "$parent_dir"
    
    # Remove submodule
    git submodule deinit "$submodule_name"
    git rm "$submodule_name"
    rm -rf ".git/modules/$submodule_name"
    
    # Add as subtree
    git subtree add --prefix="$submodule_name" "$repo_url" "$branch" --squash
    
    # Commit the conversion
    git add .
    git commit -m "Convert $submodule_name from submodule to subtree"
    
    echo -e "${GREEN}✓ Converted $submodule_path to subtree${NC}"
    
    cd - > /dev/null
}

# Main conversion process
main() {
    echo "=== Submodule to Subtree Conversion Script ==="
    echo
    
    # Check main repo first
    if ! check_unpushed_changes "." "main w4os repo"; then
        echo -e "${RED}Please commit and push changes in main repo before proceeding${NC}"
        exit 1
    fi
    
    echo
    echo "=== Phase 1: Convert opensim-rest (deepest level) ==="
    
    # Check opensim-rest repo
    if ! check_unpushed_changes "helpers/engine/opensim-rest" "opensim-rest"; then
        echo -e "${RED}Please commit and push changes in opensim-rest before proceeding${NC}"
        exit 1
    fi
    
    # Check engine repo (parent of opensim-rest)
    if ! check_unpushed_changes "helpers/engine" "opensim-engine"; then
        echo -e "${RED}Please commit and push changes in opensim-engine before proceeding${NC}"
        exit 1
    fi
    
    echo
    read -p "Convert opensim-rest submodule to subtree? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        convert_submodule_to_subtree "helpers/engine/opensim-rest" "https://github.com/GuduleLapointe/opensim-rest-php" "master"
        
        # Push the engine repo changes
        cd helpers/engine
        echo -e "${YELLOW}Pushing opensim-engine changes...${NC}"
        git push
        cd ../..
        echo -e "${GREEN}✓ Pushed opensim-engine with subtree conversion${NC}"
    fi
    
    echo
    echo "=== Phase 2: Convert engine ==="
    
    # Update engine submodule to get the latest changes
    echo -e "${YELLOW}Updating engine submodule...${NC}"
    git submodule update --remote helpers/engine
    
    # Check helpers repo (parent of engine)
    if ! check_unpushed_changes "helpers" "opensim-helpers"; then
        echo -e "${RED}Please commit and push changes in opensim-helpers before proceeding${NC}"
        exit 1
    fi
    
    echo
    read -p "Convert engine submodule to subtree? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        convert_submodule_to_subtree "helpers/engine" "https://github.com/GuduleLapointe/opensim-engine" "master"
        
        # Push the helpers repo changes
        cd helpers
        echo -e "${YELLOW}Pushing opensim-helpers changes...${NC}"
        git push
        cd ..
        echo -e "${GREEN}✓ Pushed opensim-helpers with subtree conversion${NC}"
    fi
    
    echo
    echo "=== Phase 3: Convert helpers ==="
    
    # Update helpers submodule to get the latest changes
    echo -e "${YELLOW}Updating helpers submodule...${NC}"
    git submodule update --remote helpers
    
    echo
    read -p "Convert helpers submodule to subtree in main repo? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        convert_submodule_to_subtree "helpers" "https://github.com/GuduleLapointe/opensim-helpers" "master"
        echo -e "${GREEN}✓ Converted helpers to subtree in main repo${NC}"
    fi
    
    echo
    echo "=== Conversion Complete! ==="
    echo "You can now push changes to nested subtrees using:"
    echo "  git subtree push --prefix=helpers https://github.com/GuduleLapointe/opensim-helpers master"
    echo "  git subtree push --prefix=helpers/engine https://github.com/GuduleLapointe/opensim-engine master"
    echo "  git subtree push --prefix=helpers/engine/opensim-rest https://github.com/GuduleLapointe/opensim-rest-php master"
}

# Run main function
main "$@"
