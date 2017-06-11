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

        // Move to the root directory.
        chdir(__DIR__ . '/..');

        // Recreate temp directory and set as working directory.
        $fs->removeDir('temp');
        $fs->touchDir('temp');
        chdir('temp');
    }
}
