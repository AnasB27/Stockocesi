<?php

namespace App\Exceptions;

class StockException extends \Exception 
{
    public function __construct(
        string $message = "", 
        int $code = 0, 
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function __toString(): string 
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}