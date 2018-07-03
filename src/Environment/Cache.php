<?php

namespace Deplink\Environment;

class Cache
{
    /**
     * @var System
     */
    protected $system;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @param System $system
     * @param Filesystem $fs
     */
    public function __construct(System $system, Filesystem $fs)
    {
        $this->system = $system;
        $this->fs = $fs;
    }

    /**
     * @param string|null $path
     * @return string
     */
    public function get($path = null)
    {
        if ($this->system->isPlatform(System::WINDOWS)) {
            return getenv('LOCALAPPDATA') . '/Deplink/' . $path;
        } else {
            return getenv('HOME') . '/.deplink/' . $path;
        }
    }

    /**
     * Query directory path using variables.
     *
     * Variables which contains illegal characters ([^a-zA-Z0-9-_./])
     * are replaced which hash stored in key-value dictionary.
     *
     * @param string $path
     * @param array $vars
     * @return string
     */
    public function query($path, array $vars = [])
    {
        foreach ($vars as $key => $value) {
            if ($this->containsIllegalChars($value)) {
                $value = $this->hash($key, $value);
            }

            $path = str_replace('{'. $key .'}', $value, $path);
        }

        return $this->get($path);
    }

    /**
     * @param string $value
     * @return bool
     */
    private function containsIllegalChars($value)
    {
        return preg_match('/^[a-zA-Z0-9-_\.\/]+$/', $value) !== 1;
    }

    /**
     * @param string $key
     * @param string $value
     * @return string New value.
     */
    private function hash($key, $value)
    {
        $dictJson = [];
        $dictFile = $this->get('.dict');
        if ($this->fs->existsFile($dictFile)) {
            $dictJson = json_decode($this->fs->readFile($dictFile), true);
        }

        // Get existing hash if exists.
        if(!isset($dictJson[$key])) {
            $dictJson[$key] = [];
        }

        if (isset($dictJson[$key][$value])) {
            return $dictJson[$key][$value];
        }

        // Generate new unique hash.
        $hashes = array_values($dictJson[$key]);
        $hash = md5($value);
        while(in_array($hash, $hashes)) {
            $hash = md5($hash);
        }

        // Save generated hash and return as new value.
        $dictJson[$key][$value] = $hash;
        $this->fs->writeFile($dictFile, json_encode($dictJson, JSON_PRETTY_PRINT));

        return $hash;
    }
}
