<?php

namespace Deplink\Dependencies\Fixtures;

use Deplink\Dependencies\InstallingProgress;

class DummyInstallingProgress implements InstallingProgress
{
    /**
     * @param int $installs
     * @param int $updates
     * @param int $removals
     */
    public function beforeInstallation($installs, $updates, $removals)
    {
        // Should do nothing
    }

    /**
     * @param string $packageName
     */
    public function removingStarted($packageName)
    {
        // Should do nothing
    }

    /**
     * @param string $packageName
     * @param int $percentage In range 0-100, not guarantees that all percentages will be reported.
     */
    public function removingProgress($packageName, $percentage)
    {
        // Should do nothing
    }

    /**
     * @param string $packageName
     */
    public function removingSucceed($packageName)
    {
        // Should do nothing
    }

    /**
     * @param string $packageName
     * @param \Exception $e Exception which stopped the downloading process.
     */
    public function removingFailed($packageName, \Exception $e)
    {
        // Should do nothing
    }

    /**
     * @param string $packageName
     * @param string $sourceVersion
     * @param string $targetVersion
     */
    public function updatingStarted($packageName, $sourceVersion, $targetVersion)
    {
        // Should do nothing
    }

    /**
     * @param string $packageName
     * @param string $sourceVersion
     * @param string $targetVersion
     * @param int $percentage In range 0-100, not guarantees that all percentages will be reported.
     */
    public function updatingProgress($packageName, $sourceVersion, $targetVersion, $percentage)
    {
        // Should do nothing
    }

    /**
     * @param string $packageName
     * @param string $sourceVersion
     * @param string $targetVersion
     */
    public function updatingSucceed($packageName, $sourceVersion, $targetVersion)
    {
        // Should do nothing
    }

    /**
     * @param string $packageName
     * @param string $sourceVersion
     * @param string $targetVersion
     * @param \Exception $e Exception which stopped the downloading process.
     */
    public function updatingFailed($packageName, $sourceVersion, $targetVersion, \Exception $e)
    {
        // Should do nothing
    }

    /**
     * @param string $packageName
     * @param string $version
     */
    public function installingStarted($packageName, $version)
    {
        // Should do nothing
    }

    /**
     * @param string $packageName
     * @param string $version
     * @param int $percentage In range 0-100, not guarantees that all percentages will be reported.
     */
    public function installingProgress($packageName, $version, $percentage)
    {
        // Should do nothing
    }

    /**
     * @param string $packageName
     * @param string $version
     */
    public function installingSucceed($packageName, $version)
    {
        // Should do nothing
    }

    /**
     * @param string $packageName
     * @param string $version
     * @param \Exception $e Exception which stopped the downloading process.
     */
    public function installingFailed($packageName, $version, \Exception $e)
    {
        // Should do nothing
    }

    public function afterInstallation()
    {
        // Should do nothing
    }
}
