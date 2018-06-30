<?php

return [

    /**
     * Displaying project name.
     */
    'name' => 'Deplink: Dependency Manager for C/C++',

    /**
     * CLI version using semantic versioning.
     *
     * @link http://semver.org/
     */
    'version' => 'dev-build',

    /**
     * Available commands via cli.
     *
     * @link https://github.com/symfony/console
     */
    'commands' => [
        \Deplink\Console\Commands\InitCommand::class,
        \Deplink\Console\Commands\InstallCommand::class,
        \Deplink\Console\Commands\ListCommand::class,
        //\Deplink\Console\Commands\UninstallCommand::class,
        \Deplink\Console\Commands\BuildCommand::class,
        //\Deplink\Console\Commands\RebuildCommand::class,
        //\Deplink\Console\Commands\CleanCommand::class,
        \Deplink\Console\Commands\RunCommand::class,
        //\Deplink\Console\Commands\ScriptCommand::class,
    ],
];
