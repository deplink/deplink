<?php

namespace Deplink\Downloaders\Providers;

use Deplink\Downloaders\Downloader;
use Deplink\Downloaders\DownloadingProgress;
use Deplink\Downloaders\Exceptions\DestinationNotExistsException;
use Deplink\Downloaders\Exceptions\SourceNotExistsException;
use Deplink\Downloaders\Fixtures\DummyDownloadingProgress;
use Deplink\Environment\Filesystem;
use Deplink\Packages\LocalPackage;
use Deplink\Packages\PackageFactory;

class LocalDownloader implements Downloader
{
    /**
     * @var string
     */
    private $srcDir;

    /**
     * Destination directory
     *
     * @var string
     */
    private $destDir = null;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var PackageFactory
     */
    private $packageFactory;

    /**
     * LocalDownloader constructor.
     *
     * @param Filesystem $fs
     * @param PackageFactory $packageFactory
     * @param string $src
     */
    public function __construct(Filesystem $fs, PackageFactory $packageFactory, $src)
    {
        $this->srcDir = $src;
        $this->packageFactory = $packageFactory;
        $this->fs = $fs;
    }

    /**
     * Source from which package can be download.
     *
     * @return string
     */
    public function from()
    {
        return $this->srcDir;
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
     * Local downloader just point to the directory outside
     * the project directory without downloading/copying it.
     *
     * This improves development process of packages in progress
     * which still has not been published (there're always synced).
     *
     * @param string $version
     * @param DownloadingProgress|null $progress
     * @return false|string Output directory if downloaded successfully, false otherwise.
     * @throws DestinationNotExistsException
     * @throws SourceNotExistsException
     */
    public function download($version, DownloadingProgress $progress = null)
    {
        // Assign dummy progress listener to avoid errors
        if (is_null($progress)) {
            $progress = new DummyDownloadingProgress();
        }

        // Check source directory
        if (!$this->fs->existsDir($this->from())) {
            throw new SourceNotExistsException("Cannot point to local package '{$this->from()}' (directory not exists).");
        }

        // Check destination directory
        if (is_null($this->destDir)) {
            throw new DestinationNotExistsException("Cannot download package to '{$this->destDir}' (directory not exists).");
        }

        // Copy source files
        try {
            $progress->downloadingStarted();

            // Get all files to copy (relative paths)
            $files = $this->fs->listFiles($this->from());
            $files = array_map(function ($item) {
                return mb_substr($item, mb_strlen($this->from()));
            }, $files);

            $copiedCounter = 0;
            $filesCount = count($files);
            foreach ($files as $file) {
                $progress->downloadingProgress(100 * $copiedCounter / $filesCount);

                $from = $this->fs->path($this->from(), $file);
                $to = $this->fs->path($this->destDir, $file);

                $this->fs->copyFile($from, $to);
                $copiedCounter++;
            }

            $progress->downloadingSucceed();
        } catch (\Exception $e) {
            $progress->downloadingFailed($e);
        }

        return $this->destDir;
    }

    /**
     * Download only deplink.json file.
     *
     * @param string $version
     * @return LocalPackage
     * @throws SourceNotExistsException
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Validators\Exceptions\JsonDecodeException
     * @throws \Deplink\Validators\Exceptions\ValidationException
     * @throws \InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
     */
    public function requestDetails($version)
    {
        // Check source directory
        if (!$this->fs->existsDir($this->from())) {
            throw new SourceNotExistsException("Cannot point to local package '{$this->from()}' (directory not exists).");
        }

        return $this->packageFactory->makeFromDir($this->from());
    }
}
