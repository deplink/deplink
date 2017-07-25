<?php

namespace Deplink\Dependencies\ValueObjects;

use Deplink\Packages\LocalPackage;
use Deplink\Packages\RemotePackage;

class DependencyObject
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
     * @var LocalPackage
     */
    private $local;

    /**
     * @var RemotePackage
     */
    private $remote;

    /**
     * DependencyObject constructor.
     *
     * @param string $name
     * @param string $version
     * @param LocalPackage|null $local
     * @param RemotePackage|null $remote
     */
    public function __construct($name, $version, LocalPackage $local = null, RemotePackage $remote = null)
    {
        $this->name = $name;
        $this->version = $version;
        $this->local = $local;
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
     * @return DependencyObject
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
     * @return DependencyObject
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return LocalPackage
     */
    public function getLocal()
    {
        return $this->local;
    }

    /**
     * @param LocalPackage $local
     * @return DependencyObject
     */
    public function setLocal($local)
    {
        $this->local = $local;
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
     * @return DependencyObject
     */
    public function setRemote($remote)
    {
        $this->remote = $remote;
        return $this;
    }
}
