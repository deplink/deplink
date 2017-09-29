<?php

namespace Deplink\Compilers;

use Deplink\Compilers\Exceptions\CompilerNotFoundException;
use Deplink\Environment\Config;
use Deplink\Packages\ValueObjects\CompilerConstraintObject;
use Deplink\Versions\VersionComparator;
use DI\Container;

class CompilerFactory
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
     * @var VersionComparator
     */
    private $comparator;

    /**
     * CompilerFactory constructor.
     *
     * @param Container $di
     * @param Config $config
     * @param VersionComparator $comparator
     */
    public function __construct(Container $di, Config $config, VersionComparator $comparator)
    {
        $this->di = $di;
        $this->config = $config;
        $this->comparator = $comparator;
    }

    /**
     * @param string $compiler Compiler name.
     * @return Compiler
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \InvalidArgumentException
     */
    public function make($compiler)
    {
        $provider = $this->config->get("compilers.providers.$compiler");

        return $this->di->make($provider);
    }

    /**
     * Get first compiler which is available
     * and match given version constraint.
     *
     * @param CompilerConstraintObject[] $compilers
     * @return Compiler
     * @throws CompilerNotFoundException
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \InvalidArgumentException
     */
    public function negotiate(array $compilers)
    {
        if (empty($compilers)) {
            return $this->makeAnyCompiler();
        }

        foreach ($compilers as $item) {
            $compiler = $this->make($item->getName());
            if (!$compiler->isSupported()) {
                continue; // Compiler not installed
            }

            $version = $compiler->getVersion();
            $constraint = $item->getVersionConstraint();
            if (!$this->comparator->satisfies($version, $constraint)) {
                continue; // Installed compiler not match required version
            }

            return $compiler;
        }

        $required = [];
        foreach ($compilers as $item) {
            $required[] = "{$item->getName()} ({$item->getVersionConstraint()})";
        }

        $required = implode(', ', $required);
        throw new CompilerNotFoundException("Deplink cannot find compiler proper to build package, one of $required is required.");
    }

    /**
     * Get one of the installed compilers.
     *
     * @return Compiler
     * @throws CompilerNotFoundException
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\ConfigNotExistsException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \InvalidArgumentException
     */
    public function makeAnyCompiler()
    {
        $providers = $this->config->get("compilers.providers");
        foreach ($providers as $_ => $provider) {
            /** @var Compiler $compiler */
            $compiler = $this->di->make($provider);

            if ($compiler->isSupported()) {
                return $compiler;
            }
        }

        throw new CompilerNotFoundException('Deplink cannot find any compiler on your computer. Make sure you have installed one.');
    }

    /**
     * @param string $dir Package root dir.
     * @return PackageBuildChain
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \InvalidArgumentException
     */
    public function makeBuildChain($dir)
    {
        return $this->di->make(PackageBuildChain::class, [
            'dir' => $dir,
        ]);
    }
}
