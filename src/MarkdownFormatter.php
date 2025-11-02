<?php

namespace PhpTrace;

use PhpTrace\Formatter\FormatterInterface;

/**
 * Formats parsed trace data into Markdown optimized for LLM consumption
 */
class MarkdownFormatter implements FormatterInterface
{
    private const SLOW_THRESHOLD_MS = 100;
    private ?string $projectRoot = null;

    /**
     * Format trace data as Markdown
     *
     * @param array $traceData Parsed trace data from TraceParser
     * @return string Markdown string
     */
    public function format(array $traceData): string
    {
        // Detect project root from trace data
        $this->detectProjectRoot($traceData['trace']);

        $output = [];

        // Header
        $output[] = "# PHP Execution Trace";
        $output[] = "";

        // Metadata
        $meta = $traceData['meta'];
        $output[] = sprintf("**Duration**: %.2fms", $meta['total_time_ms']);
        $output[] = sprintf("**Functions**: %d", $meta['function_count']);
        $output[] = sprintf("**PHP Version**: %s", $meta['php_version']);
        $output[] = sprintf("**Timestamp**: %s", $meta['timestamp']);
        $output[] = "";

        // Summary
        $slowest = $this->findSlowestFunction($traceData['trace']);
        if ($slowest) {
            $output[] = "## Summary";
            $output[] = "";
            $output[] = sprintf(
                "⚠️ **Slowest function**: `%s` (%.2fms) at %s:%d",
                $slowest['function'],
                $slowest['duration_ms'],
                $this->formatFilePath($slowest['file']),
                $slowest['line']
            );
            $output[] = "";
        }

        // Call tree
        $output[] = "## Call Tree";
        $output[] = "";
        $output = array_merge($output, $this->formatTraceTree($traceData['trace'], 0));

        return implode("\n", $output);
    }

    /**
     * Format the trace tree as a hierarchical Markdown list
     *
     * @param array $trace
     * @param int $depth Current depth level
     * @return array Array of markdown lines
     */
    private function formatTraceTree(array $trace, int $depth = 0): array
    {
        $lines = [];
        $indent = str_repeat("  ", $depth);

        foreach ($trace as $call) {
            $duration = $call['duration_ms'] !== null ? $call['duration_ms'] : 0;
            $isSlow = $duration >= self::SLOW_THRESHOLD_MS;

            // Format: - **function()** (duration) file:line
            $line = sprintf(
                "%s- **%s** (%.2fms)",
                $indent,
                $call['function'],
                $duration
            );

            // Add slow indicator
            if ($isSlow) {
                $line .= " ⚠️ *SLOW*";
            }

            // Add file location
            $line .= sprintf(
                " `%s:%d`",
                $this->formatFilePath($call['file']),
                $call['line']
            );

            $lines[] = $line;

            // Recursively format children
            if (!empty($call['children'])) {
                $childLines = $this->formatTraceTree($call['children'], $depth + 1);
                $lines = array_merge($lines, $childLines);
            }
        }

        return $lines;
    }

    /**
     * Find the slowest function in the trace
     *
     * @param array $trace
     * @return array|null
     */
    private function findSlowestFunction(array $trace): ?array
    {
        $slowest = null;

        foreach ($trace as $call) {
            if ($call['duration_ms'] !== null) {
                if ($slowest === null || $call['duration_ms'] > $slowest['duration_ms']) {
                    $slowest = $call;
                }
            }

            // Recursively check children
            if (!empty($call['children'])) {
                $childSlowest = $this->findSlowestFunction($call['children']);
                if ($childSlowest && ($slowest === null || $childSlowest['duration_ms'] > $slowest['duration_ms'])) {
                    $slowest = $childSlowest;
                }
            }
        }

        return $slowest;
    }

    /**
     * Detect the project root directory from trace file paths
     *
     * @param array $trace
     */
    private function detectProjectRoot(array $trace): void
    {
        if ($this->projectRoot !== null) {
            return;
        }

        // Find the common path prefix from all files
        foreach ($trace as $call) {
            if (!empty($call['file'])) {
                // Look for common project markers
                $path = $call['file'];

                // Walk up the directory tree looking for project root indicators
                $dir = dirname($path);
                while ($dir !== '/' && $dir !== '.') {
                    // Check for common project root markers
                    if (
                        file_exists($dir . '/composer.json') ||
                        file_exists($dir . '/.git') ||
                        file_exists($dir . '/src')
                    ) {
                        $this->projectRoot = $dir;
                        return;
                    }
                    $dir = dirname($dir);
                }
            }

            // Recursively check children
            if (!empty($call['children'])) {
                $this->detectProjectRoot($call['children']);
                if ($this->projectRoot !== null) {
                    return;
                }
            }
        }
    }

    /**
     * Format a file path as relative to project root
     *
     * @param string $filePath
     * @return string
     */
    private function formatFilePath(string $filePath): string
    {
        if ($this->projectRoot === null || empty($filePath)) {
            return basename($filePath);
        }

        // Strip the project root
        if (strpos($filePath, $this->projectRoot) === 0) {
            $relativePath = substr($filePath, strlen($this->projectRoot) + 1);
            return $relativePath ?: basename($filePath);
        }

        return basename($filePath);
    }

    public function getExtension(): string
    {
        return 'md';
    }
}
