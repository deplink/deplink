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
            $this->output->write("  - Installing <info>$packageName</info> (<info>$version</info>)... $percentage%");
        }
    }

    /**
     * @param string $packageName
     * @param string $version
     */
    public function installingSucceed($packageName, $version)
    {
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
