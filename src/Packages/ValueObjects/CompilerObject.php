<?php

namespace Deplink\Packages\ValueObjects;

class CompilerObject
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
     * @return CompilerObject
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
     * @return CompilerObject
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
     * @return CompilerObject[]
     */
    public static function hydrate($compilers)
    {
        $result = [];
        foreach ($compilers as $compilerName => $versionConstraint) {
            $result[] = new CompilerObject(
                $compilerName, $versionConstraint
            );
        }

        return $result;
    }
}
