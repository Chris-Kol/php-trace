# PHP Trace

[![CI](https://github.com/Chris-Kol/php-trace/workflows/CI/badge.svg)](https://github.com/Chris-Kol/php-trace/actions)
[![Coverage](https://codecov.io/gh/Chris-Kol/php-trace/branch/main/graph/badge.svg)](https://codecov.io/gh/Chris-Kol/php-trace)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg)](https://phpstan.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://www.php.net/)
[![Latest Release](https://img.shields.io/github/v/release/Chris-Kol/php-trace)](https://github.com/Chris-Kol/php-trace/releases)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Lightweight PHP execution tracer optimized for LLM-friendly debugging.

## What's New in v1.0.0

🎉 **First stable release!**

- ✨ **Configuration System**: Customize behavior with `phptrace.php` config file
- 🎛️ **Configurable Filtering**: Choose what to exclude from traces (vendor/, tests/, etc.)
- 🚀 **PHP 8.0+ Required**: Now using modern PHP features
- ✅ **Quality Tools**: PSR-12 compliant, PHPStan level 8, comprehensive tests (146 tests, 74% coverage)
- 📦 **Better Defaults**: Smart config discovery, cleaner output structure
- 🔒 **Security**: Automated security scanning and dependency auditing
- 🤖 **CI/CD**: Full GitHub Actions pipeline with multi-version PHP testing

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

**For production projects** (from Packagist - coming soon):
```bash
composer require --dev php-trace/php-trace
```

**For local development** (from GitHub):
```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/Chris-Kol/php-trace"
    }
  ],
  "require-dev": {
    "php-trace/php-trace": "^1.0"
  }
}
```

**For local path development:**
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

For Docker projects, install via Composer and configure in your PHP container:

```dockerfile
# In your Dockerfile or php.ini
auto_prepend_file=/var/www/html/vendor/php-trace/php-trace/src/bootstrap.php

# In your xdebug.ini
xdebug.mode=trace
```

Enable tracing via environment variable:
```yaml
# docker-compose.yml
environment:
  - TRACE=1
```

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

### Optional: phptrace.php Config File

Create `phptrace.php` in your project root to customize:

```php
<?php
return [
    'exclude_patterns' => ['vendor/', 'tests/'],  // What to exclude
    'output_dir' => 'traces',                      // Where to save
    'formats' => ['json', 'markdown'],             // Output formats
];
```

### Environment Variables

- `TRACE=1` - Enable tracing
- `TRACE_OUTPUT_DIR=./traces` - Custom output directory

**Activation Methods (in priority order):**
1. Environment variable: `TRACE=1`
2. `.env` file: `TRACE=1`
3. Query parameter: `?TRACE=1`
4. Cookie: `TRACE=1`

## How It Works

1. Checks if tracing is enabled (env, .env, query param, or cookie)
2. Starts Xdebug function trace
3. On request completion, generates JSON and Markdown outputs with hierarchical call tree
4. Automatically filters vendor code and converts to relative paths

## Performance

⚠️ **Important**: Xdebug tracing adds overhead. Only enable in development, never in production.

## Troubleshooting

**"Xdebug extension not loaded"**
```bash
pecl install xdebug
# Add to php.ini: zend_extension=xdebug.so
```

**"Trace files not generated"**
- Check Xdebug mode: `php -i | grep xdebug.mode` (should include "trace")
- Ensure output directory exists and is writable
- Verify bootstrap is loaded by checking for trace files after a request

**Empty traces**
```bash
php -d xdebug.mode=trace your-script.php
```

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Support

- **Issues**: [GitHub Issues](https://github.com/Chris-Kol/php-trace/issues)
- **Documentation**: See examples in the `examples/` directory
