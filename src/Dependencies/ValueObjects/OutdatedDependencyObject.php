<?php

namespace Deplink\Dependencies\ValueObjects;

use Deplink\Packages\RemotePackage;

class OutdatedDependencyObject
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $sourceVersion;

    /**
     * @var string
     */
    private $targetVersion;

    /**
     * @var RemotePackage
     */
    private $remote;

    /**
     * OutdatedDependencyObject constructor.
     *
     * @param string $name
     * @param string $sourceVersion
     * @param string $targetVersion
     * @param RemotePackage $remote
     */
    public function __construct($name, $sourceVersion, $targetVersion, RemotePackage $remote)
    {
        $this->name = $name;
        $this->sourceVersion = $sourceVersion;
        $this->targetVersion = $targetVersion;
        $this->remote = $remote;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return OutdatedDependencyObject
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getSourceVersion()
    {
        return $this->sourceVersion;
    }

    /**
     * @param string $sourceVersion
     * @return OutdatedDependencyObject
     */
    public function setSourceVersion($sourceVersion)
    {
        $this->sourceVersion = $sourceVersion;
        return $this;
    }

    /**
     * @return string
     */
    public function getTargetVersion()
    {
        return $this->targetVersion;
    }

    /**
     * @param string $targetVersion
     * @return OutdatedDependencyObject
     */
    public function setTargetVersion($targetVersion)
    {
        $this->targetVersion = $targetVersion;
        return $this;
    }

    /**
     * @return RemotePackage
     */
    public function getRemote()
    {
        return $this->remote;
    }

    /**
     * @param RemotePackage $remote
     * @return OutdatedDependencyObject
     */
    public function setRemote($remote)
    {
        $this->remote = $remote;
        return $this;
    }
}
