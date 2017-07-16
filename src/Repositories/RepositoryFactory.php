<?php

namespace Deplink\Repositories;

use Deplink\Environment\Config;
use Deplink\Packages\PackageFactory;
use Deplink\Packages\ValueObjects\RepositoryObject;
use Deplink\Repositories\Exceptions\UnknownRepositoryTypeException;
use Deplink\Repositories\Providers\EmptyRepository;
use Deplink\Repositories\Providers\LocalRepository;
use DI\Container;

class RepositoryFactory
{
    /**
     * @var Container
     */
    private $di;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var PackageFactory
     */
    private $packageFactory;

    /**
     * Factory constructor.
     *
     * @param Container $di
     * @param Config $config
     * @param PackageFactory $packageFactory
     */
    public function __construct(Container $di, Config $config, PackageFactory $packageFactory)
    {
        $this->di = $di;
        $this->config = $config;
        $this->packageFactory = $packageFactory;
    }

    /**
     * Make repository with given type.
     * The types are defined in the config/repositories.php file.
     *
     * @param string $type
     * @param string $src
     * @return Repository
     * @throws UnknownRepositoryTypeException
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \InvalidArgumentException
     */
    private function make($type, $src)
    {
        if (!$this->config->has("repositories.$type")) {
            throw new UnknownRepositoryTypeException("Unrecognized '$type' repository type.");
        }

        $namespace = $this->config->get("repositories.$type");
        $repository = $this->di->make($namespace, [
            'src' => $src,
        ]);

        return $repository;
    }

    /**
     * Create a searchable collection of repositories from plain objects.
     *
     * @param RepositoryObject[] $repositories
     * @return RepositoriesCollection
     * @throws UnknownRepositoryTypeException
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \InvalidArgumentException
     */
    public function makeCollection(array $repositories)
    {
        $collection = $this->di->make(RepositoriesCollection::class);
        foreach ($repositories as $repository) {
            $interactiveRepository = $this->make(
                $repository->getType(),
                $repository->getSource()
            );

            $collection->add($interactiveRepository);
        }

        return $collection;
    }

    /**
     * Make local repository.
     *
     * @param string $dir
     * @return LocalRepository
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \InvalidArgumentException
     */
    public function makeLocal($dir)
    {
        $repository = $this->di->make(LocalRepository::class, [
            'src' => $dir,
        ]);

        return $repository;
    }

    /**
     * Make empty repository.
     *
     * @return EmptyRepository
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \InvalidArgumentException
     */
    public function makeEmpty()
    {
        return $this->di->make(EmptyRepository::class);
    }
}
