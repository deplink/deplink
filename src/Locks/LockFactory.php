<?php

namespace Deplink\Locks;

use Deplink\Environment\Filesystem;
use Deplink\Validators\JsonValidator;
use DI\Container;

class LockFactory
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var JsonValidator
     */
    private $validator;

    /**
     * @var Container
     */
    private $di;

    /**
     * Factory constructor.
     *
     * @param Container $di
     * @param Filesystem $fs
     * @param JsonValidator $validator
     */
    public function __construct(Container $di, Filesystem $fs, JsonValidator $validator)
    {
        $this->fs = $fs;
        $this->validator = $validator;
        $this->di = $di;
    }

    /**
     * Read lock file or create empty lock if file not exists.
     *
     * @param string $file
     * @return LockFile
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Validators\Exceptions\JsonDecodeException
     * @throws \Deplink\Validators\Exceptions\ValidationException
     * @throws \InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
     */
    public function makeFromFileOrEmpty($file)
    {
        if (!$this->fs->existsFile($file)) {
            return $this->makeEmpty();
        }
        $content = $this->fs->readFile($file);
        return $this->makeFromJson($content);
    }

    /**
     * @return LockFile
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \InvalidArgumentException
     */
    public function makeEmpty()
    {
        return $this->di->make(LockFile::class);
    }

    /**
     * @param string|object $json
     * @return LockFile
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Deplink\Environment\Exceptions\InvalidPathException
     * @throws \Deplink\Validators\Exceptions\JsonDecodeException
     * @throws \Deplink\Validators\Exceptions\ValidationException
     * @throws \InvalidArgumentException
     * @throws \Seld\JsonLint\ParsingException
     */
    public function makeFromJson($json)
    {
        $this->validator->validate(
            $json,
            $this->fs->readFile(ROOT_DIR . '/resources/schemas/package-lock.schema.json')
        );

        if (!is_object($json)) {
            $json = json_decode($json);
        }

        return $this->di->make(LockFile::class, [
            'json' => $json,
        ]);
    }
}
