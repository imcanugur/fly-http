#!/bin/bash

# Fly HTTP Client - Version Bump Script
# Usage: ./version-bump.sh [patch|minor|major]

set -e

if [ $# -ne 1 ]; then
    echo "Usage: $0 [patch|minor|major]"
    echo "Example: $0 patch"
    exit 1
fi

BUMP_TYPE=$1

# Validate bump type
case $BUMP_TYPE in
    patch|minor|major)
        ;;
    *)
        echo "Error: Invalid bump type. Must be patch, minor, or major."
        exit 1
        ;;
esac

# Get current version from composer.json
CURRENT_VERSION=$(grep '"version"' composer.json | sed 's/.*"version": "\([^"]*\)".*/\1/')

if [ -z "$CURRENT_VERSION" ]; then
    echo "Error: Could not find version in composer.json"
    exit 1
fi

echo "Current version: $CURRENT_VERSION"

# Parse version components
IFS='.' read -ra VERSION_PARTS <<< "$CURRENT_VERSION"
MAJOR=${VERSION_PARTS[0]}
MINOR=${VERSION_PARTS[1]}
PATCH=${VERSION_PARTS[2]}

# Bump version
case $BUMP_TYPE in
    patch)
        PATCH=$((PATCH + 1))
        ;;
    minor)
        MINOR=$((MINOR + 1))
        PATCH=0
        ;;
    major)
        MAJOR=$((MAJOR + 1))
        MINOR=0
        PATCH=0
        ;;
esac

NEW_VERSION="$MAJOR.$MINOR.$PATCH"
echo "New version: $NEW_VERSION"

# Update composer.json
sed -i.bak "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$NEW_VERSION\"/" composer.json
rm composer.json.bak

# Update CHANGELOG.md
DATE=$(date +%Y-%m-%d)
sed -i.bak "1a ## [$NEW_VERSION] - $DATE\n\n### Changed\n- Version bump to $NEW_VERSION\n" CHANGELOG.md
rm CHANGELOG.md.bak

echo "✓ Updated composer.json to version $NEW_VERSION"
echo "✓ Updated CHANGELOG.md with new version entry"

# Git operations (optional, commented out)
echo ""
echo "Next steps:"
echo "1. Review the changes: git diff"
echo "2. Commit changes: git add -A && git commit -m 'Bump version to $NEW_VERSION'"
echo "3. Create tag: git tag -a v$NEW_VERSION -m 'Release version $NEW_VERSION'"
echo "4. Push changes: git push origin main"
echo "5. Push tag: git push origin v$NEW_VERSION"
echo "6. Update Packagist: Visit https://packagist.org/packages/imcanugur/fly-http"
