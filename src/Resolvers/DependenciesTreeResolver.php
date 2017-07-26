<?php

namespace Deplink\Resolvers;

use Deplink\Dependencies\InstalledPackagesManager;
use Deplink\Locks\LockFactory;
use Deplink\Locks\LockFile;
use Deplink\Packages\LocalPackage;
use Deplink\Packages\PackageFactory;
use Deplink\Repositories\RepositoriesCollection;
use Deplink\Repositories\RepositoryFactory;
use Deplink\Resolvers\Exceptions\ConflictsBetweenDependenciesException;
use Deplink\Versions\VersionComparator;

/**
 * Establish which packages should be removed,
 * updated or downloaded and installed from scratch.
 */
class DependenciesTreeResolver
{
    /**
     * @var \DateTime
     */
    private $snapshotAt = null;

    /**
     * @var DependenciesTreeState[]
     */
    private $resolvedStates;

    /**
     * @var RepositoriesCollection
     */
    private $repositories;

    /**
     * @var LockFile
     */
    private $locked;

    /**
     * @var LocalPackage
     */
    private $project;

    /**
     * Packages which version can be upgraded.
     *
     * @var string[]
     */
    private $unlocked;

    /**
     * @var LockFactory
     */
    protected $lockFactory;

    /**
     * @var PackageFactory
     */
    protected $packageFactory;

    /**
     * @var InstalledPackagesManager
     */
    protected $installedPackages;

    /**
     * @var RepositoryFactory
     */
    protected $repositoryFactory;

    /**
     * @var ResolverFactory
     */
    protected $resolverFactory;

    /**
     * @var VersionComparator
     */
    protected $versionComparator;

    /**
     * DependenciesTreeResolver constructor.
     *
     * @param LockFactory $lockFactory
     * @param PackageFactory $packageFactory
     * @param RepositoryFactory $repositoryFactory
     * @param InstalledPackagesManager $installedPackages
     * @param ResolverFactory $resolverFactory
     * @param VersionComparator $versionComparator
     */
    public function __construct(
        LockFactory $lockFactory,
        PackageFactory $packageFactory,
        RepositoryFactory $repositoryFactory,
        InstalledPackagesManager $installedPackages,
        ResolverFactory $resolverFactory,
        VersionComparator $versionComparator
    ) {
        $this->lockFactory = $lockFactory;
        $this->packageFactory = $packageFactory;
        $this->repositoryFactory = $repositoryFactory;
        $this->resolverFactory = $resolverFactory;
        $this->versionComparator = $versionComparator;
    }

    /**
     * Mark packages which should be upgraded
     * if any new compatible version exists.
     *
     * @param string[] $packages
     */
    public function update(array $packages)
    {
        $this->unlocked = $packages;
    }

    /**
     * Resolve tree for the current project state.
     */
    public function snapshot()
    {
        $this->locked = $this->lockFactory->makeFromFileOrEmpty('deplink.lock');
        $this->project = $this->packageFactory->makeFromDir('.');

        // All dependencies will be discovered in repositories
        // defined only in the current project deplink.json file.
        $this->repositories = $this->repositoryFactory->makeCollection(
            $this->project->getRepositories()
        );

        // Get all dependencies (including dev) from the project deplink.json file,
        // this dependencies will be used to make an initial state to check tree correctness.
        $dependencies = array_merge(
            $this->project->getDependencies(),
            $this->project->getDevDependencies()
        );

        $state = $this->resolverFactory->makeDependenciesTreeState();
        foreach ($dependencies as $packageName => $constraint) {
            $state->addConstraint($packageName, $constraint);
        }

        // Start recursive resolving process.
        $dependenciesNames = array_keys($dependencies);
        $this->resolvedStates = $this->resolve($dependenciesNames, [$state]);
        $this->snapshotAt = new \DateTime();
    }

    /**
     * Resolve packages versions from most important
     * source to the least important source.
     *
     * @param string[] $dependencies
     * @param DependenciesTreeState[] $states
     * @return DependenciesTreeState[]
     * @throws ConflictsBetweenDependenciesException
     * @throws \Deplink\Repositories\Exceptions\PackageNotFoundException
     */
    private function resolve($dependencies, $states)
    {
        foreach ($dependencies as $dependencyName) {
            $states = $this->resolveUnit($dependencyName, $states);
        }

        // Search for the matching states.
        if (!empty($states)) {
            return $states;
        }

        // Not found satisfiable state.
        // TODO: More details about conflicts and how to resolve them.
        throw new ConflictsBetweenDependenciesException("Cannot resolve dependencies tree.");
    }

    /**
     * TODO: Dependencies loop detection
     *
     * @param string $dependency
     * @param DependenciesTreeState[] $states
     * @return DependenciesTreeState[]
     * @throws \Deplink\Repositories\Exceptions\PackageNotFoundException
     */
    private function resolveUnit($dependency, $states)
    {
        // This variable will store new generated states
        // including this and nested dependencies.
        $resultStates = [];

        // Process will iterate over each state, duplicate it and
        // install the same package but in other version for every copy.
        foreach ($states as $state) {
            // This variable will store only duplicates of the current state
            // with other package version and dependencies constraints.
            $newStates = [];

            // Get available versions of dependency for the current state.
            $remote = $this->repositories->find($dependency);
            $versions = $remote->getVersionFinder()->getSatisfiedBy(
                $state->getConstraint($dependency)
            );

            // Emit new state for each version of dependency.
            // TODO: Pick newest version if unlocked (update), preferred version, locked version or newest one.
            // TODO: Limit number of versions to 2-5 (don't check all).
            $versions = $this->versionComparator->reverseSort($versions);
            foreach ($versions as $version) {
                $tmpState = clone $state;
                $tmpState->setPackage($dependency, $version);

                // Add packages constraints to the state for the given version of the dependency.
                $package = $remote->getDownloader()->requestDetails($version);
                foreach ($package->getDependencies() as $dependencyName => $constraint) {
                    $tmpState->addConstraint($dependencyName, $constraint);
                }

                $newStates[] = $tmpState;
            }

            // Repeat states generating for nested dependencies.
            foreach ($versions as $version) {
                $package = $remote->getDownloader()->requestDetails($version);
                $resultStates = array_merge(
                    $resultStates,
                    $this->resolveUnit($package->getName(), $newStates)
                );
            }
        }

        return $resultStates;
    }

    /**
     * @return DependenciesTreeState[]
     */
    public function getResolvedStates()
    {
        return $this->resolvedStates;
    }

    /**
     * Check whether snapshot was made at least once.
     *
     * @return boolean
     */
    public function hasSnapshot()
    {
        return !is_null($this->snapshotAt);
    }
}
