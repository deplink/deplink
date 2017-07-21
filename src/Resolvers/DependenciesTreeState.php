<?php

namespace Deplink\Resolvers;

class DependenciesTreeState
{
    /**
     * The package name (key) and proposed installation version (value).
     *
     * @var array
     */
    private $versions = [];

    /**
     * The package name (key) with array of string constraints.
     *
     * @var array
     */
    private $constraints = [];

    /**
     * @param string $packageName
     * @param string $version
     */
    public function set($packageName, $version)
    {
        $this->versions[$packageName] = $version;
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

    /**
     * Check whether all packages versions
     * are satisfied by the given constraints.
     *
     * @return bool
     */
    public function checkState()
    {
        //TODO: Required?
    }
}
