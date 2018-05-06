<?php

namespace Deplink\Dependencies;

use Deplink\Resolvers\Exceptions\DependenciesLoopException;

/**
 * Sort dependencies in usage order. First in list will be dependencies
 * which doesn't require other dependencies, next dependencies in the list will
 * depends on the previous ones (used to establish build order).
 */
class HierarchyFinder
{
    /**
     * @var array
     */
    private $packages;

    /**
     * @var string[]
     */
    private $sorted = [];

    /**
     * @var InstalledPackagesManager
     */
    private $packagesManager;

    public function __construct(InstalledPackagesManager $packagesManager)
    {
        $this->packagesManager = $packagesManager;
    }

    public function snapshot()
    {
        if (!$this->packagesManager->hasSnapshot()) {
            $this->packagesManager->snapshot();
        }

        $packages = $this->packagesManager->getInstalled();
        $this->prepareForProcessing($packages);

        $this->sorted = [];
        while ($this->isUnsortedPackageLeft()) {
            $package = $this->getNextWithoutDependencies();
            $this->sorted[] = $package;

            $this->removeFromDependencies($package);
        }
    }

    private function isUnsortedPackageLeft()
    {
        return !empty($this->packages);
    }

    private function prepareForProcessing(DependenciesCollection $packages)
    {
        $this->packages = [];
        foreach ($packages->getPackagesNames() as $name) {
            $this->packages[$name] = [];

            // Specify package names which must be installed first
            // before this package can be installed and build.
            $dependencies = $packages->get($name)->getLocal()->getDependencies();
            foreach ($dependencies as $dependency) {
                // Assign any value, used only keys to have fast access list.
                $this->packages[$name][$dependency->getPackageName()] = true;
            }
        }

        ksort($this->packages); // Sort packages by name
    }

    private function getNextWithoutDependencies()
    {
        foreach ($this->packages as $package => $dependencies) {
            if (empty($dependencies)) {
                unset($this->packages[$package]);
                return $package;
            }
        }

        throw new DependenciesLoopException("Cannot sort dependencies in usage order, probably dependencies loop exists.");
    }

    private function removeFromDependencies($package)
    {
        foreach ($this->packages as $_ => &$dependencies) {
            if (isset($dependencies[$package])) {
                unset($dependencies[$package]);
            }
        }
    }

    /**
     * Get dependencies sorted in usage hierarchy.
     *
     * If some packages all at the same level in the hierarchy
     * (e.g. dependencies which can be build at the same time)
     * then they will be sorted in the lexicographic order.
     *
     * @return string[]
     */
    public function getSorted()
    {
        return $this->sorted;
    }
}
