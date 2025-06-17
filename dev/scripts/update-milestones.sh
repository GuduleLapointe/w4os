#!/bin/bash

output=ROADMAP.md
project_url=https://github.com/users/GuduleLapointe/projects/2
max_issues=100 # 100 is the maximum allowed by GraphQL

# TODO: implement pagination using GraphQL cursors with multiple queries
# to handle more than 100 issues per repository.

# Repositories to query for issues
repos=(
    "GuduleLapointe/w4os"
    "GuduleLapointe/opensim-helpers" 
    "GuduleLapointe/opensim-engine"
)

# Create header
cat > "$output" << EOF
# w4os Development Roadmap

**Project Details**: $project_url

*Last updated: $(LC_ALL=C date)*

EOF

# Desired workflow:
#
# 1° Fetch the list of milestones from main repo (current one)
# 2° Fetch the list of issues belonging to the project
# 3° For each milestone, extract main phases
# 4° For each main phases, extract main phase issue (show title and body, which might or might not contain tasks lists)
# 5° For each main phase issue, extract and list childs (which might or might not be mentioned in body)
# The latter may require to use graph instead of json
#
# If the issue is from the main repository, the number is enough, #123 
# If the issue is from another repository, the username/repo must appear also (but avoid long urls for readability)

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

# Fetch all issues from GitHub Project (cleaner approach)
echo "Fetching issues from GitHub Project..."
gh project --owner GuduleLapointe item-list 2 --format json > /tmp/project_data.json



if [ ! -s /tmp/project_data.json ]; then
    echo "❌ Failed to fetch project data"
    exit 1
fi

# Build parent-child relationship map upfront using batch GraphQL
echo "Building parent-child relationship map..."

# Use batch GraphQL to get all issues with parent info from all repos at once
echo "Fetching parent-child relationships from all repositories..."
# batch_result=$(gh api graphql -H GraphQL-Features:sub_issues -H GraphQL-Features:issue_types -f query='
# query {
#   repo1: repository(owner: "GuduleLapointe", name: "w4os") {
batch_result=$(gh api graphql -H GraphQL-Features:sub_issues -H GraphQL-Features:issue_types -F max_issues="$max_issues" -f query='
query ($max_issues: Int!) {
  repo1: repository(owner: "GuduleLapointe", name: "w4os") {
    issues(first: $max_issues) {
      nodes {
        number
        title
        state
        parent {
          number
          repository {
            nameWithOwner
          }
        }
      }
    }
  }
  repo2: repository(owner: "GuduleLapointe", name: "opensim-helpers") {
    issues(first: $max_issues) {
      nodes {
        number
        title
        state
        parent {
          number
          repository {
            nameWithOwner
          }
        }
      }
    }
  }
  repo3: repository(owner: "GuduleLapointe", name: "opensim-engine") {
    issues(first: $max_issues) {
      nodes {
        number
        title
        state
        parent {
          number
          repository {
            nameWithOwner
          }
        }
      }
    }
  }
}' 2>/dev/null || echo "")

if [ -n "$batch_result" ]; then
    > /tmp/parent_child_map.txt
    
    # Process repo1 (w4os) issues
    echo "$batch_result" | jq -r '.data.repo1.issues.nodes[] | select(.parent.number != null) | "\(.parent.number)|\(.number)|\(.title)|\(.state)|GuduleLapointe/w4os|\(.parent.repository.nameWithOwner)"' >> /tmp/parent_child_map.txt
    
    # Process repo2 (opensim-helpers) issues  
    echo "$batch_result" | jq -r '.data.repo2.issues.nodes[] | select(.parent.number != null) | "\(.parent.number)|\(.number)|\(.title)|\(.state)|GuduleLapointe/opensim-helpers|\(.parent.repository.nameWithOwner)"' >> /tmp/parent_child_map.txt
    
    # Process repo3 (opensim-engine) issues
    echo "$batch_result" | jq -r '.data.repo3.issues.nodes[] | select(.parent.number != null) | "\(.parent.number)|\(.number)|\(.title)|\(.state)|GuduleLapointe/opensim-engine|\(.parent.repository.nameWithOwner)"' >> /tmp/parent_child_map.txt
    
    echo "Parent-child map built with $(wc -l < /tmp/parent_child_map.txt 2>/dev/null || echo 0) relationships"
else
    echo "⚠️  Failed to fetch batch GraphQL data, falling back to empty map" >&2
    > /tmp/parent_child_map.txt
fi

# Extract milestones from project data and create milestone file
jq '[.items[].milestone | select(. != null)] | group_by(.title) | map(.[0])' /tmp/project_data.json > /tmp/milestones.json

if [ ! -s /tmp/milestones.json ] || [ "$(jq length /tmp/milestones.json)" -eq 0 ]; then
    echo "❌ No milestones found in repository"
    exit 1
fi

# Process each milestone using the fetched data
milestones=$(jq -r 'sort_by(.title) | .[] | .title' /tmp/milestones.json)

while IFS= read -r milestone; do
    if [ -z "$milestone" ]; then
        continue
    fi
    
    echo "Processing milestone: $milestone"
    
    {
        # Get milestone info for issue counts from project data
        milestone_count=$(jq -r --arg title "$milestone" '[.items[] | select(.milestone.title == $title)] | length' /tmp/project_data.json)
        milestone_done=$(jq -r --arg title "$milestone" '[.items[] | select(.milestone.title == $title and .status == "Done")] | length' /tmp/project_data.json)
        
        echo "## $milestone ($milestone_done/$milestone_count issues)"
        echo ""
        
        # Get milestone prefix (first part before space, e.g., "v3.0" from "v3.0 Foundation")
        milestone_prefix=$(echo "$milestone" | cut -d' ' -f1)
        
        # Get all issues for this milestone from project data
        # echo "  Processing issues for: $milestone" >&2
        jq --arg milestone_title "$milestone" '[.items[] | select(.milestone.title == $milestone_title) | {number: .content.number, title: .content.title, state: (if .status == "Done" then "CLOSED" else "OPEN" end), body: .content.body, repository: .content.repository}]' /tmp/project_data.json > /tmp/milestone_issues.json
        
        if [ ! -s /tmp/milestone_issues.json ] || [ "$(jq length /tmp/milestone_issues.json)" -eq 0 ]; then
            echo "*No issues found for this milestone*"
            echo ""
            continue
        fi
        
        # Get main phase issues and sort them
        main_phases=$(jq -r --arg prefix "$milestone_prefix" '[.[] | select(.title | startswith($prefix))] | sort_by(.title) | .[] | @base64' /tmp/milestone_issues.json)
        linked_issues=()
        
        if [ -n "$main_phases" ]; then
            while IFS= read -r phase_data; do
                if [ -n "$phase_data" ]; then
                    phase_json=$(echo "$phase_data" | base64 -d)
                    title=$(echo "$phase_json" | jq -r '.title')
                    number=$(echo "$phase_json" | jq -r '.number')
                    state=$(echo "$phase_json" | jq -r '.state')
                    body=$(echo "$phase_json" | jq -r '.body')
                    
                    # Format main phase with Unicode checkbox and number at end
                    if [ "$state" = "CLOSED" ]; then
                        echo "### ✅ $title #$number"
                    else
                        echo "### ⬜ $title #$number"
                    fi
                    echo ""
                    
                    # Add phase description/body if it exists
                    if [ "$body" != "null" ] && [ -n "$body" ]; then
                        echo "$body"
                        echo ""
                    fi
                    
                    # Find child issues using simple lookup in parent-child map
                    # echo "    Looking for child issues of #$number..." >&2
                    
                    # Simple grep lookup for children of this parent
                    children=$(grep "^$number|" /tmp/parent_child_map.txt 2>/dev/null || echo "")
                    
                    if [ -n "$children" ]; then
                        while IFS='|' read -r parent_num child_num child_title child_state child_repo parent_repo; do
                            if [ -n "$child_num" ]; then
                                # echo "Found child: $child_title #$child_num in $child_repo" >&2
                                
                                # Add to linked issues list
                                linked_issues+=("$child_num")
                                
                                # Format repository reference
                                if [ "$child_repo" = "$REPO_OWNER/$REPO_NAME" ]; then
                                    child_ref="#$child_num"
                                else
                                    child_ref="$child_repo#$child_num"
                                fi
                                
                                # Format sub-issue with checkbox and repository reference - OUTPUT IMMEDIATELY
                                if [ "$child_state" = "CLOSED" ]; then
                                    echo "- [x] $child_title $child_ref"
                                else
                                    echo "- [ ] $child_title $child_ref"
                                fi
                            fi
                        done <<< "$children"
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
                    repository=$(echo "$issue_json" | jq -r '.repository')
                    
                    # Format repository reference properly
                    if [[ "$repository" == *"$REPO_OWNER/$REPO_NAME"* ]]; then
                        issue_ref="#$number"
                    else
                        # Extract owner/repo from repository URL
                        repo_short=$(echo "$repository" | sed -E 's|.*github\.com/([^/]+/[^/]+).*|\1|')
                        issue_ref="$repo_short#$number"
                    fi
                    
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
                        
                        if [ "$state" = "CLOSED" ]; then
                            echo "- [x] $title $issue_ref"
                        else
                            echo "- [ ] $title $issue_ref"
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

# Cleanup
# rm -f /tmp/milestones.json /tmp/milestone_issues.json /tmp/project_data.json /tmp/parent_child_map.txt

echo "✅ Roadmap generated: $output"
echo "📊 Contains all milestones and associated issues from GitHub"
