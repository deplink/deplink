<?php

namespace Deplink\Compilers\Providers;

use Deplink\Compilers\Compiler;
use Deplink\Compilers\Events\CompilerCommandEvent;
use Deplink\Compilers\Exceptions\BuildingPackageException;
use Deplink\Events\Bus;
use Symfony\Component\Process\Process;

abstract class BaseCompiler implements Compiler
{
    /**
     * @var string
     */
    protected $version;

    /**
     * @var bool
     */
    protected $supported = false;

    /**
     * @var bool
     */
    protected $debugSymbols = false;

    /**
     * @var bool
     */
    protected $intermediateFiles = false;

    /**
     * @var string
     */
    protected $architecture = 'unknown';

    /**
     * @var string[]
     */
    protected $sourceFiles = [];

    /**
     * @var string[]
     */
    protected $includeDirs = [];

    /**
     * @var array The macro name (key) with value (value).
     */
    protected $macros = [];

    /**
     * @var string[]
     */
    protected $linkStatic = [];

    /**
     * @var string[]
     */
    protected $linkDynamic = [];

    /**
     * @var string[]
     */
    protected $defaultArgs = [];

    /**
     * @var string[]
     */
    protected $librariesDirs = [];

    /**
     * @var Bus
     */
    protected $bus;

    public function  __construct(Bus $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Check whether compiler is supported
     * and can be used to perform compilations.
     *
     * @return bool
     */
    public function isSupported()
    {
        return $this->supported;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function withDebugSymbols($enabled = true)
    {
        $this->debugSymbols = $enabled;

        return $this;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function withIntermediateFiles($enabled = true)
    {
        $this->intermediateFiles = $enabled;

        return $this;
    }

    /**
     * @param string $arch
     * @return $this
     */
    public function setArchitecture($arch)
    {
        $this->architecture = $arch;

        return $this;
    }

    /**
     * @param string|string[] $files
     * @return $this
     */
    public function addSourceFiles($files)
    {
        $this->sourceFiles = array_merge(
            $this->sourceFiles,
            (array)$files
        );

        return $this;
    }

    /**
     * Register macro constraint.
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function addMacro($name, $value)
    {
        $this->macros[$name] = $value;

        return $this;
    }

    /**
     * Register directory in which shared
     * libraries will be looking for.
     *
     * @param string $dir
     * @return $this
     */
    public function addLibraryDir($dir)
    {
        $this->librariesDirs[] = $dir;

        return $this;
    }

    /**
     * Link static library.
     *
     * Library can be specified using name or as a path with lib name.
     * When path is provided then directory will be added to the linker
     * to looks in that directory for library files.
     *
     * @param string|string[] $libFiles File(s) without extension.
     * @param string|string[]|null $includeDirs
     * @return $this
     */
    public function addStaticLibrary($libFiles, $includeDirs = null)
    {
        $this->linkStatic = array_merge(
            $this->linkStatic,
            (array)$libFiles
        );

        if (!is_null($includeDirs)) {
            $this->addIncludeDirs($includeDirs);
        }

        return $this;
    }

    /**
     * Add directories in which compiler
     * will search for headers.
     *
     * @param string|string[] $dirs
     * @return $this
     */
    public function addIncludeDirs($dirs)
    {
        $this->includeDirs = array_merge(
            $this->includeDirs,
            (array)$dirs
        );

        return $this;
    }

    /**
     * Link shared library.
     *
     * Library can be specified using name or as a path with lib name.
     * When path is provided then directory will be added to the linker
     * to looks in that directory for library files.
     *
     * @param string|string[] $libFiles File(s) without extension.
     * @param string|string[]|null $includeDirs
     * @return $this
     */
    public function addSharedLibrary($libFiles, $includeDirs = null)
    {
        $this->linkDynamic = array_merge(
            $this->linkDynamic,
            (array)$libFiles
        );

        if (!is_null($includeDirs)) {
            $this->addIncludeDirs($includeDirs);
        }

        return $this;
    }

    /**
     * @param mixed $args,... Arguments combined with space.
     * @throws BuildingPackageException
     */
    protected function run(...$args)
    {
        $arrToString = function ($arg) {
            return is_array($arg) ? implode(' ', $arg) : $arg;
        };

        $command = implode(' ', array_map($arrToString, $args));
        $this->bus->emit(new CompilerCommandEvent($command));

        $process = new Process($command);
        $process->run(function ($type, $buffer) {
            echo "\r\n$buffer";
        });

        if(!$process->isSuccessful()) {
            throw new BuildingPackageException("The below command returns exit code {$process->getExitCode()}:\r\n$command", $process->getExitCode());
        }
    }

    /**
     * Set default compiler arguments.
     *
     * @param string|string[] $args
     * @return $this
     */
    public function setDefaultArgs($args)
    {
        $this->defaultArgs = (array)$args;
        return $this;
    }

    /**
     * Reset to the initial configuration.
     *
     * @return $this
     */
    public function reset()
    {
        $this->version = 'unknown';
        $this->debugSymbols = false;
        $this->intermediateFiles = false;
        $this->architecture = 'unknown';
        $this->sourceFiles = [];
        $this->includeDirs = [];
        $this->macros = [];
        $this->linkStatic = [];
        $this->linkDynamic = [];
        $this->librariesDirs = [];

        return $this;
    }
}
