<?php

return [

    /**
     * List of constraint checked while installing new dependency
     * (version constraint is handled separately to increase performance).
     */
    'providers' => [
        \Deplink\Resolvers\Constraints\Providers\PlatformConstraint::class,
    ],

];
