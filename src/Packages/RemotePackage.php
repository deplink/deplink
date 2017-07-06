<?php

namespace Deplink\Packages;

use Deplink\Downloaders\Downloader;
use Deplink\Repositories\Repository;
use Deplink\Versions\VersionFinder;

/**
 * Represents package located on the repository.
 *
 * @see LocalPackage
 * @see Repository
 */
class RemotePackage
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var VersionFinder
     */
    private $versionFinder;

    /**
     * @var Downloader
     */
    private $downloader;

    /**
     * @var LocalPackage
     */
    private $package;

    /**
     * Remote constructor.
     *
     * @param Repository $repository
     * @param VersionFinder $versionFinder
     * @param Downloader $downloader
     * @param LocalPackage $package
     */
    public function __construct(
        Repository $repository,
        VersionFinder $versionFinder,
        Downloader $downloader,
        LocalPackage $package
    ) {
        $this->repository = $repository;
        $this->versionFinder = $versionFinder;
        $this->downloader = $downloader;
        $this->package = $package;
    }

    /**
     * @return Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @return LocalPackage
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * @return Downloader
     */
    public function getDownloader()
    {
        return $this->downloader;
    }

    /**
     * @return VersionFinder
     */
    public function getVersionFinder()
    {
        return $this->versionFinder;
    }
}
