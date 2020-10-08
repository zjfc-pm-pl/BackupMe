<?php

declare(strict_types=1);

namespace Inmarelibero\GitIgnoreChecker\Tests;

use Inmarelibero\GitIgnoreChecker\GitIgnoreChecker;

/**
 * Class GitIgnoreCheckerTest
 * @package Inmarelibero\GitIgnoreChecker\Tests
 *
 * @covers \Inmarelibero\GitIgnoreChecker\GitIgnoreChecker
 */
class GitIgnoreCheckerTest extends AbstractTestCase
{
    /**
     * @covers \Inmarelibero\GitIgnoreChecker\GitIgnoreChecker::isPathIgnored()
     *
     * @todo add assertions
     */
    public function testIsPathIgnored()
    {
        $gitIgnoreChecker = new GitIgnoreChecker(
            $this->getTestRepositoryPath()
        );

        $this->assertFalse(
            $gitIgnoreChecker->isPathIgnored('/foo/bar')
        );

        $this->assertTrue(
            $gitIgnoreChecker->isPathIgnored('/foo/ignore_me')
        );

        $this->assertTrue(
            $gitIgnoreChecker->isPathIgnored('/ignored_foo')
        );
    }
}
