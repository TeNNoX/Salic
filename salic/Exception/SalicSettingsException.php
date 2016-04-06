<?php

namespace Salic\Exception;

class SalicSettingsException extends \Exception
{
    /**
     * @var string The file that caused this.
     */
    public $fileInfo;
    /**
     * @var mixed Some kind of json-encodable object for debugging purposes.
     */
    public $data;

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