<?php

namespace PhpTrace\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PhpTrace\Writer\FileWriter;

class FileWriterTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/php-trace-writer-test-' . uniqid();
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

    public function test__write__creates_file_with_content(): void
    {
        $writer = new FileWriter();
        $filename = $this->tempDir . '/test.txt';
        $content = 'Hello, World!';

        $writer->write($content, $filename);

        $this->assertFileExists($filename);
        $this->assertEquals($content, file_get_contents($filename));
    }

    public function test__write__overwrites_existing_file(): void
    {
        $writer = new FileWriter();
        $filename = $this->tempDir . '/test.txt';

        $writer->write('First content', $filename);
        $this->assertEquals('First content', file_get_contents($filename));

        $writer->write('Second content', $filename);
        $this->assertEquals('Second content', file_get_contents($filename));
    }

    public function test__write__creates_nested_directories(): void
    {
        $writer = new FileWriter();
        $filename = $this->tempDir . '/nested/deep/file.txt';
        $content = 'Nested file';

        $writer->write($content, $filename);

        $this->assertFileExists($filename);
        $this->assertEquals($content, file_get_contents($filename));
        $this->assertDirectoryExists($this->tempDir . '/nested/deep');
    }

    public function test__write__respects_createDirectories_false(): void
    {
        $writer = new FileWriter(createDirectories: false);
        $filename = $this->tempDir . '/nonexistent/file.txt';
        $content = 'Test';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Directory does not exist');

        $writer->write($content, $filename);
    }

    public function test__write__uses_custom_directory_permissions(): void
    {
        $writer = new FileWriter(
            createDirectories: true,
            directoryPermissions: 0700
        );
        $filename = $this->tempDir . '/perms/file.txt';

        $writer->write('test', $filename);

        $this->assertDirectoryExists($this->tempDir . '/perms');
        // Check permissions (masking with 0777 to ignore file type bits)
        $perms = fileperms($this->tempDir . '/perms') & 0777;
        $this->assertEquals(0700, $perms);
    }

    public function test__write__handles_empty_content(): void
    {
        $writer = new FileWriter();
        $filename = $this->tempDir . '/empty.txt';

        $writer->write('', $filename);

        $this->assertFileExists($filename);
        $this->assertEquals('', file_get_contents($filename));
    }

    public function test__write__handles_large_content(): void
    {
        $writer = new FileWriter();
        $filename = $this->tempDir . '/large.txt';
        $content = str_repeat('A', 1024 * 1024); // 1MB

        $writer->write($content, $filename);

        $this->assertFileExists($filename);
        $this->assertEquals($content, file_get_contents($filename));
    }

    public function test__write__handles_unicode_content(): void
    {
        $writer = new FileWriter();
        $filename = $this->tempDir . '/unicode.txt';
        $content = '你好世界 🚀 Здравствуй мир';

        $writer->write($content, $filename);

        $this->assertFileExists($filename);
        $this->assertEquals($content, file_get_contents($filename));
    }

    public function test__delete__removes_existing_file(): void
    {
        $writer = new FileWriter();
        $filename = $this->tempDir . '/delete-me.txt';

        $writer->write('content', $filename);
        $this->assertFileExists($filename);

        $result = $writer->delete($filename);

        $this->assertTrue($result);
        $this->assertFileDoesNotExist($filename);
    }

    public function test__delete__returns_false_for_nonexistent_file(): void
    {
        $writer = new FileWriter();
        $filename = $this->tempDir . '/does-not-exist.txt';

        $result = $writer->delete($filename);

        $this->assertFalse($result);
    }

    public function test__exists__returns_true_for_existing_file(): void
    {
        $writer = new FileWriter();
        $filename = $this->tempDir . '/exists.txt';

        $writer->write('content', $filename);

        $this->assertTrue($writer->exists($filename));
    }

    public function test__exists__returns_false_for_nonexistent_file(): void
    {
        $writer = new FileWriter();
        $filename = $this->tempDir . '/does-not-exist.txt';

        $this->assertFalse($writer->exists($filename));
    }

    public function test__write__handles_special_characters_in_filename(): void
    {
        $writer = new FileWriter();
        $filename = $this->tempDir . '/file-with-special_chars@123.txt';
        $content = 'Special filename';

        $writer->write($content, $filename);

        $this->assertFileExists($filename);
        $this->assertEquals($content, file_get_contents($filename));
    }

    public function test__write__handles_json_content(): void
    {
        $writer = new FileWriter();
        $filename = $this->tempDir . '/data.json';
        $data = ['key' => 'value', 'number' => 123];
        $content = json_encode($data, JSON_PRETTY_PRINT);

        if ($content === false) {
            $this->fail('Failed to encode JSON');
        }

        $writer->write($content, $filename);

        $this->assertFileExists($filename);

        $encodedData = json_encode($data);
        if ($encodedData === false) {
            $this->fail('Failed to encode JSON for assertion');
        }

        $this->assertJsonStringEqualsJsonFile($filename, $encodedData);
    }

    public function test__write__to_existing_directory(): void
    {
        $writer = new FileWriter();
        mkdir($this->tempDir . '/existing');
        $filename = $this->tempDir . '/existing/file.txt';
        $content = 'In existing dir';

        $writer->write($content, $filename);

        $this->assertFileExists($filename);
        $this->assertEquals($content, file_get_contents($filename));
    }
}
