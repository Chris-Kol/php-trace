<?php

namespace PhpTrace;

use PhpTrace\Formatter\FormatterInterface;

/**
 * Formats parsed trace data into JSON optimized for LLM consumption
 */
class JsonFormatter implements FormatterInterface
{
    private ?string $projectRoot = null;

    /**
     * Format trace data as JSON
     *
     * @param array $traceData Parsed trace data from TraceParser
     * @return string JSON string
     */
    public function format(array $traceData): string
    {
        // Detect project root from trace data
        $this->detectProjectRoot($traceData['trace']);

        // Create a structure optimized for LLM understanding
        $output = [
            'summary' => $this->generateSummary($traceData),
            'meta' => $traceData['meta'],
            'trace' => $this->formatTraceTree($traceData['trace']),
        ];

        $json = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode JSON: ' . json_last_error_msg());
        }

        return $json;
    }

    public function getExtension(): string
    {
        return 'json';
    }

    /**
     * Generate a text summary for quick LLM understanding
     *
     * @param array $traceData
     * @return string
     */
    private function generateSummary(array $traceData): string
    {
        $meta = $traceData['meta'];
        $summary = sprintf(
            "PHP execution trace: %d functions executed in %.2fms",
            $meta['function_count'],
            $meta['total_time_ms']
        );

        // Find slowest function
        $slowest = $this->findSlowestFunction($traceData['trace']);
        if ($slowest) {
            $summary .= sprintf(
                ". Slowest: %s (%.2fms)",
                $slowest['function'],
                $slowest['duration_ms']
            );
        }

        return $summary;
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
     * Format the trace tree, removing internal fields
     *
     * @param array $trace
     * @return array
     */
    private function formatTraceTree(array $trace): array
    {
        $formatted = [];

        foreach ($trace as $call) {
            $formattedCall = [
                'function' => $call['function'],
                'file' => $this->formatFilePath($call['file']),
                'line' => $call['line'],
                'duration_ms' => $call['duration_ms'],
            ];

            if (!empty($call['children'])) {
                $formattedCall['children'] = $this->formatTraceTree($call['children']);
            }

            $formatted[] = $formattedCall;
        }

        return $formatted;
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
            return $filePath;
        }

        // Strip the project root
        if (strpos($filePath, $this->projectRoot) === 0) {
            $relativePath = substr($filePath, strlen($this->projectRoot) + 1);
            return $relativePath ?: $filePath;
        }

        return $filePath;
    }
}
