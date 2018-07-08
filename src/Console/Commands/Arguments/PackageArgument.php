<?php

namespace Deplink\Console\Commands\Arguments;

use Deplink\Console\Commands\Exceptions\InvalidLinkingTypeException;

class PackageArgument
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
     * @var string
     */
    private $linkingType;

    /**
     * @return bool
     */
    public function hasName()
    {
        return !empty($this->getName());
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
     * @return PackageArgument
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasVersion()
    {
        return !empty($this->getVersion());
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
     * @return PackageArgument
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasLinkingType()
    {
        return !empty($this->getLinkingType());
    }

    /**
     * @return string
     */
    public function getLinkingType()
    {
        return $this->linkingType;
    }

    /**
     * @param string $linkingType
     * @return PackageArgument
     * @throws InvalidLinkingTypeException
     */
    public function setLinkingType($linkingType)
    {
        if(!in_array($linkingType, ['static', 'dynamic'], true)) {
            throw new InvalidLinkingTypeException("Expecting either static or dynamic linking type, got '$linkingType'.");
        }

        $this->linkingType = $linkingType;
        return $this;
    }

    /**
     * @param string $argument
     * @return PackageArgument
     * @throws InvalidLinkingTypeException
     */
    public static function parse($argument)
    {
        $package = new PackageArgument();

        $parts = explode(':', $argument, 2);
        $package->setName($parts[0]);

        if(isset($parts[1])) {
            $constraint = explode(':', $parts[1], 2);
            $package->setVersion($constraint[0]);

            if(isset($constraint[1])) {
                $package->setLinkingType($constraint[1]);
            }
        }

        return $package;
    }
}
