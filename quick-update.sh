#!/bin/bash

# Quick Repository Update Script
# Simple script for fast git pull updates

echo "🔄 Quick Update - Advanced SVG Animator Plugin"
echo "=============================================="

# Check if we're in the right directory
if [ ! -f "advanced-svg-animator.php" ]; then
    echo "❌ Error: Not in plugin directory!"
    exit 1
fi

# Show current status
echo "📍 Current branch: $(git branch --show-current)"
echo "📝 Last commit: $(git log -1 --pretty=format:'%h - %s')"
echo

# Stash any uncommitted changes
if [ -n "$(git status --porcelain)" ]; then
    echo "💾 Stashing uncommitted changes..."
    git stash push -m "Quick update stash $(date '+%Y-%m-%d %H:%M:%S')"
    STASHED=true
else
    STASHED=false
fi

# Pull latest changes
echo "⬇️  Pulling latest changes..."
if git pull origin main; then
    echo "✅ Repository updated successfully!"
else
    echo "❌ Update failed!"
    exit 1
fi

# Restore stashed changes if any
if [ "$STASHED" = true ]; then
    echo "🔄 Restoring stashed changes..."
    git stash pop
fi

echo "🎉 Update complete!"
echo
echo "Recent changes:"
git log --oneline -5
