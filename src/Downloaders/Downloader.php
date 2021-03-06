<?php

namespace Deplink\Downloaders;

use Deplink\Packages\LocalPackage;

interface Downloader
{
    /**
     * Source from which package can be download.
     *
     * @return string
     */
    public function from();

    /**
     * Set directory in which files will be stored
     * (directory will be created if not exists).
     *
     * @param string $dir
     * @return $this
     */
    public function to($dir);

    /**
     * @param string $version
     * @param DownloadingProgress|null $progress
     * @return string|false Output directory if downloaded successfully, false otherwise.
     */
    public function download($version, DownloadingProgress $progress = null);

    /**
     * Download only deplink.json file.
     *
     * @param string $version
     * @return LocalPackage
     */
    public function requestDetails($version);
}
