<?php

namespace Deplink\Console;

use Deplink\Environment\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @param Filesystem $fs
     */
    public function __construct(Filesystem $fs)
    {
        $this->fs = $fs;

        parent::__construct();
    }

    /**
     * Setup working directory and executes the current command.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     * @return int|null null or 0 if everything went fine, or an error code
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        // Store current working directory
        // to restore it at the beginning.
        $workingDir = $this->fs->getWorkingDir();

        try {
            // Setup new working directory.
            $newWorkingDir = $this->input->getOption('working-dir');
            $this->fs->setWorkingDir($newWorkingDir);

            // Execute command and restore working directory.
            $exitCode = $this->exec() ?: 0;
            $this->fs->setWorkingDir($workingDir);

            return $exitCode;
        } catch (\Exception $e) {
            // Restore working directory and forward exception.
            $this->fs->setWorkingDir($workingDir);
            throw $e;
        }
    }

    /**
     * Executes the current command.
     *
     * If method throw an exception then command exits with code 1
     * and show error message, otherwise exits with code 0.
     *
     * @return void|int Exit code, if not provided then status code 0 is returned.
     */
    protected abstract function exec();
}
