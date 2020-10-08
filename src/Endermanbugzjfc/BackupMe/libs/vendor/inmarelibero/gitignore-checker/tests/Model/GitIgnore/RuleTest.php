<?php

declare(strict_types=1);

namespace Inmarelibero\GitIgnoreChecker\Tests\Model\GitIgnore;

use Inmarelibero\GitIgnoreChecker\Exception\InvalidArgumentException;
use Inmarelibero\GitIgnoreChecker\Model\GitIgnore\File;
use Inmarelibero\GitIgnoreChecker\Model\GitIgnore\Rule;
use Inmarelibero\GitIgnoreChecker\Model\RelativePath;
use Inmarelibero\GitIgnoreChecker\Tests\AbstractTestCase;

/**
 * Class RuleTest
 * @package Inmarelibero\GitIgnoreChecker\Tests\Model\GitIgnore
 *
 * @covers \Inmarelibero\GitIgnoreChecker\Model\GitIgnore\Rule
 */
class RuleTest extends AbstractTestCase
{
    /**
     * @covers \Inmarelibero\GitIgnoreChecker\Model\GitIgnore\Rule::__construct
     */
    public function testConstructWithComment()
    {
        foreach ([
                     '#comment',
                     '# comment',
                     ' # comment',
                 ] as $rule) {
            try {
                new Rule(
                    File::buildFromRelativePathContainingGitIgnore(new RelativePath($this->getTestRepository(), '/')),
                    $rule,
                    0
                );
                $this->fail(sprintf(
                    "Rule Object shouldn't have been created with rule = \"%s\".", $rule
                ));
            } catch (InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }
    }

    /**
     * @covers \Inmarelibero\GitIgnoreChecker\Model\GitIgnore\Rule::getRuleDecisionOnPath()
     */
    public function testGetRuleDecisionOnPath()
    {
        // test "target/": folder (due to the trailing /) recursively
        $this->doTestSingleRuleGetRuleDecisionOnPath('README/', '/README', false);
        $this->doTestSingleRuleGetRuleDecisionOnPath('foo/', '/foo', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('bar_folder/', '/foo/bar_folder', true);

        // test "target": file or folder named target recursively
        $this->doTestSingleRuleGetRuleDecisionOnPath('README', '/README', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('foo', '/foo', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('bar_folder', '/foo/bar_folder', true);

        // test "/target": file or folder named target in the top-most directory (due to the leading /)
        $this->doTestSingleRuleGetRuleDecisionOnPath('/README', '/README', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('/foo', '/foo', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('/foo', '/foo/bar_folder', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('/bar_folder', '/foo/bar_folder', false);

        // test "/target/": folder named target in the top-most directory (leading and trailing /)
        $this->doTestSingleRuleGetRuleDecisionOnPath('/README/', '/README', false);
        $this->doTestSingleRuleGetRuleDecisionOnPath('/foo/', '/foo', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('/foo/', '/foo/bar_folder', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('/bar_folder/', '/foo/bar_folder', false);

        // test "*.class": every file or folder ending with .class recursively
        $this->doTestSingleRuleGetRuleDecisionOnPath('*.md', '/README', false);
        $this->doTestSingleRuleGetRuleDecisionOnPath('*.md', '/README.md', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('*.md', '/foo/README.md', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('/*.md', '/README', false);
        $this->doTestSingleRuleGetRuleDecisionOnPath('/*.md', '/README.md', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('/*.md', '/foo/README.md', false);
        $this->doTestSingleRuleGetRuleDecisionOnPath('/*.md', '/.README', false);
        $this->doTestSingleRuleGetRuleDecisionOnPath('/*.md', '/.README.md', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('/*.md', '/foo/.README.md', false);

        // test "#comment": nothing, this is a comment (first character is a #)
        // @todo restore: throws exception on __construct
//        $this->doTestSingleFileIsPathIgnored(false, '/README', '# comment');
//        $this->doTestSingleFileIsPathIgnored(false, '/foo', '# comment');
//        $this->doTestSingleFileIsPathIgnored(false, '/foo/bar_folder', '# comment');

        // test "\#comment": every file or folder with name #comment (\ for escaping)
        $this->doTestSingleRuleGetRuleDecisionOnPath('\#README', '/#README', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('\#README', '/#README', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('\#foo', '/#foo', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('\#foo/', '/#foo', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('/foo/\#README', '/foo/#README', true);

        // test "target/logs/": every folder named logs which is a subdirectory of a folder named target
        $this->doTestSingleRuleGetRuleDecisionOnPath('foo/bar_folder/', '/README', false);
        $this->doTestSingleRuleGetRuleDecisionOnPath('foo/bar_folder/', '/foo', false);
        $this->doTestSingleRuleGetRuleDecisionOnPath('foo/bar_folder/', '/foo/bar_folder', true);

        // test "target/*/logs/": every folder named logs two levels under a folder named target (* doesn’t include /)
        $this->doTestSingleRuleGetRuleDecisionOnPath('foo/*/bar_subfolder/', '/README', false);
        $this->doTestSingleRuleGetRuleDecisionOnPath('/foo/*/bar_subfolder/', '/README', false);
        $this->doTestSingleRuleGetRuleDecisionOnPath('foo/*/bar_subfolder/', '/foo/bar_folder/bar_subfolder/', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('/foo/*/bar_subfolder/', '/foo/bar_folder/bar_subfolder/', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('foo/*/bar_subfolder/', '/foo/bar_folder/README', false);
        $this->doTestSingleRuleGetRuleDecisionOnPath('/foo/*/bar_subfolder/', '/foo/bar_folder/README', false);

        // test "target/**/logs/": every folder named logs somewhere under a folder named target (** includes /)
        $this->doTestSingleRuleGetRuleDecisionOnPath('foo/*/bar_subfolder/', '/README', false);
        $this->doTestSingleRuleGetRuleDecisionOnPath('/foo/*/bar_subfolder/', '/README', false);
        $this->doTestSingleRuleGetRuleDecisionOnPath('foo/*/bar_subfolder/', '/foo/bar_folder/bar_subfolder/', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('/foo/*/bar_subfolder/', '/foo/bar_folder/bar_subfolder/', true);
        $this->doTestSingleRuleGetRuleDecisionOnPath('foo/*/bar_subfolder/', '/foo/bar_folder/README', false);
        $this->doTestSingleRuleGetRuleDecisionOnPath('/foo/*/bar_subfolder/', '/foo/bar_folder/README', false);
    }
}
