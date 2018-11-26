<?php

namespace Pho\Stream\Exception;

use Rakit\Validation\ErrorBag;

class ValidationFailedException extends \Exception
{
    private $errorBag;

    public function __construct(ErrorBag $errorBag)
    {
        $this->errorBag = $errorBag;
    }

    public function getErrorBag()
    {
        return $this->errorBag;
    }
}
