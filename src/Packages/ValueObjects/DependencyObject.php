<?php

namespace Deplink\Packages\ValueObjects;

class DependencyObject
{
    /**
     * @var string
     */
    private $packageName;

    /**
     * @var string
     */
    private $versionConstraint;

    /**
     * @var string[]
     */
    private $linkingConstraint;

    /**
     * DependencyConstraintObject constructor.
     *
     * @param $packageName
     * @param $versionConstraint
     * @param $linkingConstraint
     */
    public function __construct($packageName, $versionConstraint, $linkingConstraint)
    {
        $this->packageName = $packageName;
        $this->versionConstraint = $versionConstraint;
        $this->linkingConstraint = $linkingConstraint;
    }

    /**
     * @return string
     */
    public function getPackageName()
    {
        return $this->packageName;
    }

    /**
     * @param string $packageName
     * @return DependencyObject
     */
    public function setPackageName($packageName)
    {
        $this->packageName = $packageName;
        return $this;
    }

    /**
     * @return string
     */
    public function getVersionConstraint()
    {
        return $this->versionConstraint;
    }

    /**
     * @param string $versionConstraint
     * @return DependencyObject
     */
    public function setVersionConstraint($versionConstraint)
    {
        $this->versionConstraint = $versionConstraint;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getLinkingConstraint()
    {
        return $this->linkingConstraint;
    }

    /**
     * @param string[] $linkingConstraint
     * @return DependencyObject
     */
    public function setLinkingConstraint(array $linkingConstraint)
    {
        $this->linkingConstraint = $linkingConstraint;
        return $this;
    }

    /**
     * Convert raw array to object.
     *
     * @param array $dependencies
     * @return DependencyObject[]
     * @throws \InvalidArgumentException
     */
    public static function hydrate(array $dependencies)
    {
        $result = [];
        foreach ($dependencies as $packageName => $constraints) {
            $constraints = explode(':', $constraints, 2);

            if (!isset($constraints[0])) {
                throw new \InvalidArgumentException("Missing version constraint for the '$packageName' package.");
            }

            $versionConstraint = $constraints[0];
            $typeConstraint = isset($constraints[1]) ? [$constraints[1]] : ['static', 'dynamic'];
            if (!in_array($typeConstraint[0], ['static', 'dynamic'])) {
                throw new \InvalidArgumentException("Linking constraint for the '$packageName' package must be either 'static' or 'dynamic', given '{$typeConstraint[0]}'.");
            }

            $result[] = new DependencyObject(
                $packageName, $versionConstraint, $typeConstraint
            );
        }

        return $result;
    }
}
