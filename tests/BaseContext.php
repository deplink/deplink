<?php

namespace Deplink\Tests;

use Behat\Behat\Context\Context;
use Deplink\Environment\Filesystem;

class BaseContext implements Context
{
    /**
     * @var Filesystem
     */
    protected $fs;

    public function __construct()
    {
        $this->fs = new Filesystem();
    }

    /**
     * @BeforeScenario
     */
    public static function prepare()
    {
        $fs = new Filesystem();
        $rootDir = __DIR__ . '/..';
        $tempDir = __DIR__ . '/../temp';

        chdir($rootDir);
        $fs->removeDir($tempDir);
        $fs->touchDir($tempDir);
        chdir($tempDir);
    }
}
