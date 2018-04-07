<?php

/**
 * PHP DI Definitions
 *
 * @link http://php-di.org/doc/php-definitions.html
 */
return [

    \GuzzleHttp\ClientInterface::class => function () {
        return new \GuzzleHttp\Client();
    },

];
