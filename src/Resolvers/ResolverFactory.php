<?php

namespace Deplink\Resolvers;

use DI\Container;

class ResolverFactory
{
    /**
     * @var Container
     */
    private $di;

    /**
     * ResolverFactory constructor.
     *
     * @param Container $di
     */
    public function __construct(Container $di)
    {
        $this->di = $di;
    }

    /**
     * @return DependenciesTreeState
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \InvalidArgumentException
     */
    public function makeDependenciesTreeState()
    {
        return $this->di->make(DependenciesTreeState::class);
    }
}
