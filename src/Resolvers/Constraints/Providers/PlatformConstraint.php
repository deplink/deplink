<?php

namespace Deplink\Resolvers\Constraints\Providers;

use Deplink\Packages\LocalPackage;
use Deplink\Resolvers\Constraints\Constraint;

class PlatformConstraint implements Constraint
{
    /**
     * @var string[]
     */
    private $platforms;

    /**
     * Save some data which will be later used
     * to check remote packages compatibility.
     *
     * @param LocalPackage $package
     */
    public function register(LocalPackage $package)
    {
        $this->platforms = array_intersect(
            $this->platforms,
            $package->getPlatforms()
        );
    }

    /**
     * Check whether other package match
     * previously registered constraints.
     *
     * @param LocalPackage $package
     * @return bool True if package is compatible with registered packages, false otherwise.
     */
    public function check(LocalPackage $package)
    {
        foreach ($this->platforms as $platform) {
            if (!in_array($platform, $package->getPlatforms())) {
                return false;
            }
        }

        return true;
    }
}
