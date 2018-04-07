<?php

namespace Deplink\Dependencies;

use Deplink\Dependencies\Fixtures\DummyInstallingProgress;
use Deplink\Dependencies\Fixtures\InstallingProgressForwarder;
use Deplink\Dependencies\Fixtures\UpdatingProgressForwarder;
use Deplink\Dependencies\ValueObjects\MissingDependencyObject;
use Deplink\Dependencies\ValueObjects\OutdatedDependencyObject;
use Deplink\Environment\Filesystem;
use Deplink\Locks\LockFactory;
use Deplink\Packages\PackageFactory;
use Deplink\Resolvers\DependenciesTreeResolver;

class Installer
{
    /**
     * @var MissingDependencyObject[]
     */
    private $installs;

    /**
     * @var OutdatedDependencyObject[]
     */
    private $updates;

    /**
     * @var string[]
     */
    private $removals;

    /**
     * @var DependenciesTreeResolver
     */
    private $treeResolver;

    /**
     * @var InstalledPackagesManager
     */
    private $installedPackages;

    /**
     * @var LockFactory
     */
    private $lockFactory;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var PackageFactory
     */
    private $packageFactory;

    /**
     * Installer constructor.
     *
     * @param DependenciesTreeResolver $treeResolver
     * @param InstalledPackagesManager $installedPackages
     * @param PackageFactory $packageFactory
     * @param LockFactory $lockFactory
     * @param Filesystem $fs
     */
    public function __construct(
        DependenciesTreeResolver $treeResolver,
        InstalledPackagesManager $installedPackages,
        PackageFactory $packageFactory,
        LockFactory $lockFactory,
        Filesystem $fs
    ) {
        $this->treeResolver = $treeResolver;
        $this->installedPackages = $installedPackages;
        $this->packageFactory = $packageFactory;
        $this->lockFactory = $lockFactory;
        $this->fs = $fs;
    }

    /**
     * Download missing and outdated packages.
     *
     * @param InstallingProgress|null $progress
     * @return DependenciesCollection Collection containing newly installed dependencies.
     * @throws Excpetions\DependencyNotExistsException
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Environment\Exceptions\UnknownException
     * @throws \Deplink\Repositories\Exceptions\PackageNotFoundException
     * @throws \Deplink\Repositories\Exceptions\UnknownRepositoryTypeException
     * @throws \Deplink\Resolvers\Exceptions\ConflictsBetweenDependenciesException
     * @throws \Deplink\Validators\Exceptions\JsonDecodeException
     * @throws \Deplink\Validators\Exceptions\ValidationException
     * @throws \InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
     * @throws \Deplink\Resolvers\Exceptions\DependenciesLoopException
     */
    public function install(InstallingProgress $progress = null)
    {
        // Assign dummy progress listener to avoid errors
        if (is_null($progress)) {
            $progress = new DummyInstallingProgress();
        }

        // Prepare for installation
        if (!$this->treeResolver->hasSnapshot()) {
            $this->treeResolver->snapshot();
        }

        if (!$this->installedPackages->hasSnapshot()) {
            $this->installedPackages->snapshot();
        }

        $this->classifyDependencies();
        $progress->beforeInstallation(
            count($this->installs),
            count($this->updates),
            count($this->removals)
        );

        // Installation
        $this->removePackages($progress);
        $this->updatePackages($progress);
        $this->installPackages($progress);

        $progress->afterInstallation();
        return $this->getNewlyInstalled();
    }

    /**
     * Compare resolved and installed dependencies and establish
     * which should be removed, updated or installed from scratch.
     *
     * @throws Excpetions\DependencyNotExistsException
     */
    private function classifyDependencies()
    {
        $required = $this->treeResolver->getResolvedStates()[0]->getPackages();
        $installed = $this->installedPackages->getInstalled();

        $this->installs = [];
        $this->updates = [];
        $this->removals = $this->installedPackages->getAmbiguous();

        // Check for each dependency whether if it's new, requires
        // updates or is up to date and don't require any action.
        foreach ($required->getPackagesNames() as $package) {
            $requiredVersion = $required->get($package)->getVersion();
            if ($installed->has($package)) {
                $installedVersion = $installed->get($package)->getVersion();
                if ($requiredVersion !== $installedVersion) {
                    $this->updates[] = new OutdatedDependencyObject(
                        $package, $installedVersion, $requiredVersion,
                        $required->get($package)->getRemote()
                    );
                }
            } else {
                $this->installs[] = new MissingDependencyObject(
                    $package, $requiredVersion,
                    $required->get($package)->getRemote()
                );
            }
        }

        // Sort packages before installation process.
        usort($this->installs, function (MissingDependencyObject $left, MissingDependencyObject $right) {
            return strcmp($left->getName(), $right->getName());
        });

        usort($this->updates, function (OutdatedDependencyObject $left, OutdatedDependencyObject $right) {
            return strcmp($left->getName(), $right->getName());
        });

        sort($this->removals);
    }

    /**
     * @param InstallingProgress $progress
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Environment\Exceptions\UnknownException
     */
    private function removePackages(InstallingProgress $progress)
    {
        foreach ($this->removals as $packageName) {
            $progress->removingStarted($packageName);

            $dir = $this->fs->path('deplinks', $packageName);
            $this->fs->removeDir($dir);

            $progress->removingSucceed($packageName);
        }
    }

    /**
     * @param InstallingProgress $progress
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Environment\Exceptions\UnknownException
     */
    private function updatePackages(InstallingProgress $progress)
    {
        foreach ($this->updates as $package) {
            $progress->updatingStarted(
                $package->getName(),
                $package->getSourceVersion(),
                $package->getTargetVersion()
            );

            // Remove source version.
            $dir = $this->fs->path('deplinks', $package->getName());
            $this->fs->removeDir($dir);

            // Download target version.
            $package->getRemote()->getDownloader()->to($dir)->download(
                $package->getTargetVersion(),
                new UpdatingProgressForwarder($package, $progress)
            );

            $progress->updatingSucceed(
                $package->getName(),
                $package->getSourceVersion(),
                $package->getTargetVersion()
            );
        }
    }

    /**
     * @param InstallingProgress $progress
     */
    private function installPackages(InstallingProgress $progress)
    {
        foreach ($this->installs as $package) {
            $progress->installingStarted(
                $package->getName(),
                $package->getVersion()
            );

            $dir = $this->fs->path('deplinks', $package->getName());
            $package->getRemote()->getDownloader()->to($dir)->download(
                $package->getVersion(),
                new InstallingProgressForwarder($package, $progress)
            );

            $progress->installingSucceed(
                $package->getName(),
                $package->getVersion()
            );
        }
    }

    /**
     * @return DependenciesCollection
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Validators\Exceptions\JsonDecodeException
     * @throws \Deplink\Validators\Exceptions\ValidationException
     * @throws \InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
     */
    private function getNewlyInstalled()
    {
        $result = new DependenciesCollection();

        foreach ($this->installs as $install) {
            $dir = $this->fs->path('deplinks', $install->getName());
            $localPackage = $this->packageFactory->makeFromDir($dir);
            $result->add($install->getName(), $install->getVersion(), $localPackage, $install->getRemote());
        }

        foreach ($this->updates as $update) {
            $dir = $this->fs->path('deplinks', $update->getName());
            $localPackage = $this->packageFactory->makeFromDir($dir);
            $result->add($update->getName(), $update->getTargetVersion(), $localPackage, $update->getRemote());
        }

        return $result;
    }
}
