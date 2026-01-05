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

# Release
release-patch: ## Create patch release
	@echo "Release process not automated yet"

release-minor: ## Create minor release
	@echo "Release process not automated yet"

release-major: ## Create major release
	@echo "Release process not automated yet"
