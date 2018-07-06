<?php

namespace Deplink\Repositories\Providers;

use Deplink\Downloaders\DownloaderFactory;
use Deplink\Environment\Cache;
use Deplink\Environment\Config;
use Deplink\Environment\Filesystem;
use Deplink\Packages\PackageFactory;
use Deplink\Packages\RemotePackage;
use Deplink\Repositories\Exceptions\PackageNotFoundException;
use Deplink\Repositories\Exceptions\UnreachableRemoteRepositoryException;
use Deplink\Repositories\Repository;
use Deplink\Versions\VersionFinderFactory;
use GuzzleHttp\ClientInterface;

/**
 * Remote repository (also called online repository) stores information about
 * available packages on the server. Communication with server is done using
 * the HTTPS protocol.
 *
 * Example of such repository is the Official Online Repository which is by
 * default used as a fallback repository. To use official remote repository
 * you don't have to add any entry to the repositories property, it's
 * enabled by default.
 *
 * There's also a possibility to create private remote repository. Instruction
 * how to install and use private repositories you can be find in a special article:
 * https://deplink.org/docs/guide/private-repository
 */
class RemoteRepository implements Repository
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var PackageFactory
     */
    private $packageFactory;

    /**
     * @var VersionFinderFactory
     */
    private $versionFinderFactory;

    /**
     * @var DownloaderFactory
     */
    private $downloaderFactory;

    /**
     * Full remote repository address
     * (e.g. https://repo.deplink.org/).
     *
     * @var string
     */
    private $url;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * LocalRepository constructor.
     *
     * @param ClientInterface $client
     * @param PackageFactory $packageFactory
     * @param VersionFinderFactory $versionFinderFactory
     * @param DownloaderFactory $downloaderFactory
     * @param Cache $cache
     * @param Config $config
     * @param Filesystem $fs
     * @param string $src Full repository address (e.g. https://repo.deplink.org/)
     */
    public function __construct(
        ClientInterface $client,
        PackageFactory $packageFactory,
        VersionFinderFactory $versionFinderFactory,
        DownloaderFactory $downloaderFactory,
        Cache $cache,
        Config $config,
        Filesystem $fs,
        $src
    ) {
        $this->client = $client;
        $this->packageFactory = $packageFactory;
        $this->versionFinderFactory = $versionFinderFactory;
        $this->downloaderFactory = $downloaderFactory;
        $this->cache = $cache;
        $this->config = $config;
        $this->fs = $fs;
        $this->url = $src;
    }

    /**
     * @param string $package
     * @return string
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     */
    protected function getArchiveCachePath($package)
    {
        $dir = $this->config->get('cache.packages.remote.dir');
        $path = $this->cache->query("$dir/$package", [
            'url' => $this->url,
        ]);

        return "$path";
    }

    /**
     * @param $package
     * @return bool
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     */
    private function hasInCache($package)
    {
        $archiveDir = $this->getArchiveCachePath($package);
        if(!$this->fs->existsDir($archiveDir)) {
            return false;
        }

        return !empty($this->fs->listFiles($archiveDir));
    }

    /**
     * @param string $package
     * @return bool
     * @throws UnreachableRemoteRepositoryException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \RuntimeException
     */
    public function has($package)
    {
        if ($this->hasInCache($package)) {
            return true;
        }

        $uri = "{$this->url}/api/v1/@$package";
        $response = $this->client->request('options', $uri); // FIXME: , ['verify' => false]
        if ($response->getStatusCode() !== 200) {
            throw new UnreachableRemoteRepositoryException("Accessing remote repository endpoint $uri returned status code {$response->getStatusCode()}, the 200 status code expected.");
        }

        try {
            $json = json_decode($response->getBody()->getContents());
            return $json->data->exists;
        } catch (\Exception $e) {
            $body = $response->getBody()->getContents();
            throw new UnreachableRemoteRepositoryException("Cannot parse body '$body' returned by remote repository endpoint $uri.");
        }
    }

    /**
     * @param string $package
     * @return RemotePackage
     * @throws PackageNotFoundException
     * @throws UnreachableRemoteRepositoryException
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function get($package)
    {
        if (!$this->has($package)) {
            throw new PackageNotFoundException("Package '$package' not found in the remote repository '{$this->url}'.");
        }

        $versionFinder = $this->versionFinderFactory->makeRemote($this->url, $package);
        $downloader = $this->downloaderFactory->makeRemote($this->url, $package);

        return $this->packageFactory->makeRemote(
            $this,
            $versionFinder,
            $downloader
        );
    }
}
