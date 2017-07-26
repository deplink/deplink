<?php

namespace Deplink\Packages\ValueObjects;

class RepositoryObject
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $source;

    /**
     * RepositoryObject constructor.
     *
     * @param string $type
     * @param string $source
     */
    public function __construct($type, $source)
    {
        $this->type = $type;
        $this->source = $source;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return RepositoryObject
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     * @return RepositoryObject
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Convert raw array to object.
     *
     * @param array $repositories
     * @return RepositoryObject[]
     * @throws \InvalidArgumentException
     */
    public static function hydrate(array $repositories)
    {
        $result = [];
        foreach ($repositories as $repository) {
            $repository = (object)$repository;

            if (!isset($repository->type)) {
                throw new \InvalidArgumentException("Repository must contain 'type' key.");
            } else if (!isset($repository->src)) {
                throw new \InvalidArgumentException("Repository must contain 'source' key.");
            }

            $type = $repository->type;
            $source = $repository->src;

            $result[] = new RepositoryObject($type, $source);
        }

        return $result;
    }
}
