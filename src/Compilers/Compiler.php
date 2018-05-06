<?php

namespace Deplink\Compilers;

interface Compiler
{
    /**
     * Check whether compiler is supported
     * and can be used to perform compilations.
     *
     * @return bool
     */
    public function isSupported();

    /**
     * @link http://semver.org/
     * @return string Semantic version.
     */
    public function getVersion();

    /**
     * @param bool $enabled
     * @return $this
     */
    public function withDebugSymbols($enabled = true);

    /**
     * @param bool $enabled
     * @return $this
     */
    public function withIntermediateFiles($enabled = true);

    /**
     * @param string $arch
     * @return $this
     */
    public function setArchitecture($arch);

    /**
     * @param string|string[] $files
     * @return $this
     */
    public function addSourceFiles($files);

    /**
     * Add directories in which compiler
     * will search for headers.
     *
     * @param string|string[] $dirs
     * @return $this
     */
    public function addIncludeDirs($dirs);

    /**
     * Register macro constraint.
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function addMacro($name, $value);

    /**
     * Register directory in which shared
     * libraries will be looking for.
     *
     * @param string $dir
     * @return $this
     */
    public function addLibraryDir($dir);

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
    public function addStaticLibrary($libFiles, $includeDirs = null);

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
    public function addSharedLibrary($libFiles, $includeDirs = null);

    /**
     * @param string $outputFile File path without extension.
     * @return string Path to the output file.
     */
    public function buildExecutable($outputFile);

    /**
     * @param string $outputFile File path without extension.
     * @return string Path to the output file.
     */
    public function buildStaticLibrary($outputFile);

    /**
     * @param string $outputFile File path without extension.
     * @return string Path to the output file.
     */
    public function buildSharedLibrary($outputFile);

    /**
     * Set default compiler arguments.
     *
     * @param string|string[] $args
     * @return $this
     */
    public function setDefaultArgs($args);

    /**
     * Reset to the initial configuration.
     *
     * @return $this
     */
    public function reset();
}
