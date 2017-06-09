<?php

namespace Deplink\Tests\Environment;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Deplink\Tests\BaseContext;

class FilesystemContext extends BaseContext
{
    /**
     * @Given /^I am in "([^"]*)" directory$/
     */
    public function iAmInDirectory($dir)
    {
        throw new PendingException();
    }

    /**
     * @Then /^I should have file "([^"]*)"$/
     */
    public function iShouldHaveFile($file)
    {
        throw new PendingException();
    }

    /**
     * @Given /^I shouldn't have file "([^"]*)"$/
     */
    public function iShouldnTHaveFile($file)
    {
        throw new PendingException();
    }

    /**
     * @Given /^there is "([^"]*)" file$/
     */
    public function thereIsFile($file)
    {
        throw new PendingException();
    }

    /**
     * @Then /^I should have file "([^"]*)" which contains "((?:[^"\\]|\\.)*)":$/
     */
    public function iShouldHaveFileWhichContainsNameHelloWorld($file, $sentence)
    {
        throw new PendingException();
    }

    /**
     * @Then /^I should have file "([^"]*)" with contents:$/
     */
    public function iShouldHaveFileWithContents($file, PyStringNode $contents)
    {
        throw new PendingException();
    }
}
