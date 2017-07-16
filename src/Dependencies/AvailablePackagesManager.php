<?php

namespace Deplink\Dependencies;

use Deplink\Dependencies\Excpetions\DependenciesLoopException;
use Deplink\Dependencies\ValueObjects\DependencyConstraintsObject;
use Deplink\Packages\LocalPackage;
use Deplink\Packages\PackageFactory;
use Deplink\Packages\ValueObjects\DependencyObject;
use Deplink\Repositories\RepositoriesCollection;
use Deplink\Repositories\RepositoryFactory;

/**
 * Resolve dependencies tree from deplink.json file
 * (include nested dependencies with loops detection).
 *
 * Only repositories defined in the root deplink.json file are used.
 * This prevents packages names conflicts, realize the situation when A has 2 dependencies:
 * B and C, the B also has the C dependency (but due to the specific repositories definition
 * the C package is resolved from the other repository).
 *
 * There are 2 major use cases of the repositories:
 * - to register private repository shared between all company packages,
 * - and to improve development process experience.
 */
class AvailablePackagesManager
{
    /**
     * Key-Value pairs, where the key is the package name
     * (optimize searching for already defined packages).
     *
     * @var DependencyConstraintsObject[]
     */
    private $constraints = [];

    /**
     * @var LocalPackage
     */
    private $project;

    /**
     * @var RepositoriesCollection
     */
    private $repositories;

    /**
     * @var PackageFactory
     */
    private $packageFactory;

    /**
     * @var RepositoryFactory
     */
    private $repositoryFactory;

    /**
     * AvailablePackagesManager constructor.
     *
     * @param PackageFactory $packageFactory
     * @param RepositoryFactory $repositoryFactory
     */
    public function __construct(PackageFactory $packageFactory, RepositoryFactory $repositoryFactory)
    {
        $this->packageFactory = $packageFactory;
        $this->repositoryFactory = $repositoryFactory;
    }

    /**
     * Make snapshot of the available packages.
     */
    public function snapshot()
    {
        $this->project = $this->packageFactory->makeFromDir('.');
        $this->repositories = $this->repositoryFactory->makeCollection(
            $this->project->getRepositories()
        );

        $dependencies = array_merge(
            $this->project->getDependencies(),
            $this->project->getDevDependencies()
        );

        $this->constraints = [];
        $this->register($dependencies);
    }

    /**
     * @param DependencyObject[] $dependencies
     * @param string[] $parents Names of the parent packages.
     * @throws DependenciesLoopException
     * @throws \Deplink\Repositories\Exceptions\PackageNotFoundException
     */
    private function register(array $dependencies, array $parents = ['root'])
    {
        foreach ($dependencies as $dependency) {
            $packageName = $dependency->getPackageName();
            if (!isset($this->constraints[$packageName])) {
                $this->constraints[$packageName] = new DependencyConstraintsObject();
            }

            // Throw if dependency is added again by the same package,
            // which equals the situation where there's a dependencies loop.
            if (in_array($packageName, $parents)) {
                $chain = implode(' -> ', $parents);
                throw new DependenciesLoopException("Dependencies loop detected! Trying to add '$packageName' package which exists in the following dependencies chain: $chain.");
            }

            // Add constraints collected from the current dependency.
            $this->constraints[$packageName]
                ->addLinkingConstraint($dependency)
                ->addVersionConstraint($dependency);

            // Collect requirements from nested dependencies.
            $remotePackage = $this->repositories->find($packageName);
            $nestedDependencies = $remotePackage->getPackage()->getDependencies();
            $this->register($nestedDependencies, array_merge(
                $parents, [$dependency->getPackageName()]
            ));
        }
    }

    /**
     * Key-Value pairs, where the key is the package name.
     *
     * @return DependencyConstraintsObject[]
     */
    public function getConstraints()
    {
        return $this->constraints;
    }
}
