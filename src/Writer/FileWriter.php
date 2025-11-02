<?php

namespace PhpTrace\Writer;

/**
 * Writes content to filesystem
 */
class FileWriter implements WriterInterface
{
    public function __construct(
        private bool $createDirectories = true,
        private int $directoryPermissions = 0755
    ) {
    }

    public function write(string $content, string $filename): void
    {
        $directory = dirname($filename);

        // Ensure directory exists if configured
        if ($this->createDirectories) {
            if (!is_dir($directory)) {
                $created = mkdir($directory, $this->directoryPermissions, true);

                if (!$created) {
                    throw new \RuntimeException(
                        "Failed to create directory: {$directory}"
                    );
                }
            }
        } else {
            // If not creating directories, check that parent directory exists
            if (!is_dir($directory)) {
                throw new \RuntimeException(
                    "Directory does not exist: {$directory}"
                );
            }
        }

        // Write the file
        $bytesWritten = file_put_contents($filename, $content);

        if ($bytesWritten === false) {
            throw new \RuntimeException(
                "Failed to write file: {$filename}"
            );
        }
    }

    /**
     * Delete a file
     *
     * @param string $filename Path to file to delete
     * @return bool True if file was deleted, false if it didn't exist
     * @throws \RuntimeException If deletion fails
     */
    public function delete(string $filename): bool
    {
        if (!file_exists($filename)) {
            return false;
        }

        $deleted = unlink($filename);

        if (!$deleted) {
            throw new \RuntimeException(
                "Failed to delete file: {$filename}"
            );
        }

        return true;
    }

    /**
     * Check if a file exists
     */
    public function exists(string $filename): bool
    {
        return file_exists($filename);
    }
}
