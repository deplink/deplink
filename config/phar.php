<?php

return [

    /**
     * Full output path for the phar archive
     * (including file name and extension).
     */
    'output' => 'bin/deplink.phar',

    /**
     * Regular expression that is used to filter the list of files.
     * Only file paths matching the regular expression will be included in the archive.
     *
     * @see Phar::buildFromDirectory
     * @link http://php.net/manual/en/phar.buildfromdirectory.php
     */
    'pattern' => '/^.*(\.php|\.schema\.json)$/',

];
