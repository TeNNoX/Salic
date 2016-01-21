<?php

namespace Salic\Settings;

use Salic\Exception\SalicSettingsException;
use Salic\Utils;

class PageSettings extends Settings
{

    /**
     * @var TranslatedString The title of the page
     */
    public $title;

    /**
     * @var string The template for this page
     */
    public $template;

    /**
     * @var array The list of blocks by area
     */
    public $areas;

    /**
     * @var array The list of variables
     */
    public $variables;

    private $pageKey;

    /**
     * @var self Cached instances (in array by pagekey)
     */
    protected static $cached;

    /**
     * @param string $pageKey The pagekey to get the settings for
     * @return PageSettings A cached or fresh instance of the wanted settings object
     */
    public static function get($pageKey)
    {
        if (!self::$cached)
            self::$cached = [];

        if (array_key_exists($pageKey, self::$cached))
            return self::$cached[$pageKey];
        return new self($pageKey);
    }

    public function blocks($area)
    {
        return $this->areas[$area];
    }

    public function __construct($pageKey)
    {
        $this->pageKey = $pageKey;
        Utils::mkdirs(self::baseDir . "data/$pageKey");
        $this->file = 'data/' . $pageKey . '/page.json';
        parent::__construct();
    }

    public function getDefault()
    {
        $defaultAreas = $this->getDefaultAreas();

        return ['title' => ucfirst($this->pageKey),  // generate string via uppercasing first letter of pageKey
            'template' => 'default',
            'areas' => $defaultAreas,  // empty areas array
            'variables' => []]; // emtpy variables array
    }

    public function parseFromJson($json)
    {
        $this->title = self::getTranslatedString('title', $json);
        $this->template = self::getString('template', $json, 'default');

        $this->areas = self::getDict('areas', $json, []);
        //TODO: parse blocks array to Block objects ?

        foreach ($this->areas as $area => $blocks) {
            if (!is_array($blocks))
                throw new SalicSettingsException("Blocklist for '$area' is not an array", $this->file . self::fis . "areas");
        }

        $this->variables = self::getDict('variables', $json, []);
    }

    public function validate()
    {
        // check if template exists
        $templateSettings = TemplateSettings::get();
        if (!$templateSettings->exists($this->template))
            throw new SalicSettingsException("Template '" . $this->template . "' not found", $this->file, $templateSettings->templates);

        // create empty areas that are not present
        $myTemplate = $templateSettings->sub($this->template);
        $templateAreas = $myTemplate['areas'];
        foreach ($templateAreas as $area) {
            if (!array_key_exists($area, $this->areas))
                $this->areas[$area] = [];
        }

        // check blocks list
        foreach ($this->areas as $areaKey => $blocks) {
            // check if area exists in template
            if (!in_array($areaKey, $templateAreas))
                throw new SalicSettingsException("Area '$areaKey' doesn't exist in the template", $this->file . self::fis . 'areas');

            $blockKeys = array(); // for duplicate checking

            foreach ($blocks as $i => $block) { // check all blocks
                self::getString('key', $block, $this->file . "areas>$areaKey>$i"); // use index, blockKey is not known yet

                // check for duplicate keys
                $blockKey = $block['key'];
                if (in_array($blockKey, $blockKeys))
                    throw new SalicSettingsException("Duplicate block blockKey '$blockKey'", $this->file . self::fis . "areas>$areaKey");
                $blockKeys[] = $blockKey;

                self::getString('type', $block, "areas>$areaKey>$blockKey"); // eg. 'templates.json:areas>main>intro'
                // TODO: check if block exists
            }
        }

        // check variables list
        $templateVars = $myTemplate['variables'];
        foreach ($this->variables as $var => $value) {
            // check if variable exists in template
            if (!array_key_exists($var, $templateVars))
                throw new SalicSettingsException("Variable '$var' doesn't exist in the template", $this->file . self::fis . 'variables');

            //TODO: variable value type checking
        }
    }


    /**
     * generate empty areas array from template
     * @param string $template The template name, if not default
     * @return array
     */
    private function getDefaultAreas($template = 'default')
    {
        $defaultAreas = array();
        foreach (TemplateSettings::data2($template)['areas'] as $area) {
            $defaultAreas[$area] = array(); // ['area1' => [], 'area2' => []]
        }
        return $defaultAreas;
    }
}