<?php

declare(strict_types=1);

namespace Inmarelibero\GitIgnoreChecker\Utils;

use Inmarelibero\GitIgnoreChecker\Exception\InvalidArgumentException;

/**
 * Class StringUtils
 * @package Inmarelibero\GitIgnoreChecker\Utils
 */
class StringUtils
{
    /**
     * Return $string with the trailing "/" (if not present)
     *
     * @param $input
     * @return string
     */
    public static function addTrailingSlashIfMissing(string $input) : string
    {
        if (self::stringHasTrailingSlash($input)) {
            return $input;
        }

        return sprintf("%s/", $input);
    }

    /**
     * @param array $tokens
     * @param string $regex
     * @return bool
     */
    private static function atLeastOneTokenMatchesRegex($tokens, $regex) : bool 
    {
        return self::searchFirstTokenMatchesRegex($tokens, $regex) !== null;
    }

    /**
     * Build an executable Regex for a given .gitignore rule
     *
     * @param $regex
     * @return string
     */
    public static function buildRegexForRule($rule) :string
    {
        // remove initial and trailing slash
        $ruleFormatted = $rule;
        $ruleFormatted = str_replace(".", "\.", $ruleFormatted);;
        $ruleFormatted = str_replace("*", ".*", $ruleFormatted);;
        $ruleFormatted = StringUtils::removeInitialSlash($ruleFormatted);
        $ruleFormatted = StringUtils::removeTrailingSlash($ruleFormatted);

        $regex = sprintf("^%s$", $ruleFormatted);

        return $regex;
    }

    /**
     * Explode a string using "/" as delimiter
     *
     * @param string $input
     * @return array
     */
    public static function explodeStringWithDirectorySeparatorAsDelimiter(string $input) : array
    {
        $tokens = explode("/", $input);
        $tokens = array_filter($tokens);

        return array_values($tokens);
    }

    /**
     * @param string $regex
     * @param string $input
     * @return bool
     */
    public static function regexIsMatched(string $regex, string $input) : bool
    {
        // always escape # (ignore already escaped "\#")
        $regex = preg_replace("@(?<!\\\)#@", '\#', $regex);

        // build the executable regex
        $regex = sprintf('#%s#i', $regex);

        return preg_match($regex, $input) === 1;
    }

    /**
     * Given a $path, return true if $regex matches it's last token
     *
     * @param string $regex
     * @param string $path
     * @return bool
     */
    private static function regexMatchesLastPathToken(string $regex, string $path) : bool
    {
        $pathTokens = StringUtils::explodeStringWithDirectorySeparatorAsDelimiter($path);

        $index = self::searchFirstTokenMatchesRegex($pathTokens, $regex);

        // matched token is the last one
        if ($index === count($pathTokens) - 1) {
            if (!StringUtils::stringHasTrailingSlash($path)) {
                return false;
            }
        }

        return $index !== null;
    }

    /**
     * @param string $regex
     * @param string $path
     * @return bool
     */
    private static function regexMatchesFirstPathToken(string $regex, string $path) : bool
    {
        $pathTokens = StringUtils::explodeStringWithDirectorySeparatorAsDelimiter($path);

        return self::searchFirstTokenMatchesRegex($pathTokens, $regex) === 0;
    }

    /**
     * Return $string without the initial "/" (if present)
     *
     * @param $input
     * @return bool|string
     */
    public static function removeInitialSlash(string $input) : string
    {
        if (self::stringHasInitialSlash($input)) {
            $input = substr($input, 1);
        }

        return $input;
    }

    /**
     * Return $string without the triling "/" (if present)
     *
     * @param $input
     * @return bool|string
     */
    public static function removeTrailingSlash(string $input) : string
    {
        if (self::stringHasTrailingSlash($input)) {
            $input = substr($input, 0, -1);
        }

        return $input;
    }

    /**
     * "Complex" rule means that the rule contains "subfolders" logic
     *
     * @param string $rule
     * @param string $path
     * @return bool
     */
    public static function ruleComplexMatchesPath(string $rule, string $path) : bool
    {
        $ruleTokens = self::explodeStringWithDirectorySeparatorAsDelimiter($rule);
        $pathTokens = StringUtils::explodeStringWithDirectorySeparatorAsDelimiter($path);

        foreach ($ruleTokens as $ruleToken) {
            $regex = self::buildRegexForRule($ruleToken);

            if (!self::atLeastOneTokenMatchesRegex($pathTokens, $regex)) {
                return false;
            }
        }

        /*
         * check that rule tokens order is respected
         */
        $lastIndex = null;

        foreach ($ruleTokens as $ruleToken) {
            $regex = self::buildRegexForRule($ruleToken);
            $index = self::searchFirstTokenMatchesRegex($pathTokens, $regex);

            if ($lastIndex === null) {
                $lastIndex = $index;
                continue;
            }

            if (in_array($ruleToken, ['*', '**'])) {
                continue;
            }

            if ($index <= $lastIndex) {
                return false;
            }
        }

        /*
         *
         */
        if (StringUtils::stringHasInitialSlash($rule) && !self::regexMatchesFirstPathToken( self::buildRegexForRule($ruleTokens[0]), $path)) {
            return false;
        }

        if (StringUtils::stringHasTrailingSlash($rule) && !self::regexMatchesLastPathToken(self::buildRegexForRule($ruleTokens[count($ruleTokens)-1]), $path)) {
            return false;
        }

        return true;
    }

    /**
     * Return true if $rule contains a logic that implies subfolders
     *
     * @param string $rule
     * @return bool
     */
    public static function ruleIsOnSubfolders(string $rule)
    {
        return self::regexIsMatched('.+\/.+', $rule);
    }

    /**
     * "Simple" rule means that the rule does not contains "subfolders" logic
     *
     * @param string $rule
     * @param string $path
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function ruleSimpleMatchesPath(string $rule, string $path) : bool
    {
        if (self::ruleIsOnSubfolders($rule)) {
            throw new InvalidArgumentException("Rule \"$rule\" cannot be used here.");
        }

        //
        $regex = self::buildRegexForRule($rule);
        $pathTokens = StringUtils::explodeStringWithDirectorySeparatorAsDelimiter($path);

        /*
         * check that at least one token of $path matches the "simple" rule
         */
        $atLeastOnetokenIsMatched = self::atLeastOneTokenMatchesRegex($pathTokens, $regex);

        if ($atLeastOnetokenIsMatched !== true) {
            return false;
        }

        if (StringUtils::stringHasInitialSlash($rule) && !self::regexMatchesFirstPathToken($regex, $path)) {
            return false;
        }

        if (StringUtils::stringHasTrailingSlash($rule) && !self::regexMatchesLastPathToken($regex, $path)) {
            return false;
        }

        return true;
    }

    /**
     * Return the index of the found token matching $regex
     *
     * @param array $tokens
     * @param string $regex
     * @return int|null
     */
    private static function searchFirstTokenMatchesRegex(array $tokens, string $regex)
    {
        foreach ($tokens as $k => $token) {
            if (self::regexIsMatched($regex, $token)) {
                return $k;
            }
        }

        return null;
    }

    /**
     * Return true if $string has an initial "/"
     *
     * @param $input
     * @return bool
     */
    public static function stringHasInitialSlash(string $input) : bool
    {
        return self::regexIsMatched('^\/.*', $input);
    }

    /**
     * Return true if $string has a trailing "/"
     *
     * @param $input
     * @return bool
     */
    public static function stringHasTrailingSlash(string $input) : bool
    {
        return self::regexIsMatched('.*\/$', $input);
    }
}
