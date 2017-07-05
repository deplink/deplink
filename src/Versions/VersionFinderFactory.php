<?php

namespace Deplink\Versions;

use Deplink\Packages\LocalPackage;
use Deplink\Versions\Providers\LocalPackageVersionFinder;
use DI\Container;

class VersionFinderFactory
{
    /**
     * @var Container
     */
    private $di;

    /**
     * Factory constructor.
     *
     * @param Container $di
     */
    public function __construct(Container $di)
    {
        $this->di = $di;
    }

    /**
     * @param LocalPackage $package
     * @return LocalPackageVersionFinder
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \InvalidArgumentException
     */
    public function makeLocalPackageVersionFinder(LocalPackage $package)
    {
        return $this->di->make(LocalPackageVersionFinder::class, [
            'package' => $package,
        ]);
    }
}
