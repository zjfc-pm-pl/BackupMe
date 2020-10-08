<?php

declare(strict_types=1);

namespace Inmarelibero\GitIgnoreChecker\Tests;

use Inmarelibero\GitIgnoreChecker\Model\RelativePath;
use Inmarelibero\GitIgnoreChecker\Utils\PathUtils;

/**
 * Class PathUtilsTest
 * @package Inmarelibero\GitIgnoreChecker\Tests
 *
 * Unit tests for class PathUtils.
 *
 * @covers \Inmarelibero\GitIgnoreChecker\Utils\PathUtils
 */
class PathUtilsTest extends AbstractTestCase
{
    /**
     * @covers \Inmarelibero\GitIgnoreChecker\Utils\PathUtils::getRelativeDirectoriesToScan()
     */
    public function testGetDirectoriesToScan()
    {
        // '/'
        $this->assertCount(0, PathUtils::getRelativeDirectoriesToScan(new RelativePath($this->getTestRepository(), '/')));

        // '/foo'
        $this->assertCount(1, PathUtils::getRelativeDirectoriesToScan(new RelativePath($this->getTestRepository(), '/foo')));

        // '/foo/bar'
        $this->assertCount(2, PathUtils::getRelativeDirectoriesToScan(new RelativePath($this->getTestRepository(), '/foo/bar')));
    }
    
    /**
     * @covers \Inmarelibero\GitIgnoreChecker\Utils\PathUtils::ruleMatchesPath()
     */
    public function testMatchPath()
    {
        // test "target/": folder (due to the trailing /) recursively
        $this->doTestSinglePathUtilsRuleMatchesPath('README/', '/README', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('foo/', '/foo/', true);
//        $this->doTestSinglePathUtilsRuleMatchesPath('foo/', '/foo', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('bar_folder/', '/foo/bar_folder/', true);
//        $this->doTestSinglePathUtilsRuleMatchesPath('bar_folder/', '/foo/bar_folder', false);

        // test "target": file or folder named target recursively
        $this->doTestSinglePathUtilsRuleMatchesPath('README', '/README', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('foo', '/foo', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('foo', '/foo/', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('bar_folder', '/foo/bar_folder', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('bar_folder', '/foo/bar_folder/', true);

        // test "/target": file or folder named target in the top-most directory (due to the leading /)
        $this->doTestSinglePathUtilsRuleMatchesPath('/README', '/README', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('/foo', '/foo', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('/foo', '/foo/', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('/foo', '/foo/bar_folder', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('/foo', '/foo/bar_folder/', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('/bar_folder', '/foo/bar_folder', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('/bar_folder', '/foo/bar_folder/', false);

        // test "/target/": folder named target in the top-most directory (leading and trailing /)
        $this->doTestSinglePathUtilsRuleMatchesPath('/README/', '/README', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('/foo/', '/foo/', true);
//        $this->doTestSinglePathUtilsRuleMatchesPath('/foo/', '/foo', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('/foo/', '/foo/bar_folder', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('/bar_folder/', '/foo/bar_folder', false);

        // test "*.class": every file or folder ending with .class recursively
        $this->doTestSinglePathUtilsRuleMatchesPath('/*.md', '/README', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('/*.md', '/README.md', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('/*.md', '/foo/README.md', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('*.md', '/README', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('*.md', '/README.md', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('*.md', '/foo/README.md', true);

        $this->doTestSinglePathUtilsRuleMatchesPath('/*.md', '/.README', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('/*.md', '/.README.md', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('/*.md', '/foo/.README.md', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('*.md', '/.README', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('*.md', '/.README.md', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('*.md', '/foo/.README.md', true);

        // test "#comment": nothing, this is a comment (first character is a #)
        // @todo restore: throws exception on __construct
//        $this->doTestSinglePathUtilsRuleMatchesPath(false, '/README', '# comment');
//        $this->doTestSinglePathUtilsRuleMatchesPath(false, '/foo', '# comment');
//        $this->doTestSinglePathUtilsRuleMatchesPath(false, '/foo/bar_folder', '# comment');

        // test "\#comment": every file or folder with name #comment (\ for escaping)
        $this->doTestSinglePathUtilsRuleMatchesPath('\#README', '/#README', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('\#README', '/#README', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('\#foo', '/#foo', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('\#foo/', '/#foo', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('/foo/\#README', '/foo/#README', true);

        // test "target/logs/": every folder named logs which is a subdirectory of a folder named target
        $this->doTestSinglePathUtilsRuleMatchesPath('foo/bar_folder/', '/README', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('foo/bar_folder/', '/foo', false);

//        $this->doTestSinglePathUtilsRuleMatchesPath('foo/bar_folder/', '/foo/bar_folder', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('foo/bar_folder/', '/foo/bar_folder/', true);

        $this->doTestSinglePathUtilsRuleMatchesPath('foo/bar_folder/', '/bar_folder/foo/', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('foo/bar_folder/', '/bar_folder/foo', false);

        $this->doTestSinglePathUtilsRuleMatchesPath('bar_folder/baz_folder/', '/foo/bar_folder/baz_folder', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('bar_folder/baz_folder/', '/foo/bar_folder/baz_folder/', true);

        $this->doTestSinglePathUtilsRuleMatchesPath('/foo/bar_folder/', '/README', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('/foo/bar_folder/', '/foo', false);

//        $this->doTestSinglePathUtilsRuleMatchesPath('/foo/bar_folder/', '/foo/bar_folder', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('/foo/bar_folder/', '/foo/bar_folder/', true);

        $this->doTestSinglePathUtilsRuleMatchesPath('/foo/bar_folder/', '/bar_folder/foo/', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('/foo/bar_folder/', '/bar_folder/foo', false);

        $this->doTestSinglePathUtilsRuleMatchesPath('/bar_folder/baz_folder/', '/foo/bar_folder/baz_folder', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('/bar_folder/baz_folder/', '/foo/bar_folder/baz_folder/', false);

        // test "target/*/logs/": every folder named logs two levels under a folder named target (* doesn’t include /)
        $this->doTestSinglePathUtilsRuleMatchesPath('foo/*/bar_subfolder/', '/README', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('/foo/*/bar_subfolder/', '/README', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('foo/*/bar_subfolder/', '/foo/bar_folder/bar_subfolder/', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('/foo/*/bar_subfolder/', '/foo/bar_folder/bar_subfolder/', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('foo/*/bar_subfolder/', '/foo/bar_folder/README', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('/foo/*/bar_subfolder/', '/foo/bar_folder/README', false);

        // test "target/**/logs/": every folder named logs somewhere under a folder named target (** includes /)
        $this->doTestSinglePathUtilsRuleMatchesPath('foo/**/bar_subfolder/', '/README', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('/foo/**/bar_subfolder/', '/README', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('foo/**/bar_subfolder/', '/foo/bar_folder/bar_subfolder/', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('/foo/**/bar_subfolder/', '/foo/bar_folder/bar_subfolder/', true);
        $this->doTestSinglePathUtilsRuleMatchesPath('foo/**/bar_subfolder/', '/foo/bar_folder/README', false);
        $this->doTestSinglePathUtilsRuleMatchesPath('/foo/**/bar_subfolder/', '/foo/bar_folder/README', false);
    }
}
