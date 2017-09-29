<?php

namespace Deplink\Compilers;

use Deplink\Compilers\Exceptions\BuildingPackageException;
use Deplink\Environment\Filesystem;
use Deplink\Environment\System;
use Deplink\Packages\LocalPackage;
use Deplink\Packages\PackageFactory;

/**
 * Compile, assembly and link package.
 */
class PackageBuildChain
{
    /**
     * @var Compiler
     */
    private $compiler;

    /**
     * @var LocalPackage
     */
    private $package;

    /**
     * @var boolean
     */
    private $debugMode;

    /**
     * @var string
     */
    private $workingDir;

    /**
     * @var string
     */
    private $previousWorkingDir;

    /**
     * @var string
     */
    private $dependenciesDir;

    /**
     * @var CompilerFactory
     */
    private $compilerFactory;

    /**
     * @var PackageFactory
     */
    private $packageFactory;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var System
     */
    private $system;

    /**
     * @param CompilerFactory $compilerFactory
     * @param PackageFactory $packageFactory
     * @param System $system
     * @param Filesystem $fs
     * @param string $dir
     */
    public function __construct(
        CompilerFactory $compilerFactory,
        PackageFactory $packageFactory,
        Filesystem $fs,
        System $system,
        $dir
    ) {
        $this->compilerFactory = $compilerFactory;
        $this->packageFactory = $packageFactory;
        $this->workingDir = $dir;
        $this->system = $system;
        $this->fs = $fs;
    }

    /**
     * Set directory which contains previously
     * built dependencies (deplinks directory).
     *
     * @param string $dir
     * @return $this
     */
    public function setDependenciesDir($dir)
    {
        $this->dependenciesDir = $dir;
        return $this;
    }

    /**
     * When enabled include debug symbols and intermediate files.
     *
     * @param bool $enabled
     * @return $this
     */
    public function debugMode($enabled = false)
    {
        $this->debugMode = $enabled;
        return $this;
    }

    /**
     * @throws BuildingPackageException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     */
    public function build()
    {
        $this->previousWorkingDir = $this->fs->getWorkingDir();
        $this->fs->setWorkingDir($this->workingDir);

        try {
            $this->package = $this->packageFactory->makeFromDir('.');
            $this->compiler = $this->compilerFactory->negotiate($this->package->getCompilers());

            $this->setCompilerOptions();
            $this->setCompilerMacros();
            $this->linkLibraries();
            $this->makeArtifacts();
        } catch (\Exception $e) {
            throw new BuildingPackageException("Exception occurred while building the {$this->package->getName()} package.", 0, $e);
        } finally {
            $this->fs->setWorkingDir($this->previousWorkingDir);
        }
    }

    private function setCompilerOptions()
    {
        foreach ($this->package->getSourceDirs() as $srcDir) {
            $files = $this->fs->listFiles($srcDir, '.*\.(c|cpp)');
            $this->compiler->addSourceFiles($files);
        }

        $this->compiler
            ->addIncludeDirs($this->package->getIncludeDirs())
            ->addIncludeDirs($this->dependenciesDir);

        if ($this->debugMode) {
            $this->compiler->withDebugSymbols()
                ->withIntermediateFiles();
        }
    }

    private function setCompilerMacros()
    {
        // TODO: ->addMacro()
    }

    private function linkLibraries()
    {
        // TODO: ->addSharedLibrary()
        // TODO: ->addStaticLibrary()
    }

    private function makeArtifacts()
    {
        foreach ($this->package->getArchitectures() as $arch) {
            $this->compiler->setArchitecture($arch);

            $outputFile = explode('/', $this->package->getName())[1];
            $outputPath = $this->fs->path($this->workingDir, 'build', $arch, $outputFile);

            if ($this->package->getType() === 'project') {
                $this->makeExecutableArtifacts($outputPath);
            } else {
                $this->makeLibraryArtifacts($outputPath);
            }
        }
    }

    private function makeExecutableArtifacts($outputPath)
    {
        $path = $this->system->toExePath($outputPath);

        $this->fs->touchDir($this->fs->getDirName($path));
        $this->compiler->buildExecutable($path);
    }

    private function makeLibraryArtifacts($outputPath)
    {
        // Static library
        if ($this->package->hasLinkingType('static')) {
            $path = $this->system->toStaticLibPath($outputPath);

            $this->fs->touchDir($this->fs->getDirName($path));
            $this->compiler->buildStaticLibrary($path);
        }

        // Shared library
        if ($this->package->hasLinkingType('dynamic')) {
            $path = $this->system->toSharedLibPath($outputPath);

            $this->fs->touchDir($this->fs->getDirName($path));
            $this->compiler->buildSharedLibrary($path);
        }
    }
}
