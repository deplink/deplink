<?php

namespace Deplink\Dependencies;

use Deplink\Dependencies\Excpetions\DependencyNotExistsException;
use Deplink\Dependencies\ValueObjects\DependencyObject;
use Deplink\Packages\LocalPackage;
use Deplink\Packages\RemotePackage;
use Iterator;

class DependenciesCollection implements Iterator
{
    /**
     * @var DependencyObject[]
     */
    private $dependencies = [];

    /**
     * @param string $name
     * @param string $version
     * @param LocalPackage|null $local
     * @param RemotePackage|null $remote
     */
    public function add($name, $version, $local = null, $remote = null)
    {
        $this->dependencies[$name] = new DependencyObject(
            $name, $version, $local, $remote
        );
    }

    /**
     * Check if package exists, if version was provided
     * then also checks that versions are equal.
     *
     * @param string $name
     * @param string|null $version
     * @return boolean
     */
    public function has($name, $version = null)
    {
        if (!isset($this->dependencies[$name])) {
            return false;
        }

        if (!is_null($version) && $this->dependencies[$name]->getVersion() !== $version) {
            return false;
        }

        return true;
    }

    /**
     * Remove package if exists.
     *
     * @param string $name
     */
    public function remove($name)
    {
        if ($this->has($name)) {
            unset($this->dependencies[$name]);
        }
    }

    /**
     * @return string[]
     */
    public function getPackagesNames()
    {
        return array_keys($this->dependencies);
    }

    /**
     * @param string $name
     * @return DependencyObject
     * @throws DependencyNotExistsException
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new DependencyNotExistsException("The '$name' dependency not exists in the given collection.");
        }

        return $this->dependencies[$name];
    }

    /**
     * Add items to the collection from the specified collection.
     * Items which exists in collection will be overridden by new one.
     *
     * @param DependenciesCollection $collection
     * @return $this
     * @throws DependencyNotExistsException
     */
    public function merge(DependenciesCollection $collection)
    {
        foreach ($collection->getPackagesNames() as $name) {
            $this->dependencies[$name] = $collection->get($name);
        }

        return $this;
    }

    /**
     * Return the current element
     *
     * @link http://php.net/manual/en/iterator.current.php
     * @return DependencyObject Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return current($this->dependencies);
    }

    /**
     * Move forward to next element
     *
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        next($this->dependencies);
    }

    /**
     * Return the key of the current element
     *
     * @link http://php.net/manual/en/iterator.key.php
     * @return string Scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return key($this->dependencies);
    }

    /**
     * Checks if current position is valid
     *
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        $key = key($this->dependencies);
        return ($key !== null && $key !== false);
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        reset($this->dependencies);
    }
}
