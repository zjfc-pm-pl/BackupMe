<?php

declare(strict_types=1);

namespace Inmarelibero\GitIgnoreChecker\Tests;

use Inmarelibero\GitIgnoreChecker\GitIgnoreChecker;
use Inmarelibero\GitIgnoreChecker\Model\GitIgnore\File;
use Inmarelibero\GitIgnoreChecker\Model\GitIgnore\Rule;
use Inmarelibero\GitIgnoreChecker\Model\RelativePath;
use Inmarelibero\GitIgnoreChecker\Model\Repository;
use Inmarelibero\GitIgnoreChecker\Utils\PathUtils;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractTestCase
 * @package Inmarelibero\GitIgnoreChecker\Tests
 */
class AbstractTestCase extends TestCase
{
    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @return string
     */
    protected function getTestRepositoryPath()
    {
        return realpath(__DIR__.'/../test_repository');
    }

    /**
     * @return Repository
     */
    protected function getTestRepository()
    {
        return new Repository(
            realpath($this->getTestRepositoryPath())
        );
    }

    /**
     * @param string $rule
     * @param string $relativePath
     * @param bool $expectedMatch
     */
    protected function doTestSinglePathUtilsRuleMatchesPath(string $rule, string $relativePath, bool $expectedMatch) : void
    {
        $relativePath = new RelativePath($this->getTestRepository(), $relativePath);

        $this->assertEquals($expectedMatch, PathUtils::ruleMatchesPath($rule, $relativePath), $this->getErrorMessageForDoTestSinglePathUtilsRuleMatchesPath($expectedMatch, $rule, $relativePath));

        // automatically test $rule adding an initial "!": $relative must be matched anyway
        if (strpos($rule, "!") !== 0) {
            $ruleWithInitialExclamationMark = '!'.$rule;

            $this->assertEquals($expectedMatch, PathUtils::ruleMatchesPath($ruleWithInitialExclamationMark, $relativePath), $this->getErrorMessageForDoTestSinglePathUtilsRuleMatchesPath($expectedMatch, $ruleWithInitialExclamationMark, $relativePath));
        }
    }

    /**
     * @param $expectedMatch
     * @param string $rule
     * @param $path
     * @return string
     */
    protected function getErrorMessageForDoTestSinglePathUtilsRuleMatchesPath($expectedMatch, $rule, $path)
    {
        return sprintf(
            "Path \"%s\" %s have been matched against rule \"%s\".",
            $path,
            ($expectedMatch === true) ? 'should' : 'shouldn\'t',
            $rule
        );
    }

    /**
     * @param string $rule
     * @param string $relativePath
     * @param bool $expectedMatch
     */
    protected function doTestSingleRuleGetRuleDecisionOnPath(string $rule, string $relativePath, bool $expectedMatch) : void
    {
        if (!is_bool($expectedMatch)) {
            throw new \InvalidArgumentException("ExpectedMatch must be a boolean.");
        }

        $ruleObj = new Rule(
            File::buildFromRelativePathContainingGitIgnore(new RelativePath($this->getTestRepository(), '/')),
            $rule,
            0
        );

        $relativePath = new RelativePath($this->getTestRepository(), $relativePath);

        $this->assertEquals(
            $expectedMatch,
            $ruleObj->getRuleDecisionOnPath($relativePath),
            $this->getErrorMessageForDoTestSingleRuleGetRuleDecisionOnPath($expectedMatch, $ruleObj, $relativePath)
        );

        // automatically test $rule adding an initial "!": must always not ignore the file
        if (strpos($rule, "!") !== 0) {
            $ruleWithInitialExclamationMark = '!'.$rule;

            $this->doTestSingleRuleGetRuleDecisionOnPath($ruleWithInitialExclamationMark, $relativePath->getPath(), false);
        }
    }

    /**
     * @param bool $expectedMatch
     * @param Rule $rule
     * @param RelativePath $relativePath
     * @return string
     */
    protected function getErrorMessageForDoTestSingleRuleGetRuleDecisionOnPath(bool $expectedMatch, Rule $rule, RelativePath $relativePath)
    {
        return sprintf(
            "Path \"%s\" %s have been matched against rule \"%s\".",
            $relativePath->getPath(),
            ($expectedMatch === true) ? 'should' : 'shouldn\'t',
            $rule->getRule()
        );
    }

    /**
     * @param string $content
     * @param string $relativePath
     * @param bool$expectedMatch
     */
    protected function doTestSingleFileIsPathIgnored(string $content, string $relativePath, bool $expectedMatch) : void
    {
        if (!is_bool($expectedMatch)) {
            throw new \InvalidArgumentException("ExpectedMatch must be a boolean.");
        }

        $file = File::buildFromContent(new RelativePath($this->getTestRepository(), '/'), $content);

        $relativePath = new RelativePath($this->getTestRepository(), $relativePath);

        $this->assertEquals(
            $expectedMatch,
            $file->isPathIgnored($relativePath),
            $this->getErrorMessageForDoTestSingleFileIsPathIgnored($expectedMatch, $file, $relativePath)
        );
    }

    /**
     * @param $expectedMatch
     * @param File $file
     * @param $path
     * @return string
     */
    protected function getErrorMessageForDoTestSingleFileIsPathIgnored(bool $expectedMatch, File $file, RelativePath $relativePath)
    {
        return sprintf(<<<EOF
Path "%s" %s have been matched against .gitignore file with content:
%s
EOF

            ,
            $relativePath->getPath(),
            ($expectedMatch === true) ? 'should' : 'shouldn\'t',
            $file->getContent()
        );
    }
}