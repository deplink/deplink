<?php

namespace Deplink\Dependencies;

use Deplink\Dependencies\Excpetions\DependencyNotExistsException;

class DependenciesCollection
{
    /**
     * @var array Package name (key) with object (value). Object contains 'version' and 'data' keys.
     */
    private $dependencies = [];

    /**
     * @param string $name
     * @param string $version
     * @param mixed $data
     */
    public function add($name, $version, $data = null)
    {
        $this->dependencies[$name] = (object)[
            'version' => $version,
            'data' => $data,
        ];
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

        if (!is_null($version) && $this->dependencies[$name]->version !== $version) {
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
     * @return string
     * @throws DependencyNotExistsException
     */
    public function getVersion($name)
    {
        if (!$this->has($name)) {
            throw new DependencyNotExistsException("The '$name' dependency not exists in the given collection.");
        }

        return $this->dependencies[$name]->version;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws DependencyNotExistsException
     */
    public function getData($name)
    {
        if (!$this->has($name)) {
            throw new DependencyNotExistsException("The '$name' dependency not exists in the given collection.");
        }

        return $this->dependencies[$name]->data;
    }
}
