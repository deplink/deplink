<?php

namespace Deplink\Packages;

use DI\Container;

class PackageFactory
{
    /**
     * @var Container
     */
    private $di;

    /**
     * PackageFactory constructor.
     */
    public function __construct(Container $di)
    {
        $this->di = $di;
    }

    /**
     * @return LocalPackage
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \InvalidArgumentException
     */
    public function makeEmpty()
    {
        return $this->di->make(LocalPackage::class);
    }

    /**
     * @param string $path
     * @param string|null $version
     * @return LocalPackage
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \InvalidArgumentException
     */
    public function makeFromDir($path, $version = null)
    {
        $obj = $this->di->make(LocalPackage::class);
        $obj->setSrc($path);

        if (!is_null($version)) {
            $obj->setVersion($version);
        }

        return $obj;
    }
}
