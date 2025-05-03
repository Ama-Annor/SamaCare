<?php
// Include PHPUnit framework
require_once 'vendor/autoload.php';
require_once 'assets/includes/functions.php'; 

use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    public function testSanitizeInput()
    {
        $this->assertEquals('Hello World', sanitizeInput(' Hello World '));
        $this->assertEquals('Hello &quot;World&quot;', sanitizeInput('Hello "World"'));
        $this->assertEquals('&lt;script&gt;alert(1)&lt;/script&gt;', sanitizeInput('<script>alert(1)</script>'));
    }

    public function testIsValidDate()
    {
        $this->assertTrue(isValidDate('2023-01-01'));
        $this->assertTrue(isValidDate('2024-02-29')); // Leap year
        $this->assertFalse(isValidDate('2023-02-31')); // Invalid day
        $this->assertFalse(isValidDate('2023/01/01')); // Wrong format
    }

    public function testIsValidTime()
    {
        $this->assertEquals(true, (bool)isValidTime('12:30'));
        $this->assertEquals(true, (bool)isValidTime('12:30:45'));
        $this->assertEquals(false, (bool)isValidTime('24:00'));
        $this->assertEquals(false, (bool)isValidTime('12:60'));
    }

    public function testFormatDate()
    {
        $this->assertEquals('Jan 15, 2023', formatDate('2023-01-15'));
        $this->assertEquals('2023/01/15', formatDate('2023-01-15', 'Y/m/d'));
    }

    public function testFormatTime()
    {
        $this->assertEquals('01:30 PM', formatTime('13:30'));
        $this->assertEquals('13:30', formatTime('13:30', false));
    }

    public function testGenerateRandomString()
    {
        $randomString = generateRandomString(15);
        $this->assertEquals(15, strlen($randomString));
    }

    public function testStringContains()
    {
        $this->assertTrue(stringContains('Hello World', 'World'));
        $this->assertFalse(stringContains('Hello World', 'Goodbye'));
    }

    public function testGetPaginationInfo()
    {
        $result = getPaginationInfo(100, 10, 5);
        $this->assertEquals(100, $result['total_items']);
        $this->assertEquals(10, $result['items_per_page']);
        $this->assertEquals(5, $result['current_page']);
        $this->assertEquals(10, $result['total_pages']);
        $this->assertEquals(40, $result['offset']);
    }
}