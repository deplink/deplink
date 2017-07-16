<?php

namespace Deplink\Dependencies\ValueObjects;

use Deplink\Packages\ValueObjects\DependencyObject;

/**
 * Store information about the requirements for the package
 * collected from the whole dependencies tree.
 */
class DependencyConstraintsObject
{
    /**
     * @var string[]
     */
    private $versionConstraints = [];

    /**
     * @var string[][]
     */
    private $linkingConstraints = [];

    /**
     * @return \string[]
     */
    public function getVersionConstraints()
    {
        return $this->versionConstraints;
    }

    /**
     * @param \string[] $versionConstraints
     * @return DependencyConstraintsObject
     */
    public function setVersionConstraints($versionConstraints)
    {
        $this->versionConstraints = $versionConstraints;
        return $this;
    }

    /**
     * @param DependencyObject $dependency
     * @return $this
     */
    public function addVersionConstraint(DependencyObject $dependency)
    {
        $this->versionConstraints[] = $dependency->getVersionConstraint();
        return $this;
    }

    /**
     * @return \string[][]
     */
    public function getLinkingConstraints()
    {
        return $this->linkingConstraints;
    }

    /**
     * @param \string[] $linkingConstraints
     * @return DependencyConstraintsObject
     */
    public function setLinkingConstraints($linkingConstraints)
    {
        $this->linkingConstraints = $linkingConstraints;
        return $this;
    }

    /**
     * @param DependencyObject $dependency
     * @return $this
     */
    public function addLinkingConstraint(DependencyObject $dependency)
    {
        $this->linkingConstraints[] = $dependency->getLinkingConstraint();
        return $this;
    }
}
