<?php

namespace Jag\Broadcaster\GooglePubSub\Exceptions;

use Exception;

class KeyNotFoundException extends Exception
{
    protected $path;

    public function __construct($path)
    {
        parent::__construct('Key not found at ' . $path);
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }
}
