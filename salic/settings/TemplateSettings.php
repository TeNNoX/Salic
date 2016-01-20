<?php

namespace Salic\Settings;

use Salic\Exception\SalicSettingsException;


class TemplateSettings extends Settings
{

    public $default;
    public $templates;

    protected static $cached;

    public static function get()
    {
        if (self::$cached)
            return self::$cached;
        return new static();
    }

    public function __construct()
    {
        $this->file = 'templates.json';
        parent::__construct();
    }

    public function sub($name)
    {
        return $this->templates[$name];
    }

    public function exists($name)
    {
        return array_key_exists($name, $this->templates);
    }

    public function getDefault()
    {
        return [
            'default' => [
                'file' => 'default.html.twig',
                'fields' => [],
                "areas" => [],
            ],
        ];
    }

    public function parseFromJson($json)
    {
        $this->templates = self::getDict(null, $json);

        $this->default = array_key_exists('default', $this->templates) ? 'default' : array_keys($this->templates)[0];
    }

    public function validate()
    {
        // check if default is in available
        if (!array_key_exists($this->default, $this->templates)) {// make sure the specified homepage exists
            throw new SalicSettingsException("Default template '{$this->default}' not found", $this->file);
        }

        foreach ($this->templates as $name => &$template) {
            $fileInfo = $this->file . $name;  // fileInfo is e.g. 'templates.json:default'

            self::getString('file', $template, null, $fileInfo);
            // TODO: check if template file exists

            if (!array_key_exists('fields', $template))
                $template['fields'] = [];
            if (!array_key_exists('areas', $template))
                $template['areas'] = [];

            //TODO: parse templates to Template objects
            self::getList('fields', $template, $fileInfo);
            self::getList('areas', $template, $fileInfo);
        }
    }
}