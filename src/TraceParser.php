<?php

namespace PhpTrace;

use PhpTrace\Parser\ParserInterface;

/**
 * Parses Xdebug function trace files (format 1 - computer-readable)
 * and converts them into a hierarchical call tree structure.
 */
class TraceParser implements ParserInterface
{
    /**
     * @param array<string> $excludePatterns
     */
    public function __construct(
        private array $excludePatterns = ['vendor/', 'composer/']
    ) {
    }

    /**
     * Parse an Xdebug trace file into a hierarchical structure
     *
     * @param string $traceFile Path to the Xdebug trace file
     * @return array Parsed trace data with metadata and call tree
     */
    public function parse(string $traceFile): array
    {
        if (!file_exists($traceFile)) {
            throw new \RuntimeException("Trace file not found: {$traceFile}");
        }

        $lines = file($traceFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (empty($lines)) {
            return [
                'meta' => [
                    'total_time_ms' => 0,
                    'function_count' => 0,
                    'timestamp' => date('c'),
                ],
                'trace' => [],
            ];
        }

        $callStack = [];
        $rootCalls = [];
        $startTime = null;
        $endTime = null;
        $functionCount = 0;

        foreach ($lines as $line) {
            // Xdebug format 1 (computer-readable):
            // Level    Function ID 0=entry 1=exit 2=return Time    Memory
            // Function Name   User Defined    Include File    Filename    Line Number Params
            // Example entry: 2 1   0   0.0010  100000  main    1       /path/to/file.php   1
            // Example exit:  2 1   1   0.0020  102000

            $parts = explode("\t", $line);

            if (count($parts) < 4) {
                continue; // Skip malformed lines
            }

            $level = (int)$parts[0];
            $functionId = (int)$parts[1];
            $type = (int)$parts[2]; // 0=entry, 1=exit, 2=return
            $time = (float)$parts[3];

            if ($startTime === null) {
                $startTime = $time;
            }
            $endTime = $time;

            if ($type === 0) {
                // Function entry
                $memory = isset($parts[4]) ? (int)$parts[4] : 0;
                $functionName = isset($parts[5]) ? $parts[5] : 'unknown';
                $filename = isset($parts[8]) ? $parts[8] : '';
                $lineNumber = isset($parts[9]) ? (int)$parts[9] : 0;

                // Skip excluded paths
                if ($this->shouldExclude($filename)) {
                    continue;
                }

                $functionCount++;

                $call = [
                    'function' => $functionName,
                    'file' => $filename,
                    'line' => $lineNumber,
                    'level' => $level,
                    'time_start' => $time,
                    'time_end' => null,
                    'duration_ms' => null,
                    'memory_start' => $memory,
                    'children' => [],
                ];

                if (empty($callStack)) {
                    // Root level call
                    $rootCalls[] = &$call;
                    $callStack[] = &$call;
                } else {
                    // Nested call - add to parent's children
                    $parent = &$callStack[count($callStack) - 1];
                    $parent['children'][] = &$call;
                    $callStack[] = &$call;
                }

                unset($call); // Break reference
            } elseif ($type === 1) {
                // Function exit
                if (!empty($callStack)) {
                    $exitedCall = &$callStack[count($callStack) - 1];
                    $exitedCall['time_end'] = $time;
                    $exitedCall['duration_ms'] = round(($time - $exitedCall['time_start']) * 1000, 2);
                    array_pop($callStack);
                }
            }
        }

        $totalTime = $endTime !== null && $startTime !== null
            ? round(($endTime - $startTime) * 1000, 2)
            : 0;

        return [
            'meta' => [
                'total_time_ms' => $totalTime,
                'function_count' => $functionCount,
                'timestamp' => date('c'),
                'php_version' => PHP_VERSION,
            ],
            'trace' => $rootCalls,
        ];
    }

    /**
     * Check if a file path should be excluded from the trace
     *
     * @param string $filepath
     * @return bool
     */
    private function shouldExclude(string $filepath): bool
    {
        foreach ($this->excludePatterns as $pattern) {
            if (strpos($filepath, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set custom exclude patterns
     *
     * @param array $patterns
     */
    public function setExcludePatterns(array $patterns): void
    {
        $this->excludePatterns = $patterns;
    }

    /**
     * Add an exclude pattern
     *
     * @param string $pattern
     */
    public function addExcludePattern(string $pattern): void
    {
        $this->excludePatterns[] = $pattern;
    }
}
