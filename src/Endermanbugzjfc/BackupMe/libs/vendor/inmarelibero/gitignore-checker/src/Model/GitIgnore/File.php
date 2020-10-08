<?php

declare(strict_types=1);

namespace Inmarelibero\GitIgnoreChecker\Model\GitIgnore;

use Inmarelibero\GitIgnoreChecker\Exception\FileNotFoundException;
use Inmarelibero\GitIgnoreChecker\Exception\InvalidArgumentException;
use Inmarelibero\GitIgnoreChecker\Exception\LogicException;
use Inmarelibero\GitIgnoreChecker\Exception\RuleNotFoundException;
use Inmarelibero\GitIgnoreChecker\Model\RelativePath;

/**
 * Class GitIgnoreFile
 * @package Inmarelibero\GitIgnoreChecker\Model
 *
 * @see https://git-scm.com/docs/gitignore
 *
 * Represent a .gitignore file
 */
class File
{
    /**
     * @var RelativePath
     */
    protected $relativePath;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var Rule[]
     */
    protected $rules = [];

    /**
     * Disable construtor
     */
    private function __construct() {}

    /**
     * GitIgnoreFile constructor.
     *
     * @param RelativePath $relativePathContainingGitIgnore path containing a .gitignore file, eg. "/", "/foo/", ""/foo/bar/""
     * @return File
     * @throws FileNotFoundException
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public static function buildFromRelativePathContainingGitIgnore(RelativePath $relativePathContainingGitIgnore) : File
    {
        $obj = new File();

        $obj->setRelativePath($relativePathContainingGitIgnore);
        $gitIgnorePath = $obj->getAbsolutePathForGitIgnore();

        $obj->parseContentByReadingFile($gitIgnorePath);

        return $obj;
    }

    /**
     * GitIgnoreFile constructor.
     *
     * @param RelativePath $relativePathContainingGitIgnore path containing a .gitignore file, eg. "/", "/foo/", ""/foo/bar/""
     * @param string $content
     * @return File
     * @throws FileNotFoundException
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public static function buildFromContent(RelativePath $relativePathContainingGitIgnore, string $content) : File
    {
        $obj = new File();

        $obj->setRelativePath($relativePathContainingGitIgnore);
        $obj->parseContent($content);

        return $obj;
    }

    /**
     * Set relative path containing the .gitignore file
     *
     * @param RelativePath $relativePathContainingGitIgnore
     * @return File
     * @throws InvalidArgumentException
     * @throws FileNotFoundException
     */
    private function setRelativePath(RelativePath $relativePathContainingGitIgnore) : File
    {
        if (!$relativePathContainingGitIgnore->isFolder()) {
            throw new \InvalidArgumentException();
        }

        /*
         * check that $path represents the path containing the .gitignore file, not the path containing the ".gitignore" string
         */
        if (preg_match("#\.gitignore$#", $relativePathContainingGitIgnore->getPath())) {
            throw new InvalidArgumentException(
                sprintf("The path must not end with .gitignore: \"%s\" given.", $relativePathContainingGitIgnore->getPath())
            );
        }

        /*
         * check that a file ".gitignore" is actually found in $relativePathContainingGitIgnore
         */
        if (!$relativePathContainingGitIgnore->containsPath('/.gitignore')) {
            throw new FileNotFoundException(
                sprintf("The path \"%s\" does not contain a .gitignore file.", $relativePathContainingGitIgnore->getPath())
            );
        }

        $this->relativePath = $relativePathContainingGitIgnore;

        return $this;
    }

    /**
     * Return the absolute path for the .gitignore file
     *
     * @param RelativePath $relativePathContainingGitIgnore
     * @return string
     */
    private function getAbsolutePathForGitIgnore() : string
    {
        return sprintf("%s/.gitignore", $this->relativePath->getAbsolutePath());
    }

    /**
     * Get RelativePath
     *
     * @return RelativePath
     */
    public function getRelativePath() : RelativePath
    {
        return $this->relativePath;
    }

    /**
     * Parse the content of a given .gitignore absolute path
     *
     * @param string $absolutePath absolute path of the .gitignore file, eg. "/var/www/foo/.gitignore"
     * @return Rule[]
     * @throws InvalidArgumentException
     */
    private function parseContentByReadingFile(string $absolutePath) : array
    {
        return $this->parseContent(file_get_contents($absolutePath));
    }

    /**
     * Parse content
     *
     * @param string $content
     * @return Rule[]
     * @throws InvalidArgumentException
     */
    private function parseContent(string $content) : array
    {
        $this->content = $content;

        $lines = explode(PHP_EOL, $content);

        array_walk($lines, function(&$item) {
            $item = trim($item);
        });

        $lines = array_filter($lines);

        return $this->parseGitIgnoreLines($lines);
    }

    /**
     * Parse every line of a .gitignore
     *
     * @param array $lines
     * @return Rule[]
     * @throws InvalidArgumentException
     */
    private function parseGitIgnoreLines(array $lines) : array
    {
        $lines = array_values($lines);

        foreach ($lines as $k => $line) {
            $this->rules[] = new Rule($this, $line, $k);
        }

        return $this->getRules();
    }

    /**
     * Return content
     *
     * @return string
     */
    public function getContent() : string
    {
        return $this->content;
    }

    /**
     * Return the GitIgnore parsed rules
     *
     * @return Rule[]
     */
    public function getRules() : array
    {
        return $this->rules;
    }

    /**
     * Return true if a given $path is ignored by file .gitignore
     *
     * @param RelativePath $relativepath
     * @return bool
     */
    public function isPathIgnored(RelativePath $relativepath) : bool
    {
        try {
            $lastRuleInvolvedInPathNotExcluding = $this->getLastRuleInvolvedInPath($relativepath, true);
        } catch (RuleNotFoundException $e) {
            return false;
        }

        try {
            $lastRuleInvolvedInPathExcluding = $this->getLastRuleInvolvedInPath($relativepath, false, true);
        } catch (RuleNotFoundException $e) {
            $lastRuleInvolvedInPathExcluding = null;
        }

        if ($lastRuleInvolvedInPathExcluding instanceof Rule) {
            if ($lastRuleInvolvedInPathExcluding->getIndex() > $lastRuleInvolvedInPathNotExcluding->getIndex()) {
                return false;
            }
        }

        return $lastRuleInvolvedInPathNotExcluding->getRuleDecisionOnPath($relativepath);
    }

    /**
     * Get the last Rule that matches a given path
     * Rule will be applied and the decision to ignore or not the path will be taken
     *
     * @todo refactor $onlyNotExcluding and $onlyExcluding: improve names? use OptionsResolver?
     *
     * @param $relativePath
     * @param bool $onlyNotExcluding
     * @param bool $onlyExcluding
     * @return Rule
     */
    private function getLastRuleInvolvedInPath(RelativePath $relativePath, $onlyNotExcluding = false, $onlyExcluding = false) : Rule
    {
        /** @var Rule[] $reversedRules */
        $reversedRules = array_reverse($this->getRules());

        foreach ($reversedRules as $rule) {
            if ($onlyNotExcluding === true && $rule->ruleIsExcluding()) {
                continue;
            }

            if ($onlyExcluding === true && $rule->ruleIsExcluding() !== true) {
                continue;
            }

            if ($rule->pathIsMached($relativePath)) {
                return $rule;
            }
        }

        throw new RuleNotFoundException();
    }
}
