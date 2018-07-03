<?php

namespace Deplink\Downloaders\Providers;

use Deplink\Downloaders\Downloader;
use Deplink\Downloaders\DownloadingProgress;
use Deplink\Downloaders\Fixtures\DummyDownloadingProgress;
use Deplink\Environment\Cache;
use Deplink\Environment\Config;
use Deplink\Environment\Filesystem;
use Deplink\Environment\System;
use Deplink\Packages\LocalPackage;
use Deplink\Packages\PackageFactory;
use Deplink\Repositories\Exceptions\UnreachableRemoteRepositoryException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Config\Definition\Exception\Exception;
use ZipArchive;

/**
 * Download packages from remote repository address:
 * https://<host>/api/v1/@<package>/deplink.json (metadata)
 * https://<host>/api/v1/@<package>/download (full package)
 */
class RemoteDownloader implements Downloader
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
     * @var ClientInterface
     */
    private $client;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var string
     */
    private $packageName;

    /**
     * Destination directory
     *
     * @var string
     */
    private $destDir = null;

    /**
     * @var System
     */
    private $system;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param Filesystem $fs
     * @param PackageFactory $packageFactory
     * @param ClientInterface $client
     * @param Config $config
     * @param System $system
     * @param Cache $cache
     * @param string $baseUrl
     * @param string $packageName
     */
    public function __construct(
        Filesystem $fs,
        PackageFactory $packageFactory,
        ClientInterface $client,
        Config $config,
        System $system,
        Cache $cache,
        $baseUrl,
        $packageName
    ) {
        $this->fs = $fs;
        $this->packageFactory = $packageFactory;
        $this->client = $client;
        $this->config = $config;
        $this->baseUrl = $baseUrl;
        $this->packageName = $packageName;
        $this->system = $system;
        $this->cache = $cache;
    }

    /**
     * Source from which package can be download.
     *
     * @return string
     */
    public function from()
    {
        return "{$this->baseUrl}/api/v1/@{$this->packageName}/download";
    }

    /**
     * Set directory in which files will be stored
     * (directory will be created if not exists).
     *
     * @param string $dir
     * @return $this
     */
    public function to($dir)
    {
        $this->destDir = $dir;

        return $this;
    }

    /**
     * @param $version
     * @return string
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Environment\Exceptions\UnknownException
     */
    protected function getArchiveCachePath($version)
    {
        $dir = $this->config->get('cache.packages.remote.dir');
        $path = $this->cache->query("$dir/{$this->packageName}", [
            'url' => $this->baseUrl,
        ]);

        $this->fs->touchDir($path);
        return "$path/$version.zip";
    }

    /**
     * @param string $version
     * @param DownloadingProgress|null $progress
     * @return string|false Output directory if downloaded successfully, false otherwise.
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Environment\Exceptions\UnknownException
     * @throws \Deplink\Environment\Exceptions\FileCopyException
     */
    public function download($version, DownloadingProgress $progress = null)
    {
        // Assign dummy progress listener to avoid errors
        if (is_null($progress)) {
            $progress = new DummyDownloadingProgress();
        }

        // Download package (0-80%)
        $progress->downloadingStarted();
        $archiveFile = $this->getArchiveCachePath($version);
        if(!$this->fs->existsFile($archiveFile)) {
            try {
                $this->downloadPackage($version, $progress);
            } catch (\Exception $e) {
                $progress->downloadingFailed($e);
                return false;
            } catch (GuzzleException $e) {
                $progress->downloadingFailed($e);
                return false;
            }
        }

        // Extract archive (80-100%)
        $this->extractArchive($version, $progress);
        $progress->downloadingSucceed();

        return $this->destDir;
    }

    /**
     * Download only deplink.json file.
     *
     * @param string $version
     * @return LocalPackage
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \RuntimeException
     * @throws UnreachableRemoteRepositoryException
     */
    public function requestDetails($version)
    {
        // Return cached deplink.json file.
        try {
            $archiveFile = $this->getArchiveCachePath($version);
            if ($this->fs->existsFile($archiveFile)) {
                $zip = new ZipArchive;
                $zip->open($archiveFile);
                $json = $zip->getFromName('deplink.json');
                return $this->packageFactory->makeFromJson($json);
            }
        }
        catch(\Exception $e) {
            // Do nothing, but it means that something went wrong while retrieving cached version.
            // Details will be retrieved directly from the remote repository.
        }

        // Remove prefix ("v" or "v.") from version number.
        $version = preg_replace('/^(v\.|v)/i', '', $version);

        // Request deplink.json file for specific package version.
        $uri = "{$this->baseUrl}/api/v1/@{$this->packageName}/$version/deplink.json";
        $response = $this->client->request('get', $uri);
        if ($response->getStatusCode() !== 200) {
            throw new UnreachableRemoteRepositoryException("Accessing remote repository endpoint $uri returned status code {$response->getStatusCode()}, the 200 status code expected.");
        }

        try {
            // Parse json content, update cache and return value.
            $json = json_decode($response->getBody()->getContents());
            $this->cached[$version] = $this->packageFactory->makeFromJson($json->data);
            return $this->cached[$version];
        } catch (\Exception $e) {
            $body = $response->getBody()->getContents();
            throw new UnreachableRemoteRepositoryException("Cannot parse body '$body' returned by remote repository endpoint $uri.");
        }
    }

    /**
     * @param $version
     * @param DownloadingProgress $progress
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Environment\Exceptions\UnknownException
     */
    private function extractArchive($version, DownloadingProgress $progress)
    {
        $zip = new ZipArchive;
        $zipPath = $this->getArchiveCachePath($version);
        if ($zip->open($zipPath) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $progress->downloadingProgress(80 + floor($i / $zip->numFiles * 20));
                $filePath = $zip->getNameIndex($i);

                $srcLastChar = substr($filePath, -1, 1);
                $isFile = $srcLastChar !== '/' && $srcLastChar !== '\\';
                if ($isFile) {
                    $destPath = $this->fs->path($this->destDir, $filePath);
                    $this->fs->writeFile($destPath, $zip->getFromIndex($i));
                }
            }

            $zip->close();
        }
    }

    /**
     * @param $version
     * @param DownloadingProgress $progress
     * @throws GuzzleException
     * @throws UnreachableRemoteRepositoryException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Environment\Exceptions\UnknownException
     */
    private function downloadPackage($version, DownloadingProgress $progress)
    {
        $uri = "{$this->baseUrl}/api/v1/@{$this->packageName}/$version/download";
        $response = $this->client->request('get', $uri, [
            'sink' => $this->getArchiveCachePath($version),
            'progress' => function ($downloadTotal, $downloadedBytes) use ($progress) {
                if ($downloadTotal <= 0) {
                    return; // FIXME: Check why Guzzle sometimes pass zeros.
                }

                $progress->downloadingProgress(floor($downloadedBytes / $downloadTotal * 80));
            },
        ]);

        // Report failed downloading
        if ($response->getStatusCode() !== 200) {
            throw new UnreachableRemoteRepositoryException("Accessing remote repository endpoint $uri returned status code {$response->getStatusCode()}, the 200 status code expected.");
        }
    }
}
