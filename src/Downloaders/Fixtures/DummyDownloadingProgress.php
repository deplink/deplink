<?php

namespace Deplink\Downloaders\Fixtures;

use Deplink\Downloaders\DownloadingProgress;

class DummyDownloadingProgress implements DownloadingProgress
{
    public function downloadingStarted()
    {
        // Should do nothing
    }

    /**
     * @param int $percentage In range 0-100, not guarantees that all percentages will be reported.
     */
    public function downloadingProgress($percentage)
    {
        // Should do nothing
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
        // Should do nothing
    }
}
