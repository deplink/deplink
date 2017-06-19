<?php

use Behat\Behat\Context\Context;
use Deplink\Environment\Filesystem;

class BaseContext implements Context
{
    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * Deplink root directory.
     *
     * @var string
     */
    const ROOT_DIR = __DIR__ . '/../../..';

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

        $fs->touchDir('temp');
        $fs->setWorkingDir('temp');
    }

    /**
     * @AfterScenario
     */
    public static function cleanup()
    {
        $fs = new Filesystem();

        $fs->setWorkingDir(self::ROOT_DIR);
        $fs->removeDir('temp');
    }
}
