<?php

namespace Deplink\Tests\Environment;

use Behat\Gherkin\Node\PyStringNode;
use Deplink\Tests\BaseContext;
use PHPUnit\Framework\Assert;

class FilesystemContext extends BaseContext
{
    /**
     * @Given I am in :file directory
     */
    public function iAmInDirectory($dir)
    {
        $this->fs->touchDir($dir);
        $this->fs->setWorkingDir($dir);
    }

    /**
     * @Then I should have file :file
     */
    public function iShouldHaveFile($file)
    {
        Assert::assertFileExists($file);
    }

    /**
     * @Given I shouldn't have file :file
     */
    public function iShouldnTHaveFile($file)
    {
        Assert::assertFileNotExists($file);
    }

    /**
     * @Given there is :file file
     */
    public function thereIsFile($file)
    {
        $this->fs->touchFile($file);
    }

    /**
     * @Then /^I should have file "([^"]*)" which contains "((?:[^"\\]|\\.)*)"$/
     */
    public function iShouldHaveFileWhichContains($file, $sentence)
    {
        $sentence = str_replace('\\"', '"', $sentence);

        Assert::assertFileExists($file);
        Assert::assertContains($sentence, $this->fs->readFile($file));
    }

    /**
     * @Then I should have file :file with contents:
     */
    public function iShouldHaveFileWithContents($file, PyStringNode $contents)
    {
        Assert::assertFileExists($file);
        Assert::assertContains($contents->getRaw(), $this->fs->readFile($file));
    }

    /**
     * @Given directory :dir exists
     */
    public function directoryExists($dir)
    {
        $this->fs->touchDir($dir);
    }
}
