<?php

namespace Deplink\Versions\Providers;

use Deplink\Repositories\Exceptions\UnreachableRemoteRepositoryException;
use Deplink\Versions\VersionComparator;
use GuzzleHttp\ClientInterface;

/**
 * Retrieve available package versions from remote repository address:
 * https://<host>/api/v1/@<package>/versions
 *
 * Versions once downloaded will be cached and reused.
 */
class RemoteRepositoryVersionFinder extends BaseVersionFinder
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var string
     */
    private $packageName;

    /**
     * @var string[]
     */
    private $versions = null;

    /**
     * LocalPackageVersionFinder constructor.
     *
     * @param VersionComparator $comparator
     * @param ClientInterface $client
     * @param string $baseUrl
     * @param string $packageName
     */
    public function __construct(VersionComparator $comparator, ClientInterface $client, $baseUrl, $packageName)
    {
        $this->client = $client;
        $this->baseUrl = $baseUrl;
        $this->packageName = $packageName;

        parent::__construct($comparator);
    }

    /**
     * Get all available package versions.
     *
     * @return string[]
     * @throws UnreachableRemoteRepositoryException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \RuntimeException
     */
    public function get()
    {
        if (is_null($this->versions)) {
            $this->versions = $this->requestVersions();
        }

        return $this->versions;
    }

    /**
     * Get latest available string.
     *
     * @return string
     * @throws UnreachableRemoteRepositoryException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \RuntimeException
     */
    public function latest()
    {
        return array_pop($this->comparator->sort($this->get()));
    }

    /**
     * Request endpoint to retrieve package available versions.
     *
     * @return string[]
     * @throws UnreachableRemoteRepositoryException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \RuntimeException
     */
    private function requestVersions()
    {
        $uri = "{$this->baseUrl}/api/v1/@{$this->packageName}/versions";
        $response = $this->client->request('get', $uri);
        if ($response->getStatusCode() !== 200) {
            throw new UnreachableRemoteRepositoryException("Accessing remote repository endpoint $uri returned status code {$response->getStatusCode()}, the 200 status code expected.");
        }

        try {
            $json = json_decode($response->getBody()->getContents());
            return $json->data;
        } catch (\Exception $e) {
            $body = $response->getBody()->getContents();
            throw new UnreachableRemoteRepositoryException("Cannot parse body '$body' returned by remote repository endpoint $uri.");
        }
    }
}
