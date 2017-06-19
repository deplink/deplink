<?php

namespace Deplink\Packages;

class PackageFactory
{
    /**
     * @return LocalPackage
     */
    public function makeEmpty()
    {
        return new LocalPackage();
    }
}
