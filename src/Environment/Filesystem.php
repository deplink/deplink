<?php

namespace Deplink\Environment;

use Deplink\Environment\Exceptions\FileCopyException;
use Deplink\Environment\Exceptions\InvalidPathException;
use Deplink\Environment\Exceptions\UnknownException;
use Exception;

/**
 * Manage local filesystem.
 * Use "/" as a directory separator.
 */
class Filesystem
{
    /**
     * @return string
     */
    public function getWorkingDir()
    {
        return $this->path(getcwd());
    }

    /**
     * @param string $dir
     * @throws InvalidPathException
     */
    public function setWorkingDir($dir)
    {
        if (!$this->existsDir($dir)) {
            throw new InvalidPathException("The specified '$dir' working directory is not accessible");
        }

        if (!chdir($dir)) {
            throw new InvalidPathException("Unknown exception occurred while setting '$dir' as working directory");
        }
    }

    /**
     * Read contents of the existing file.
     *
     * @param string $file
     * @return string
     * @throws InvalidPathException
     */
    public function readFile($file)
    {
        if (!$this->existsFile($file)) {
            throw new InvalidPathException("The '$file' file doesn't exists");
        }

        return file_get_contents($file);
    }

    /**
     * Alias for 'isFile'.
     *
     * @see isFile
     * @param string $file
     * @return bool
     */
    public function existsFile($file)
    {
        return $this->isFile($file);
    }

    /**
     * Check whether given file exists.
     *
     * @param string $file
     * @return True if file, false if directory or non existing path.
     */
    public function isFile($file)
    {
        return is_file($file);
    }

    /**
     * Create or overwrite file with given contents
     * (missing path directories will be created).
     *
     * @param string $file
     * @param string $contents
     * @throws InvalidPathException
     * @throws UnknownException
     */
    public function writeFile($file, $contents)
    {
        $this->touchFile($file);
        file_put_contents($file, $contents);
    }

    /**
     * Make file if not exists.
     *
     * @param string $file
     * @throws InvalidPathException
     * @throws UnknownException
     */
    public function touchFile($file)
    {
        // Throw if the given path is a directory.
        if ($this->isDir($file)) {
            throw new InvalidPathException("The '$file' directory already exists");
        }

        // Create file if not exists.
        if (!$this->existsFile($file)) {
            // Ensure that the given directory exists,
            // php 'touch' function do not create dirs.
            $this->touchDir(dirname($file));

            try {
                touch($file);
            } catch (Exception $e) {
                $scriptOwner = get_current_user();
                throw new UnknownException("Cannot create '$file' file. Please check if file name is valid and the '$scriptOwner' user have appropriate permissions");
            }
        }
    }

    /**
     * Check whether given directory exists.
     *
     * @param string $dir
     * @return True if directory, false if file or non existing path.
     */
    public function isDir($dir)
    {
        return is_dir($dir);
    }

    /**
     * Make directory if not exists.
     *
     * @param string $dir
     * @param int $mode See http://php.net/manual/en/function.mkdir.php
     * @throws InvalidPathException
     * @throws UnknownException
     */
    public function touchDir($dir, $mode = 0744)
    {
        // Throw if the given path is a file.
        if ($this->isFile($dir)) {
            throw new InvalidPathException("The '$dir' file already exists");
        }

        // Create directory if not exists.
        if (!$this->existsDir($dir)) {
            try {
                mkdir($dir, $mode, true);
            } catch (Exception $e) {
                $scriptOwner = get_current_user();
                throw new UnknownException("Cannot create '$dir' directory. Please check if dir name is valid and the '$scriptOwner' user have appropriate permissions");
            }
        }
    }

    /**
     * Alias for 'isDir'.
     *
     * @see isDir
     * @param string $dir
     * @return bool
     */
    public function existsDir($dir)
    {
        return $this->isDir($dir);
    }

    /**
     * Search files in directory and sub-directories
     * which name match the given pattern.
     *
     * @param string $dir
     * @param string $pattern Case-sensitive regex.
     * @return string[]
     * @throws InvalidPathException Directory not exists.
     */
    public function listFiles($dir, $pattern = '.*')
    {
        if (!$this->existsDir($dir)) {
            throw new InvalidPathException("Cannot list files because the '$dir' directory not exists");
        }

        $results = [];
        foreach (scandir($dir) as $name) {
            if ($name == '.' || $name == '..') {
                continue;
            }

            $path = $this->path($dir, $name);
            if ($this->isDir($path)) {
                // Merge results from sub-directories.
                $subResults = $this->listFiles($path, $pattern);
                $results = array_merge($results, $subResults);
            } else {
                // Add file to the results if file name match given pattern.
                if ($this->isFile($path) && preg_match("/^$pattern$/u", $name)) {
                    $results[] = $path;
                }
            }
        }

        return $results;
    }

    /**
     * Search sub-directories for the directory.
     *
     * @param string $dir
     * @param int $depth Minimum directory depth.
     * @return \string[]
     */
    public function listDirs($dir, $depth = 1)
    {
        if ($depth <= 0 || !$this->existsDir($dir)) {
            return [];
        }

        $results = [];
        foreach (scandir($dir) as $name) {
            if ($name == '.' || $name == '..') {
                continue;
            }

            $path = $this->path($dir, $name);
            if ($this->isDir($path)) {
                // And merge results from sub-directories.
                $subResults = $this->listDirs($path, $depth - 1);
                $results = array_merge($results, $subResults);

                // Add result if reached required depth.
                if ($depth == 1) {
                    $results[] = $path;
                }
            }
        }

        return $results;
    }

    /**
     * Get path relative to the working directory.
     *
     * @param string[] ...$parts
     * @return string
     */
    public function path(...$parts)
    {
        $protocol = '';
        $path = implode('/', $parts);

        // Detect and temporary remove protocol from path.
        if (preg_match('#^([a-z]+):[\\\/]{2}#', $path, $matches) == 1) {
            $path = substr($path, strlen($matches[0]));
            $protocol = $matches[1] . '://';
        }

        // Remove all groups of '/' or '\' symbols to single '/' symbol.
        $path = preg_replace('#([\\\/]+)#', '/', $path);

        return $protocol . $path;
    }

    /**
     * Remove directory or file.
     *
     * @param string $path
     * @throws InvalidPathException
     * @throws UnknownException
     */
    public function removePath($path)
    {
        if ($this->isFile($path)) {
            $this->removeFile($path);
        } else {
            $this->removeDir($path);
        }
    }

    /**
     * Remove file if exists.
     *
     * @param string $file
     * @throws InvalidPathException
     * @throws UnknownException
     */
    public function removeFile($file)
    {
        // Throw if user attempt to remove directory instead of file.
        if ($this->isDir($file)) {
            throw new InvalidPathException("Cannot remove '$file' file, directory with that name already exists");
        }

        // Skip if file not exists (eq. removed previously).
        if (!$this->existsFile($file)) {
            return;
        }

        // Remove file.
        try {
            unlink($file);
        } catch (Exception $e) {
            $scriptOwner = get_current_user();
            throw new UnknownException("Cannot remove '$file' file. Please check if other programs don't use this file and the '$scriptOwner' user have appropriate permissions");
        }
    }

    /**
     * Remove directory if exists.
     *
     * @param string $dir
     * @throws InvalidPathException
     * @throws UnknownException
     */
    public function removeDir($dir)
    {
        // Throw if user attempt to remove file instead of directory.
        if ($this->isFile($dir)) {
            throw new InvalidPathException("Cannot remove '$dir' directory, file with that name already exists");
        }

        // Skip if directory not exists (eq. removed previously).
        if (!$this->existsDir($dir)) {
            return;
        }

        // Empty directory before removing them.
        foreach (scandir($dir) as $name) {
            if ($name == '.' || $name == '..') {
                continue;
            }

            $path = $this->path($dir, $name);
            if ($this->isFile($path)) {
                $this->removeFile($path);
            } else {
                $this->removeDir($path);
            }
        }

        // Remove directory.
        try {
            rmdir($dir);
        } catch (Exception $e) {
            $scriptOwner = get_current_user();
            throw new UnknownException("Cannot remove '$dir' directory. Please check if the '$scriptOwner' user have appropriate permissions");
        }
    }

    /**
     * Get file name (including extensions) from path.
     *
     * @param string $file
     * @return string
     */
    public function getFileName($file)
    {
        $file = preg_replace('/[\\\\\/]+/', '/', $file);
        $parts = explode('/', $file);

        return array_pop($parts);
    }

    /**
     * Get dir from path pointing to the file.
     *
     * @param string $file
     * @return string
     */
    public function getDirName($file)
    {
        $file = preg_replace('/[\\\\\/]+/', '/', $file);
        $parts = explode('/', $file);

        // Remove file name part.
        array_pop($parts);

        return implode('/', $parts);
    }

    /**
     * @param string $from
     * @param string $to
     * @throws FileCopyException
     * @throws InvalidPathException
     * @throws UnknownException
     */
    public function copyFile($from, $to)
    {
        $dir = $this->getDirName($to);
        $this->touchDir($dir);

        if (!$this->existsFile($from)) {
            throw new InvalidPathException("Cannot copy '$from' to '$to' because source file not exists.");
        }

        if (!copy($from, $to)) {
            throw new FileCopyException("Failed to copy '$from' to '$to' (unknown reason). Please check directories permissions.");
        }
    }
}
