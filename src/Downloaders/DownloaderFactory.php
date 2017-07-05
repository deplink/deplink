<?php

namespace Deplink\Downloaders;

use Deplink\Downloaders\Providers\LocalDownloader;
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
     * @param $dir
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
}
