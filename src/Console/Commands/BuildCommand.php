<?php

namespace Deplink\Console\Commands;

use Deplink\Console\BaseCommand;
use Symfony\Component\Console\Input\InputOption;

class BuildCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('build')
            ->setDescription('Build project and related project dependencies')
            ->addOption('working-dir', 'd', InputOption::VALUE_REQUIRED,
                'Use the given directory as working directory', '.');
    }

    /**
     * Executes the current command.
     *
     * If method throw an exception then command exits with code 1
     * and show error message, otherwise exits with code 0.
     *
     * @return void|int Exit code, if not provided then status code 0 is returned.
     */
    protected function exec()
    {
        // Build dependencies...

        $this->output->write('Building project... ');

        // Build project...

        $this->output->writeln('<info>OK</info>');
    }
}
