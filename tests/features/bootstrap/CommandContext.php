<?php

use Behat\Gherkin\Node\PyStringNode;
use PHPUnit\Framework\Assert;

class CommandContext extends BaseContext
{
    /**
     * Output of the latest executed command.
     *
     * @var string
     */
    protected $output;

    /**
     * Exit code of the latest executed command.
     *
     * @var int
     */
    protected $exitCode;

    /**
     * @When I run :cmd
     */
    public function iRun($cmd)
    {
        $this->output = [];
        $this->exitCode = -1;
        $cmd = $this->replaceSelfExecution($cmd);

        exec("$cmd 2>&1", $this->output, $this->exitCode);
        $this->output = implode(' ', $this->output);
    }

    /**
     * Replace deplink execution at the beginning of the command.
     *
     * @param string $cmd
     * @return string Modified command.
     */
    private function replaceSelfExecution($cmd)
    {
        $prefix = 'deplink ';

        // Check if command starts with the "deplink " prefix.
        if (strpos($cmd, $prefix) !== 0) {
            return $cmd;
        }

        // Replace "deplink" with "php path/to/deplink.php --no-ansi",
        // the --no-ansi option removes console output formatting
        // (formatting produces special symbols which breaks assertions).
        $deplinkCall = 'php ' . self::ROOT_DIR . '/bin/deplink.php --no-ansi ';

        // Replace all '/' and '\' to os-compatible path separator.
        $deplinkCall = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $deplinkCall);

        return $deplinkCall . substr($cmd, strlen($prefix));
    }

    /**
     * @Then command should exit with status code :exitCode
     */
    public function commandShouldExitWithStatusCode($exitCode)
    {
        Assert::assertEquals($exitCode, $this->exitCode);
    }

    /**
     * @Then command should not exit with status code :exitCode
     */
    public function commandShouldNotExitWithStatusCode($exitCode)
    {
        Assert::assertNotEquals($exitCode, $this->exitCode);
    }

    /**
     * @Then the console output should contains :sentence
     */
    public function theConsoleOutputShouldContains($sentence)
    {
        Assert::assertContains($sentence, $this->output);
    }

    /**
     * @Then the console output should contains:
     */
    public function theConsoleOutputShouldContains1(PyStringNode $string)
    {
        $given = preg_replace('/\s+/', ' ', $this->output);
        $expected = preg_replace('/\s+/', ' ', $string->getRaw());

        Assert::assertContains($expected, $given);
    }
}
