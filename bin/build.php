<?php

// ATTENTION: Allow write-access for the phar archives,
//            open php.ini and set phar.readonly to off.

define('ROOT_DIR', __DIR__ . '/..');

// Load phar config file.
$config = (object)(require ROOT_DIR . '/config/phar.php');
$pharFile = ROOT_DIR . '/' . $config->output;

// If archive is not deleted before creating the new one
// then the size will only increase (incremental build).
if (file_exists($pharFile)) {
    unlink($pharFile);
}

// Archive project directory as phar file.
$archive = new Phar($pharFile);
$archive->buildFromDirectory(ROOT_DIR, $config->pattern);
$archive->setStub(file_get_contents(ROOT_DIR . '/bootstrap/phar.php'));
