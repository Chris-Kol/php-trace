#!/bin/bash
# Development Environment Setup Script for PHP-Trace
# This script sets up everything needed for contributing to PHP-Trace

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Helper functions
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}� $1${NC}"
}

print_step() {
    echo -e "${PURPLE}�🚀 $1${NC}"
}

print_step "Setting up PHP-Trace development environment..."
echo ""

# Check requirements
print_info "Checking requirements..."

# Check PHP version
if ! command -v php &> /dev/null; then
    print_error "PHP is not installed"
    echo "   Install PHP 8.0+ from: https://www.php.net/downloads"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)
if ! php -r "exit(version_compare(PHP_VERSION, '8.0', '>=') ? 0 : 1);"; then
    print_error "PHP 8.0+ required, found: $PHP_VERSION"
    echo "   Please upgrade to PHP 8.0 or higher"
    exit 1
fi

print_success "PHP $PHP_VERSION found"

# Check Xdebug
if ! php -m | grep -q xdebug; then
    print_warning "Xdebug not found - tracing functionality will be limited"
    echo "   Install with: pecl install xdebug"
    echo "   Or on macOS: brew install php-xdebug"
    echo "   Or on Ubuntu: sudo apt-get install php-xdebug"
    XDEBUG_AVAILABLE=false
else
    XDEBUG_VERSION=$(php -r "echo phpversion('xdebug');")
    print_success "Xdebug $XDEBUG_VERSION found"
    XDEBUG_AVAILABLE=true
fi

# Check Composer
if ! command -v composer &> /dev/null; then
    print_error "Composer is not installed"
    echo "   Install from: https://getcomposer.org/"
    exit 1
fi

COMPOSER_VERSION=$(composer --version --no-ansi | cut -d' ' -f3)
print_success "Composer $COMPOSER_VERSION found"

# Check Node.js (for pre-commit hooks)
if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    print_success "Node.js $NODE_VERSION found (for pre-commit hooks)"
    NODE_AVAILABLE=true
else
    print_warning "Node.js not found - pre-commit hooks will be skipped"
    echo "   Install from: https://nodejs.org/ (recommended for better developer experience)"
    NODE_AVAILABLE=false
fi

# Check Make
if command -v make &> /dev/null; then
    print_success "Make found"
    MAKE_AVAILABLE=true
else
    print_warning "Make not found - convenience commands will be limited"
    echo "   On macOS: xcode-select --install"
    echo "   On Ubuntu: sudo apt-get install build-essential"
    MAKE_AVAILABLE=false
fi

# Check Git
if command -v git &> /dev/null; then
    print_success "Git found"
    GIT_AVAILABLE=true
else
    print_warning "Git not found - version control features limited"
    GIT_AVAILABLE=false
fi

echo ""

# Install PHP dependencies
print_step "Installing PHP dependencies..."
if composer install --no-progress --no-interaction; then
    print_success "PHP dependencies installed"
else
    print_error "Failed to install PHP dependencies"
    exit 1
fi

# Install Node.js dependencies (if Node.js available)
if [ "$NODE_AVAILABLE" = true ]; then
    print_step "Installing Node.js dependencies for pre-commit hooks..."
    if npm install --silent; then
        print_success "Pre-commit hooks setup complete"

        # Initialize Husky hooks
        if npx husky install; then
            print_success "Husky hooks initialized"
        else
            print_warning "Failed to initialize Husky hooks"
        fi
    else
        print_warning "Failed to install Node.js dependencies - pre-commit hooks disabled"
        NODE_AVAILABLE=false
    fi
else
    print_info "Skipping pre-commit hooks setup (Node.js not available)"
fi

echo ""

# Run initial validation
print_step "Running initial validation..."

echo "  → Validating composer.json..."
if composer validate --strict --no-interaction; then
    print_success "Composer validation passed"
else
    print_error "Composer validation failed"
    exit 1
fi

echo "  → Running tests..."
if composer test; then
    print_success "Tests passed"
else
    print_error "Tests failed - please fix before continuing"
    exit 1
fi

echo "  → Checking code style..."
if composer cs-check; then
    print_success "Code style check passed"
else
    print_info "Fixing code style issues..."
    if composer cs-fix; then
        print_success "Code style issues fixed"
    else
        print_error "Failed to fix code style issues"
        exit 1
    fi
fi

echo "  → Running static analysis..."
if composer phpstan; then
    print_success "Static analysis passed"
else
    print_error "Static analysis failed - please fix errors before continuing"
    exit 1
fi

print_success "Initial validation passed"
echo ""

# Create example traces directory
if [ ! -d "traces" ]; then
    mkdir -p traces
    echo "✅ Created traces directory"
fi

# Test tracing functionality
if [ "$XDEBUG_AVAILABLE" = true ]; then
    print_step "Testing tracing functionality..."
    if TRACE=1 php examples/test_without_xdebug.php > /dev/null 2>&1; then
        print_success "Tracing test passed"
    else
        print_warning "Tracing test had issues - check Xdebug configuration"
    fi
else
    print_info "Skipping tracing test (Xdebug not available)"
fi

# Set up Git hooks if Git is available
if [ "$GIT_AVAILABLE" = true ] && [ "$NODE_AVAILABLE" = true ]; then
    if [ -d ".git" ]; then
        print_step "Configuring Git hooks..."
        # This will be handled by Husky during npm install
        print_success "Git hooks configured"
    else
        print_info "Not in a Git repository - hooks will be set up when you initialize Git"
    fi
fi

echo ""
print_success "Setup complete! 🎉"
echo ""

# Display summary
echo "📊 Setup Summary:"
echo "   PHP: $(php -r 'echo PHP_VERSION;')"
echo "   Composer: $COMPOSER_VERSION"
if [ "$XDEBUG_AVAILABLE" = true ]; then
    echo "   Xdebug: $XDEBUG_VERSION ✅"
else
    echo "   Xdebug: Not available ⚠️"
fi
if [ "$NODE_AVAILABLE" = true ]; then
    echo "   Node.js: $NODE_VERSION ✅"
    echo "   Pre-commit hooks: Active ✅"
else
    echo "   Node.js: Not available ⚠️"
    echo "   Pre-commit hooks: Disabled ⚠️"
fi
echo ""

print_info "Available commands:"
echo ""

if [ "$MAKE_AVAILABLE" = true ]; then
    echo "🔧 Makefile commands:"
    echo "   make help          # Show all available commands"
    echo "   make test          # Run tests"
    echo "   make ci-local      # Run full CI suite locally"
    echo "   make trace-example # Test tracing with example"
    echo ""
fi

echo "� Composer commands:"
echo "   composer test           # Run tests"
echo "   composer test-coverage  # Run tests with coverage"
echo "   composer phpstan        # Static analysis"
echo "   composer cs-check       # Check code style"
echo "   composer cs-fix         # Fix code style"
echo ""

if [ "$NODE_AVAILABLE" = true ]; then
    echo "📦 NPM commands:"
    echo "   npm run validate       # Run all validation checks"
    echo "   npm run validate:php   # Run PHP-specific checks"
    echo ""
fi

echo "🏢 Workplace deployment workflow:"
echo "   1. Run 'composer test && composer phpstan && composer cs-check'"
echo "   2. Commit your changes: 'git commit -m \"feat: your changes\"'"
if [ "$NODE_AVAILABLE" = true ]; then
    echo "   3. Pre-commit hooks will automatically validate your code"
fi
echo "   4. Push to GitHub: 'git push'"
echo "   5. GitHub Actions will run full CI pipeline"
echo "   6. Create release: 'git tag v1.0.0 && git push --tags'"
echo "   7. Share the GitHub release with your colleagues"
echo ""

echo "📖 Documentation:"
echo "   - README.md                    # User documentation"
echo "   - CODING_STANDARDS.md          # Development guidelines"
echo "   - documentation/planning/      # Planning and architecture docs"
echo "   - .github/workflows/          # CI/CD pipeline configuration"
echo ""

if [ "$NODE_AVAILABLE" = true ]; then
    print_success "Pre-commit hooks are active - your commits will be automatically checked for quality"
else
    print_info "💡 Install Node.js to enable pre-commit hooks for better developer experience"
fi

if [ "$XDEBUG_AVAILABLE" = false ]; then
    print_info "💡 Install Xdebug to enable full tracing functionality testing"
fi

echo ""
print_step "Next steps:"
echo "   1. Read CODING_STANDARDS.md for development guidelines"
echo "   2. Try running 'composer test' to ensure everything works"
echo "   3. Create a test trace with one of the examples/"
if [ "$MAKE_AVAILABLE" = true ]; then
    echo "   4. Run 'make ci-local' to test the full CI pipeline locally"
fi
echo ""
print_success "Happy coding! The CI/CD pipeline will ensure quality when you push to GitHub. 🚀"