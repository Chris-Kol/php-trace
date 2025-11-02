# Installation Guide

## Prerequisites

- PHP 8.0 or higher
- Xdebug 3.x extension

## Installing Xdebug

### macOS (Homebrew)

```bash
# Install via PECL
pecl install xdebug

# Or use Homebrew
brew install php@8.2-xdebug  # Replace with your PHP version
```

### Ubuntu/Debian

```bash
sudo apt-get install php-xdebug
```

### Other Linux

```bash
pecl install xdebug
```

### Verify Installation

```bash
php -m | grep xdebug
```

You should see `xdebug` in the output.

## Configuring Xdebug

Add to your `php.ini` (find location with `php --ini`):

```ini
zend_extension="xdebug.so"
xdebug.mode=off
xdebug.start_with_request=no
```

**Important**: Setting `xdebug.mode=off` ensures Xdebug has minimal overhead when not actively tracing.

Verify configuration:

```bash
php -i | grep xdebug.mode
```

## Installing PHP Trace

### Option 1: Clone Repository

```bash
cd /path/to/your/projects
git clone https://github.com/Chris-Kol/php-trace.git
```

### Option 2: Download ZIP

Download and extract to your preferred location.

### Option 3: Composer (when published)

```bash
composer require --dev php-trace/php-trace
```

## Usage Methods

### Method 1: Auto-prepend (Project-wide)

Add to your project's `.user.ini` or `php.ini`:

```ini
auto_prepend_file=/absolute/path/to/php-trace/src/bootstrap.php
```

Then trace any script:

```bash
TRACE=1 php your-script.php
```

### Method 2: Command-line Override

```bash
TRACE=1 php -d auto_prepend_file=/path/to/php-trace/src/bootstrap.php your-script.php
```

### Method 3: Manual Include

Add to the top of your PHP file:

```php
<?php
if (getenv('TRACE') === '1') {
    require '/path/to/php-trace/src/bootstrap.php';
}

// Your code here...
```

## Testing the Installation

Run the included test:

```bash
cd php-trace
php examples/test_without_xdebug.php
```

This will verify that the parsing and formatting components work correctly.

To test with real Xdebug tracing:

```bash
cd php-trace
TRACE=1 php -d auto_prepend_file=src/bootstrap.php examples/sample.php
```

Check `/tmp/` for the generated trace files:

```bash
ls -lt /tmp/php-trace-* | head -4
```

## Configuration Options

Set these environment variables to customize behavior:

- `TRACE=1` - Enable tracing
- `TRACE_OUTPUT_DIR=/custom/path` - Change output directory (default: /tmp)

## Troubleshooting

### "Xdebug extension not loaded"

Verify Xdebug is installed:

```bash
php -m | grep xdebug
```

If not listed, reinstall Xdebug and check your `php.ini`.

### "xdebug_start_trace() not available"

Check Xdebug version:

```bash
php -i | grep "xdebug.version"
```

You need Xdebug 3.x. If you have 2.x, upgrade:

```bash
pecl upgrade xdebug
```

### Trace files not generated

1. Check that `/tmp` is writable:
   ```bash
   ls -ld /tmp
   ```

2. Try custom output directory:
   ```bash
   TRACE=1 TRACE_OUTPUT_DIR=$HOME php your-script.php
   ```

3. Check PHP error log for messages:
   ```bash
   tail -f /var/log/php_errors.log
   ```

### Empty or incomplete traces

If functions are missing from the trace:

1. Verify vendor/ filtering is desired (it's intentional)
2. Check if the function actually executed
3. Review Xdebug configuration

### Performance impact

Xdebug tracing adds overhead (2-7x slowdown). This is normal and expected. Only use for debugging, not production.

## Next Steps

1. Read the [README.md](README.md) for usage examples
2. Try tracing your own PHP application
3. Feed the Markdown output to an LLM for debugging assistance

## Getting Help

- Check the [README](README.md) for common usage patterns
- Review example scripts in `examples/`
- Open an issue on GitHub for bugs or questions
