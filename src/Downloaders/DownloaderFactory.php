<?php

namespace Deplink\Downloaders;

use Deplink\Downloaders\Providers\LocalDownloader;
use Deplink\Downloaders\Providers\RemoteDownloader;
use DI\Container;

class DownloaderFactory
{
    /**
     * @var Container
     */
    private $di;

    /**
     * Factory constructor.
     *
     * @param Container $di
     */
    public function __construct(Container $di)
    {
        $this->di = $di;
    }

    /**
     * @param string $dir
     * @return LocalDownloader
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \InvalidArgumentException
     */
    public function makeLocal($dir)
    {
        return $this->di->make(LocalDownloader::class, [
            'src' => $dir,
        ]);
    }

    /**
     * @param string $baseUrl
     * @param string $packageName
     * @return RemoteDownloader
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \InvalidArgumentException
     */
    public function makeRemote($baseUrl, $packageName)
    {
        return $this->di->make(RemoteDownloader::class, [
            'baseUrl' => $baseUrl,
            'packageName' => $packageName,
        ]);
    }
}
