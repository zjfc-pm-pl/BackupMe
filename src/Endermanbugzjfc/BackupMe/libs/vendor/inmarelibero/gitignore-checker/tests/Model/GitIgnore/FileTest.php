<?php

declare(strict_types=1);

namespace Inmarelibero\GitIgnoreChecker\Tests\Model\GitIgnore;

use Inmarelibero\GitIgnoreChecker\Model\GitIgnore\File;
use Inmarelibero\GitIgnoreChecker\Model\RelativePath;
use Inmarelibero\GitIgnoreChecker\Tests\AbstractTestCase;

/**
 * Class GitIgnoreFileTest
 * @package Inmarelibero\GitIgnoreChecker\Tests\Model
 *
 * @covers \Inmarelibero\GitIgnoreChecker\Model\GitIgnore\File
 */
class FileTest extends AbstractTestCase
{
    /**
     * @covers \Inmarelibero\GitIgnoreChecker\Model\GitIgnore\File::buildFromRelativePathContainingGitIgnore()
     */
    public function testBuildFromRelativePathContainingGitIgnore()
    {
        $repository = $this->getTestRepository();

        $file = File::buildFromRelativePathContainingGitIgnore(new RelativePath($repository, '/'));
        $this->assertInstanceOf(File::class, $file);

        $this->assertCount(2, $file->getRules());
    }

    /**
     * @covers \Inmarelibero\GitIgnoreChecker\Model\GitIgnore\File::buildFromContent()
     */
    public function testBuildFromContent()
    {
        $repository = $this->getTestRepository();

        $file = File::buildFromContent(new RelativePath($repository, '/'), <<<EOF
foo
bar

baz
EOF
);
        $this->assertInstanceOf(File::class, $file);

        $this->assertCount(3, $file->getRules());
    }

    /**
     * @covers \Inmarelibero\GitIgnoreChecker\Model\GitIgnore\File::isPathIgnored()
     */
    public function testIsPathIgnored()
    {
        // test "target/": folder (due to the trailing /) recursively
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
README/
!README/
EOF
            , '/README', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!README/
README/
EOF
            , '/README', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
foo/
!foo/
EOF
            , '/foo', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!foo/
foo/
EOF
            , '/foo', true);


        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
bar_folder/
!bar_folder/
EOF
            , '/foo/bar_folder', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!bar_folder/
bar_folder/
EOF
            , '/foo/bar_folder', true);

        // test "target": file or folder named target recursively
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
README
!README
EOF
            , '/README', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!README
README
EOF
            , '/README', true);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
foo
!foo
EOF
            , '/foo', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!foo
foo
EOF
            , '/foo', true);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
bar_folder
!bar_folder
EOF
            , '/foo/bar_folder', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!bar_folder
bar_folder
EOF
            , '/foo/bar_folder', true);

        // test "/target": file or folder named target in the top-most directory (due to the leading /)
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
/README
!/README
EOF
            , '/README', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!/README
/README
EOF
            , '/README', true);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
/foo
!/foo
EOF
            , '/foo', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!/foo
/foo
EOF
            , '/foo', true);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
/foo
!/foo
EOF
            , '/foo/bar_folder', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!/foo
/foo
EOF
            , '/foo/bar_folder', true);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!/bar_folder
/bar_folder
EOF
            , '/foo/bar_folder', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
/bar_folder
!/bar_folder
EOF
            , '/foo/bar_folder', false);

        // test "/target/": folder named target in the top-most directory (leading and trailing /)
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!/README/
/README/
EOF
            , '/README', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
/README/
!/README/
EOF
            , '/README', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!/foo/
/foo/
EOF
, '/foo', true);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
/foo/
!/foo/
EOF
, '/foo', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!/foo/
/foo/
EOF
, '/foo/bar_folder', true);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
/foo/
!/foo/
EOF
, '/foo/bar_folder', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!/bar_folder/
/bar_folder/
EOF
            , '/foo/bar_folder', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
/bar_folder/
!/bar_folder/
EOF
            , '/foo/bar_folder', false);

        // test "*.class": every file or folder ending with .class recursively
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!*.md
*.md
EOF
            , '/README', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
*.md
!*.md
EOF
            , '/README', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!*.md
*.md
EOF
            , '/README.md', true);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
*.md
!*.md
EOF
            , '/README.md', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!*.md
*.md
EOF
            , '/foo/README.md', true);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
*.md
!*.md
EOF
            , '/foo/README.md', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!/*.md
/*.md
EOF
            , '/README', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
/*.md
!/*.md
EOF
            , '/README', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!/*.md
/*.md
EOF
            , '/README.md', true);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
/*.md
!/*.md
EOF
            , '/README.md', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!/*.md
/*.md
EOF
            , '/foo/README.md', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
/*.md
!/*.md
EOF
            , '/foo/README.md', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!/*.md
/*.md
EOF
            , '/.README', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
/*.md
!/*.md
EOF
            , '/.README', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!/*.md
/*.md
EOF
            , '/.README.md', true);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
/*.md
!/*.md
EOF
            , '/.README.md', false);
        $this->doTestSingleFileIsPathIgnored('/*.md', '/foo/.README.md', false);

        // test "#comment": nothing, this is a comment (first character is a #)
        // @todo restore: throws exception on __construct
//        $this->doTestSingleFileIsPathIgnored(false, '/README', '# comment');
//        $this->doTestSingleFileIsPathIgnored(false, '/foo', '# comment');
//        $this->doTestSingleFileIsPathIgnored(false, '/foo/bar_folder', '# comment');

        // test "\#comment": every file or folder with name #comment (\ for escaping)
//        $this->doTestSingleFileIsPathIgnored(true, '/#README', '\#README');
//        $this->doTestSingleFileIsPathIgnored(false, '/foo', '\# comment');
//        $this->doTestSingleFileIsPathIgnored(false, '/foo/bar_folder', '\# comment');

        // test "target/logs/": every folder named logs which is a subdirectory of a folder named target
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!foo/bar_folder/
foo/bar_folder/
EOF
            , '/README', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
foo/bar_folder/
!foo/bar_folder/
EOF
            , '/README', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!foo/bar_folder/
foo/bar_folder/
EOF
            , '/foo', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
foo/bar_folder/
!foo/bar_folder/
EOF
            , '/foo', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!foo/bar_folder/
foo/bar_folder/
EOF
            , '/foo/bar_folder', true);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
foo/bar_folder/
!foo/bar_folder/
EOF
            , '/foo/bar_folder', false);

        // test "target/*/logs/": every folder named logs two levels under a folder named target (* doesn’t include /)
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!foo/*/bar_subfolder/
foo/*/bar_subfolder/
EOF
            , '/README', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
foo/*/bar_subfolder/
!foo/*/bar_subfolder/
EOF
            , '/README', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!/foo/*/bar_subfolder/
/foo/*/bar_subfolder/
EOF
            , '/README', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
/foo/*/bar_subfolder/
!/foo/*/bar_subfolder/
EOF
            , '/README', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!foo/*/bar_subfolder/
foo/*/bar_subfolder/
EOF
            , '/foo/bar_folder/bar_subfolder/', true);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
foo/*/bar_subfolder/
!foo/*/bar_subfolder/
EOF
            , '/foo/bar_folder/bar_subfolder/', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!/foo/*/bar_subfolder/
/foo/*/bar_subfolder/
EOF
            , '/foo/bar_folder/bar_subfolder/', true);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
/foo/*/bar_subfolder/
!/foo/*/bar_subfolder/
EOF
            , '/foo/bar_folder/bar_subfolder/', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!foo/*/bar_subfolder/
foo/*/bar_subfolder/
EOF
            , '/foo/bar_folder/README', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
foo/*/bar_subfolder/
!foo/*/bar_subfolder/
EOF
            , '/foo/bar_folder/README', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!/foo/*/bar_subfolder/
/foo/*/bar_subfolder/
EOF
            , '/foo/bar_folder/README', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
/foo/*/bar_subfolder/
!/foo/*/bar_subfolder/
EOF
            , '/foo/bar_folder/README', false);

        // test "target/**/logs/": every folder named logs somewhere under a folder named target (** includes /)
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!foo/*/bar_subfolder/
foo/*/bar_subfolder/
EOF
            , '/README', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
foo/*/bar_subfolder/
!foo/*/bar_subfolder/
EOF
            , '/README', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!/foo/*/bar_subfolder/
/foo/*/bar_subfolder/
EOF
            , '/README', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
/foo/*/bar_subfolder/
!/foo/*/bar_subfolder/
EOF
            , '/README', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!foo/*/bar_subfolder/
foo/*/bar_subfolder/
EOF
            , '/foo/bar_folder/bar_subfolder/', true);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
foo/*/bar_subfolder/
!foo/*/bar_subfolder/
EOF
            , '/foo/bar_folder/bar_subfolder/', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!/foo/*/bar_subfolder/
/foo/*/bar_subfolder/
EOF
            , '/foo/bar_folder/bar_subfolder/', true);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
/foo/*/bar_subfolder/
!/foo/*/bar_subfolder/
EOF
            , '/foo/bar_folder/bar_subfolder/', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!foo/*/bar_subfolder/
foo/*/bar_subfolder/
EOF
            , '/foo/bar_folder/README', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
foo/*/bar_subfolder/
!foo/*/bar_subfolder/
EOF
            , '/foo/bar_folder/README', false);

        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
!/foo/*/bar_subfolder/
/foo/*/bar_subfolder/
EOF
            , '/foo/bar_folder/README', false);
        $this->doTestSingleFileIsPathIgnored(
            <<<EOF
/foo/*/bar_subfolder/
!/foo/*/bar_subfolder/
EOF
            , '/foo/bar_folder/README', false);
    }
}
