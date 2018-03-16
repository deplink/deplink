<?php

namespace Deplink\Resolvers;

use Deplink\Dependencies\InstalledPackagesManager;
use Deplink\Locks\LockFactory;
use Deplink\Locks\LockFile;
use Deplink\Packages\LocalPackage;
use Deplink\Packages\PackageFactory;
use Deplink\Packages\ValueObjects\DependencyObject;
use Deplink\Repositories\Exceptions\PackageNotFoundException;
use Deplink\Repositories\RepositoriesCollection;
use Deplink\Repositories\RepositoryFactory;
use Deplink\Resolvers\Exceptions\ConflictsBetweenDependenciesException;
use Deplink\Resolvers\Exceptions\DependenciesLoopException;
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
     *
     * @param boolean $includeDev
     * @throws ConflictsBetweenDependenciesException
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
     * @throws DependenciesLoopException
     */
    public function snapshot($includeDev = true)
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
        /** @var DependencyObject[] $dependencies */
        $dependencies = array_merge(
            $this->project->getDependencies(),
            ($includeDev ? $this->project->getDevDependencies() : [])
        );

        $state = $this->resolverFactory->makeDependenciesTreeState();
        foreach ($dependencies as $dependency) {
            $state->addConstraint(
                $dependency->getPackageName(),
                $dependency->getVersionConstraint()
            );
        }

        // Start recursive resolving process.
        $this->resolvedStates = $this->resolve($dependencies, [$state]);
        $this->snapshotAt = new \DateTime();
    }

    /**
     * Resolve packages versions from most important
     * source to the least important source.
     *
     * @param DependencyObject[] $dependencies
     * @param DependenciesTreeState[] $states
     * @return DependenciesTreeState[]
     * @throws ConflictsBetweenDependenciesException
     * @throws PackageNotFoundException
     * @throws DependenciesLoopException
     */
    private function resolve($dependencies, $states)
    {
        foreach ($dependencies as $dependency) {
            $states = $this->resolveUnit($dependency, $states);
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
     * @param DependencyObject $dependency
     * @param DependenciesTreeState[] $states
     * @param string[] $parents
     * @return DependenciesTreeState[]
     * @throws PackageNotFoundException
     * @throws DependenciesLoopException
     */
    private function resolveUnit(DependencyObject $dependency, $states, array $parents = [])
    {
        // This variable will store new generated states
        // including this and nested dependencies.
        $resultStates = [];

        // Add current dependency to the parents chains.
        $parents = array_merge($parents, [$dependency->getPackageName()]);

        // Process will iterate over each state, duplicate it and
        // install the same package but in other version for every copy.
        foreach ($states as $state) {
            // This variable will store only duplicates of the current state
            // with other package version and dependencies constraints.
            $newStates = [];

            // Get available versions of dependency for the current state.
            $remote = $this->repositories->find($dependency->getPackageName());
            $versions = $remote->getVersionFinder()->getSatisfiedBy(
                $state->getConstraint($dependency->getPackageName())
            );

            // Emit new state for each version of dependency.
            // TODO: Pick newest version if unlocked (update), preferred version, locked version or newest one.
            // TODO: Limit number of versions to 2-5 (don't check all).
            $versions = $this->versionComparator->reverseSort($versions);
            foreach ($versions as $version) {
                $tmpState = clone $state;
                $tmpState->setPackage($dependency->getPackageName(), $version, $remote);

                // Add packages constraints to the state for the given version of the dependency.
                // TODO: Support multiple constraints: version, platform, linking type...
                $package = $remote->getDownloader()->requestDetails($version);
                foreach ($package->getDependencies() as $dependencyName => $constraint) {
                    $tmpState->addConstraint($dependencyName, $constraint);
                }

                $newStates[] = $tmpState;
            }

            // Repeat states generating for nested dependencies.
            foreach ($versions as $version) {
                $package = $remote->getDownloader()->requestDetails($version);

                // If package has dependencies then try to match dependency
                // to previously generated states, merge valid states.
                foreach ($package->getDependencies() as $nestedDependency) {
                    // Fail if the dependencies loop has been detected
                    // (print full invalid dependencies chain).
                    $nestedDependencyName = $nestedDependency->getPackageName();
                    if (in_array($nestedDependencyName, $parents)) {
                        $loopChain = implode(' -> ', $parents);
                        throw new DependenciesLoopException("Dependencies loop detected: $loopChain -> $nestedDependencyName");
                    }

                    $resultStates = array_merge(
                        $resultStates,
                        $this->resolveUnit($nestedDependency, $newStates, $parents)
                    );
                }

                // If package doesn't contains any dependencies
                // then we can assume that all states are valid.
                if (empty($package->getDependencies())) {
                    $resultStates = array_merge(
                        $resultStates,
                        $newStates
                    );
                }
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
