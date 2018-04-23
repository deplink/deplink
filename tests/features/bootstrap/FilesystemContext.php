<?php

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
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
     * @Given I should have directory :directory
     */
    public function iShouldHaveDirectory($directory)
    {
        Assert::assertDirectoryExists($directory);
    }

    /**
     * @Given I shouldn't have directory :directory
     */
    public function iShouldnTHaveDirectory($directory)
    {
        Assert::assertDirectoryNotExists($directory);
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

    /**
     * @Given file :file not exists
     */
    public function fileNotExists($file)
    {
        $this->fs->removeFile($file);
        Assert::assertFileNotExists($file);
    }

    /**
     * @Given the :file file should contains:
     */
    public function theFileShouldContains($file, PyStringNode $string)
    {
        $given = $this->fs->readFile($file);
        $given = preg_replace('/\s+/', ' ', $given);
        $expected = preg_replace('/\s+/', ' ', $string->getRaw());

        Assert::assertContains($expected, $given);
    }

    /**
     * @Given there is :file file with contents:
     */
    public function thereIsFileWithContents($file, PyStringNode $string)
    {
        $this->fs->writeFile($file, $string->getRaw());
    }

    /**
     * @Given remove :dir folder
     */
    public function removeFolder($dir)
    {
        $this->fs->removeDir($dir);
        Assert::assertDirectoryNotExists($dir);
    }

    /**
     * @Given I should have :count of files:
     */
    public function iShouldHaveOfFiles($count, TableNode $table)
    {
        $left = $count;
        foreach ($table as $row) {
            if ($this->fs->existsFile($row['path'])) {
                $left--;
            }
        }

        $found = $count - $left;
        Assert::assertLessThanOrEqual(0, $left, "Found $found of $count files, $left files left.");
    }
}
