<?php

namespace Deplink\Tests\Console;

use Behat\Behat\Tester\Exception\PendingException;
use Deplink\Tests\BaseContext;

class CommandContext extends BaseContext
{
    /**
     * @When I run :cmd
     */
    public function iRun($cmd)
    {
        throw new PendingException();
    }

    /**
     * @Given the console output should contains :sentence
     */
    public function theConsoleOutputShouldContains($sentence)
    {
        throw new PendingException();
    }
}
