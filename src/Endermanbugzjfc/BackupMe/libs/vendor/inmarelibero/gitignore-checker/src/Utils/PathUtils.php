<?php

declare(strict_types=1);

namespace Inmarelibero\GitIgnoreChecker\Utils;

use Inmarelibero\GitIgnoreChecker\Exception\InvalidArgumentException;
use Inmarelibero\GitIgnoreChecker\Exception\LogicException;
use Inmarelibero\GitIgnoreChecker\Model\RelativePath;

/**
 * Class PathUtils
 * @package Inmarelibero\GitIgnoreChecker\Utils
 */
class PathUtils
{
    /**
     * Return true if $rule matches $relativePath
     *
     * Note: if rule begins with "!", this returns true because path is matched
     *
     * @param string $rule
     * @param string $relativePath
     * @return bool
     */
    public static function ruleMatchesPath($rule, RelativePath $relativePath) : bool
    {
        $pathToMatch = $relativePath->getPath();

        if ($relativePath->isFolder()) {
            $pathToMatch = StringUtils::addTrailingSlashIfMissing($pathToMatch);
        }

        // remove initial "!" if present, as if rule begins with "!", this returns true because path is matched
        if (strpos($rule, "!") === 0) {
            $rule = substr($rule, 1);
        }

        if (StringUtils::ruleIsOnSubfolders($rule)) {
            return StringUtils::ruleComplexMatchesPath($rule, $pathToMatch) === true;
        }

        if (StringUtils::ruleSimpleMatchesPath($rule, $pathToMatch)) {
            return true;
        }

        return false;
    }

    /**
     * Array containing all the paths to be scanned, contained in the $repositoryBaseDir (the first one coincides with it)
     *
     * @param RelativePath $relativePath
     * @return string[]
     */
    public static function getRelativeDirectoriesToScan(RelativePath $relativePath) : array
    {
        $output = [];

        $tokens = StringUtils::explodeStringWithDirectorySeparatorAsDelimiter($relativePath->getPath());
        $tokensCount = \count($tokens);

        for ($i = 0; $i < $tokensCount; $i++) {
            $output[] = "/" . implode('/', array_slice($tokens, 0, $i));
        }

        return $output;
    }

    /**
     * Return true if $path represents a readable and existing file/folder
     *
     * @param string $path
     * @param bool $checkIsDir
     * @param bool $checkIsFile
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function absolutePathIsValid(string $path, $checkIsDir = false, $checkIsFile = false) : bool
    {
        if (!StringUtils::stringHasInitialSlash($path)) {
            throw new InvalidArgumentException(sprintf("Argument must be an absolute path: \"%s\" given.", $path));
        }

        if (!file_exists($path)) {
            throw new InvalidArgumentException(sprintf("Path \"%s\" does not exist.", $path));
        }

        if ($checkIsDir === true) {
            if (!is_dir($path)) {
                return false;
            }
        }

        if ($checkIsFile === true) {
            if (!is_file($path)) {
                return false;
            }
        }

        if (!is_readable($path)) {
            return false;
        }

        if (realpath($path) === false) {
            return false;
        }

        return true;
    }

    /**
     * Replaces "//" with "/"
     *
     * @param string $input
     * @return string
     */
    public static function removeDoubleSlashes(string $input) : string
    {
        return preg_replace("#\/\/#", "/", $input);  //@todo handle "#" in $input
    }
}
