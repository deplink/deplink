<?php

namespace Deplink\Repositories;

use Deplink\Packages\RemotePackage;
use Deplink\Repositories\Exceptions\PackageNotFoundException;

/**
 * Class responsible for searching packages
 * in the collection of repositories.
 */
class RepositoriesCollection
{
    /**
     * @var Repository[]
     */
    private $repositories = [];

    /**
     * Add new repository to the collection.
     * Repositories are searched in order in which they have been added.
     *
     * @param Repository $repo
     */
    public function add(Repository $repo)
    {
        $this->repositories[] = $repo;
    }

    /**
     * @param string $package
     * @return bool
     */
    public function has($package)
    {
        foreach ($this->repositories as $repository) {
            if ($repository->has($package)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get package from the first repository containing it.
     *
     * @param string $package
     * @return RemotePackage
     * @throws PackageNotFoundException
     */
    public function find($package)
    {
        foreach ($this->repositories as $repository) {
            if ($repository->has($package)) {
                return $repository->get($package);
            }
        }

        $repositoriesCount = count($this->repositories);
        throw new PackageNotFoundException("The '$package' package was not found in the repositories collection (containing $repositoriesCount repositories).'");
    }
}
