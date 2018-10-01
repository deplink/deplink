<?php


namespace Deplink\Events;


/**
 * Events bus is responsible for spawning and
 * listening for all kinds of events.
 */
class Bus
{
    /**
     * Key is an event class and
     * value is an array of handlers.
     *
     * @var array
     */
    private $handlers = [];

    /**
     * @param Event $event
     */
    public function emit(Event $event)
    {
        $ns = get_class($event);
        if(!isset($this->handlers[$ns])) {
            return;
        }

        foreach($this->handlers[$ns] as $handler) {
            $result = $handler($event);
            if($result === false) {
                return;
            }
        }
    }

    /**
     * @param object|string $event Event class instance or namespace.
     * @param \Closure $handler Receives event as a first argument and
     *                          can return false to stop event propagation.
     */
    public function listen($event, \Closure $handler)
    {
        if($event instanceof Event) {
            $event = get_class($event);
        }

        if(!isset($this->handlers[$event])) {
            $this->handlers[$event] = [];
        }

        $this->handlers[$event][] = $handler;
    }
}