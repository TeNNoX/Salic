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
        //Utils::mkdirs(self::baseDir . "data/$pageKey");
        if(!is_dir(self::baseDir . "pages/$pageKey")) {
            throw new SalicSettingsException("No page config for: '$pageKey'", "pages/$pageKey");
        }
        $this->file = 'pages/' . $pageKey . '/page.json';
        parent::__construct();
    }

    public function getDefault()
    {
        $defaultAreas = $this->getDefaultAreas();
        $defaultTitle = ucfirst(end(explode('/', $this->pageKey))); // generate string via uppercasing first letter of pageKey: '[page1/]page2' => 'Page2'

        return ['title' => $defaultTitle,
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
        $myTemplate = $templateSettings->data($this->template);
        $templateAreas = $myTemplate->areas;
        foreach ($templateAreas as $area) {
            if (!array_key_exists($area, $this->areas))
                $this->areas[$area] = [];
        }

        // check blocks list
        foreach ($this->areas as $areaKey => &$blocks) {
            // check if area exists in template
            if (!in_array($areaKey, $templateAreas))
                throw new SalicSettingsException("Area '$areaKey' doesn't exist in the template", $this->file . self::fis . 'areas');

            $blockKeys = array(); // for duplicate checking

            foreach ($blocks as $i => &$block) { // check all blocks
                $block['key'] = self::getString('key', $block, null, "areas>$areaKey>$i"); // use index, blockKey is not known yet

                // check for duplicate keys
                $blockKey = $block['key'];
                if (in_array($blockKey, $blockKeys))
                    throw new SalicSettingsException("Duplicate blockKey '$blockKey'", $this->file . self::fis . "areas>$areaKey");
                $blockKeys[] = $blockKey;

                $block['type'] = self::getString('type', $block, null, "areas>$areaKey>$blockKey"); // eg. 'templates.json:areas>main>intro'
                if (!BlockSettings::exists2($block['type']))
                    throw new SalicSettingsException("Block '{$block['type']}' is not defined in blocks.json", $this->file . self::fis . "areas>$areaKey>$blockKey", BlockSettings::get()->blocktypes);

                $blockSettings = BlockSettings::data2($block['type']);
                if($blockSettings->subblocks === true) { // if the blocktype has variable subblocks, check if the count is given
                    $block['subblock-count'] = self::getInt('subblock-count', $block, 1, "areas>$areaKey>$blockKey");
                }

                // set to parsed value, TODO: move stuff like this to parseFromJson()
                $block['vars'] = self::getDict('vars', $block, [], "areas>$areaKey>$blockKey");
            }
        }

        // check variables list
        $templateVars = $myTemplate->variables;
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
        foreach (TemplateSettings::data2($template)->areas as $area) {
            $defaultAreas[$area] = array(); // ['area1' => [], 'area2' => []]
        }
        return $defaultAreas;
    }

    public static function getBlock($blocks, $key)
    {
        foreach ($blocks as $block) {
            if ($block['key'] == $key)
                return $block;
        }
        return null;
    }

    public static function pageExists($pagekey)
    {
        return is_dir(Settings::baseDir . "pages/$pagekey");
    }

    public static function listAvailablePages()
    {
        return glob(Settings::baseDir . "pages/*", GLOB_ONLYDIR);
    }
}