<?php

namespace Deplink\Dependencies\ValueObjects;

use Deplink\Packages\RemotePackage;

class MissingDependencyObject
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $version;

    /**
     * @var RemotePackage
     */
    private $remote;

    /**
     * MissingDependencyObject constructor.
     *
     * @param string $name
     * @param string $version
     * @param RemotePackage $remote
     */
    public function __construct($name, $version, RemotePackage $remote)
    {
        $this->name = $name;
        $this->version = $version;
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
     * @return MissingDependencyObject
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return MissingDependencyObject
     */
    public function setVersion($version)
    {
        $this->version = $version;
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
     * @return MissingDependencyObject
     */
    public function setRemote($remote)
    {
        $this->remote = $remote;
        return $this;
    }
}
