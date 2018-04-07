<?php

namespace Deplink\Console\Commands\Output;

use Deplink\Dependencies\InstallingProgress;
use Symfony\Component\Console\Output\OutputInterface;

class InstallationProgressFormater implements InstallingProgress
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var bool
     */
    private $trackProgress;

    /**
     * Get versions prefixed with "v" char.
     *
     * @param string $version
     * @return string
     */
    private function normalizeVersion($version)
    {
        // Remove prefix ("v" or "v.") from version number.
        $version = preg_replace('/^(v\.|v)/i', '', $version);

        return "v$version";
    }

    /**
     * InstallationProgressFormater constructor.
     *
     * @param OutputInterface $output
     * @param boolean $trackProgress
     */
    public function __construct(OutputInterface $output, $trackProgress)
    {
        $this->output = $output;
        $this->trackProgress = $trackProgress;
    }

    /**
     * @param int $installs
     * @param int $updates
     * @param int $removals
     */
    public function beforeInstallation($installs, $updates, $removals)
    {
        $this->output->writeln("Dependencies: <info>$installs installs</info>, <info>$updates updates</info>, <info>$removals removals</info>");
    }

    /**
     * @param string $packageName
     */
    public function removingStarted($packageName)
    {
        if ($this->trackProgress) {
            $this->output->write("  - Removing <info>$packageName</info>");
        }
    }

    /**
     * @param string $packageName
     * @param int $percentage In range 0-100, not guarantees that all percentages will be reported.
     */
    public function removingProgress($packageName, $percentage)
    {
        if ($this->trackProgress) {
            $this->output->write("\r  - Removing <info>$packageName</info>... $percentage%");
        }
    }

    /**
     * @param string $packageName
     */
    public function removingSucceed($packageName)
    {
        // The 8 spaces at the end overwrites the "... 100%" part.
        $this->output->writeln("\r  - Removing <info>$packageName</info>        ");
    }

    /**
     * @param string $packageName
     * @param \Exception $e Exception which stopped the downloading process.
     * @throws \Exception
     */
    public function removingFailed($packageName, \Exception $e)
    {
        throw $e;
    }

    /**
     * @param string $packageName
     * @param string $sourceVersion
     * @param string $targetVersion
     */
    public function updatingStarted($packageName, $sourceVersion, $targetVersion)
    {
        if ($this->trackProgress) {
            $sourceVersion = $this->normalizeVersion($sourceVersion);
            $targetVersion = $this->normalizeVersion($targetVersion);

            $this->output->write("  - Updating <info>$packageName</info> (<info>$sourceVersion -> $targetVersion</info>)");
        }
    }

    /**
     * @param string $packageName
     * @param string $sourceVersion
     * @param string $targetVersion
     * @param int $percentage In range 0-100, not guarantees that all percentages will be reported.
     */
    public function updatingProgress($packageName, $sourceVersion, $targetVersion, $percentage)
    {
        if ($this->trackProgress) {
            $sourceVersion = $this->normalizeVersion($sourceVersion);
            $targetVersion = $this->normalizeVersion($targetVersion);

            $this->output->write("  - Updating <info>$packageName</info> (<info>$sourceVersion -> $targetVersion</info>)... $percentage%");
        }
    }

    /**
     * @param string $packageName
     * @param string $sourceVersion
     * @param string $targetVersion
     */
    public function updatingSucceed($packageName, $sourceVersion, $targetVersion)
    {
        $sourceVersion = $this->normalizeVersion($sourceVersion);
        $targetVersion = $this->normalizeVersion($targetVersion);

        // The 8 spaces at the end overwrites the "... 100%" part.
        $this->output->writeln("\r  - Updating <info>$packageName</info> (<info>$sourceVersion -> $targetVersion</info>)        ");
    }

    /**
     * @param string $packageName
     * @param string $sourceVersion
     * @param string $targetVersion
     * @param \Exception $e Exception which stopped the downloading process.
     * @throws \Exception
     */
    public function updatingFailed($packageName, $sourceVersion, $targetVersion, \Exception $e)
    {
        throw $e;
    }

    /**
     * @param string $packageName
     * @param string $version
     */
    public function installingStarted($packageName, $version)
    {
        if ($this->trackProgress) {
            $version = $this->normalizeVersion($version);

            $this->output->write("  - Installing <info>$packageName</info> (<info>$version</info>)");
        }
    }

    /**
     * @param string $packageName
     * @param string $version
     * @param int $percentage In range 0-100, not guarantees that all percentages will be reported.
     */
    public function installingProgress($packageName, $version, $percentage)
    {
        if ($this->trackProgress) {
            $version = $this->normalizeVersion($version);

            $this->output->write("  - Installing <info>$packageName</info> (<info>$version</info>)... $percentage%");
        }
    }

    /**
     * @param string $packageName
     * @param string $version
     */
    public function installingSucceed($packageName, $version)
    {
        $version = $this->normalizeVersion($version);

        // The 8 spaces at the end overwrites the "... 100%" part.
        $this->output->writeln("\r  - Installing <info>$packageName</info> (<info>$version</info>)        ");
    }

    /**
     * @param string $packageName
     * @param string $version
     * @param \Exception $e Exception which stopped the downloading process.
     * @throws \Exception
     */
    public function installingFailed($packageName, $version, \Exception $e)
    {
        throw $e;
    }

    public function afterInstallation()
    {
        // Should do nothing
    }
}
