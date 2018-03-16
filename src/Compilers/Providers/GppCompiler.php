<?php

namespace Deplink\Compilers\Providers;

class GppCompiler extends GccCompiler
{
    /**
     * @var string
     */
    protected $cmd = "g++";
}
