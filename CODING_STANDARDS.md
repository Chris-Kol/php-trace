# Coding Standards

This document defines the code quality standards for the PHP-Trace project.

## PHP Code Style

### PSR Standards
- **PSR-1**: Basic Coding Standard ✅
- **PSR-4**: Autoloading Standard ✅
- **PSR-12**: Extended Coding Style Guide ✅

### Automated Enforcement

Code style is enforced via **PHP-CS-Fixer**:

```bash
composer run cs-fix      # Auto-fix style issues
composer run cs-check    # Check without fixing
```

### Style Rules

```php
<?php

namespace PhpTrace\Example;

use PhpTrace\Config\TraceConfig;
use PhpTrace\Formatter\FormatterInterface;

/**
 * Example class demonstrating our coding style
 */
class ExampleClass implements ExampleInterface
{
    private const DEFAULT_THRESHOLD = 100;

    public function __construct(
        private TraceConfig $config,
        private FormatterInterface $formatter
    ) {
    }

    public function process(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $result = [];

        foreach ($data as $item) {
            $processed = $this->processItem($item);

            if ($processed !== null) {
                $result[] = $processed;
            }
        }

        return $result;
    }

    private function processItem(mixed $item): ?array
    {
        // Implementation
        return null;
    }
}
```

### Key Points

1. **Constructor Property Promotion** (PHP 8.0+)
   ```php
   public function __construct(
       private TraceConfig $config,  // ✅ Good
       private string $outputDir     // ✅ Good
   ) {
   }
   ```

2. **Type Hints Everywhere**
   ```php
   public function parse(string $file): array  // ✅ Good
   public function parse($file)                // ❌ Bad
   ```

3. **Nullable Types**
   ```php
   private function find(?string $path): ?array  // ✅ Good
   ```

4. **Array Shapes in DocBlocks**
   ```php
   /**
    * @return array{function: string, file: string, line: int, duration_ms: float}
    */
   public function parse(string $file): array
   ```

5. **Early Returns**
   ```php
   // ✅ Good
   public function process(?string $input): string
   {
       if ($input === null) {
           return '';
       }

       return $this->transform($input);
   }

   // ❌ Bad
   public function process(?string $input): string
   {
       if ($input !== null) {
           return $this->transform($input);
       } else {
           return '';
       }
   }
   ```

## Static Analysis

### PHPStan

Run static analysis:

```bash
composer run phpstan
```

**Current Level**: 5 (working towards level 8)

### Rules

1. **No @phpstan-ignore comments** without explanation
2. **No mixed types** unless absolutely necessary
3. **No @var annotations** for obvious types
4. **Document complex array shapes**

### Examples

```php
// ✅ Good - Clear types
public function parse(string $file): array
{
    /** @var array<string, mixed> */
    $data = json_decode($content, true);
    return $data;
}

// ❌ Bad - Mixed types
public function parse($file)
{
    return json_decode($content, true);
}
```

## Testing Standards

### Coverage Requirements

- **Minimum**: 80% code coverage
- **Target**: 90% code coverage
- **Critical paths**: 100% coverage

### Test Structure

```php
<?php

namespace PhpTrace\Tests\Unit\Parser;

use PhpTrace\Parser\XdebugParser;
use PHPUnit\Framework\TestCase;

class XdebugParserTest extends TestCase
{
    private XdebugParser $parser;

    protected function setUp(): void
    {
        $this->parser = new XdebugParser();
    }

    /**
     * Test naming: test_{method}__{scenario}
     */
    public function test_parse__returns_empty_array_when_file_not_found(): void
    {
        $result = $this->parser->parse('/nonexistent/file.xt');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_parse__builds_hierarchical_structure(): void
    {
        $traceFile = __DIR__ . '/../../Fixtures/sample-trace.xt';

        $result = $this->parser->parse($traceFile);

        $this->assertArrayHasKey('trace', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertIsArray($result['trace']);
    }

    /**
     * @dataProvider invalidTraceProvider
     */
    public function test_parse__handles_invalid_trace_gracefully(string $content): void
    {
        // Test implementation
    }

    public function invalidTraceProvider(): array
    {
        return [
            'empty file' => [''],
            'malformed lines' => ["invalid\ndata"],
            'missing columns' => ["1\t2\t3"],
        ];
    }
}
```

### Test Naming Convention

- **Unit tests**: `{ClassName}Test.php`
- **Integration tests**: `{Feature}IntegrationTest.php`
- **Test methods**: `test_{method}__{scenario}()`

### Assertions

```php
// ✅ Specific assertions
$this->assertSame(5, $count);
$this->assertTrue($result);
$this->assertInstanceOf(TraceConfig::class, $config);

// ❌ Generic assertions
$this->assertEquals(5, $count);  // Too loose
$this->assertNotNull($config);   // Not specific enough
```

## SOLID Principles

### Single Responsibility Principle

Each class should have one reason to change.

```php
// ✅ Good - Single responsibility
class XdebugParser implements ParserInterface
{
    public function parse(string $file): array
    {
        // Only parses, doesn't format or write
    }
}

// ❌ Bad - Multiple responsibilities
class TraceProcessor
{
    public function parseAndFormatAndWrite(string $file): void
    {
        // Doing too much
    }
}
```

### Open/Closed Principle

Open for extension, closed for modification.

```php
// ✅ Good - Open for extension
interface FormatterInterface
{
    public function format(array $data): string;
}

class JsonFormatter implements FormatterInterface { }
class MarkdownFormatter implements FormatterInterface { }
class HtmlFormatter implements FormatterInterface { }  // Easy to add

// ❌ Bad - Requires modification
class Formatter
{
    public function format(array $data, string $type): string
    {
        switch ($type) {
            case 'json': // ...
            case 'markdown': // ...
            // Need to modify this class to add new formats
        }
    }
}
```

### Liskov Substitution Principle

Subtypes must be substitutable for their base types.

```php
// ✅ Good - Substitutable
interface DetectorInterface
{
    public function isEnabled(): bool;
}

class QueryDetector implements DetectorInterface
{
    public function isEnabled(): bool
    {
        return isset($_GET['TRACE']) && $_GET['TRACE'] === '1';
    }
}
```

### Interface Segregation Principle

No client should depend on methods it doesn't use.

```php
// ✅ Good - Small, focused interfaces
interface ParserInterface
{
    public function parse(string $file): array;
}

interface FormatterInterface
{
    public function format(array $data): string;
}

// ❌ Bad - Fat interface
interface TraceProcessorInterface
{
    public function parse(string $file): array;
    public function format(array $data): string;
    public function write(string $content): void;
    public function detect(): bool;
}
```

### Dependency Inversion Principle

Depend on abstractions, not concretions.

```php
// ✅ Good - Depends on interface
class TraceManager
{
    public function __construct(
        private ParserInterface $parser,
        private FormatterInterface $formatter
    ) {
    }
}

// ❌ Bad - Depends on concrete class
class TraceManager
{
    public function __construct()
    {
        $this->parser = new XdebugParser();  // Tightly coupled
    }
}
```

## Documentation Standards

### Class Documentation

```php
/**
 * Parses Xdebug trace files into hierarchical structures
 *
 * This parser reads Xdebug format 1 (computer-readable) trace files
 * and converts them into nested arrays representing the call hierarchy.
 *
 * @see https://xdebug.org/docs/trace
 */
class XdebugParser implements ParserInterface
{
}
```

### Method Documentation

```php
/**
 * Parse an Xdebug trace file into a hierarchical structure
 *
 * @param string $traceFile Absolute path to the .xt trace file
 * @return array{meta: array, trace: array} Parsed trace data
 * @throws RuntimeException If file not found or unreadable
 */
public function parse(string $traceFile): array
{
}
```

### Complex Logic

```php
// Xdebug format 1 uses tab-separated values with the following columns:
// 0: Level (call depth)
// 1: Function ID
// 2: Type (0=entry, 1=exit, 2=return)
// 3: Time (in seconds)
// 4: Memory (in bytes)
// 5: Function name
// ...
$parts = explode("\t", $line);
```

## Git Commit Standards

### Conventional Commits

We follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

### Types

- `feat`: New feature
- `fix`: Bug fix
- `refactor`: Code change that neither fixes a bug nor adds a feature
- `test`: Adding or updating tests
- `docs`: Documentation changes
- `style`: Code style changes (formatting, missing semicolons, etc.)
- `perf`: Performance improvements
- `chore`: Maintenance tasks

### Examples

```
feat(parser): add support for Xdebug format 2

Implemented parser for the human-readable trace format as an
alternative to format 1.

Closes #123
```

```
fix(detector): handle missing .env file gracefully

Previously, the DotEnvDetector would throw an exception if the
.env file didn't exist. Now it returns false silently.

Fixes #456
```

```
refactor(formatter): extract path formatting to separate method

Moved relative path logic to a dedicated formatPath() method
to improve testability and reduce duplication.
```

## Error Handling

### Exceptions

```php
// ✅ Good - Specific exceptions
class TraceFileNotFoundException extends RuntimeException
{
}

public function parse(string $file): array
{
    if (!file_exists($file)) {
        throw new TraceFileNotFoundException("Trace file not found: {$file}");
    }
}

// ❌ Bad - Generic exceptions
public function parse(string $file): array
{
    if (!file_exists($file)) {
        throw new Exception("Error");  // Not specific enough
    }
}
```

### Logging

```php
// ✅ Good - PSR-3 logger
use Psr\Log\LoggerInterface;

class TraceManager
{
    public function __construct(
        private ?LoggerInterface $logger = null
    ) {
    }

    public function process(): void
    {
        $this->logger?->info('Starting trace processing');

        try {
            // ...
        } catch (Exception $e) {
            $this->logger?->error('Trace processing failed', [
                'exception' => $e,
                'trace_file' => $file
            ]);
        }
    }
}
```

## Performance Considerations

1. **Avoid N+1 queries** (even in file operations)
2. **Use generators** for large datasets
3. **Cache expensive operations**
4. **Profile before optimizing**

```php
// ✅ Good - Generator for large files
public function readLines(string $file): Generator
{
    $handle = fopen($file, 'r');

    while (($line = fgets($handle)) !== false) {
        yield $line;
    }

    fclose($handle);
}

// ❌ Bad - Load entire file into memory
public function readLines(string $file): array
{
    return file($file);  // Memory issues with large files
}
```

## Security

1. **Never execute user input**
2. **Validate file paths** before reading
3. **Sanitize output** for web contexts
4. **Don't log sensitive data**

```php
// ✅ Good - Path validation
public function readTrace(string $userPath): array
{
    $realPath = realpath($userPath);

    if ($realPath === false || !str_starts_with($realPath, $this->traceDir)) {
        throw new SecurityException('Invalid trace path');
    }

    return $this->parser->parse($realPath);
}
```

## Code Review Checklist

Before submitting a PR, verify:

- [ ] All tests pass (`composer test`)
- [ ] Code style is correct (`composer cs-check`)
- [ ] Static analysis passes (`composer phpstan`)
- [ ] Code coverage is maintained or improved
- [ ] Documentation is updated
- [ ] CHANGELOG.md is updated (for features/fixes)
- [ ] No breaking changes (or clearly documented)
- [ ] Commit messages follow conventional commits
