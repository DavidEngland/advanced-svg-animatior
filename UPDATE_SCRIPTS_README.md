# Repository Update Scripts Guide

This directory contains shell scripts to help you easily update your Advanced SVG Animator plugin repository with the latest changes from GitHub.

## 📁 Available Scripts

### 1. `update-repo.sh` - Full-Featured Update Script
**Best for: Comprehensive updates with safety checks**

**Features:**
- ✅ Checks for uncommitted changes and offers to stash them
- ✅ Shows current repository status and branch information  
- ✅ Fetches and pulls latest changes safely
- ✅ Handles merge conflicts and diverged branches
- ✅ Option to restore stashed changes after update
- ✅ Displays recent commit history
- ✅ Colored output for easy reading

### 2. `quick-update.sh` - Simple Update Script  
**Best for: Fast updates when you trust the process**

**Features:**
- 🚀 Quick git pull with minimal interaction
- 💾 Auto-stashes uncommitted changes
- 🔄 Auto-restores stashed changes
- 📋 Shows recent commit history
- ⚡ Fast execution with emoji indicators

## 🚀 How to Use

### Option 1: Full Update (Recommended for first-time users)
```bash
# Navigate to the plugin directory
cd /path/to/wp-content/plugins/advanced-svg-animator

# Run the comprehensive update script
./update-repo.sh
```

### Option 2: Quick Update (For experienced users)
```bash
# Navigate to the plugin directory  
cd /path/to/wp-content/plugins/advanced-svg-animator

# Run the quick update script
./quick-update.sh
```

### Option 3: Manual Git Commands (Traditional way)
```bash
# Check status
git status

# Stash changes if needed
git stash

# Pull latest changes
git pull origin main

# Restore stashed changes
git stash pop
```

## 🛡️ Safety Features

### What the scripts check for:
- ✅ Verifies you're in the correct plugin directory
- ✅ Confirms you're in a valid git repository
- ✅ Detects uncommitted changes and handles them safely
- ✅ Shows you what will be updated before proceeding
- ✅ Handles merge conflicts gracefully

### What happens to your local changes:
- **Uncommitted changes**: Automatically stashed and restored
- **Local commits**: Preserved and merged with remote changes
- **Conflicts**: Clearly reported with instructions

## 📋 Example Usage Scenarios

### Scenario 1: Clean Repository (No local changes)
```bash
$ ./quick-update.sh
🔄 Quick Update - Advanced SVG Animator Plugin
==============================================
📍 Current branch: main
📝 Last commit: fc9ead9 - Fix fatal error on plugin activation
⬇️  Pulling latest changes...
Already up to date.
✅ Repository updated successfully!
🎉 Update complete!
```

### Scenario 2: With Uncommitted Changes
```bash
$ ./update-repo.sh
============================================
  Advanced SVG Animator - Repository Update
============================================

[INFO] Current repository status:
  Branch: main
  Last commit: fc9ead9 - Fix fatal error on plugin activation
  Remote URL: https://github.com/DavidEngland/advanced-svg-animatior.git

[WARNING] You have uncommitted changes:
 M advanced-svg-animator.php
 ?? test-file.txt

Do you want to stash these changes before updating? (y/n): y
[INFO] Stashing uncommitted changes...
[SUCCESS] Changes stashed successfully
[INFO] Pulling 3 new commits from remote...
[SUCCESS] Repository updated successfully!

Do you want to restore your stashed changes? (y/n): y
[INFO] Restoring stashed changes...
[SUCCESS] Stashed changes restored successfully
```

## 🔧 Troubleshooting

### Permission Denied Error
```bash
# Make scripts executable
chmod +x update-repo.sh quick-update.sh
```

### Not in Plugin Directory Error
```bash
# Navigate to the correct directory first
cd /path/to/wp-content/plugins/advanced-svg-animator
```

### Merge Conflicts
If you encounter merge conflicts:
1. The script will notify you
2. Manually resolve conflicts in affected files
3. Run `git add .` then `git commit`
4. Run the update script again

### Script Won't Run
Make sure you're using the correct path:
```bash
# If in the plugin directory:
./update-repo.sh

# If running from elsewhere:
/full/path/to/advanced-svg-animator/update-repo.sh
```

## 📝 What Gets Updated

When you run these scripts, you'll get:
- ✅ Latest plugin code and features
- ✅ Bug fixes and security patches  
- ✅ Updated documentation
- ✅ New SVG icons and assets
- ✅ Block enhancements and new animation types

## 🎯 Best Practices

1. **Always backup your site** before major updates
2. **Test on staging first** before updating production
3. **Read the commit messages** to understand what changed
4. **Check for breaking changes** in the changelog
5. **Update during low-traffic periods** for production sites

## 📞 Support

If you encounter issues with the update scripts:
1. Check the error messages carefully
2. Ensure you have git installed and configured
3. Verify your GitHub access permissions
4. Contact the development team with error details

---

**Note**: These scripts are designed for the Advanced SVG Animator plugin repository. Make sure you're in the correct directory before running them.
