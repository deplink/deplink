<?php

namespace Deplink\Dependencies\Fixtures;

use Deplink\Dependencies\InstallingProgress;
use Deplink\Dependencies\ValueObjects\OutdatedDependencyObject;
use Deplink\Downloaders\DownloadingProgress;

class UpdatingProgressForwarder implements DownloadingProgress
{
    /**
     * @var InstallingProgress
     */
    private $progress;

    /**
     * @var OutdatedDependencyObject
     */
    private $package;

    /**
     * UpdatingProgressFixture constructor.
     *
     * @param OutdatedDependencyObject $package
     * @param InstallingProgress $progress
     */
    public function __construct(OutdatedDependencyObject $package, InstallingProgress $progress)
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
        $this->progress->updatingProgress(
            $this->package->getName(),
            $this->package->getSourceVersion(),
            $this->package->getTargetVersion(),
            $percentage
        );
    }

    public function downloadingSucceed()
    {
        $this->progress->updatingSucceed(
            $this->package->getName(),
            $this->package->getSourceVersion(),
            $this->package->getTargetVersion()
        );
    }

    /**
     * @param \Exception $e Exception which stopped the downloading process.
     */
    public function downloadingFailed(\Exception $e)
    {
        $this->progress->updatingFailed(
            $this->package->getName(),
            $this->package->getSourceVersion(),
            $this->package->getTargetVersion(),
            $e
        );
    }
}
