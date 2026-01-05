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

# Release (CI-Independent)
release-auto: ## Automatic release based on conventional commits
	php bin/release --auto

release-patch: ## Create patch release (1.0.0 → 1.0.1)
	php bin/release patch

release-minor: ## Create minor release (1.0.0 → 1.1.0)
	php bin/release minor

release-major: ## Create major release (1.0.0 → 2.0.0)
	php bin/release major

# Git Hooks
setup-hooks: ## Install git hooks for automatic versioning
	php bin/setup-hooks

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
