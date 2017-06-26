<?php

namespace Deplink\Locks;

use Deplink\Locks\Exceptions\DuplicateLockEntryException;
use Deplink\Locks\Exceptions\NotFoundLockEntryException;

class LockFile implements \JsonSerializable
{
    /**
     * @var array
     */
    private $packages = [];

    /**
     * @param object|null $json Result of the json_decode function.
     */
    public function __construct($json = null)
    {
        $this->packages = !is_null($json) ? (array)$json->dependencies : [];
    }

    /**
     * @param string $package
     * @param string $version
     * @throws DuplicateLockEntryException
     */
    public function add($package, $version)
    {
        if (isset($this->packages[$package])) {
            $lockedVersion = $this->packages[$package];
            throw new DuplicateLockEntryException("Package '$package' ($version) already locked with version $lockedVersion.");
        }

        $this->packages[$package] = $version;
    }

    /**
     * @param string $package
     * @throws NotFoundLockEntryException
     */
    public function remove($package)
    {
        if (!isset($this->packages[$package])) {
            throw new NotFoundLockEntryException("Cannot remove '$package' from lock file (package not found).");
        }

        unset($this->packages[$package]);
    }

    /**
     * Get installed packages versions.
     *
     * @return array Package name (key) with installed version (value).
     */
    public function packages()
    {
        return $this->packages;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *  which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return [
            'dependencies' => new \ArrayObject($this->packages),
        ];
    }

    /**
     * Get human-readable json.
     *
     * @return string
     */
    public function getJson()
    {
        return json_encode($this, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
