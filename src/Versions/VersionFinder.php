<?php

namespace Deplink\Versions;

/**
 * Resolve available package versions for given package.
 */
interface VersionFinder
{
    /**
     * @param string $version
     * @return bool
     */
    public function has($version);

    /**
     * Get all available package versions.
     *
     * @return string[]
     */
    public function get();

    /**
     * Get latest available string.
     *
     * @return string
     */
    public function latest();

    /**
     * Get versions which satisfy constraint.
     *
     * @param string $constraint Version constraint (eq. ">= 5.3").
     * @return string[]
     */
    public function getSatisfiedBy($constraint);

    /**
     * @param string $version
     * @return string[]
     */
    public function getGreaterThan($version);
}
