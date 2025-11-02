<?php

namespace PhpTrace\Formatter;

/**
 * Interface for trace output formatting
 */
interface FormatterInterface
{
    /**
     * Format parsed trace data into output string
     *
     * @param array<string, mixed> $traceData Parsed trace data
     * @return string Formatted output
     */
    public function format(array $traceData): string;

    /**
     * Get the file extension for this format
     *
     * @return string File extension (without dot), e.g., 'json', 'md'
     */
    public function getExtension(): string;
}
