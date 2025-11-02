<?php

namespace PhpTrace\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PhpTrace\Detector\EnvDetector;

class EnvDetectorTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up environment variables
        putenv('TRACE');
        putenv('CUSTOM_VAR');
    }

    public function test__isEnabled__returns_true_when_env_var_is_set(): void
    {
        putenv('TRACE=1');

        $detector = new EnvDetector();

        $this->assertTrue($detector->isEnabled());
    }

    public function test__isEnabled__returns_false_when_env_var_not_set(): void
    {
        putenv('TRACE');

        $detector = new EnvDetector();

        $this->assertFalse($detector->isEnabled());
    }

    public function test__isEnabled__returns_false_when_env_var_has_wrong_value(): void
    {
        putenv('TRACE=0');

        $detector = new EnvDetector();

        $this->assertFalse($detector->isEnabled());
    }

    public function test__isEnabled__returns_false_when_env_var_is_empty(): void
    {
        putenv('TRACE=');

        $detector = new EnvDetector();

        $this->assertFalse($detector->isEnabled());
    }

    public function test__constructor__accepts_custom_variable_name(): void
    {
        putenv('CUSTOM_VAR=1');

        $detector = new EnvDetector('CUSTOM_VAR');

        $this->assertTrue($detector->isEnabled());
    }

    public function test__constructor__accepts_custom_expected_value(): void
    {
        putenv('TRACE=enabled');

        $detector = new EnvDetector('TRACE', 'enabled');

        $this->assertTrue($detector->isEnabled());
    }

    public function test__isEnabled__is_case_sensitive_for_value(): void
    {
        putenv('TRACE=TRUE');

        $detector = new EnvDetector('TRACE', '1');

        $this->assertFalse($detector->isEnabled());
    }

    public function test__isEnabled__handles_numeric_strings_correctly(): void
    {
        putenv('TRACE=1');

        $detector = new EnvDetector('TRACE', '1');

        $this->assertTrue($detector->isEnabled());
    }

    public function test__isEnabled__returns_false_for_boolean_true(): void
    {
        // PHP's putenv doesn't support boolean, but this tests string comparison
        putenv('TRACE=true');

        $detector = new EnvDetector('TRACE', '1');

        $this->assertFalse($detector->isEnabled());
    }
}
