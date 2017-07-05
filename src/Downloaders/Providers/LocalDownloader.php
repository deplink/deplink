<?php

namespace Deplink\Downloaders\Providers;

use Deplink\Downloaders\Downloader;
use Deplink\Downloaders\DownloadingProgress;
use Deplink\Downloaders\Exceptions\DestinationNotExistsException;
use Deplink\Downloaders\Exceptions\SourceNotExistsException;
use Deplink\Downloaders\Fixtures\DummyDownloadingProgress;
use Deplink\Environment\Filesystem;

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
     * LocalDownloader constructor.
     *
     * @param Filesystem $fs
     * @param string $src
     */
    public function __construct(Filesystem $fs, $src)
    {
        $this->srcDir = $src;
        $this->fs = $fs;
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
        if (!is_callable($progress)) {
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
            foreach ($files as &$file) {
                $file = mb_substr($file, mb_strlen($this->from()));
            }

            $copiedCounter = 0;
            $filesCount = count($files);
            foreach ($files as $file) {
                $progress->downloadingProgress($copiedCounter / $filesCount);

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
     * Source from which package can be download.
     *
     * @return string
     */
    public function from()
    {
        return $this->srcDir;
    }
}
