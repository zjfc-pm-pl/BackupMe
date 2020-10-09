<?php

declare(strict_types=1);

namespace Endermanbugzjfc\BackupMe\libs\Inmarelibero\GitIgnoreChecker;

use Endermanbugzjfc\BackupMe\libs\Inmarelibero\GitIgnoreChecker\Exception\FileNotFoundException;
use Endermanbugzjfc\BackupMe\libs\Inmarelibero\GitIgnoreChecker\Exception\InvalidArgumentException;
use Endermanbugzjfc\BackupMe\libs\Inmarelibero\GitIgnoreChecker\Exception\LogicException;
use Endermanbugzjfc\BackupMe\libs\Inmarelibero\GitIgnoreChecker\Model\GitIgnore\File;
use Endermanbugzjfc\BackupMe\libs\Inmarelibero\GitIgnoreChecker\Model\RelativePath;
use Endermanbugzjfc\BackupMe\libs\Inmarelibero\GitIgnoreChecker\Model\Repository;
use Endermanbugzjfc\BackupMe\libs\Inmarelibero\GitIgnoreChecker\Utils\PathUtils;

/**
 * Class GitIgnoreChecker
 * @package Endermanbugzjfc\BackupMe\libs\Inmarelibero\GitIgnoreChecker
 */
final class GitIgnoreChecker
{
    /**
     * @var Repository
     */
    protected $repository;

    /**
     * GitIgnoreChecker constructor.
     *
     * @param string $repositoryPath absolute path representing the Repository project root
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function __construct($repositoryPath)
    {
        $this->repository = new Repository($repositoryPath);
    }

    /**
     * @return Repository
     */
    public function getRepository() : Repository
    {
        return $this->repository;
    }

    /**
     * Return true if a given path is ignored
     *
     * $path must begin with "/" but it's always relative to Repository root
     *
     * @param string $path
     * @return bool
     * @throws FileNotFoundException
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function isPathIgnored($path) : bool
    {
        $relativePathToCheck = new RelativePath($this->getRepository(), $path);

        // for each parent directory, read possible .gitignore and check if $path is ignored by it
        $directories = PathUtils::getRelativeDirectoriesToScan($relativePathToCheck);

        // @todo check order priority
        foreach ($directories as $directory) {
            $relativePathToScan = new RelativePath($this->getRepository(), $directory);

            try {
                $file = $this->searchGitIgnoreFileInRelativePath($relativePathToScan);
            } catch (FileNotFoundException $e) {
                continue;
            }

            if (!$file instanceof File) {
                continue;
            }

            if ($file->isPathIgnored($relativePathToCheck)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $relativePath
     * @return File
     * @throws FileNotFoundException
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    private function searchGitIgnoreFileInRelativePath(RelativePath $relativePath) : File
    {
        return File::buildFromRelativePathContainingGitIgnore($relativePath);
    }
}
