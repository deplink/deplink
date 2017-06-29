<?php

namespace Deplink\Repositories;

use Deplink\Packages\RemotePackage;
use Deplink\Repositories\Exceptions\PackageNotFoundException;

/**
 * Repository constructor can accept argument with name $src
 * (the value of "type" field in section "repositories" in package json).
 */
interface Repository
{
    /**
     * @param string $package
     * @return bool
     */
    public function has($package);

    /**
     * @param string $package
     * @return RemotePackage
     * @throws PackageNotFoundException
     */
    public function get($package);
}
