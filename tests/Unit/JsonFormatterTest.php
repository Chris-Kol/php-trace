<?php

namespace PhpTrace\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PhpTrace\JsonFormatter;

class JsonFormatterTest extends TestCase
{
    public function test__format__returns_valid_json(): void
    {
        $formatter = new JsonFormatter();
        $traceData = $this->getMockTraceData();

        $result = $formatter->format($traceData);

        $this->assertJson($result);
    }

    public function test__format__contains_summary(): void
    {
        $formatter = new JsonFormatter();
        $traceData = $this->getMockTraceData();

        $result = $formatter->format($traceData);
        $decoded = json_decode($result, true);

        $this->assertArrayHasKey('summary', $decoded);
        $this->assertIsString($decoded['summary']);
        $this->assertStringContainsString('functions executed', $decoded['summary']);
    }

    public function test__format__contains_metadata(): void
    {
        $formatter = new JsonFormatter();
        $traceData = $this->getMockTraceData();

        $result = $formatter->format($traceData);
        $decoded = json_decode($result, true);

        $this->assertArrayHasKey('meta', $decoded);
        $this->assertEquals(100.5, $decoded['meta']['total_time_ms']);
        $this->assertEquals(3, $decoded['meta']['function_count']);
        $this->assertArrayHasKey('timestamp', $decoded['meta']);
        $this->assertArrayHasKey('php_version', $decoded['meta']);
    }

    public function test__format__contains_trace_tree(): void
    {
        $formatter = new JsonFormatter();
        $traceData = $this->getMockTraceData();

        $result = $formatter->format($traceData);
        $decoded = json_decode($result, true);

        $this->assertArrayHasKey('trace', $decoded);
        $this->assertIsArray($decoded['trace']);
        $this->assertCount(1, $decoded['trace']);
    }

    public function test__format__includes_slowest_function_in_summary(): void
    {
        $formatter = new JsonFormatter();
        $traceData = $this->getMockTraceDataWithSlowestFunction();

        $result = $formatter->format($traceData);
        $decoded = json_decode($result, true);

        $this->assertStringContainsString('Slowest:', $decoded['summary']);
        $this->assertStringContainsString('slowFunction', $decoded['summary']);
        $this->assertStringContainsString('150.00ms', $decoded['summary']);
    }

    public function test__format__handles_empty_trace(): void
    {
        $formatter = new JsonFormatter();
        $traceData = [
            'meta' => [
                'total_time_ms' => 0,
                'function_count' => 0,
                'timestamp' => '2025-01-01T00:00:00+00:00',
                'php_version' => '8.0.0',
            ],
            'trace' => [],
        ];

        $result = $formatter->format($traceData);
        $decoded = json_decode($result, true);

        $this->assertArrayHasKey('summary', $decoded);
        $this->assertArrayHasKey('meta', $decoded);
        $this->assertArrayHasKey('trace', $decoded);
        $this->assertEmpty($decoded['trace']);
    }

    public function test__format__formats_trace_tree_structure(): void
    {
        $formatter = new JsonFormatter();
        $traceData = $this->getMockTraceData();

        $result = $formatter->format($traceData);
        $decoded = json_decode($result, true);

        $rootCall = $decoded['trace'][0];
        $this->assertEquals('main', $rootCall['function']);
        // File path might be relative or absolute depending on project root detection
        $this->assertStringContainsString('index.php', $rootCall['file']);
        $this->assertEquals(1, $rootCall['line']);
        $this->assertEquals(100.5, $rootCall['duration_ms']);

        $this->assertArrayHasKey('children', $rootCall);
        $this->assertCount(2, $rootCall['children']);
    }

    public function test__format__includes_nested_children(): void
    {
        $formatter = new JsonFormatter();
        $traceData = $this->getMockTraceData();

        $result = $formatter->format($traceData);
        $decoded = json_decode($result, true);

        $child = $decoded['trace'][0]['children'][0];
        $this->assertEquals('functionA', $child['function']);
        $this->assertEquals(50.0, $child['duration_ms']);

        $nestedChild = $decoded['trace'][0]['children'][1];
        $this->assertEquals('functionB', $nestedChild['function']);
        $this->assertEquals(40.0, $nestedChild['duration_ms']);
    }

    public function test__format__finds_slowest_function_in_children(): void
    {
        $formatter = new JsonFormatter();
        $traceData = $this->getMockTraceDataWithNestedSlowestFunction();

        $result = $formatter->format($traceData);
        $decoded = json_decode($result, true);

        $this->assertStringContainsString('deepFunction', $decoded['summary']);
        $this->assertStringContainsString('200.00ms', $decoded['summary']);
    }

    public function test__format__handles_null_duration(): void
    {
        $formatter = new JsonFormatter();
        $traceData = [
            'meta' => [
                'total_time_ms' => 10.0,
                'function_count' => 1,
                'timestamp' => '2025-01-01T00:00:00+00:00',
                'php_version' => '8.0.0',
            ],
            'trace' => [
                [
                    'function' => 'testFunc',
                    'file' => 'test.php',
                    'line' => 1,
                    'duration_ms' => null,
                ],
            ],
        ];

        $result = $formatter->format($traceData);
        $decoded = json_decode($result, true);

        $this->assertNull($decoded['trace'][0]['duration_ms']);
    }

    public function test__getExtension__returns_json(): void
    {
        $formatter = new JsonFormatter();

        $this->assertEquals('json', $formatter->getExtension());
    }

    public function test__format__uses_pretty_print(): void
    {
        $formatter = new JsonFormatter();
        $traceData = $this->getMockTraceData();

        $result = $formatter->format($traceData);

        // Pretty print should have newlines and indentation
        $this->assertStringContainsString("\n", $result);
        $this->assertStringContainsString('    ', $result);
    }

    private function getMockTraceData(): array
    {
        return [
            'meta' => [
                'total_time_ms' => 100.5,
                'function_count' => 3,
                'timestamp' => '2025-01-01T00:00:00+00:00',
                'php_version' => '8.0.0',
            ],
            'trace' => [
                [
                    'function' => 'main',
                    'file' => '/project/src/index.php',
                    'line' => 1,
                    'duration_ms' => 100.5,
                    'children' => [
                        [
                            'function' => 'functionA',
                            'file' => '/project/src/functions.php',
                            'line' => 10,
                            'duration_ms' => 50.0,
                        ],
                        [
                            'function' => 'functionB',
                            'file' => '/project/src/functions.php',
                            'line' => 20,
                            'duration_ms' => 40.0,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getMockTraceDataWithSlowestFunction(): array
    {
        return [
            'meta' => [
                'total_time_ms' => 200.0,
                'function_count' => 2,
                'timestamp' => '2025-01-01T00:00:00+00:00',
                'php_version' => '8.0.0',
            ],
            'trace' => [
                [
                    'function' => 'main',
                    'file' => '/project/src/index.php',
                    'line' => 1,
                    'duration_ms' => 50.0,
                ],
                [
                    'function' => 'slowFunction',
                    'file' => '/project/src/slow.php',
                    'line' => 5,
                    'duration_ms' => 150.0,
                ],
            ],
        ];
    }

    private function getMockTraceDataWithNestedSlowestFunction(): array
    {
        return [
            'meta' => [
                'total_time_ms' => 250.0,
                'function_count' => 3,
                'timestamp' => '2025-01-01T00:00:00+00:00',
                'php_version' => '8.0.0',
            ],
            'trace' => [
                [
                    'function' => 'main',
                    'file' => '/project/src/index.php',
                    'line' => 1,
                    'duration_ms' => 50.0,
                    'children' => [
                        [
                            'function' => 'midFunction',
                            'file' => '/project/src/mid.php',
                            'line' => 10,
                            'duration_ms' => 100.0,
                            'children' => [
                                [
                                    'function' => 'deepFunction',
                                    'file' => '/project/src/deep.php',
                                    'line' => 20,
                                    'duration_ms' => 200.0,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test__format__handles_empty_file_path(): void
    {
        $formatter = new JsonFormatter();
        $traceData = [
            'meta' => [
                'total_time_ms' => 10.0,
                'function_count' => 1,
                'timestamp' => '2025-01-01T00:00:00+00:00',
                'php_version' => '8.0.0',
            ],
            'trace' => [
                [
                    'function' => 'testFunc',
                    'file' => '',
                    'line' => 1,
                    'duration_ms' => 10.0,
                ],
            ],
        ];

        $result = $formatter->format($traceData);
        $decoded = json_decode($result, true);

        $this->assertEquals('', $decoded['trace'][0]['file']);
    }

    public function test__format__handles_trace_without_slowest_function(): void
    {
        $formatter = new JsonFormatter();
        $traceData = [
            'meta' => [
                'total_time_ms' => 10.0,
                'function_count' => 1,
                'timestamp' => '2025-01-01T00:00:00+00:00',
                'php_version' => '8.0.0',
            ],
            'trace' => [
                [
                    'function' => 'testFunc',
                    'file' => '/project/test.php',
                    'line' => 1,
                    'duration_ms' => null, // No duration
                ],
            ],
        ];

        $result = $formatter->format($traceData);
        $decoded = json_decode($result, true);

        // Summary should not mention slowest function
        $this->assertStringNotContainsString('Slowest:', $decoded['summary']);
    }

    public function test__format__handles_multiple_nulls_in_duration(): void
    {
        $formatter = new JsonFormatter();
        $traceData = [
            'meta' => [
                'total_time_ms' => 50.0,
                'function_count' => 3,
                'timestamp' => '2025-01-01T00:00:00+00:00',
                'php_version' => '8.0.0',
            ],
            'trace' => [
                [
                    'function' => 'func1',
                    'file' => '/project/test.php',
                    'line' => 1,
                    'duration_ms' => null,
                ],
                [
                    'function' => 'func2',
                    'file' => '/project/test.php',
                    'line' => 5,
                    'duration_ms' => 50.0,
                ],
                [
                    'function' => 'func3',
                    'file' => '/project/test.php',
                    'line' => 10,
                    'duration_ms' => null,
                ],
            ],
        ];

        $result = $formatter->format($traceData);
        $decoded = json_decode($result, true);

        // Should find func2 as slowest
        $this->assertStringContainsString('func2', $decoded['summary']);
        $this->assertStringContainsString('50.00ms', $decoded['summary']);
    }

    public function test__format__handles_project_root_detection_with_no_markers(): void
    {
        $formatter = new JsonFormatter();
        $traceData = [
            'meta' => [
                'total_time_ms' => 10.0,
                'function_count' => 1,
                'timestamp' => '2025-01-01T00:00:00+00:00',
                'php_version' => '8.0.0',
            ],
            'trace' => [
                [
                    'function' => 'testFunc',
                    'file' => '/random/path/without/markers/file.php',
                    'line' => 1,
                    'duration_ms' => 10.0,
                ],
            ],
        ];

        $result = $formatter->format($traceData);
        $decoded = json_decode($result, true);

        // Should keep full path when no project root detected
        $this->assertStringContainsString('/random/path/without/markers/file.php', $decoded['trace'][0]['file']);
    }

    public function test__format__handles_files_with_children_having_empty_files(): void
    {
        $formatter = new JsonFormatter();
        $traceData = [
            'meta' => [
                'total_time_ms' => 100.0,
                'function_count' => 2,
                'timestamp' => '2025-01-01T00:00:00+00:00',
                'php_version' => '8.0.0',
            ],
            'trace' => [
                [
                    'function' => 'parent',
                    'file' => '/project/parent.php',
                    'line' => 1,
                    'duration_ms' => 100.0,
                    'children' => [
                        [
                            'function' => 'child',
                            'file' => '',
                            'line' => 1,
                            'duration_ms' => 50.0,
                        ],
                    ],
                ],
            ],
        ];

        $result = $formatter->format($traceData);
        $decoded = json_decode($result, true);

        $this->assertEquals('', $decoded['trace'][0]['children'][0]['file']);
    }

    public function test__format__detects_project_root_in_nested_children(): void
    {
        // Create a temp directory structure with a marker
        $tempDir = sys_get_temp_dir() . '/test-project-' . uniqid();
        mkdir($tempDir);
        mkdir($tempDir . '/src');
        file_put_contents($tempDir . '/composer.json', '{}');

        $formatter = new JsonFormatter();
        $traceData = [
            'meta' => [
                'total_time_ms' => 100.0,
                'function_count' => 2,
                'timestamp' => '2025-01-01T00:00:00+00:00',
                'php_version' => '8.0.0',
            ],
            'trace' => [
                [
                    'function' => 'parent',
                    'file' => '',
                    'line' => 1,
                    'duration_ms' => 100.0,
                    'children' => [
                        [
                            'function' => 'child',
                            'file' => $tempDir . '/src/file.php',
                            'line' => 1,
                            'duration_ms' => 50.0,
                        ],
                    ],
                ],
            ],
        ];

        $result = $formatter->format($traceData);
        $decoded = json_decode($result, true);

        // Should detect project root from child and apply to all paths
        $this->assertStringContainsString('src/file.php', $decoded['trace'][0]['children'][0]['file']);

        // Cleanup
        unlink($tempDir . '/composer.json');
        rmdir($tempDir . '/src');
        rmdir($tempDir);
    }
}
