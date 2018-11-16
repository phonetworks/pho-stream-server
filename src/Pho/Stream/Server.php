<?php

namespace Pho\Stream;

class Server extends  Jacwright\RestServer\RestServer
{
    public function __construct(string $mode = 'debug')
    {
        parent::__construct($mode);
        $this->initControllers();
    }

    private function initControllers()
    {
        $this->addClass("");
    }
}