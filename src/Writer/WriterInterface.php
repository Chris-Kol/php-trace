<?php

namespace PhpTrace\Writer;

/**
 * Interface for writing output to filesystem
 */
interface WriterInterface
{
    /**
     * Write content to a file
     *
     * @param string $content Content to write
     * @param string $filename Full path to the output file
     */
    public function write(string $content, string $filename): void;
}
