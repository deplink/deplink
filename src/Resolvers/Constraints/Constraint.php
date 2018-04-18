<?php

namespace Deplink\Resolvers\Constraints;

use Deplink\Packages\LocalPackage;

interface Constraint
{
    /**
     * Save some data which will be later used
     * to check remote packages compatibility.
     *
     * @param LocalPackage $package
     * @return mixed
     */
    public function register(LocalPackage $package);

    /**
     * Check whether other package match
     * previously registered constraints.
     *
     * @param LocalPackage $package
     * @return bool True if package is compatible with registered packages, false otherwise.
     */
    public function check(LocalPackage $package);
}
