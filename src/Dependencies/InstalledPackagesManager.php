<?php

namespace Deplink\Dependencies;

use Deplink\Environment\Config;
use Deplink\Environment\Filesystem;
use Deplink\Locks\LockFactory;

/**
 * List installed dependencies from structure in deplinks directory
 * (their versions are stored in installed.lock file inside deplinks directory).
 */
class InstalledPackagesManager
{
    /**
     * @var DependenciesCollection
     */
    protected $installed;

    /**
     * @var string[]
     */
    protected $ambiguous = [];

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var LockFactory
     */
    private $lockFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * Observer constructor.
     *
     * @param Filesystem $fs
     * @param LockFactory $lockFactory
     * @param Config $config
     */
    public function __construct(Filesystem $fs, LockFactory $lockFactory, Config $config)
    {
        $this->fs = $fs;
        $this->lockFactory = $lockFactory;
        $this->config = $config;
    }

    /**
     * @return array Package name (key) with installed version (value).
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Validators\Exceptions\JsonDecodeException
     * @throws \Deplink\Validators\Exceptions\ValidationException
     * @throws \InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
     */
    private function listLockedPackages()
    {
        $installed = $this->lockFactory->makeFromFileOrEmpty('deplinks/installed.lock');
        return $installed->packages();
    }

    /**
     * @return \string[] Packages names.
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     */
    private function listDownloadedPackages()
    {
        $outputDir = 'deplinks';
        $dir2package = function ($dir) use ($outputDir) {
            return substr($dir, strlen("$outputDir/"));
        };

        $cmodules = $this->fs->listDirs($outputDir, 2);
        $packages = array_map($dir2package, $cmodules);

        return $packages;
    }

    /**
     * Make snapshot of the installed packages.
     *
     * @return $this
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Validators\Exceptions\JsonDecodeException
     * @throws \Deplink\Validators\Exceptions\ValidationException
     * @throws \InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
     */
    public function snapshot()
    {
        $locked = $this->listLockedPackages();
        $downloaded = $this->listDownloadedPackages();

        // Detect ambiguous packages.
        $installedPackagesNames = array_keys($locked);
        $this->ambiguous = array_merge(
            array_diff($installedPackagesNames, $downloaded), // Marked as installed, but not exists in dir structure.
            array_diff($downloaded, $installedPackagesNames) // Installed, but not contains information about version.
        );

        // Mark other packages as installed.
        $installed = array_filter($locked, function ($key) use ($downloaded) {
            return array_search($key, $downloaded) !== false;
        }, ARRAY_FILTER_USE_KEY);

        $this->installed = new DependenciesCollection();
        foreach ($installed as $name => $version) {
            $this->installed->add($name, $version);
        }

        return $this;
    }

    /**
     * Get names of packages which are:
     * - installed, but version cannot be determined,
     * - or not installed, but listed as an installed ones.
     *
     * @return string[]
     */
    public function getAmbiguous()
    {
        return $this->ambiguous;
    }

    /**
     * @return DependenciesCollection
     */
    public function getInstalled()
    {
        return $this->installed;
    }

    /**
     * Check whether given package is installed.
     *
     * @param string $package
     * @return bool
     */
    public function hasInstalled($package)
    {
        return $this->installed->has($package);
    }
}
