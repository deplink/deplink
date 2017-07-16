<?php

namespace Deplink\Packages;

use Deplink\Packages\ValueObjects\DependencyObject;
use Deplink\Packages\ValueObjects\RepositoryObject;

/**
 * Mutable package json file.
 */
class LocalPackage implements \JsonSerializable
{
    const AVAILABLE_TYPES = [
        'project', 'library',
    ];

    /**
     * The name of the package. It consists of organization name and project name, separated by /.
     *
     * The name can contain alphanumeric characters and dash symbols.
     * In order to simplify its installation, it's recommended to define a short name.
     *
     * Required for published packages (libraries).
     *
     * @var string
     */
    private $name;

    /**
     * The type of the package. Two types are supported: library and project.
     * Project type protect from accidental publishing or linking in other package.
     *
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string[]
     */
    private $includeDirs = ['include'];

    /**
     * @var string[]
     */
    private $sourceDirs = ['src'];

    /**
     * TODO
     *
     * @var CompilerConstraintObject[]
     */
    private $compilers = [];

    /**
     * TODO
     * @var PlatformConstraintObject[]
     */
    private $platforms = [];

    /**
     * TODO
     * @var ArchitectureConstraintObject[]
     */
    private $architectures = [];

    /**
     * TODO
     * @var LinkingTypeConstraintObject[]
     */
    private $linkingTypes = [];

    /**
     * @var DependencyObject[]
     */
    private $dependencies = [];

    /**
     * Development dependencies are installed only for the root package
     * and can be omitted using --no-dev option. Use them only for packages
     * which aren't required for the proper functioning of the project.
     *
     * @var DependencyObject[]
     */
    private $devDependencies = [];

    /**
     * TODO
     * @var MacroObject
     */
    private $macros = [];

    /**
     * TODO
     * @var ScriptConstraintObject
     */
    private $scripts = [];

    /**
     * @var RepositoryObject[]
     */
    private $repositories = [];

    /**
     * Dir where the json file is located.
     *
     * @var string
     */
    private $dir;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return LocalPackage
     * @throws \InvalidArgumentException
     */
    public function setName($name)
    {
        if (!preg_match('#^[a-z0-9-]+/[a-z0-9-]+$#', $name)) {
            throw new \InvalidArgumentException("Invalid '$name' package name, use org/package format (only alphanumeric and dashes allowed)");
        }

        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return LocalPackage
     * @throws \InvalidArgumentException
     */
    public function setType($type)
    {
        if (!in_array($type, self::AVAILABLE_TYPES, true)) {
            $msg = implode("', '", self::AVAILABLE_TYPES);
            throw new \InvalidArgumentException("Package type can be one of '$msg', the '$type' given.");
        }

        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return LocalPackage
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return string
     */
    public function getDir()
    {
        return $this->dir;
    }

    /**
     * @param string $dir
     * @return LocalPackage
     */
    public function setDir($dir)
    {
        $this->dir = $dir;
        return $this;
    }

    /**
     * @return \string[]
     */
    public function getIncludeDirs()
    {
        return $this->includeDirs;
    }

    /**
     * @param string|\string[] $includeDirs
     * @return LocalPackage
     */
    public function setIncludeDirs($includeDirs)
    {
        $this->includeDirs = (array)$includeDirs;
        return $this;
    }

    /**
     * @return \string[]
     */
    public function getSourceDirs()
    {
        return $this->sourceDirs;
    }

    /**
     * @param string|\string[] $sourceDirs
     * @return LocalPackage
     */
    public function setSourceDirs($sourceDirs)
    {
        $this->sourceDirs = (array)$sourceDirs;
        return $this;
    }

    /**
     * @return RepositoryObject[]
     */
    public function getRepositories()
    {
        return $this->repositories;
    }

    /**
     * @param RepositoryObject[] $repositories
     * @return LocalPackage
     */
    public function setRepositories($repositories)
    {
        $this->repositories = $repositories;
        return $this;
    }

    /**
     * @return DependencyObject[]
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * @param DependencyObject[] $dependencies
     * @return LocalPackage
     */
    public function setDependencies($dependencies)
    {
        $this->dependencies = $dependencies;
        return $this;
    }

    /**
     * @return DependencyObject[]
     */
    public function getDevDependencies()
    {
        return $this->devDependencies;
    }

    /**
     * @param DependencyObject[] $devDependencies
     * @return LocalPackage
     */
    public function setDevDependencies($devDependencies)
    {
        $this->devDependencies = $devDependencies;
        return $this;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *  which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        $result = [];
        $attributes = ['name', 'type', 'version'];
        foreach ($attributes as $attribute) {
            $value = $this->{$attribute};
            if (!empty($value)) {
                $result[$attribute] = $value;
            }
        }

        return new \ArrayObject($result);
    }

    /**
     * Get human-readable json.
     *
     * @return string
     */
    public function getJson()
    {
        return json_encode($this, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $name = $this->getName();
        $version = $this->getVersion();

        return "$name@$version";
    }
}
