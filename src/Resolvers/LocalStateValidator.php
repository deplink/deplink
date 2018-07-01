<?php

namespace Deplink\Resolvers;

use Deplink\Dependencies\DependenciesCollection;
use Deplink\Dependencies\InstalledPackagesManager;
use Deplink\Packages\PackageFactory;
use Deplink\Packages\ValueObjects\DependencyObject;
use Deplink\Resolvers\Exceptions\ConflictsBetweenDependenciesException;
use Deplink\Versions\VersionComparator;

/**
 * Class is responsible to retrieve local dependencies state
 * (throws an exception in case of incompatibility).
 */
class LocalStateValidator
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
     * @param InstalledPackagesManager $packagesManager
     * @param ResolverFactory $factory
     * @param PackageFactory $packageFactory
     * @param VersionComparator $comparator
     */
    public function __construct(
        InstalledPackagesManager $packagesManager,
        ResolverFactory $factory,
        PackageFactory $packageFactory,
        VersionComparator $comparator
    ) {
        $this->packagesManager = $packagesManager;
        $this->factory = $factory;
        $this->packageFactory = $packageFactory;
        $this->comparator = $comparator;
    }

    /**
     * @param bool $includeDev
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Validators\Exceptions\JsonDecodeException
     * @throws \Deplink\Validators\Exceptions\ValidationException
     * @throws \InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
     */
    public function snapshot($includeDev = true)
    {
        // Reset previous snapshot state.
        $this->state = null;

        // Retrieve information about installed packages.
        if (!$this->packagesManager->hasSnapshot()) {
            $this->packagesManager->snapshot();
        }

        // Stop if ambiguous packages detected (invalid state).
        if (!empty($this->packagesManager->getAmbiguous())) {
            return;
        }

        $state = $this->factory->makeDependenciesTreeState();
        $project = $this->packageFactory->makeFromDir('.');

        // Get all dependencies (including dev) from the project deplink.json file,
        // this dependencies will be used to make an initial state to check tree correctness.
        /** @var DependencyObject[] $dependencies */
        $dependencies = array_merge(
            $project->getDependencies(),
            ($includeDev ? $project->getDevDependencies() : [])
        );

        $this->registerConstraints($dependencies, $state);

        // Get constraints from installed dependencies.
        $installed = $this->packagesManager->getInstalled();
        foreach ($installed as $package) {
            $dependencies = $package->getLocal()->getDependencies();
            $this->registerConstraints($dependencies, $state);
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
     */
    protected function registerConstraints(array $dependencies, DependenciesTreeState $state)
    {
        foreach ($dependencies as $dependency) {
            $state->addConstraint(
                $dependency->getPackageName(),
                $dependency->getVersionConstraint()
            );
        }
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
            $installed->getPackagesNames()
        ));
    }
}
