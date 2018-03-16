<?php

return [

    /**
     * List of compilers supported in the deplink.json file.
     */
    'providers' => [
        'g++' => \Deplink\Compilers\Providers\GppCompiler::class,
        'gcc' => \Deplink\Compilers\Providers\GccCompiler::class,
    ],

];
