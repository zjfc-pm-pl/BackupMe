<?php

namespace Inmarelibero\GitIgnoreChecker\Model;

use Inmarelibero\GitIgnoreChecker\Exception\InvalidArgumentException;
use Inmarelibero\GitIgnoreChecker\Utils\PathUtils;
use Inmarelibero\GitIgnoreChecker\Utils\StringUtils;

/**
 * Class Repository
 * @package Inmarelibero\GitIgnoreChecker\Model
 */
class Repository
{
    /**
     * @var string project root dir
     */
    protected $path;

    /**
     * Repository constructor.
     *
     * @param string $path absolute path of the repository in the filesystem
     * @throws InvalidArgumentException
     */
    public function __construct(string $path)
    {
        $this->setPath($path);
    }

    /**
     * Set the path of the Git repository (project root)
     *
     * @param string $path
     * @return Repository
     * @throws InvalidArgumentException
     */
    private function setPath(string $path) : Repository
    {
        if (!PathUtils::absolutePathIsValid($path, true)) {
            throw new InvalidArgumentException(
                sprintf("Unable to set path \"%s\".", $path)
            );
        }

        $path = realpath($path);

        if (!$path) {
            throw new InvalidArgumentException(
                sprintf("Unable to set path \"%s\".", $path)
            );
        }

        $this->path = $path;

        return $this;
    }

    /**
     * Get the absolute path of the Repository project root
     *
     * @return string
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * Build the absolute path for a given relative path
     *
     * @param string $relativePath
     * @return string
     * @throws InvalidArgumentException
     */
    public function buildAbsolutePath(string $relativePath) : string
    {
        if (!StringUtils::stringHasInitialSlash($relativePath)) {
            throw new InvalidArgumentException(sprintf("Path to check must begin with \"\/\", \"%s\" given.", $relativePath));
        }

        $output = sprintf("%s/%s", $this->getPath(), $relativePath);
        $output = PathUtils::removeDoubleSlashes($output);

        return $output;
    }
}
