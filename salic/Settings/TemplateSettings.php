<?php

namespace Salic\Settings;

use Salic\Exception\SalicSettingsException;
use Salic\Salic;


class TemplateSettings extends Settings
{

    public $default;
    public $templates;

    /**
     * @var self A cached instance of this, if available
     */
    protected static $cached;

    /**
     * @return self A cached or fresh instance of this
     */
    public static function get()
    {
        if (self::$cached)
            return self::$cached;
        return new self();
    }

    public function __construct()
    {
        $this->file = 'templates.json';
        parent::__construct();
    }

    /**
     * @param string $name The template name
     * @return array
     */
    public function data($name)
    {
        return $this->templates[$name];
    }

    /**
     * @param string $name The template name
     * @return array
     */
    public static function data2($name) // static version
    {
        return self::get()->templates[$name];
    }

    public function exists($name)
    {
        return array_key_exists($name, $this->templates);
    }

    public static function exists2($name) // static version
    {
        return array_key_exists($name, self::get()->templates);
    }

    public function getDefault()
    {
        return [
            'default' => [
                'file' => 'default.html.twig',
                'fields' => [],
                'variables' => [],
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
            $extraInfo = $name;  // fileInfo is e.g. 'templates.json:default'

            $template['file'] = self::getString('file', $template, $name . Salic::templateExtension, $extraInfo); //default = eg. 'templatename.html.twig'
            // TODO: check if template file exists

            //TODO: parse templates to Template objects
            //TODO: sanitize var/field/area/block names (no underscore?, no dot, ...)
            $template['fields'] = self::getList('fields', $template, [], $extraInfo);
            $template['variables'] = self::getDict('variables', $template, [], $extraInfo);
            $template['areas'] = self::getList('areas', $template, [], $extraInfo);
        }
    }
}