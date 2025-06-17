#!/bin/bash

output=ROADMAP.md

set -e

echo "Fetching milestones and issues from GitHub repository..."

# Check if gh CLI is available and authenticated
if ! command -v gh &> /dev/null; then
    echo "❌ GitHub CLI not found. Install with: brew install gh"
    exit 1
fi

if ! gh auth status &> /dev/null; then
    echo "❌ GitHub CLI not authenticated. Run: gh auth login"
    exit 1
fi

# Get repository info for URLs
REPO_INFO=$(gh repo view --json owner,name)
REPO_OWNER=$(echo "$REPO_INFO" | jq -r '.owner.login')
REPO_NAME=$(echo "$REPO_INFO" | jq -r '.name')
REPO_URL="https://github.com/$REPO_OWNER/$REPO_NAME"

echo "Repository: $REPO_URL"

# Fetch all milestones once
echo "Fetching milestones..."
gh api repos/:owner/:repo/milestones > /tmp/milestones.json

if [ ! -s /tmp/milestones.json ] || [ "$(jq length /tmp/milestones.json)" -eq 0 ]; then
    echo "❌ No milestones found in repository"
    exit 1
fi

# Create header
cat > "$output" << 'EOF'
# w4os Development Roadmap

*This file is auto-generated from GitHub milestones and issues. Do not edit manually.*
*To update: run `dev/scripts/update-milestones.sh`*

EOF

# echo "## Overview" >> "$output"
# echo "" >> "$output"

# # Get milestone overview using the fetched data
# echo "Adding milestone overview..."
# jq -r '.[] | "- **\(.title)**: \(.closed_issues)/\(.open_issues + .closed_issues) issues (\(if (.open_issues + .closed_issues) > 0 then ((.closed_issues / (.open_issues + .closed_issues) * 100) | floor) else 0 end)% complete)"' /tmp/milestones.json >> "$output"

# echo "" >> "$output"

# Process each milestone using the fetched data
milestones=$(jq -r 'sort_by(.title) | .[] | .title' /tmp/milestones.json)

while IFS= read -r milestone; do
    if [ -z "$milestone" ]; then
        continue
    fi
    
    echo "Processing milestone: $milestone"
    
    {
        milestone_prefix=$(echo "$milestone" | cut -d' ' -f1)
        echo "## Milestone: $milestone ($milestone_prefix)"
        echo ""
        
        # Get milestone description from fetched data
        # milestone_desc=$(jq -r --arg title "$milestone" '.[] | select(.title == $title) | .description // ""' /tmp/milestones.json)
        # if [ -n "$milestone_desc" ]; then
        #     echo "$milestone_desc"
        #     echo ""
        # fi
        
        # Get milestone prefix (first part before space, e.g., "v3.0" from "v3.0 Foundation")
        
        # Get all issues for this milestone
        echo "  Fetching issues for: $milestone" >&2
        gh issue list --milestone "$milestone" --limit 100 --json number,title,state,body,labels > /tmp/milestone_issues.json
        
        if [ ! -s /tmp/milestone_issues.json ] || [ "$(jq length /tmp/milestone_issues.json)" -eq 0 ]; then
            echo "*No issues found for this milestone*"
            echo ""
            continue
        fi
        
        # Get main phase issues (titles starting with milestone prefix) and sort them
        main_phases=$(jq -r --arg prefix "$milestone_prefix" '[.[] | select(.title | startswith($prefix))] | sort_by(.title) | .[] | @base64' /tmp/milestone_issues.json)
        linked_issues=()
        
        if [ -n "$main_phases" ]; then
            while IFS= read -r phase_data; do
                if [ -n "$phase_data" ]; then
                    phase_json=$(echo "$phase_data" | base64 -d)
                    title=$(echo "$phase_json" | jq -r '.title')
                    number=$(echo "$phase_json" | jq -r '.number')
                    body=$(echo "$phase_json" | jq -r '.body // ""')
                    # body=$(echo "$phase_json") # DEBUG
                    
                    # Format main phase as h3 header
                    echo "### $title"
                    echo ""
                    
                    # Add phase description (everything before first task list or first issue reference)
                    if [ -n "$body" ]; then
                        # Extract description (everything before first "- [" or first "#" reference)
                        description=$(echo "$body" | sed '/^- \[/,$d' | sed '/^#[0-9]/,$d' | sed '/closes #/,$d' | sed '/fixes #/,$d' | sed 's/^[[:space:]]*//' | sed '/^$/d')
                        if [ -n "$description" ]; then
                            echo "$description"
                            echo ""
                        fi
                    fi
                    
                    # Find sub-issues linked to this main phase (referenced in body)
                    if [ -n "$body" ]; then
                        # Extract issue numbers referenced in the body (#123 format)
                        referenced_issues=$(echo "$body" | grep -oE '#[0-9]+' | sed 's/#//' | sort -u)
                        
                        for ref_number in $referenced_issues; do
                            # Check if this referenced issue exists in our milestone
                            sub_issue=$(jq -r --arg num "$ref_number" '.[] | select(.number == ($num | tonumber))' /tmp/milestone_issues.json)
                            if [ -n "$sub_issue" ]; then
                                sub_title=$(echo "$sub_issue" | jq -r '.title')
                                sub_state=$(echo "$sub_issue" | jq -r '.state')
                                
                                # Add to linked issues list
                                linked_issues+=("$ref_number")
                                
                                # Format sub-issue with checkbox and #number
                                if [ "$sub_state" = "closed" ]; then
                                    echo "- [x] #$ref_number $sub_title"
                                else
                                    echo "- [ ] #$ref_number $sub_title"
                                fi
                            fi
                        done
                    fi
                    
                    echo ""
                fi
            done <<< "$main_phases"
        fi
        
        # Get additional issues (not main phases and not linked as sub-issues)
        additional_issues=$(jq -r --arg prefix "$milestone_prefix" '[.[] | select(.title | startswith($prefix) | not)] | sort_by(.title) | .[] | @base64' /tmp/milestone_issues.json)
        additional_found=false
        
        if [ -n "$additional_issues" ]; then
            while IFS= read -r issue_data; do
                if [ -n "$issue_data" ]; then
                    issue_json=$(echo "$issue_data" | base64 -d)
                    title=$(echo "$issue_json" | jq -r '.title')
                    number=$(echo "$issue_json" | jq -r '.number')
                    state=$(echo "$issue_json" | jq -r '.state')
                    labels=$(echo "$issue_json" | jq -r '.labels[].name // empty' | tr '\n' ' ')
                    
                    # Skip if it's already linked as a sub-issue
                    is_linked=false
                    for linked_num in "${linked_issues[@]}"; do
                        if [ "$number" = "$linked_num" ]; then
                            is_linked=true
                            break
                        fi
                    done
                    
                    if [ "$is_linked" = false ]; then
                        if [ "$additional_found" = false ]; then
                            echo "### Additional Issues"
                            echo ""
                            additional_found=true
                        fi
                        
                        if [ "$state" = "closed" ]; then
                            echo "- [x] #$number $title$([ -n "$labels" ] && echo " ($labels)" || echo "")"
                        else
                            echo "- [ ] #$number $title$([ -n "$labels" ] && echo " ($labels)" || echo "")"
                        fi
                    fi
                fi
            done <<< "$additional_issues"
            
            if [ "$additional_found" = true ]; then
                echo ""
            fi
        fi
        
    } >> "$output"
    
done <<< "$milestones"

# Add footer
echo "---" >> "$output"
echo "" >> "$output"
echo "## Links" >> "$output"
echo "" >> "$output"
echo "- 📊 [GitHub Project]($REPO_URL/projects) - Live progress tracking" >> "$output"
echo "- 🐛 [GitHub Issues]($REPO_URL/issues) - Bug reports and feature requests" >> "$output"
echo "" >> "$output"
echo "*Last updated: $(date)*" >> "$output"
echo "*Generated from GitHub milestones and issues*" >> "$output"

# Cleanup
rm -f /tmp/milestones.json /tmp/milestone_issues.json

echo "✅ Roadmap generated: $output"
echo "📊 Contains all milestones and associated issues from GitHub"
