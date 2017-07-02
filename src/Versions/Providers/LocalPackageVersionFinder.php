<?php

namespace Deplink\Versions\Providers;

use Deplink\Packages\LocalPackage;
use Deplink\Versions\VersionComparator;
use Deplink\Versions\VersionFinder;

/**
 * Provide only one version specified in the
 * deplink.json file (the 'version' property).
 *
 * If version is not defined then the '0.1.0' version
 * will be used and treated always as a new version
 * (forcing to download package every time).
 */
class LocalPackageVersionFinder implements VersionFinder
{
    /**
     * @var LocalPackage
     */
    private $package;

    /**
     * @var VersionComparator
     */
    private $comparator;

    /**
     * LocalPackageVersionFinder constructor.
     *
     * @param VersionComparator $comparator
     * @param LocalPackage $package
     */
    public function __construct(VersionComparator $comparator, LocalPackage $package)
    {
        $this->package = $package;
        $this->comparator = $comparator;
    }

    /**
     * @param string $version
     * @return bool
     */
    public function has($version)
    {
        return $this->comparator->equalTo($version, $this->get()[0]);
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

    /**
     * Get versions which satisfy constraint.
     *
     * @param string $constraint Version constraint (eq. ">= 5.3").
     * @return string[]
     */
    public function getSatisfiedBy($constraint)
    {
        return $this->comparator->satisfiedBy($this->get(), $constraint);
    }

    /**
     * @param string $version
     * @return string[]
     */
    public function getGreaterThan($version)
    {
        return $this->getSatisfiedBy(">$version");
    }
}
