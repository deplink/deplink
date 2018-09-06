<?php


namespace Deplink\Compilers\Events;


use Deplink\Events\Event;

class CompilerCommandEvent implements Event
{
    /**
     * @var string
     */
    private $command;

    /**
     * @param string $command
     */
    public function __construct($command)
    {
        $this->command = $command;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }
}