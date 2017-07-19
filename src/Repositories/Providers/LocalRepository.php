<?php

namespace Deplink\Repositories\Providers;

use Deplink\Downloaders\DownloaderFactory;
use Deplink\Environment\Filesystem;
use Deplink\Packages\PackageFactory;
use Deplink\Packages\RemotePackage;
use Deplink\Repositories\Exceptions\PackageNotFoundException;
use Deplink\Repositories\Repository;
use Deplink\Versions\VersionFinderFactory;

/**
 * Stores information about packages in local filesystem structure.
 *
 * The root directory passed to the constructor contains the directories
 * which represents the organizations. Each organization directory contains
 * directories which represents the specified organization package.
 *
 * Each package directory must contains valid cbuilder.json file.
 *
 * Example structure:
 * - root_dir/company_name/console/...
 * - root_dir/company_name/di_container/...
 * - root_dir/org_1/package_name/...
 *   root_dir/org_1/package_name/cbuilder.json
 */
class LocalRepository implements Repository
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var PackageFactory
     */
    private $packageFactory;

    /**
     * @var string
     */
    private $dir = '.';

    /**
     * @var VersionFinderFactory
     */
    private $versionFinderFactory;

    /**
     * @var DownloaderFactory
     */
    private $downloaderFactory;

    /**
     * LocalRepository constructor.
     *
     * @param Filesystem $fs
     * @param PackageFactory $packageFactory
     * @param VersionFinderFactory $versionFinderFactory
     * @param DownloaderFactory $downloaderFactory
     * @param $src
     */
    public function __construct(
        Filesystem $fs,
        PackageFactory $packageFactory,
        VersionFinderFactory $versionFinderFactory,
        DownloaderFactory $downloaderFactory,
        $src
    ) {
        $this->fs = $fs;
        $this->packageFactory = $packageFactory;
        $this->versionFinderFactory = $versionFinderFactory;
        $this->downloaderFactory = $downloaderFactory;
        $this->dir = $src;
    }

    /**
     * Get unique identifier. Instances of the repositories
     * with the same type and source should return same identifier.
     *
     * Common pattern is to return identifier in format:
     * <classNamespace>|<sourcePath>
     *
     * @return string
     */
    public function getId()
    {
        return self::class . '|' . $this->getDir();
    }

    /**
     * @param string $package
     * @return RemotePackage
     * @throws PackageNotFoundException
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Validators\Exceptions\JsonDecodeException
     * @throws \Deplink\Validators\Exceptions\ValidationException
     * @throws \InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
     */
    public function get($package)
    {
        if (!$this->has($package)) {
            throw new PackageNotFoundException("Package '$package' not found in the local repository '{$this->dir}'.");
        }

        $path = $this->pathFor($package);
        $versionFinder = $this->versionFinderFactory->makeLocalPackageVersionFinder($path);
        $downloader = $this->downloaderFactory->makeLocal($path);

        return $this->packageFactory->makeRemote(
            $this,
            $versionFinder,
            $downloader
        );
    }

    /**
     * @param string $package
     * @return bool
     */
    public function has($package)
    {
        $dir = $this->pathFor($package);

        return $this->fs->existsDir($dir);
    }

    /**
     * Get directory for the package.
     *
     * @param string $package
     * @return string
     */
    private function pathFor($package)
    {
        return $this->fs->path($this->dir, $package);
    }

    /**
     * @return string;
     */
    public function getDir()
    {
        return $this->dir;
    }
}
