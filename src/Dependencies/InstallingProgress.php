<?php

namespace Deplink\Dependencies;

interface InstallingProgress
{
    /**
     * @param int $installs
     * @param int $updates
     * @param int $removals
     */
    public function beforeInstallation($installs, $updates, $removals);

    /**
     * @param string $packageName
     */
    public function removingStarted($packageName);

    /**
     * @param string $packageName
     * @param int $percentage In range 0-100, not guarantees that all percentages will be reported.
     */
    public function removingProgress($packageName, $percentage);

    /**
     * @param string $packageName
     */
    public function removingSucceed($packageName);

    /**
     * @param string $packageName
     * @param \Exception $e Exception which stopped the downloading process.
     */
    public function removingFailed($packageName, \Exception $e);

    /**
     * @param string $packageName
     * @param string $sourceVersion
     * @param string $targetVersion
     */
    public function updatingStarted($packageName, $sourceVersion, $targetVersion);

    /**
     * @param string $packageName
     * @param string $sourceVersion
     * @param string $targetVersion
     * @param int $percentage In range 0-100, not guarantees that all percentages will be reported.
     */
    public function updatingProgress($packageName, $sourceVersion, $targetVersion, $percentage);

    /**
     * @param string $packageName
     * @param string $sourceVersion
     * @param string $targetVersion
     */
    public function updatingSucceed($packageName, $sourceVersion, $targetVersion);

    /**
     * @param string $packageName
     * @param string $sourceVersion
     * @param string $targetVersion
     * @param \Exception $e Exception which stopped the downloading process.
     */
    public function updatingFailed($packageName, $sourceVersion, $targetVersion, \Exception $e);

    /**
     * @param string $packageName
     * @param string $version
     */
    public function installingStarted($packageName, $version);

    /**
     * @param string $packageName
     * @param string $version
     * @param int $percentage In range 0-100, not guarantees that all percentages will be reported.
     */
    public function installingProgress($packageName, $version, $percentage);

    /**
     * @param string $packageName
     * @param string $version
     */
    public function installingSucceed($packageName, $version);

    /**
     * @param string $packageName
     * @param string $version
     * @param \Exception $e Exception which stopped the downloading process.
     */
    public function installingFailed($packageName, $version, \Exception $e);

    public function afterInstallation();
}
