<?php

namespace PhpTrace\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PhpTrace\MarkdownFormatter;

class MarkdownFormatterTest extends TestCase
{
    public function test__format__returns_markdown_string(): void
    {
        $formatter = new MarkdownFormatter();
        $traceData = $this->getMockTraceData();

        $result = $formatter->format($traceData);

        $this->assertIsString($result);
        $this->assertStringContainsString('#', $result);
    }

    public function test__format__contains_header(): void
    {
        $formatter = new MarkdownFormatter();
        $traceData = $this->getMockTraceData();

        $result = $formatter->format($traceData);

        $this->assertStringContainsString('# PHP Execution Trace', $result);
    }

    public function test__format__contains_metadata(): void
    {
        $formatter = new MarkdownFormatter();
        $traceData = $this->getMockTraceData();

        $result = $formatter->format($traceData);

        $this->assertStringContainsString('**Duration**: 100.50ms', $result);
        $this->assertStringContainsString('**Functions**: 3', $result);
        $this->assertStringContainsString('**PHP Version**: 8.0.0', $result);
        $this->assertStringContainsString('**Timestamp**:', $result);
    }

    public function test__format__contains_summary_section(): void
    {
        $formatter = new MarkdownFormatter();
        $traceData = $this->getMockTraceDataWithSlowestFunction();

        $result = $formatter->format($traceData);

        $this->assertStringContainsString('## Summary', $result);
        $this->assertStringContainsString('⚠️ **Slowest function**:', $result);
    }

    public function test__format__contains_call_tree_section(): void
    {
        $formatter = new MarkdownFormatter();
        $traceData = $this->getMockTraceData();

        $result = $formatter->format($traceData);

        $this->assertStringContainsString('## Call Tree', $result);
    }

    public function test__format__shows_slowest_function_details(): void
    {
        $formatter = new MarkdownFormatter();
        $traceData = $this->getMockTraceDataWithSlowestFunction();

        $result = $formatter->format($traceData);

        $this->assertStringContainsString('`slowFunction`', $result);
        $this->assertStringContainsString('(150.00ms)', $result);
        $this->assertStringContainsString('slow.php:5', $result);
    }

    public function test__format__handles_empty_trace(): void
    {
        $formatter = new MarkdownFormatter();
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

        $this->assertStringContainsString('# PHP Execution Trace', $result);
        $this->assertStringContainsString('**Duration**: 0.00ms', $result);
        $this->assertStringContainsString('**Functions**: 0', $result);
        $this->assertStringContainsString('## Call Tree', $result);
    }

    public function test__format__shows_hierarchical_tree_with_indentation(): void
    {
        $formatter = new MarkdownFormatter();
        $traceData = $this->getMockTraceData();

        $result = $formatter->format($traceData);

        // Root level (no indentation)
        $this->assertStringContainsString('- **main**', $result);

        // First level children (2 spaces indentation)
        $this->assertStringContainsString('  - **functionA**', $result);
        $this->assertStringContainsString('  - **functionB**', $result);
    }

    public function test__format__shows_duration_for_each_function(): void
    {
        $formatter = new MarkdownFormatter();
        $traceData = $this->getMockTraceData();

        $result = $formatter->format($traceData);

        $this->assertStringContainsString('(100.50ms)', $result);
        $this->assertStringContainsString('(50.00ms)', $result);
        $this->assertStringContainsString('(40.00ms)', $result);
    }

    public function test__format__shows_slow_indicator_for_slow_functions(): void
    {
        $formatter = new MarkdownFormatter();
        $traceData = $this->getMockTraceDataWithSlowestFunction();

        $result = $formatter->format($traceData);

        // slowFunction (150ms) should have SLOW indicator
        $this->assertMatchesRegularExpression(
            '/slowFunction.*⚠️ \*SLOW\*/s',
            $result
        );
    }

    public function test__format__does_not_show_slow_indicator_for_fast_functions(): void
    {
        $formatter = new MarkdownFormatter();
        $traceData = [
            'meta' => [
                'total_time_ms' => 50.0,
                'function_count' => 1,
                'timestamp' => '2025-01-01T00:00:00+00:00',
                'php_version' => '8.0.0',
            ],
            'trace' => [
                [
                    'function' => 'fastFunction',
                    'file' => '/project/src/fast.php',
                    'line' => 5,
                    'duration_ms' => 50.0,
                ],
            ],
        ];

        $result = $formatter->format($traceData);

        // Should NOT have SLOW indicator for <100ms functions
        $this->assertStringNotContainsString('⚠️ *SLOW*', $result);
    }

    public function test__format__shows_file_and_line_number(): void
    {
        $formatter = new MarkdownFormatter();
        $traceData = $this->getMockTraceData();

        $result = $formatter->format($traceData);

        $this->assertStringContainsString('`index.php:1`', $result);
        $this->assertStringContainsString('`functions.php:10`', $result);
        $this->assertStringContainsString('`functions.php:20`', $result);
    }

    public function test__format__handles_nested_children_with_proper_indentation(): void
    {
        $formatter = new MarkdownFormatter();
        $traceData = $this->getMockTraceDataWithNestedSlowestFunction();

        $result = $formatter->format($traceData);

        // Root level (no indent)
        $this->assertStringContainsString('- **main**', $result);

        // Level 1 (2 spaces)
        $this->assertStringContainsString('  - **midFunction**', $result);

        // Level 2 (4 spaces)
        $this->assertStringContainsString('    - **deepFunction**', $result);
    }

    public function test__format__finds_slowest_function_in_nested_children(): void
    {
        $formatter = new MarkdownFormatter();
        $traceData = $this->getMockTraceDataWithNestedSlowestFunction();

        $result = $formatter->format($traceData);

        $this->assertStringContainsString('**Slowest function**: `deepFunction` (200.00ms)', $result);
    }

    public function test__getExtension__returns_md(): void
    {
        $formatter = new MarkdownFormatter();

        $this->assertEquals('md', $formatter->getExtension());
    }

    public function test__format__handles_no_slowest_function(): void
    {
        $formatter = new MarkdownFormatter();
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
                    'duration_ms' => null,
                ],
            ],
        ];

        $result = $formatter->format($traceData);

        // Should not have Summary section if no slowest function
        $this->assertStringNotContainsString('## Summary', $result);
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
        $formatter = new MarkdownFormatter();
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

        // Should handle empty file path gracefully
        $this->assertStringContainsString('testFunc', $result);
        $this->assertStringContainsString('(10.00ms)', $result);
    }

    public function test__format__handles_trace_with_all_null_durations(): void
    {
        $formatter = new MarkdownFormatter();
        $traceData = [
            'meta' => [
                'total_time_ms' => 0.0,
                'function_count' => 2,
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
                    'duration_ms' => null,
                ],
            ],
        ];

        $result = $formatter->format($traceData);

        // Should not have Summary section when no slowest function
        $this->assertStringNotContainsString('## Summary', $result);
        $this->assertStringContainsString('## Call Tree', $result);
    }

    public function test__format__handles_deeply_nested_structure(): void
    {
        $formatter = new MarkdownFormatter();
        $traceData = [
            'meta' => [
                'total_time_ms' => 400.0,
                'function_count' => 4,
                'timestamp' => '2025-01-01T00:00:00+00:00',
                'php_version' => '8.0.0',
            ],
            'trace' => [
                [
                    'function' => 'level1',
                    'file' => '/project/test.php',
                    'line' => 1,
                    'duration_ms' => 100.0,
                    'children' => [
                        [
                            'function' => 'level2',
                            'file' => '/project/test.php',
                            'line' => 5,
                            'duration_ms' => 100.0,
                            'children' => [
                                [
                                    'function' => 'level3',
                                    'file' => '/project/test.php',
                                    'line' => 10,
                                    'duration_ms' => 100.0,
                                    'children' => [
                                        [
                                            'function' => 'level4',
                                            'file' => '/project/test.php',
                                            'line' => 15,
                                            'duration_ms' => 100.0,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $formatter->format($traceData);

        // Check proper indentation at each level
        $this->assertStringContainsString('- **level1**', $result); // 0 spaces
        $this->assertStringContainsString('  - **level2**', $result); // 2 spaces
        $this->assertStringContainsString('    - **level3**', $result); // 4 spaces
        $this->assertStringContainsString('      - **level4**', $result); // 6 spaces
    }

    public function test__format__handles_mixed_null_and_valid_durations_in_nested_tree(): void
    {
        $formatter = new MarkdownFormatter();
        $traceData = [
            'meta' => [
                'total_time_ms' => 150.0,
                'function_count' => 3,
                'timestamp' => '2025-01-01T00:00:00+00:00',
                'php_version' => '8.0.0',
            ],
            'trace' => [
                [
                    'function' => 'parent',
                    'file' => '/project/test.php',
                    'line' => 1,
                    'duration_ms' => null,
                    'children' => [
                        [
                            'function' => 'child1',
                            'file' => '/project/test.php',
                            'line' => 5,
                            'duration_ms' => 50.0,
                        ],
                        [
                            'function' => 'child2',
                            'file' => '/project/test.php',
                            'line' => 10,
                            'duration_ms' => 150.0,
                        ],
                    ],
                ],
            ],
        ];

        $result = $formatter->format($traceData);

        // Should find child2 as slowest despite parent having null duration
        $this->assertStringContainsString('**Slowest function**: `child2` (150.00ms)', $result);
        $this->assertMatchesRegularExpression('/child2.*⚠️ \*SLOW\*/s', $result);
    }
}
