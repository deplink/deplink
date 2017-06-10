<?php

namespace Deplink\Packages\ValueObjects;

class PackageNameObject
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @param $name
     */
    public function __construct($name)
    {
        $this->set($name);
    }

    /**
     * @return string
     */
    public function get()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return PackageNameObject
     * @throws \InvalidArgumentException
     */
    public function set($name)
    {
        if (!preg_match('#^[a-z0-9-]+/[a-z0-9-]+$#', $name)) {
            throw new \InvalidArgumentException("Invalid '$name' package name, use org/package format (use only letters, digits and dashes)");
        }

        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    function __toString()
    {
        return $this->get();
    }
}
