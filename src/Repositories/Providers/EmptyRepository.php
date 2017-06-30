<?php

namespace Deplink\Repositories\Providers;

use Deplink\Packages\RemotePackage;
use Deplink\Repositories\Exceptions\PackageNotFoundException;
use Deplink\Repositories\Repository;

/**
 * Empty repository used as a parent for the root package.
 */
class EmptyRepository implements Repository
{
    /**
     * @param string $package
     * @return bool
     */
    public function has($package)
    {
        return false;
    }

    /**
     * @param string $package
     * @return RemotePackage
     * @throws PackageNotFoundException
     */
    public function get($package)
    {
        throw new PackageNotFoundException("Cannot find '$package' package in the repository which by definition is without packages.'");
    }
}
