#!/usr/bin/env php
<?php

// ATTENTION: Allow write-access for the phar archives,
//            open php.ini and set phar.readonly to off.

Phar::mapPhar('deplink.phar');

$basePath = 'phar://' . __FILE__ . '/';
require $basePath . 'bin/deplink.php';

__HALT_COMPILER();
