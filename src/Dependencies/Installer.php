<?php

namespace Deplink\Dependencies;

use Deplink\Dependencies\Fixtures\DummyInstallingProgress;
use Deplink\Dependencies\Fixtures\InstallingProgressForwarder;
use Deplink\Dependencies\Fixtures\UpdatingProgressForwarder;
use Deplink\Dependencies\ValueObjects\MissingDependencyObject;
use Deplink\Dependencies\ValueObjects\OutdatedDependencyObject;
use Deplink\Environment\Filesystem;
use Deplink\Locks\LockFactory;
use Deplink\Resolvers\DependenciesTreeResolver;

class Installer
{
    /**
     * @var MissingDependencyObject[]
     */
    protected $installs;

    /**
     * @var OutdatedDependencyObject[]
     */
    protected $updates;

    /**
     * @var string[]
     */
    protected $removals;

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
     * Installer constructor.
     *
     * @param DependenciesTreeResolver $treeResolver
     * @param InstalledPackagesManager $installedPackages
     * @param LockFactory $lockFactory
     * @param Filesystem $fs
     */
    public function __construct(
        DependenciesTreeResolver $treeResolver,
        InstalledPackagesManager $installedPackages,
        LockFactory $lockFactory,
        Filesystem $fs
    ) {
        $this->treeResolver = $treeResolver;
        $this->installedPackages = $installedPackages;
        $this->lockFactory = $lockFactory;
        $this->fs = $fs;
    }

    /**
     * Download missing and outdated packages.
     *
     * @param InstallingProgress $progress
     * @throws Excpetions\DependencyNotExistsException
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Environment\Exceptions\UnknownException
     * @throws \Deplink\Locks\Exceptions\DuplicateLockEntryException
     * @throws \Deplink\Validators\Exceptions\JsonDecodeException
     * @throws \Deplink\Validators\Exceptions\ValidationException
     * @throws \InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
     */
    public function install(InstallingProgress $progress)
    {
        // Assign dummy progress listener to avoid errors
        if (!is_callable($progress)) {
            $progress = new DummyInstallingProgress();
        }

        // Prepare for installation
        $this->treeResolver->snapshot();
        $this->installedPackages->snapshot();

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
        $this->updateLockFile();

        $progress->afterInstallation();
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
                    $updates[] = new OutdatedDependencyObject(
                        $package, $installedVersion, $requiredVersion,
                        $installed->get($package)->getRemote()
                    );
                }
            } else {
                $installs[] = new MissingDependencyObject(
                    $package, $requiredVersion,
                    $installed->get($package)->getRemote()
                );
            }
        }
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
        }
    }

    /**
     * Generates new lock file. Old lock file will be overwritten if exists.
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Environment\Exceptions\UnknownException
     * @throws \Deplink\Locks\Exceptions\DuplicateLockEntryException
     * @throws \InvalidArgumentException
     */
    private function updateLockFile()
    {
        $lockFile = $this->lockFactory->makeEmpty();

        foreach ($this->installs as $install) {
            $lockFile->add($install->getName(), $install->getVersion());
        }

        foreach ($this->updates as $update) {
            $lockFile->add($update->getName(), $update->getTargetVersion());
        }

        $this->fs->writeFile(
            'deplinks/installed.lock',
            $lockFile->getJson()
        );
    }
}
