<?php

namespace Deplink\Environment;

class System
{
    const UNKNOWN = 0;

    const WINDOWS = 1;

    const LINUX = 2;

    const MAC = 4;

    const EXECUTABLE = [
        self::WINDOWS => '.exe',
        self::LINUX => '',
        self::MAC => '',
    ];

    const STATIC_LIBRARY = [
        self::WINDOWS => '.lib',
        self::LINUX => '.a',
        self::MAC => '.a',
    ];

    const SHARED_LIBRARY = [
        self::WINDOWS => '.dll',
        self::LINUX => '.so',
        self::MAC => '.so',
    ];

    const LIBRARY_PREFIX = [
        self::WINDOWS => '',
        self::LINUX => 'lib',
        self::MAC => 'lib',
    ];

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * FileNameResolver constructor.
     *
     * @param Filesystem $fs
     */
    public function __construct(Filesystem $fs)
    {
        $this->fs = $fs;
    }

    /**
     * Detect user platform.
     *
     * Returned values can be compared using logical "or" operator.
     * Example: if($system->getPlatform() == System::LINUX | System::MAC) { ... }
     *
     * @return int Either System::WINDOWS, System::LINUX, System::MAC or System::UNKNOWN.
     */
    public function getPlatform()
    {
        if (stristr(PHP_OS, 'DAR')) {
            return self::MAC;
        }

        if (stristr(PHP_OS, 'WIN')) {
            return self::WINDOWS;
        }

        if (stristr(PHP_OS, 'LINUX')) {
            return self::LINUX;
        }

        return self::UNKNOWN;
    }

    /**
     * Check whether user OS is Windows, Linux or Mac.
     *
     * @param int|int[] $platforms Either System::WINDOWS, System::LINUX, System::MAC or System::UNKNOWN.
     * @return bool
     */
    public function isPlatform($platforms)
    {
        $platforms = (array)$platforms;
        $platforms = array_reduce($platforms, function ($carry, $item) {
            return $carry | $item;
        }, 0);

        return $this->getPlatform() == $platforms;
    }

    /**
     * Get static library extension.
     *
     * @return string
     */
    public function getStaticLibExt()
    {
        return self::STATIC_LIBRARY[$this->getPlatform()];
    }

    public function getExePath($outputFile)
    {
        return $outputFile . $this->getExeExt();
    }

    /**
     * Get executable extension.
     *
     * @return string
     */
    public function getExeExt()
    {
        return self::EXECUTABLE[$this->getPlatform()];
    }

    /**
     * @param string $libFile
     * @return string
     */
    public function toSharedLibPath($libFile)
    {
        $dir = $this->fs->getDirName($libFile);

        $prefix = $this->getLibPrefix();
        $file = $this->fs->getFileName($libFile);
        $ext = $this->toSharedLibExt();

        return $this->fs->path($dir, $prefix . $file . $ext);
    }

    /**
     * @return string
     */
    public function getLibPrefix()
    {
        return self::LIBRARY_PREFIX[$this->getPlatform()];
    }

    /**
     * Get shared library extension.
     *
     * @return string
     */
    public function toSharedLibExt()
    {
        return self::SHARED_LIBRARY[$this->getPlatform()];
    }

    /**
     * @param string $libFile
     * @return string
     */
    public function toStaticLibPath($libFile)
    {
        $dir = $this->fs->getDirName($libFile);

        $prefix = $this->getLibPrefix();
        $file = $this->fs->getFileName($libFile);
        $ext = $this->getStaticLibExt();

        return $this->fs->path($dir, $prefix . $file . $ext);
    }
}
