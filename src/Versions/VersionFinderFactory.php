<?php

namespace Deplink\Versions;

use Deplink\Packages\PackageFactory;
use Deplink\Versions\Providers\LocalPackageVersionFinder;
use Deplink\Versions\Providers\RemoteRepositoryVersionFinder;
use DI\Container;

class VersionFinderFactory
{
    /**
     * @var Container
     */
    private $di;

    /**
     * @var PackageFactory
     */
    private $packageFactory;

    /**
     * Factory constructor.
     *
     * @param Container $di
     * @param PackageFactory $packageFactory
     */
    public function __construct(Container $di, PackageFactory $packageFactory)
    {
        $this->di = $di;
        $this->packageFactory = $packageFactory;
    }

    /**
     * @param string $path
     * @return LocalPackageVersionFinder
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Validators\Exceptions\JsonDecodeException
     * @throws \Deplink\Validators\Exceptions\ValidationException
     * @throws \InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
     */
    public function makeLocal($path)
    {
        return $this->di->make(LocalPackageVersionFinder::class, [
            'package' => $this->packageFactory->makeFromDir($path),
        ]);
    }

    /**
     * @param string $baseUrl
     * @param string $packageName
     * @return RemoteRepositoryVersionFinder
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \InvalidArgumentException
     */
    public function makeRemote($baseUrl, $packageName)
    {
        return $this->di->make(RemoteRepositoryVersionFinder::class, [
            'baseUrl' => $baseUrl,
            'packageName' => $packageName,
        ]);
    }
}
