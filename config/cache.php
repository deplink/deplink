<?php

return [

    /**
     * Directories are given relative to the location:
     * - Windows: %LOCALAPPDATA%/Deplink
     * - Linux: ~/.deplink
     */
    'packages' => [
        'remote' => [
            'dir' => 'packages/remote/{url}',
        ],
    ],

];
