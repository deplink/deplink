<?php

namespace Deplink\Dependencies\Fixtures;

use Deplink\Dependencies\InstallingProgress;
use Deplink\Dependencies\ValueObjects\MissingDependencyObject;
use Deplink\Downloaders\DownloadingProgress;

class InstallingProgressForwarder implements DownloadingProgress
{
    /**
     * @var InstallingProgress
     */
    private $progress;

    /**
     * @var MissingDependencyObject
     */
    private $package;

    /**
     * UpdatingProgressFixture constructor.
     *
     * @param MissingDependencyObject $package
     * @param InstallingProgress $progress
     */
    public function __construct(MissingDependencyObject $package, InstallingProgress $progress)
    {
        $this->progress = $progress;
        $this->package = $package;
    }

    public function downloadingStarted()
    {
        // Should do nothing
    }

    /**
     * @param int $percentage In range 0-100, not guarantees that all percentages will be reported.
     */
    public function downloadingProgress($percentage)
    {
        $this->progress->installingProgress(
            $this->package->getName(),
            $this->package->getVersion(),
            $percentage
        );
    }

    public function downloadingSucceed()
    {
        // Should do nothing
    }

    /**
     * @param \Exception $e Exception which stopped the downloading process.
     */
    public function downloadingFailed(\Exception $e)
    {
        $this->progress->installingFailed(
            $this->package->getName(),
            $this->package->getVersion(),
            $e
        );
    }
}
