<?php

namespace Deplink\Console\Commands;

use Deplink\Console\BaseCommand;
use Deplink\Environment\Filesystem;
use Deplink\Environment\System;
use DI\Container;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class RunCommand extends BaseCommand
{
    /**
     * @var System
     */
    protected $system;

    /**
     * RunCommand constructor.
     *
     * @param Filesystem $fs
     * @param Container $di
     * @param System $system
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(
        Filesystem $fs,
        Container $di,
        System $system
    ) {
        $this->system = $system;

        parent::__construct($fs, $di);
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('run')
            ->setDescription('Run project executable file')
            ->addArgument('args', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Arguments passed to the executable file')
            ->addOption('arch', null, InputOption::VALUE_REQUIRED, 'Run binary compiled for specified architecture')
            ->addOption('timeout', 't', InputOption::VALUE_REQUIRED, 'Program execution timeout (disabled by default)', null)
            ->addOption('working-dir', 'd', InputOption::VALUE_REQUIRED,
                'Use the given directory as working directory', '.');
    }

    /**
     * Executes the current command.
     *
     * If method throw an exception then command exits with code 1
     * and show error message, otherwise exits with code 0.
     *
     * @return int Exit code, if not provided then status code 0 is returned.
     * @throws \Exception
     */
    protected function exec()
    {
        $this->checkProject();

        if (!$this->isProjectPackage()) {
            $this->output->writeln("Only packages of type 'project' can be run.");
            return 1;
        }

        $path = $this->getExecutablePath();
        if (!$this->fs->existsFile($path)) {
            $arch = $this->getArch();
            $this->output->writeln("Executable file not exists for '$arch' architecture, build project first.");
            return 2;
        }

        $args = $this->input->getArgument('args');
        return $this->runExecutable($path, $args);
    }

    /**
     * @return bool
     */
    protected function isProjectPackage()
    {
        return $this->package->getType() === 'project';
    }

    /**
     * @return string
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function getExecutablePath()
    {
        $file = str_replace('/', '-', $this->package->getName());
        $ext = $this->system->getExeExt();
        return $this->fs->path('build', $this->getArch(), "$file$ext");
    }

    /**
     * @return string
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    private function getArch()
    {
        $arch = $this->input->getOption('arch');

        // If architecture wasn't provided then use first from package file.
        if (empty($arch)) {
            $arch = $this->package->getArchitectures()[0];
        }

        return $arch;
    }

    /**
     * @param string $path
     * @param string[] $args
     * @return int Exit status code.
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    private function runExecutable($path, array $args = [])
    {
        $args = implode(' ', $args);
        $timeout = $this->input->getOption('timeout');

        $process = new Process(
            "\"$path\" $args",
            $this->fs->getWorkingDir(),
            null, null, $timeout
        );

        return $process->run(function ($type, $buffer) {
            echo $buffer;
        });
    }
}
