<?php

declare(strict_types=1);

namespace Inmarelibero\GitIgnoreChecker\Tests\Model;

use Inmarelibero\GitIgnoreChecker\Model\Repository;
use Inmarelibero\GitIgnoreChecker\Tests\AbstractTestCase;

/**
 * Class RepositoryTest
 * @package Inmarelibero\GitIgnoreChecker\Tests\Model
 *
 * @covers \Inmarelibero\GitIgnoreChecker\Model\Repository
 */
class RepositoryTest extends AbstractTestCase
{
    /**
     * @covers \Inmarelibero\GitIgnoreChecker\Model\Repository::__construct
     */
    public function testConstruct()
    {
        $repositoryPath = $this->getTestRepositoryPath();
        $repository = new Repository($this->getTestRepositoryPath());

        $this->assertEquals($repositoryPath, $repository->getPath());
    }

    /**
     * @covers \Inmarelibero\GitIgnoreChecker\Model\Repository::buildAbsolutePath()
     */
    public function testBuildAbsolutePath()
    {
        $repository = $this->getTestRepository();

        $this->assertEquals($repository->getPath().'/', $repository->buildAbsolutePath('/'));
        $this->assertEquals($repository->getPath().'/foo', $repository->buildAbsolutePath('/foo'));
        $this->assertEquals($repository->getPath().'/.README', $repository->buildAbsolutePath('/.README'));
        $this->assertEquals($repository->getPath().'/foo/bar', $repository->buildAbsolutePath('/foo/bar'));
    }
}
