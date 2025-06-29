#!/bin/bash

# Advanced SVG Animator - Repository Update Script
# This script helps you easily update your local repository with the latest changes

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if we're in a git repository
check_git_repo() {
    if ! git rev-parse --git-dir > /dev/null 2>&1; then
        print_error "This directory is not a git repository!"
        exit 1
    fi
}

# Function to check for uncommitted changes
check_uncommitted_changes() {
    if [ -n "$(git status --porcelain)" ]; then
        print_warning "You have uncommitted changes:"
        git status --short
        echo
        read -p "Do you want to stash these changes before updating? (y/n): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            print_status "Stashing uncommitted changes..."
            git stash push -m "Auto-stash before update $(date '+%Y-%m-%d %H:%M:%S')"
            print_success "Changes stashed successfully"
            STASHED=true
        else
            print_warning "Proceeding with uncommitted changes..."
            STASHED=false
        fi
    else
        STASHED=false
    fi
}

# Function to show current branch and status
show_current_status() {
    print_status "Current repository status:"
    echo "  Branch: $(git branch --show-current)"
    echo "  Last commit: $(git log -1 --pretty=format:'%h - %s (%cr)')"
    echo "  Remote URL: $(git remote get-url origin)"
    echo
}

# Function to update repository
update_repository() {
    print_status "Fetching latest changes from remote..."
    git fetch origin
    
    local current_branch=$(git branch --show-current)
    local remote_branch="origin/$current_branch"
    
    # Check if remote branch exists
    if ! git show-ref --verify --quiet refs/remotes/$remote_branch; then
        print_error "Remote branch $remote_branch does not exist!"
        exit 1
    fi
    
    # Check if we're behind the remote
    local behind=$(git rev-list --count HEAD..$remote_branch)
    local ahead=$(git rev-list --count $remote_branch..HEAD)
    
    if [ "$behind" -eq 0 ] && [ "$ahead" -eq 0 ]; then
        print_success "Repository is already up to date!"
        return 0
    elif [ "$ahead" -gt 0 ] && [ "$behind" -gt 0 ]; then
        print_warning "Branch has diverged from remote (ahead: $ahead, behind: $behind)"
        echo "You may need to resolve conflicts manually"
    elif [ "$ahead" -gt 0 ]; then
        print_warning "Local branch is ahead by $ahead commits"
        echo "Consider pushing your changes first"
    fi
    
    if [ "$behind" -gt 0 ]; then
        print_status "Pulling $behind new commits from remote..."
        if git pull origin "$current_branch"; then
            print_success "Repository updated successfully!"
        else
            print_error "Failed to pull changes. You may need to resolve conflicts."
            exit 1
        fi
    fi
}

# Function to restore stashed changes
restore_stash() {
    if [ "$STASHED" = true ]; then
        echo
        read -p "Do you want to restore your stashed changes? (y/n): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            print_status "Restoring stashed changes..."
            if git stash pop; then
                print_success "Stashed changes restored successfully"
            else
                print_error "Failed to restore stashed changes. Check 'git stash list'"
            fi
        else
            print_warning "Your changes remain stashed. Use 'git stash pop' to restore them later"
        fi
    fi
}

# Function to show what's new
show_whats_new() {
    echo
    print_status "Recent changes in the repository:"
    git log --oneline --graph --decorate -10
    echo
}

# Main script execution
main() {
    echo "============================================"
    echo "  Advanced SVG Animator - Repository Update"
    echo "============================================"
    echo
    
    # Check if we're in the right directory
    if [ ! -f "advanced-svg-animator.php" ]; then
        print_error "This doesn't appear to be the Advanced SVG Animator plugin directory!"
        print_status "Please run this script from the plugin root directory"
        exit 1
    fi
    
    # Perform checks and updates
    check_git_repo
    show_current_status
    check_uncommitted_changes
    update_repository
    restore_stash
    show_whats_new
    
    print_success "Repository update completed!"
    echo
    print_status "Next steps:"
    echo "  - Test the updated plugin on your site"
    echo "  - Check for any new features or changes"
    echo "  - Report any issues to the development team"
    echo
}

# Run the main function
main "$@"
