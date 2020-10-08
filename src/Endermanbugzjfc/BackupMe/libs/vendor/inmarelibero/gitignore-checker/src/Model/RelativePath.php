<?php

namespace Inmarelibero\GitIgnoreChecker\Model;

use Inmarelibero\GitIgnoreChecker\Exception\FileNotFoundException;
use Inmarelibero\GitIgnoreChecker\Exception\InvalidArgumentException;
use Inmarelibero\GitIgnoreChecker\Exception\LogicException;
use Inmarelibero\GitIgnoreChecker\Utils\PathUtils;

/**
 * Class RelativePath
 * @package Inmarelibero\GitIgnoreChecker\Model
 */
class RelativePath
{
    /**
     * @var Repository
     */
    protected $repository;

    /**
     * A relative path in a given Repository.
     * Even if it's relative from the point of view of the filesystem, must begin with "/" because it's always relative to the Repository root
     *
     * @var string
     */
    protected $path;

    /**
     * RelativePath constructor.
     *
     * @param Repository $repository
     * @param string $path path a relative path in a given Repository (must begin with "/" because it's always relative to the Repository root)
     * @throws FileNotFoundException
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function __construct(Repository $repository, string $path)
    {
        $this->repository = $repository;
        $this->setPath($path);
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return (string) $this->path;
    }

    /**
     * Set path
     * Check if file/folder actually exists before setting it
     *
     * @param string $path
     * @return RelativePath
     * @throws InvalidArgumentException
     */
    public function setPath(string $path) : RelativePath
    {
        // check relative $path represents a valid file
        if (!PathUtils::absolutePathIsValid(
            $this->repository->buildAbsolutePath($path)
        )) {
            throw new InvalidArgumentException(
                sprintf("Unable to set path \"%s\".", $path)
            );
        };

        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * Get Repository
     *
     * @return Repository
     */
    public function getRepository() : Repository
    {
        return $this->repository;
    }

    /**
     * Get absolute path
     *
     * @return string
     */
    public function getAbsolutePath() : string
    {
        return $this->getRepository()->buildAbsolutePath($this->path);
    }

    /**
     * Return true if this RelativePath represents a folder
     *
     * @return bool
     */
    public function isFolder() : bool
    {
        return PathUtils::absolutePathIsValid($this->getAbsolutePath(), true);
    }

    /**
     * Throw an exception if this does not represent a folder
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function checkIsFolder() : bool
    {
        return PathUtils::absolutePathIsValid($this->getAbsolutePath(), true);
    }

    /**
     * Return true if this relative path contains a given path (eg. ".gitignore")
     *
     * @param string $path
     * @return bool
     * @throws LogicException
     */
    public function containsPath(string $path) : bool
    {
        $absolutePath = $this->getRepository()->buildAbsolutePath('/'.$this->getPath().'/'.$path);

        if (file_exists($absolutePath)) {
            if (!is_readable($absolutePath)) {
                throw new LogicException(
                    sprintf("Path \"%s\" has been found in folder \"%s\", but it's not readable..", $path, $this->getRepository()->getPath())
                );
            }

            return true;
        }

        return false;
    }
}
