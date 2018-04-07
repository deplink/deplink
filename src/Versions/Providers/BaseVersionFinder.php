<?php

namespace Deplink\Versions\Providers;

use Deplink\Versions\VersionComparator;
use Deplink\Versions\VersionFinder;

abstract class BaseVersionFinder implements VersionFinder
{
    /**
     * @var VersionComparator
     */
    protected $comparator;

    /**
     * @param VersionComparator $comparator
     */
    public function __construct(VersionComparator $comparator)
    {
        $this->comparator = $comparator;
    }

    /**
     * @param string $version
     * @return bool
     */
    public function has($version)
    {
        return !empty($this->comparator->satisfiedBy($this->get(), $version));
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
