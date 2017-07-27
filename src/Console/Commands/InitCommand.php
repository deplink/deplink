<?php

namespace Deplink\Console\Commands;

use Deplink\Console\BaseCommand;
use Deplink\Environment\Filesystem;
use Deplink\Packages\PackageFactory;
use Deplink\Packages\ValueObjects\PackageNameObject;
use DI\Container;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class InitCommand extends BaseCommand
{
    /**
     * @var PackageFactory
     */
    private $factory;

    /**
     * InitCommand constructor.
     *
     * @param Filesystem $fs
     * @param Container $di
     * @param PackageFactory $factory
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(Filesystem $fs, Container $di, PackageFactory $factory)
    {
        $this->factory = $factory;

        parent::__construct($fs, $di);
    }

    protected function configure()
    {
        $this->setName('init')
            ->setDescription('Create basic deplink.json file')
            ->addArgument('name', InputArgument::OPTIONAL, 'Name of the package')
            ->addOption('working-dir', 'd', InputOption::VALUE_REQUIRED,
                'Use the given directory as working directory', '.');
    }

    /**
     * Executes the current command.
     *
     * If method throw an exception then command exits with code 1
     * and show error message, otherwise exits with code 0.
     *
     * @return int|void Exit code, if not provided then status code 0 is returned.
     * @throws \Exception
     */
    protected function exec()
    {
        $this->output->write('Creating deplink.json file... ');

        $packageName = $this->resolveName();
        $this->createPackageFile($packageName);

        $this->output->writeln('<info>OK</info>');
    }

    /**
     * Get package name passed in the argument
     * or retrieve it from the current working directory.
     *
     * @return string
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    private function resolveName()
    {
        $name = $this->input->getArgument('name');
        if (empty($name)) {
            $workingDir = $this->fs->getWorkingDir();
            $parts = explode('/', $workingDir);

            $package = array_pop($parts) ?: 'package';
            $org = array_pop($parts) ?: 'org';

            $name = "$org/$package";
        }

        return $name;
    }

    /**
     * @param string $packageName
     * @throws \Exception
     */
    private function createPackageFile($packageName)
    {
        if ($this->fs->existsFile('deplink.json')) {
            throw new \Exception("Package already exists in given directory");
        }

        if (!empty($this->fs->listFiles('.')) || !empty($this->fs->listDirs('.'))) {
            throw new \Exception("Cannot initialize package in non-empty directory");
        }

        $package = $this->factory->makeEmpty()
            ->setName($packageName)
            ->setType('project');

        $this->fs->writeFile('deplink.json', $package->getJson());
    }
}
