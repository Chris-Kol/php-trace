<?php

namespace PhpTrace\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PhpTrace\Detector\DotEnvDetector;

class DotEnvDetectorTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/php-trace-dotenv-test-' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $scannedFiles = scandir($dir);
        if ($scannedFiles === false) {
            return;
        }

        $files = array_diff($scannedFiles, ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function test__isEnabled__returns_true_when_env_file_has_variable(): void
    {
        file_put_contents($this->tempDir . '/.env', "TRACE=1\n");

        $originalDir = getcwd();
        if ($originalDir === false) {
            $this->markTestSkipped('Cannot get current directory');
        }

        chdir($this->tempDir);

        try {
            $detector = new DotEnvDetector();
            $this->assertTrue($detector->isEnabled());
        } finally {
            chdir($originalDir);
        }
    }

    public function test__isEnabled__returns_false_when_no_env_file_exists(): void
    {
        $originalDir = getcwd();
        if ($originalDir === false) {
            $this->markTestSkipped('Cannot get current directory');
        }

        chdir($this->tempDir);

        try {
            $detector = new DotEnvDetector();
            $this->assertFalse($detector->isEnabled());
        } finally {
            chdir($originalDir);
        }
    }

    public function test__isEnabled__returns_false_when_variable_has_wrong_value(): void
    {
        file_put_contents($this->tempDir . '/.env', "TRACE=0\n");

        $originalDir = getcwd();
        if ($originalDir === false) {
            $this->markTestSkipped('Cannot get current directory');
        }

        chdir($this->tempDir);

        try {
            $detector = new DotEnvDetector();
            $this->assertFalse($detector->isEnabled());
        } finally {
            chdir($originalDir);
        }
    }

    public function test__isEnabled__finds_env_file_in_parent_directory(): void
    {
        file_put_contents($this->tempDir . '/.env', "TRACE=1\n");

        $subDir = $this->tempDir . '/subdir';
        mkdir($subDir);

        $originalDir = getcwd();
        if ($originalDir === false) {
            $this->markTestSkipped('Cannot get current directory');
        }

        chdir($subDir);

        try {
            $detector = new DotEnvDetector();
            $this->assertTrue($detector->isEnabled());
        } finally {
            chdir($originalDir);
        }
    }

    public function test__isEnabled__handles_whitespace_in_env_file(): void
    {
        file_put_contents($this->tempDir . '/.env', "  TRACE  =  1  \n");

        $originalDir = getcwd();
        if ($originalDir === false) {
            $this->markTestSkipped('Cannot get current directory');
        }

        chdir($this->tempDir);

        try {
            $detector = new DotEnvDetector();
            $this->assertTrue($detector->isEnabled());
        } finally {
            chdir($originalDir);
        }
    }

    public function test__isEnabled__ignores_other_variables(): void
    {
        file_put_contents($this->tempDir . '/.env', "FOO=bar\nTRACE=1\nBAZ=qux\n");

        $originalDir = getcwd();
        if ($originalDir === false) {
            $this->markTestSkipped('Cannot get current directory');
        }

        chdir($this->tempDir);

        try {
            $detector = new DotEnvDetector();
            $this->assertTrue($detector->isEnabled());
        } finally {
            chdir($originalDir);
        }
    }

    public function test__constructor__accepts_custom_variable_name(): void
    {
        file_put_contents($this->tempDir . '/.env', "DEBUG=1\n");

        $originalDir = getcwd();
        if ($originalDir === false) {
            $this->markTestSkipped('Cannot get current directory');
        }

        chdir($this->tempDir);

        try {
            $detector = new DotEnvDetector('DEBUG');
            $this->assertTrue($detector->isEnabled());
        } finally {
            chdir($originalDir);
        }
    }

    public function test__constructor__accepts_custom_expected_value(): void
    {
        file_put_contents($this->tempDir . '/.env', "TRACE=enabled\n");

        $originalDir = getcwd();
        if ($originalDir === false) {
            $this->markTestSkipped('Cannot get current directory');
        }

        chdir($this->tempDir);

        try {
            $detector = new DotEnvDetector('TRACE', 'enabled');
            $this->assertTrue($detector->isEnabled());
        } finally {
            chdir($originalDir);
        }
    }

    public function test__isEnabled__stops_searching_at_max_depth(): void
    {
        file_put_contents($this->tempDir . '/.env', "TRACE=1\n");

        $deepDir = $this->tempDir . '/a/b/c/d/e/f';
        mkdir($deepDir, 0777, true);

        $originalDir = getcwd();
        if ($originalDir === false) {
            $this->markTestSkipped('Cannot get current directory');
        }

        chdir($deepDir);

        try {
            $detector = new DotEnvDetector('TRACE', '1', 5);
            $this->assertFalse($detector->isEnabled());
        } finally {
            chdir($originalDir);
        }
    }

    public function test__isEnabled__handles_comments_in_env_file(): void
    {
        file_put_contents(
            $this->tempDir . '/.env',
            "# This is a comment\nTRACE=1\n# Another comment\n"
        );

        $originalDir = getcwd();
        if ($originalDir === false) {
            $this->markTestSkipped('Cannot get current directory');
        }

        chdir($this->tempDir);

        try {
            $detector = new DotEnvDetector();
            $this->assertTrue($detector->isEnabled());
        } finally {
            chdir($originalDir);
        }
    }

    public function test__isEnabled__returns_false_for_partial_match(): void
    {
        file_put_contents($this->tempDir . '/.env', "TRACE_DEBUG=1\n");

        $originalDir = getcwd();
        if ($originalDir === false) {
            $this->markTestSkipped('Cannot get current directory');
        }

        chdir($this->tempDir);

        try {
            $detector = new DotEnvDetector('TRACE');
            $this->assertFalse($detector->isEnabled());
        } finally {
            chdir($originalDir);
        }
    }
}
