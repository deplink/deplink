<?php

namespace Deplink\Tests\Environment;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Deplink\Tests\BaseContext;

class FilesystemContext extends BaseContext
{
    /**
     * @Given I am in :file directory
     */
    public function iAmInDirectory($dir)
    {
        throw new PendingException();
    }

    /**
     * @Then I should have file :file
     */
    public function iShouldHaveFile($file)
    {
        throw new PendingException();
    }

    /**
     * @Given I shouldn't have file :file
     */
    public function iShouldnTHaveFile($file)
    {
        throw new PendingException();
    }

    /**
     * @Given there is :file file
     */
    public function thereIsFile($file)
    {
        throw new PendingException();
    }

    /**
     * @Then /^I should have file "([^"]*)" which contains "((?:[^"\\]|\\.)*)"$/
     */
    public function iShouldHaveFileWhichContains($file, $sentence)
    {
        throw new PendingException();
    }

    /**
     * @Then I should have file :file with contents:
     */
    public function iShouldHaveFileWithContents($file, PyStringNode $contents)
    {
        throw new PendingException();
    }
}
