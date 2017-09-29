<?php

return [

    /**
     * List of compilers supported in the deplink.json file.
     */
    'providers' => [
        'gcc' => \Deplink\Compilers\Providers\GccCompiler::class,
    ],

];
