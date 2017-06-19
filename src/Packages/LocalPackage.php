<?php

namespace Deplink\Packages;

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
    private $includes = ['include'];

    /**
     * @var string[]
     */
    private $sources = ['src'];

    /**
     * @var CompilerConstraintObject[]
     */
//    private $compilers = [];

    /**
     * @var PlatformConstraintObject[]
     */
//    private $platforms = [];

    /**
     * @var ArchitectureConstraintObject[]
     */
//    private $architectures = [];

    /**
     * @var LinkingTypeConstraintObject[]
     */
//    private $linkingTypes = [];

    /**
     * @var DependencyConstraintObject[]
     */
//    private $dependencies = [];

    /**
     * @var DependencyConstraintObject[]
     */
//    private $devDependencies = [];

    /**
     * @var MacroObject
     */
//    private $macros = [];

    /**
     * @var ScriptConstraintObject
     */
//    private $scripts = [];

    /**
     * @var RepositoryObject[]
     */
//    private $repositories = [];

    /**
     * Path to the json file.
     *
     * @var string
     */
    private $src;

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
    public function getSrc()
    {
        return $this->src;
    }

    /**
     * @param string $src
     * @return LocalPackage
     */
    public function setSrc($src)
    {
        $this->src = $src;
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
