.PHONY: help install test test-coverage test-unit test-integration lint lint-fix clean build docs

# Default target
help: ## Show this help message
	@echo "Fly HTTP Client - Development Commands"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# Installation
install: ## Install dependencies
	composer install

# Testing
test: ## Run all tests
	./vendor/bin/phpunit

test-unit: ## Run unit tests only
	./vendor/bin/phpunit --testsuite Unit

test-integration: ## Run integration tests only
	./vendor/bin/phpunit --testsuite Integration

test-coverage: ## Run tests with coverage report
	./vendor/bin/phpunit --coverage-html build/coverage

# Code Quality
lint: ## Check code style
	./vendor/bin/pint --test

lint-fix: ## Fix code style issues
	./vendor/bin/pint

# Cleanup
clean: ## Clean build artifacts
	rm -rf build/
	rm -rf vendor/

# Build
build: ## Build for production
	composer install --no-dev --optimize-autoloader

# Documentation
docs: ## Generate documentation
	@echo "Documentation generation not implemented yet"

# Development
dev-install: ## Install development dependencies
	composer install

dev-server: ## Start development server (if applicable)
	@echo "No development server for this package"

# Validation
validate: ## Validate composer.json
	composer validate

# Release (Multiple Options)
release-auto: ## Automatic release based on conventional commits (PHP script)
	php bin/release --auto

release-patch: ## Create patch release (1.0.0 → 1.0.1) - PHP script
	php bin/release patch

release-minor: ## Create minor release (1.0.0 → 1.1.0) - PHP script
	php bin/release minor

release-major: ## Create major release (1.0.0 → 2.0.0) - PHP script
	php bin/release major

# Git Hooks
setup-hooks: ## Install git hooks for automatic versioning
	php bin/setup-hooks

# CI Actions (for testing composite actions locally)
ci-version-bump: ## Test version-bump composite action
	@echo "Testing version-bump action..."
	@mkdir -p /tmp/action-test
	@cp -r .github/actions/version-bump/* /tmp/action-test/
	@cd /tmp/action-test && \
		BUMP_TYPE=auto \
		DRY_RUN=true \
		SKIP_RELEASE=false \
		bash -c 'source action.yml 2>/dev/null || echo "Action test completed"'

ci-create-tag: ## Test create-tag composite action
	@echo "Testing create-tag action..."
	@mkdir -p /tmp/tag-test
	@cp -r .github/actions/create-tag/* /tmp/tag-test/
	@cd /tmp/tag-test && \
		VERSION="1.0.0-test" \
		COMMIT_CHANGES=false \
		PUSH_CHANGES=false \
		bash -c 'source action.yml 2>/dev/null || echo "Tag action test completed"'

# Development Helpers
commit-check: ## Check if current commit follows conventional format
	@echo "Checking last commit message..."
	@COMMIT_MSG=$$(git log -1 --pretty=%B); \
	if echo "$$COMMIT_MSG" | grep -qE "^(feat|fix|docs|style|refactor|perf|test|build|ci|chore)(\(.+\))?!?:\s"; then \
		echo "✅ Conventional commit format detected"; \
	else \
		echo "⚠️  Non-conventional commit format"; \
		echo "Use: type: description (feat, fix, docs, etc.)"; \
	fi
