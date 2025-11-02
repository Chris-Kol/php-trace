# PHP Trace

<!-- CI/CD Badges - Will be added when GitHub Actions are set up -->
<!--
[![CI](https://github.com/Chris-Kol/php-trace/workflows/CI/badge.svg)](https://github.com/Chris-Kol/php-trace/actions)
[![Coverage](https://codecov.io/gh/Chris-Kol/php-trace/branch/main/graph/badge.svg)](https://codecov.io/gh/Chris-Kol/php-trace)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg)](https://phpstan.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://www.php.net/)
-->

Lightweight PHP execution tracer optimized for LLM-friendly debugging.

## What's New in v2.0

- ✨ **Configuration System**: Customize behavior with `phptrace.php` config file
- 🎛️ **Configurable Filtering**: Choose what to exclude from traces (vendor/, tests/, etc.)
- 🚀 **PHP 8.0+ Required**: Now using modern PHP features
- ✅ **Quality Tools**: PSR-12 compliant, PHPStan level 8, comprehensive tests
- 📦 **Better Defaults**: Smart config discovery, cleaner output structure

**Migration from v1**: The old setup still works! Just note the PHP version requirement changed to 8.0+.

## The Problem

Debugging PHP applications typically requires:
1. Setting breakpoints and stepping through code manually
2. Having lengthy conversations with LLMs to explain execution paths
3. Using heavy profiling tools with output designed for humans, not AI

## The Solution

PHP Trace captures execution paths automatically and outputs them in formats optimized for Large Language Model consumption, drastically reducing debugging overhead.

## Features

- **Lightweight**: Based on Xdebug function trace (not full profiling)
- **LLM-Optimized**: Outputs both JSON and Markdown formats
- **Zero Code Changes**: Activated via environment variable, .env file, query parameter, or cookie
- **Smart Filtering**: Automatically excludes vendor/ directories
- **Timing Information**: Shows execution time for each function
- **Relative Paths**: Portable output works across different machines
- **Browser Support**: Works with web requests and AJAX calls
- **Docker Compatible**: Easy integration with containerized projects
- **PHP 8.0+ Compatible**: Modern PHP with latest features
- **Configurable**: Customize via `phptrace.php` config file

## Quick Start

### Requirements

- PHP 8.0 or higher
- Xdebug 3.x installed

### Installation via Composer

```bash
composer require --dev php-trace/php-trace
```

Or for local development:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../php-trace"
    }
  ],
  "require-dev": {
    "php-trace/php-trace": "@dev"
  }
}
```

Then run:
```bash
composer install
```

## Usage

### Method 1: .env File (Recommended for Development)

Add to your project's `.env`:

```env
TRACE=1
TRACE_OUTPUT_DIR=traces
```

Configure PHP to auto-prepend the bootstrap (choose one):

**Option A: php.ini or .user.ini**
```ini
auto_prepend_file=vendor/php-trace/php-trace/src/bootstrap.php
xdebug.mode=trace
```

**Option B: PHP built-in server**
```bash
php -S localhost:8000 \
  -d auto_prepend_file=vendor/php-trace/php-trace/src/bootstrap.php \
  -d xdebug.mode=trace
```

Now **all requests** (pages, AJAX, API calls) will be automatically traced!

### Method 2: Query Parameter (Per-Request in Browser)

Add `?TRACE=1` to any URL:

```
http://localhost:8000/index.php?TRACE=1
http://localhost:8000/api/users?TRACE=1
```

### Method 3: Cookie (All Browser Requests)

Set a cookie once, trace all requests:

```javascript
// In browser console:
document.cookie = 'TRACE=1; path=/';
```

Now all page loads and AJAX requests are traced automatically!

To disable:
```javascript
document.cookie = 'TRACE=0; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
```

### Method 4: CLI Scripts

```bash
TRACE=1 php your-script.php
```

Or with custom output directory:
```bash
TRACE=1 TRACE_OUTPUT_DIR=./traces php your-script.php
```

## Docker Integration

### For Dockerized Projects

**1. Update `composer.json`:**

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../php-trace"
    }
  ],
  "require-dev": {
    "php-trace/php-trace": "@dev"
  }
}
```

**2. Update `docker/php/xdebug.ini`:**

Add `trace` to xdebug.mode:
```ini
xdebug.mode = coverage,debug,develop,trace
```

**3. Update `docker/php/php.ini`:**

Add auto-prepend:
```ini
auto_prepend_file=/var/www/html/vendor/php-trace/php-trace/src/bootstrap.php
```

**4. Add to `.env`:**

```env
TRACE=1
TRACE_OUTPUT_DIR=/var/www/html/traces
```

**5. Create traces directory and rebuild:**

```bash
mkdir traces
docker-compose build
docker-compose up -d
```

Done! All requests to your Docker container will be traced.

## Output

### File Locations

Traces are saved with timestamps and request info:

- **CLI**: `traces/php-trace-2025-10-31_16-30-45.{json,md}`
- **Web GET**: `traces/php-trace-2025-10-31_16-30-45-get-index.{json,md}`
- **Web POST**: `traces/php-trace-2025-10-31_16-30-45-post-api_users.{json,md}`

### Markdown Format (LLM-Friendly)

```markdown
# PHP Execution Trace

**Duration**: 244.72ms
**Functions**: 15

## Summary

⚠️ **Slowest function**: `slowDatabase` (155.48ms) at index.php:5

## Call Tree

- **renderPage** (244.32ms) ⚠️ *SLOW* `index.php:34`
  - **processData** (211.37ms) ⚠️ *SLOW* `index.php:19`
    - **slowDatabase** (155.48ms) ⚠️ *SLOW* `index.php:5`
      - **usleep** (155.10ms) ⚠️ *SLOW* `index.php:5`
```

### JSON Format (Machine-Readable)

```json
{
  "summary": "PHP execution trace: 15 functions executed in 244.72ms. Slowest: slowDatabase (155.48ms)",
  "meta": {
    "total_time_ms": 244.72,
    "function_count": 15,
    "timestamp": "2025-10-31T16:46:39+00:00",
    "php_version": "8.0.30"
  },
  "trace": [
    {
      "function": "renderPage",
      "file": "index.php",
      "line": 34,
      "duration_ms": 244.32,
      "children": [...]
    }
  ]
}
```

## Feeding Traces to an LLM

Simply copy the Markdown output and paste it into your AI conversation:

```
I'm debugging a slow request. Here's the execution trace:

[paste markdown from traces/php-trace-*.md]

Can you identify the bottleneck and suggest optimizations?
```

The LLM will immediately see:
- Execution flow and function hierarchy
- Which functions are slow (marked with ⚠️)
- Exact file locations with relative paths
- Timing for each operation

## Configuration

### phptrace.php Config File (Recommended)

Create a `phptrace.php` file in your project root to customize behavior:

```php
<?php
// phptrace.php

return [
    /**
     * Patterns to exclude from traces
     * Set to [] to include vendor code
     */
    'exclude_patterns' => [
        'vendor/',
        'composer/',
        // Add more patterns as needed
    ],

    /**
     * Directory to write trace files
     */
    'output_dir' => 'traces',

    /**
     * Output formats to generate
     */
    'formats' => ['json', 'markdown'],
];
```

A template is provided at `phptrace.php.dist`. Copy it to get started:

```bash
cp vendor/php-trace/php-trace/phptrace.php.dist phptrace.php
```

**Config File Discovery:**
PHP-Trace automatically looks for `phptrace.php` in:
1. Current directory
2. Parent directories (up to 5 levels)

**Example: Include Vendor Code**

To debug third-party libraries:

```php
<?php
return [
    'exclude_patterns' => [], // Include everything!
    'output_dir' => 'traces',
    'formats' => ['json', 'markdown'],
];
```

### Environment Variables

Environment variables have the highest priority and override config file settings:

- `TRACE=1` - Enable tracing
- `TRACE_OUTPUT_DIR=./traces` - Custom output directory (overrides `phptrace.php` config)

**Priority Order for Output Directory:**
1. Environment variable: `TRACE_OUTPUT_DIR`
2. Config file: `phptrace.php` → `'output_dir'`
3. Default: `traces/` (relative to project root)

### Activation Priority (highest to lowest)

1. Environment variable: `TRACE=1`
2. `.env` file: `TRACE=1`
3. Query parameter: `?TRACE=1`
4. Cookie: `TRACE=1`

## How It Works

1. Bootstrap checks if tracing is enabled (env, .env, query param, or cookie)
2. If enabled, starts Xdebug function trace with `xdebug.mode=trace`
3. Script executes normally
4. On shutdown:
   - Loads configuration from `phptrace.php` (if exists)
   - Parses the Xdebug trace file
   - Detects project root (looks for `composer.json`, `.git`, or `src/`)
   - Converts absolute paths to relative paths
   - Builds hierarchical call tree with timing
   - Filters code based on config (default: excludes `vendor/` and `composer/`)
   - Generates both JSON and Markdown outputs
   - Cleans up raw trace files

## Performance

- **Overhead when tracing**: 2-7x slowdown (development only)
- **Overhead when not tracing**: Near-zero (just checks if enabled)
- **File size**: ~1-5KB per trace (depends on call depth)

## Examples

See the `examples/` directory:
- `sample.php` - CLI script example
- `web.php` - Web application example
- `test_without_xdebug.php` - Test components without Xdebug

See the `test-project/` for a complete working example with Composer integration.

## Troubleshooting

### "Xdebug extension not loaded"

Install Xdebug:
```bash
pecl install xdebug
```

Then add to `php.ini`:
```ini
zend_extension=xdebug.so
xdebug.mode=trace
```

### "Trace files not generated"

1. Check Xdebug mode: `php -i | grep xdebug.mode`
2. Ensure output directory exists: `mkdir -p traces`
3. Check permissions: `ls -ld traces/`
4. Verify bootstrap is loaded: add `error_log('Trace loaded')` to bootstrap

### Traces are empty

Make sure `xdebug.mode` includes `trace`:
```bash
php -d xdebug.mode=trace your-script.php
```

### "STDERR not defined" error

This is fixed in the latest version. Update to ensure compatibility with web contexts.

## Roadmap

- [x] CLI script tracing
- [x] Web request tracing
- [x] Browser query parameter support
- [x] Cookie-based activation
- [x] .env file support
- [x] Relative path output
- [x] Docker compatibility
- [x] Configuration system (`phptrace.php`)
- [x] Configurable filtering
- [x] PSR-12 code style compliance
- [x] PHPStan level 8 static analysis
- [x] Comprehensive test coverage
- [ ] Include function arguments (optional)
- [ ] Include return values (optional)
- [ ] Web UI for viewing traces
- [ ] Observer API extension for PHP 8.2+ (lower overhead)
- [ ] Migration to latest PHP features (8.3+)

## License

MIT

## Contributing

Contributions welcome! Please open an issue or PR.

## Credits

Built to solve the problem of explaining code execution paths to LLMs during debugging sessions.
