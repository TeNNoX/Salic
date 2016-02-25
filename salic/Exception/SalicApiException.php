<?php

namespace Salic\Exception;

class SalicApiException extends \Exception
{
    public $debugData; // some kind of json-encodable object for debugging purposes

    public function __construct($message, $debugData = null)
    {
        parent::__construct($message);
        $this->debugData = $debugData;
    }
}