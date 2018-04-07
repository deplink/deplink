<?php

namespace Deplink\Packages;

use Deplink\Packages\ValueObjects\CompilerObject;
use Deplink\Packages\ValueObjects\ConstraintObject;
use Deplink\Packages\ValueObjects\DependencyObject;
use Deplink\Packages\ValueObjects\RepositoryObject;

/**
 * Mutable package json file.
 */
class LocalPackage
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
     * @var CompilerObject[]
     */
    private $compilers = [];

    /**
     * TODO
     *
     * @var PlatformConstraintObject[]
     */
    private $platforms = [];

    /**
     * @var string[]
     */
    private $architectures = [];

    /**
     * Either static or dynamic.
     *
     * @var string[]
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
     *
     * @var MacroObject
     */
    private $macros = [];

    /**
     * TODO
     *
     * @var ScriptConstraintObject
     */
    private $scripts = [];

    /**
     * @var RepositoryObject[]
     */
    private $repositories = [];

    /**
     * Additional configuration without strict format.
     *
     * @var ConstraintObject
     */
    private $config;

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
     * @return CompilerObject[]
     */
    public function getCompilers()
    {
        return $this->compilers;
    }

    /**
     * @param CompilerObject[] $compilers
     * @return LocalPackage
     */
    public function setCompilers($compilers)
    {
        $this->compilers = $compilers;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getLinkingTypes()
    {
        return $this->linkingTypes;
    }

    /**
     * @param string|string[] $linkingTypes
     * @return LocalPackage
     */
    public function setLinkingTypes($linkingTypes)
    {
        $this->linkingTypes = (array)$linkingTypes;
        return $this;
    }

    /**
     * @param string $linkingType
     * @return bool
     */
    public function hasLinkingType($linkingType)
    {
        return array_search($linkingType, $this->getLinkingTypes()) !== false;
    }

    /**
     * @return string[]
     */
    public function getArchitectures()
    {
        return $this->architectures;
    }

    /**
     * @param string|string[] $architectures
     * @return LocalPackage
     */
    public function setArchitectures($architectures)
    {
        $this->architectures = (array)$architectures;
        return $this;
    }

    /**
     * Access config using dot notation.
     *
     * For example the: $package->getConfig('compiler.gcc')
     * will resolve the json key: "config": { "compiler": { "gcc": <value> }}
     *
     * Last key in json can contains additional constraints:
     * "config": { "compiler": { "gcc:linux": <value> }}
     *
     * @param string $key Key in dot notation.
     * @param mixed $default
     * @param string[] $constraints
     * @return mixed
     */
    public function getConfig($key, $default = null, array $constraints = [])
    {
        return $this->config->get($key, $default, $constraints);
    }

    /**
     * @return ConstraintObject
     */
    public function getConfigObject()
    {
        return $this->config;
    }

    /**
     * @param ConstraintObject $config
     * @return LocalPackage
     */
    public function setConfig(ConstraintObject $config)
    {
        $this->config = $config;
        return $this;
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
