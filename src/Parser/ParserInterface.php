<?php

namespace PhpTrace\Parser;

/**
 * Interface for trace file parsing
 */
interface ParserInterface
{
    /**
     * Parse a trace file into structured data
     *
     * @param string $traceFile Path to the trace file
     * @return array<string, mixed> Parsed trace data with metadata and call tree
     */
    public function parse(string $traceFile): array;
}
