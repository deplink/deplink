<?php

namespace Deplink\Console\Commands;

use Deplink\Console\BaseCommand;
use Deplink\Console\Commands\Output\InstallationProgressFormater;
use Deplink\Dependencies\DependenciesCollection;
use Deplink\Dependencies\InstalledPackagesManager;
use Deplink\Dependencies\Installer;
use Deplink\Environment\Filesystem;
use Deplink\Locks\LockFactory;
use Deplink\Resolvers\DependenciesTreeResolver;
use Deplink\Resolvers\DependenciesTreeState;
use DI\Container;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class InstallCommand extends BaseCommand
{
    /**
     * Resolved dependencies tree states.
     *
     * @var DependenciesTreeState
     */
    protected $states;

    /**
     * Packages installed in the deplinks directory
     * (after installation process).
     *
     * @var DependenciesCollection
     */
    protected $installed;

    /**
     * @var Container
     */
    private $di;

    /**
     * @var LockFactory
     */
    private $lockFactory;

    /**
     * InstallCommand constructor.
     *
     * @param Filesystem $fs
     * @param LockFactory $lockFactory
     * @param Container $di
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(Filesystem $fs, LockFactory $lockFactory, Container $di)
    {
        $this->di = $di;
        $this->lockFactory = $lockFactory;

        parent::__construct($fs, $di);
    }

    protected function configure()
    {
        $this->setName('install')
            ->setDescription('Install dependencies')
            ->setHelp('Install dependencies listed in the deplink.lock or in the deplink.json if lock file is missing or outdated.')
            ->addArgument('package', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Name of the packages to install')
            ->addOption('no-progress', null, InputOption::VALUE_NONE,
                'Outputs only steps without showing the progress')
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
        $this->checkProject();
        $this->updateProjectPackage();
        $this->retrieveInstalledDependencies();
        $this->resolveDependenciesTree();
        $this->installDependencies();
        $this->writeLockFile();
        $this->writeAutoloadHeader();
    }

    private function updateProjectPackage()
    {
        // TODO: Add package to deplink.json (potential issues: already exists, deplink.json not found)
    }

    private function retrieveInstalledDependencies()
    {
        $this->output->write('Retrieving installed dependencies... ');
        if (!$this->fs->existsDir('deplinks')) {
            $this->output->writeln('<comment>Skipped</comment>');
            return;
        }

        $manager = $this->di->get(InstalledPackagesManager::class);
        $manager->snapshot();

        // Cleanup directory with installed dependencies
        foreach ($manager->getAmbiguous() as $packageName) {
            $path = $this->fs->path('deplinks', $packageName);
            $this->fs->removeDir($path);
        }

        $this->output->writeln('<info>OK</info>');
    }

    private function resolveDependenciesTree()
    {
        $this->output->write('Resolving dependencies tree... ');

        $manager = $this->di->get(DependenciesTreeResolver::class);
        $manager->snapshot();

        $this->states = $manager->getResolvedStates();
        $this->output->writeln('<info>OK</info>');
    }

    private function installDependencies()
    {
        $installer = $this->di->get(Installer::class);
        $trackProgress = !$this->input->hasOption('no-progress');

        $this->installed = $installer->install(
            new InstallationProgressFormater($this->output, $trackProgress)
        );
    }

    private function writeLockFile()
    {
        $this->output->write('Writing lock file... ');

        $lockFile = $this->lockFactory->makeEmpty();
        $packagesNames = $this->installed->getPackagesNames();

        foreach ($packagesNames as $packageName) {
            $lockFile->add(
                $this->installed->get($packageName)->getName(),
                $this->installed->get($packageName)->getVersion()
            );
        }

        $this->fs->writeFile(
            'deplinks/installed.lock',
            $lockFile->getJson()
        );

        $this->output->writeln('<info>OK</info>');
    }

    private function writeAutoloadHeader()
    {
        $this->output->write('Generating autoload header... ');

        // Get list of header files from each of the packages.
        $includes = [];
        foreach ($this->installed->getPackagesNames() as $packageName) {
            $package = $this->installed->get($packageName);

            foreach ($package->getLocal()->getIncludeDirs() as $searchDir) {
                $dir = $this->fs->path('deplinks', $package->getName(), $searchDir);
                $files = $this->fs->listFiles($dir, '.*\.(h|hpp)');
                $files = array_map(function ($item) {
                    // Remove deplinks prepend from paths.
                    return substr($item, strlen('deplinks/'));
                }, $files);

                $includes = array_merge($includes, $files);
            }

            $includes = array_unique($includes);
        }

        // Write autoload.h header file.
        $headerText = '#pragma once' . PHP_EOL . PHP_EOL;
        foreach ($includes as $includeFile) {
            $headerText .= '#include "' . $includeFile . '"' . PHP_EOL;
        }

        $this->fs->writeFile('deplinks/autoload.h', $headerText);

        $this->output->writeln('<info>OK</info>');
    }
}
