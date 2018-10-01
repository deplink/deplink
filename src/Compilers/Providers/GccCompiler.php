<?php

namespace Deplink\Compilers\Providers;

use Deplink\Compilers\Exceptions\CompilerNotFoundException;
use Deplink\Compilers\Exceptions\UnknownCompilerVersionException;
use Deplink\Environment\Filesystem;
use Deplink\Environment\System;
use Deplink\Events\Bus;

/**
 * @link https://gcc.gnu.org/
 * @link http://www.mingw.org/ (Windows)
 * @link https://cygwin.com/ (Windows)
 */
class GccCompiler extends BaseCompiler
{
    /**
     * @var string
     */
    protected $cmd = "gcc";

    /**
     * See gcc -x option.
     *
     * @var string
     */
    protected $langOption = "-x c";

    /**
     * Console options required by the specified architectures.
     *
     * @var array
     */
    const ARCHITECTURE_OPTIONS = [
        'x86' => '-m32',
        'x64' => '-m64',
    ];

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var System
     */
    protected $system;

    /**
     * GccCompiler constructor.
     *
     * @param Filesystem $fs
     * @param System $system
     * @param Bus $bus
     */
    public function __construct(Filesystem $fs, System $system, Bus $bus)
    {
        $this->fs = $fs;
        $this->system = $system;
        $this->prepare();

        parent::__construct($bus);
    }

    /**
     * Search gcc in system and set
     * required flags and version number.
     */
    private function prepare()
    {
        try {
            $this->getVersion();
            $this->supported = true;
        } catch (\Exception $e) {
            $this->supported = false;
        }
    }

    /**
     * @link http://semver.org/
     * @return string Semantic version.
     * @throws CompilerNotFoundException
     * @throws UnknownCompilerVersionException
     */
    public function getVersion()
    {
        $output = [];
        $exitCode = 0;

        // Example output:
        // ---------------------------------------------------------------------------
        // gcc (tdm64-1) 5.1.0
        // Copyright (C) 2015 Free Software Foundation, Inc.
        // This is free software; see the source for copying conditions.  There is NO
        // warranty; not even for MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
        // ---------------------------------------------------------------------------
        // gcc (Ubuntu 5.4.0-6ubuntu1~ 16.04.4) 5.4.0 20160609
        // Copyright (C) 2015 Free Software Foundation, Inc.
        // This is free software; see the source for copying conditions.  There is NO
        // warranty; not even for MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
        // ---------------------------------------------------------------------------
        exec("{$this->cmd} --version", $output, $exitCode);
        if ($exitCode != 0) {
            throw new CompilerNotFoundException("The {$this->cmd} compiler couldn't be found. Check if the {$this->cmd} is added to your path environment variables.");
        }

        $matches = [];
        if (preg_match('/[0-9]+\.[0-9]+\.[0-9]+/', $output[0], $matches) != 1) {
            throw new UnknownCompilerVersionException("The {$this->cmd} compiler was found, but failed at establishing the compiler version. Please open the issue and attach result of the '{$this->cmd} --version', thanks!");
        }

        return $matches[0];
    }

    /**
     * @param string $arch
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setArchitecture($arch)
    {
        $available = array_keys(self::ARCHITECTURE_OPTIONS);
        if (!in_array($arch, $available)) {
            throw new \InvalidArgumentException("Architecture '$arch' isn't supported.");
        }

        parent::setArchitecture($arch);
        return $this;
    }

    /**
     * @param string $outputFile File path without extension.
     * @return string Path to the output file.
     * @throws \Deplink\Compilers\Exceptions\BuildingPackageException
     */
    public function buildExecutable($outputFile)
    {
        $outputPath = $this->system->toExePath($outputFile);

        $this->run($this->cmd,
            $this->langOption,
            $this->sourceFiles,
            self::ARCHITECTURE_OPTIONS[$this->architecture],
            ['-o', $outputPath],
            $this->debugSymbols ? '-g' : [],
            $this->intermediateFiles ? '-save-temps=obj' : [],
            $this->getMacrosCommandOptions(),
            $this->getLibrariesCommandOptions(),
            $this->getIncludeDirsCommandOptions(),
            $this->getDefaultArgs()
        );

        return $outputPath;
    }

    /**
     * Get macro defines gcc options.
     *
     * @link https://gcc.gnu.org/onlinedocs/gcc/Preprocessor-Options.html#Preprocessor-Options
     * @return array
     */
    private function getMacrosCommandOptions()
    {
        $results = [];
        foreach ($this->macros as $name => $value) {
            $value = str_replace('"', '\"', $value);
            $results[] = '-D';
            $results[] = "$name=\"$value\"";
        }

        return $results;
    }

    /**
     * Get static and shared libraries gcc options.
     *
     * @link https://gcc.gnu.org/onlinedocs/gcc/Link-Options.html#Link-Options
     * @return array
     */
    private function getLibrariesCommandOptions()
    {
        $libraries = array_merge(
            $this->linkStatic,
            $this->linkDynamic
        );

        // Search libraries in build directory.
        //
        // Searching for libraries in dependency build directory
        // cannot be performed because compiler can find and use
        // library for different OS (compiler search lib by name).
        $results = [];
        foreach($this->librariesDirs as $libDir) {
            $results[] = '-L';
            $results[] = $libDir;
        }

        foreach ($libraries as $libPath) {
            $libName = $this->fs->getFileName($libPath);

            $results[] = '-l';
            $results[] = escapeshellarg($libName);
        }

        return $results;
    }

    /**
     * Get include dirs gcc options.
     *
     * @link https://gcc.gnu.org/onlinedocs/gcc/Directory-Options.html#Directory-Options
     * @return array
     */
    private function getIncludeDirsCommandOptions()
    {
        $results = [];
        foreach ($this->includeDirs as $dir) {
            $results[] = '-I';
            $results[] = escapeshellarg($dir);
        }

        return $results;
    }

    /**
     * @param string $outputFile File path without extension.
     * @return string Path to the output file.
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Environment\Exceptions\UnknownException
     * @throws \Deplink\Compilers\Exceptions\BuildingPackageException
     */
    public function buildStaticLibrary($outputFile)
    {
        $outputPath = $this->system->toStaticLibPath($outputFile);
        $objFiles = $this->buildObjectFiles(
            $this->fs->getDirName($outputFile)
        );

        // Library file
        $this->run('ar',
            // "r" means to insert with replacement,
            // "c" means to create a new archive,
            // and "s" means to write an index.
            'rcs',
            $outputPath,
            $objFiles
        );

        return $outputPath;
    }

    /**
     * @return string[]
     */
    public function getDefaultArgs()
    {
        if (!empty($this->defaultArgs)) {
            return $this->defaultArgs;
        }

        $args = ['-Wall', '-O3'];
        if (!$this->system->isPlatform(System::WINDOWS)) {
            $args[] = '-fPIC';
        }

        return $args;
    }

    /**
     * @param string $dir
     * @return string[]
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Environment\Exceptions\UnknownException
     * @throws \Deplink\Compilers\Exceptions\BuildingPackageException
     */
    private function buildObjectFiles($dir)
    {
        $objects = [];
        foreach($this->sourceFiles as $sourceFile) {
            $path = $this->fs->path($dir, $this->fs->truncateExtension($sourceFile) .'.o');
            $this->fs->touchDir($this->fs->getDirName($path));

            $this->run($this->cmd,
                '-c', // compile and assemble, but do not link
                $this->langOption,
                $sourceFile,
                ['-o', $path],
                $this->debugSymbols ? '-g' : [],
                $this->intermediateFiles ? '-save-temps=obj' : [],
                self::ARCHITECTURE_OPTIONS[$this->architecture],
                $this->getMacrosCommandOptions(),
                $this->getIncludeDirsCommandOptions(),
                $this->getDefaultArgs()
            );

            $objects[] = $path;
        }

        return $objects;
    }

    /**
     * @param string $outputFile File path without extension.
     * @return string Path to the output file.
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Environment\Exceptions\UnknownException
     * @throws \Deplink\Compilers\Exceptions\BuildingPackageException
     */
    public function buildSharedLibrary($outputFile)
    {
        $outputPath = $this->system->toSharedLibPath($outputFile);
        $objFiles = $this->buildObjectFiles(
            $this->fs->getDirName($outputFile)
        );

        // Library file
        $this->run($this->cmd,
            '-shared',
            $objFiles,
            ['-o', $outputPath],
            self::ARCHITECTURE_OPTIONS[$this->architecture],
            $this->getMacrosCommandOptions(),
            $this->getLibrariesCommandOptions(),
            $this->getDefaultArgs()
        );

        return $outputPath;
    }
}
