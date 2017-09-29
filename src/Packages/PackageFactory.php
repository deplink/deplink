<?php

namespace Deplink\Packages;

use Deplink\Downloaders\Downloader;
use Deplink\Environment\Config;
use Deplink\Environment\Exceptions\InvalidPathException;
use Deplink\Environment\Filesystem;
use Deplink\Packages\ValueObjects\CompilerConstraintObject;
use Deplink\Packages\ValueObjects\DependencyObject;
use Deplink\Packages\ValueObjects\RepositoryObject;
use Deplink\Repositories\Repository;
use Deplink\Validators\JsonValidator;
use Deplink\Versions\VersionFinder;
use DI\Container;

class PackageFactory
{
    /**
     * @var Container
     */
    private $di;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var JsonValidator
     */
    private $validator;

    /**
     * @var Config
     */
    private $config;

    /**
     * PackageFactory constructor.
     *
     * @param Container $di
     * @param Filesystem $fs
     * @param JsonValidator $validator
     * @param Config $config
     */
    public function __construct(
        Container $di,
        Filesystem $fs,
        JsonValidator $validator,
        Config $config
    ) {
        $this->di = $di;
        $this->fs = $fs;
        $this->validator = $validator;
        $this->config = $config;
    }

    /**
     * @return LocalPackage
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \InvalidArgumentException
     */
    public function makeEmpty()
    {
        return $this->di->make(LocalPackage::class);
    }

    /**
     * @param string $dir
     * @param string|null $version
     * @return LocalPackage
     * @throws InvalidPathException
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Validators\Exceptions\JsonDecodeException
     * @throws \Deplink\Validators\Exceptions\ValidationException
     * @throws \InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
     */
    public function makeFromDir($dir, $version = null)
    {
        $file = $this->fs->path($dir, 'deplink.json');

        return $this->makeFromFile($file, $version);
    }

    /**
     * @param string $file
     * @param string|null $version
     * @return LocalPackage
     * @throws InvalidPathException
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Validators\Exceptions\JsonDecodeException
     * @throws \Deplink\Validators\Exceptions\ValidationException
     * @throws \InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
     */
    public function makeFromFile($file, $version = null)
    {
        $content = $this->fs->readFile($file);
        $obj = $this->makeFromJson($content, $version);

        $dir = $this->fs->getDirName($file);
        $obj->setDir($dir);

        return $obj;
    }

    /**
     * @param string|object $json
     * @param string|null $version
     * @return LocalPackage
     * @throws InvalidPathException
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Validators\Exceptions\JsonDecodeException
     * @throws \Deplink\Validators\Exceptions\ValidationException
     * @throws \InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
     */
    public function makeFromJson($json, $version = null)
    {
        $this->validator->validate(
            $json,
            $this->fs->readFile(ROOT_DIR . '/resources/schemas/package.schema.json')
        );

        if (!is_object($json)) {
            $json = json_decode($json);
        }

        $package = $this->di->make(LocalPackage::class);
        $this->syncPropertiesWithJson($package, $json);

        // Overwrite version using version provided by the developer
        // (helpful in situations where the version property is ignored).
        if (!is_null($version)) {
            $package->setVersion($version);
        }

        return $package;
    }

    /**
     * Get json value or default if not exists.
     *
     * @param object $json
     * @param string $key Dot notation.
     * @param mixed $default
     * @return mixed
     */
    private function get($json, $key, $default = null)
    {
        $result = $json;
        foreach (explode('.', $key) as $item) {
            if (!isset($result->{$item})) {
                return $default;
            }

            $result = $result->{$item};
        }

        return $result;
    }

    /**
     * @param LocalPackage $package
     * @param object $json
     * @throws InvalidPathException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \InvalidArgumentException
     */
    private function syncPropertiesWithJson(LocalPackage $package, $json)
    {
        $package->setName($this->get($json, 'name'));
        $package->setType($this->get($json, 'type', 'library'));
        $package->setVersion($this->get($json, 'version'));
        $package->setIncludeDirs($this->get($json, 'include', 'include'));
        $package->setSourceDirs($this->get($json, 'source', 'src'));
        $package->setLinkingTypes($this->get($json, 'linking', ['static', 'dynamic']));
        $package->setArchitectures($this->get($json, 'arch', ['x86', 'x64']));

        $compilers = $this->get($json, 'compilers', []);
        $package->setCompilers(CompilerConstraintObject::hydrate($compilers));

        $dependencies = $this->get($json, 'dependencies', []);
        $package->setDependencies(DependencyObject::hydrate($dependencies));

        $devDependencies = $this->get($json, 'dev-dependencies', []);
        $package->setDevDependencies(DependencyObject::hydrate($devDependencies));

        // Register package repositories (including default repositories).
        $repositories = $this->get($json, 'repositories', []);
        $defaultRepositories = $this->config->get('repositories.defaults');
        $repositories = array_merge($repositories, $defaultRepositories);
        $package->setRepositories(RepositoryObject::hydrate($repositories));
    }

    /**
     * @param Repository $repository
     * @param VersionFinder $versionFinder
     * @param Downloader $downloader
     * @return RemotePackage
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \InvalidArgumentException
     */
    public function makeRemote(
        Repository $repository,
        VersionFinder $versionFinder,
        Downloader $downloader
    ) {
        return $this->di->make(RemotePackage::class, [
            'repository' => $repository,
            'versionFinder' => $versionFinder,
            'downloader' => $downloader,
        ]);
    }
}
