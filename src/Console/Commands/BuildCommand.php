<?php

namespace Deplink\Console\Commands;

use Deplink\Compilers\CompilerFactory;
use Deplink\Console\BaseCommand;
use Deplink\Environment\Filesystem;
use DI\Container;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;

class BuildCommand extends BaseCommand
{
    /**
     * @var CompilerFactory
     */
    private $factory;

    /**
     * InitCommand constructor.
     *
     * @param Filesystem $fs
     * @param Container $di
     * @param CompilerFactory $factory
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(Filesystem $fs, Container $di, CompilerFactory $factory)
    {
        $this->factory = $factory;

        parent::__construct($fs, $di);
    }

    protected function configure()
    {
        $this->setName('build')
            ->setDescription('Build project and related project dependencies')
            ->addOption('prod', null, InputOption::VALUE_NONE,
                'Include debug symbols and intermediate artifacts')
            ->addOption('no-progress', null, InputOption::VALUE_NONE,
                'Outputs only steps without showing dynamic progress')
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
        $this->installDependencies();
        $this->buildDependencies();

        $this->output->write('Building project... ');
        $this->buildProject();
        $this->output->writeln('<info>OK</info>');
    }

    private function installDependencies()
    {
        $arguments = new ArrayInput([
            '--no-dev' => $this->input->getOption('prod'),
            '--no-progress' => $this->input->getOption('no-progress'),
            '--working-dir' => $this->input->getOption('working-dir'),
        ]);

        $command = $this->getApplication()->find('install');
        $command->run($arguments, $this->output);
    }

    private function buildDependencies()
    {
        // TODO: ...
    }

    private function buildProject()
    {
        $builder = $this->factory->makeBuildChain('.');
        $builder->setDependenciesDir('deplinks')
            ->debugMode(!$this->input->getOption('prod'))
            ->build();
    }
}
