<?php

declare(strict_types=1);

namespace Inmarelibero\GitIgnoreChecker\Tests;

use Inmarelibero\GitIgnoreChecker\Utils\StringUtils;

/**
 * Class StringUtilsTest
 * @package Inmarelibero\GitIgnoreChecker\Tests
 *
 * Unit tests for class StringUtils
 *
 * @todo add missing tests for remaining methods, reoder
 */
class StringUtilsTest extends AbstractTestCase
{
    /**
     * @covers \Inmarelibero\GitIgnoreChecker\Utils\StringUtils::stringHasInitialSlash()
     */
    public function testStringHasInitialSlash()
    {
        $this->assertTrue(StringUtils::stringHasInitialSlash('/'));
        $this->assertTrue(StringUtils::stringHasInitialSlash('/foo'));
        $this->assertTrue(StringUtils::stringHasInitialSlash('/foo/bar'));
        $this->assertTrue(StringUtils::stringHasInitialSlash('/foo/.gitignore'));
        $this->assertTrue(StringUtils::stringHasInitialSlash('/.gitignore'));

        $this->assertFalse(StringUtils::stringHasInitialSlash(''));
        $this->assertFalse(StringUtils::stringHasInitialSlash('foo'));
        $this->assertFalse(StringUtils::stringHasInitialSlash('foo/bar'));
        $this->assertFalse(StringUtils::stringHasInitialSlash('foo/.gitignore'));
        $this->assertFalse(StringUtils::stringHasInitialSlash('.gitignore'));
    }

    /**
     * @covers \Inmarelibero\GitIgnoreChecker\Utils\StringUtils::explodeStringWithDirectorySeparatorAsDelimiter()
     */
    public function testExplodeStringWithDirectorySeparatorAsDelimiter()
    {
        $this->assertEquals(
            [],
            StringUtils::explodeStringWithDirectorySeparatorAsDelimiter('')
        );

        $this->assertEquals(
            [],
            StringUtils::explodeStringWithDirectorySeparatorAsDelimiter('/')
        );

        $this->assertEquals(
            ['foo'],
            StringUtils::explodeStringWithDirectorySeparatorAsDelimiter('/foo')
        );

        $this->assertEquals(
            ['foo', 'bar'],
            StringUtils::explodeStringWithDirectorySeparatorAsDelimiter('/foo/bar')
        );

        $this->assertEquals(
            ['foo', 'bar'],
            StringUtils::explodeStringWithDirectorySeparatorAsDelimiter('foo/bar')
        );

        $this->assertEquals(
            ['foo', 'bar'],
            StringUtils::explodeStringWithDirectorySeparatorAsDelimiter('/foo/bar')
        );
    }
}
