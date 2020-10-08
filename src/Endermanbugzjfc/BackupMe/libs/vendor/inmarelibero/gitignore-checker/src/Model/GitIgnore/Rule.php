<?php

declare(strict_types=1);

namespace Inmarelibero\GitIgnoreChecker\Model\GitIgnore;

use Inmarelibero\GitIgnoreChecker\Exception\InvalidArgumentException;
use Inmarelibero\GitIgnoreChecker\Model\RelativePath;
use Inmarelibero\GitIgnoreChecker\Utils\PathUtils;

/**
 * Class Rule
 * @package Inmarelibero\GitIgnoreChecker\Model
 *
 * @see https://git-scm.com/docs/gitignore
 *
 * Represent a .gitignore rule
 */
class Rule
{
    /**
     * @var File
     */
    protected $file;

    /**
     * @var string represents a line of a .gitignre file
     */
    protected $rule;

    /**
     * @var int represents the row number in the original .gitignore file
     */
    protected $index;

    /**
     * Rule constructor.
     *
     * @param File $file
     * @param string $rule
     * @param int $index the row number in the original .gitignore file
     * @throws InvalidArgumentException
     */
    public function __construct(File $file, string $rule, int $index)
    {
        $this->gitIgnoreFile = $file;
        $this->setRule($rule);
        $this->setIndex($index);
    }

    /**
     * @param string $rule
     * @return Rule
     * @throws InvalidArgumentException
     */
    private function setRule(string $rule) : Rule
    {
        $rule = trim($rule);

        if (empty($rule)) {
            throw new InvalidArgumentException(
                sprintf("Rule must be a valid string, \"%s\" given.", $rule)
            );
        }

        // rule begins with a comment
        if (strpos($rule, '#') === 0) {
            throw new InvalidArgumentException(
                sprintf("Rule cannot be created from comment, \"%s\" given.", $rule)
            );
        }

        $this->rule = $rule;

        return $this;
    }

    /**
     * Get original rule
     *
     * @return string
     */
    public function getRule() : string
    {
        return $this->rule;
    }

    /**
     * @return int
     */
    public function getIndex() : int
    {
        return $this->index;
    }

    /**
     * @param int $index
     * @return Rule
     * @throws InvalidArgumentException
     */
    public function setIndex(int $index) : Rule
    {
        if (!(\is_int($index) && $index >= 0) ) {
            throw new InvalidArgumentException(
                sprintf("\$index must me an integer >= 0: \"%s\" given.", (string) $index)
            );
        }

        $this->index = $index;

        return $this;
    }

    /**
     * Return true if this rule is matched, but must exclude a given path to be ignored (as to say rule begins with "!", eg. "!foo")
     *
     * @return bool
     */
    public function ruleIsExcluding() : bool
    {
        return strpos($this->getRule(), "!") === 0;
    }

    /**
     * If $rule (eg. "foo") is involved in $pathRelativeToRootDir (path is matched), return:
     *  - true if $pathRelativeToRootDir must be ignored
     *  - false if $pathRelativeToRootDir must not be ignored
     *  - null if $pathRelativeToRootDir is not involved in decision
     *
     * @todo check if useful
     *
     * @param RelativePath $relativePath
     * @return bool|null
     * @see https://labs.consol.de/development/git/2017/02/22/gitignore.html
     */
    public function getRuleDecisionOnPath(RelativePath $relativePath)
    {
        if (PathUtils::ruleMatchesPath($this->getRule(), $relativePath) !== true) {
            return null;
        }

        return $this->ruleIsExcluding() !== true;
    }

    /**
     * Return true if $rule (eg. "foo") is matched for $relativePath
     *
     * @param RelativePath $relativePath
     * @return bool
     * @see https://labs.consol.de/development/git/2017/02/22/gitignore.html
     */
    public function pathIsMached(RelativePath $relativePath) : bool
    {
        return PathUtils::ruleMatchesPath($this->getRule(), $relativePath) === true;
    }
}
