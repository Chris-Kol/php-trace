# PHP-Trace Development Makefile
# Provides convenient shortcuts for common development tasks

.PHONY: help install test test-coverage phpstan cs-check cs-fix clean validate ci-local

# Default target
help: ## Show this help message
	@echo "PHP-Trace Development Commands:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'
	@echo ""
	@echo "Examples:"
	@echo "  make install     # Install all dependencies"
	@echo "  make test        # Run tests quickly"
	@echo "  make ci-local    # Run full CI suite locally"

install: ## Install Composer dependencies
	composer install

update: ## Update Composer dependencies  
	composer update

test: ## Run PHPUnit tests (fast, no coverage)
	composer test

test-coverage: ## Run tests with coverage report (slower)
	composer test-coverage
	@echo ""
	@echo "Coverage report generated in: coverage/index.html"

test-coverage-text: ## Run tests with text coverage report
	composer test-coverage-text

phpstan: ## Run PHPStan static analysis
	composer phpstan

cs-check: ## Check code style (PSR-12)
	composer cs-check

cs-fix: ## Fix code style issues automatically
	composer cs-fix

validate: ## Validate composer.json
	composer validate --strict

clean: ## Clean generated files
	rm -rf vendor/
	rm -rf coverage/
	rm -rf var/
	rm -rf .phpunit.result.cache

ci-local: ## Run full CI suite locally (same as GitHub Actions)
	@echo "🚀 Running local CI checks..."
	@echo ""
	@echo "1/5 Validating composer.json..."
	@make validate
	@echo ""
	@echo "2/5 Running PHPUnit tests..."
	@make test
	@echo ""
	@echo "3/5 Running PHPStan analysis..."
	@make phpstan
	@echo ""
	@echo "4/5 Checking code style..."
	@make cs-check
	@echo ""
	@echo "5/5 Running coverage check..."
	@make test-coverage-text
	@echo ""
	@echo "✅ All checks passed! Your code is ready for CI."

security: ## Run security audit
	composer audit

trace-example: ## Run example with tracing (requires Xdebug)
	@echo "Running example with tracing enabled..."
	TRACE=1 php -d xdebug.mode=trace examples/sample.php
	@echo ""
	@echo "Check /tmp/ for generated trace files:"
	@ls -la /tmp/php-trace-* 2>/dev/null || echo "No trace files generated (check Xdebug installation)"

serve-example: ## Start web server with tracing for examples
	@echo "Starting web server on http://localhost:8000"
	@echo "Visit http://localhost:8000/web.php?TRACE=1 to test web tracing"
	php -S localhost:8000 -t examples -d auto_prepend_file=src/bootstrap.php -d xdebug.mode=trace

# Release helpers
tag: ## Create a new version tag (use: make tag VERSION=v1.2.3)
ifndef VERSION
	$(error VERSION is required. Usage: make tag VERSION=v1.2.3)
endif
	@echo "Creating tag $(VERSION)..."
	git tag $(VERSION)
	git push origin $(VERSION)
	@echo "✅ Tag $(VERSION) created and pushed. GitHub Actions will create the release."

untag: ## Remove a version tag (use: make untag VERSION=v1.2.3)  
ifndef VERSION
	$(error VERSION is required. Usage: make untag VERSION=v1.2.3)
endif
	@echo "Removing tag $(VERSION)..."
	git tag -d $(VERSION)
	git push origin :refs/tags/$(VERSION)
	@echo "✅ Tag $(VERSION) removed."

release-dry-run: ## Test release process without actually releasing
	@echo "🧪 Testing release process..."
	@make ci-local
	@echo ""
	@echo "✅ Release dry-run completed successfully"
	@echo "📋 To create actual release: make tag VERSION=v1.0.0"