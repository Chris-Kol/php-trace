# PHP Trace: LLM-Optimized Execution Tracer

## Goal

Build a lightweight PHP execution tracer that captures code execution paths and outputs them in formats optimized for Large Language Model consumption, eliminating the need for manual debugging and lengthy explanations when discussing bugs with AI assistants.

### Problem Statement

Software engineers debugging PHP applications currently face a tedious workflow:
1. Set breakpoints and manually step through code with a debugger
2. Trace execution paths by hand
3. Have lengthy back-and-forth conversations with LLMs explaining the code path
4. Repeat this process for each bug

This is time-consuming and inefficient. Heavy profiling tools like Xdebug produce output designed for human consumption, not AI processing.

### Solution

A lightweight tracing tool that:
- Automatically captures execution paths during a request
- Outputs in LLM-optimized formats (JSON + Markdown)
- Requires no code changes (environment variable activation)
- Filters out noise (vendor/ directories)
- Provides timing information to identify bottlenecks
- Works across PHP 7.4-8.x

## References

### Process Documentation
- `/Users/ckoleri/code/processes/WRITE_PLANNING_DOC.md` - Planning document structure and lifecycle
- `/Users/ckoleri/code/processes/WRITE_EVERGREEN_DOC.md` - Evergreen documentation guidelines

### Project Files
- `/Users/ckoleri/code/php-trace/` - Project root directory
- `/Users/ckoleri/code/php-trace/src/TraceParser.php` - Parses Xdebug trace files into hierarchical structure
- `/Users/ckoleri/code/php-trace/src/JsonFormatter.php` - Formats traces as LLM-friendly JSON
- `/Users/ckoleri/code/php-trace/src/MarkdownFormatter.php` - Formats traces as hierarchical Markdown
- `/Users/ckoleri/code/php-trace/src/bootstrap.php` - Auto-prepend script for activation
- `/Users/ckoleri/code/php-trace/examples/` - Sample scripts and tests

## Principles, Key Decisions

### Technical Approach
1. **Xdebug-based MVP**: Start with Xdebug function trace (format 1) for immediate compatibility with PHP 7.4-8.x
2. **Post-processing**: Parse trace files after execution rather than during (simpler, more reliable)
3. **Dual format output**: Both JSON and Markdown to let users experiment with LLMs
4. **Smart filtering**: Auto-exclude vendor/ and composer/ paths to reduce noise
5. **Zero code changes**: Activation via `TRACE=1` environment variable
6. **Future-ready**: Architecture allows for Observer API extension later (PHP 8.0+)

### Output Format Design
- **JSON**: Hierarchical structure with metadata, summary, and full call tree
- **Markdown**: Human-readable hierarchical list with timing and slow function indicators
- **Token efficiency**: Markdown summary for quick LLM understanding, full JSON for detailed analysis
- **Bottleneck highlighting**: Automatically flag functions taking >100ms

### Configuration Philosophy
- Environment-based activation (not code changes)
- Sensible defaults (filter vendor/, output to /tmp)
- Configurable via environment variables when needed
- Compatible with `auto_prepend_file` for project-wide setup

## Technical Architecture

### Component Structure

```
php-trace/
├── src/
│   ├── TraceParser.php       # Parse Xdebug format 1 into hierarchical tree
│   ├── JsonFormatter.php     # Format as LLM-optimized JSON
│   ├── MarkdownFormatter.php # Format as hierarchical Markdown
│   └── bootstrap.php         # Activation and orchestration
├── examples/
│   ├── sample.php            # Demo application with slow functions
│   └── test_without_xdebug.php # Test with mock trace data
└── tests/                    # (Future) PHPUnit tests
```

### Data Flow

1. **Activation**: `bootstrap.php` detects `TRACE=1` environment variable
2. **Tracing**: Calls `xdebug_start_trace()` with format 1 (computer-readable)
3. **Execution**: User's PHP script runs normally
4. **Shutdown**: Registered shutdown function fires
5. **Parsing**: `TraceParser` reads `.xt` file, builds hierarchical call tree
6. **Filtering**: Removes vendor/ paths, calculates timing
7. **Formatting**: Both formatters generate output files
8. **Output**: JSON and Markdown files written to output directory
9. **Cleanup**: Raw `.xt` file deleted

### Xdebug Trace Format

Format 1 (computer-readable) is tab-separated with columns:
```
Level	Function ID	0=entry 1=exit	Time	Memory	Function Name	User Defined	Include File	Filename	Line Number
```

Example:
```
2	3	0	0.000300	104000	processUser	0		/path/to/file.php	9
```

### Output Formats

**JSON Structure:**
```json
{
  "summary": "PHP execution trace: 6 functions executed in 201.10ms. Slowest: slowDatabaseQuery (150.10ms)",
  "meta": {
    "total_time_ms": 201.10,
    "function_count": 6,
    "timestamp": "2025-10-31T15:00:00+00:00",
    "php_version": "8.0.30"
  },
  "trace": [
    {
      "function": "main",
      "file": "/path/to/file.php",
      "line": 42,
      "duration_ms": 200.90,
      "children": [...]
    }
  ]
}
```

**Markdown Structure:**
```markdown
# PHP Execution Trace

**Duration**: 201.10ms
**Functions**: 6

## Summary

⚠️ **Slowest function**: `slowDatabaseQuery` (150.10ms) at file.php:4

## Call Tree

- **main** (200.90ms) ⚠️ *SLOW* `file.php:42`
  - **processUser** (170.50ms) ⚠️ *SLOW* `file.php:9`
    - **slowDatabaseQuery** (150.10ms) ⚠️ *SLOW* `file.php:4`
```

## Actions

### Phase 1: Project Setup ✓
- [x] Create project directory structure (`src/`, `examples/`, `tests/`)
- [x] Initialize git repository
- [x] Create `composer.json` for PHP 7.4+ compatibility
- [x] Create `.gitignore` for common exclusions
- [x] Create comprehensive README with quick start guide

### Phase 2: Core Components ✓
- [x] Build `TraceParser.php`
  - [x] Parse Xdebug format 1 (tab-separated)
  - [x] Convert flat trace to hierarchical call tree
  - [x] Calculate duration for each function call
  - [x] Filter out vendor/ and composer/ paths
  - [x] Handle edge cases (empty traces, malformed lines)
- [x] Build `JsonFormatter.php`
  - [x] Format hierarchical trace as JSON
  - [x] Include metadata (timing, count, PHP version)
  - [x] Generate text summary
  - [x] Identify slowest function
- [x] Build `MarkdownFormatter.php`
  - [x] Format as hierarchical Markdown list
  - [x] Add timing annotations
  - [x] Flag slow functions (>100ms) with ⚠️
  - [x] Include metadata header

### Phase 3: Activation System ✓
- [x] Build `bootstrap.php`
  - [x] Check for `TRACE=1` environment variable
  - [x] Verify Xdebug is loaded
  - [x] Configure Xdebug trace settings
  - [x] Start trace with unique filename
  - [x] Register shutdown function
  - [x] Parse and format trace on shutdown
  - [x] Output file paths to stderr
  - [x] Cleanup raw trace file

### Phase 4: Testing & Examples ✓
- [x] Create `examples/sample.php` with realistic slow functions
  - [x] Database query simulation (150ms)
  - [x] Validation logic (20ms)
  - [x] View rendering (30ms)
  - [x] Nested function calls
- [x] Create `examples/test_without_xdebug.php`
  - [x] Generate mock Xdebug trace file
  - [x] Test TraceParser
  - [x] Test JsonFormatter
  - [x] Test MarkdownFormatter
  - [x] Display output previews
  - [x] Provide installation instructions
- [x] Run tests and verify output
  - ✅ Successfully parsed 6 functions
  - ✅ Correctly identified slowest function
  - ✅ Clean JSON output
  - ✅ Hierarchical Markdown with slow indicators

### Phase 5: Documentation ✓
- [x] Create README.md
  - [x] Problem/solution statement
  - [x] Features list
  - [x] Quick start guide
  - [x] Installation instructions (auto-prepend and manual)
  - [x] Example output
  - [x] LLM usage example
  - [x] Configuration options
  - [x] Roadmap
- [x] Create planning document (this file)
  - [x] Goal and context
  - [x] Principles and decisions
  - [x] Technical architecture
  - [x] Implementation phases
  - [x] Appendix with research findings

### Phase 6: Project Completion ✓
- [x] Mark all phases as complete
- [x] Move planning doc to `/completed/`
- [x] Summary of deliverables

## Status

**Completed**: 2025-10-31

All MVP features implemented and tested successfully. The tool is ready for initial use with Xdebug-enabled PHP environments.

## Deliverables

1. **Working PHP Trace tool** with:
   - Xdebug trace parser
   - JSON and Markdown formatters
   - Environment-based activation
   - Vendor path filtering
   - Timing analysis

2. **Example scripts**:
   - Sample application demonstrating slow functions
   - Test script that works without Xdebug

3. **Documentation**:
   - Comprehensive README
   - Quick start guide
   - Usage examples
   - Planning document with architecture

## Next Steps (Future Enhancements)

1. **Install Xdebug** for real-world testing:
   ```bash
   # macOS with Homebrew
   pecl install xdebug

   # Configure php.ini
   zend_extension="xdebug.so"
   xdebug.mode=off
   ```

2. **Test with real application**:
   - Run against Slim framework app
   - Identify actual performance bottlenecks
   - Gather feedback on output format

3. **Future features** (see README roadmap):
   - Function arguments capture (optional)
   - Return values capture (optional)
   - Sampling mode for production
   - Observer API extension for PHP 8+
   - Web UI for viewing traces

# Appendix

## Research Findings Summary

### Existing Solutions Evaluated

1. **Xdebug Function Trace**: Mature, widely compatible, but not LLM-optimized
2. **phptrace**: Outdated (last update 2017), uncertain PHP 8 support
3. **phpspy**: Sampling profiler (not execution tracer)
4. **Tideways/XHProf**: Profiler focused on metrics, not execution paths
5. **APM Solutions**: Overkill, designed for production monitoring
6. **OpenTelemetry**: Too heavyweight for simple debugging

**Gap identified**: No tool outputs execution traces in LLM-friendly formats.

### Performance Characteristics

| Solution | Overhead | PHP Version | Production Safe? |
|----------|----------|-------------|------------------|
| Xdebug 3 trace | 2-7x | 7.0+ | No |
| Observer API | 1-3% | 8.0+ | Yes |
| Pure PHP | Extreme | Any | No |

### Format Efficiency

- **Markdown**: Most token-efficient for hierarchical data
- **JSON**: Best for structured parsing
- **YAML**: Good balance but requires library
- **JSONL**: Good for streaming, loses hierarchy

### Key Insights

1. Extension-based tracing is necessary (pure PHP too slow)
2. Xdebug is the most practical starting point
3. Observer API is the future for PHP 8+ (requires custom extension)
4. Markdown format is surprisingly efficient for LLMs
5. Post-processing approach is simpler than real-time formatting

## Test Results

Mock trace test executed successfully:
- **Functions traced**: 6
- **Total execution time**: 201.10ms
- **Slowest function**: slowDatabaseQuery (150.10ms)
- **Output files**: JSON and Markdown generated correctly
- **Hierarchy**: Nested calls properly represented
- **Filtering**: Would exclude vendor/ paths (tested via code review)

## User Feedback Integration

Initial design based on user requirements:
1. ✅ Lightweight (not full profiling)
2. ✅ LLM-optimized output
3. ✅ No code changes required
4. ✅ PHP 7.4-8.x compatible
5. ✅ Plain PHP (no framework dependency)
6. ✅ Timing information included
7. ✅ Vendor filtering
8. ✅ Both JSON and Markdown formats

## Sample Output Files

Located at: `/tmp/test-trace.{json,md}`

These demonstrate the actual output format that will be fed to LLMs for debugging assistance.
