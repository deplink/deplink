<?php

return [

    /**
     * List of repositories supported in the deplink.json file.
     */
    'providers' => [
        'local' => \Deplink\Repositories\Providers\LocalRepository::class,
        'remote' => \Deplink\Repositories\Providers\RemoteRepository::class,
    ],

    /**
     * Default repositories added at the end of the list of package repositories.
     */
    'defaults' => [
        [
            "type" => "remote",
            "src" => "https://repo.deplink.org",
        ],
    ],

];
