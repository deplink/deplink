#!/usr/bin/env php
<?php

Phar::mapPhar('deplink.phar');

$basePath = 'phar://' . __FILE__ . '/';
require $basePath . 'bin/deplink.php';

__HALT_COMPILER();
