# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-11-02

### Added
- **Initial stable release** 🎉
- Xdebug-based PHP function tracing with zero code changes
- Multiple activation methods:
  - Environment variable (`TRACE=1`)
  - `.env` file support
  - Query parameter (`?TRACE=1`)
  - Cookie support for persistent browser tracing
- Dual output formats optimized for LLMs:
  - JSON format with hierarchical structure
  - Markdown format with call tree visualization
- Configuration system via `phptrace.php` file:
  - Configurable filtering patterns
  - Custom output directories
  - Format selection
- Automatic vendor/ directory filtering
- Smart project root detection
- Relative path output for portability
- Timing information with slow function highlighting (>100ms)
- Docker compatibility
- Composer package structure
- Comprehensive test suite:
  - 146 unit tests
  - 74.36% code coverage
  - PHPStan Level 8 static analysis
  - PSR-12 code style compliance
- CI/CD pipeline:
  - Multi-version PHP testing (8.0, 8.1, 8.2, 8.3)
  - Automated code quality checks
  - Security scanning (Composer audit, Trivy, Symfony security checker)
  - Coverage enforcement (70% minimum)
  - Codecov integration
- Complete documentation:
  - Installation guide
  - Usage examples for CLI and web
  - Docker integration instructions
  - Troubleshooting guide
  - Configuration reference

### Technical Details
- **PHP Version**: 8.0+ required
- **Dependencies**: Xdebug 3.x, PHP-DI 7.1
- **Architecture**: SOLID principles, dependency injection, interface-based design
- **Code Quality**: PSR-12 compliant, PHPStan Level 8, no dead code
- **Test Coverage**: 146 tests, 263 assertions, 74.36% line coverage

### Project Structure
```
php-trace/
├── src/               # Core library
│   ├── Config/       # Configuration management
│   ├── Detector/     # Trace activation detection
│   ├── Formatter/    # Output formatting (JSON, Markdown)
│   ├── Parser/       # Xdebug trace parsing
│   ├── Tracer/       # Xdebug integration
│   ├── Writer/       # File writing
│   └── TraceManager.php  # Main orchestration
├── tests/            # Comprehensive test suite
├── examples/         # Usage examples
└── .github/          # CI/CD workflows
```

### Known Limitations
- Requires Xdebug 3.x (2-7x overhead during tracing)
- Shutdown function limitation prevents certain types of integration testing
- Some components cannot reach 100% test coverage due to Xdebug dependencies

### Migration Notes
- This is the first stable release
- No breaking changes from previous development versions
- PHP 8.0+ is now required (was 7.4+ in development)

## [Unreleased]

### Planned Features
- Include function arguments (optional)
- Include return values (optional)
- Web UI for viewing traces
- Observer API extension for PHP 8.2+ (lower overhead alternative)
- Packagist publication for easier installation

---

[1.0.0]: https://github.com/Chris-Kol/php-trace/releases/tag/v1.0.0
