<?php

namespace Deplink\Compilers\Providers;

class GppCompiler extends GccCompiler
{
    /**
     * @var string
     */
    protected $cmd = "g++";

    /**
     * See gcc -x option.
     *
     * @var string
     */
    protected $langOption = "-x c++";
}
