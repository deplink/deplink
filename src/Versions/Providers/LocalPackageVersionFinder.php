<?php

namespace Deplink\Versions\Providers;

use Deplink\Packages\LocalPackage;
use Deplink\Versions\VersionComparator;

/**
 * Provide only one version specified in the
 * deplink.json file (the 'version' property).
 *
 * If version is not defined then the '0.1.0' version
 * will be used and treated always as a new version
 * (forcing to download package every time).
 */
class LocalPackageVersionFinder extends BaseVersionFinder
{
    /**
     * @var LocalPackage
     */
    private $package;

    /**
     * LocalPackageVersionFinder constructor.
     *
     * @param VersionComparator $comparator
     * @param LocalPackage $package
     */
    public function __construct(VersionComparator $comparator, LocalPackage $package)
    {
        $this->package = $package;

        parent::__construct($comparator);
    }

    /**
     * Get all available package versions.
     *
     * @return string[]
     */
    public function get()
    {
        return [$this->package->getVersion() ?: '0.1.0'];
    }
}
