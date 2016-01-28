<?php

namespace Salic\Exception;

class SalicSettingsException extends \Exception
{
    public $fileInfo;
    public $data; // some kind of json-encodable object for debugging purposes

    public function __construct($message, $fileInfo, $data = null)
    {
        parent::__construct($message);
        $this->fileInfo = $fileInfo;
        $this->data = $data;
    }

    public function __toString()
    {
        return __CLASS__ . ": {$this->message} [{$this->fileInfo}]\n";
    }
}