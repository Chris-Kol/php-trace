# Refactor PHP-Trace to Professional Package

## Goal

Transform the PHP-Trace MVP into a production-ready, professionally structured package following SOLID principles, with comprehensive testing, code quality tools, and CI/CD integration.

### Problem Statement

The current MVP works well but has several areas for improvement:
1. All logic is in a single bootstrap file - no separation of concerns
2. No automated tests - changes could break functionality
3. No code quality tools - potential for bugs and inconsistent style
4. No dependency injection - hard to test and extend
5. Configuration scattered across environment variables
6. No CI/CD pipeline
7. Missing standard package files (LICENSE, CHANGELOG, etc.)

### Solution

Refactor into a well-architected package with:
- Clean separation of concerns following SOLID principles
- Comprehensive test coverage (unit + integration)
- Code quality enforcement (PHP-CS-Fixer, PHPStan)
- Dependency injection for flexibility
- Centralized configuration
- CI/CD automation
- Professional package structure

## References

### Process Documentation
- `documentation/WRITE_PLANNING_DOC.md` - Planning document structure
- `documentation/WRITE_EVERGREEN_DOC.md` - Evergreen documentation guidelines

### Current Implementation
- `src/bootstrap.php` - Single file containing all logic (needs refactoring)
- `src/TraceParser.php` - Xdebug trace parser
- `src/JsonFormatter.php` - JSON output formatter
- `src/MarkdownFormatter.php` - Markdown output formatter

### Related Planning Docs
- `documentation/planning/completed/251031a_php_trace_llm_debugging_tool.md` - Original MVP implementation

## Principles, Key Decisions

### Architecture Principles
1. **SOLID Compliance**: Each class has a single responsibility
2. **Dependency Injection**: All dependencies injected, not created
3. **Interface-Based Design**: Program to interfaces, not implementations
4. **Testability**: Every component can be unit tested in isolation
5. **Extensibility**: Easy to add new formatters, detectors, tracers

### Technology Stack
- **Testing**: PHPUnit 9.x (compatible with PHP 7.4+)
- **Code Style**: PHP-CS-Fixer with PSR-12 standard
- **Static Analysis**: PHPStan level 8
- **CI/CD**: GitHub Actions
- **Version Control**: Semantic Versioning (semver)

### Design Decisions
1. **Keep backward compatibility**: Existing bootstrap.php continues to work
2. **Gradual migration**: New architecture coexists with old
3. **No breaking changes**: Users don't need to modify their setup
4. **Use proven packages**: Leverage existing solutions instead of reinventing the wheel

### Recommended Packages to Use

**Core Dependencies:**
- `psr/log` (PSR-3 Logger Interface) - Standard logging interface
- `symfony/finder` - Better file searching than manual implementations
- `league/container` or `php-di/php-di` - Dependency injection container

**Development Dependencies:**
- `phpunit/phpunit` ^9.5 - Testing framework
- `friendsofphp/php-cs-fixer` ^3.0 - Code style enforcement
- `phpstan/phpstan` ^1.0 - Static analysis
- `phpstan/extension-installer` - Auto-load PHPStan extensions
- `mockery/mockery` ^1.5 - Better mocking than PHPUnit's built-in
- `fakerphp/faker` ^1.20 - Generate test data
- `symfony/var-dumper` - Better debugging output
- `vlucas/phpdotenv` ^5.5 - Robust .env file parsing (optional, only if we want it)

**Optional Enhancements:**
- `monolog/monolog` - If we want file/stream logging
- `twig/twig` - If we build HTML formatter with templates
- `league/flysystem` - If we want to support S3/cloud storage output

**Why these choices:**
- **PSR-3 Logger**: Industry standard, users can inject their own logger
- **Symfony Finder**: Battle-tested, handles edge cases we'd miss
- **Mockery**: More expressive than PHPUnit mocks, better DX
- **PHP-DI**: Autowiring, easy to use, popular choice

## Technical Architecture

### New Directory Structure

```
php-trace/
├── src/
│   ├── Config/
│   │   └── TraceConfig.php           # Configuration object
│   ├── Detector/
│   │   ├── DetectorInterface.php     # Interface for trace enablement detection
│   │   ├── CompositeDetector.php     # Chains multiple detectors
│   │   ├── EnvironmentDetector.php   # Checks TRACE env var
│   │   ├── DotEnvDetector.php        # Checks .env file
│   │   ├── QueryDetector.php         # Checks ?TRACE=1
│   │   └── CookieDetector.php        # Checks TRACE cookie
│   ├── Tracer/
│   │   ├── TracerInterface.php       # Interface for trace collection
│   │   └── XdebugTracer.php          # Xdebug implementation
│   ├── Parser/
│   │   ├── ParserInterface.php       # Interface for trace parsing
│   │   └── XdebugParser.php          # Xdebug format parser (refactored TraceParser)
│   ├── Formatter/
│   │   ├── FormatterInterface.php    # Interface for output formatting
│   │   ├── JsonFormatter.php         # JSON formatter (refactored)
│   │   ├── MarkdownFormatter.php     # Markdown formatter (refactored)
│   │   └── HtmlFormatter.php         # NEW: HTML formatter for web UI
│   ├── Writer/
│   │   ├── WriterInterface.php       # Interface for output writing
│   │   └── FileWriter.php            # Writes to filesystem
│   ├── TraceManager.php              # Orchestrates the tracing process
│   ├── bootstrap.php                 # Backward-compatible bootstrap
│   └── bootstrap-v2.php              # New DI-based bootstrap
├── tests/
│   ├── Unit/
│   │   ├── Config/
│   │   ├── Detector/
│   │   ├── Parser/
│   │   ├── Formatter/
│   │   └── TraceManagerTest.php
│   ├── Integration/
│   │   ├── EndToEndTest.php
│   │   └── XdebugIntegrationTest.php
│   └── Fixtures/
│       └── sample-trace.xt           # Sample Xdebug trace for testing
├── .php-cs-fixer.php                 # Code style config
├── phpstan.neon                      # Static analysis config
├── phpunit.xml                       # Test configuration
├── .github/
│   └── workflows/
│       └── ci.yml                    # CI/CD pipeline
├── CHANGELOG.md                      # Version history
├── LICENSE                           # MIT license
└── CONTRIBUTING.md                   # Contribution guidelines
```

### Component Responsibilities

#### TraceConfig
```php
class TraceConfig
{
    public function __construct(
        private bool $enabled,
        private string $outputDir,
        private int $slowThreshold = 100,
        private array $excludePatterns = ['/vendor/', '/composer/']
    ) {}
}
```

#### DetectorInterface
```php
interface DetectorInterface
{
    public function isEnabled(): bool;
}
```

#### TracerInterface
```php
interface TracerInterface
{
    public function start(string $outputFile): void;
    public function stop(): string; // Returns trace file path
}
```

#### ParserInterface
```php
interface ParserInterface
{
    public function parse(string $traceFile): array;
}
```

#### FormatterInterface
```php
interface FormatterInterface
{
    public function format(array $traceData): string;
    public function getExtension(): string; // 'json', 'md', 'html'
}
```

#### TraceManager
```php
class TraceManager
{
    public function __construct(
        private TraceConfig $config,
        private DetectorInterface $detector,
        private TracerInterface $tracer,
        private ParserInterface $parser,
        private array $formatters, // FormatterInterface[]
        private WriterInterface $writer
    ) {}

    public function handleRequest(): void
    {
        if (!$this->detector->isEnabled()) {
            return;
        }

        $this->tracer->start($outputFile);

        register_shutdown_function(function() {
            $traceFile = $this->tracer->stop();
            $data = $this->parser->parse($traceFile);

            foreach ($this->formatters as $formatter) {
                $output = $formatter->format($data);
                $this->writer->write($output, $formatter->getExtension());
            }
        });
    }
}
```

## Actions

### Phase 1: Setup Testing Infrastructure
- [ ] Add PHPUnit to composer.json dev dependencies
  - `composer require --dev phpunit/phpunit:^9.5`
- [ ] Create phpunit.xml configuration
  - Set test directories
  - Configure code coverage
  - Set PHP version compatibility
- [ ] Create tests directory structure
  - `tests/Unit/`
  - `tests/Integration/`
  - `tests/Fixtures/`
- [ ] Create sample Xdebug trace fixture for testing
  - Copy real trace output
  - Use for parser tests
- [ ] Add test command to composer.json scripts
  - `"test": "phpunit"`
  - `"test-coverage": "phpunit --coverage-html coverage"`
- [ ] Verify tests run (empty test suite is fine)
  - `composer test`

### Phase 2: Add Code Quality Tools
- [ ] Add PHP-CS-Fixer
  - `composer require --dev friendsofphp/php-cs-fixer:^3.0`
  - Create `.php-cs-fixer.php` config (PSR-12)
  - Add `"cs-fix": "php-cs-fixer fix"` script
  - Run on existing code
- [ ] Add PHPStan
  - `composer require --dev phpstan/phpstan:^1.0`
  - Create `phpstan.neon` config (level 5 initially)
  - Add `"phpstan": "phpstan analyse"` script
  - Fix any immediate issues
- [ ] Update .gitignore
  - Add `/coverage/`
  - Add `.phpunit.result.cache`
  - Add `.php-cs-fixer.cache`
- [ ] Document code quality commands in README

### Phase 3: Create Interfaces and Config
- [ ] Create `src/Config/TraceConfig.php`
  - All configuration in one place
  - Factory method: `TraceConfig::fromEnvironment()`
  - Immutable value object
- [ ] Create `src/Detector/DetectorInterface.php`
  - Simple `isEnabled(): bool` method
- [ ] Create `src/Tracer/TracerInterface.php`
  - `start(string $outputFile): void`
  - `stop(): string` (returns trace file path)
- [ ] Create `src/Parser/ParserInterface.php`
  - `parse(string $traceFile): array`
- [ ] Create `src/Formatter/FormatterInterface.php`
  - `format(array $traceData): string`
  - `getExtension(): string`
- [ ] Create `src/Writer/WriterInterface.php`
  - `write(string $content, string $filename): void`
- [ ] Write unit tests for TraceConfig
  - Test fromEnvironment() factory
  - Test getters
  - Test defaults

### Phase 4: Implement Detectors
- [ ] Create `src/Detector/EnvironmentDetector.php`
  - Checks `getenv('TRACE') === '1'`
  - Unit test with mock environment
- [ ] Create `src/Detector/DotEnvDetector.php`
  - Reads .env file
  - Unit test with fixture .env
- [ ] Create `src/Detector/QueryDetector.php`
  - Checks `$_GET['TRACE']`
  - Unit test with mock $_GET
- [ ] Create `src/Detector/CookieDetector.php`
  - Checks `$_COOKIE['TRACE']`
  - Unit test with mock $_COOKIE
- [ ] Create `src/Detector/CompositeDetector.php`
  - Chains multiple detectors
  - Unit test with mock detectors
  - Test priority order

### Phase 5: Refactor Tracer
- [ ] Create `src/Tracer/XdebugTracer.php`
  - Extract Xdebug logic from bootstrap
  - Implement TracerInterface
  - Handle gzip decompression
  - Unit tests (mock xdebug functions)
- [ ] Update bootstrap.php to use XdebugTracer
  - Backward compatibility maintained

### Phase 6: Refactor Parser
- [ ] Rename `src/TraceParser.php` to `src/Parser/XdebugParser.php`
  - Implement ParserInterface
  - Keep existing functionality
  - Add project root detection logic
- [ ] Write comprehensive parser unit tests
  - Test with fixture trace files
  - Test vendor filtering
  - Test timing calculations
  - Test hierarchical structure
- [ ] Update existing code to use new Parser

### Phase 7: Refactor Formatters
- [ ] Move `src/JsonFormatter.php` to `src/Formatter/`
  - Implement FormatterInterface
  - Add `getExtension(): string` method
  - Keep existing functionality
- [ ] Move `src/MarkdownFormatter.php` to `src/Formatter/`
  - Implement FormatterInterface
  - Add `getExtension(): string` method
  - Keep existing functionality
- [ ] Write formatter unit tests
  - Test JSON structure
  - Test Markdown hierarchy
  - Test slow function detection
  - Test relative paths
- [ ] Create `src/Formatter/HtmlFormatter.php` (bonus)
  - Generate interactive HTML view
  - Collapsible call tree
  - Syntax highlighting

### Phase 8: Implement Writer
- [ ] Create `src/Writer/FileWriter.php`
  - Implements WriterInterface
  - Handles file naming with timestamps
  - Handles request info in filename
  - Unit test with temp directory

### Phase 9: Create TraceManager
- [ ] Create `src/TraceManager.php`
  - Constructor with DI
  - `handleRequest()` method
  - Orchestrates detector, tracer, parser, formatters, writer
- [ ] Write TraceManager unit tests
  - Mock all dependencies
  - Test disabled tracing
  - Test enabled tracing flow
  - Test error handling
- [ ] Write integration test
  - End-to-end with real components
  - Generate actual trace
  - Verify output files

### Phase 10: Create New Bootstrap
- [ ] Create `src/bootstrap-v2.php`
  - Uses dependency injection
  - Creates TraceManager instance
  - Cleaner, more maintainable
- [ ] Update old `src/bootstrap.php`
  - Keep backward compatibility
  - Optionally delegate to v2 internally
- [ ] Add example in documentation
  - Show both bootstrap options
  - Migration guide

### Phase 11: Add Package Essentials
- [ ] Create LICENSE file
  - MIT license text
- [ ] Create CHANGELOG.md
  - Format: Keep a Changelog
  - Document v1.0.0 MVP
  - Document v2.0.0 refactoring
- [ ] Create CONTRIBUTING.md
  - How to contribute
  - Running tests
  - Code style
  - PR process
- [ ] Update composer.json
  - Add keywords
  - Add authors
  - Add support links
  - Semantic versioning

### Phase 12: Setup CI/CD
- [ ] Create `.github/workflows/ci.yml`
  - Run on pull requests and pushes
  - Test on PHP 7.4, 8.0, 8.1, 8.2, 8.3
  - Run PHPUnit tests
  - Run PHP-CS-Fixer check
  - Run PHPStan
  - Upload code coverage
- [ ] Add status badges to README
  - CI status
  - Code coverage
  - Latest version
- [ ] Test CI pipeline
  - Create test PR
  - Verify all checks pass

### Phase 13: Documentation Updates
- [ ] Update README.md
  - Add "Code Quality" section
  - Add contribution guidelines link
  - Add testing instructions for contributors
- [ ] Create ARCHITECTURE.md
  - Document component structure
  - Explain design decisions
  - Show class diagrams
- [ ] Update planning doc with completion status
- [ ] Move to `documentation/planning/completed/`

## Appendix

### PHP-CS-Fixer Configuration

```php
<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => true,
        'trailing_comma_in_multiline' => true,
        'phpdoc_scalar' => true,
        'unary_operator_spaces' => true,
        'binary_operator_spaces' => true,
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
        ],
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name' => true,
    ])
    ->setFinder($finder);
```

### PHPStan Configuration

```neon
parameters:
    level: 5
    paths:
        - src
    excludePaths:
        - src/bootstrap.php
    ignoreErrors:
        # Allow dynamic xdebug function checks
        - '#Function xdebug_\w+ not found#'
```

### PHPUnit Configuration

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">src</directory>
        </include>
        <exclude>
            <file>src/bootstrap.php</file>
        </exclude>
    </coverage>
</phpunit>
```

### Test Naming Conventions

- Unit tests: `{ClassName}Test.php`
- Integration tests: `{Feature}IntegrationTest.php`
- Test methods: `test_{method}__{scenario}()`

Example:
```php
class TraceConfigTest extends TestCase
{
    public function test_fromEnvironment__returns_config_with_defaults(): void
    {
        // ...
    }

    public function test_fromEnvironment__uses_custom_output_dir_when_set(): void
    {
        // ...
    }
}
```

### Backward Compatibility Strategy

The old bootstrap.php will remain functional. Users can migrate at their own pace:

**Current (v1.x):**
```ini
auto_prepend_file=vendor/php-trace/php-trace/src/bootstrap.php
```

**New (v2.x - optional):**
```ini
auto_prepend_file=vendor/php-trace/php-trace/src/bootstrap-v2.php
```

Both work identically from the user's perspective.
