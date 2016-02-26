<?php

namespace Salic\Settings;


class Template
{

    public $fields;
    public $areas;
    public $variables;

    /**
     * Template constructor.
     * @param $filename
     * @param array $fields
     * @param array $areas
     * @param array $variables
     */
    public function __construct($filename, $fields = [], $areas = [], $variables = [])
    {
        $this->filename = $filename;
        $this->fields = $fields;
        $this->areas = $areas;
        $this->variables = $variables;
    }
}