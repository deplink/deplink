<?php

namespace Deplink\Downloaders;

interface DownloadingProgress
{
    public function downloadingStarted();

    /**
     * @param int $percentage In range 0-100, not guarantees that all percentages will be reported.
     */
    public function downloadingProgress($percentage);

    public function downloadingSucceed();

    /**
     * @param \Exception $e Exception which stopped the downloading process.
     */
    public function downloadingFailed(\Exception $e);
}
