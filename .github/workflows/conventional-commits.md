# Conventional Commits Guide

This project uses [Conventional Commits](https://conventionalcommits.org/) specification for automatic version management and changelog generation.

## Commit Message Format

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

### Types

- `feat`: A new feature
- `fix`: A bug fix
- `docs`: Documentation only changes
- `style`: Changes that do not affect the meaning of the code
- `refactor`: A code change that neither fixes a bug nor adds a feature
- `perf`: A code change that improves performance
- `test`: Adding missing tests or correcting existing tests
- `build`: Changes that affect the build system or external dependencies
- `ci`: Changes to our CI configuration files and scripts
- `chore`: Other changes that don't modify src or test files

### Breaking Changes

To indicate a breaking change, add `!` after the type/scope or add `BREAKING CHANGE:` in the footer:

```bash
feat!: remove deprecated API
fix!: change behavior of existing function

BREAKING CHANGE: remove deprecated API
```

## Version Bump Rules

| Commit Type | Version Bump |
|-------------|--------------|
| `feat!:` or `BREAKING CHANGE:` | Major (1.0.0 → 2.0.0) |
| `feat:` | Minor (1.0.0 → 1.1.0) |
| `fix:`, `perf:`, or others | Patch (1.0.0 → 1.0.1) |

## Examples

```bash
# Feature (minor bump)
git commit -m "feat: add circuit breaker middleware"

# Bug fix (patch bump)
git commit -m "fix: resolve memory leak in cache middleware"

# Breaking change (major bump)
git commit -m "feat!: remove deprecated HTTP methods

BREAKING CHANGE: remove support for HTTP/1.0"

# Documentation (no version bump)
git commit -m "docs: update installation guide"
```

## Automation

This repository automatically:
- Bumps version based on commit messages
- Updates CHANGELOG.md
- Creates Git tags
- Publishes GitHub releases
- Updates Packagist

Just commit with conventional format and push to `main` branch!
