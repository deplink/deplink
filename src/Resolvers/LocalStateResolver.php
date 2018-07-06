<?php

namespace Deplink\Resolvers;

use Deplink\Dependencies\DependenciesCollection;
use Deplink\Dependencies\InstalledPackagesManager;
use Deplink\Locks\LockFactory;
use Deplink\Packages\PackageFactory;
use Deplink\Packages\ValueObjects\DependencyObject;
use Deplink\Repositories\RepositoryFactory;
use Deplink\Resolvers\Exceptions\ConflictsBetweenDependenciesException;
use Deplink\Versions\VersionComparator;

/**
 * Class is responsible to retrieve local dependencies state
 * (throws an exception in case of incompatibility).
 */
class LocalStateResolver
{
    private $state = null;

    /**
     * @var InstalledPackagesManager
     */
    private $packagesManager;

    /**
     * @var ResolverFactory
     */
    private $factory;

    /**
     * @var PackageFactory
     */
    private $packageFactory;

    /**
     * @var VersionComparator
     */
    private $comparator;

    /**
     * @var LockFactory
     */
    private $lockFactory;

    /**
     * @var RepositoryFactory
     */
    private $repositoryFactory;

    /**
     * @param InstalledPackagesManager $packagesManager
     * @param ResolverFactory $factory
     * @param PackageFactory $packageFactory
     * @param VersionComparator $comparator
     * @param LockFactory $lockFactory
     * @param RepositoryFactory $repositoryFactory
     */
    public function __construct(
        InstalledPackagesManager $packagesManager,
        ResolverFactory $factory,
        PackageFactory $packageFactory,
        VersionComparator $comparator,
        LockFactory $lockFactory,
        RepositoryFactory $repositoryFactory
    ) {
        $this->packagesManager = $packagesManager;
        $this->factory = $factory;
        $this->packageFactory = $packageFactory;
        $this->comparator = $comparator;
        $this->lockFactory = $lockFactory;
        $this->repositoryFactory = $repositoryFactory;
    }

    /**
     * @return DependenciesTreeState
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Repositories\Exceptions\PackageNotFoundException
     * @throws \Deplink\Repositories\Exceptions\UnknownRepositoryTypeException
     * @throws \Deplink\Validators\Exceptions\JsonDecodeException
     * @throws \Deplink\Validators\Exceptions\ValidationException
     * @throws \InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
     */
    private function stateFromLockFile()
    {
        $state = $this->factory->makeDependenciesTreeState();
        $project = $this->packageFactory->makeFromDir('.');
        $repositories = $this->repositoryFactory->makeCollection($project->getRepositories());

        $lock = $this->lockFactory->makeFromFileOrEmpty('deplink.lock');
        foreach ($lock->packages() as $name => $version) {
            $remote = $repositories->find($name);
            $state->setPackage($name, $version, $remote);
            $state->addConstraint($name, $version);
        }

        return $state;
    }

    /**
     * @param DependenciesTreeState $state
     * @param bool $includeDev
     * @return DependenciesTreeState
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Validators\Exceptions\JsonDecodeException
     * @throws \Deplink\Validators\Exceptions\ValidationException
     * @throws \InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
     */
    private function addProjectConstraints(DependenciesTreeState $state, $includeDev = true)
    {
        $project = $this->packageFactory->makeFromDir('.');

        // Get all dependencies (including dev) from the project deplink.json file,
        // this dependencies will be used to make an initial state to check tree correctness.
        /** @var DependencyObject[] $dependencies */
        $dependencies = array_merge(
            $project->getDependencies(),
            ($includeDev ? $project->getDevDependencies() : [])
        );

        return $this->registerConstraints($dependencies, $state);
    }

    /**
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Validators\Exceptions\JsonDecodeException
     * @throws \Deplink\Validators\Exceptions\ValidationException
     * @throws \InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
     */
    private function hasInstalledPackages()
    {
        // Retrieve information about installed packages.
        if (!$this->packagesManager->hasSnapshot()) {
            $this->packagesManager->snapshot();
        }

        return !empty($this->packagesManager->getAmbiguous())
            || !empty($this->packagesManager->getInstalled());
    }

    /**
     * @param DependenciesTreeState $state
     * @return DependenciesTreeState
     */
    private function addDependenciesConstraints(DependenciesTreeState $state)
    {
        // Get constraints from installed dependencies.
        $installed = $this->packagesManager->getInstalled();
        foreach ($installed as $package) {
            $dependencies = $package->getLocal()->getDependencies();
            $this->registerConstraints($dependencies, $state);
        }

        return $state;
    }

    /**
     * @param bool $includeDev
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Repositories\Exceptions\PackageNotFoundException
     * @throws \Deplink\Repositories\Exceptions\UnknownRepositoryTypeException
     * @throws \Deplink\Validators\Exceptions\JsonDecodeException
     * @throws \Deplink\Validators\Exceptions\ValidationException
     * @throws \InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
     */
    public function snapshot($includeDev = true)
    {
        $state = $this->stateFromLockFile();
        $state = $this->addProjectConstraints($state, $includeDev);
        if ($this->hasInstalledPackages()) {
            $state = $this->addDependenciesConstraints($state);
        }

        if ($this->isValidState($state)) {
            $this->state = $state;
        }
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return !is_null($this->state);
    }

    /**
     * @return null|DependenciesTreeState
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param DependencyObject[] $dependencies
     * @param DependenciesTreeState $state
     * @return DependenciesTreeState
     */
    protected function registerConstraints(array $dependencies, DependenciesTreeState $state)
    {
        foreach ($dependencies as $dependency) {
            $state->addConstraint(
                $dependency->getPackageName(),
                $dependency->getVersionConstraint()
            );
        }

        return $state;
    }

    /**
     * @param DependenciesTreeState $state
     * @return bool
     */
    protected function isValidState(DependenciesTreeState $state)
    {
        $installed = $this->packagesManager->getInstalled();
        foreach ($installed as $package) {
            $constraints = $state->getConstraint($package->getName());
            if (!$this->comparator->satisfies($package->getVersion(), $constraints)) {
                return false;
            }
        }

        return empty(array_diff(
            array_keys($state->getConstraints()),
            $state->getPackages()->getPackagesNames()
        ));
    }
}
