<?php

require __DIR__ . '/vendor/autoload.php';

use Inmarelibero\GitIgnoreChecker\Exception\GitIgnoreCherkerException;
use Inmarelibero\GitIgnoreChecker\GitIgnoreChecker;

try {
    $gic = new GitIgnoreChecker(__DIR__ . '/tests/test_repository');
} catch (GitIgnoreCherkerException $e) {
    echo sprintf("ERROR: %s", $e->getMessage());
    echo PHP_EOL;
}

$paths  = [
    "/foo/bar_folder/README",
    "/foo",
    "/ignored_foo/",
    "/README",
    "/.README",
    "/README.md",
];

echo sprintf("Considering repository \"%s\".", $gic->getRepository()->getPath());
echo PHP_EOL;

foreach ($paths as $path) {
    try {
        $isIgnored = $gic->isPathIgnored($path);
    } catch (GitIgnoreCherkerException $e) {    // @todo catch better exception
        echo $e->getMessage();
        echo PHP_EOL;
        continue;
    }

    echo sprintf("Path \"%s\" %s ignored.", $path, ($isIgnored === true) ? 'is' : 'is not');
    echo PHP_EOL;
}
