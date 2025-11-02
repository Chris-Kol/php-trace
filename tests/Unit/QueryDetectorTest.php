<?php

namespace PhpTrace\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PhpTrace\Detector\QueryDetector;

class QueryDetectorTest extends TestCase
{
    public function test__isEnabled__returns_true_when_query_param_is_set(): void
    {
        $queryParams = ['TRACE' => '1'];

        $detector = new QueryDetector($queryParams);

        $this->assertTrue($detector->isEnabled());
    }

    public function test__isEnabled__returns_false_when_query_param_not_set(): void
    {
        $queryParams = [];

        $detector = new QueryDetector($queryParams);

        $this->assertFalse($detector->isEnabled());
    }

    public function test__isEnabled__returns_false_when_query_param_has_wrong_value(): void
    {
        $queryParams = ['TRACE' => '0'];

        $detector = new QueryDetector($queryParams);

        $this->assertFalse($detector->isEnabled());
    }

    public function test__isEnabled__returns_false_when_query_param_is_empty(): void
    {
        $queryParams = ['TRACE' => ''];

        $detector = new QueryDetector($queryParams);

        $this->assertFalse($detector->isEnabled());
    }

    public function test__constructor__accepts_custom_parameter_name(): void
    {
        $queryParams = ['debug' => '1'];

        $detector = new QueryDetector($queryParams, 'debug');

        $this->assertTrue($detector->isEnabled());
    }

    public function test__constructor__accepts_custom_expected_value(): void
    {
        $queryParams = ['TRACE' => 'yes'];

        $detector = new QueryDetector($queryParams, 'TRACE', 'yes');

        $this->assertTrue($detector->isEnabled());
    }

    public function test__isEnabled__is_case_sensitive_for_parameter_name(): void
    {
        $queryParams = ['trace' => '1'];

        $detector = new QueryDetector($queryParams, 'TRACE');

        $this->assertFalse($detector->isEnabled());
    }

    public function test__isEnabled__is_case_sensitive_for_value(): void
    {
        $queryParams = ['TRACE' => 'TRUE'];

        $detector = new QueryDetector($queryParams, 'TRACE', '1');

        $this->assertFalse($detector->isEnabled());
    }

    public function test__isEnabled__handles_numeric_strings_correctly(): void
    {
        $queryParams = ['TRACE' => '1'];

        $detector = new QueryDetector($queryParams, 'TRACE', '1');

        $this->assertTrue($detector->isEnabled());
    }

    public function test__isEnabled__ignores_other_query_parameters(): void
    {
        $queryParams = ['foo' => 'bar', 'TRACE' => '1', 'baz' => 'qux'];

        $detector = new QueryDetector($queryParams);

        $this->assertTrue($detector->isEnabled());
    }

    public function test__isEnabled__uses_strict_comparison(): void
    {
        // PHP $_GET values are always strings, so integer won't match string '1'
        $queryParams = ['TRACE' => 1];

        $detector = new QueryDetector($queryParams, 'TRACE', '1');

        $this->assertFalse($detector->isEnabled());
    }

    public function test__isEnabled__handles_string_numbers_correctly(): void
    {
        // This is the real-world scenario - $_GET['TRACE'] = '1'
        $queryParams = ['TRACE' => '1'];

        $detector = new QueryDetector($queryParams, 'TRACE', '1');

        $this->assertTrue($detector->isEnabled());
    }
}
