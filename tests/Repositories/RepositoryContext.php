<?php

namespace Deplink\Tests\Repositories;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Deplink\Tests\BaseContext;
use PHPUnit\Framework\Assert;

class RepositoryContext extends BaseContext
{
    /**
     * @Given local repository contains packages:
     */
    public function localRepositoryContainsPackages(TableNode $table)
    {
        foreach ($table as $row) {
            $packageName = $row['package'];
            $version = $row['version'];

            $path = $this->fs->path(self::ROOT_DIR, 'resources/repository', $packageName, 'deplink.json');
            Assert::assertFileExists($path);

            // Read file and replace a value of the "version" key.
            $json = json_decode($this->fs->readFile($path), true);
            $json['version'] = $version;

            $this->fs->writeFile($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    /**
     * @Given the :packageName package requires:
     */
    public function thePackageRequires($packageName, TableNode $table)
    {
        $path = $this->fs->path(self::ROOT_DIR, 'resources/repository', $packageName, 'deplink.json');
        Assert::assertFileExists($path);

        $json = json_decode($this->fs->readFile($path), true);

        // Overwrite dependencies versions
        foreach ($table as $row) {
            $packageName = $row['package'];
            $versionConstraint = $row['version'];
            if (isset($row['isDev']) && $row['isDev'] == 'true') {
                Assert::assertArrayHasKey($packageName, $json['dev-dependencies']);
                $json['dev-dependencies'][$packageName] = $versionConstraint;
            } else {
                Assert::assertArrayHasKey($packageName, $json['dependencies']);
                $json['dependencies'][$packageName] = $versionConstraint;
            }
        }

        $this->fs->writeFile($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Given the :packageName package contains:
     */
    public function thePackageContains($packageName, PyStringNode $string)
    {
        $path = $this->fs->path(self::ROOT_DIR, 'resources/repository', $packageName, 'deplink.json');
        Assert::assertFileExists($path);

        $fileContents = $this->fs->readFile($path);
        Assert::assertContains($string->getRaw(), $fileContents);
    }
}
