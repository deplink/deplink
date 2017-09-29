<?php

namespace Deplink\Packages\ValueObjects;

class CompilerConstraintObject
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $versionConstraint;

    /**
     * CompilerConstraintObject constructor.
     *
     * @param string $name
     * @param string $versionConstraint
     */
    public function __construct($name, $versionConstraint)
    {
        $this->name = $name;
        $this->versionConstraint = $versionConstraint;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return CompilerConstraintObject
     */
    public function setName($name)
    {
        $this->name = $name;
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
     * @return CompilerConstraintObject
     */
    public function setVersionConstraint($versionConstraint)
    {
        $this->versionConstraint = $versionConstraint;
        return $this;
    }

    /**
     * Convert raw array to object.
     *
     * @param object|array $compilers
     * @return CompilerConstraintObject[]
     * @throws \InvalidArgumentException
     */
    public static function hydrate($compilers)
    {
        $result = [];
        foreach ($compilers as $compilerName => $versionConstraint) {
            $result[] = new CompilerConstraintObject(
                $compilerName, $versionConstraint
            );
        }

        return $result;
    }
}
