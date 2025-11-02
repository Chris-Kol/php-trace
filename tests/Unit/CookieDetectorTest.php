<?php

namespace PhpTrace\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PhpTrace\Detector\CookieDetector;

class CookieDetectorTest extends TestCase
{
    public function test__isEnabled__returns_true_when_cookie_is_set(): void
    {
        $cookies = ['TRACE' => '1'];

        $detector = new CookieDetector($cookies);

        $this->assertTrue($detector->isEnabled());
    }

    public function test__isEnabled__returns_false_when_cookie_not_set(): void
    {
        $cookies = [];

        $detector = new CookieDetector($cookies);

        $this->assertFalse($detector->isEnabled());
    }

    public function test__isEnabled__returns_false_when_cookie_has_wrong_value(): void
    {
        $cookies = ['TRACE' => '0'];

        $detector = new CookieDetector($cookies);

        $this->assertFalse($detector->isEnabled());
    }

    public function test__isEnabled__returns_false_when_cookie_is_empty(): void
    {
        $cookies = ['TRACE' => ''];

        $detector = new CookieDetector($cookies);

        $this->assertFalse($detector->isEnabled());
    }

    public function test__constructor__accepts_custom_cookie_name(): void
    {
        $cookies = ['debug_trace' => '1'];

        $detector = new CookieDetector($cookies, 'debug_trace');

        $this->assertTrue($detector->isEnabled());
    }

    public function test__constructor__accepts_custom_expected_value(): void
    {
        $cookies = ['TRACE' => 'on'];

        $detector = new CookieDetector($cookies, 'TRACE', 'on');

        $this->assertTrue($detector->isEnabled());
    }

    public function test__isEnabled__is_case_sensitive_for_cookie_name(): void
    {
        $cookies = ['trace' => '1'];

        $detector = new CookieDetector($cookies, 'TRACE');

        $this->assertFalse($detector->isEnabled());
    }

    public function test__isEnabled__is_case_sensitive_for_value(): void
    {
        $cookies = ['TRACE' => 'TRUE'];

        $detector = new CookieDetector($cookies, 'TRACE', '1');

        $this->assertFalse($detector->isEnabled());
    }

    public function test__isEnabled__handles_numeric_strings_correctly(): void
    {
        $cookies = ['TRACE' => '1'];

        $detector = new CookieDetector($cookies, 'TRACE', '1');

        $this->assertTrue($detector->isEnabled());
    }

    public function test__isEnabled__ignores_other_cookies(): void
    {
        $cookies = ['session' => 'abc123', 'TRACE' => '1', 'user' => 'john'];

        $detector = new CookieDetector($cookies);

        $this->assertTrue($detector->isEnabled());
    }

    public function test__isEnabled__uses_strict_comparison(): void
    {
        // PHP $_COOKIE values are always strings, so integer won't match string '1'
        $cookies = ['TRACE' => 1];

        $detector = new CookieDetector($cookies, 'TRACE', '1');

        $this->assertFalse($detector->isEnabled());
    }

    public function test__isEnabled__handles_string_numbers_correctly(): void
    {
        // This is the real-world scenario - $_COOKIE['TRACE'] = '1'
        $cookies = ['TRACE' => '1'];

        $detector = new CookieDetector($cookies, 'TRACE', '1');

        $this->assertTrue($detector->isEnabled());
    }
}
