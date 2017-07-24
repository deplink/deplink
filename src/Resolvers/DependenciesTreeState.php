<?php

namespace Deplink\Resolvers;

use Deplink\Dependencies\DependenciesCollection;

class DependenciesTreeState
{
    /**
     * @var DependenciesCollection
     */
    private $packages = [];

    /**
     * The package name (key) with array of string constraints.
     *
     * @var array
     */
    private $constraints = [];

    /**
     * @param string $packageName
     * @param string $version
     * @return $this
     */
    public function setPackage($packageName, $version)
    {
        $this->packages->add($packageName, $version);

        return $this;
    }

    /**
     * @return DependenciesCollection
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * @param string $packageName
     * @param string $constraint
     */
    public function addConstraint($packageName, $constraint)
    {
        if (!isset($this->constraints[$packageName])) {
            $this->constraints[$packageName] = [];
        }

        $this->constraints[$packageName][] = $constraint;
    }

    /**
     * @param string $packageName
     * @return string
     */
    public function getConstraint($packageName)
    {
        return implode(' ', $this->constraints[$packageName]);
    }
}
