<?php

namespace Deplink\Console\Commands;

use Deplink\Compilers\CompilerFactory;
use Deplink\Compilers\Events\CompilerCommandEvent;
use Deplink\Console\BaseCommand;
use Deplink\Dependencies\HierarchyFinder;
use Deplink\Dependencies\InstalledPackagesManager;
use Deplink\Dependencies\ValueObjects\DependencyObject;
use Deplink\Environment\Filesystem;
use Deplink\Environment\System;
use Deplink\Events\Bus;
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
     * @var HierarchyFinder
     */
    private $hierarchyFinder;

    /**
     * @var InstalledPackagesManager
     */
    private $packagesManager;

    /**
     * @var System
     */
    private $system;

    /**
     * @var Bus
     */
    private $bus;

    /**
     * InitCommand constructor.
     *
     * @param Filesystem $fs
     * @param Container $di
     * @param CompilerFactory $factory
     * @param HierarchyFinder $hierarchyFinder
     * @param InstalledPackagesManager $packagesManager
     * @param System $system
     * @param Bus $bus
     */
    public function __construct(
        Filesystem $fs,
        Container $di,
        CompilerFactory $factory,
        HierarchyFinder $hierarchyFinder,
        InstalledPackagesManager $packagesManager,
        System $system,
        Bus $bus
    )
    {
        $this->factory = $factory;
        $this->hierarchyFinder = $hierarchyFinder;
        $this->packagesManager = $packagesManager;
        $this->system = $system;
        $this->bus = $bus;

        parent::__construct($fs, $di);
    }

    protected function configure()
    {
        $this->setName('build')
            ->setDescription('Build project and related project dependencies')
            ->addOption('no-dev', null, InputOption::VALUE_NONE,
                'Disable debug symbols and intermediate artifacts')
            ->addOption('no-progress', null, InputOption::VALUE_NONE,
                'Outputs only steps without showing dynamic progress')
            ->addOption('working-dir', 'd', InputOption::VALUE_REQUIRED,
                'Use the given directory as working directory', '.')
            ->addOption('compiler', 'c', InputOption::VALUE_REQUIRED,
                'Force to use specified compiler regardless to the deplink.json settings');

        $this->bus->listen(CompilerCommandEvent::class, function(CompilerCommandEvent $event) {
            if($this->output->isVerbose()) {
                $this->output->writeln($event->getCommand());
            }
        });
    }

    /**
     * Executes the current command.
     *
     * If method throw an exception then command exits with code 1
     * and show error message, otherwise exits with code 0.
     *
     * @return void|int Exit code, if not provided then status code 0 is returned.
     * @throws \Exception
     */
    protected function exec()
    {
        $this->checkProject();
        $this->installDependencies();
        $this->buildDependencies();
        $this->copyLibrariesToBuildDir();

        $writeMethod = $this->output->isVerbose() ? 'writeln' : 'write';
        $this->output->{$writeMethod}('Building project... ');

        $this->buildProject();

        $this->output->writeln('<info>OK</info>');
    }

    private function installDependencies()
    {
        $arguments = new ArrayInput([
            '--no-dev' => $this->input->getOption('no-dev'),
            '--no-progress' => $this->input->getOption('no-progress'),
            '--working-dir' => $this->input->getOption('working-dir'),
        ]);

        $command = $this->getApplication()->find('install');
        $command->run($arguments, $this->output);
    }

    private function buildDependencies()
    {
        $buildQueue = $this->getPackagesToBuild();

        // Get and print metadata
        $builds = $this->getRequiredBuildsCount($buildQueue);
        $upToDate = $this->getUpToDateBuildsCount($buildQueue);

        $this->output->writeln("Dependencies: <info>$builds builds</info>, <info>$upToDate up-to-date</info>");

        // Build dependencies
        $dependenciesAbsPath = $this->fs->path($this->fs->getWorkingDir(), 'deplinks');
        foreach ($buildQueue as $packageName) {
            $this->output->writeln("  - Building <info>$packageName</info>");

            $builder = $this->factory->makeBuildChain("deplinks/$packageName");
            $builder->setDependenciesDir($dependenciesAbsPath)
                ->setCompiler($this->input->getOption('compiler'))
                ->setArchitectures($this->package->getArchitectures())
                ->debugMode(!$this->input->getOption('no-dev'))
                ->build();

            $this->copyLibraryToBuildDir($packageName);
        }
    }

    private function copyLibrariesToBuildDir()
    {
        $packages = $this->packagesManager->getInstalled();
        foreach ($packages->getPackagesNames() as $name) {
            $this->copyLibraryToBuildDir($name);
        }
    }

    private function copyLibraryToBuildDir($name)
    {
        $packages = $this->packagesManager->getInstalled();
        $package = $packages->get($name)->getLocal();
        foreach ($this->package->getArchitectures() as $arch) {
            $libFile = str_replace('/', '-', $package->getName());
            $libPath = $this->fs->path('deplinks', $name, 'build', $arch, $libFile);
            $destPath = $this->fs->path('build', $arch, $libFile);

            if (in_array('static', $package->getLinkingTypes())) {
                $this->fs->copyFile(
                    $this->system->toStaticLibPath($libPath),
                    $this->system->toStaticLibPath($destPath)
                );
            }

            if (in_array('dynamic', $package->getLinkingTypes())) {
                $this->fs->copyFile(
                    $this->system->toSharedLibPath($libPath),
                    $this->system->toSharedLibPath($destPath)
                );
            }
        }
    }

    private function buildProject()
    {
        $builder = $this->factory->makeBuildChain('.');
        $builder->setDependenciesDir('deplinks')
            ->setCompiler($this->input->getOption('compiler'))
            ->debugMode(!$this->input->getOption('no-dev'))
            ->build();
    }

    /**
     * Get packages which should be build (not built previously),
     * results are sorted in the build order.
     *
     * @return string[]
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Dependencies\Excpetions\DependencyNotExistsException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Validators\Exceptions\JsonDecodeException
     * @throws \Deplink\Validators\Exceptions\ValidationException
     * @throws \InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
     */
    private function getPackagesToBuild()
    {
        $this->packagesManager->snapshot();
        $dependencies = $this->packagesManager->getInstalled();

        $this->hierarchyFinder->snapshot();
        $sortedPackages = $this->hierarchyFinder->getSorted();

        $toBuild = [];
        foreach ($sortedPackages as $packageName) {
            $package = $dependencies->get($packageName);
            if (!$this->isBuilt($package)) {
                $toBuild[] = $packageName;
            }
        }

        return $toBuild;
    }

    private function isBuilt(DependencyObject $package)
    {
        return $this->fs->existsDir($this->fs->path('deplinks', $package->getName(), 'build'));
    }

    /**
     * @param string[] $buildQueue
     * @return int
     */
    private function getRequiredBuildsCount($buildQueue)
    {
        return count($buildQueue);
    }

    /**
     * @param string[] $buildQueue
     * @return int
     */
    private function getUpToDateBuildsCount($buildQueue)
    {
        $dependencies = $this->packagesManager->getInstalled();
        $totalCount = count($dependencies->getPackagesNames());

        return $totalCount - $this->getRequiredBuildsCount($buildQueue);
    }
}
